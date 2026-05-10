<?php
ob_start();
//sale_add.php — Loyverse-style POS
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);

$pdo = getDB();
check_csrf();

/* ================= LOAD DATA ================= */
$products = $pdo->query("
  SELECT id, code, name, stock_qty, unit, sold_by,
         sell_price, wholesale_price,
         promo_discount_type, promo_discount_value,
         category_id
  FROM products
  WHERE is_active = 1
  ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$customers  = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* index products */
$pindex = [];
foreach ($products as $p) {
  $pindex[(int)$p['id']] = $p;
}

/* ================= TICKETS ================= */
if (!isset($_SESSION['tickets'])) {
  $_SESSION['tickets'] = [];
}

/* delete ticket */
if (isset($_GET['delete_ticket'])) {
  foreach ($_SESSION['tickets'] as $k => $t) {
    if ($t['id'] === $_GET['delete_ticket']) {
      unset($_SESSION['tickets'][$k]);
      $_SESSION['tickets'] = array_values($_SESSION['tickets']);
      break;
    }
  }
  header('Location: sale_add.php');
  exit;
}

/* load ticket */
$initialTicket = null;
if (isset($_GET['load_ticket'])) {
  foreach ($_SESSION['tickets'] as $t) {
    if ($t['id'] === $_GET['load_ticket']) {
      $initialTicket = $t;
      break;
    }
  }
}

/* ================= POST HANDLER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $action = $_POST['action'] ?? 'save_sale';
    $items  = $_POST['items'] ?? [];

    if (!$items) {
      $_SESSION['error'] = 'No items added to sale';
      header('Location: sale_add.php');
      exit;
    }

    foreach ($items as $it) {
      if ((float)($it['qty'] ?? 0) <= 0) {
        $_SESSION['error'] = 'Quantity must be greater than zero';
        header('Location: sale_add.php');
        exit;
      }
    }

    $customer_id = ($_POST['customer_id'] ?? '') !== '' ? (int)$_POST['customer_id'] : null;
    $date        = $_POST['date'] ?? date('Y-m-d');
    $notes       = trim($_POST['notes'] ?? '');
    $sale_mode   = ($_POST['sale_mode'] ?? 'retail') === 'wholesale' ? 'wholesale' : 'retail';

    $inv_disc_type  = $_POST['inv_discount_type'] ?? null;
    $inv_disc_value = ($_POST['inv_discount_value'] ?? '') !== '' ? (float)$_POST['inv_discount_value'] : null;

    if (!in_array($inv_disc_type, ['percent','amount',null], true)) {
      $inv_disc_type = null;
      $inv_disc_value = null;
    }
    if ($inv_disc_value !== null && $inv_disc_value < 0) {
      $inv_disc_value = null;
    }

    /* normalize raw items */
    $rawItems = [];
    foreach ($items as $it) {
      $rawItems[] = [
        'product_id' => (int)($it['product_id'] ?? 0),
        'qty' => (float)($it['qty'] ?? 0),
        'discount_type' => $it['discount_type'] ?? null,
        'discount_value' => ($it['discount_value'] ?? '') !== '' ? (float)$it['discount_value'] : null
      ];
    }

    /* SAVE TICKET */
    if ($action === 'save_ticket') {
      $_SESSION['tickets'][] = [
        'id' => bin2hex(random_bytes(8)),
        'created_at' => date('c'),
        'date' => $date,
        'customer_id' => $customer_id,
        'sale_mode' => $sale_mode,
        'notes' => $notes,
        'inv_discount_type' => $inv_disc_type,
        'inv_discount_value' => $inv_disc_value,
        'items' => $rawItems
      ];

      $_SESSION['success'] = 'Ticket saved successfully';
      header('Location: sale_add.php');
      exit;
    }

    /* BUILD CLEAN SALE ITEMS */
    $clean = [];
    foreach ($rawItems as $r) {
      $pid = (int)$r['product_id'];
      if ($pid <= 0 || !isset($pindex[$pid])) continue;

      $prod = $pindex[$pid];

      $qty = $prod['sold_by'] === 'weight'
        ? max(0.25, round($r['qty'] * 4) / 4)
        : max(1, (int)round($r['qty']));

      $unit = $sale_mode === 'wholesale'
        ? (float)($prod['wholesale_price'] ?: $prod['sell_price'])
        : (float)$prod['sell_price'];

      $clean[] = [
        'product_id' => $pid,
        'qty' => $qty,
        'unit_price' => round($unit, 2),
        'discount_type' => $r['discount_type'],
        'discount_value' => $r['discount_value'],
        'price_tier' => $sale_mode
      ];
    }

    if (!$clean) {
      $_SESSION['error'] = 'Please add at least one valid item';
      header('Location: sale_add.php');
      exit;
    }
    /* ================= STOCK VALIDATION ================= */
foreach ($clean as $item) {

  $stmt = $pdo->prepare("SELECT name, stock_qty FROM products WHERE id = ?");
  $stmt->execute([$item['product_id']]);
  $prod = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$prod) {
    $_SESSION['error'] = "Product not found.";
    header('Location: sale_add.php');
    exit;
  }

  // ❌ BLOCK negative or insufficient stock
  if ($prod['stock_qty'] <= 0) {
    $_SESSION['error'] = "Cannot sell '{$prod['name']}' — stock is 0 or negative.";
    header('Location: sale_add.php');
    exit;
  }

  if ($prod['stock_qty'] < $item['qty']) {
    $_SESSION['error'] = "Not enough stock for '{$prod['name']}'. Available: {$prod['stock_qty']}";
    header('Location: sale_add.php');
    exit;
  }
}

    create_transaction(
  $pdo,
  'sale',
  current_user()['id'],
  $date,
  $notes,
  null,
  $customer_id,
  $clean,
  $inv_disc_type,
  $inv_disc_value,
  $sale_mode
);


