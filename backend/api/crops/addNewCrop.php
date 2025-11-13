<?php
header("Content-Type: application/json");
include '../../db_connect.php';

class CropController {
    private $conn;
    // Save directly into assets/images
    private $uploadDir = __DIR__ . '/../../../assets/images';

    public function __construct($db) {
        $this->conn = $db;
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0775, true);
        }
    }

    public function addCrop() {
        $crop_name   = $this->pickStr(['crop_name','cropName','name']);
        $description = $this->pickStr(['description','desc']);
        $category    = $this->pickStr(['category']);
        $duration    = $this->pickInt(['duration','duration_days','days']);

        if ($crop_name === '' || $duration <= 0) {
            return $this->response(false, "Crop name and valid duration are required.");
        }

        $check = $this->conn->prepare("SELECT crop_id FROM crops WHERE crop_name = ?");
        $check->bind_param("s", $crop_name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return $this->response(false, "Crop name already exists.");
        }

        $imagePath = $this->handleUpload($_FILES['image_file'] ?? null);

        $stmt = $this->conn->prepare("
            INSERT INTO crops (crop_name, description, image_path, category, duration)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $crop_name, $description, $imagePath, $category, $duration);

        if (!$stmt->execute()) {
            return $this->response(false, "Database insert failed: " . $stmt->error);
        }

        return $this->response(true, "New crop added successfully!", [
            "crop_id" => $stmt->insert_id,
            "image_path" => $imagePath
        ]);
    }

    private function pickStr(array $keys): string {
        foreach ($keys as $k) {
            if (isset($_POST[$k])) return trim((string)$_POST[$k]);
        }
        return '';
    }

    private function pickInt(array $keys): int {
        foreach ($keys as $k) {
            if (!isset($_POST[$k])) continue;
            $raw = trim((string)$_POST[$k]);
            if ($raw === '') continue;
            if (is_numeric($raw)) return max(0, (int)$raw);
            if (preg_match('/\d+/', $raw, $m)) return max(0, (int)$m[0]);
        }
        return 0;
    }

    private function handleUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return '';

        $maxMB = 10;
        if ($file['size'] > $maxMB * 1024 * 1024) {
            throw new Exception("Image exceeds {$maxMB}MB limit.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) throw new Exception("Invalid image type.");

        $ext = $allowed[$mime];
        $base = preg_replace('/[^a-z0-9_-]/i','_', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
        $fname = $base . '_' . uniqid() . '.' . $ext;
        $target = $this->uploadDir . '/' . $fname;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            error_log('addNewCrop: failed to move upload to ' . $target);
            throw new Exception("Failed to save image.");
        }

        // Store relative path pointing to assets/images
        return 'assets/images/' . $fname;
    }

    private function response($success, $message, $extra = []) {
        return array_merge(['success'=>$success,'message'=>$message], $extra);
    }
}

try {
    // Distinguish JSON (legacy) vs multipart
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $json = json_decode(file_get_contents("php://input"), true);
        if (!$json) { echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit; }
        // Simple JSON path route (no file upload)
        $controller = new CropController($conn);
        // Simulate POST for reuse
        $_POST = $json;
        echo json_encode($controller->addCrop());
        exit;
    }

    // Multipart form
    $controller = new CropController($conn);
    echo json_encode($controller->addCrop());
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
