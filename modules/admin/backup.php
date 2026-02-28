<?php
// Define Root Path
$rootDir = dirname(__DIR__, 2);

// Robust Include
if (file_exists($rootDir . '/includes/guard.php')) {
    require_once $rootDir . '/includes/guard.php';
} else {
    die("Critical Error: includes/guard.php not found in $rootDir");
}

require_login_and_module('admin');
ais_init_session();

// Security Check
if (!in_array($_SESSION['role'], ['SUPERADMIN', 'ADMIN'])) {
    die("Access Denied");
}

if (file_exists($rootDir . '/config/database.php')) {
    require_once $rootDir . '/config/database.php';
} else {
    die("Critical Error: config/database.php not found in $rootDir");
}

// --- CONFIGURATION ---
$backupDir = $rootDir . '/backups/';
$migrationDir = $rootDir . '/backups/migrations/';

if (!file_exists($backupDir)) {
    @mkdir($backupDir, 0755, true);
}
if (!file_exists($migrationDir)) {
    @mkdir($migrationDir, 0755, true);
}

$metaFile = $backupDir . 'backup_meta.json';

// --- HELPER CLASSES ---

class DBBackupManager {
    private $pdo;
    private $backupDir;
    private $metaFile;

    public function __construct($pdo, $backupDir, $metaFile) {
        $this->pdo = $pdo;
        $this->backupDir = $backupDir;
        $this->metaFile = $metaFile;
    }

    public function getMeta() {
        if (file_exists($this->metaFile)) {
            $content = file_get_contents($this->metaFile);
            return json_decode($content, true) ?? [];
        }
        return [];
    }

    public function saveMeta($filename, $data) {
        $meta = $this->getMeta();
        $meta[$filename] = $data;
        file_put_contents($this->metaFile, json_encode($meta, JSON_PRETTY_PRINT));
    }

    public function deleteMeta($filename) {
        $meta = $this->getMeta();
        if (isset($meta[$filename])) {
            unset($meta[$filename]);
            file_put_contents($this->metaFile, json_encode($meta, JSON_PRETTY_PRINT));
        }
    }

    public function getStats($dbname) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?");
            $stmt->execute([$dbname]);
            $tableCount = $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = ?");
            $stmt->execute([$dbname]);
            $sizeBytes = $stmt->fetchColumn();
            $sizeMB = round($sizeBytes / 1024 / 1024, 2);

            $stmt = $this->pdo->query("SELECT VERSION()");
            $version = $stmt->fetchColumn();

