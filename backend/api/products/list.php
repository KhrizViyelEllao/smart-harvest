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

try {
    $conn->set_charset('utf8mb4');

    $hasQuality = false;
    if ($res = $conn->query("SHOW COLUMNS FROM products LIKE 'quality'")) {
        $hasQuality = $res->num_rows > 0;
        $res->free();
    }
    $qualitySelect = $hasQuality ? 'p.quality' : 'NULL AS quality';

    $sql = "
        SELECT
            p.product_id,
            p.name,
            p.description,
            p.price_per_kg,
            p.available_qty,
            p.status,
            p.image_url,
            p.created_at,
            p.harvest_id,
            {$qualitySelect},
            h.actual_yield_kg,
            h.quality AS harvest_quality,
            h.harvest_date,
            c.crop_name,
            f.name AS field_name
        FROM products p
        LEFT JOIN harvests h ON h.harvest_id = p.harvest_id
        LEFT JOIN crops c    ON c.crop_id   = h.crop_id
        LEFT JOIN fields f   ON f.field_id  = h.field_id
        ORDER BY p.created_at DESC
    ";
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Failed to load products: ' . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['price_per_kg']     = $row['price_per_kg'] !== null ? (float)$row['price_per_kg'] : null;
        $row['available_qty']    = $row['available_qty'] !== null ? (float)$row['available_qty'] : null;
        $row['harvest_actual_kg'] = $row['actual_yield_kg'] !== null ? (float)$row['actual_yield_kg'] : null;
        unset($row['actual_yield_kg']);
        $data[] = $row;
    }
    $result->free();

    respond(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => $e->getMessage()], 500);
}