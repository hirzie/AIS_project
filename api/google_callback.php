<?php
require_once __DIR__ . '/../config/database.php';

// Helper functions (same as in manage_agenda.php)
function saveSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $stmt = $pdo->prepare("UPDATE core_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}

try {
    // Load Config
    $googleConfigPath = '../config/google_calendar.php';
    if (!file_exists($googleConfigPath)) {
        die('Konfigurasi Google Calendar hilang.');
    }
    $googleConfig = require $googleConfigPath;

    $code = $_GET['code'] ?? '';
    $error = $_GET['error'] ?? '';

    if ($error) {
        die('Google Error: ' . htmlspecialchars($error));
    }

    if (!$code) {
        die('Tidak ada kode otorisasi yang diterima.');
    }

    // Exchange code for token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => $googleConfig['client_id'],
        'client_secret' => $googleConfig['client_secret'],
        'redirect_uri' => $googleConfig['redirect_uri'],
        'grant_type' => 'authorization_code'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    
    if (isset($tokenData['access_token'])) {
        saveSetting($pdo, 'google_calendar_token', $tokenData['access_token']);
        
        // Refresh token is only returned on the first authorization (consent screen)
        // or if 'access_type' is 'offline' and 'prompt' is 'consent' (which we use)
        if (isset($tokenData['refresh_token'])) {
            saveSetting($pdo, 'google_calendar_refresh_token', $tokenData['refresh_token']);
        }
        
        // Redirect back to agenda page with success status
        header('Location: ../modules/academic/school_agenda.php?status=synced');
        exit;
    } else {
        // Log error for debugging
        error_log('Google Token Error: ' . $response);
        die('Gagal mendapatkan akses token. Detail: ' . ($tokenData['error_description'] ?? $response));
    }

} catch (Exception $e) {
    die('Error System: ' . $e->getMessage());
}
?>
