<?php
// public/reports/sales_details.php
// Returns an HTML fragment (no full page layout) for the AJAX modal in sales_report.php

require_once __DIR__ . '/includes/auth.php';     // provides require_login()
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
require_login(); // ensure only logged-in users can fetch details

// helpers (same logic as in sales_report)
function line_gross($qty, $unit) {
  return max(0.0, (float)$qty * (float)$unit);
}
function line_discount_amount($gross, $dt, $dv) {
  if ($dv === null || $dv === '' || $dt === null) return 0.0;
  if ($dt === 'percent') return max(0.0, $gross * (max(0, min(100, (float)$dv))/100));
  if ($dt === 'amount')  return max(0.0, (float)$dv);
  return 0.0;
}
function invoice_discount_amount($subtotal, $dt, $dv) {
  if (!$dt || $dv === null) return 0.0;
  if ($dt === 'percent') return max(0.0, $subtotal * (max(0,min(100,(float)$dv))/100));
  if ($dt === 'amount')  return max(0.0, (float)$dv);
  return 0.0;
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

// read GET
$ajax_bucket = $_GET['ajax_bucket'] ?? null;
$gran = $_GET['granularity'] ?? 'day';
$from = $_GET['from'] ?? null;
$to   = $_GET['to'] ?? null;
$mode = $_GET['mode'] ?? '';

$valid = ['day','week','month'];
if (!in_array($gran, $valid, true)) $gran = 'day';

if (!$ajax_bucket) {
  echo "<div class='alert alert-warning'>Missing bucket parameter.</div>";
  exit;
}

list($bStart, $bEnd) = bucket_range_from_key($ajax_bucket, $gran);
if (!$bStart || !$bEnd) {
  echo "<div class='alert alert-warning'>Invalid bucket.</div>";
  exit;
}

// Build query for transactions in bucket (sales & refunds)
$qParams = [$bStart, $bEnd];
$modeWhere = '';
if ($mode === 'retail' || $mode === 'wholesale') {
  $modeWhere = " AND t.sale_mode = ? ";
  $qParams[] = $mode;
}

$detailTxSql = "
  SELECT t.id, t.type, t.ref_no, t.date, t.created_at, t.user_id,
         t.extra_discount_type, t.extra_discount_value, t.sale_mode
  FROM transactions t
  WHERE t.type IN ('sale','refund')
    AND t.date BETWEEN ? AND ?
    $modeWhere
  ORDER BY t.date DESC, t.id DESC
";
$dSt = $pdo->prepare($detailTxSql);
$dSt->execute($qParams);
$detailTxRows = $dSt->fetchAll(PDO::FETCH_ASSOC);

if (empty($detailTxRows)) {
  echo "<div class='alert alert-info'>No transactions found for this bucket.</div>";
  exit;
}

// fetch items for these transactions
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
$detailItemsByTx = [];
foreach ($itSt as $r) $detailItemsByTx[$r['transaction_id']][] = $r;

// render fragment: list transactions as cards with line tables
foreach ($detailTxRows as $t) {
  $txItems = $detailItemsByTx[$t['id']] ?? [];
  $dtRaw  = $t['created_at'] ?: $t['date'];
  $dtDisp = $dtRaw ? date('Y-m-d H:i', strtotime($dtRaw)) : '';
  echo "<div class='card mb-2'><div class='card-body p-2'>";
  echo "<div class='d-flex justify-content-between align-items-start'>";
  echo "<div>";
  echo "<strong>" . h($t['ref_no'] ?? ('TX_'.$t['id'])) . "</strong>";
  if ($t['type'] === 'refund') echo " <span class='badge bg-danger ms-2'>Refund</span>";
  echo "<div class='small text-muted'>" . h($dtDisp) . " · " . h(ucfirst($t['sale_mode'] ?? '')) . "</div>";
  echo "</div>"; // left
  // compute tx totals
  $txGross = 0.0; $txLineDisc = 0.0; $txInvAlloc = 0.0; $txNet = 0.0; $txCogs = 0.0;
  $linesTemp = [];
  foreach ($txItems as $li) {
    $qty = (float)$li['qty']; $unit = (float)$li['unit_price'];
    $gross = line_gross($qty,$unit);
    $lineDisc = line_discount_amount($gross, $li['discount_type'], $li['discount_value']);
    $netBeforeInvoice = max(0.0, $gross - $lineDisc);
    $linesTemp[] = ['qty'=>$qty,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBeforeInvoice'=>$netBeforeInvoice,'cost_at_sale'=>$li['cost_at_sale']];
    $txGross += $gross; $txLineDisc += $lineDisc;
  }
  $txInvAmt = invoice_discount_amount(array_sum(array_column($linesTemp,'netBeforeInvoice')), $t['extra_discount_type'], $t['extra_discount_value']);
  $sumNetBefore = array_sum(array_column($linesTemp,'netBeforeInvoice'));
  foreach ($linesTemp as $li) {
    $share = ($sumNetBefore>0)? ($li['netBeforeInvoice']/$sumNetBefore):0;
    $alloc = $txInvAmt * $share; $txInvAlloc += $alloc;
    $lineNet = max(0.0, $li['netBeforeInvoice'] - $alloc);
    $txNet += $lineNet;
    $unitCost = ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : 0.0;
    $txCogs += $li['qty'] * $unitCost;
  }
  $txDiscounts = $txLineDisc + $txInvAlloc; $txProfit = $txNet - $txCogs;
  echo "<div class='text-end'>";
  echo "<div class='fw-bold'>Net: ₱" . number_format($txNet,2) . "</div>";
  echo "<div class='small text-muted'>Gross: ₱" . number_format($txGross,2) . " · Discounts: ₱" . number_format($txDiscounts,2) . "</div>";
  echo "</div>"; // right
  echo "</div>"; // d-flex

  // lines table
  if ($txItems) {
    echo "<table class='table table-sm mt-2 mb-0'><thead><tr><th style='width:18%'>Code</th><th>Item</th><th class='text-end'>Qty</th><th class='text-end'>Unit</th><th class='text-end'>Discount</th><th class='text-end'>Line Total</th></tr></thead><tbody>";
    foreach ($txItems as $li) {
      $ln = line_gross($li['qty'],$li['unit_price']);
      $ld = line_discount_amount($ln,$li['discount_type'],$li['discount_value']);
      $lnAfter = max(0, $ln - $ld);
      $discLabel = '';
      if ($li['discount_type']==='percent' && $li['discount_value']!=='') $discLabel = number_format((float)$li['discount_value'],2).' %';
      if ($li['discount_type']==='amount'  && $li['discount_value']!=='') $discLabel = '₱'.number_format((float)$li['discount_value'],2);
      echo "<tr>";
      echo "<td>" . h($li['code']) . "</td>";
      echo "<td>" . h($li['name']) . "</td>";
      echo "<td class='text-end'>" . (float)$li['qty'] . "</td>";
      echo "<td class='text-end'>₱" . number_format((float)$li['unit_price'],2) . "</td>";
      echo "<td class='text-end'>" . ($discLabel?:'—') . "</td>";
      echo "<td class='text-end'>₱" . number_format($lnAfter,2) . "</td>";
      echo "</tr>";
    }
    echo "</tbody></table>";
  }

  echo "<div class='mt-2 small text-muted'>Invoice discount: " . ($t['extra_discount_type'] ? h($t['extra_discount_type']).' '.h($t['extra_discount_value']) : '—') .
       " · COGS: ₱" . number_format($txCogs,2) . " · Profit: ₱" . number_format($txProfit,2) . "</div>";

  echo "</div></div>"; // card
}

exit;
