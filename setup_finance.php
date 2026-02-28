<?php
require_once 'config/database.php';
// Fix for session save path error - use local project directory
$sessPath = __DIR__ . '/sessions';
if (file_exists($sessPath)) { session_save_path($sessPath); }
elseif (file_exists('C:/xampp/tmp')) { session_save_path('C:/xampp/tmp'); }
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'SUPERADMIN') {
    header("Location: login.php");
    exit;
}
echo "<h1>Setup Modul Keuangan</h1>";
try {
    $sql = file_get_contents(__DIR__ . '/database/finance_schema.sql');
    $pdo->exec($sql);
    echo "<p style='color:green'>✅ Tabel Keuangan berhasil dibuat!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Gagal membuat tabel: " . $e->getMessage() . "</p>";
}
echo "<p><a href='modules/finance/dashboard.php'>Ke Dashboard Keuangan</a></p>";
