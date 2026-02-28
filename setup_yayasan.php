<?php
require 'config/database.php';
// Add YAYASAN if not exists
$stmt = $pdo->prepare("SELECT id FROM core_units WHERE code = 'YAYASAN'");
$stmt->execute();
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO core_units (code, name, type, receipt_code) VALUES ('YAYASAN', 'Yayasan Al-Amanah', 'FOUNDATION', 'YS')");
    echo "Added YAYASAN unit.\n";
} else {
    echo "YAYASAN unit already exists.\n";
}

$stmt = $pdo->query('SELECT * FROM core_units');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>