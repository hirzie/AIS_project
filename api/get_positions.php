<?php
// api/get_positions.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, name, unit_id FROM hr_positions ORDER BY name ASC");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enrich with unit name if needed, but for dropdown usually name is enough.
    // If unit_id is present, we can filter in frontend if we want.
    
    echo json_encode($positions);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
