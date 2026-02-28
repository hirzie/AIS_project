<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Create core_units table
    $sql = "CREATE TABLE IF NOT EXISTS `core_units` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `unit_level` enum('TK','SD','SMP','SMA','SMK','OTHER') NOT NULL DEFAULT 'OTHER',
        `prefix` varchar(10) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `headmaster` varchar(100) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table core_units created successfully.\n";

    // Check if empty, then seed
    $stmt = $pdo->query("SELECT COUNT(*) FROM core_units");
    if ($stmt->fetchColumn() == 0) {
        $units = [
            ['TK Islam Al-Amanah', 'TK', 'TKIT'],
            ['SD Islam Al-Amanah', 'SD', 'SDIT'],
            ['SMP Islam Al-Amanah', 'SMP', 'SMPIT'],
            ['SMA Islam Al-Amanah', 'SMA', 'SMAIT']
        ];

        $insert = $pdo->prepare("INSERT INTO core_units (name, unit_level, prefix) VALUES (?, ?, ?)");
        foreach ($units as $u) {
            $insert->execute($u);
        }
        echo "Default units inserted.\n";
    } else {
        echo "Units already exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

