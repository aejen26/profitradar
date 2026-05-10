<?php

require_once '/includes/db.php';

$id = $_GET['id'];
$status = $_GET['status'];

$conn->query("
UPDATE deliveries
SET status = '$status'
WHERE id = $id
");

header("Location: view.php?id=".$id);