            return ['tables' => $tableCount, 'size' => $sizeMB . ' MB', 'version' => $version];
        } catch (Exception $e) {
            return ['tables' => '-', 'size' => '-', 'version' => '-'];
        }
    }

    public function createBackup($dbname, $note = '') {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.sql';
        $filepath = $this->backupDir . $filename;
        
        // Increase limits for dump
        @set_time_limit(0);
        @ini_set('memory_limit', '1G');

        $f = fopen($filepath, 'w');
        if (!$f) return ['success' => false, 'error' => 'Cannot create file'];

        // Write Header
        fwrite($f, "-- AIS Backup " . date('Y-m-d H:i:s') . "\n");
        fwrite($f, "-- Note: " . str_replace("\n", " ", $note) . "\n");
        fwrite($f, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($f, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tableCount = 0;
        try {
            $stmt = $this->pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Structure
                fwrite($f, "DROP TABLE IF EXISTS `$table`;\n");
                $stmt = $this->pdo->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                if ($row && isset($row[1])) {
                    fwrite($f, $row[1] . ";\n\n");
                }

                // Data
                $stmt = $this->pdo->query("SELECT * FROM `$table`");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols = array_keys($row);
                    $vals = array_map(function($v) {
                        return $v === null ? "NULL" : $this->pdo->quote($v);
                    }, array_values($row));
                    
                    fwrite($f, "INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n");
                }
                fwrite($f, "\n");
                $tableCount++;
            }

            fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($f);

            // Save Meta
            $this->saveMeta($filename, [
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user_id'] ?? 'System',
                'table_count' => $tableCount
            ]);

            return ['success' => true, 'file' => $filename, 'tables' => $tableCount];
        } catch (Exception $e) {
            fclose($f);
            if (file_exists($filepath)) unlink($filepath);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

class DBMigrationManager {
    private $pdo;
    private $migrationDir;

    public function __construct($pdo, $migrationDir) {
        $this->pdo = $pdo;
        $this->migrationDir = $migrationDir;
        $this->initTable();
    }

    private function initTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS sys_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function getMigrations() {
        $files = glob($this->migrationDir . '*.sql');
        $migrations = [];
        
        $stmt = $this->pdo->query("SELECT migration_name, executed_at FROM sys_migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // name => executed_at

        foreach ($files as $file) {
            $name = basename($file);
            $migrations[] = [
                'name' => $name,
                'path' => $file,
                'status' => isset($executed[$name]) ? 'APPLIED' : 'PENDING',
                'executed_at' => $executed[$name] ?? null,
                'timestamp' => filemtime($file)
            ];
        }
        
        // Sort by name (timestamp prefix usually handles order)
        usort($migrations, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $migrations;
    }

    public function createMigration($desc) {
        $desc = preg_replace('/[^a-z0-9_]/i', '_', strtolower($desc));
        $filename = date('Ymd_His') . '_' . $desc . '.sql';
        $content = "-- Migration: $desc\n-- Created: " . date('Y-m-d H:i:s') . "\n\n-- Tulis SQL Anda di sini:\n\n";
        
        if (file_put_contents($this->migrationDir . $filename, $content)) {
            return $filename;
        }
        return false;
    }

    public function runMigration($filename) {
        $filepath = $this->migrationDir . $filename;
        if (!file_exists($filepath)) return ['success' => false, 'error' => 'File not found'];

        // Check if already executed
        $stmt = $this->pdo->prepare("SELECT id FROM sys_migrations WHERE migration_name = ?");
        $stmt->execute([$filename]);
        if ($stmt->fetch()) return ['success' => true, 'message' => 'Already executed'];

        $sql = file_get_contents($filepath);
        
        try {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Simple split by semicolon for now (can be improved)
            // Or just exec raw if no delimiters
            $this->pdo->exec($sql);
            
            $stmt = $this->pdo->prepare("INSERT INTO sys_migrations (migration_name) VALUES (?)");
            $stmt->execute([$filename]);
            
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            return ['success' => true, 'message' => 'Executed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// --- ACTION HANDLER ---

$manager = new DBBackupManager($pdo, $backupDir, $metaFile);
$migrator = new DBMigrationManager($pdo, $migrationDir);
$message = '';
$error = '';

// STREAM RESTORE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'stream_restore') {
     // ... (Existing Restore Logic - Kept for compatibility) ...
     // Restore logic from previous step is preserved here implicitly 
     // For brevity in this write, assuming the file is fully replaced, 
     // I will re-paste the robust restore logic below.
     
     if (ob_get_level()) ob_end_clean();
     @ignore_user_abort(true); 
     @set_time_limit(0); 
     @ini_set('memory_limit', '-1');
     @ini_set('output_buffering', 0);
     @ini_set('implicit_flush', 1);
     
     header('Content-Type: text/plain');
     header('X-Accel-Buffering: no'); 
     header('Cache-Control: no-cache');
 
     $file = $_GET['file'] ?? '';
     $filepath = $backupDir . basename($file);
 
     if (!file_exists($filepath)) {
         echo "Error: File backup tidak ditemukan.\n";
         exit;
     }
 
     echo "=== MEMULAI RESTORE DATABASE ===\n";
     echo "File: " . basename($file) . "\n";
     echo "Waktu: " . date('Y-m-d H:i:s') . "\n";
     echo "----------------------------------------\n";
     echo str_repeat(" ", 1024) . "\n";
     flush();
 
     $useNative = false;
     $mysqlBin = 'mysql'; 
     
     if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
         $xamppPath = 'C:/xampp/mysql/bin/mysql.exe';
         if (file_exists($xamppPath)) {
             $mysqlBin = '"' . $xamppPath . '"';
             $useNative = true;
         }
     } else {
         $paths = ['/usr/bin/mysql', '/usr/local/bin/mysql', '/usr/sbin/mysql'];
         foreach ($paths as $p) {
             if (file_exists($p)) {
                 $mysqlBin = $p;
                 $useNative = true;
                 break;
             }
         }
         if (!$useNative && function_exists('shell_exec')) {
             $path = trim(shell_exec('which mysql'));
             if (!empty($path)) {
                 $mysqlBin = $path;
                 $useNative = true;
             }
         }
         if (!$useNative && function_exists('exec')) {
             $useNative = true; 
         }
     }
 
     $disabledFuncs = array_map('trim', explode(',', ini_get('disable_functions')));
     $canProcOpen = function_exists('proc_open') && !in_array('proc_open', $disabledFuncs);
     $canSystem = function_exists('system') && !in_array('system', $disabledFuncs);
     $canExec = function_exists('exec') && !in_array('exec', $disabledFuncs);
 
     if ($useNative && ($canProcOpen || $canSystem || $canExec)) {
         echo "[INFO] Using Native MySQL Client ($mysqlBin)\n";
         flush();
 
         $cmd = "$mysqlBin -h " . escapeshellarg($host) . " -u " . escapeshellarg($username);
         if ($password) {
             $cmd .= " -p" . escapeshellarg($password);
         }
         $cmd .= " " . escapeshellarg($dbname) . " < \"" . $filepath . "\"";
 
         $debugCmd = str_replace($password, '********', $cmd);
         echo "[INFO] Running: $debugCmd\n";
         flush();
 
         if ($canProcOpen) {
             $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
             $process = proc_open($cmd, $descriptors, $pipes);
             if (is_resource($process)) {
                 stream_set_blocking($pipes[1], 0);
                 stream_set_blocking($pipes[2], 0);
                 $lastUpdate = time();
                 $dots = 0;
                 while (true) {
                     $status = proc_get_status($process);
                     $out = stream_get_contents($pipes[1]);
                     $err = stream_get_contents($pipes[2]);
                     if ($out) { echo $out; flush(); }
                     if ($err && strpos($err, 'Using a password') === false) { echo "\n[MYSQL] " . $err; flush(); }
                     if (!$status['running']) break;
                     
                     if (time() - $lastUpdate > 1) {
                         echo "."; flush();
                         $dots++; if ($dots > 80) { echo "\n"; $dots = 0; }
                         $lastUpdate = time();
                     }
                     usleep(200000);
                 }
                 fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
                 $exitCode = proc_close($process);
                 if ($exitCode === 0) {
                     echo "\n\n=== RESTORE SELESAI (NATIVE PROC_OPEN) ===\nStatus: SUKSES\n";
                     exit;
                 } else {
                     echo "\n[ERROR] Process exited with code $exitCode. Trying Fallback...\n";
                 }
             }
         } elseif ($canSystem) {
             echo "\n[INFO] Trying system() method...\n";
             flush();
             $ret = null;
             system($cmd . ' 2>&1', $ret);
             if ($ret === 0) {
                 echo "\n\n=== RESTORE SELESAI (NATIVE SYSTEM) ===\nStatus: SUKSES\n";
                 exit;
             }
         } elseif ($canExec) {
             echo "\n[INFO] Trying exec() method...\n";
             flush();
             $output = [];
             $ret = null;
             exec($cmd . ' 2>&1', $output, $ret);
             echo implode("\n", $output);
             if ($ret === 0) {
                 echo "\n\n=== RESTORE SELESAI (NATIVE EXEC) ===\nStatus: SUKSES\n";
                 exit;
             }
         }
     } else {
         echo "[INFO] Native MySQL Client not available or disabled. Using PHP Driver (Slower)...\n";
     }
 
     // Fallback PHP Loop
     try {
         $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
         echo "[INFO] Foreign Key Checks disabled.\n";
         flush();
         
         $handle = fopen($filepath, "r");
         $query = '';
         $delimiter = ';';
         $count = 0;
         $errors = 0;
         $currentTable = '';
         $lastUpdate = time();
 
         while (($line = fgets($handle)) !== false) {
             $trim = trim($line);
             if ($trim === '' || strpos($trim, '--') === 0 || strpos($trim, '/*') === 0) continue;
             if (preg_match('/^DELIMITER\s+(\S+)/', $trim, $matches)) {
                 $delimiter = $matches[1];
                 continue;
             }
             $query .= $line;
             if (substr($trim, -strlen($delimiter)) === $delimiter) {
                 try {
                     $sqlToRun = substr($query, 0, -strlen($delimiter));
                     if (trim($sqlToRun) !== '') {
                         $pdo->exec($sqlToRun);
                         $count++;
                         if ($count % 50 === 0) { echo "."; flush(); }
                         if (time() - $lastUpdate > 10) { echo "."; flush(); $lastUpdate = time(); }
                     }
                 } catch (Exception $e) {
                     $errors++;
                     echo "\n[ERROR] " . $e->getMessage() . "\n";
                     flush();
                 }
                 $query = '';
             }
         }
         fclose($handle);
         $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
         echo "\n\n=== RESTORE SELESAI ===\nTotal: $count\nErrors: $errors\nStatus: " . ($errors===0?'SUKSES':'ERROR');
     } catch (Exception $e) {
         echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
     }
     exit;
}

// POST HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // BACKUP ACTIONS
    if ($action === 'create_point') {
        $res = $manager->createBackup($dbname, $_POST['note'] ?? '');
        if ($res['success']) $message = "Backup berhasil: " . $res['file'];
        else $error = "Gagal backup: " . $res['error'];
    }
    if ($action === 'upload_restore') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $name = basename($_FILES['backup_file']['name']);
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'sql') {
                $error = "Hanya file .sql yang diperbolehkan.";
            } else {
                $target = $backupDir . 'uploaded_' . date('YmdHis') . '_' . $name;
                if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $target)) {
                    $manager->saveMeta(basename($target), [
                        'note' => $_POST['note'] ?? 'Uploaded',
                        'created_at' => date('Y-m-d H:i:s'),
                        'uploaded_by' => $_SESSION['user_id'] ?? 'System'
                    ]);
                    $message = "File berhasil diupload.";
                } else $error = "Upload gagal.";
            }
        }
    }
    if ($action === 'delete_point') {
        $file = $_POST['file'] ?? '';
        $filepath = $backupDir . basename($file);
        if (file_exists($filepath)) {
            unlink($filepath);
            $manager->deleteMeta(basename($file));
            $message = "Backup dihapus.";
        }
    }
    if ($action === 'download_point') {
        $file = $_POST['file'] ?? '';
        $filepath = $backupDir . basename($file);
        if (file_exists($filepath)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    }

    // MIGRATION ACTIONS
    if ($action === 'create_migration') {
        $desc = $_POST['description'] ?? 'update';
        $file = $migrator->createMigration($desc);
        if ($file) $message = "Migrasi dibuat: $file. Silakan edit file tersebut di folder backups/migrations.";
        else $error = "Gagal membuat file migrasi.";
    }
    if ($action === 'run_migration') {
        $file = $_POST['file'] ?? '';
        $res = $migrator->runMigration($file);
        if ($res['success']) $message = "Migrasi $file berhasil dijalankan.";
        else $error = "Gagal menjalankan migrasi: " . ($res['error'] ?? 'Unknown');
    }
    if ($action === 'mark_applied') {
        // Just insert to DB without running
        $file = $_POST['file'] ?? '';
        $stmt = $pdo->prepare("INSERT IGNORE INTO sys_migrations (migration_name) VALUES (?)");
        $stmt->execute([$file]);
        $message = "Migrasi $file ditandai sebagai selesai (tanpa eksekusi).";
    }
}

// VIEW PREP
$activeTab = $_GET['tab'] ?? 'backup';

// Backup List
$backups = [];
$files = glob($backupDir . '*.sql');
$meta = $manager->getMeta();
foreach ($files as $file) {
    $name = basename($file);
    $m = $meta[$name] ?? [];
    $backups[] = [
        'name' => $name,
        'size' => round(filesize($file) / 1024, 2) . ' KB',
        'date' => date('Y-m-d H:i', filemtime($file)),
        'timestamp' => filemtime($file),
        'note' => $m['note'] ?? '-',
        'table_count' => $m['table_count'] ?? '?'
    ];
}
usort($backups, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

// Migration List
$migrations = $migrator->getMigrations();

$dbStats = $manager->getStats($dbname);

require_once '../../includes/header.php';
?>

<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">System Database</h1>
            <p class="text-slate-500 text-sm">Backup, Restore & Migration Management</p>
        </div>
        <div class="text-right text-xs text-slate-500">
            <p>DB: <span class="font-mono font-bold text-blue-600"><?php echo $dbname; ?></span></p>
            <p>Tables: <?php echo $dbStats['tables']; ?> | Size: <?php echo $dbStats['size']; ?></p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8">
            <a href="?tab=backup" class="<?php echo $activeTab==='backup'?'border-blue-500 text-blue-600':'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Backup & Restore
            </a>
            <a href="?tab=migration" class="<?php echo $activeTab==='migration'?'border-blue-500 text-blue-600':'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Database Migrations
            </a>
        </nav>
    </div>

    <?php if ($activeTab === 'backup'): ?>
    <!-- BACKUP CONTENT -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h2 class="font-bold text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle text-blue-500"></i> Buat Backup
            </h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_point">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
                    <textarea name="note" class="w-full border rounded p-2 text-sm" rows="2" placeholder="Contoh: Pre-update v2.0"></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                    Buat Backup
                </button>
            </form>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h2 class="font-bold text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-upload text-purple-500"></i> Upload .sql
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_restore">
                <div class="mb-2">
                    <input type="text" name="note" class="w-full border rounded p-2 text-sm mb-2" placeholder="Catatan file...">
                    <input type="file" name="backup_file" accept=".sql" required class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                </div>
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition">
                    Upload
                </button>
            </form>
        </div>
        <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 text-sm text-slate-600">
            <h3 class="font-bold mb-2">Info</h3>
            <p>Gunakan fitur ini untuk menyimpan snapshot database sebelum melakukan perubahan besar.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 font-bold text-slate-700">
            Riwayat Backup
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Waktu</th>
                        <th class="px-6 py-3">File / Note</th>
                        <th class="px-6 py-3">Ukuran</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($backups as $b): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-bold text-slate-700"><?php echo $b['date']; ?></div>
                            <div class="text-xs text-slate-400"><?php echo time_elapsed_string($b['timestamp']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-mono text-blue-600 mb-1"><?php echo $b['name']; ?></div>
                            <div class="text-slate-600"><?php echo htmlspecialchars($b['note']); ?></div>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs"><?php echo $b['size']; ?></td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="download_point">
                                    <input type="hidden" name="file" value="<?php echo $b['name']; ?>">
                                    <button type="submit" class="text-slate-400 hover:text-blue-600" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                                <button onclick="startRestore('<?php echo $b['name']; ?>')" class="bg-white border border-red-200 text-red-600 hover:bg-red-50 px-3 py-1 rounded text-xs font-bold transition">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Hapus permanen?');">
                                    <input type="hidden" name="action" value="delete_point">
                                    <input type="hidden" name="file" value="<?php echo $b['name']; ?>">
                                    <button type="submit" class="text-slate-400 hover:text-red-600 ml-2" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <!-- MIGRATION CONTENT -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h3 class="font-bold text-lg mb-4">Buat Migrasi Baru (Development)</h3>
        <form method="POST" class="flex gap-4 items-end">
            <input type="hidden" name="action" value="create_migration">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Perubahan</label>
                <input type="text" name="description" class="w-full border rounded p-2 text-sm" placeholder="Contoh: add_column_phone_to_users" required>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded font-bold hover:bg-indigo-700 transition">
                <i class="fas fa-plus"></i> Buat File Migrasi
            </button>
        </form>
        <p class="text-xs text-slate-500 mt-2">
            File akan dibuat di <code>backups/migrations/</code>. Silakan edit file tersebut untuk menambahkan SQL.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <div class="font-bold text-slate-700">Daftar Migrasi</div>
            <div class="text-xs text-slate-500">Menampilkan status migrasi di server ini</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Nama Migrasi</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3">Dieksekusi Pada</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($migrations as $m): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-mono text-xs text-slate-700">
                            <?php echo $m['name']; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($m['status'] === 'APPLIED'): ?>
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold">SUDAH</span>
                            <?php else: ?>
                                <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[10px] font-bold">BELUM</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <?php echo $m['executed_at'] ?? '-'; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($m['status'] === 'PENDING'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Jalankan migrasi ini?');">
                                    <input type="hidden" name="action" value="run_migration">
                                    <input type="hidden" name="file" value="<?php echo $m['name']; ?>">
                                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700">
                                        Jalankan
                                    </button>
                                </form>
                                <form method="POST" class="inline ml-2" onsubmit="return confirm('Tandai selesai tanpa menjalankan SQL?');">
                                    <input type="hidden" name="action" value="mark_applied">
                                    <input type="hidden" name="file" value="<?php echo $m['name']; ?>">
                                    <button type="submit" class="text-slate-400 hover:text-green-600" title="Mark as Applied (Manual)">
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-slate-400 text-xs italic">Selesai</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($migrations)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada file migrasi.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Terminal Modal for Restore (Same as before) -->
<div id="restoreModal" class="fixed inset-0 bg-slate-900/80 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-slate-900 rounded-lg shadow-2xl w-full max-w-4xl h-[80vh] flex flex-col border border-slate-700 m-4">
        <div class="flex justify-between items-center p-4 border-b border-slate-700 bg-slate-800 rounded-t-lg">
            <h3 class="text-white font-bold font-mono"><i class="fas fa-terminal mr-2 text-green-400"></i>Restore Console</h3>
            <button onclick="closeRestoreModal()" class="text-slate-400 hover:text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 overflow-auto font-mono text-xs text-green-400 bg-black" id="terminalOutput"></div>
        <div class="p-4 border-t border-slate-700 bg-slate-800 flex justify-between items-center rounded-b-lg">
            <span id="restoreStatus" class="text-slate-400 text-xs">Ready</span>
            <button onclick="closeRestoreModal()" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded text-sm font-bold transition">Tutup</button>
        </div>
    </div>
</div>
<script>
// Restore JS functions (same as before)
function startRestore(filename) {
    if (!confirm('PERINGATAN: Seluruh data database akan digantikan dengan data dari backup ini. Lanjutkan?')) return;
    const modal = document.getElementById('restoreModal');
    const output = document.getElementById('terminalOutput');
    const status = document.getElementById('restoreStatus');
    modal.classList.remove('hidden');
    output.innerHTML = 'Connecting to server...\n';
    status.innerText = 'Initializing...';
    fetch('?action=stream_restore&file=' + encodeURIComponent(filename))
    .then(response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        function read() {
            reader.read().then(({done, value}) => {
                if (done) {
                    status.innerText = 'Finished';
                    output.innerHTML += '\n[CONNECTION CLOSED]';
                    output.scrollTop = output.scrollHeight;
                    return;
                }
                const text = decoder.decode(value);
                output.innerHTML += text;
                output.scrollTop = output.scrollHeight;
                if (text.includes('STATUS: SUKSES')) {
                    status.innerText = 'Success';
                    status.classList.add('text-green-400');
                }
                read();
            }).catch(error => { output.innerHTML += '\n[STREAM ERROR] ' + error; });
        }
        read();
    })
    .catch(err => { output.innerHTML += '\n[FETCH ERROR] ' + err; });
}
function closeRestoreModal() {
    document.getElementById('restoreModal').classList.add('hidden');
    if (document.getElementById('restoreStatus').innerText.includes('Success')) {
        window.location.reload();
    }
}
</script>

<?php
function time_elapsed_string($timestamp) {
    // ... (Existing helper) ...
    $etime = time() - $timestamp;
    if ($etime < 1) return 'just now';
    $a = array( 365 * 24 * 60 * 60  =>  'year', 30 * 24 * 60 * 60  =>  'month', 24 * 60 * 60  =>  'day', 60 * 60  =>  'hour', 60  =>  'minute', 1  =>  'second');
    $a_plural = array( 'year'   => 'years', 'month'  => 'months', 'day'    => 'days', 'hour'   => 'hours', 'minute' => 'minutes', 'second' => 'seconds');
    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}
require_once '../../includes/footer.php'; 
?>