
<?php
/**
 * includes/functions.php — shared helpers
 */
require_once __DIR__ . '/db.php';

/* -------------------------- General helpers -------------------------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ------------------------------ CSRF -------------------------------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
        if (!$ok) {
            http_response_code(400);
            die('Invalid CSRF token');
        }
    }
}

/* ----------------------------- Settings ----------------------------- */
function get_setting(PDO $pdo, string $key, $default=null){
    $st = $pdo->prepare('SELECT `value` FROM settings WHERE `key`=?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v !== false ? $v : $default;
}
function set_setting(PDO $pdo, string $key, string $value): void {
    $st = $pdo->prepare(
        "INSERT INTO settings(`key`,`value`) VALUES(?,?)
         ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)"
    );
    $st->execute([$key, $value]);
}

/* ------------------------------ Audit ------------------------------- */
function log_action(
    PDO $pdo, ?int $user_id, string $action, string $entity,
    ?int $entity_id, string $desc = '', $old = null, $new = null
){
    $st = $pdo->prepare(
        'INSERT INTO audit_log(user_id,action,entity,entity_id,description,old_data,new_data)
         VALUES(?,?,?,?,?,?,?)'
    );
    $st->execute([
        $user_id, $action, $entity, $entity_id, $desc,
        $old ? json_encode($old) : null,
        $new ? json_encode($new) : null
    ]);
}

/* --------------------------- Stock helpers -------------------------- */
function low_stock_threshold_for(PDO $pdo, array $product): int {
    $global = (int)get_setting($pdo, 'low_stock_default', 5);
    return isset($product['low_stock_threshold']) && $product['low_stock_threshold'] !== null
        ? (int)$product['low_stock_threshold']
        : $global;
}
function is_low_stock(PDO $pdo, array $product): bool {
    // Compare as float to preserve decimal stock precision
    return (float)$product['stock_qty'] < low_stock_threshold_for($pdo, $product);
}
function get_low_stock_count(PDO $pdo): int {
    $global = (int)get_setting($pdo, 'low_stock_default', 5);
    $sql = 'SELECT COUNT(*) FROM products p
            WHERE p.is_active=1
              AND p.stock_qty < COALESCE(p.low_stock_threshold, ?)';
    $st = $pdo->prepare($sql);
    $st->execute([$global]);
    return (int)$st->fetchColumn();
}
function apply_stock_change(PDO $pdo, int $product_id, float $delta): void {
    // Use float delta so weighted items (e.g., 0.5 kg) are handled correctly
    $st = $pdo->prepare(
        'UPDATE products
         SET stock_qty = GREATEST(0, stock_qty + ?), updated_at=NOW()
         WHERE id=?'
    );
    $st->execute([(float)$delta, $product_id]);
}

