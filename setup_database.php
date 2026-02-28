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
echo "<h1>AIS Database Setup Tool</h1>";
echo "<pre>";
try {
    echo "Import Struktur Tabel...\n";
    $schemaSql = file_get_contents(__DIR__ . '/database/full_schema.sql');
    $pdo->exec($schemaSql);
    echo "OK\n";
    echo "\nSETUP SELESAI\n";
    echo "</pre>";
    echo '<br><a href="index.php"><button>Buka Aplikasi</button></a>';
} catch (PDOException $e) {
    die("\nERROR: " . $e->getMessage());
} catch (Exception $e) {
    die("\nERROR: " . $e->getMessage());
}
?>
