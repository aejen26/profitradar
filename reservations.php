<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
require_login();
$pdo = getDB();

$sql = "
SELECT r.*, p.name AS product_name
FROM reservations r
JOIN products p ON p.id = r.product_id
WHERE r.status = 'reserved'
ORDER BY r.created_at DESC
";

$rows = $pdo->query($sql)->fetchAll();
?>

<h4>Reservations</h4>

<table class="table table-striped">
<thead>
<tr>
<th>Product</th>
<th>Quantity</th>
<th>Note</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['product_name']) ?></td>
<td><?= $r['quantity'] ?></td>
<td><?= htmlspecialchars($r['note']) ?></td>
<td><?= $r['created_at'] ?></td>

<td>

<a class="btn btn-success btn-sm"
href="complete_reservation.php?id=<?= $r['id'] ?>">
Complete
</a>

<a class="btn btn-danger btn-sm"
href="cancel_reservation.php?id=<?= $r['id'] ?>"
onclick="return confirm('Cancel reservation?')">
Cancel
</a>

</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php require_once __DIR__.'/includes/footer.php'; ?>