<?php
// public/tickets.php
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);

if (session_status() === PHP_SESSION_NONE) session_start();

$ticketsRaw = $_SESSION['tickets'] ?? [];
$tickets = [];

if ($ticketsRaw) {
  $isAssoc = array_keys($ticketsRaw) !== range(0, count($ticketsRaw) - 1);
  if ($isAssoc) {
    foreach ($ticketsRaw as $t) if (is_array($t)) $tickets[] = $t;
  } else {
    foreach ($ticketsRaw as $t) if (is_array($t)) $tickets[] = $t;
  }
}

function ticket_id_from($t) {
  if (!empty($t['id'])) return (string)$t['id'];
  if (!empty($t['key'])) return (string)$t['key'];
  return substr(md5(json_encode($t)), 0, 10);
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    $flash = ['type'=>'danger','msg'=>'Invalid CSRF token'];
  } else {

    if ($_POST['action'] === 'delete_one' && !empty($_POST['ticket_id'])) {

      $tid = (string)$_POST['ticket_id'];

      foreach ($_SESSION['tickets'] ?? [] as $k => $t) {
        $id = ticket_id_from($t);
        if ($id === $tid) unset($_SESSION['tickets'][$k]);
      }

      $flash = ['type'=>'success','msg'=>"Ticket $tid deleted."];

      $ticketsRaw = $_SESSION['tickets'] ?? [];
      $tickets = is_array($ticketsRaw) ? array_values($ticketsRaw) : [];

    } elseif ($_POST['action'] === 'delete_all') {

      unset($_SESSION['tickets']);
      $tickets = [];
      $flash = ['type'=>'success','msg'=>'All tickets deleted.'];

    }
  }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h4 class="fw-semibold mb-0">Saved POS Tickets</h4>
<small class="text-muted">Manage saved sales transactions</small>
</div>

<div class="d-flex gap-2">

<a class="btn btn-outline-secondary"
href="/sale_add.php">
Open POS
</a>

<form method="post"
onsubmit="return confirm('Delete ALL tickets?');">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="action" value="delete_all">

<button class="btn btn-danger">
Delete All
</button>

</form>

</div>

</div>


<?php if ($flash): ?>

<div class="alert alert-<?= h($flash['type']) ?>">
<?= h($flash['msg']) ?>
</div>

<?php endif; ?>


<div class="card shadow-sm">

<div class="card-body p-0">

<?php if (empty($tickets)): ?>

<div class="p-4 text-center text-muted">
No saved tickets.
</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>
<th style="width:180px">Ticket ID</th>
<th style="width:200px">Created</th>
<th style="width:120px">Items</th>
<th style="width:120px">Preview</th>
<th class="text-end" style="width:220px">Actions</th>
</tr>

</thead>

<tbody>

<?php foreach ($tickets as $t):

$tid = ticket_id_from($t);
$created = $t['created_at'] ?? ($t['created'] ?? null);
$items = $t['items'] ?? ($t['lines'] ?? []);
$count = is_array($items) ? count($items) : 0;

?>

<tr>

<td class="fw-semibold text-monospace">
<?= h($tid) ?>
</td>

<td>
<?= h($created ? date('Y-m-d H:i', strtotime($created)) : '—') ?>
</td>

<td>
<span class="badge bg-secondary">
<?= (int)$count ?> item(s)
</span>
</td>

<td>

<button
class="btn btn-sm btn-outline-primary btn-preview"
data-id="<?= h($tid) ?>"
<?= $count ? '' : 'disabled' ?>>
Preview
</button>

</td>

<td class="text-end">

<a
class="btn btn-sm btn-primary"
href="/sale_add.php?load_ticket=<?= urlencode($tid) ?>">
Load
</a>

<form method="post"
style="display:inline-block"
onsubmit="return confirm('Delete ticket <?= h($tid) ?>?');">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="action" value="delete_one">
<input type="hidden" name="ticket_id" value="<?= h($tid) ?>">

<button class="btn btn-sm btn-outline-danger">
Delete
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>
</div>



<!-- Ticket Preview Modal -->
<div class="modal fade" id="ticketPreviewModal" tabindex="-1">

<div class="modal-dialog modal-lg modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Ticket Preview</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div id="ticketPreviewHeader"
class="mb-3 small text-muted"></div>

<div class="table-responsive">

<table class="table table-sm">

<thead class="table-light">
<tr>
<th>Code</th>
<th>Item</th>
<th class="text-end">Qty</th>
<th class="text-end">Unit</th>
</tr>
</thead>

<tbody id="ticketPreviewLines"></tbody>

<tfoot>
<tr>
<th colspan="3" class="text-end">Total</th>
<th id="ticketPreviewTotal" class="text-end">₱0.00</th>
</tr>
</tfoot>

</table>

</div>

</div>

</div>

</div>

</div>

<script>

document.addEventListener('click', async (e) => {

const btn = e.target.closest('.btn-preview');
if (!btn) return;

const id = btn.dataset.id;

const fd = new FormData();
fd.append('ticket_id', id);
fd.append('csrf', '<?= h(csrf_token()) ?>');

try {

const res = await fetch('<?= APP_BASE ?>/api/tickets_view.php', {
method:'POST',
body:fd,
credentials:'same-origin',
headers:{'X-Requested-With':'XMLHttpRequest'}
});

const j = await res.json();

if (!j.ok) return alert(j.error || 'Failed to load ticket');

const hdr = j.ticket || {};
const lines = j.lines || [];
const total = j.total || 0;

document.getElementById('ticketPreviewHeader').innerHTML = `
<div><strong>Ref:</strong> ${hdr.ref ?? hdr.id ?? ''}</div>
<div><small>${hdr.created_at ?? ''} — ${hdr.customer ?? '— walk-in —'}</small></div>
<div><small>By: ${hdr.user ?? ''}</small></div>
`;

const bodyEl = document.getElementById('ticketPreviewLines');
bodyEl.innerHTML = '';

for (const L of lines) {

const qty = Number(L.qty);
const unit = Number(L.unit_price || 0).toFixed(2);
const lineTotal = Number(L.line_total || 0).toFixed(2);

const tr = document.createElement('tr');

tr.innerHTML = `
<td>${escapeHtml(L.code || '—')}</td>
<td>${escapeHtml(L.name || 'Unnamed')}</td>
<td class="text-end">${qty}</td>
<td class="text-end">₱${unit}</td>
`;

bodyEl.appendChild(tr);
}

document.getElementById('ticketPreviewTotal').textContent =
'₱' + Number(total).toFixed(2);

const modal = new bootstrap.Modal(
document.getElementById('ticketPreviewModal')
);

modal.show();

} catch (err) {
console.error(err);
alert('Failed to load ticket');
}

});

function escapeHtml(s){
if (!s) return '';
return String(s).replace(/[&<>"']/g,
m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