/* ================= DEDUCT INVENTORY FROM BATCHES ================= */

/* ================= DEDUCT INVENTORY FROM BATCHES ================= */

foreach ($clean as $item) {

    $productId = (int)$item['product_id'];
    $qtyToDeduct = (float)$item['qty'];

    if ($qtyToDeduct <= 0) continue;

    // oldest batch first
    $batchStmt = $pdo->prepare("
        SELECT id, remaining_qty
        FROM product_batches
        WHERE product_id = ?
          AND remaining_qty > 0
        ORDER BY expiry_date ASC, id ASC
    ");

    $batchStmt->execute([$productId]);

    $batches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($batches as $batch) {

        if ($qtyToDeduct <= 0) break;

        $available = (float)$batch['remaining_qty'];

        if ($available <= 0) continue;

        $deduct = min($available, $qtyToDeduct);

        $newQty = $available - $deduct;

        // update batch quantity
        $upd = $pdo->prepare("
            UPDATE product_batches
            SET remaining_qty = ?
            WHERE id = ?
        ");

        $upd->execute([$newQty, $batch['id']]);

        $qtyToDeduct -= $deduct;
    }
}


$_SESSION['success'] = 'Sale recorded successfully';
header('Location: sale_add.php');
exit;

  } catch (Throwable $e) {

    $_SESSION['error'] = $e->getMessage();

    header('Location: sale_add.php');
    exit;
  }
}


/* ---------------- Initial items for JS if ticket loaded -------------- */
$initialItems = [];
if ($initialTicket) {
  foreach ($initialTicket['items'] as $it) {
    $pid = (int)($it['product_id'] ?? 0);
    if ($pid <= 0 || !isset($pindex[$pid])) continue;
    $prod = $pindex[$pid];
    // unit price from DB (display only) — prefer posted unit_price in ticket if present
    $unit = ($initialTicket['sale_mode'] ?? 'retail') === 'wholesale' ? (float)$prod['wholesale_price'] : (float)$prod['sell_price'];
    $initialItems[] = [
      'product_id' => $pid,
      'qty' => (string)($it['qty'] ?? '1'),
      'unit_price' => number_format($unit,2,'.',''),
      'price_tier' => $initialTicket['sale_mode'] ?? 'retail',
      'discount_type' => $it['discount_type'] ?? null,
      'discount_value' => isset($it['discount_value']) ? $it['discount_value'] : null
    ];
  }
}

