<?php
// filepath: c:\xampp\htdocs\Agrilink\backend\api\tasks\get_field_task_details.php
header('Content-Type: application/json');
include '../../db_connect.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid task ID']);
    exit;
}

$stmt = $conn->prepare("
  SELECT 
    ft.field_task_id,
    ft.task_id,
    ft.field_id,
    ft.assigned_farmer_id,
    ft.start_date,
    ft.end_date,
    ft.status,
    ft.notes,
    ft.details,
    t.task_name,
    t.description as task_description,
    t.icon,
    t.category,
    f.name as field_name,
    f.area,
    fr.farmer_name,
    GROUP_CONCAT(DISTINCT c.crop_name SEPARATOR ', ') as crop_name
  FROM field_tasks ft
  JOIN tasks t ON ft.task_id = t.task_id
  LEFT JOIN fields f ON ft.field_id = f.field_id
  LEFT JOIN farmers fr ON ft.assigned_farmer_id = fr.farmer_id
  LEFT JOIN field_crops fc ON ft.field_id = fc.field_id
  LEFT JOIN crops c ON fc.crop_id = c.crop_id
  WHERE ft.field_task_id = ?
  GROUP BY ft.field_task_id
");

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format dates
    if ($row['start_date']) {
        $row['start_date'] = date('M d, Y', strtotime($row['start_date']));
    }
    if ($row['end_date']) {
        $row['end_date'] = date('M d, Y g:i A', strtotime($row['end_date']));
    }
    
    // ✅ Parse and format details JSON
    if ($row['details']) {
        $detailsArray = json_decode($row['details'], true);
        if ($detailsArray && is_array($detailsArray)) {
            $formatted = [];
            foreach ($detailsArray as $key => $value) {
                // Convert snake_case or camelCase to Title Case
                $label = ucwords(str_replace(['_', '-'], ' ', $key));
                
                // Format values nicely
                if (is_array($value)) {
                    $value = implode(', ', $value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (empty($value)) {
                    $value = 'N/A';
                }
                
                $formatted[] = "<strong>{$label}:</strong> {$value}";
            }
            $row['details_formatted'] = implode('<br>', $formatted);
        } else {
            $row['details_formatted'] = 'No additional details';
        }
    } else {
        $row['details_formatted'] = 'No additional details';
    }
    
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Task not found']);
}

$stmt->close();
$conn->close();
?>