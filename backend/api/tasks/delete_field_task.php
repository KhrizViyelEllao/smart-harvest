<?php
header('Content-Type: application/json');
include '../../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['DELETE', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fieldTaskId = isset($input['field_task_id']) ? (int)$input['field_task_id'] : 0;

if ($fieldTaskId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid field_task_id']);
    exit;
}

// Optional: verify exists
$exists = false;
if ($stmt = $conn->prepare('SELECT 1 FROM field_tasks WHERE field_task_id = ?')) {
    $stmt->bind_param('i', $fieldTaskId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
}
if (!$exists) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

// Delete task (harvests.field_task_id will be set to NULL via FK ON DELETE SET NULL)
if ($del = $conn->prepare('DELETE FROM field_tasks WHERE field_task_id = ?')) {
    $del->bind_param('i', $fieldTaskId);
    if ($del->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $del->error]);
    }
    $del->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
}