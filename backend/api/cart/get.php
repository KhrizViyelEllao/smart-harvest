<?php

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  $userId = (int)($_SESSION['user_id'] ?? ($_GET['user_id'] ?? 0));
  if ($userId <= 0) throw new Exception('user_id required');

  $sql = "
    SELECT ci.cart_item_id, ci.product_id, ci.quantity_kg,
           p.name, p.description, p.price_per_kg, p.available_qty, p.image_url
    FROM cart_items ci
    INNER JOIN products p ON p.product_id = ci.product_id
    WHERE ci.user_id = ?
    ORDER BY ci.updated_at DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode(['success'=>true,'data'=>$items]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}