/* --------------- Categories helper (auto-create by name) ------------ */
function ensure_category_exists(PDO $pdo, string $name): ?int {
    $name = trim($name);
    if ($name === '') return null;

    // Try existing
    $st = $pdo->prepare('SELECT id FROM categories WHERE name=? LIMIT 1');
    $st->execute([$name]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;

    // Create (handle unique race)
    try {
        $ins = $pdo->prepare('INSERT INTO categories(name) VALUES(?)');
        $ins->execute([$name]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        $st = $pdo->prepare('SELECT id FROM categories WHERE name=? LIMIT 1');
        $st->execute([$name]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }
}

/* -------------------- Product code generator ------------------------ */
/**
 * Generate next product code with prefix+zero-padded number.
 * Uses settings.product_seq as an atomic counter.
 *
 * Call this ONLY when you actually need to reserve a code (e.g. on save).
 */
if (!function_exists('generate_product_code')) {
    function generate_product_code(PDO $pdo, string $prefix = 'P', int $pad = 4): string {
        try {
            // Ensure settings row exists (value stored as string).
            $ins = $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(:k,'0') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
            $ins->execute([':k' => 'product_seq']);

            // Atomically increment the counter
            $up = $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(:k,1)
                                 ON DUPLICATE KEY UPDATE `value` = CAST(`value` AS UNSIGNED) + 1");
            $up->execute([':k' => 'product_seq']);

            // Read the current value
            $st = $pdo->prepare("SELECT CAST(`value` AS UNSIGNED) AS seq FROM settings WHERE `key` = :k LIMIT 1");
            $st->execute([':k' => 'product_seq']);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $seq = ($row && isset($row['seq'])) ? (int)$row['seq'] : 0;

            return $prefix . str_pad((string)$seq, $pad, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            // Fallback: find max numeric suffix in products and increment
            try {
                $like = $prefix . '%';
                $sql = "SELECT code FROM products WHERE code LIKE ? ORDER BY id DESC LIMIT 1";
                $st2 = $pdo->prepare($sql);
                $st2->execute([$like]);
                $r2 = $st2->fetch(PDO::FETCH_ASSOC);
                $seq = 0;
                if ($r2 && !empty($r2['code'])) {
                    // strip non-digits
                    if (preg_match('/(\d+)$/', $r2['code'], $m)) $seq = (int)$m[1];
                }
            } catch (Throwable $e2) {
                $seq = (int)date('YmdHis');
            }
            $next = $seq + 1;
            return $prefix . str_pad((string)$next, $pad, '0', STR_PAD_LEFT);
        }
    }
}

/**
 * Find the next available product code by scanning existing codes.
 * This does NOT modify any settings/counters — it simply returns a unique code.
 *
 * Example: prefix="P", pad=4 -> P0001, P0002, ...
 */
if (!function_exists('get_next_available_product_code')) {
    function get_next_available_product_code(PDO $pdo, string $prefix = 'P', int $pad = 4): string {
        $prefixLike = $prefix . '%';
        // Find the max numeric suffix present (use REGEXP and extract if DB supports)
        // We attempt to get the latest code and parse the numeric suffix.
        $st = $pdo->prepare("SELECT code FROM products WHERE code LIKE ? ORDER BY id DESC LIMIT 1");
        $st->execute([$prefixLike]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $seq = 0;
        if ($row && !empty($row['code'])) {
            if (preg_match('/(\d+)$/', $row['code'], $m)) {
                $seq = (int)$m[1];
            }
        }

        // Start from next integer and loop until unique (defensive)
        $tries = 0;
        do {
            $seq++;
            $candidate = $prefix . str_pad((string)$seq, $pad, '0', STR_PAD_LEFT);
            $chk = $pdo->prepare("SELECT 1 FROM products WHERE code = ? LIMIT 1");
            $chk->execute([$candidate]);
            $exists = (bool)$chk->fetchColumn();
            $tries++;
            // safety: if something went wrong, break after many tries
            if ($tries > 10000) {
                throw new RuntimeException('Unable to generate unique product code (too many attempts).');
            }
        } while ($exists);

        return $candidate;
    }
}

/* ----------------------- Transactions (core) ------------------------ */
/**
 * Create a purchase/sale/refund transaction with optional discounts and sale_mode.
 *
 * @param array $items Each item: [
 *    'product_id' => int,
 *    'qty' => int|float,
 *    'unit_price' => float,
 *    'discount_type' => 'percent'|'amount'|null,
 *    'discount_value' => float|null,
 *    'price_tier' => 'retail'|'wholesale'|null
 * ]
 * @param string|null $extra_discount_type 'percent'|'amount'|null (invoice-level)
 * @param float|null  $extra_discount_value
 * @param string|null $sale_mode 'retail'|'wholesale'|null (header tag)
 * @return int transaction id
 */
function create_transaction(
    PDO $pdo, string $type, int $user_id, string $date, string $notes,
    ?int $supplier_id, ?int $customer_id, array $items,
    ?string $extra_discount_type = null, ?float $extra_discount_value = null,
    ?string $sale_mode = null
): int {

    if (!in_array($type, ['sale','purchase','refund'], true)) {
        throw new InvalidArgumentException('Invalid transaction type');
    }

    // 🔒 Refund must reference original transaction
    if ($type === 'refund' && empty($notes)) {
        throw new InvalidArgumentException('Refund must reference original transaction');
    }

    $pdo->beginTransaction();
    try {
        /* ---------------- Header ---------------- */
        $prefix = strtoupper(substr($type, 0, 1));
        $ref_no = $prefix . '_' . date('Ymd_His');

        $st = $pdo->prepare("
            INSERT INTO transactions
              (type, user_id, date, notes, supplier_id, customer_id, ref_no,
               extra_discount_type, extra_discount_value, sale_mode, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
        ");
        $st->execute([
            $type, $user_id, $date, $notes, $supplier_id, $customer_id, $ref_no,
            $extra_discount_type ?: null,
            $extra_discount_value !== null ? (float)$extra_discount_value : null,
            $sale_mode ?: null
        ]);

        $tx_id = (int)$pdo->lastInsertId();

        /* ------------- Prepared statements ------------- */
        $insLine   = $pdo->prepare("
            INSERT INTO transaction_items
              (transaction_id, product_id, qty, unit_price, discount_type, discount_value, price_tier, cost_at_sale)
            VALUES (?,?,?,?,?,?,?,?)
        ");

        $updPlus   = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, updated_at=NOW() WHERE id=?");
        $updMinus  = $pdo->prepare("
    UPDATE products 
    SET stock_qty = stock_qty - ?, updated_at=NOW() 
    WHERE id=? AND stock_qty >= ?
");

        $selStock  = $pdo->prepare("SELECT stock_qty FROM products WHERE id=?");
        $selCost   = $pdo->prepare("SELECT cost_price FROM products WHERE id=?");

        // 🔒 Refund protection queries
        $checkRefund = $pdo->prepare("
            SELECT COUNT(*) FROM transactions WHERE type='refund' AND notes LIKE ?
        ");
        $selSoldQty = $pdo->prepare("
            SELECT SUM(qty) FROM transaction_items WHERE transaction_id=? AND product_id=?
        ");

        /* ------------- Prevent double refund ------------- */
        //if ($type === 'refund') {
            //$checkRefund->execute(['%' . $notes . '%']);
            //if ($checkRefund->fetchColumn() > 0) {
                //throw new Exception('This transaction has already been refunded');
           // }
        //}

        /* ---------------- Line items ---------------- */
        foreach ($items as $it) {
            $pid   = (int)($it['product_id'] ?? 0);
            $qty   = (float)($it['qty'] ?? 0);
            $price = (float)($it['unit_price'] ?? 0);
            $dt    = $it['discount_type'] ?? null;
            $dv    = ($it['discount_value'] ?? '') !== '' ? (float)$it['discount_value'] : null;
            $tier  = $it['price_tier'] ?? null;

            if ($pid <= 0 || $qty <= 0) continue;

            if ($dt === 'percent' && $dv !== null) $dv = max(0, min(100, $dv));
            if ($dt === 'amount'  && $dv !== null) $dv = max(0, $dv);

            /* 🔒 SALE: prevent overselling */
            if ($type === 'sale') {
                $selStock->execute([$pid]);
                $currentStock = (float)$selStock->fetchColumn();

                if ($currentStock < $qty) {
                    throw new Exception("Insufficient stock for product ID {$pid}");
                }
            }

            /* 🔒 REFUND: validate refund qty */
            if ($type === 'refund') {
                // Extract original TX ID from notes (e.g., "Refund for TX_ID=123")
                $origTxId = (int) filter_var($notes, FILTER_SANITIZE_NUMBER_INT);

                $selSoldQty->execute([$origTxId, $pid]);
                $soldQty = (float)$selSoldQty->fetchColumn();

                if ($qty > $soldQty) {
                    throw new Exception("Refund quantity exceeds sold quantity for product ID {$pid}");
                }
            }

            /* Cost snapshot */
            $costSnap = null;
            if ($type === 'sale' || $type === 'refund') {
                $selCost->execute([$pid]);
                $costSnap = (float)$selCost->fetchColumn();
            }

            /* Insert line */
            $insLine->execute([
                $tx_id, $pid, $qty, $price,
                $dt ?: null, $dv, $tier, $costSnap
            ]);

$transactionItemId = $pdo->lastInsertId();

/* -------- STOCK UPDATE + LOGGING -------- */

if ($type === 'purchase') {

    $updPlus->execute([$qty, $pid]);

    $selStock->execute([$pid]);
    $afterStock = (float)$selStock->fetchColumn();

    logStockMovement(
    $pdo,
    $pid,
    'purchase',
    $qty,
    'transaction',
    $tx_id,
    'Supplier purchase',
    $afterStock,
    'Purchase'
);
}

elseif ($type === 'sale') {

    $updMinus->execute([$qty, $pid, $qty]);

    if ($updMinus->rowCount() === 0) {
        throw new Exception("Stock error: insufficient stock (race condition).");
    }

    $selStock->execute([$pid]);
    $afterStock = (float)$selStock->fetchColumn();

    logStockMovement(
    $pdo,
    $pid,
    'sale',
    -$qty,
    'transaction',
    $tx_id,
    'POS sale',
    $afterStock,
    'POS'
);
}

elseif ($type === 'refund') {

    $updPlus->execute([$qty, $pid]);

    $selStock->execute([$pid]);
    $afterStock = (float)$selStock->fetchColumn();

    logStockMovement(
    $pdo,
    $pid,
    'refund',
    $qty,
    'transaction',
    $tx_id,
    'Sale refund',
    $afterStock,
    'Refund'
);
}


/* Create batch for purchase */
if ($type === 'purchase') {

    $expiry = $it['expiry_date'] ?? null;

    $batchStmt = $pdo->prepare("
        INSERT INTO product_batches
        (product_id, transaction_item_id, batch_no, quantity, remaining_qty, cost_price, expiry_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $prodCodeStmt = $pdo->prepare("SELECT code FROM products WHERE id=?");
    $prodCodeStmt->execute([$pid]);
    $productCode = $prodCodeStmt->fetchColumn();

    $batchCode = strtoupper($productCode . '-' . date('Ymd', strtotime($date)));

    $batchStmt->execute([
        $pid,
        $transactionItemId,
        $batchCode,
        $qty,
        $qty,
        $price,
        !empty($expiry) ? $expiry : null
    ]);
}

} // ✅ CLOSE FOREACH HERE (VERY IMPORTANT)

        /* ---------------- Audit ---------------- */
        log_action(
            $pdo, $user_id, 'create', 'transactions', $tx_id,
            "$type recorded", null, [
                'items' => $items,
                'extra_discount_type' => $extra_discount_type,
                'extra_discount_value' => $extra_discount_value,
                'sale_mode' => $sale_mode
            ]
        );

        $pdo->commit();
        return $tx_id;

    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
function clearExpiredReservations($pdo)
{
    $stmt = $pdo->query("
        SELECT id, product_id, quantity
        FROM reservations
        WHERE status='reserved'
        AND expires_at IS NOT NULL
        AND expires_at < NOW()
    ");

    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {

        $pdo->beginTransaction();

        // restore stock reservation
        $pdo->prepare("
            UPDATE products
            SET reserved_qty = reserved_qty - ?
            WHERE id = ?
        ")->execute([$r['quantity'], $r['product_id']]);

        // mark reservation expired
        $pdo->prepare("
            UPDATE reservations
            SET status='expired'
            WHERE id = ?
        ")->execute([$r['id']]);

        $pdo->commit();
    }
}
function logStockMovement(
    PDO $pdo,
    int $productId,
    string $type,
    float $qty,
    ?string $refType = null,
    ?int $refId = null,
    ?string $note = null,
    ?float $stockAfter = null,
    ?string $source = null
): void {

    $stmt = $pdo->prepare("
        INSERT INTO inventory_movements
        (product_id, movement_type, quantity, stock_after, ref_type, ref_id, note, source)
        VALUES (?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $productId,
        $type,
        $qty,
        $stockAfter,
        $refType,
        $refId,
        $note,
        $source
    ]);
}
function recordInventoryMovement($conn, $product_id, $type, $qty) {

    $conn->begin_transaction();

    try {

        // get stock
        $stmt = $conn->prepare("SELECT stock_qty, reserved_qty FROM products WHERE id=? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $stock = $result['stock_qty'];
        $reserved = $result['reserved_qty'];
        $available = $stock - $reserved;

        // validate stock deduction
        if (in_array($type, ['sale','damage','transfer']) && $qty > $available) {
            throw new Exception("Not enough available stock");
        }

        // insert movement record
        $stmt = $conn->prepare("
            INSERT INTO inventory_movements 
            (product_id, movement_type, quantity, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("isi", $product_id, $type, $qty);
        $stmt->execute();

        // update stock
        if ($type == 'purchase' || $type == 'return') {
            $stmt = $conn->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id=?");
        } else {
            $stmt = $conn->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id=?");
        }

        $stmt->bind_param("ii", $qty, $product_id);
        $stmt->execute();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        die($e->getMessage());
    }
}