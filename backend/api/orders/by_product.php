<?php

session_start();
header('Content-Type: application/json');
include '../../db_connect.php';

try {
  $allowed = ['farm_owner','farmer','admin']; // include your exact farm owner role key
  if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowed, true)) {
    http_response_code(401);
    throw new Exception('Unauthorized');
  }
  $pid = (int)($_GET['product_id'] ?? 0);
  if ($pid <= 0) throw new Exception('product_id required');

  $stmt = $conn->prepare("SELECT o.order_id,o.buyer_name,o.contact_info,o.quantity_kg,o.total_price,o.status,o.created_at
                          FROM orders o
                          WHERE o.product_id=? AND o.status IN ('pending','confirmed','completed')
                          ORDER BY o.created_at DESC");
  $stmt->bind_param('i', $pid);
  $stmt->execute();
  $res = $stmt->get_result();
  $data = [];
  while($r=$res->fetch_assoc()) $data[]=$r;
  $stmt->close();
  echo json_encode(['success'=>true,'data'=>$data]);
} catch(Exception $e){
  http_response_code(http_response_code() ?: 400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}