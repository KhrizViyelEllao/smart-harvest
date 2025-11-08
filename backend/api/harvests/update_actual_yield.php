<?php
header('Content-Type: application/json');
include '../../db_connect.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload.');
    }

    $harvestId = isset($input['harvest_id']) ? (int)$input['harvest_id'] : 0;
    $actualRaw = $input['actual_yield_kg'] ?? null;
    $notesRaw  = $input['notes'] ?? null;

    if ($harvestId <= 0) {
        throw new Exception('harvest_id is required.');
    }
    if ($actualRaw === null || $actualRaw === '' || !is_numeric($actualRaw)) {
        throw new Exception('actual_yield_kg must be a number.');
    }
    $actual = (float)$actualRaw;
    if ($actual < 0) {
        throw new Exception('actual_yield_kg cannot be negative.');
    }

    // Ensure the harvest exists
    if (!$chk = $conn->prepare('SELECT 1 FROM harvests WHERE harvest_id = ?')) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $chk->bind_param('i', $harvestId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        throw new Exception('Harvest not found.');
    }
    $chk->close();

    // Normalize notes (null if empty)
    $notes = null;
    if ($notesRaw !== null) {
        $notes = trim((string)$notesRaw);
        if ($notes === '') $notes = null;
    }

    // Update
    if (!$stmt = $conn->prepare('UPDATE harvests SET actual_yield_kg = ?, notes = ? WHERE harvest_id = ?')) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('dsi', $actual, $notes, $harvestId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Update failed: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'harvest_id' => $harvestId,
        'actual_yield_kg' => $actual,
        'notes' => $notes,
        'message' => 'Actual yield updated.'
    ]);
} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
}