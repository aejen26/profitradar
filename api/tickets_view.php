<?php
// public/api/tickets_view.php — hardened with fallbacks for code/name
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit;
}

$csrf = $_POST['csrf'] ?? '';
if (!hash_equals($_SESSION['csrf'] ?? '', (string)$csrf)) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid CSRF token']); exit;
}

if (!is_logged_in()) {
  http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Not authenticated']); exit;
}

$tid = trim((string)($_POST['ticket_id'] ?? ''));
if ($tid === '') { echo json_encode(['ok'=>false,'error'=>'Missing ticket id']); exit; }

$ticketsRaw = $_SESSION['tickets'] ?? [];
$tickets = [];
if ($ticketsRaw) {
  $isAssoc = array_keys($ticketsRaw) !== range(0, count($ticketsRaw) - 1);
  if ($isAssoc) {
    foreach ($ticketsRaw as $k => $t) if (is_array($t)) $tickets[] = $t;
  } else {
    foreach ($ticketsRaw as $t) if (is_array($t)) $tickets[] = $t;
  }
}

function ticket_id_from($t) {
  if (!empty($t['id'])) return (string)$t['id'];
  if (!empty($t['key'])) return (string)$t['key'];
  return substr(md5(json_encode($t)), 0, 10);
}

$found = null;
foreach ($tickets as $t) {
  if (ticket_id_from($t) === $tid) { $found = $t; break; }
}
if (!$found) { echo json_encode(['ok'=>false,'error'=>'Ticket not found']); exit; }

$hdr = [
  'id' => $found['id'] ?? $found['key'] ?? $tid,
  'created_at' => $found['created_at'] ?? $found['created'] ?? null,
  'notes' => $found['notes'] ?? null,
  'customer' => $found['customer'] ?? ($found['customer_name'] ?? null),
  'user' => $found['user'] ?? ($found['user_name'] ?? null),
  'ref' => $found['ref_no'] ?? null
];

$lines = $found['items'] ?? ($found['lines'] ?? []);
if (!is_array($lines)) $lines = [];

// helper to compute line total (keeps behavior similar to sales lines)
function line_net_calc($qty,$unit,$dt,$dv){
  $gross = (float)$qty * (float)$unit;
  if ($dt==='percent' && $dv !== null) $gross -= $gross * (max(0,min(100,(float)$dv))/100);
  if ($dt==='amount'  && $dv !== null) $gross -= max(0,(float)$dv);
  return max(0,$gross);
}

$total = 0.0;
$linesOut = [];
foreach ($lines as $L) {
  // robust extraction of qty/unit/discount fields (accept many variants)
  $qty = isset($L['qty']) ? (float)$L['qty'] : (float)($L['quantity'] ?? ($L['q'] ?? 0));
  $unit = isset($L['unit_price']) ? (float)$L['unit_price'] : (float)($L['price'] ?? ($L['unit'] ?? 0));

  // discount fields
  $dt = $L['discount_type'] ?? ($L['disc_type'] ?? null);
  $dv = array_key_exists('discount_value', $L) ? $L['discount_value'] : ($L['discount'] ?? ($L['disc'] ?? null));

  // PROPER fallbacks for code/name
  $code = $L['code'] ??
          $L['sku'] ??
          $L['product_code'] ??
          $L['product_sku'] ??
          null;

  $name = $L['name'] ??
          $L['title'] ??
          $L['product_name'] ??
          $L['item_name'] ??
          $L['label'] ??
          null;

  // if no name found, try to stringify whatever else might be useful
  if ($name === null) {
    if (!empty($L['product']) && is_string($L['product'])) $name = $L['product'];
    elseif (!empty($L['label']) && is_string($L['label'])) $name = $L['label'];
  }

  $lineTotal = line_net_calc($qty, $unit, $dt, $dv);
  $linesOut[] = [
    'code' => $code,
    'name' => $name,
    'qty' => $qty,
    'unit_price' => $unit,
    'discount_type' => $dt,
    'discount_value' => $dv,
    'line_total' => round($lineTotal, 2),
  ];
  $total += $lineTotal;
}

echo json_encode(['ok'=>true, 'ticket'=>$hdr, 'lines'=>$linesOut, 'total'=>round($total,2)]);
exit;