/* ---------------- Render UI ---------------- */
?>
<style>


/* ===== PAGE LAYOUT ===== */

.pos-wrapper{
    display:flex;
    gap:24px;
    align-items:flex-start;
    width:100%;
}

.pos-left{
    width:45%;
    min-width:420px;
    border:1px solid #dee2e6;
    border-radius:12px;
    padding:16px;
    background:#fff;
}

.pos-right{
    width:55%;
    min-width:420px;
}

.ticket{
    width:100%;
    background:#fff;
    border:1px solid #dee2e6;
    border-radius:12px;
    padding:18px;
}

/* ===== PRODUCT GRID ===== */

.product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
    gap:12px;
}

.product-card{
    border:1px solid #e2e6ea;
    border-radius:10px;
    padding:10px;
    cursor:pointer;
    background:#fafbfc;
    transition:all .15s;
}

.product-card:hover{
    border-color:#0d6efd;
    box-shadow:0 4px 10px rgba(0,0,0,0.08);
}

.product-title{
    font-weight:600;
    font-size:14px;
}

.product-meta{
    font-size:12px;
    color:#6c757d;
}

/* ===== CATEGORY ICON ===== */

.cat-icon{
    position:absolute;
    top:10px;
    left:10px;
    width:28px;
    height:28px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:12px;
    font-weight:700;
}

/* ===== TICKET TABLE ===== */

#ticketTable{
    width:100%;
    table-layout:fixed;
}

#ticketTable th,
#ticketTable td{
    vertical-align:middle;
    padding:6px;
}

#ticketTable th:nth-child(1),
#ticketTable td:nth-child(1){
    width:45%;
}

#ticketTable th:nth-child(2),
#ticketTable td:nth-child(2){
    width:90px;
    text-align:right;
}

#ticketTable th:nth-child(3),
#ticketTable td:nth-child(3){
    width:140px;
    text-align:right;
}

#ticketTable th:nth-child(4),
#ticketTable td:nth-child(4){
    width:120px;
    text-align:right;
}

#ticketTable th:nth-child(5),
#ticketTable td:nth-child(5){
    width:80px;
    text-align:right;
}

/* ===== DISCOUNT INPUTS ===== */

.disc-select{
    width:60px;
    display:inline-block;
}

.disc-input{
    width:70px;
    display:inline-block;
}

/* ===== QTY INPUT ===== */

.qty-input{
    width:80px;
    text-align:right;
}

/* ===== TOTALS ===== */

#ticketGrandTotal{
    font-size:28px;
    font-weight:700;
    color:#0d6efd;
}

/* ===== SEARCH BAR ===== */

.pos-top-card{
    border:1px solid #dee2e6;
    border-radius:10px;
    padding:12px;
    background:#fff;
}

.small-muted{
    font-size:12px;
    color:#6c757d;
}

/* ===== MOBILE ===== */

@media (max-width:900px){

.pos-wrapper{
flex-direction:column;
}

.pos-left,
.pos-right{
width:100%;
min-width:100%;
}

}


</style>

<div class="lr-top">

  <div>
    <h4 class="mb-0">Sale (OUT)</h4>
  </div>
  <div class="text-end">
  </div>
</div>

