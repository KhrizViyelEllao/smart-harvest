<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

function fail($m,$c=400){ http_response_code($c); echo json_encode(['success'=>false,'message'=>$m]); exit; }

try {
  if(($_SERVER['REQUEST_METHOD']??'')!=='POST') fail('Invalid method');
  $in = json_decode(file_get_contents('php://input'), true);
  if(!is_array($in)) fail('Invalid JSON');

  $pid = (int)($in['product_id'] ?? 0);
  $name = trim($in['name'] ?? '');
  $desc = trim($in['description'] ?? '');
  $price = (float)($in['price_per_kg'] ?? 0);
  $avail = (float)($in['available_qty'] ?? 0);
  $quality = trim($in['quality'] ?? '');

  if($pid<=0) fail('product_id required');
  if($name==='') fail('Name required');
  if($price<0) fail('Bad price');
  if($avail<0) fail('Bad quantity');
  if($quality!=='' && !in_array($quality,['high','medium','low'],true)) fail('Invalid quality');

  // Get harvest_id if present
  $stmt = $conn->prepare("SELECT harvest_id FROM products WHERE product_id=?");
  $stmt->bind_param('i',$pid);
  $stmt->execute();
  $r = $stmt->get_result();
  if($r->num_rows===0){ $stmt->close(); fail('Product not found'); }
  $prod = $r->fetch_assoc();
  $stmt->close();

  $stmt2 = $conn->prepare("UPDATE products SET name=?, description=?, price_per_kg=?, available_qty=? WHERE product_id=?");
  $stmt2->bind_param('ssddi',$name,$desc,$price,$avail,$pid);
  if(!$stmt2->execute()) { $stmt2->close(); fail('Update failed'); }
  $stmt2->close();

  if($quality!=='' && (int)$prod['harvest_id']>0){
    $hid = (int)$prod['harvest_id'];
    $stmt3 = $conn->prepare("UPDATE harvests SET quality=? WHERE harvest_id=?");
    $stmt3->bind_param('si',$quality,$hid);
    $stmt3->execute();
    $stmt3->close();
  }

  echo json_encode(['success'=>true]);
} catch(Throwable $e){
  fail($e->getMessage(), 500);
}