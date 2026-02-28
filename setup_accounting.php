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
echo "<h1>Setup Modul Akuntansi</h1>";
try {
    $sql = file_get_contents(__DIR__ . '/database/accounting_schema.sql');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    $pdo->exec($sql);
    echo "<p style='color:green'>✅ Tabel Akuntansi berhasil dibuat & diupdate!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Gagal update tabel: " . $e->getMessage() . "</p>";
}
echo "<p><a href='modules/finance/dashboard.php'>Ke Dashboard Keuangan</a></p>";
