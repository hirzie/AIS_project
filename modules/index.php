<?php
// Fix for session save path error - use local project directory
$sessPath = __DIR__ . '/../sessions';
if (file_exists($sessPath)) { session_save_path($sessPath); }
elseif (file_exists('C:/xampp/tmp')) { session_save_path('C:/xampp/tmp'); }
session_start();

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$prefix = '/'; // Default to root

// Check local environment (localhost or IP)
if (stripos($host, 'localhost') !== false || preg_match('/^[\d\.]+$/', $host)) {
    if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
        $prefix = '/' . $m[1] . '/';
    } elseif ($_SERVER['SERVER_PORT'] == 8000 || $_SERVER['SERVER_PORT'] == 8080) {
        $prefix = '/';
    }
} else {
    // Production/Staging: force root
    $prefix = '/';
}

header("Location: " . $prefix . "index.php");
exit;
