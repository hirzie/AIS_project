<?php
// config/database.php
// Konfigurasi Database Cerdas (Auto-Detect Local vs Server)

$host = getenv('DB_HOST') ?: 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isCli = (PHP_SAPI === 'cli');
$dirPath = __DIR__;
$isTestInstance = (
    preg_match('#^/AIStest/#i', $scriptName) ||
    preg_match('#/AIStest/?$#i', $docRoot) ||
    (stripos($serverName, 'test') !== false) ||
    (stripos($dirPath, 'AIStest') !== false)
);
$envDb = getenv('DB_NAME') ?: null;
$dbname = $envDb ?: ($isTestInstance ? 'aiscore_test' : 'aiscore');

// Set Timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Ambil variable server dengan aman
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

// Cek apakah script berjalan di Localhost (XAMPP) atau Server Live
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$isLocal = $isCli ? true : ($serverName === 'localhost' || $remoteAddr === '127.0.0.1' || $remoteAddr === '::1');

$envUser = getenv('DB_USER') ?: null;
$envPass = getenv('DB_PASS') ?: null;
if ($envUser !== null || $envPass !== null) {
    $username = $envUser ?? '';
    $password = $envPass ?? '';
    if ($isLocal) { $host = $host ?: '127.0.0.1'; }
} else {
    if ($isLocal) {
        $host = '127.0.0.1';
        $username = 'root';
        $password = '';
    } else {
        if ($isTestInstance) {
            $username = 'aiscore_test';
            $password = '12345654';
        } else {
            $username = 'aiscore';
            $password = '12345654';
        }
    }
}

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    try {
        $pdo->exec("SET time_zone = '+07:00'");
    } catch (\Throwable $e) { /* ignore */ }
} catch (PDOException $e) {
    $msg = $e->getMessage();
    $unknownDb = (stripos($msg, 'Unknown database') !== false);
    if ($unknownDb && $isLocal) {
        try {
            $pdoProbe = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdoProbe->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $cAiscore = $pdoProbe->query("SHOW DATABASES LIKE 'aiscore'")->rowCount() > 0;
            $cAis = $pdoProbe->query("SHOW DATABASES LIKE 'ais'")->rowCount() > 0;
            $cTest = $pdoProbe->query("SHOW DATABASES LIKE 'aiscore_test'")->rowCount() > 0;
            $cSchoolDb = $pdoProbe->query("SHOW DATABASES LIKE 'school_db'")->rowCount() > 0;
            $cAISdb = $pdoProbe->query("SHOW DATABASES LIKE 'AIS_db'")->rowCount() > 0;
            if ($cAiscore) { $dbname = 'aiscore'; }
            else if ($cAis) { $dbname = 'ais'; }
            else if ($cTest) { $dbname = 'aiscore_test'; }
            else if ($cSchoolDb) { $dbname = 'school_db'; }
            else if ($cAISdb) { $dbname = 'AIS_db'; }
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            try { $pdo->exec("SET time_zone = '+07:00'"); } catch (\Throwable $e2) {}
        } catch (PDOException $e2) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
            }
            echo json_encode(['success'=>false,'error'=>'Database Connection Failed','message'=>$e2->getMessage()]);
            exit;
        }
    } else {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode(['success'=>false,'error'=>'Database Connection Failed','message'=>$msg]);
        exit;
    }
}
?>
