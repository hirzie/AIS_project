<?php
// config/google_calendar.php
// Aman: kredensial tidak disimpan di repo. Ambil dari core_settings atau ENV.

// Deteksi Redirect URI secara dinamis agar bisa jalan di localhost:8000 maupun XAMPP biasa
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Cek apakah folder AIS ada di URL (untuk XAMPP htdocs/AIS)
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
$m = [];
$folder = '';
if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) { $folder = '/' . $m[1]; }
elseif (preg_match('#^/(AIS|AIStest)/#i', $reqUri, $m)) { $folder = '/' . $m[1]; }
$baseUrl = $protocol . "://" . $host . $folder;
$redirectUri = $baseUrl . "/api/google_callback.php";

// Ambil kredensial secara aman
$clientId = getenv('GOOGLE_CALENDAR_CLIENT_ID') ?: '';
$clientSecret = getenv('GOOGLE_CALENDAR_CLIENT_SECRET') ?: '';

// Coba ambil dari core_settings jika tersedia
try {
    require_once __DIR__ . '/database.php';
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM core_settings WHERE setting_key IN ('google_calendar_client_id','google_calendar_client_secret')");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['setting_key'] === 'google_calendar_client_id' && !empty($row['setting_value'])) {
                $clientId = $row['setting_value'];
            } elseif ($row['setting_key'] === 'google_calendar_client_secret' && !empty($row['setting_value'])) {
                $clientSecret = $row['setting_value'];
            }
        }
    }
} catch (\Throwable $e) { /* ignore */ }

return [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'scopes' => 'https://www.googleapis.com/auth/calendar'
];
