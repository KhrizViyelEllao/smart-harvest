<?php
header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');

  $harvestId = (int)($_POST['harvest_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $price = $_POST['price_per_kg'] ?? '';
  $qty = $_POST['available_qty'] ?? '';

  if ($harvestId <= 0) throw new Exception('harvest_id required');
  if ($name === '') throw new Exception('name required');
  if ($price === '' || !is_numeric($price) || $price < 0) throw new Exception('price invalid');
  if ($qty === '' || !is_numeric($qty) || $qty < 0) throw new Exception('quantity invalid');

  // Ensure harvest exists and belongs to current farmer (if logged in)
  $farmerId = $_SESSION['farmer_id'] ?? null;
  $chkSql = "SELECT h.harvest_id, f.farmer_id FROM harvests h LEFT JOIN fields f ON h.field_id = f.field_id WHERE h.harvest_id = ?";
  $chk = $conn->prepare($chkSql);
  if (!$chk) throw new Exception('DB error');
  $chk->bind_param('i', $harvestId);
  $chk->execute();
  $res = $chk->get_result();
  if (!$res->num_rows) { $chk->close(); throw new Exception('Harvest not found'); }
  $row = $res->fetch_assoc();
  $chk->close();
  if ($farmerId && $row['farmer_id'] && (int)$row['farmer_id'] !== (int)$farmerId) throw new Exception('Not authorized for this harvest');

  // Prevent duplicate listing per harvest
  $dup = $conn->prepare("SELECT product_id FROM products WHERE harvest_id = ? LIMIT 1");
  $dup->bind_param('i', $harvestId);
  $dup->execute();
  $dup->store_result();
  if ($dup->num_rows > 0) { $dup->close(); throw new Exception('This harvest is already listed'); }
  $dup->close();

  // Handle image upload
  $imageUrl = null;
  if (!empty($_FILES['image']['name'])) {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array(mime_content_type($_FILES['image']['tmp_name']), $allowed, true)) {
      throw new Exception('Unsupported image type');
    }
    $uploadDir = '../../../uploads/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $fname = 'prod_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    $dest = $uploadDir . $fname;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) throw new Exception('Image upload failed');
    $imageUrl = 'uploads/products/' . $fname; // web path
  }

  $stmt = $conn->prepare("INSERT INTO products (harvest_id, name, description, price_per_kg, available_qty, image_url) VALUES (?,?,?,?,?,?)");
  if (!$stmt) throw new Exception('Prepare failed');
  $priceF = (float)$price; $qtyF = (float)$qty;
  $stmt->bind_param('issdds', $harvestId, $name, $desc, $priceF, $qtyF, $imageUrl);
  if (!$stmt->execute()) throw new Exception('Insert failed: ' . $stmt->error);
  $id = $stmt->insert_id;
  $stmt->close();

  echo json_encode(['success' => true, 'product_id' => $id]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}