<!-- 💡 New Layout: Custom Flex POS Split -->
<div class="pos-wrapper">
  
  <!-- Left Side (Product Grid Area) -->
  <div class="pos-left">
    <div class="pos-top-card">
      <div class="d-flex mb-2 align-items-center">
        <input id="productSearch" class="form-control me-2" placeholder="Search product code or name..." style="flex:1;">
        <div>
          <div class="btn-group" role="group" aria-label="Sale Mode">
            <button type="button" class="btn btn-sm btn-outline-primary active" data-mode="retail" id="btnRetail">Retail</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-mode="wholesale" id="btnWholesale">Wholesale</button>
          </div>
        </div>
      </div>

      <div class="lr-cats" id="categoriesBar">
        <label for="categorySelect" class="">Category:</label>
        <select id="categorySelect" class="form-select form-select-sm" style="max-width:300px;">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div id="productGrid" class="product-grid" style="margin-top:12px;">
      <!-- JS renders tiles -->
    </div>
  </div>

  <!-- Right Side (Ticket / Checkout) -->
  <div class="pos-right">
    <div class="ticket">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="small-muted">Customer</div>
          <select class="form-select form-select-sm" id="ticketCustomer">
            <option value="">— walk-in —</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="text-end">
          <div class="small-muted">Date</div>
          <input type="date" id="ticketDate" class="form-control form-control-sm" value="<?= h(date('Y-m-d')) ?>">
        </div>
      </div>

      <table class="table table-sm mb-2" id="ticketTable">
        <thead>
          <tr>
            <th>Item</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Disc</th>
            <th class="text-end">Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="ticketBody">
          <tr><td colspan="5" class="text-muted small">No items</td></tr>
        </tbody>
      </table>

      <div class="mb-1 small-muted">Invoice Discount</div>
      <div class="d-flex gap-2 align-items-center mb-2">
        <select id="invDiscountType" class="form-select form-select-sm" style="width:110px;">
          <option value="">— none —</option>
          <option value="percent">Percent (%)</option>
          <option value="amount">Amount (₱)</option>
        </select>
        <input id="invDiscountValue" type="number" step="0.01" class="form-control form-control-sm" placeholder="Value" style="width:120px;">
        <div class="ms-auto text-end">
          <div class="small-muted">Subtotal</div>
          <div id="ticketSubtotal" class="fs-6">₱0.00</div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div class="small-muted">Total Discounts</div>
        <div id="ticketDiscountTotal" class="fw-bold">₱0.00</div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <div class="fs-5">Grand Total</div>
        <div id="ticketGrandTotal" class="fs-4">₱0.00</div>
      </div>

      <div class="d-grid gap-2 mt-3">
        <!--<button id="btnPay" type="button" class="btn btn-loy">Pay</button>-->
        <button id="btnSaveTicket" class="btn btn-outline-secondary">Save Ticket</button>
        <button id="btnSaveSale" class="btn btn-primary">Save Sale</button>
      </div>
    </div>

    <div class="mt-3">
      <h6 class="mb-2">Saved Tickets</h6>
      <?php if (empty($_SESSION['tickets'])): ?>
        <div class="text-muted small">No tickets</div>
      <?php else: ?>
        <ul class="list-group small">
          <?php foreach (array_reverse($_SESSION['tickets']) as $t): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <div><strong><?= h($t['id']) ?></strong></div>
                <div class="small-muted"><?= h(date('Y-m-d H:i', strtotime($t['created_at']))) ?> · <?= (int)count($t['items']) ?> items</div>
              </div>
              <div class="btn-group-vertical">
                <a class="btn btn-sm btn-outline-primary" href="sale_add.php?load_ticket=<?= urlencode($t['id']) ?>">Load</a>
                <a class="btn btn-sm btn-outline-danger" href="sale_add.php?delete_ticket=<?= urlencode($t['id']) ?>" onclick="return confirm('Delete ticket?')">Del</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Hidden form to POST Save Ticket / Save Sale -->
<form id="hiddenPostForm" method="post" style="display:none;">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="action" id="hidden_action" value="save_sale">
  <input type="hidden" name="customer_id" id="hidden_customer">
  <input type="hidden" name="date" id="hidden_date">
  <input type="hidden" name="notes" id="hidden_notes">
  <input type="hidden" name="sale_mode" id="hidden_sale_mode" value="retail">
  <input type="hidden" name="inv_discount_type" id="hidden_inv_discount_type">
  <input type="hidden" name="inv_discount_value" id="hidden_inv_discount_value">
  <div id="hidden_items"></div>
</form>

