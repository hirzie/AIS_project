<?php
require_once __DIR__ . '/config/database.php';

try {
    echo "Checking acad_unit_positions table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `acad_unit_positions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `unit_id` int(11) NOT NULL,
      `person_id` int(11) NOT NULL,
      `position` enum('PRINCIPAL','VICE_PRINCIPAL') NOT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_position` (`unit_id`,`person_id`,`position`),
      KEY `idx_unit` (`unit_id`),
      KEY `idx_person` (`person_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    echo "Table acad_unit_positions ensured.\n";
    
    // Verify columns
    $stmt = $pdo->query("SHOW COLUMNS FROM acad_unit_positions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
