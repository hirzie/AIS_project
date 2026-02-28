<?php
ini_set('date.timezone', 'Asia/Jakarta');
date_default_timezone_set('Asia/Jakarta');
function ais_init_session() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    if (preg_match('#^/AIStest/#i', $scriptName)) { 
        session_name('AIStestSESSID'); 
        if (preg_match('#^/([^/]+)/#', $scriptName, $m)) {
            ini_set('session.cookie_path', '/' . $m[1] . '/');
        } else {
            ini_set('session.cookie_path', '/AIStest/');
        }
    } elseif (preg_match('#^/AIS/#i', $scriptName)) { 
        session_name('AISSESSID'); 
        if (preg_match('#^/([^/]+)/#', $scriptName, $m)) {
            ini_set('session.cookie_path', '/' . $m[1] . '/');
        } else {
            ini_set('session.cookie_path', '/AIS/');
        }
    } else {
        if (stripos($serverName, 'test') !== false) {
            session_name('AIStestSESSID'); 
            ini_set('session.cookie_path', '/');
        } else {
            session_name('AISSESSID'); 
            ini_set('session.cookie_path', '/');
        }
    }
    
    // Fix for session save path error - use local project directory
    $sessPath = __DIR__ . '/../sessions';
    if (!file_exists($sessPath)) {
        @mkdir($sessPath, 0777, true);
    }
    if (file_exists($sessPath) && is_dir($sessPath)) {
        session_save_path($sessPath);
    } else {
        // Fallback to system temp if local fails
        $sessPath = 'C:/xampp/tmp';
        if (file_exists($sessPath)) { session_save_path($sessPath); }
    }
    
    session_start();
}
function ais_ensure_allowed_units($pdo) {
    ais_init_session();
    
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    
    // Fix: Force reset allowed_units for Admins to prevent stale session data
    if (in_array($role, ['SUPERADMIN', 'ADMIN'])) {
        $_SESSION['allowed_units'] = [];
        return;
    }

    if (isset($_SESSION['allowed_units']) && is_array($_SESSION['allowed_units'])) return;
    
    $allowedUnits = [];
    // Only process for non-admins
    if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
        $personId = $_SESSION['person_id'] ?? null;
        if (!$personId && !empty($_SESSION['user_id'])) {
            try {
                $ps = $pdo->prepare("SELECT people_id FROM core_users WHERE id = ?");
                $ps->execute([$_SESSION['user_id']]);
                $pid = $ps->fetchColumn();
                if ($pid) {
                    $personId = $pid;
                    $_SESSION['person_id'] = $pid;
                }
            } catch (\Throwable $e) {}
        }
        if ($personId) {
            try {
                $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE person_id = ? LIMIT 1");
                $stmtEmp->execute([$personId]);
                $empId = $stmtEmp->fetchColumn();
                if ($empId) {
                    $stmtUA = $pdo->prepare("SELECT u.code AS unit_code FROM hr_unit_access hua JOIN core_units u ON hua.unit_id = u.id WHERE hua.employee_id = ?");
                    $stmtUA->execute([$empId]);
                    $rows = $stmtUA->fetchAll(\PDO::FETCH_COLUMN);
                    foreach ($rows as $uc) {
                        $uc = strtolower(trim((string)$uc));
                        if ($uc !== '') $allowedUnits[$uc] = true;
                    }
                }
            } catch (\Throwable $e) {}

            // Also check if they are Principal in core_units (Implicit Access)
            try {
                 $stmtPrin = $pdo->prepare("SELECT code FROM core_units WHERE principal_id = ?");
                 $stmtPrin->execute([$personId]);
                 $pRows = $stmtPrin->fetchAll(\PDO::FETCH_COLUMN);
                 foreach ($pRows as $puc) {
                     $puc = strtolower(trim((string)$puc));
                     if ($puc !== '') $allowedUnits[$puc] = true;
                 }
            } catch (\Throwable $e) {}

            // Check acad_unit_positions for KEPALA/WAKASEK
            try {
                 $stmtPos = $pdo->prepare("
                    SELECT u.code 
                    FROM acad_unit_positions p
                    JOIN core_units u ON p.unit_id = u.id
                    WHERE p.person_id = ? AND p.is_active = 1
                 ");
                 $stmtPos->execute([$personId]);
                 $posRows = $stmtPos->fetchAll(\PDO::FETCH_COLUMN);
                 foreach ($posRows as $puc) {
                     $puc = strtolower(trim((string)$puc));
                     if ($puc !== '') $allowedUnits[$puc] = true;
                 }
            } catch (\Throwable $e) {}

            // Check if user is a teacher (Guru) in any unit
            // This is critical for Multi-Unit Teacher View
            try {
                 // Check acad_schedules
                 $stmtSch = $pdo->prepare("
                    SELECT DISTINCT u.code
                    FROM acad_schedules s
                    JOIN acad_classes c ON s.class_id = c.id
                    JOIN acad_class_levels l ON c.level_id = l.id
                    JOIN core_units u ON l.unit_id = u.id
                    WHERE s.teacher_id = ?
                 ");
                 $stmtSch->execute([$personId]);
                 $schRows = $stmtSch->fetchAll(\PDO::FETCH_COLUMN);
                 foreach ($schRows as $suc) {
                     $suc = strtolower(trim((string)$suc));
                     if ($suc !== '') $allowedUnits[$suc] = true;
                 }
                 
                 // Check acad_subjects
                 $stmtSub = $pdo->prepare("
                    SELECT DISTINCT u.code
                    FROM acad_subjects s
                    JOIN core_units u ON s.unit_id = u.id
                    WHERE s.teacher_id = ?
                 ");
                 $stmtSub->execute([$personId]);
                 $subRows = $stmtSub->fetchAll(\PDO::FETCH_COLUMN);
                 foreach ($subRows as $suc) {
                     $suc = strtolower(trim((string)$suc));
                     if ($suc !== '') $allowedUnits[$suc] = true;
                 }

                 // Check acad_subject_teachers
                 $stmtST = $pdo->prepare("
                    SELECT DISTINCT u.code
                    FROM acad_subject_teachers ast
                    JOIN acad_subjects s ON ast.subject_id = s.id
                    JOIN core_units u ON s.unit_id = u.id
                    WHERE ast.teacher_id = ?
                 ");
                 $stmtST->execute([$personId]);
                 $stRows = $stmtST->fetchAll(\PDO::FETCH_COLUMN);
                 foreach ($stRows as $suc) {
                     $suc = strtolower(trim((string)$suc));
                     if ($suc !== '') $allowedUnits[$suc] = true;
                 }

                 // Check acad_classes (Homeroom Teacher)
                 $stmtHR = $pdo->prepare("
                    SELECT DISTINCT u.code
                    FROM acad_classes c
                    JOIN acad_class_levels l ON c.level_id = l.id
                    JOIN core_units u ON l.unit_id = u.id
                    JOIN acad_years ay ON c.academic_year_id = ay.id
                    WHERE c.homeroom_teacher_id = ? AND ay.status = 'ACTIVE'
                 ");
                 $stmtHR->execute([$personId]);
                 $hrRows = $stmtHR->fetchAll(\PDO::FETCH_COLUMN);
                 foreach ($hrRows as $suc) {
                     $suc = strtolower(trim((string)$suc));
                     if ($suc !== '') $allowedUnits[$suc] = true;
                 }

            } catch (\Throwable $e) {}
        }
    }
    $_SESSION['allowed_units'] = $allowedUnits;
}
function ais_is_unit_allowed($pdo, $unitCode) {
    ais_init_session();
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if (in_array($role, ['SUPERADMIN','ADMIN'])) return true;
    $u = strtoupper(trim((string)$unitCode));
    $map = $_SESSION['allowed_units'] ?? [];
    $allowed = array_keys(array_filter(is_array($map) ? $map : []));
    $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
    if (in_array($u, $allowedUp)) return true;
    try {
        require_once __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare("SELECT UPPER(code) AS c, UPPER(receipt_code) AS r FROM core_units WHERE UPPER(code) = ? OR UPPER(receipt_code) = ? LIMIT 1");
        $stmt->execute([$u, $u]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $c = $row['c'] ?? null;
            $r = $row['r'] ?? null;
            if (($c && in_array($c, $allowedUp)) || ($r && in_array($r, $allowedUp))) return true;
        }
    } catch (\Throwable $e) {}
    return false;
}
function __ais_log($message) {
    try {
        $line = date('Y-m-d H:i:s') . ' ' . $message . "\n";
        @file_put_contents(__DIR__ . '/../debug_guard.log', $line, FILE_APPEND);
    } catch (\Throwable $e) {}
}
function __ais_redirect_prefix() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // If running on localhost or IP address (local dev), respect the subdirectory if present
    if (stripos($host, 'localhost') !== false || preg_match('/^[\d\.]+$/', $host)) {
        if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
            return '/' . $m[1] . '/';
        }
        $port = $_SERVER['SERVER_PORT'] ?? 80;
        // Support 8000 and 8080 as root
        if ($port == 8000 || $port == 8080) {
            return '/';
        }
    } else {
        // On production/staging domains (e.g. app.alamanahlembang.sch.id), force root '/'
        // because the app is served from root, even if folders on disk are /AIS/
        return '/';
    }
    
    // Default fallback
    return '/';
}
function require_login_and_module($module = null) {
    ais_init_session();
    if (!isset($_SESSION['user_id'])) {
        __ais_log("redirect_login script=" . ($_SERVER['SCRIPT_NAME'] ?? '') . " cookie_path=" . (ini_get('session.cookie_path') ?: ''));
        header("Location: " . __ais_redirect_prefix() . "login.php");
        exit;
    }
    if (!isset($_SESSION['allowed_modules']) || !is_array($_SESSION['allowed_modules']) || count($_SESSION['allowed_modules']) === 0) {
        $roleInit = strtoupper(trim($_SESSION['role'] ?? ''));
        if ($roleInit === 'MANAGER') { $roleInit = 'MANAGERIAL'; }
        elseif ($roleInit === 'SUPERUSER' || $roleInit === 'ADMINISTRATOR') { $roleInit = 'SUPERADMIN'; }
        elseif ($roleInit === 'ADMIN UNIT') { $roleInit = 'ADMIN'; }
        elseif ($roleInit === 'AKADEMIK') { $roleInit = 'ACADEMIC'; }
        elseif ($roleInit === 'YAYASAN') { $roleInit = 'FOUNDATION'; }
        elseif ($roleInit === 'KEUANGAN') { $roleInit = 'FINANCE'; }
        elseif ($roleInit === 'PERPUSTAKAAN') { $roleInit = 'LIBRARY'; }
        elseif ($roleInit === 'ASRAMA') { $roleInit = 'BOARDING'; }
        elseif ($roleInit === 'KANTIN') { $roleInit = 'POS'; }
        $allowed = ['core' => true];
        if (in_array($roleInit, ['SUPERADMIN','ADMIN'])) {
            $modsCfg = require __DIR__ . '/../config/modules.php';
            foreach ($modsCfg as $m) {
                $k = strtolower($m['code'] ?? '');
                if ($k) { $allowed[$k] = true; }
            }
            foreach (['people','kiosk','payroll','counseling'] as $ex) { $allowed[$ex] = true; }
        } elseif ($roleInit === 'ACADEMIC') {
            $allowed['academic'] = true;
        } elseif ($roleInit === 'STAFF') {
            $allowed['academic'] = true;
        } elseif ($roleInit === 'FOUNDATION') {
            $allowed['foundation'] = true;
        } elseif ($roleInit === 'MANAGERIAL') {
            $allowed['executive'] = true;
        } elseif ($roleInit === 'PRINCIPAL' || $roleInit === 'TEACHER' || $roleInit === 'GURU' || $roleInit === 'HOMEROOM') {
            $allowed['workspace'] = true;
        } elseif ($roleInit === 'FINANCE') {
            $allowed['finance'] = true;
        } elseif ($roleInit === 'POS') {
            $allowed['pos'] = true;
        } elseif ($roleInit === 'SECURITY' || $roleInit === 'KEAMANAN') {
            $allowed['security'] = true;
        }
        try {
            require_once __DIR__ . '/../config/database.php';
            $uid = $_SESSION['user_id'] ?? null;
            if ($uid && !in_array($roleInit, ['SUPERADMIN','ADMIN'])) {
                $colStmt = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'access_modules'");
                if ($colStmt && $colStmt->fetch()) {
                    $modsStmt = $pdo->prepare("SELECT access_modules, people_id FROM core_users WHERE id = ?");
                    $modsStmt->execute([$uid]);
                    $row = $modsStmt->fetch(\PDO::FETCH_ASSOC);
                    $modsJson = $row['access_modules'] ?? null;
                    $pid = $row['people_id'] ?? ($_SESSION['person_id'] ?? null);
                    if ($modsJson) {
                        $decoded = json_decode($modsJson, true);
                        if (is_array($decoded) && count($decoded) > 0) {
                            $modsCfg = require __DIR__ . '/../config/modules.php';
                            $keys = array_map(function($m){ return strtolower($m['code'] ?? ''); }, $modsCfg);
                            $keys = array_values(array_filter($keys));
                            $keys = array_merge(['core'], $keys, ['people','kiosk','payroll','counseling']);
                            $allowed = [];
                            foreach ($keys as $k) { $allowed[$k] = ($k === 'core'); }
                            $list = [];
                            $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
                            if ($isAssoc) {
                                foreach ($decoded as $mk => $val) {
                                    if ($val) {
                                        $k = strtolower(trim((string)$mk));
                                        if ($k) { $list[] = $k; }
                                    }
                                }
                            } else {
                                foreach ($decoded as $m) {
                                    $k = strtolower(trim(is_string($m) ? $m : (string)$m));
                                    if ($k) { $list[] = $k; }
                                }
                            }
                            foreach ($list as $k) { $allowed[$k] = true; }
                        }
                    }
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
                        // They also need access to Workspace View (Wali Kelas Tab)
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
                }
            }
        } catch (\Throwable $e) {}
        $_SESSION['allowed_modules'] = $allowed;
        __ais_log("init_allowed_modules role=" . $roleInit . " keys=" . implode(',', array_keys(array_filter($allowed))));
    }
    if ($module) {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (in_array($role, ['SUPERADMIN','ADMIN','ADMINISTRATOR','SUPERUSER'])) {
            __ais_log("allow_admin role=" . $role . " script=" . ($_SERVER['SCRIPT_NAME'] ?? ''));
            return;
        }
        $mods = $_SESSION['allowed_modules'] ?? [];
        if (!(isset($mods[$module]) && $mods[$module] === true)) {
            __ais_log("redirect_noaccess module=" . $module . " role=" . $role . " keys=" . implode(',', array_keys(array_filter($mods))));
            header("Location: " . __ais_redirect_prefix() . "index.php?noaccess=" . urlencode($module));
            exit;
        }
    }
}
