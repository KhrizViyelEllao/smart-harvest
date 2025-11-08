<?php
header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload.');
    }

    $cropId      = isset($input['crop_id']) ? (int)$input['crop_id'] : 0;
    $fieldId     = isset($input['field_id']) ? (int)$input['field_id'] : 0;
    $harvestDate  = trim($input['harvest_date'] ?? '');
    $predictedRaw = $input['predicted_yield_kg'] ?? '';
    $actualRaw    = $input['actual_yield_kg'] ?? '';
    $qualityRaw   = $input['quality'] ?? '';
    $notesRaw     = $input['notes'] ?? '';

    if ($cropId <= 0) {
        throw new Exception('Crop is required.');
    }
    if (!$harvestDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $harvestDate)) {
        throw new Exception('Valid harvest date is required.');
    }
    if ($fieldId <= 0) throw new Exception('Field is required.');

    $predicted = ($predictedRaw === '' || $predictedRaw === null) ? null : (float)$predictedRaw;
    $actual    = ($actualRaw === '' || $actualRaw === null) ? null : (float)$actualRaw;

    $allowedQualities = ['high','medium','low'];
    $quality = strtolower(trim($qualityRaw));
    if ($quality === '' || !in_array($quality, $allowedQualities, true)) {
        throw new Exception('Quality must be one of: high, medium, low.');
    }

    $notes = trim($notesRaw) !== '' ? trim($notesRaw) : null;

    // Optional: locate a related field_task_id
    $fieldTaskId = null;
    $taskLookup = $conn->prepare("
        SELECT field_task_id
        FROM field_tasks
        WHERE field_id = ? AND (crop_id = ? OR crop_id IS NULL)
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if ($taskLookup) {
        $taskLookup->bind_param('ii', $fieldId, $cropId);
        if ($taskLookup->execute()) {
            $res = $taskLookup->get_result();
            if ($row = $res->fetch_assoc()) $fieldTaskId = (int)$row['field_task_id'];
        }
        $taskLookup->close();
    }

    $stmt = $conn->prepare("
        INSERT INTO harvests (crop_id, field_id, harvest_date, predicted_yield_kg, actual_yield_kg, quality, notes, field_task_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    $stmt->bind_param(
        'iisddssi',
        $cropId,
        $fieldId,
        $harvestDate,
        $predicted,
        $actual,
        $quality,
        $notes,
        $fieldTaskId
    );

    if (!$stmt->execute()) throw new Exception('Insert failed: ' . $stmt->error);
    $harvestId = $stmt->insert_id;
    $stmt->close();

    // Fetch field name to return
    $fieldName = null;
    $f = $conn->prepare("SELECT name FROM fields WHERE field_id = ?");
    if ($f) {
        $f->bind_param('i', $fieldId);
        if ($f->execute()) {
            $r = $f->get_result()->fetch_assoc();
            $fieldName = $r['name'] ?? null;
        }
        $f->close();
    }

    echo json_encode([
        'success'     => true,
        'harvest_id'  => $harvestId,
        'field_id'    => $fieldId,
        'field_name'  => $fieldName,
        'quality'     => $quality,
        'message'     => 'Harvest saved successfully.'
    ]);
} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
}