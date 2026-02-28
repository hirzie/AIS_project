<?php
require_once __DIR__ . '/../../config/database.php';

try {
    echo "Listing tables with 'unit' in name:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%unit%'");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    echo "\nListing tables with 'acad' in name:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'acad_%'");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
