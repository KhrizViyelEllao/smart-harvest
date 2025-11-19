<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db_connect.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(['success' => false, 'message' => 'Invalid JSON payload'], 400);
}

$productId = (int)($input['product_id'] ?? 0);
if ($productId <= 0) {
    respond(['success' => false, 'message' => 'Product ID is required'], 400);
}

try {
    $stmt = $conn->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected <= 0) {
        respond(['success' => false, 'message' => 'Product not found'], 404);
    }

    respond(['success' => true]);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => $e->getMessage()], 500);
}