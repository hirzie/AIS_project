<?php
// tools/build_release.php
// Script untuk membuat paket rilis SekolahOS (Zip + Database)
// Run: php tools/build_release.php

echo "\n🚀  MEMULAI PROSES BUILD SEKOLAH OS (WINDOWS MODE)\n";
echo "=====================================\n";

// 1. Konfigurasi
$rootDir = realpath(__DIR__ . '/../');
$versionConfig = require $rootDir . '/config/version.php';
$version = $versionConfig['version'];
$date = date('Ymd_Hi');
$zipFilename = "SekolahOS_v{$version}_{$date}.zip";
$zipPath = $rootDir . '\\' . $zipFilename;
$sqlFilename = "database_aiscore_{$version}_{$date}.sql";
$sqlPath = $rootDir . '\\' . $sqlFilename;
$tempDir = $rootDir . '\\temp_build_' . $date;

// 2. Database Dump
echo "\n[1/5] 💾  Backup Database...\n";
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'aiscore'; 

$cmdDump = "mysqldump --user={$username} --password=\"{$password}\" --host={$host} {$dbname} > \"{$sqlPath}\"";
system($cmdDump, $retval);

if ($retval !== 0) {
    echo "⚠️  Gagal melakukan dump database.\n";
    $hasDb = false;
} else {
    echo "    ✅ Database tersimpan: $sqlFilename\n";
    $hasDb = true;
}

// 3. Prepare Temp Directory (Robocopy)
echo "\n[2/5] 📂  Menyiapkan File (Robocopy)...\n";
if (!is_dir($tempDir)) mkdir($tempDir);

// Build Exclusion Lists
$excludeDirs = ['.git', '.vscode', '.idea', 'node_modules', 'vendor', 'tools', 'uploads', 'temp_*'];
$excludeFiles = ['composer.lock', 'package-lock.json', '.DS_Store', 'Thumbs.db', 'database.php', 'google_calendar.php', '*.zip', '*.sql', 'debug_*'];

$xd = implode(' ', $excludeDirs);
$xf = implode(' ', $excludeFiles);

// Command Robocopy
// /E = Copy Subdirs including Empty
// /XD = Exclude Dirs
// /XF = Exclude Files
// /NFL /NDL = No File/Dir Logging (Quiet)
$cmdCopy = "robocopy \"{$rootDir}\" \"{$tempDir}\" /E /XD {$xd} /XF {$xf} /NFL /NDL /NJH /NJS";

// Robocopy Exit Codes: 0-7 are success/partial success.
exec($cmdCopy, $output, $return_var);

echo "    ✅ File disalin ke temporary folder.\n";

// 4. Add Special Files
echo "\n[3/5] ➕  Menambahkan File Konfigurasi & Database...\n";

// Rename database.php -> database.example.php
if (file_exists($rootDir . '/config/database.php')) {
    copy($rootDir . '/config/database.php', $tempDir . '/config/database.example.php');
}

// Move SQL to database_setup
if ($hasDb && file_exists($sqlPath)) {
    if (!is_dir($tempDir . '/database_setup')) mkdir($tempDir . '/database_setup');
    rename($sqlPath, $tempDir . '/database_setup/' . $sqlFilename);
}

// 5. Compress using PowerShell
echo "\n[4/5] 📦  Mengompres ZIP (PowerShell)...\n";
// Note: Compress-Archive requires absolute paths usually or correct relative context.
// We will zip the CONTENTS of tempDir
$psCommand = "powershell -Command \"Compress-Archive -Path '{$tempDir}\\*' -DestinationPath '{$zipPath}' -Force\"";
system($psCommand, $retvalZip);

if ($retvalZip === 0) {
    echo "    ✅ ZIP Berhasil dibuat: $zipFilename\n";
} else {
    echo "    ❌ Gagal membuat ZIP. Kode Error: $retvalZip\n";
}

// 6. Cleanup
echo "\n[5/5] 🧹  Membersihkan Temporary Files...\n";
// Recursive Delete Temp Dir
$cmdDel = "rmdir /s /q \"{$tempDir}\"";
system($cmdDel);

if (file_exists($sqlPath)) unlink($sqlPath); // Just in case it wasn't moved

echo "\n=====================================\n";
echo "🎉  BUILD SELESAI!\n";
echo "📂  Lokasi File: $zipPath\n";
echo "=====================================\n";
echo "\nINSTRUKSI HOSTING (PERTAMA KALI):\n";
echo "1. Upload '$zipFilename' ke File Manager hosting (public_html).\n";
echo "2. Extract file tersebut.\n";
echo "3. Buat Database baru di Hosting (MySQL Database Wizard).\n";
echo "4. Import file SQL yang ada di folder 'database_setup' ke database baru (via phpMyAdmin).\n";
echo "5. Rename 'config/database.example.php' menjadi 'config/database.php'.\n";
echo "6. Edit 'config/database.php' dan sesuaikan user/pass database hosting.\n";
echo "   (Pastikan bagian 'ELSE' di file config terisi dengan kredensial hosting).\n";
echo "\nINSTRUKSI UPDATE MINGGUAN:\n";
echo "1. Upload ZIP, Extract dan pilih 'Overwrite'.\n";
echo "2. Config database Anda AMAN (karena yang di-upload adalah .example.php).\n";
echo "=====================================\n";
?>