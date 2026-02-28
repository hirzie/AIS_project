<?php
require_once 'config/database.php';

try {
    // 1. Rename Table
    $check = $pdo->query("SHOW TABLES LIKE 'acad_teacher_constraints'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE acad_teacher_constraints RENAME TO acad_schedule_constraints");
        echo "Renamed table.\n";
    }

    // 2. Change Column teacher_id -> entity_id
    // Check if column exists first to avoid error if re-run
    $checkCol = $pdo->query("SHOW COLUMNS FROM acad_schedule_constraints LIKE 'teacher_id'");
    if ($checkCol->rowCount() > 0) {
        // Drop FK first (name usually table_ibfk_1 but might vary, let's try generic drop or ignore error)
        // MariaDB/MySQL usually requires exact name.
        // Let's try to query constraint name.
        $dbName = 'ais_school'; // Or fetch from config? assuming default from context or just try-catch
        
        // Simpler: Just try to drop the FK by standard name.
        try {
            $pdo->exec("ALTER TABLE acad_schedule_constraints DROP FOREIGN KEY acad_teacher_constraints_ibfk_1");
        } catch (Exception $e) { /* Ignore if not exists */ }

        $pdo->exec("ALTER TABLE acad_schedule_constraints CHANGE COLUMN teacher_id entity_id INT NOT NULL");
        echo "Changed teacher_id to entity_id.\n";
    }

    // 3. Add Type Column
    $checkType = $pdo->query("SHOW COLUMNS FROM acad_schedule_constraints LIKE 'type'");
    if ($checkType->rowCount() == 0) {
        $pdo->exec("ALTER TABLE acad_schedule_constraints ADD COLUMN type ENUM('TEACHER', 'SUBJECT') NOT NULL DEFAULT 'TEACHER' AFTER id");
        echo "Added type column.\n";
    }

    echo "Migration constraints completed.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>