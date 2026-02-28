<?php
require_once __DIR__ . '/includes/guard.php';
ais_init_session();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, people_id FROM core_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $passwordOk = false;
        if ($user && isset($user['password_hash']) && $user['password_hash']) {
            $passwordOk = password_verify($password, $user['password_hash']);
        }
        if (!$passwordOk) {
            try {
                $col = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'password'");
                if ($col && $col->fetch()) {
                    $stmt2 = $pdo->prepare("SELECT id, username, password, role, people_id FROM core_users WHERE username = ?");
                    $stmt2->execute([$username]);
                    $u2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($u2) {
                        if ($u2['password'] === $password || md5($password) === $u2['password']) {
                            $user = [
                                'id' => $u2['id'],
                                'username' => $u2['username'],
                                'role' => $u2['role'],
                                'people_id' => $u2['people_id'] ?? null
                            ];
                            $passwordOk = true;
                        }
                    }
                }
            } catch (Exception $e) { /* ignore */ }
        }

        if ($user && $passwordOk) {
            // Login Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Allowed Modules (Multi-Module Access)
            $role = strtoupper(trim($user['role'] ?? ''));
            if ($role === 'MANAGER') { $role = 'MANAGERIAL'; }
            elseif ($role === 'SUPERUSER' || $role === 'ADMINISTRATOR') { $role = 'SUPERADMIN'; }
            elseif ($role === 'ADMIN UNIT') { $role = 'ADMIN'; }
            elseif ($role === 'AKADEMIK') { $role = 'ACADEMIC'; }
            elseif ($role === 'YAYASAN') { $role = 'FOUNDATION'; }
            elseif ($role === 'KEUANGAN') { $role = 'FINANCE'; }
            elseif ($role === 'PERPUSTAKAAN') { $role = 'LIBRARY'; }
            elseif ($role === 'ASRAMA') { $role = 'BOARDING'; }
            elseif ($role === 'KANTIN') { $role = 'POS'; }
            $allowed = [
                'core' => true
            ];
            if (in_array($role, ['SUPERADMIN','ADMIN'])) {
                $modsCfg = require __DIR__ . '/config/modules.php';
                $keys = array_map(function($m){ return strtolower($m['code'] ?? ''); }, $modsCfg);
                $extras = ['people','kiosk','payroll','counseling'];
                foreach ($keys as $k) { if ($k) { $allowed[$k] = true; } }
                foreach ($extras as $ex) { $allowed[$ex] = true; }
                $allowed['core'] = true;
            } elseif ($role === 'ACADEMIC') {
                $allowed['academic'] = true;
            } elseif ($role === 'FOUNDATION') {
                $allowed['foundation'] = true;
            } elseif ($role === 'MANAGERIAL') {
                $allowed['executive'] = true;
            } elseif ($role === 'PRINCIPAL') {
                $allowed['workspace'] = true;
            } elseif ($role === 'TEACHER' || $role === 'GURU') {
                $allowed['workspace'] = true;
            } elseif ($role === 'FINANCE') {
                $allowed['finance'] = true;
            } elseif ($role === 'POS') {
                $allowed['pos'] = true;
            } elseif ($role === 'SECURITY' || $role === 'KEAMANAN') {
                $allowed['security'] = true;
            } elseif ($role === 'CLEANING' || $role === 'KEBERSIHAN') {
                $allowed['cleaning'] = true;
            }
            // Optional per-user overrides if column exists
            try {
                $colStmt = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'access_modules'");
                if ($colStmt && $colStmt->fetch()) {
                    $modsStmt = $pdo->prepare("SELECT access_modules FROM core_users WHERE id = ?");
                    $modsStmt->execute([$user['id']]);
                    $modsJson = $modsStmt->fetchColumn();
                    $mods = null;
                    if ($modsJson) {
                        $decoded = json_decode($modsJson, true);
                        if (is_array($decoded) && count($decoded) > 0) {
                            $mods = $decoded;
                        }
                    }
                    // Apply per-user override ONLY for non-admin roles and only if list is non-empty
                    if (!in_array($role, ['SUPERADMIN','ADMIN']) && is_array($mods) && count($mods) > 0) {
                        $modsCfg = require __DIR__ . '/config/modules.php';
                        $keys = array_map(function($m){ return strtolower($m['code'] ?? ''); }, $modsCfg);
                        $keys = array_values(array_filter($keys));
                        $keys = array_merge(['core'], $keys, ['people','kiosk','payroll','counseling']);
                        $allowed = [];
                        foreach ($keys as $k) { $allowed[$k] = ($k === 'core'); }
                        $list = [];
                        $isAssoc = array_keys($mods) !== range(0, count($mods) - 1);
                        if ($isAssoc) {
                            foreach ($mods as $mk => $val) {
                                if ($val) {
                                    $k = strtolower(trim((string)$mk));
                                    if ($k) { $list[] = $k; }
                                }
                            }
                        } else {
                            foreach ($mods as $m) {
                                $k = strtolower(trim(is_string($m) ? $m : (string)$m));
                                if ($k) { $list[] = $k; }
                            }
                        }
                        foreach ($list as $k) { $allowed[$k] = true; }
                    }
                }
            } catch (Exception $e) { /* ignore */ }
            $_SESSION['person_id'] = $user['people_id'] ?? null;
            if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
                try {
                    $pid = $_SESSION['person_id'] ?? null;
                    if ($pid) {
                        $ps = $pdo->prepare("SELECT custom_attributes FROM core_people WHERE id = ?");
                        $ps->execute([$pid]);
                        $attrsJson = $ps->fetchColumn();
                        if ($attrsJson) {
                            $attrs = json_decode($attrsJson, true);
                            $div = strtoupper(trim((string)($attrs['division'] ?? '')));
                            if ($div === 'SECURITY') { $allowed['security'] = true; }
                            elseif ($div === 'CLEANING') { $allowed['cleaning'] = true; }
                            elseif ($div === 'FINANCE') { $allowed['finance'] = true; }
                            elseif ($div === 'EXECUTIVE') { $allowed['executive'] = true; }
                            elseif ($div === 'FOUNDATION') { $allowed['foundation'] = true; }
                            elseif ($div === 'ACADEMIC') { $allowed['academic'] = true; }
                        }
                    }

                    // Check if user is a Principal (Kepala Sekolah) in core_units
                    if ($pid) {
                        $stmtPrin = $pdo->prepare("SELECT COUNT(*) FROM core_units WHERE principal_id = ?");
                        $stmtPrin->execute([$pid]);
                        if ($stmtPrin->fetchColumn() > 0) {
                            $allowed['workspace'] = true;
                        }

                        // Check if user is a Homeroom Teacher (Wali Kelas)
                        try {
                            $stmtWali = $pdo->prepare("
                                SELECT COUNT(*) 
                                FROM acad_classes c 
                                JOIN hr_employees e ON c.homeroom_teacher_id = e.id 
                                WHERE e.person_id = ?
                            ");
                            $stmtWali->execute([$pid]);
                            if ($stmtWali->fetchColumn() > 0) {
                                $allowed['workspace'] = true;
                            }
                        } catch (\Throwable $e) {}
                    }
                } catch (Exception $e) { /* ignore */ }
            }
            $_SESSION['allowed_modules'] = $allowed;
            
            // Redirect logic
            $role = strtoupper(trim($_SESSION['role'] ?? ''));
            if (in_array($role, ['SUPERADMIN','ADMIN','ADMINISTRATOR','SUPERUSER'])) {
                header("Location: " . __ais_redirect_prefix() . "index.php");
                exit;
            }
            
            // Role specific redirects
            if (isset($allowed['workspace']) && $allowed['workspace']) {
                header("Location: " . __ais_redirect_prefix() . "modules/workspace/index.php");
                exit;
            }

            header("Location: index.php");
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Mohon isi username dan password.";
    }
}
$ver = [];
try { $ver = require __DIR__ . '/config/version.php'; } catch (\Throwable $e) {}
$vstr = isset($ver['version']) ? (string)$ver['version'] : '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
$isLocal = ($serverName === 'localhost' || $serverName === '127.0.0.1');
$isTest = (preg_match('#/AIStest/#i', $scriptName) || preg_match('#/AIStest/#i', $reqUri) || stripos($serverName, 'test') !== false);
$envLabel = $isLocal ? 'Local' : ($isTest ? 'Staging' : 'Production');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SekolahOS</title>
    <script>
        (function() {
            var m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
            var isLocal = (location.hostname === 'localhost' || location.hostname === '127.0.0.1');
            var port = window.location.port;
            if (isLocal && (port === '8000' || port === '8080')) {
                window.BASE_URL = '/';
            } else {
                window.BASE_URL = m ? ('/' + m[1] + '/') : (isLocal ? '/AIS/' : '/');
            }
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='10' fill='%230256D1'/%3E%3Ctext x='32' y='44' font-size='36' text-anchor='middle' fill='white'%3ESO%3C/text%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center">

    <div class="w-full max-w-md p-6">
        
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 p-8 text-center">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mx-auto mb-4 text-white text-3xl shadow-inner">
                    <i class="fas fa-school"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-1">SekolahOS</h1>
                <p class="text-blue-100 text-sm">Sistem Informasi Manajemen Sekolah</p>
                <div class="mt-2 flex items-center justify-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-white/20 text-white"><?php echo htmlspecialchars($envLabel); ?></span>
                    <?php if ($vstr): ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-white/20 text-white">v<?php echo htmlspecialchars($vstr); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form -->
            <div class="p-8">
                <h2 class="text-xl font-bold text-slate-800 mb-6 text-center">Silakan Login</h2>

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 flex items-center gap-2 border border-red-100">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Username</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-400"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="w-full border border-slate-300 rounded-lg pl-10 pr-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" placeholder="Masukkan username" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-400"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="w-full border border-slate-300 rounded-lg pl-10 pr-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" placeholder="Masukkan password" required>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-6 mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-slate-600">Ingat Saya</span>
                        </label>
                        <a href="#" class="text-sm text-blue-600 hover:underline">Lupa Password?</a>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg shadow-blue-200 transition-all active:scale-[0.98]">
                        Masuk Sistem <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>
            
        </div>

    </div>

</body>
</html>