<!-- Pay Modal (simple) -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog pay-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div>Total: <strong id="payTotal">₱0.00</strong></div>
        <div class="mt-3">
          <label>Amount Received</label>
          <input id="payReceived" type="number" step="0.01" class="form-control" value="">
        </div>
        <div class="mt-2 small-muted">Change: <span id="payChange">₱0.00</span></div>
      </div>
      <div class="modal-footer">
        <button id="payCancel" type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="payConfirm" type="button" class="btn btn-loy">Confirm & Save Sale</button>
      </div>
    </div>
  </div>
</div>

<!-- bootstrap JS assumed already present in footer -->
<script>
const PRODUCTS = <?= json_encode($products, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
// build category name + color maps (auto-pick pastel colors)
const CATEGORY_COLORS = {};
const CATEGORY_NAMES = {};
(function(){
  const palette = ['#1e88e5','#43a047','#fb8c00','#e91e63','#6a1b9a','#00acc1','#fdd835','#8d6e63'];
  for (let i = 0; i < CATEGORIES.length; i++) {
    const c = CATEGORIES[i];
    const id = String(c.id);
    CATEGORY_NAMES[id] = c.name;
    CATEGORY_COLORS[id] = palette[i % palette.length];
  }
  CATEGORY_COLORS['default'] = '#6c757d';
})();
const initialItems = <?= json_encode($initialItems, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const initialTicket = <?= json_encode($initialTicket ?? null, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

let currentMode = 'retail';
let cart = []; // array of {product_id, code, name, qty, unit_price, sold_by, unit, price_tier, discount_type, discount_value}

/* helpers */
function money(n){ return '₱' + Number(n || 0).toFixed(2); }
function priceFor(prod, mode){ if (!prod) return 0; if (mode==='wholesale'){ const w = parseFloat(prod.wholesale_price); return (w>0 ? w : parseFloat(prod.sell_price||0)); } return parseFloat(prod.sell_price||0); }
function fmtStock(q){ const n = parseFloat(q||0); return (n%1===0 ? n.toFixed(0) : n.toFixed(3).replace(/\.?0+$/,'')); }

/* Product Grid rendering */
const productGrid = document.getElementById('productGrid');
const productSearch = document.getElementById('productSearch');
let activeCategory = '';

function renderProducts(){
  productGrid.innerHTML = '';
  const q = (productSearch.value || '').trim().toLowerCase();

  for (const p of PRODUCTS) {

    if (activeCategory && String(p.category_id) !== String(activeCategory)) continue;

    if (q) {
      const hay = (p.name + ' ' + p.code).toLowerCase();
      if (!hay.includes(q)) continue;
    }

    const price = priceFor(p, currentMode);

    const card = document.createElement('div');
    card.className = 'product-card';
    card.dataset.id = p.id;

    const cid = p.category_id !== undefined && p.category_id !== null ? String(p.category_id) : 'default';
    const catColor = CATEGORY_COLORS[cid] || CATEGORY_COLORS['default'];
    const catName = CATEGORY_NAMES[cid] || '';
    const initial = (catName && catName.length) ? catName.trim().charAt(0).toUpperCase() : '';

    card.innerHTML = `
      <div style="position:relative;">
        <div class="cat-icon" title="${catName}" style="background:${catColor};">${initial}</div>
        <div style="padding-left:44px;">
          <div class="product-title">${p.code} — ${p.name}</div>
          <div class="product-meta">${fmtStock(p.stock_qty)} ${p.unit || 'pc'}</div>
        </div>
      </div>
      <div style="text-align:right">
        <div class="fw-bold">${money(price)}</div>
        <div class="product-meta">${p.sold_by === 'weight' ? (p.unit || 'kg') : 'pc'}</div>
      </div>`;

    // ✅ Disable if no stock
    if (parseFloat(p.stock_qty) > 0) {
        card.addEventListener('click', ()=> onAddProduct(p));
    } else {
        card.style.opacity = "0.5";
        card.style.pointerEvents = "none";
    }

    productGrid.appendChild(card);
  }

  // ✅ empty message OUTSIDE loop
  if (!productGrid.children.length) {
    const note = document.createElement('div');
    note.className = 'text-muted';
    note.textContent = 'No products found.';
    productGrid.appendChild(note);
  }
}

/* Category dropdown */
const categorySelect = document.getElementById('categorySelect');
if (categorySelect) {
  categorySelect.addEventListener('change', () => {
    activeCategory = categorySelect.value || '';
    renderProducts();
  });
}

/* Search */
productSearch.addEventListener('input', ()=> renderProducts());

/* Add product to cart */
function onAddProduct(p){
  let qty = 1;
  if (p.sold_by === 'weight') {
    const val = prompt(`Enter weight in ${p.unit || 'kg'} (e.g. 0.25, 0.5, 1):`, '0.5');
    if (val === null) return;
    qty = parseFloat(val);
    if (isNaN(qty) || qty <= 0) { alert('Invalid quantity'); return; }
    // normalize to quarter kilo client-side
    qty = Math.max(0.25, Math.round(qty * 4) / 4);
  }
  const unitPrice = priceFor(p, currentMode);
  const found = cart.find(x => x.product_id == p.id && x.unit_price == unitPrice && x.discount_type==null && x.discount_value==null);
  if (found) found.qty = Number(found.qty) + Number(qty);
  else cart.push({
    product_id: p.id,
    code: p.code,
    name: p.name,
    qty: Number(qty),
    unit_price: Number(unitPrice),
    sold_by: p.sold_by,
    unit: p.unit || 'pc',
    price_tier: currentMode,
    discount_type: null,
    discount_value: null
  });
  renderTicket();
}

/* render ticket */
function renderTicket(){
  const tbody = document.getElementById('ticketBody');
  tbody.innerHTML = '';
  if (!cart.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-muted small">No items</td></tr>'; updateTotals(); return; }
  let subtotal = 0;
  cart.forEach((it, idx) => {
    const gross = Number(it.qty) * Number(it.unit_price);
    const lineDisc = calcLineDiscount(gross, it.discount_type, it.discount_value);
    const lineNet = Math.max(0, gross - lineDisc);
    subtotal += lineNet;

    const tr = document.createElement('tr');
    tr.innerHTML = `<td>
                      <div class="fw-bold">${it.code} — ${it.name}</div>
                      <div class="small-muted">${it.sold_by==='weight'? it.qty + ' ' + it.unit : (parseInt(it.qty) + ' pc')}</div>
                    </td>
                    <td class="text-end">
                      <input data-idx="${idx}" class="form-control form-control-sm qty-input" type="number" value="${it.qty}"
                             step="${it.sold_by==='weight'? '0.25':'1'}" min="${it.sold_by==='weight'? '0.25':'1'}" style="width:90px;margin-left:auto;">
                    </td>
                    <td class="text-end">
                      <select data-idx="${idx}" class="form-select form-select-sm disc-select" title="Discount Type">
                        <option value="" ${it.discount_type===null||it.discount_type===''?'selected':''}>—</option>
                        <option value="percent" ${it.discount_type==='percent'?'selected':''}>%</option>
                        <option value="amount" ${it.discount_type==='amount'?'selected':''}>₱</option>
                      </select>
                      <input data-idx="${idx}" class="form-control form-control-sm disc-input" type="number" step="0.01" min="0"
                             value="${it.discount_value!==null?it.discount_value:''}" placeholder="val">
                      <div class="line-disc">${money(lineDisc)}</div>
                    </td>
                    <td class="text-end">${money(lineNet)}</td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-danger btn-remove" data-idx="${idx}">Remove</button></td>`;
    tbody.appendChild(tr);
  });

  // wire qty inputs, discount selectors and remove
  tbody.querySelectorAll('.qty-input').forEach(inp=>{
    inp.addEventListener('change', ()=>{
      const i = Number(inp.dataset.idx);
      let v = parseFloat(inp.value || 0);
      if (isNaN(v) || v <= 0) v = 0;
      if (cart[i].sold_by !== 'weight') v = Math.max(1, Math.round(v));
      else v = Math.max(0.25, Math.round(v * 4) / 4);
      cart[i].qty = v;
      renderTicket();
    });
  });
  tbody.querySelectorAll('.disc-select').forEach(sel=>{
    sel.addEventListener('change', ()=>{
      const i = Number(sel.dataset.idx);
      cart[i].discount_type = sel.value || null;
      // if cleared, nullify value as well
      if (!sel.value) cart[i].discount_value = null;
      renderTicket();
    });
  });
  tbody.querySelectorAll('.disc-input').forEach(inp=>{
    inp.addEventListener('input', ()=>{
      const i = Number(inp.dataset.idx);
      const v = inp.value === '' ? null : parseFloat(inp.value);
      cart[i].discount_value = (v === null || isNaN(v)) ? null : v;
      renderTicket();
    });
  });
  tbody.querySelectorAll('.btn-remove').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const i = Number(btn.dataset.idx);
      cart.splice(i,1);
      renderTicket();
    });
  });

  // set invoice discount UI from current (if loaded ticket)
  if (initialTicket) {
    // handled in init()
  }

  updateTotals();
}

