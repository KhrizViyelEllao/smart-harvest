<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  $productId = (int)($_GET['product_id'] ?? 0);
  if ($productId <= 0) throw new Exception('product_id required');

  $sql = "
    SELECT pr.review_id, pr.rating, pr.review_text, pr.created_at,
           u.name AS reviewer_name
    FROM product_reviews pr
    LEFT JOIN users u ON u.user_id = pr.user_id
    WHERE pr.product_id = ?
    ORDER BY pr.created_at DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}