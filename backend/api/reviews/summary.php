<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  $productId = (int)($_GET['product_id'] ?? 0);
  if ($productId <= 0) throw new Exception('product_id required');

  $sql = "
    SELECT
      COUNT(*) AS total_reviews,
      COALESCE(AVG(rating),0) AS avg_rating,
      SUM(rating=5) AS five_star,
      SUM(rating=4) AS four_star,
      SUM(rating=3) AS three_star,
      SUM(rating=2) AS two_star,
      SUM(rating=1) AS one_star
    FROM product_reviews
    WHERE product_id = ?
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  $summary = $res ? $res->fetch_assoc() : [];
  $stmt->close();

  echo json_encode(['success'=>true,'data'=>$summary]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}