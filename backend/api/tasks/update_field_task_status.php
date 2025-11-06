<?php
// filepath: c:\xampp\htdocs\Agrilink\backend\api\tasks\update_field_task_status.php
header('Content-Type: application/json');
include '../../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$field_task_id = intval($input['field_task_id'] ?? 0);
$status = trim($input['status'] ?? '');

if ($field_task_id <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Validate status
$validStatuses = ['pending', 'in-progress', 'completed', 'abandoned', 'deleted'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    if ($status === 'deleted') {
        // Delete the task
        $stmt = $conn->prepare("DELETE FROM field_tasks WHERE field_task_id = ?");
        $stmt->bind_param('i', $field_task_id);
    } else {
        // Update status
        $stmt = $conn->prepare("UPDATE field_tasks SET status = ? WHERE field_task_id = ?");
        $stmt->bind_param('si', $status, $field_task_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update task']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>