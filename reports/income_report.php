<?php
// public/reports/income_report.php
require_once __DIR__ . '/../includes/header.php';
require_role(['admin','staff','auditor']);
$pdo = getDB();

/* ----------------- Config ----------------- */
define('PAGE_SIZE', 200); // we won't paginate tiles, keep large

/* ----------------- Filters ----------------- */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$mode = $_GET['mode'] ?? ''; // '', 'retail', 'wholesale'
$gran = $_GET['granularity'] ?? 'day'; // 'day'|'week'|'month'
// ensure these are defined so AJAX fragments don't emit "undefined variable" warnings
$product_id  = $_GET['product_id']  ?? '';
$category_id = $_GET['category_id'] ?? '';
$categoryFilter = ($_GET['category_id'] ?? '') === '' ? '' : (int)$_GET['category_id'];
$ajax_bucket = $_GET['ajax_bucket'] ?? null;
$ajax_product = $_GET['ajax_product'] ?? null;

$validGrans = ['day','week','month'];
if (!in_array($gran, $validGrans, true)) $gran = 'day';

/* ----------------- Helpers ----------------- */
function calc_line_gross($qty, $unit) {
  return max(0.0, (float)$qty * (float)$unit);
}
function calc_line_discount($gross, $dt, $dv) {
  if ($dv === null || $dv === '' || $dt === null) return 0.0;
  if ($dt === 'percent') return max(0.0, $gross * (max(0,min(100,(float)$dv))/100));
  if ($dt === 'amount')  return max(0.0, (float)$dv);
  return 0.0;
}
function calc_invoice_discount_amount($subtotal, $dt, $dv) {
  if (!$dt || $dv === null) return 0.0;
  if ($dt === 'percent') return max(0.0, $subtotal * (max(0,min(100,(float)$dv))/100));
  if ($dt === 'amount')  return max(0.0, (float)$dv);
  return 0.0;
}
if (!function_exists('money')) {
  function money($n){ return '₱'.number_format((float)$n,2); }
}

function bucket_key($dtString, $gran) {
  $ts = strtotime($dtString);
  if ($gran === 'day')   return date('Y-m-d', $ts);
  if ($gran === 'week')  return date('o-\WW', $ts);
  return date('Y-m', $ts);
}
function bucket_label($key, $gran) {
  if ($gran === 'day') return date('M j, Y', strtotime($key));
  if ($gran === 'week') {
    if (preg_match('/^(\d+)-W(\d+)$/', $key, $m)) return "Week {$m[2]}, {$m[1]}";
    return $key;
  }
  return date('M Y', strtotime($key . '-01'));
}
function bucket_range_from_key($key, $gran) {
  if ($gran === 'day') {
    $start = $key; $end = $key;
  } elseif ($gran === 'week') {
    if (!preg_match('/^(\d+)-W(\d+)$/', $key, $m)) return [null,null];
    $y = (int)$m[1]; $w = (int)$m[2];
    $d = new DateTime(); $d->setISODate($y, $w);
    $start = $d->format('Y-m-d'); $d->modify('+6 days'); $end = $d->format('Y-m-d');
  } else {
    if (!preg_match('/^\d{4}-\d{2}$/', $key)) return [null,null];
    $start = $key . '-01'; $end = date('Y-m-t', strtotime($start));
  }
  return [$start, $end];
}

/* ----------------- Load categories & products for tiles ----------------- */
$categories = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$productsQuery = "SELECT id, code, name, stock_qty, unit, sold_by, sell_price, wholesale_price, category_id FROM products WHERE is_active=1";
$paramsProducts = [];
if ($categoryFilter !== '') {
  $productsQuery .= " AND category_id = ?";
  $paramsProducts[] = $categoryFilter;
}
$productsQuery .= " ORDER BY name";
$products = $pdo->prepare($productsQuery);
$products->execute($paramsProducts);
$products = $products->fetchAll(PDO::FETCH_ASSOC);

/* ----------------- Fetch transactions + items (sales + refunds) ----------------- */
$params = [$from, $to];
$modeSql = '';
if ($mode === 'retail' || $mode === 'wholesale') {
  $modeSql = " AND t.sale_mode = ? ";
  $params[] = $mode;
}

$txSql = "
  SELECT t.id, t.type, t.date, t.created_at, t.extra_discount_type, t.extra_discount_value, t.sale_mode
  FROM transactions t
  WHERE t.type IN ('sale','refund')
    AND t.date BETWEEN ? AND ?
    $modeSql
  ORDER BY t.date, t.id
";
$txSt = $pdo->prepare($txSql);
$txSt->execute($params);
$txRows = $txSt->fetchAll(PDO::FETCH_ASSOC);

$txIds = array_map(function($r){ return (int)$r['id']; }, $txRows);
$items = [];
if ($txIds) {
  // fetch items, join product to get code/name/category/cost snapshot
  $in = implode(',', array_fill(0, count($txIds), '?'));
  $itSql = "
    SELECT ti.transaction_id, ti.qty, ti.unit_price, ti.discount_type, ti.discount_value, ti.cost_at_sale,
           p.id AS product_id, p.code, p.name, p.category_id
    FROM transaction_items ti
    JOIN products p ON p.id = ti.product_id
    WHERE ti.transaction_id IN ($in)
    ORDER BY ti.transaction_id, ti.id
  ";
  $itSt = $pdo->prepare($itSql);
  $itSt->execute($txIds);
  foreach ($itSt as $r) $items[] = $r;
}
$itemsByTx = [];
foreach ($items as $it) $itemsByTx[(int)$it['transaction_id']][] = $it;

