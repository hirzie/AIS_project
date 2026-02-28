<?php
require_once 'config/database.php';

try {
    // Check if columns exist
    $check = $pdo->query("SHOW COLUMNS FROM acad_subject_teachers LIKE 'weekly_count'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE acad_subject_teachers ADD COLUMN weekly_count INT DEFAULT 1");
        echo "Added weekly_count column.\n";
    }

    $check = $pdo->query("SHOW COLUMNS FROM acad_subject_teachers LIKE 'session_length'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE acad_subject_teachers ADD COLUMN session_length INT DEFAULT 1");
        echo "Added session_length column.\n";
    }
    
    echo "Database migration completed.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>