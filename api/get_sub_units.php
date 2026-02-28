<?php
// api/get_sub_units.php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $unit_id = $_GET['unit_id'] ?? null;
    
    $sql = "SELECT DISTINCT sub_unit FROM hr_positions WHERE sub_unit IS NOT NULL AND sub_unit != ''";
    $params = [];

    if ($unit_id && $unit_id !== 'all') {
        $sql .= " AND unit_id = ?";
        $params[] = $unit_id;
    }
    
    $sql .= " ORDER BY sub_unit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $subUnits = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($subUnits);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([]);
}
?>
