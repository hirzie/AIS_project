<?php
require 'config/database.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS acad_unit_positions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unit_id INT NOT NULL,
        position VARCHAR(50) NOT NULL COMMENT 'KEPALA, WAKASEK',
        person_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_position (unit_id, position, person_id),
        FOREIGN KEY (unit_id) REFERENCES core_units(id) ON DELETE CASCADE,
        FOREIGN KEY (person_id) REFERENCES core_people(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    echo "Table acad_unit_positions created successfully.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
