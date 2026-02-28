<?php
require_once __DIR__ . '/../../config/database.php';

try {
    // $pdo is exposed by config/database.php
    
    echo "Checking acad_unit_positions table...\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'acad_unit_positions'");
    if ($stmt->rowCount() == 0) {
        echo "Table does not exist. Creating...\n";
        $sql = "CREATE TABLE `acad_unit_positions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `unit_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `position` enum('PRINCIPAL','VICE_PRINCIPAL') NOT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_unit_position` (`unit_id`, `position`),
            KEY `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
        echo "Table created successfully.\n";
    } else {
        echo "Table exists. Checking columns...\n";
        $stmt = $pdo->query("DESCRIBE acad_unit_positions");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        print_r($columns);
    }

    // Migrate data from acad_units if needed (backwards compatibility/initial seed)
    // Only if acad_units has principal_id and vice_principal_id
    $stmt = $pdo->query("SHOW COLUMNS FROM acad_units LIKE 'principal_id'");
    if ($stmt->rowCount() > 0) {
        echo "Migrating data from acad_units...\n";
        $units = $pdo->query("SELECT id, principal_id, vice_principal_id FROM acad_units")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($units as $unit) {
            if (!empty($unit['principal_id'])) {
                $sql = "INSERT IGNORE INTO acad_unit_positions (unit_id, user_id, position) VALUES (?, ?, 'PRINCIPAL')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$unit['id'], $unit['principal_id']]);
            }
            if (!empty($unit['vice_principal_id'])) {
                $sql = "INSERT IGNORE INTO acad_unit_positions (unit_id, user_id, position) VALUES (?, ?, 'VICE_PRINCIPAL')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$unit['id'], $unit['vice_principal_id']]);
            }
        }
        echo "Migration attempted.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
