<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db_connect.php';

$data     = json_decode(file_get_contents('php://input'), true);
$fieldId  = isset($data['field_id']) ? (int)$data['field_id'] : 0;

if ($fieldId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid field_id']);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

try {
    $conn->begin_transaction();

    // Remove dependent records first (adjust if more tables reference fields.field_id)
    $stmt = $conn->prepare('DELETE FROM field_crops WHERE field_id = ?');
    $stmt->bind_param('i', $fieldId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM field_tasks WHERE field_id = ?');
    $stmt->bind_param('i', $fieldId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM fields WHERE field_id = ?');
    $stmt->bind_param('i', $fieldId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected <= 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Field not found or already deleted']);
        exit;
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
