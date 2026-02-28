<?php
header('Content-Type: application/json');
try {
    $modules = require __DIR__ . '/../config/modules.php';
    echo json_encode(['success' => true, 'data' => $modules]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
