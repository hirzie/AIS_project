<?php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Create core_settings table if not exists (Key-Value store approach is flexible)
    $sqlSettings = "CREATE TABLE IF NOT EXISTS `core_settings` (
        `setting_key` varchar(50) NOT NULL,
        `setting_value` text,
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlSettings);
    echo "core_settings table check/create done.\n";

    // 2. Seed Foundation Data if missing
    // Check if any FOUNDATION unit exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM core_units WHERE type = 'FOUNDATION'");
    if ($stmt->fetchColumn() == 0) {
        $sqlFoundation = "INSERT INTO core_units (name, code, receipt_code, address, type, created_at) 
                          VALUES ('Yayasan Al-Amanah', 'YAYASAN', 'YYS', 'Jl. Yayasan No. 1', 'FOUNDATION', NOW())";
        $pdo->exec($sqlFoundation);
        echo "Foundation data seeded.\n";
    } else {
        echo "Foundation data already exists.\n";
    }

    // 3. Check core_units structure to ensure we have what we need
    $stmt = $pdo->query("DESCRIBE core_units");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nColumns in core_units:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    // 4. Ensure core_users has optional columns (email, status, access_modules)
    $stmt = $pdo->query("DESCRIBE core_users");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('email', $cols)) {
        $pdo->exec("ALTER TABLE core_users ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `password_hash`");
        echo "core_users.email added.\n";
    } else {
        echo "core_users.email exists.\n";
    }
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE core_users ADD COLUMN `status` enum('ACTIVE','SUSPENDED') DEFAULT 'ACTIVE' AFTER `email`");
        echo "core_users.status added.\n";
    } else {
        echo "core_users.status exists.\n";
    }
    if (!in_array('access_modules', $cols)) {
        $pdo->exec("ALTER TABLE core_users ADD COLUMN `access_modules` text DEFAULT NULL AFTER `status`");
        echo "core_users.access_modules added.\n";
    } else {
        echo "core_users.access_modules exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

