<?php
// filepath: c:\xampp\htdocs\Agrilink\backend\api\save_field_task.php
include_once '../db_connect.php';
header('Content-Type: application/json');

// Decode JSON from frontend
$data = json_decode(file_get_contents("php://input"), true);

// Debugging: Save raw input to file (optional)
file_put_contents('../debug_save_field_task.txt', print_r($data, true));

// Validate input
if (!$data || empty($data['farmer_ids']) || empty($data['task']) || empty($data['fields'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$farmer_ids = $data['farmer_ids'];
$fields = $data['fields'];
$details = json_encode($data['details'], JSON_UNESCAPED_UNICODE);

// pull scheduled date/time coming from step 2
$end_date_raw = $data['end_date'] ?? $data['start_date'] ?? null;
$end_time_raw = $data['end_time'] ?? $data['start_time'] ?? null;

$end_date = null;
if ($end_date_raw) {
    $end_date = $end_time_raw ? ($end_date_raw . ' ' . $end_time_raw) : $end_date_raw;
}

// Decode task data
$taskData = json_decode($data['task'], true);
$taskName = $taskData['name'] ?? '';
$taskIdFromData = $taskData['id'] ?? null;

// ✅ Determine task_id
if ($taskIdFromData) {
    $task_id = $taskIdFromData;
} else {
    // If no ID, try to fetch it by name
    $stmt = $conn->prepare("SELECT task_id FROM tasks WHERE task_name = ?");
    $stmt->bind_param("s", $taskName);
    $stmt->execute();
    $taskRes = $stmt->get_result();
    $taskRow = $taskRes->fetch_assoc();

    if (!$taskRow) {
        echo json_encode(["success" => false, "message" => "Task not found: " . $taskName]);
        exit;
    }

    $task_id = $taskRow['task_id'];
}

// ✅ Insert records with date and notes
$stmt = $conn->prepare("
    INSERT INTO field_tasks (
        task_id,
        field_id,
        assigned_farmer_id,
        details,
        status,
        end_date,
        notes,
        start_date
    ) VALUES (
        ?, ?, ?, ?, 'pending', ?, ?, NOW()
    )
");

foreach ($fields as $field_id) {
    foreach ($farmer_ids as $farmer_id) {
        $stmt->bind_param("iiisss", $task_id, $field_id, $farmer_id, $details, $end_date, $notes);
        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
            exit;
        }
    }
}

echo json_encode(["success" => true, "message" => "Task assigned successfully"]);
?>
