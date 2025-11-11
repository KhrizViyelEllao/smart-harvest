<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors','0'); ini_set('html_errors','0');
include '../../db_connect.php';

try {
  $roles = ['farm_owner','farmer','admin'];
  if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $roles, true)) {
    http_response_code(401);
    throw new Exception('Unauthorized');
  }
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');

  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $orderId = (int)($raw['order_id'] ?? 0);
  $newStatus = $raw['status'] ?? '';
  if ($orderId <= 0) throw new Exception('order_id required');
  if (!in_array($newStatus, ['confirmed','completed','cancelled'], true)) throw new Exception('Invalid status');

  $conn->begin_transaction();

  // Lock order
  $stmt = $conn->prepare("SELECT product_id, quantity_kg, status FROM orders WHERE order_id=? FOR UPDATE");
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { $stmt->close(); throw new Exception('Order not found'); }
  $o = $res->fetch_assoc();
  $stmt->close();

  $pid = (int)$o['product_id'];

  // Try to detect ownership path via harvests (products -> harvests -> owner)
  $ownerCol = null;
  $tbl = $conn->query("SHOW TABLES LIKE 'harvests'");
  if ($tbl && $tbl->num_rows) {
    foreach (['user_id','farmer_id','owner_id','created_by'] as $c) {
      $chk = $conn->query("SHOW COLUMNS FROM harvests LIKE '$c'");
      if ($chk && $chk->num_rows === 1) { $ownerCol = $c; break; }
    }
  }

  if ($ownerCol) {
    $ownQ = $conn->prepare("SELECT h.`$ownerCol` AS owner_id
                            FROM products p
                            INNER JOIN harvests h ON h.harvest_id = p.harvest_id
                            WHERE p.product_id=?");
    $ownQ->bind_param('i', $pid);
    $ownQ->execute();
    $ownR = $ownQ->get_result();
    if ($ownR->num_rows === 0) { $ownQ->close(); throw new Exception('Product/Harvest not found'); }
    $ownerId = (int)$ownR->fetch_assoc()['owner_id'];
    $ownQ->close();

    if (($_SESSION['role'] ?? '') !== 'admin' && $ownerId !== (int)$_SESSION['user_id']) {
      http_response_code(403);
      throw new Exception('Forbidden');
    }
  } // else: no owner column; proceed with role-only authorization

  // Validate transition
  $current = $o['status'];
  $valid = ($current==='pending'   && in_array($newStatus,['confirmed','cancelled'],true))
        || ($current==='confirmed' && in_array($newStatus,['completed','cancelled'],true));
  if (!$valid) throw new Exception('Invalid transition');

  // If cancelling, restore product qty
  if ($newStatus === 'cancelled') {
    $qty = (float)$o['quantity_kg'];
    $lock = $conn->prepare("SELECT available_qty, status FROM products WHERE product_id=? FOR UPDATE");
    $lock->bind_param('i',$pid);
    $lock->execute();
    $pr = $lock->get_result();
    if ($pr->num_rows===0){ $lock->close(); throw new Exception('Product missing'); }
    $p = $pr->fetch_assoc();
    $lock->close();

    $newAvail = (float)$p['available_qty'] + $qty;
    $newProdStatus = $newAvail>0 ? 'available' : $p['status'];

    $upd = $conn->prepare("UPDATE products SET available_qty=?, status=? WHERE product_id=?");
    $upd->bind_param('dsi', $newAvail, $newProdStatus, $pid);
    if (!$upd->execute()) { $upd->close(); throw new Exception('Product update failed'); }
    $upd->close();
  }

  $stmt4 = $conn->prepare("UPDATE orders SET status=? WHERE order_id=?");
  $stmt4->bind_param('si', $newStatus, $orderId);
  if (!$stmt4->execute()) { $stmt4->close(); throw new Exception('Status update failed'); }
  $stmt4->close();

  $conn->commit();
  echo json_encode(['success'=>true,'status'=>$newStatus, 'ownership'=>'fallback']);
} catch (Exception $e) {
  if ($conn) $conn->rollback();
  if (!http_response_code()) http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}