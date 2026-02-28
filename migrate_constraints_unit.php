<?php
require_once 'config/database.php';

try {
    $check = $pdo->query("SHOW COLUMNS FROM acad_schedule_constraints LIKE 'unit_id'");
    if ($check->rowCount() == 0) {
        // Add unit_id column
        $pdo->exec("ALTER TABLE acad_schedule_constraints ADD COLUMN unit_id INT NULL AFTER entity_id");
        $pdo->exec("ALTER TABLE acad_schedule_constraints ADD FOREIGN KEY (unit_id) REFERENCES core_units(id)");
        echo "Added unit_id to constraints.\n";
    } else {
        echo "Column unit_id already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>