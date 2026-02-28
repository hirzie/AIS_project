<?php
// check_migration_status.php
// Verifikasi status migrasi database untuk fitur AI Display
header('Content-Type: text/plain');
require_once 'config/database.php';

echo "=== STATUS CEK MIGRASI DATABASE ===\n";
echo "Waktu: " . date('Y-m-d H:i:s') . "\n\n";

$status = [
    'table_exists' => false,
    'column_metadata' => false,
    'enum_ai_override' => false,
    'setting_gemini' => false
];

// 1. Cek Tabel app_display_messages
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_display_messages'");
    if ($stmt->rowCount() > 0) {
        $status['table_exists'] = true;
        echo "[OK] Tabel 'app_display_messages' ditemukan.\n";
        
        // Cek Struktur Kolom
        $stmtCol = $pdo->query("SHOW COLUMNS FROM app_display_messages");
        $columns = $stmtCol->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'metadata') {
                $status['column_metadata'] = true;
                echo "[OK] Kolom 'metadata' ditemukan.\n";
            }
            if ($col['Field'] === 'type') {
                if (strpos($col['Type'], "'AI_OVERRIDE'") !== false) {
                    $status['enum_ai_override'] = true;
                    echo "[OK] ENUM type mencakup 'AI_OVERRIDE'.\n";
                } else {
                    echo "[FAIL] ENUM type BELUM mencakup 'AI_OVERRIDE'. (Current: {$col['Type']})\n";
                }
            }
        }
    } else {
        echo "[FAIL] Tabel 'app_display_messages' TIDAK DITEMUKAN.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Gagal mengecek tabel: " . $e->getMessage() . "\n";
}

// 2. Cek Setting Google Gemini
try {
    $stmtSet = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
    $stmtSet->execute(['google_gemini_api_key']);
    if ($row = $stmtSet->fetch()) {
        $status['setting_gemini'] = true;
        echo "[OK] Setting 'google_gemini_api_key' ditemukan.\n";
    } else {
        echo "[WARN] Setting 'google_gemini_api_key' belum ada di core_settings.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Gagal mengecek settings: " . $e->getMessage() . "\n";
}

echo "\n=== KESIMPULAN ===\n";
if ($status['table_exists'] && $status['column_metadata'] && $status['enum_ai_override']) {
    echo "SIAP DIGUNAKAN. Database sudah sesuai untuk fitur Display AI.\n";
} else {
    echo "BELUM SIAP. Silakan jalankan script migrasi ulang (migrate_ai_display.php).\n";
}
?>