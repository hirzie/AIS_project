<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Check if receipt_code length is enough
    $stmt = $pdo->query("DESCRIBE core_units receipt_code");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current Type: " . $col['Type'] . "\n";
    
    // Check if length is less than 10
    if (strpos($col['Type'], 'varchar(5)') !== false) {
        echo "Updating receipt_code column length...\n";
        $pdo->exec("ALTER TABLE core_units MODIFY COLUMN receipt_code VARCHAR(20)");
        echo "Updated to VARCHAR(20).\n";
        
        // Fix existing YAYASAN data
        $pdo->exec("UPDATE core_units SET receipt_code = 'YAYASAN' WHERE code = 'YAYASAN' AND receipt_code = 'YAYAS'");
        echo "Fixed truncated 'YAYAS' to 'YAYASAN'.\n";
    } else {
        echo "Column length seems sufficient.\n";
        // Ensure YAYASAN is correct anyway
        $pdo->exec("UPDATE core_units SET receipt_code = 'YAYASAN' WHERE code = 'YAYASAN' AND receipt_code = 'YAYAS'");
        echo "Checked/Fixed 'YAYASAN' data.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

