<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Check if table exists first to avoid fatal errors if DB is partial
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM core_settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode($settings);
} catch (PDOException $e) {
    // Return empty object instead of 500 if just table missing (for init)
    // Or return error
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo json_encode([]); // Empty settings if table missing
    } else {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
