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
    $conn->set_charset('utf8mb4');

    $stmt = $conn->prepare("SELECT harvest_id FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $productRes = $stmt->get_result();
    if (!$productRes || $productRes->num_rows === 0) {
        $stmt->close();
        respond(['success' => false, 'message' => 'Product not found'], 404);
    }
    $productRow = $productRes->fetch_assoc();
    $stmt->close();

    $fields = [];
    $params = [];
    $types  = '';

    if (isset($input['name'])) {
        $name = trim((string)$input['name']);
        if ($name === '') {
            respond(['success' => false, 'message' => 'Name cannot be empty'], 400);
        }
        $fields[] = 'name = ?';
        $types   .= 's';
        $params[] = $name;
    }

    if (isset($input['description'])) {
        $fields[] = 'description = ?';
        $types   .= 's';
        $params[] = trim((string)$input['description']);
    }

    if (isset($input['price_per_kg'])) {
        $price = (float)$input['price_per_kg'];
        if ($price < 0) {
            respond(['success' => false, 'message' => 'Price cannot be negative'], 400);
        }
        $fields[] = 'price_per_kg = ?';
        $types   .= 'd';
        $params[] = $price;
    }

    if (isset($input['available_qty'])) {
        $available = (float)$input['available_qty'];
        if ($available < 0) {
            respond(['success' => false, 'message' => 'Available quantity cannot be negative'], 400);
        }

        $harvestId = (int)$productRow['harvest_id'];
        if ($harvestId > 0) {
            $stmtHarvest = $conn->prepare("SELECT actual_yield_kg FROM harvests WHERE harvest_id = ?");
            $stmtHarvest->bind_param('i', $harvestId);
            $stmtHarvest->execute();
            $harvestRes = $stmtHarvest->get_result();
            if ($harvestRes && $harvestRes->num_rows) {
                $harvest = $harvestRes->fetch_assoc();
                if ($harvest['actual_yield_kg'] !== null && $available > (float)$harvest['actual_yield_kg']) {
                    $stmtHarvest->close();
                    respond([
                        'success' => false,
                        'message' => 'Available quantity (' . number_format($available, 2) . ' kg) cannot exceed recorded harvest actual (' . number_format((float)$harvest['actual_yield_kg'], 2) . ' kg).'
                    ], 422);
                }
            }
            $stmtHarvest->close();
        }

        $fields[] = 'available_qty = ?';
        $types   .= 'd';
        $params[] = $available;
    }

    if (isset($input['status'])) {
        $status = trim((string)$input['status']);
        if (!in_array($status, ['available', 'sold_out'], true)) {
            respond(['success' => false, 'message' => 'Invalid status value'], 400);
        }
        $fields[] = 'status = ?';
        $types   .= 's';
        $params[] = $status;
    }

    $hasQuality = false;
    if ($res = $conn->query("SHOW COLUMNS FROM products LIKE 'quality'")) {
        $hasQuality = $res->num_rows > 0;
        $res->free();
    }
    if ($hasQuality && array_key_exists('quality', $input)) {
        $fields[] = 'quality = ?';
        $types   .= 's';
        $params[] = trim((string)$input['quality']);
    }

    if (!$fields) {
        respond(['success' => false, 'message' => 'Nothing to update'], 400);
    }

    $types   .= 'i';
    $params[] = $productId;

    $sql  = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE product_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    respond(['success' => true, 'updated' => $affected >= 0]);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => $e->getMessage()], 500);
}