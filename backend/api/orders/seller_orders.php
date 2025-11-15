<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed = ['pending','confirmed','completed','cancelled'];
if ($status !== '' && !in_array($status, $allowed, true)) $status = '';

try {
  $sql = "
    SELECT
      o.order_id,
      o.product_id,
      o.quantity_kg,
      o.total_price,
      o.status,
      o.created_at,
      o.buyer_name,
      o.contact_info,
      o.delivery_option,
      o.address,
      p.name AS product_name
    FROM orders o
    INNER JOIN products p ON p.product_id = o.product_id
    WHERE 1 = 1
  ";
  $params = [];
  $types  = '';

  if ($status !== '') {
    $sql .= " AND o.status = ? ";
    $params[] = $status;
    $types    .= 's';
  }

  $sql .= " ORDER BY o.order_id DESC ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception('Prepare failed: '.$conn->error);
  if ($params) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res  = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}