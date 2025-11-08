<?php
header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload.');
    }

    $fieldTaskId = isset($input['field_task_id']) ? (int)$input['field_task_id'] : 0;
    $status      = trim($input['status'] ?? '');
    $endDate     = trim($input['end_date'] ?? '');

    if ($fieldTaskId <= 0) {
        throw new Exception('Valid field_task_id is required.');
    }
    if ($status === '') {
        throw new Exception('Status is required.');
    }

    $params = [$status];
    $types  = 's';
    $setClauses = ['status = ?', 'updated_at = NOW()'];

    if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $setClauses[] = 'end_date = ?';
        $params[] = $endDate;
        $types .= 's';
    }

    $params[] = $fieldTaskId;
    $types .= 'i';

    $sql = "UPDATE field_tasks SET " . implode(', ', $setClauses) . " WHERE field_task_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception('Update failed: ' . $stmt->error);
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Task updated successfully.'
    ]);
} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
}