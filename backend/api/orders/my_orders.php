<?php
session_start();
header('Content-Type: application/json');
include '../../db_connect.php';

try {
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') throw new Exception('Unauthorized');
  $uid = (int)$_SESSION['user_id'];
  $sql = "SELECT o.order_id, o.product_id, p.name AS product_name, o.quantity_kg, o.total_price, o.status, o.created_at
          FROM orders o
          LEFT JOIN products p ON o.product_id = p.product_id
          WHERE o.user_id = ?
          ORDER BY o.created_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $uid);
  $stmt->execute();
  $res = $stmt->get_result();
  $data = [];
  while ($r = $res->fetch_assoc()) $data[] = $r;
  $stmt->close();
  echo json_encode(['success'=>true,'data'=>$data]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}