function calcLineDiscount(gross, dt, dv){
  gross = Number(gross) || 0;
  if (!dt || dv === null || dv === '' || typeof dv === 'undefined') return 0;
  if (dt === 'percent') return Math.max(0, gross * (Math.max(0, Math.min(100, Number(dv))) / 100));
  if (dt === 'amount')  return Math.max(0, Number(dv));
  return 0;
}

function updateTotals(){
  let subtotal = 0;
  let lineDiscounts = 0;
  cart.forEach(it=>{
    const gross = Number(it.qty) * Number(it.unit_price);
    const ld = calcLineDiscount(gross, it.discount_type, it.discount_value);
    const net = Math.max(0, gross - ld);
    subtotal += net;
    lineDiscounts += ld;
  });

  // invoice discount
  const invType = document.getElementById('invDiscountType').value;
  const invVal  = (document.getElementById('invDiscountValue').value || '') === '' ? null : parseFloat(document.getElementById('invDiscountValue').value);
  let invAlloc = 0;
  if (invType && invVal !== null && !isNaN(invVal)) {
    if (invType === 'percent') invAlloc = subtotal * (Math.max(0, Math.min(100, invVal))/100);
    if (invType === 'amount')  invAlloc = Math.max(0, invVal);
  }

  const totalDiscounts = lineDiscounts + invAlloc;
  const grand = Math.max(0, subtotal - invAlloc);

  document.getElementById('ticketSubtotal').textContent = money(subtotal);
  document.getElementById('ticketDiscountTotal').textContent = money(totalDiscounts);
  document.getElementById('ticketGrandTotal').textContent = money(grand);
  document.getElementById('payTotal').textContent = money(grand);
  document.getElementById('ticketTotal') && (document.getElementById('ticketTotal').textContent = money(grand));
}

