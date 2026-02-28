<?php
require_once __DIR__ . '/config/database.php';

echo "Installing Teacher Features Schema...\n";

try {
    // 1. Create Tasks Table
    $sql1 = "CREATE TABLE IF NOT EXISTS acad_teacher_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        due_date DATE,
        status ENUM('PENDING', 'IN_PROGRESS', 'DONE') DEFAULT 'PENDING',
        priority ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'MEDIUM',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES core_people(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql1);
    echo "Table 'acad_teacher_tasks' created or already exists.\n";

    // 2. Add Holder to Inventory
    try {
        $sql2 = "ALTER TABLE inv_assets_movable ADD COLUMN holder_id INT DEFAULT NULL";
        $pdo->exec($sql2);
        echo "Column 'holder_id' added to 'inv_assets_movable'.\n";
        
        $sql3 = "ALTER TABLE inv_assets_movable ADD CONSTRAINT fk_inv_holder FOREIGN KEY (holder_id) REFERENCES core_people(id) ON DELETE SET NULL";
        $pdo->exec($sql3);
        echo "Foreign key 'fk_inv_holder' added.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column 'holder_id' already exists in 'inv_assets_movable'.\n";
        } else {
            throw $e;
        }
    }

    echo "Installation complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
