<?php
header('Content-Type: application/json');
include_once '../../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['field_id']) || empty($input['field_id'])) {
  echo json_encode(["success" => false, "message" => "Missing field_id"]);
  exit;
}

$field_id = intval($input['field_id']);

try {
  // Select crops that belong to the given field
  $fieldStmt = $conn->prepare("
    SELECT field_id, name, area, perimeter, type, notes
    FROM fields
    WHERE field_id = ?
  ");
  $fieldStmt->bind_param("i", $field_id);
  $fieldStmt->execute();
  $fieldInfo = $fieldStmt->get_result()->fetch_assoc();
  $fieldStmt->close();

  $stmt = $conn->prepare("
    SELECT 
      c.crop_id,
      c.crop_name,
      c.description,
      c.image_path,
      fc.planting_date,
      fc.expected_harvest
    FROM field_crops fc
    INNER JOIN crops c ON fc.crop_id = c.crop_id
    WHERE fc.field_id = ?
  ");

  $stmt->bind_param("i", $field_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $crops = [];
  while ($row = $result->fetch_assoc()) {
    $crops[] = $row;
  }

  if (empty($crops)) {
    echo json_encode(["success" => false, "message" => "No crops found for this field.", "data" => []]);
  } else {
    echo json_encode([
      "success" => true,
      "data"    => $crops,
      "field"   => $fieldInfo
    ]);
  }

} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
