<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid request'
    ]);
    exit;
}

$ticketId = $_POST['ticket_id'] ?? '';

if (!$ticketId) {
    echo json_encode([
        'ok' => false,
        'error' => 'Missing ticket ID'
    ]);
    exit;
}

$tickets = $_SESSION['tickets'] ?? [];

$ticket = null;

foreach ($tickets as $t) {

    $id = $t['id'] ?? '';

    if ($id === $ticketId) {
        $ticket = $t;
        break;
    }
}

if (!$ticket) {

    echo json_encode([
        'ok' => false,
        'error' => 'Ticket not found'
    ]);

    exit;
}

$items = $ticket['items'] ?? [];

$total = 0;

foreach ($items as $item) {

    $qty = (float)($item['qty'] ?? 0);
    $price = (float)($item['unit_price'] ?? 0);

    $total += ($qty * $price);
}

echo json_encode([
    'ok' => true,
    'ticket' => [
        'id' => $ticket['id'] ?? '',
        'created_at' => $ticket['created_at'] ?? '',
        'customer' => $ticket['customer'] ?? 'Walk-in',
        'notes' => $ticket['notes'] ?? ''
    ],
    'lines' => $items,
    'total' => number_format($total, 2, '.', '')
]);

exit;
