<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

try {
    // 1. Handle File Upload (Logo)
    $logoUrl = null;
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['logo_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $uploadDir = '../uploads/logos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFilename = 'school_logo_' . time() . '.' . $ext;
            $dest = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                $logoUrl = 'uploads/logos/' . $newFilename;
            }
        }
    }

    // 2. Handle Text Data
    // Note: If sending FormData (multipart), $_POST is populated. 
    // If sending JSON raw, we need php://input. 
    // Since we have file upload, frontend MUST send FormData.
    
    $settings = $_POST;
    
    // If a new logo was uploaded, update the logo setting
    if ($logoUrl) {
        $settings['logo_url'] = $logoUrl;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'logo_url' => $logoUrl]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