/* ----------------- Aggregate into buckets ----------------- */
$byBucket = [];
function ensure_bucket(&$byBucket, $key) {
  if (!isset($byBucket[$key])) {
    $byBucket[$key] = [
      'gross' => 0.0, 'refunds' => 0.0, 'discounts' => 0.0, 'net_sales' => 0.0,
      'cogs' => 0.0, 'gross_profit' => 0.0, 'tx_count' => 0, 'items' => 0
    ];
  }
}

foreach ($txRows as $t) {
  $tid = (int)$t['id'];
  $rows = $itemsByTx[$tid] ?? [];
  if (!$rows) continue;

  // If category filter is set, skip transactions that do not include any item in that category
  if ($categoryFilter !== '') {
    $hasCatItem = false;
    foreach ($rows as $ri) {
      if ((int)$ri['category_id'] === $categoryFilter) { $hasCatItem = true; break; }
    }
    if (!$hasCatItem) continue;
  }

  $subtotal_net_before_invoice = 0.0;
  $lineInfos = [];
  foreach ($rows as $li) {
    $qty = (float)$li['qty'];
    $unit = (float)$li['unit_price'];
    $gross = calc_line_gross($qty, $unit);
    $lineDisc = calc_line_discount($gross, $li['discount_type'], $li['discount_value']);
    $netBeforeInvoice = max(0.0, $gross - $lineDisc);
    $lineInfos[] = [
      'qty'=>$qty,'unit'=>$unit,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBeforeInvoice'=>$netBeforeInvoice,
      'cost_at_sale'=> ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : 0.0
    ];
    $subtotal_net_before_invoice += $netBeforeInvoice;
  }

  $invDiscAmt = calc_invoice_discount_amount($subtotal_net_before_invoice, $t['extra_discount_type'], $t['extra_discount_value']);

  $cogs_total = 0.0; $gross_total = 0.0; $line_discounts_total = 0.0; $inv_alloc_total = 0.0; $net_total_after_all = 0.0; $items_count = 0;
  foreach ($lineInfos as $li) {
    $share = ($subtotal_net_before_invoice > 0) ? ($li['netBeforeInvoice'] / $subtotal_net_before_invoice) : 0.0;
    $alloc = $invDiscAmt * $share;
    $line_net_after = max(0.0, $li['netBeforeInvoice'] - $alloc);
    $line_discounts_total += $li['lineDisc'];
    $inv_alloc_total += $alloc;
    $gross_total += $li['gross'];
    $net_total_after_all += $line_net_after;
    $items_count += $li['qty'];
    $unitCost = $li['cost_at_sale'] ?? 0.0;
    $cogs_total += $li['qty'] * $unitCost;
  }

  $dtRaw = $t['created_at'] ?: $t['date'];
  $bucketKey = bucket_key($dtRaw ?: $t['date'], $gran);
  ensure_bucket($byBucket, $bucketKey);

  if ($t['type'] === 'refund') {
    // record refunds as positive number, but subtract from gross/net/cogs/profit
    $byBucket[$bucketKey]['refunds'] += $net_total_after_all;
    $byBucket[$bucketKey]['discounts'] += ($line_discounts_total + $inv_alloc_total);
    $byBucket[$bucketKey]['gross'] -= $gross_total;
    $byBucket[$bucketKey]['net_sales'] -= $net_total_after_all;
    $byBucket[$bucketKey]['cogs'] -= $cogs_total;
    $byBucket[$bucketKey]['gross_profit'] -= ($net_total_after_all - $cogs_total);
    $byBucket[$bucketKey]['tx_count'] += 1;
    $byBucket[$bucketKey]['items'] += $items_count;
  } else {
    $byBucket[$bucketKey]['gross'] += $gross_total;
    $byBucket[$bucketKey]['discounts'] += ($line_discounts_total + $inv_alloc_total);
    $byBucket[$bucketKey]['net_sales'] += $net_total_after_all;
    $byBucket[$bucketKey]['cogs'] += $cogs_total;
    $byBucket[$bucketKey]['gross_profit'] += ($net_total_after_all - $cogs_total);
    $byBucket[$bucketKey]['tx_count'] += 1;
    $byBucket[$bucketKey]['items'] += $items_count;
  }
}

/* sort buckets recent-first */
uksort($byBucket, function($a,$b) use($gran){
  if ($gran === 'day') return strtotime($b) <=> strtotime($a);
  if ($gran === 'week') {
    $getTs = function($k){
      if (preg_match('/^(\d+)-W(\d+)$/',$k,$m)) { $d=new DateTime(); $d->setISODate((int)$m[1], (int)$m[2]); return $d->getTimestamp(); }
      return strtotime($k);
    };
    return $getTs($b) <=> $getTs($a);
  }
  return strtotime($b . '-01') <=> strtotime($a . '-01');
});

