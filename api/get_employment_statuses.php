<?php
// api/get_employment_statuses.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM hr_employment_statuses ORDER BY id ASC");
    $statuses = $stmt->fetchAll();
    echo json_encode($statuses);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
