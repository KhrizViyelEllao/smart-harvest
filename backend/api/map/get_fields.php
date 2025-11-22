<?php
// filepath: c:\xampp\htdocs\Agrilink\backend\api\map\get_fields.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db_connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// Base field data
$sql = "SELECT field_id, name, area, perimeter, type, notes, geometry FROM fields";
$result = $conn->query($sql);

$fields = [];
while ($row = $result->fetch_assoc()) {
    $id = (int)$row['field_id'];
    $fields[$id] = [
        'field_id'  => $id,
        'name'      => $row['name'],
        'area'      => $row['area'],
        'perimeter' => $row['perimeter'],
        'type'      => $row['type'],
        'notes'     => $row['notes'],
        'geometry'  => json_decode($row['geometry'], true),
        'crops'     => []
    ];
}
$result->free();

// Attach crops per field
if (!empty($fields)) {
    $ids = implode(',', array_keys($fields));
    $cropSql = "
        SELECT
            fc.field_id,
            fc.planting_date,
            fc.expected_harvest,
            c.crop_name,
            c.category
        FROM field_crops fc
        INNER JOIN crops c ON c.crop_id = fc.crop_id
        WHERE fc.field_id IN ($ids)
        ORDER BY fc.planting_date DESC
    ";
    $cropRes = $conn->query($cropSql);
    while ($crop = $cropRes->fetch_assoc()) {
        $fid = (int)$crop['field_id'];
        if (!isset($fields[$fid])) continue;
        $fields[$fid]['crops'][] = [
            'crop_name'        => $crop['crop_name'],
            'category'         => $crop['category'],
            'planting_date'    => $crop['planting_date'],
            'expected_harvest' => $crop['expected_harvest']
        ];
    }
    $cropRes->free();
}

echo json_encode(array_values($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$conn->close();