/* totals */
$totGross = $totRefunds = $totDiscounts = $totNet = $totCogs = $totProfit = 0.0;
foreach ($byBucket as $m) {
  $totGross += (float)$m['gross'];
  $totRefunds += (float)$m['refunds'];
  $totDiscounts += (float)$m['discounts'];
  $totNet += (float)$m['net_sales'];
  $totCogs += (float)$m['cogs'];
  $totProfit += (float)$m['gross_profit'];
}

/* ----------------- Per-category table (reuse aggregated rx but compute fresh per category) ----------------- */
$byCategory = [];
foreach ($txRows as $t) {
  $tid = (int)$t['id'];
  $rows = $itemsByTx[$tid] ?? [];
  if (!$rows) continue;
  $subtotal_net_before_invoice = 0.0;
  $lineInfos = [];
  foreach ($rows as $li) {
    if ($categoryFilter !== '' && (int)$li['category_id'] !== $categoryFilter) continue;
    $qty = (float)$li['qty'];
    $unit = (float)$li['unit_price'];
    $gross = calc_line_gross($qty,$unit);
    $lineDisc = calc_line_discount($gross, $li['discount_type'], $li['discount_value']);
    $netBeforeInvoice = max(0.0,$gross-$lineDisc);
    $lineInfos[] = ['product_id'=>$li['product_id'],'qty'=>$qty,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBeforeInvoice'=>$netBeforeInvoice,'cost_at_sale'=>$li['cost_at_sale'],'code'=>$li['code'],'name'=>$li['name'],'category_id'=>$li['category_id']];
    $subtotal_net_before_invoice += $netBeforeInvoice;
  }
  if (!$lineInfos) continue;
  $invDiscAmt = calc_invoice_discount_amount($subtotal_net_before_invoice, $t['extra_discount_type'], $t['extra_discount_value']);

  foreach ($lineInfos as $li) {
    $share = ($subtotal_net_before_invoice>0)?($li['netBeforeInvoice']/$subtotal_net_before_invoice):0;
    $alloc = $invDiscAmt * $share;
    $line_net_after = max(0.0,$li['netBeforeInvoice'] - $alloc);
    $signed = ($t['type']==='refund') ? -1.0 : 1.0;

    $catId = (int)$li['category_id'];
    $catName = 'Uncategorized';
    foreach ($categories as $c) { if ((int)$c['id'] === $catId) { $catName = $c['name']; break; } }

    if (!isset($byCategory[$catName])) {
      $byCategory[$catName] = ['items_sold'=>0,'gross'=>0.0,'discounts'=>0.0,'net'=>0.0,'cogs'=>0.0,'profit'=>0.0];
    }
    $unitCost = ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : 0.0;
    $byCategory[$catName]['items_sold'] += $signed * $li['qty'];
    $byCategory[$catName]['gross'] += $signed * $li['gross'];
    $byCategory[$catName]['discounts'] += $signed * ($li['lineDisc'] + $alloc);
    $byCategory[$catName]['net'] += $signed * $line_net_after;
    $byCategory[$catName]['cogs'] += $signed * ($unitCost * $li['qty']);
    $byCategory[$catName]['profit'] += $signed * ($line_net_after - ($unitCost * $li['qty']));
  }
}
uksort($byCategory, function($a,$b) use($byCategory){ return ($byCategory[$b]['net'] ?? 0) <=> ($byCategory[$a]['net'] ?? 0); });

/* ----------------- Per-item aggregation for Top items ----------------- */
$byItem = [];
foreach ($txRows as $t) {
  $tid = (int)$t['id'];
  $rows = $itemsByTx[$tid] ?? [];
  if (!$rows) continue;
  $linesTemp = [];
  $subtotal = 0.0;
  foreach ($rows as $li) {
    if ($categoryFilter !== '' && (int)$li['category_id'] !== $categoryFilter) continue;
    $qty = (float)$li['qty'];
    $unit = (float)$li['unit_price'];
    $gross = calc_line_gross($qty,$unit);
    $ld = calc_line_discount($gross,$li['discount_type'],$li['discount_value']);
    $nbi = max(0.0,$gross - $ld);
    $linesTemp[] = ['product_id'=>$li['product_id'],'code'=>$li['code'],'name'=>$li['name'],'qty'=>$qty,'gross'=>$gross,'lineDisc'=>$ld,'netBefore'=>$nbi,'cost_at_sale'=>$li['cost_at_sale']];
    $subtotal += $nbi;
  }
  if (!$linesTemp) continue;
  $invAmt = calc_invoice_discount_amount($subtotal, $t['extra_discount_type'], $t['extra_discount_value']);
  $is_refund = ($t['type']==='refund');
  foreach ($linesTemp as $lt) {
    $share = ($subtotal>0)?($lt['netBefore']/$subtotal):0;
    $alloc = $invAmt * $share;
    $netAfter = max(0.0,$lt['netBefore'] - $alloc);
    $signed = $is_refund ? -1.0 : 1.0;
    $pid = (int)$lt['product_id'];
    if (!isset($byItem[$pid])) $byItem[$pid] = ['code'=>$lt['code'],'name'=>$lt['name'],'items_sold'=>0,'gross'=>0.0,'discounts'=>0.0,'net'=>0.0,'cogs'=>0.0];
    $unitCost = ($lt['cost_at_sale'] !== null && $lt['cost_at_sale'] !== '') ? (float)$lt['cost_at_sale'] : 0.0;
    $byItem[$pid]['items_sold'] += $signed * $lt['qty'];
    $byItem[$pid]['gross'] += $signed * $lt['gross'];
    $byItem[$pid]['discounts'] += $signed * ($lt['lineDisc'] + $alloc);
    $byItem[$pid]['net'] += $signed * $netAfter;
    $byItem[$pid]['cogs'] += $signed * ($unitCost * $lt['qty']);
  }
}
foreach ($byItem as $k => $v) {
  $profit = $v['net'] - $v['cogs'];
  $margin = ($v['net'] != 0) ? ($profit / $v['net'] * 100) : null;
  $byItem[$k]['profit'] = $profit;
  $byItem[$k]['margin'] = $margin;
}
usort($byItem, function($a,$b){ return $b['net'] <=> $a['net']; });
$topItems = array_slice($byItem, 0, 10);

/* ----------------- Build chart data (net sales over time) ----------------- */
$labels = []; $values = [];
$timelineKeys = array_reverse(array_keys($byBucket));
$tLabels = [];
$tValues = [];
foreach (array_reverse($timelineKeys) as $k) {
  $tLabels[] = bucket_label($k,$gran);
  $tValues[] = round(($byBucket[$k]['net_sales'] ?? 0),2);
}
$labels = $tLabels; $values = $tValues;

/* ----------------- AJAX bucket detail handler ----------------- */
if ($ajax_bucket) {
  list($bStart, $bEnd) = bucket_range_from_key($ajax_bucket, $gran);
  if (!$bStart || !$bEnd) {
    echo "<div class='alert alert-warning'>Invalid bucket.</div>"; exit;
  }

  // We'll search transactions that either have t.date in [bStart,bEnd]
  // OR whose created_at (datetime) falls in the same inclusive range.
  // For created_at we'll expand to full day timestamps to include all times.
  $qParams = [$bStart, $bEnd];

  // created_at bounds (00:00:00..23:59:59)
  $createdStart = $bStart . ' 00:00:00';
  $createdEnd   = $bEnd   . ' 23:59:59';
  // We'll push them after the date params and use them in the SQL below.
  $qParams[] = $createdStart;
  $qParams[] = $createdEnd;

  $modeWhere = '';
  if ($mode === 'retail' || $mode === 'wholesale') { $modeWhere = " AND t.sale_mode = ? "; $qParams[] = $mode; }
  if ($product_id !== '') { $modeWhere .= " AND EXISTS (SELECT 1 FROM transaction_items ti WHERE ti.transaction_id = t.id AND ti.product_id = ?) "; $qParams[] = (int)$product_id; }
  if ($category_id !== '') { $modeWhere .= " AND EXISTS (SELECT 1 FROM transaction_items ti JOIN products p ON p.id = ti.product_id WHERE ti.transaction_id = t.id AND p.category_id = ?) "; $qParams[] = (int)$category_id; }

  // Note: the query uses placeholders in the same order as $qParams
  $detailTxSql = "
    SELECT t.id, t.type, t.ref_no, t.date, t.created_at, t.user_id, t.extra_discount_type, t.extra_discount_value, t.sale_mode
    FROM transactions t
    WHERE t.type IN ('sale','refund')
      AND (
            (t.date BETWEEN ? AND ?)
            OR (t.created_at BETWEEN ? AND ?)
          )
      $modeWhere
    ORDER BY t.date DESC, t.id DESC
  ";
  $dSt = $pdo->prepare($detailTxSql);
  $dSt->execute($qParams);
  $detailTxRows = $dSt->fetchAll(PDO::FETCH_ASSOC);
  $detailItemsByTx = [];
  if ($detailTxRows) {
    $dTxIds = array_map(function($r){ return (int)$r['id']; }, $detailTxRows);
    $in = implode(',', array_fill(0, count($dTxIds), '?'));
    $itSql = "
      SELECT ti.transaction_id, p.code, p.name, ti.qty, ti.unit_price, ti.discount_type, ti.discount_value, ti.cost_at_sale
      FROM transaction_items ti
      JOIN products p ON p.id = ti.product_id
      WHERE ti.transaction_id IN ($in)
      ORDER BY ti.transaction_id, ti.id
    ";
    $itSt = $pdo->prepare($itSql);
    $itSt->execute($dTxIds);
    foreach ($itSt as $r) { $detailItemsByTx[$r['transaction_id']][] = $r; }
  }

  if (empty($detailTxRows)) {
    echo "<div class='alert alert-info'>No transactions found for this bucket.</div>"; exit;
  }

  foreach ($detailTxRows as $t) {
    $txItems = $detailItemsByTx[$t['id']] ?? [];
    $dtRaw  = $t['created_at'] ?: $t['date'];
    $dtDisp = $dtRaw ? date('Y-m-d H:i', strtotime($dtRaw)) : '';
    echo "<div class='card mb-2'><div class='card-body p-2'>";
    echo "<div class='d-flex justify-content-between'><div><strong>" . h($t['ref_no'] ?? ('TX_'.$t['id'])) . "</strong>";
    if ($t['type'] === 'refund') echo " <span class='badge bg-danger ms-2'>Refund</span>";
    echo "<div class='small text-muted'>" . h($dtDisp) . " · " . h(ucfirst($t['sale_mode'] ?? '')) . "</div></div>";

    // compute tx totals
    $txGross = 0.0; $txLineDisc = 0.0; $txInvAlloc = 0.0; $txNet = 0.0; $txCogs = 0.0;
    $linesTemp = [];
    foreach ($txItems as $li) {
      $qty = (float)$li['qty']; $unit = (float)$li['unit_price'];
      $gross = calc_line_gross($qty,$unit);
      $lineDisc = calc_line_discount($gross, $li['discount_type'], $li['discount_value']);
      $netBeforeInvoice = max(0.0, $gross - $lineDisc);
      $linesTemp[] = ['qty'=>$qty,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBefore'=>$netBeforeInvoice,'cost_at_sale'=>$li['cost_at_sale']];
      $txGross += $gross; $txLineDisc += $lineDisc;
    }
    $txInvAmt = calc_invoice_discount_amount(array_sum(array_column($linesTemp,'netBefore')), $t['extra_discount_type'], $t['extra_discount_value']);
    $sumNetBefore = array_sum(array_column($linesTemp,'netBefore'));
    foreach ($linesTemp as $li) {
      $share = ($sumNetBefore>0)? ($li['netBefore']/$sumNetBefore):0;
      $alloc = $txInvAmt * $share; $txInvAlloc += $alloc;
      $txNet += max(0.0, $li['netBefore'] - $alloc);
      $unitCost = ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : 0.0;
      $txCogs += $li['qty'] * $unitCost;
    }
    $txDiscounts = $txLineDisc + $txInvAlloc; $txProfit = $txNet - $txCogs;

    echo "<div class='text-end'><div class='fw-bold'>Net: ₱" . number_format($txNet,2) . "</div>";
    echo "<div class='small text-muted'>Gross: ₱" . number_format($txGross,2) . " · Discounts: ₱" . number_format($txDiscounts,2) . "</div></div></div>";

    if ($txItems) {
      echo "<table class='table table-sm mt-2 mb-0'><thead><tr><th style='width:18%'>Code</th><th>Item</th><th class='text-end'>Qty</th><th class='text-end'>Unit</th><th class='text-end'>Discount</th><th class='text-end'>Line Total</th></tr></thead><tbody>";
      foreach ($txItems as $li) {
        $ln = calc_line_gross($li['qty'],$li['unit_price']);
        $ld = calc_line_discount($ln,$li['discount_type'],$li['discount_value']);
        $lnAfter = max(0, $ln - $ld);
        $discLabel = '';
        if ($li['discount_type']==='percent' && $li['discount_value']!=='') $discLabel = number_format((float)$li['discount_value'],2).' %';
        if ($li['discount_type']==='amount'  && $li['discount_value']!=='') $discLabel = '₱'.number_format((float)$li['discount_value'],2);
        echo "<tr><td>" . h($li['code']) . "</td><td>" . h($li['name']) . "</td><td class='text-end'>" . (float)$li['qty'] . "</td><td class='text-end'>₱" . number_format((float)$li['unit_price'],2) . "</td><td class='text-end'>" . ($discLabel?:'—') . "</td><td class='text-end'>₱" . number_format($lnAfter,2) . "</td></tr>";
      }
      echo "</tbody></table>";
    }

    echo "<div class='mt-2 small text-muted'>Invoice discount: " . ($t['extra_discount_type'] ? h($t['extra_discount_type']).' '.h($t['extra_discount_value']) : '—') . " · COGS: ₱" . number_format($txCogs,2) . " · Profit: ₱" . number_format($txProfit,2) . "</div>";
    echo "</div></div>";
  }

  exit;
}

/* ----------------- AJAX product detail handler ----------------- */
if ($ajax_product) {
  $prodId = (int)$ajax_product;
  // fetch transactions in period that include this product
  $qParams = [$from, $to, $prodId];
  $modeWhere = '';
  if ($mode === 'retail' || $mode === 'wholesale') { $modeWhere = " AND t.sale_mode = ? "; $qParams[] = $mode; }

  $detailTxSql = "
    SELECT DISTINCT t.id, t.type, t.ref_no, t.date, t.created_at, t.user_id, t.extra_discount_type, t.extra_discount_value, t.sale_mode
    FROM transactions t
    JOIN transaction_items txi ON txi.transaction_id = t.id
    WHERE t.type IN ('sale','refund')
  AND (
      (t.date BETWEEN ? AND ?)
      OR (t.created_at BETWEEN ? AND ?)
  )

      AND txi.product_id = ?
      $modeWhere
    ORDER BY t.date DESC, t.id DESC
  ";
  $dSt = $pdo->prepare($detailTxSql);
  $dSt->execute($qParams);
  $detailTxRows = $dSt->fetchAll(PDO::FETCH_ASSOC);

  $detailItemsByTx = [];
  if ($detailTxRows) {
    $dTxIds = array_map(function($r){ return (int)$r['id']; }, $detailTxRows);
    $in = implode(',', array_fill(0, count($dTxIds), '?'));
    $itSql = "
      SELECT ti.transaction_id, p.code, p.name, ti.qty, ti.unit_price, ti.discount_type, ti.discount_value, ti.cost_at_sale
      FROM transaction_items ti
      JOIN products p ON p.id = ti.product_id
      WHERE ti.transaction_id IN ($in)
      ORDER BY ti.transaction_id, ti.id
    ";
    $itSt = $pdo->prepare($itSql);
    $itSt->execute($dTxIds);
    foreach ($itSt as $r) { $detailItemsByTx[$r['transaction_id']][] = $r; }
  }

  if (empty($detailTxRows)) {
    echo "<div class='alert alert-info'>No transactions found for this product in the selected period.</div>";
    exit;
  }

  // render fragment (same as bucket handler)
  foreach ($detailTxRows as $t) {
    $txItems = $detailItemsByTx[$t['id']] ?? [];
    $dtRaw  = $t['created_at'] ?: $t['date'];
    $dtDisp = $dtRaw ? date('Y-m-d H:i', strtotime($dtRaw)) : '';
    echo "<div class='card mb-2'><div class='card-body p-2'>";
    echo "<div class='d-flex justify-content-between'><div><strong>" . h($t['ref_no'] ?? ('TX_'.$t['id'])) . "</strong>";
    if ($t['type'] === 'refund') echo " <span class='badge bg-danger ms-2'>Refund</span>";
    echo "<div class='small text-muted'>" . h($dtDisp) . " · " . h(ucfirst($t['sale_mode'] ?? '')) . "</div></div>";

    // compute tx totals
    $txGross = 0.0; $txLineDisc = 0.0; $txInvAlloc = 0.0; $txNet = 0.0; $txCogs = 0.0;
    $linesTemp = [];
    foreach ($txItems as $li) {
      $qty = (float)$li['qty']; $unit = (float)$li['unit_price'];
      $gross = calc_line_gross($qty,$unit);
      $lineDisc = calc_line_discount($gross, $li['discount_type'], $li['discount_value']);
      $netBeforeInvoice = max(0.0, $gross - $lineDisc);
      $linesTemp[] = ['qty'=>$qty,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBefore'=>$netBeforeInvoice,'cost_at_sale'=>$li['cost_at_sale']];
      $txGross += $gross; $txLineDisc += $lineDisc;
    }
    $txInvAmt = calc_invoice_discount_amount(array_sum(array_column($linesTemp,'netBefore')), $t['extra_discount_type'], $t['extra_discount_value']);
    $sumNetBefore = array_sum(array_column($linesTemp,'netBefore'));
    foreach ($linesTemp as $li) {
      $share = ($sumNetBefore>0)? ($li['netBefore']/$sumNetBefore):0;
      $alloc = $txInvAmt * $share; $txInvAlloc += $alloc;
      $txNet += max(0.0, $li['netBefore'] - $alloc);
      $unitCost = ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : 0.0;
      $txCogs += $li['qty'] * $unitCost;
    }
    $txDiscounts = $txLineDisc + $txInvAlloc; $txProfit = $txNet - $txCogs;

    echo "<div class='text-end'><div class='fw-bold'>Net: ₱" . number_format($txNet,2) . "</div>";
    echo "<div class='small text-muted'>Gross: ₱" . number_format($txGross,2) . " · Discounts: ₱" . number_format($txDiscounts,2) . "</div></div></div>";

    if ($txItems) {
      echo "<table class='table table-sm mt-2 mb-0'><thead><tr><th style='width:18%'>Code</th><th>Item</th><th class='text-end'>Qty</th><th class='text-end'>Unit</th><th class='text-end'>Discount</th><th class='text-end'>Line Total</th></tr></thead><tbody>";
      foreach ($txItems as $li) {
        $ln = calc_line_gross($li['qty'],$li['unit_price']);
        $ld = calc_line_discount($ln,$li['discount_type'],$li['discount_value']);
        $lnAfter = max(0, $ln - $ld);
        $discLabel = '';
        if ($li['discount_type']==='percent' && $li['discount_value']!=='') $discLabel = number_format((float)$li['discount_value'],2).' %';
        if ($li['discount_type']==='amount'  && $li['discount_value']!=='') $discLabel = '₱'.number_format((float)$li['discount_value'],2);
        echo "<tr><td>" . h($li['code']) . "</td><td>" . h($li['name']) . "</td><td class='text-end'>" . (float)$li['qty'] . "</td><td class='text-end'>₱" . number_format((float)$li['unit_price'],2) . "</td><td class='text-end'>" . ($discLabel?:'—') . "</td><td class='text-end'>₱" . number_format($lnAfter,2) . "</td></tr>";
      }
      echo "</tbody></table>";
    }

    echo "<div class='mt-2 small text-muted'>Invoice discount: " . ($t['extra_discount_type'] ? h($t['extra_discount_type']).' '.h($t['extra_discount_value']) : '—') . " · COGS: ₱" . number_format($txCogs,2) . " · Profit: ₱" . number_format($txProfit,2) . "</div>";
    echo "</div></div>";
  }

  exit;
}

/* ----------------- Render Page ----------------- */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Income Report</h4>
  <div class="text-end small">
    <div><strong>Period:</strong> <?= h($from) ?> → <?= h($to) ?></div>
    <div><strong>Granularity:</strong> <?= h(ucfirst($gran)) ?></div>
    <div><strong>Mode:</strong> <?= ($mode==='retail'||$mode==='wholesale') ? h(ucfirst($mode)) : 'All' ?></div>
  </div>
</div>

<form class="row g-2 mb-3">
  <div class="col-md-3"><label class="form-label">From</label><input type="date" class="form-control" name="from" value="<?= h($from) ?>"></div>
  <div class="col-md-3"><label class="form-label">To</label><input type="date" class="form-control" name="to" value="<?= h($to) ?>"></div>
  <div class="col-md-2"><label class="form-label">Mode</label>
    <select name="mode" class="form-select">
      <option value="" <?= $mode===''?'selected':'' ?>>All</option>
      <option value="retail" <?= $mode==='retail'?'selected':'' ?>>Retail</option>
      <option value="wholesale" <?= $mode==='wholesale'?'selected':'' ?>>Wholesale</option>
    </select>
  </div>
  <div class="col-md-2"><label class="form-label">Group by</label>
    <select class="form-select" name="granularity">
      <option value="day" <?= $gran==='day'?'selected':'' ?>>Days</option>
      <option value="week" <?= $gran==='week'?'selected':'' ?>>Weeks</option>
      <option value="month" <?= $gran==='month'?'selected':'' ?>>Months</option>
    </select>
  </div>

  <div class="col-md-2"><label class="form-label">Category</label>
    <select class="form-select" name="category_id">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ($categoryFilter !== '' && (int)$c['id'] === (int)$categoryFilter) ? 'selected' : '' ?>>
          <?= h($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md-2 align-self-end">
    <button class="btn btn-outline-secondary w-100">Filter</button>
  </div>
</form>

<!-- Product tiles (like POS) -->
<div class="row mb-3">
  <div class="col-12">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <div class="p-3 bg-primary text-white rounded" style="min-width:140px">
        <div class="h6">All</div>
        <div class="small">Show all products</div>
      </div>
      <?php foreach ($products as $p): 
        $displayPrice = $mode === 'wholesale' ? $p['wholesale_price'] : $p['sell_price'];
      ?>
        <a href="javascript:void(0)" class="product-tile p-3 bg-white border rounded text-decoration-none text-reset" data-product="<?= (int)$p['id'] ?>" style="min-width:200px; max-width:300px;">
          <div class="d-flex justify-content-between">
            <div>
              <div style="font-weight:600"><?= h($p['code']) ?> — <?= h($p['name']) ?></div>
              <div class="small text-muted"><?= (float)$p['stock_qty'] ?> <?= h($p['unit']?:'pc') ?></div>
            </div>
            <div class="text-end">
              <div style="font-weight:700"><?= money($displayPrice) ?></div>
              <div class="small text-muted"><?= $p['sold_by'] === 'weight' ? ($p['unit']?:'kg') : 'pc' ?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-md-2"><div class="p-3 bg-light border rounded small">Items Sold<br><strong><?= (int)array_sum(array_column($byBucket,'items')) ?></strong></div></div>
  <div class="col-md-2"><div class="p-3 bg-light border rounded small">Gross Sales<br><strong><?= money($totGross) ?></strong></div></div>
  <div class="col-md-2"><div class="p-3 bg-light border rounded small">Refunds<br><strong><?= money($totRefunds) ?></strong></div></div>
  <div class="col-md-2"><div class="p-3 bg-light border rounded small">Discounts<br><strong><?= money($totDiscounts) ?></strong></div></div>
  <div class="col-md-2"><div class="p-3 bg-light border rounded small">Net Sales<br><strong><?= money($totNet) ?></strong></div></div>
  <div class="col-md-2"><div class="p-3 bg-light border rounded small">COGS<br><strong><?= money($totCogs) ?></strong></div></div>
  <div class="col-md-2 mt-2"><div class="p-3 bg-light border rounded small">Gross Profit<br><strong><?= money($totProfit) ?></strong></div></div>
</div>

<p class="text-muted"><strong>Margin:</strong> <?= ($totNet>0) ? number_format(($totNet-$totCogs)/$totNet*100,2).'%' : '—' ?></p>

<!-- Top items table -->
<h5 class="mb-2">Top Items (by Net Sales)</h5>
<table class="table table-striped table-sm">
  <thead>
    <tr>
      <th>Item</th>
      <th class="text-end">Items Sold</th>
      <th class="text-end">Gross Sales</th>
      <th class="text-end">Discounts</th>
      <th class="text-end">Net Sales</th>
      <th class="text-end">COGS</th>
      <th class="text-end">Gross Profit</th>
      <th class="text-end">Margin</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($topItems)): ?>
      <tr><td colspan="8" class="text-muted">No items for this filter.</td></tr>
    <?php else: ?>
      <?php foreach ($topItems as $it): ?>
        <tr>
          <td><a href="javascript:void(0)" class="item-link" data-product="<?= (int)($it['code']?0:0) ?>" data-product-name="<?= h($it['name']) ?>" data-product-id="<?= (int)array_search($it, $topItems) ?>" data-product-code="<?= h($it['code']) ?>" data-product-pid="<?= (int)key($topItems) ?>"><?= h(($it['code'] ? "{$it['code']} — " : '').$it['name']) ?></a></td>
          <td class="text-end"><?= (float)$it['items_sold'] ?></td>
          <td class="text-end"><?= money($it['gross']) ?></td>
          <td class="text-end"><?= money($it['discounts']) ?></td>
          <td class="text-end"><?= money($it['net']) ?></td>
          <td class="text-end"><?= money($it['cogs']) ?></td>
          <td class="text-end"><?= money($it['profit']) ?></td>
          <td class="text-end"><?= $it['margin']!==null ? number_format($it['margin'],2).'%' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Net sales chart -->
<div class="row mb-4">
  <div class="col-lg-8">
    <h5 class="mb-2">Net Sales Over Time (<?= h(ucfirst($gran)) ?>)</h5>
    <canvas id="netSalesChart" height="220"></canvas>
  </div>
</div>

<!-- Per-category table -->
<h5 class="mt-4">Category Table</h5>
<table class="table table-striped table-sm">
  <thead>
    <tr>
      <th>Category</th>
      <th class="text-end">Items Sold</th>
      <th class="text-end">Gross Sales</th>
      <th class="text-end">Discounts</th>
      <th class="text-end">Net Sales</th>
      <th class="text-end">COGS</th>
      <th class="text-end">Gross Profit</th>
      <th class="text-end">Margin</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$byCategory): ?>
      <tr><td colspan="8" class="text-muted">No data for this period.</td></tr>
    <?php else: ?>
      <?php foreach ($byCategory as $cat => $m): 
        $margin = ($m['net']>0) ? (($m['net']-$m['cogs'])/$m['net']*100) : null;
      ?>
        <tr>
          <td><?= h($cat) ?></td>
          <td class="text-end"><?= (float)$m['items_sold'] ?></td>
          <td class="text-end"><?= money($m['gross']) ?></td>
          <td class="text-end"><?= money($m['discounts']) ?></td>
          <td class="text-end"><?= money($m['net']) ?></td>
          <td class="text-end"><?= money($m['cogs']) ?></td>
          <td class="text-end"><?= money($m['profit']) ?></td>
          <td class="text-end"><?= $margin!==null ? number_format($margin,2).'%' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Buckets table (click date to open modal with details) -->
<h5 class="mt-4">Buckets</h5>
<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>Date</th>
      <th class="text-end">Gross sales</th>
      <th class="text-end">Refunds</th>
      <th class="text-end">Discounts</th>
      <th class="text-end">Net sales</th>
      <th class="text-end">Cost of goods</th>
      <th class="text-end">Gross profit</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($byBucket)): ?>
      <tr><td colspan="7" class="text-muted">No data for this period.</td></tr>
    <?php else: ?>
      <?php foreach ($byBucket as $key => $m): ?>
        <tr>
          <td><a href="javascript:void(0)" class="bucket-link" data-bucket="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= h(bucket_label($key,$gran)) ?></a></td>
          <td class="text-end"><?= money($m['gross']) ?></td>
          <td class="text-end"><?= money($m['refunds']) ?></td>
          <td class="text-end"><?= money($m['discounts']) ?></td>
          <td class="text-end"><?= money($m['net_sales'] - $m['refunds']) ?></td>
          <td class="text-end"><?= money($m['cogs']) ?></td>
          <td class="text-end"><?= money($m['gross_profit']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Modal for AJAX details -->
<div class="modal fade" id="bucketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bucket / Product details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="bucketModalBody">
        <div class="text-center text-muted">Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  // --- Chart setup ---
  const labels = <?= json_encode($labels) ?>;
  const data   = <?= json_encode($values) ?>;
  const ctx = document.getElementById('netSalesChart');
  if (ctx && labels.length > 0) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Net Sales',
          data: data,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  // --- Modal logic ---
  const modalEl = document.getElementById('bucketModal');
  const modalBody = document.getElementById('bucketModalBody');
  const bsModal = new bootstrap.Modal(modalEl);

  function openModal(params) {
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
    bsModal.show();
    const query = new URLSearchParams({
      from: '<?= h($from) ?>',
      to: '<?= h($to) ?>',
      granularity: '<?= h($gran) ?>',
      mode: '<?= h($mode) ?>',
      ...params
    }).toString();
    fetch('income_report.php?' + query, { credentials: 'same-origin' })
      .then(r => r.text())
      .then(html => modalBody.innerHTML = html)
      .catch(err => {
        modalBody.innerHTML = '<div class="alert alert-danger">Failed to load details.</div>';
        console.error(err);
      });
  }

  // --- Make date buckets clickable ---
  document.querySelectorAll('.bucket-link').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      const bucket = el.dataset.bucket;
      if (bucket) openModal({ ajax_bucket: bucket });
    });
  });

  // --- Make product tiles clickable ---
  document.querySelectorAll('.product-tile').forEach(tile => {
    tile.addEventListener('click', e => {
      e.preventDefault();
      const productId = tile.dataset.product;
      if (productId) openModal({ ajax_product: productId });
    });
  });
});
</script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
