<?php
require_once __DIR__ . '/../config/database.php';
$results = [];
try {
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $results[] = "db=" . $db;
    $t1 = $pdo->query("SHOW TABLES LIKE 'acad_student_details'")->rowCount() > 0;
    $results[] = "acad_student_details=" . ($t1 ? "YES" : "NO");
    $t2 = $pdo->query("SHOW TABLES LIKE 'acad_subject_teachers'")->rowCount() > 0;
    $results[] = "acad_subject_teachers=" . ($t2 ? "YES" : "NO");
    $c1 = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'access_modules'")->rowCount() > 0;
    $results[] = "core_users.access_modules=" . ($c1 ? "YES" : "NO");
    $c2 = $pdo->query("SHOW COLUMNS FROM acad_subject_teachers LIKE 'weekly_count'")->rowCount() > 0;
    $results[] = "acad_subject_teachers.weekly_count=" . ($c2 ? "YES" : "NO");
    $c3 = $pdo->query("SHOW COLUMNS FROM acad_subject_teachers LIKE 'session_length'")->rowCount() > 0;
    $results[] = "acad_subject_teachers.session_length=" . ($c3 ? "YES" : "NO");
} catch (Exception $e) {
    $results[] = "error=" . $e->getMessage();
}
echo implode("\n", $results) . "\n";
