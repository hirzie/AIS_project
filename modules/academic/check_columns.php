<?php
require_once __DIR__ . '/../../config/database.php';

try {
    echo "Columns of core_units:\n";
    $stmt = $pdo->query("DESCRIBE core_units");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

    echo "\nColumns of core_users:\n";
    $stmt = $pdo->query("DESCRIBE core_users");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
