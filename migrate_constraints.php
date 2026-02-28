<?php
require_once 'config/database.php';

try {
    $sql = file_get_contents('database/create_constraints.sql');
    $pdo->exec($sql);
    echo "Tabel acad_teacher_constraints berhasil dibuat.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>