document.getElementById('invDiscountType').addEventListener('change', updateTotals);
document.getElementById('invDiscountValue').addEventListener('input', updateTotals);

/* sale mode toggle */
function setMode(mode){
  currentMode = mode;
  document.getElementById('hidden_sale_mode').value = mode;
  document.getElementById('sale_mode')?.value && (document.getElementById('sale_mode').value = mode);
  document.querySelectorAll('[data-mode]').forEach(b=>{
    const active = b.dataset.mode === mode;
    b.classList.toggle('active', active);
    b.classList.toggle('btn-primary', active);
    b.classList.toggle('btn-outline-primary', !active);
  });
  renderProducts();
}
document.getElementById('btnRetail').addEventListener('click', ()=> setMode('retail'));
document.getElementById('btnWholesale').addEventListener('click', ()=> setMode('wholesale'));

/* Save Ticket / Save Sale wiring */
document.getElementById('btnSaveTicket').addEventListener('click', ()=> {
  if (!cart.length) { alert('No items to save'); return; }
  buildAndSubmit('save_ticket');
});

document.getElementById('btnSaveSale').addEventListener('click', ()=> {
  if (!cart.length) { alert('No items to save'); return; }
  const payModal = new bootstrap.Modal(document.getElementById('payModal'));
  document.getElementById('payReceived').value = '';
  document.getElementById('payChange').textContent = money(0);
  document.getElementById('payTotal').textContent = document.getElementById('ticketGrandTotal').textContent;
  payModal.show();
});

