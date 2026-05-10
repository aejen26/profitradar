<?php
require_once __DIR__.'/includes/auth.php';

$pdo = getDB();
$user_id = $_SESSION['user']['id'] ?? 0;

header('Content-Type: application/json');

try {

    $data = json_decode(file_get_contents("php://input"), true);

    $name = trim($data['name'] ?? '');
    $contact = trim($data['contact'] ?? '');
    $phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
    $email = trim($data['email'] ?? '');

    if ($name === '') {
        echo json_encode(['success'=>false,'error'=>'Name required']);
        exit;
    }

    // check if exists
    $st = $pdo->prepare("SELECT id FROM suppliers WHERE name=? AND user_id=? LIMIT 1");
    $st->execute([$name, $user_id]);
    $id = $st->fetchColumn();

    if (!$id) {
        $stmt = $pdo->prepare("
    INSERT INTO suppliers (name, contact, phone, email, user_id)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $contact, $phone, $email, $user_id]);

        $id = $pdo->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'id' => $id,
        'name' => $name
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() // shows real error
    ]);
}