<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Select all active units (assuming type='SCHOOL' or 'FOUNDATION')
    $stmt = $pdo->query("SELECT * FROM core_units ORDER BY id ASC");
    $rawUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $units = [];
    foreach ($rawUnits as $u) {
        $units[] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'code' => $u['code'], // Keep original code
            'unit_level' => $u['code'], // Map 'code' (TK, SD) to unit_level
            'prefix' => $u['receipt_code'], // Map 'receipt_code' (SDIT, etc) to prefix
            'address' => $u['address'],
            'headmaster' => '', // Column missing in DB, returning empty for now
            'is_active' => 1 // Assuming all fetched are active
        ];
    }
    echo json_encode($units);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