/* Pay modal logic */
document.getElementById('payReceived').addEventListener('input', ()=>{
  const rec = parseFloat(document.getElementById('payReceived').value || 0);
  const total = parseFloat((document.getElementById('ticketGrandTotal').textContent || '₱0').replace(/[^0-9.-]+/g,"")) || 0;
  const change = Math.max(0, rec - total);
  document.getElementById('payChange').textContent = money(change);
});

document.getElementById('payConfirm').addEventListener('click', ()=>{
  buildAndSubmit('save_sale');
});

/* Build hidden form and submit */
function buildAndSubmit(action){
  const hidden = document.getElementById('hidden_items');
  hidden.innerHTML = '';
  document.getElementById('hidden_action').value = action;
  document.getElementById('hidden_customer').value = document.getElementById('ticketCustomer').value || '';
  document.getElementById('hidden_date').value = document.getElementById('ticketDate').value || '';
  document.getElementById('hidden_notes').value = ''; // you can add a notes input if needed
  document.getElementById('hidden_sale_mode').value = currentMode;
  document.getElementById('hidden_inv_discount_type').value = document.getElementById('invDiscountType').value || '';
  document.getElementById('hidden_inv_discount_value').value = document.getElementById('invDiscountValue').value || '';

  cart.forEach((it, idx) => {
    hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${idx}][product_id]" value="${encodeURIComponent(it.product_id)}">`);
    hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${idx}][qty]" value="${encodeURIComponent(it.qty)}">`);
    hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${idx}][unit_price]" value="${encodeURIComponent(it.unit_price)}">`);
    hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${idx}][price_tier]" value="${encodeURIComponent(it.price_tier)}">`);
    hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${idx}][discount_type]" value="${encodeURIComponent(it.discount_type ?? '')}">`);
    hidden.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${idx}][discount_value]" value="${encodeURIComponent(it.discount_value ?? '')}">`);
  });

  // submit hidden form
  document.getElementById('hiddenPostForm').submit();
}

/* initial load */
(function init(){
  // populate cart from loaded ticket or nothing
  if (initialItems && initialItems.length) {
    cart = initialItems.map(it=>({
      product_id: it.product_id,
      code: (PRODUCTS.find(p=>p.id==it.product_id)||{}).code||'',
      name: (PRODUCTS.find(p=>p.id==it.product_id)||{}).name||'',
      qty: isNaN(parseFloat(it.qty)) ? 1 : parseFloat(it.qty),
      unit_price: parseFloat(it.unit_price) || 0,
      sold_by: (PRODUCTS.find(p=>p.id==it.product_id)||{}).sold_by || 'each',
      unit: (PRODUCTS.find(p=>p.id==it.product_id)||{}).unit || 'pc',
      price_tier: it.price_tier || 'retail',
      discount_type: it.discount_type ?? null,
      discount_value: (typeof it.discount_value !== 'undefined' && it.discount_value !== null) ? it.discount_value : null
    }));
    // set customer/date and invoice discount if present server-side
    <?php if ($initialTicket): ?>
      document.getElementById('ticketCustomer').value = '<?= h($initialTicket['customer_id'] ?? '') ?>';
      document.getElementById('ticketDate').value = '<?= h($initialTicket['date'] ?? date('Y-m-d')) ?>';
      setMode('<?= h($initialTicket['sale_mode'] ?? 'retail') ?>');
      // invoice discount (if ticket stored it)
      <?php if (isset($initialTicket['inv_discount_type'])): ?>
        document.getElementById('invDiscountType').value = '<?= h($initialTicket['inv_discount_type']) ?>';
      <?php endif; ?>
      <?php if (isset($initialTicket['inv_discount_value'])): ?>
        document.getElementById('invDiscountValue').value = '<?= h($initialTicket['inv_discount_value']) ?>';
      <?php endif; ?>
    <?php endif; ?>
  }
  renderProducts();
  renderTicket();
})();
</script>

<?php require_once __DIR__.'/includes/footer.php'; ?>
