<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$action = $_GET['action'] ?? '';

 

function log_activity($pdo, $module, $category, $action, $entity_type, $entity_id, $title, $description) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        entity_id VARCHAR(64) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$module, $category, $action, $entity_type, (string)$entity_id, $title, $description, $userId]);
}

function ensure_daily_table($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS acad_attendance_daily (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('HADIR','SAKIT','IZIN','ALFA','CUTI') NOT NULL,
        note VARCHAR(255) DEFAULT NULL,
        recorded_by INT DEFAULT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        request_id VARCHAR(64) DEFAULT NULL,
        UNIQUE KEY uniq_student_day (student_id, class_id, date),
        UNIQUE KEY uniq_request_id (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

if (!function_exists('getSetting')) {
    function getSetting($pdo, $key) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            return $stmt->fetchColumn();
        } catch (Throwable $e) { return null; }
    }
}

if (!function_exists('sendWhatsAppMessage')) {
    function sendWhatsAppMessage($pdo, $text, $targetOverride = null) {
        $prefix = '';
        try {
            $prefSetting = (string)getSetting($pdo, 'wa_prefix_testing');
            $isTest = false;
            $req = $_SERVER['REQUEST_URI'] ?? '';
            $scr = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = __DIR__ ?? '';
            if (stripos($req, '/AIStest/') !== false || stripos($scr, '/AIStest/') !== false || stripos($dir, 'AIStest') !== false) {
                $isTest = true;
            }
            if ($prefSetting !== '') { $prefix = $prefSetting; }
            elseif ($isTest) { $prefix = '[TEST] '; }
        } catch (Throwable $e) {}
        if ($prefix !== '' && strpos($text, $prefix) !== 0) {
            $text = $prefix . $text;
        }
        $url = trim((string)getSetting($pdo, 'wa_api_url'));
        $token = trim((string)getSetting($pdo, 'wa_api_token'));
        $target = $targetOverride ?: trim((string)getSetting($pdo, 'wa_security_target'));
        if ($url === '' || $token === '' || $text === '') return ['success' => false, 'error' => 'Konfigurasi tidak lengkap'];
        $t = preg_replace('/[^0-9]/', '', (string)$target);
        if ($t === '') return ['success' => false, 'error' => 'Target kosong'];
        if (substr($t, 0, 1) === '0') $t = '62' . substr($t, 1);
        if (strpos($t, '@') === false) $t .= '@c.us';
        $payload = json_encode(['to' => $t, 'message' => $text, 'clientId' => $token], JSON_UNESCAPED_UNICODE);
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 20, 'ignore_errors' => true], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        try {
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp === false) return ['success' => false, 'error' => 'Koneksi gagal'];
            $j = json_decode($resp, true);
            if ($j && isset($j['success'])) return ['success' => !!$j['success'], 'error' => $j['error'] ?? null];
            return ['success' => false, 'error' => 'Respon tidak valid'];
        } catch (Throwable $e) { return ['success' => false, 'error' => $e->getMessage()]; }
    }
}

try {
    if ($action == 'get_students_for_attendance') {
        $class_id = $_GET['class_id'] ?? '';
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');

        if (!$class_id) throw new Exception("Class ID is required");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check Homeroom
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(u.code) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                    exit;
                }
            }
        }

        // Get students in the class
        $sql = "SELECT p.id, p.name, p.identity_number as nis, 
                       am.id as attendance_id, am.active_days, am.hadir, am.sakit, am.izin, am.alfa, am.cuti, am.remarks
                FROM core_people p
                JOIN acad_student_classes sc ON p.id = sc.student_id
                LEFT JOIN acad_attendance_monthly am ON p.id = am.student_id 
                     AND am.class_id = sc.class_id 
                     AND am.month = ? AND am.year = ?
                WHERE sc.class_id = ? AND sc.status = 'ACTIVE'
                ORDER BY p.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$month, $year, $class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $students]);
    } 
    elseif ($action == 'save_attendance_batch') {
        $data = json_decode(file_get_contents('php://input'), true);
        $records = $data['records'] ?? [];
        $class_id = $data['class_id'] ?? '';
        $month = $data['month'] ?? '';
        $year = $data['year'] ?? '';
        $active_days = isset($data['active_days']) ? (int)$data['active_days'] : 0;

        if (empty($records) || !$class_id || !$month || !$year) {
            throw new Exception("Missing required data");
        }
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(u.code) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
            $stmtU->execute([$class_id]);
            $unitCode = $stmtU->fetchColumn();
            $allowedMap = $_SESSION['allowed_units'] ?? [];
            $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
            $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
            if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                exit;
            }
        }

        $pdo->beginTransaction();

        $sql = "INSERT INTO acad_attendance_monthly 
                (student_id, class_id, month, year, active_days, hadir, sakit, izin, alfa, cuti, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                active_days = VALUES(active_days),
                hadir = VALUES(hadir),
                sakit = VALUES(sakit),
                izin = VALUES(izin),
                alfa = VALUES(alfa),
                cuti = VALUES(cuti),
                remarks = VALUES(remarks)";
        
        $stmt = $pdo->prepare($sql);

        // Fallback: if active_days not provided, try to read existing value from DB
        if ($active_days <= 0) {
            $q = $pdo->prepare("SELECT active_days FROM acad_attendance_monthly WHERE class_id = ? AND month = ? AND year = ? LIMIT 1");
            $q->execute([$class_id, $month, $year]);
            $exist = $q->fetchColumn();
            if ($exist !== false && $exist > 0) {
                $active_days = (int)$exist;
            }
        }

        foreach ($records as $row) {
            $izin = isset($row['izin']) ? (int)$row['izin'] : 0;
            $sakit = isset($row['sakit']) ? (int)$row['sakit'] : 0;
            $alfa = isset($row['alfa']) ? (int)$row['alfa'] : 0;
            $cuti = isset($row['cuti']) ? (int)$row['cuti'] : 0;
            $remarks = isset($row['remarks']) ? (string)$row['remarks'] : '';
            $exceptions = max(0, $izin + $sakit + $alfa + $cuti);
            $hadirClient = isset($row['hadir']) ? (int)$row['hadir'] : null;
            $hadir = ($active_days > 0) ? max(0, $active_days - $exceptions)
                                        : (($hadirClient !== null) ? $hadirClient : max(0, 0 - $exceptions));

            $stmt->execute([
                (int)$row['id'],
                $class_id,
                $month,
                $year,
                $active_days,
                $hadir,
                $sakit,
                $izin,
                $alfa,
                $cuti,
                $remarks
            ]);

            // --- MONTHLY NOTIFICATION LOGIC ---
            // If there is ANY absence (Sakit/Izin/Alfa) in the month, we might want to notify?
            // BUT usually monthly is a RECAP. 
            // The user asked for "absensi harian tersimpan... tidak ada notif wa ke ortu"
            // This block is for `save_attendance_batch` which is MONTHLY recap.
            // If the user is saving DAILY attendance via `save_attendance_daily_batch`, that logic is below.
            // If the user is using the Monthly Batch form to input daily data (aggregated), 
            // we probably shouldn't spam WA for every student for the whole month at once.
            // However, if the user means "Daily Attendance" form in Principal view, that calls `save_attendance_daily_batch`.
            // Let's check `save_attendance_daily_batch` logic below.
        }

        $pdo->commit();
        // Log activity: Attendance recap saved (with class name)
        try {
            $stmtCls = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
            $stmtCls->execute([$class_id]);
            $className = $stmtCls->fetchColumn();
            if (!$className) { $className = (string)$class_id; }
            log_activity(
                $pdo,
                'ACADEMIC',
                'ATTENDANCE_RECAP',
                'RECAP_SAVE',
                'CLASS',
                $class_id,
                'Rekap Presensi Disimpan • ' . $className,
                "Kelas: [{$class_id}] {$className}, Bulan: {$month}, Tahun: {$year}, Baris: " . count($records)
            );
        } catch (\Throwable $e) { /* swallow logging errors */ }
        
        echo json_encode(['success' => true, 'message' => 'Saved', 'data' => ['class_id' => $class_id, 'month' => $month, 'year' => $year, 'rows' => count($records)]]);
    }
    elseif ($action == 'get_attendance_summary') {
        $unit = $_GET['unit'] ?? 'all';
        $month = $_GET['month'] ?? '';
        $year = $_GET['year'] ?? '';
        
        $params = [];
        $where = "";
        
        if ($unit !== 'all') {
            $where = "WHERE u.code = ?";
            $params[] = $unit;
        }
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $map = $_SESSION['allowed_units'] ?? [];
            $codes = array_keys(array_filter(is_array($map) ? $map : []));
            $up = array_map(function($s){ return strtoupper(trim((string)$s)); }, $codes);
            if ($unit === 'all') {
                if (count($up) === 0) { echo json_encode(['success' => true, 'data' => []]); exit; }
                $where = "WHERE (UPPER(u.code) IN (" . implode(',', array_fill(0, count($up), '?')) . ") OR UPPER(u.receipt_code) IN (" . implode(',', array_fill(0, count($up), '?')) . "))";
                $params = array_merge($up, $up);
            } else {
                $unitUp = strtoupper(trim((string)$unit));
                if (!in_array($unitUp, $up)) { echo json_encode(['success' => true, 'data' => []]); exit; }
            }
        }

        if ($month) {
            $where .= ($where ? " AND " : " WHERE ") . "am.month = ?";
            $params[] = $month;
        }
        if ($year) {
            $where .= ($where ? " AND " : " WHERE ") . "am.year = ?";
            $params[] = $year;
        }

        $sql = "SELECT am.class_id, c.name as class_name, am.month, am.year, am.active_days,
                       u.code as unit_code,
                       COUNT(am.student_id) as total_students,
                       SUM(am.sakit) as total_sakit,
                       SUM(am.izin) as total_izin,
                       SUM(am.alfa) as total_alfa,
                       SUM(am.cuti) as total_cuti
                FROM acad_attendance_monthly am
                JOIN acad_classes c ON am.class_id = c.id
                JOIN acad_class_levels cl ON c.level_id = cl.id
                JOIN core_units u ON cl.unit_id = u.id
                $where
                GROUP BY am.class_id, am.month, am.year
                ORDER BY am.year DESC, am.month DESC, c.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $summary]);
    }
    elseif ($action == 'get_class_attendance_summary') {
        $class_id = $_GET['class_id'] ?? 0;
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check Homeroom
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                    echo json_encode(['success' => true, 'data' => []]);
                    exit;
                }
            }
        }
        $sql = "SELECT am.month, am.year, am.active_days,
                       SUM(am.sakit) as total_sakit,
                       SUM(am.izin) as total_izin,
                       SUM(am.alfa) as total_alfa,
                       SUM(am.cuti) as total_cuti,
                       COUNT(DISTINCT am.student_id) as total_students
                FROM acad_attendance_monthly am
                WHERE am.class_id = ?
                GROUP BY am.month, am.year
                ORDER BY am.year DESC, am.month DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$class_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    }
    elseif ($action == 'get_student_attendance_summary') {
        $student_id = $_GET['student_id'] ?? 0;
        $sql = "SELECT am.month, am.year, am.active_days,
                       am.sakit, am.izin, am.alfa, am.cuti, am.hadir
                FROM acad_attendance_monthly am
                WHERE am.student_id = ?
                ORDER BY am.year DESC, am.month DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    }
    elseif ($action == 'get_daily_attendance') {
        ensure_daily_table($pdo);
        $class_id = $_GET['class_id'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!$class_id) throw new Exception("Class ID is required");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check if user is homeroom teacher for this class
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                    echo json_encode(['success' => true, 'data' => []]);
                    exit;
                }
            }
        }
        $sql = "SELECT p.id, p.name, p.identity_number as nis, p.gender, p.birth_place, p.birth_date,
                       d.status, d.note
                FROM core_people p
                JOIN acad_student_classes sc ON p.id = sc.student_id
                LEFT JOIN acad_attendance_daily d ON d.student_id = p.id AND d.class_id = sc.class_id AND d.date = ?
                WHERE sc.class_id = ? AND sc.status = 'ACTIVE'
                ORDER BY p.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date, $class_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }
    elseif ($action == 'get_daily_class_summary') {
        ensure_daily_table($pdo);
        $class_id = $_GET['class_id'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!$class_id) throw new Exception("Class ID is required");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check Homeroom
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                    echo json_encode(['success' => true, 'data' => ['present' => 0, 'izin' => 0, 'sakit' => 0, 'alfa' => 0, 'cuti' => 0]]);
                    exit;
                }
            }
        }
        $stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM acad_attendance_daily WHERE class_id = ? AND date = ? GROUP BY status");
        $stmt->execute([$class_id, $date]);
        $map = ['HADIR' => 0, 'IZIN' => 0, 'SAKIT' => 0, 'ALFA' => 0, 'CUTI' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $k = strtoupper(trim((string)$r['status']));
            $map[$k] = (int)$r['cnt'];
        }
        echo json_encode(['success' => true, 'data' => ['present' => $map['HADIR'], 'izin' => $map['IZIN'], 'sakit' => $map['SAKIT'], 'alfa' => $map['ALFA'], 'cuti' => $map['CUTI']]]);
    }
    elseif ($action == 'get_unit_daily_status') {
        ensure_daily_table($pdo);
        $unit = $_GET['unit'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        $whereUnit = "";
        $params = [];
        
        // RBAC Check & Unit Filtering
        if ($unit !== 'all') {
            $whereUnit = "AND (UPPER(u.code) = ? OR UPPER(u.receipt_code) = ?)";
            $params[] = strtoupper($unit);
            $params[] = strtoupper($unit);
        }
        
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $map = $_SESSION['allowed_units'] ?? [];
            $codes = array_keys(array_filter(is_array($map) ? $map : []));
            $up = array_map(function($s){ return strtoupper(trim((string)$s)); }, $codes);
            
            if ($unit !== 'all') {
                if (!in_array(strtoupper($unit), $up)) {
                    echo json_encode(['success' => true, 'data' => []]);
                    exit;
                }
            } else {
                if (empty($up)) { echo json_encode(['success' => true, 'data' => []]); exit; }
                $inClause = implode(',', array_fill(0, count($up), '?'));
                $whereUnit .= " AND (UPPER(u.code) IN ($inClause) OR UPPER(u.receipt_code) IN ($inClause))";
                foreach ($up as $x) $params[] = $x;
                foreach ($up as $x) $params[] = $x;
            }
        }

        // Query: Get all classes in unit and check if they have any attendance records for the date
        // We use a LEFT JOIN on a subquery or EXISTS check
        $sql = "SELECT c.id, c.name, 
                       (CASE WHEN EXISTS (
                           SELECT 1 FROM acad_attendance_daily d 
                           WHERE d.class_id = c.id AND d.date = ?
                       ) THEN 'Submitted' ELSE 'Pending' END) as status
                FROM acad_classes c
                JOIN acad_class_levels l ON c.level_id = l.id
                JOIN core_units u ON l.unit_id = u.id
                WHERE 1=1 $whereUnit
                ORDER BY l.order_index ASC, c.name ASC";
        
        // Prepend date to params
        array_unshift($params, $date);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
    }
    elseif ($action == 'save_attendance_daily_batch') {
        ensure_daily_table($pdo);
        $data = json_decode(file_get_contents('php://input'), true);
        $records = $data['records'] ?? [];
        $class_id = $data['class_id'] ?? '';
        $date = $data['date'] ?? date('Y-m-d');
        if (empty($records) || !$class_id || !$date) throw new Exception("Missing required data");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check Homeroom
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                    exit;
                }
            }
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO acad_attendance_daily (student_id, class_id, date, status, note, recorded_by, request_id)
                               VALUES (?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note)");
        $userId = $_SESSION['user_id'] ?? null;
        $saved = 0;
        foreach ($records as $row) {
            $sid = isset($row['id']) ? (int)$row['id'] : 0;
            $status = strtoupper(trim((string)($row['status'] ?? 'HADIR')));
            $note = isset($row['note']) ? (string)$row['note'] : '';
            $req = isset($row['request_id']) ? (string)$row['request_id'] : null;
            if (!$sid) continue;
            try {
                $stmt->execute([$sid, $class_id, $date, $status, $note, $userId, $req]);
                $saved++;

                // --- NOTIFICATION LOGIC (IZIN/SAKIT/ALFA) ---
                if (in_array($status, ['IZIN', 'SAKIT', 'ALFA'])) {
                    try {
                        $stmtInfo = $pdo->prepare("
                            SELECT p.name, c.name as class_name,
                                   d.guardian_mobile_1, d.guardian_phone, d.guardian_name,
                                   p.custom_attributes
                            FROM core_people p
                            LEFT JOIN acad_student_details d ON p.id = d.student_id
                            LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
                            LEFT JOIN acad_classes c ON sc.class_id = c.id
                            WHERE p.id = ?
                            LIMIT 1
                        ");
                        $stmtInfo->execute([$sid]);
                        $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                        if ($info) {
                            $phone = '';
                            // Priority: guardian_mobile_1 > guardian_phone > custom_attributes
                            if (!empty($info['guardian_mobile_1'])) $phone = trim((string)$info['guardian_mobile_1']);
                            if (!$phone && !empty($info['guardian_phone'])) $phone = trim((string)$info['guardian_phone']);
                            if (!$phone && !empty($info['custom_attributes'])) {
                                $ca = json_decode($info['custom_attributes'], true);
                                if (isset($ca['mobile_phone']) && $ca['mobile_phone']) $phone = trim((string)$ca['mobile_phone']);
                            }

                            if ($phone) {
                                $sName = $info['name'];
                                $cName = $info['class_name'] ?? '-';
                                $dateFmt = date('d-m-Y', strtotime($date));
                                
                                $msg = "*LAPORAN KETIDAKHADIRAN SISWA*\n\n";
                                $msg .= "Diberitahukan bahwa siswa:\n";
                                $msg .= "Nama: *$sName*\n";
                                $msg .= "Kelas: *$cName*\n";
                                $msg .= "Tanggal: *$dateFmt*\n";
                                $msg .= "Status: *$status*\n";
                                if ($note) $msg .= "Keterangan: $note\n";
                                $msg .= "\nMohon perhatian Bapak/Ibu Wali Murid. Terima kasih.";

                                sendWhatsAppMessage($pdo, $msg, $phone);
                            } else {
                                // Log failure to find phone number
                                log_activity($pdo, 'ACADEMIC', 'ATTENDANCE_NOTIFY', 'FAIL', 'STUDENT', $sid, 'No Phone Number', "Could not find phone number for $sName");
                            }
                        }
                    } catch (\Throwable $e) {
                        log_activity($pdo, 'ACADEMIC', 'ATTENDANCE_NOTIFY', 'ERROR', 'STUDENT', $sid, 'Notification Error', $e->getMessage());
                    }
                }
                // ---------------------------------------------

            } catch (\Throwable $e) {}
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Saved', 'data' => ['class_id' => $class_id, 'date' => $date, 'rows' => $saved]]);
    }
    elseif ($action == 'get_class_attendance_month') {
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        if (!$class_id) throw new Exception("Missing class_id");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
            $stmtU->execute([$class_id]);
            $unitCode = $stmtU->fetchColumn();
            $allowedMap = $_SESSION['allowed_units'] ?? [];
            $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
            $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
            if (!$unitCode || !in_array($unitCode, $allowedUp)) { echo json_encode(['success' => true, 'data' => []]); exit; }
        }
        $stmt = $pdo->prepare("SELECT 
                                    SUM(am.hadir) AS hadir,
                                    SUM(am.sakit) AS sakit,
                                    SUM(am.izin) AS izin,
                                    SUM(am.alfa) AS alfa,
                                    SUM(am.cuti) AS cuti,
                                    MAX(am.active_days) AS active_days,
                                    COUNT(DISTINCT am.student_id) AS total_students
                               FROM acad_attendance_monthly am
                               WHERE am.class_id = ? AND am.month = ? AND am.year = ?");
        $stmt->execute([$class_id, $month, $year]);
        $sum = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasMonthly = $sum && array_sum(array_map('intval', [$sum['hadir'],$sum['izin'],$sum['sakit'],$sum['alfa'],$sum['cuti']])) > 0;
        if ($hasMonthly) {
            echo json_encode(['success' => true, 'data' => [
                'active_days' => (int)($sum['active_days'] ?? 0),
                'total_students' => (int)($sum['total_students'] ?? 0),
                'hadir' => (int)($sum['hadir'] ?? 0),
                'izin' => (int)($sum['izin'] ?? 0),
                'sakit' => (int)($sum['sakit'] ?? 0),
                'alfa' => (int)($sum['alfa'] ?? 0),
                'cuti' => (int)($sum['cuti'] ?? 0),
                'source' => 'MONTHLY'
            ]]);
            exit;
        }
        ensure_daily_table($pdo);
        $stmt2 = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM acad_attendance_daily WHERE class_id = ? AND YEAR(date) = ? AND MONTH(date) = ? GROUP BY status");
        $stmt2->execute([$class_id, $year, $month]);
        $map = ['HADIR' => 0, 'IZIN' => 0, 'SAKIT' => 0, 'ALFA' => 0, 'CUTI' => 0];
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $map[strtoupper(trim((string)$r['status']))] = (int)$r['cnt'];
        }
        $stmtDays = $pdo->prepare("SELECT COUNT(DISTINCT date) AS days FROM acad_attendance_daily WHERE class_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
        $stmtDays->execute([$class_id, $year, $month]);
        $days = (int)$stmtDays->fetchColumn();
        $stmtStud = $pdo->prepare("SELECT COUNT(*) FROM acad_student_classes WHERE class_id = ? AND status = 'ACTIVE'");
        $stmtStud->execute([$class_id]);
        $totalStudents = (int)$stmtStud->fetchColumn();
        echo json_encode(['success' => true, 'data' => [
            'active_days' => $days,
            'total_students' => $totalStudents,
            'hadir' => $map['HADIR'],
            'izin' => $map['IZIN'],
            'sakit' => $map['SAKIT'],
            'alfa' => $map['ALFA'],
            'cuti' => $map['CUTI'],
            'source' => 'DAILY'
        ]]);
    }
    elseif ($action == 'get_daily_calendar') {
        ensure_daily_table($pdo);
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        if (!$class_id) throw new Exception("Missing class_id");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
            $stmtU->execute([$class_id]);
            $unitCode = $stmtU->fetchColumn();
            $allowedMap = $_SESSION['allowed_units'] ?? [];
            $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
            $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
            if (!$unitCode || !in_array($unitCode, $allowedUp)) { echo json_encode(['success' => true, 'data' => []]); exit; }
        }
        $stmt = $pdo->prepare("SELECT date, status, COUNT(*) AS cnt FROM acad_attendance_daily WHERE class_id = ? AND YEAR(date) = ? AND MONTH(date) = ? GROUP BY date, status ORDER BY date ASC");
        $stmt->execute([$class_id, $year, $month]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $d = $r['date'];
            if (!isset($map[$d])) $map[$d] = ['HADIR'=>0,'IZIN'=>0,'SAKIT'=>0,'ALFA'=>0,'CUTI'=>0];
            $k = strtoupper(trim((string)$r['status']));
            $map[$d][$k] = (int)$r['cnt'];
        }
        $out = [];
        foreach ($map as $d => $cnts) {
            $total = max(1, $cnts['HADIR'] + $cnts['IZIN'] + $cnts['SAKIT'] + $cnts['ALFA'] + $cnts['CUTI']);
            $pct = round(($cnts['HADIR'] / $total) * 100);
            $out[] = [
                'date' => $d,
                'hadir' => $cnts['HADIR'],
                'izin' => $cnts['IZIN'],
                'sakit' => $cnts['SAKIT'],
                'alfa' => $cnts['ALFA'],
                'cuti' => $cnts['CUTI'],
                'present_pct' => $pct
            ];
        }
        echo json_encode(['success' => true, 'data' => $out]);
    }
    elseif ($action == 'get_month_student_attendance') {
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        if (!$class_id) throw new Exception("Missing class_id");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
            $stmtU->execute([$class_id]);
            $unitCode = $stmtU->fetchColumn();
            $allowedMap = $_SESSION['allowed_units'] ?? [];
            $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
            $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
            if (!$unitCode || !in_array($unitCode, $allowedUp)) { echo json_encode(['success' => true, 'data' => []]); exit; }
        }
        $sql = "SELECT am.student_id, p.name, p.identity_number AS nis, am.hadir, am.izin, am.sakit, am.alfa, am.cuti, am.active_days
                FROM acad_attendance_monthly am
                JOIN core_people p ON am.student_id = p.id
                WHERE am.class_id = ? AND am.month = ? AND am.year = ?
                ORDER BY p.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$class_id, $month, $year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) { echo json_encode(['success' => true, 'data' => $rows, 'source' => 'MONTHLY']); exit; }
        ensure_daily_table($pdo);
        $sql2 = "SELECT p.id AS student_id, p.name, p.identity_number AS nis, ad.status, COUNT(*) AS cnt
                 FROM core_people p
                 JOIN acad_student_classes sc ON p.id = sc.student_id
                 LEFT JOIN acad_attendance_daily ad ON ad.student_id = p.id AND ad.class_id = sc.class_id AND YEAR(ad.date) = ? AND MONTH(ad.date) = ?
                 WHERE sc.class_id = ? AND sc.status = 'ACTIVE'
                 GROUP BY p.id, p.name, p.identity_number, ad.status";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$year, $month, $class_id]);
        $agg = [];
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sid = (int)$r['student_id'];
            if (!isset($agg[$sid])) $agg[$sid] = ['student_id'=>$sid,'name'=>$r['name'],'nis'=>$r['nis'],'hadir'=>0,'izin'=>0,'sakit'=>0,'alfa'=>0,'cuti'=>0,'active_days'=>0];
            $st = strtoupper(trim((string)$r['status']));
            $cnt = (int)$r['cnt'];
            if ($st === 'HADIR') $agg[$sid]['hadir'] += $cnt;
            elseif ($st === 'IZIN') $agg[$sid]['izin'] += $cnt;
            elseif ($st === 'SAKIT') $agg[$sid]['sakit'] += $cnt;
            elseif ($st === 'ALFA') $agg[$sid]['alfa'] += $cnt;
            elseif ($st === 'CUTI') $agg[$sid]['cuti'] += $cnt;
        }
        $out = array_values($agg);
        foreach ($out as &$o) { $o['active_days'] = max(0, $o['hadir'] + $o['izin'] + $o['sakit'] + $o['alfa'] + $o['cuti']); }
        echo json_encode(['success' => true, 'data' => $out, 'source' => 'DAILY']);
    }
    elseif ($action == 'rollup_daily_to_monthly') {
        ensure_daily_table($pdo);
        $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
        $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('n');
        $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
        if (!$class_id) throw new Exception("Missing class_id");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check Homeroom
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) { http_response_code(403); echo json_encode(['success' => false, 'error' => 'Unit not allowed']); exit; }
            }
        }
        $sqlAgg = "SELECT student_id, status, COUNT(*) AS cnt 
                   FROM acad_attendance_daily 
                   WHERE class_id = ? AND YEAR(date) = ? AND MONTH(date) = ?
                   GROUP BY student_id, status";
        $stmtAgg = $pdo->prepare($sqlAgg);
        $stmtAgg->execute([$class_id, $year, $month]);
        $rows = $stmtAgg->fetchAll(PDO::FETCH_ASSOC);
        $byStudent = [];
        foreach ($rows as $r) {
            $sid = (int)$r['student_id'];
            $st = strtoupper(trim((string)$r['status']));
            $cnt = (int)$r['cnt'];
            if (!isset($byStudent[$sid])) $byStudent[$sid] = ['hadir'=>0,'izin'=>0,'sakit'=>0,'alfa'=>0,'cuti'=>0];
            if ($st === 'HADIR') $byStudent[$sid]['hadir'] += $cnt;
            elseif ($st === 'IZIN') $byStudent[$sid]['izin'] += $cnt;
            elseif ($st === 'SAKIT') $byStudent[$sid]['sakit'] += $cnt;
            elseif ($st === 'ALFA') $byStudent[$sid]['alfa'] += $cnt;
            elseif ($st === 'CUTI') $byStudent[$sid]['cuti'] += $cnt;
        }
        $pdo->beginTransaction();
        $stmtUp = $pdo->prepare("INSERT INTO acad_attendance_monthly 
                (student_id, class_id, month, year, active_days, hadir, sakit, izin, alfa, cuti, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                active_days = VALUES(active_days),
                hadir = VALUES(hadir),
                sakit = VALUES(sakit),
                izin = VALUES(izin),
                alfa = VALUES(alfa),
                cuti = VALUES(cuti),
                remarks = VALUES(remarks)");
        $saved = 0;
        foreach ($byStudent as $sid => $cnts) {
            $activeDays = max(0, $cnts['hadir'] + $cnts['izin'] + $cnts['sakit'] + $cnts['alfa'] + $cnts['cuti']);
            $stmtUp->execute([$sid, $class_id, $month, $year, $activeDays, $cnts['hadir'], $cnts['sakit'], $cnts['izin'], $cnts['alfa'], $cnts['cuti'], 'Rollup from daily']);
            $saved++;
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Rolled up', 'data' => ['class_id' => $class_id, 'month' => $month, 'year' => $year, 'rows' => $saved]]);
    }
    elseif ($action == 'get_semester_class_summary') {
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
        $semester = strtoupper(trim($_GET['semester'] ?? ''));
        if (!$class_id) throw new Exception("Missing class_id");
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            // Check Homeroom
            $isHomeroom = false;
            $pid = $_SESSION['person_id'] ?? null;
            if ($pid) {
                $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE id = ? AND homeroom_teacher_id = ?");
                $stmtHR->execute([$class_id, $pid]);
                if ($stmtHR->fetchColumn()) {
                    $isHomeroom = true;
                }
            }

            if (!$isHomeroom) {
                $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) { echo json_encode(['success' => true, 'data' => []]); exit; }
            }
        }
        if (!$academic_year_id) {
            $stmtYear = $pdo->query("SELECT id, name FROM acad_years WHERE status = 'ACTIVE' LIMIT 1");
            $yr = $stmtYear->fetch(PDO::FETCH_ASSOC);
            if ($yr) $academic_year_id = (int)$yr['id'];
        }
        $stmtYear2 = $pdo->prepare("SELECT name FROM acad_years WHERE id = ?");
        $stmtYear2->execute([$academic_year_id]);
        $name = (string)$stmtYear2->fetchColumn();
        $parts = explode('/', $name);
        $startYear = isset($parts[0]) ? (int)$parts[0] : (int)date('Y');
        $endYear = isset($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $months = [];
        if ($semester === 'GANJIL' || $semester === '') {
            $months = array_map(function($m){ return ['month'=>$m,'year'=>null]; }, [7,8,9,10,11,12]);
            foreach ($months as &$m) { $m['year'] = $startYear; }
        } else {
            $months = array_map(function($m){ return ['month'=>$m,'year'=>null]; }, [1,2,3,4,5,6]);
            foreach ($months as &$m) { $m['year'] = $endYear; }
        }
        $total = ['hadir'=>0,'izin'=>0,'sakit'=>0,'alfa'=>0,'cuti'=>0,'active_days'=>0];
        foreach ($months as $m) {
            $stmtM = $pdo->prepare("SELECT SUM(hadir) AS hadir, SUM(izin) AS izin, SUM(sakit) AS sakit, SUM(alfa) AS alfa, SUM(cuti) AS cuti, MAX(active_days) AS active_days FROM acad_attendance_monthly WHERE class_id = ? AND month = ? AND year = ?");
            $stmtM->execute([$class_id, $m['month'], $m['year']]);
            $row = $stmtM->fetch(PDO::FETCH_ASSOC);
            $has = $row && array_sum(array_map('intval', [$row['hadir'],$row['izin'],$row['sakit'],$row['alfa'],$row['cuti']])) > 0;
            if ($has) {
                $total['hadir'] += (int)$row['hadir'];
                $total['izin'] += (int)$row['izin'];
                $total['sakit'] += (int)$row['sakit'];
                $total['alfa'] += (int)$row['alfa'];
                $total['cuti'] += (int)$row['cuti'];
                $total['active_days'] += (int)$row['active_days'];
            } else {
                ensure_daily_table($pdo);
                $stmtD = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM acad_attendance_daily WHERE class_id = ? AND YEAR(date) = ? AND MONTH(date) = ? GROUP BY status");
                $stmtD->execute([$class_id, $m['year'], $m['month']]);
                foreach ($stmtD->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $k = strtoupper(trim((string)$r['status']));
                    $cnt = (int)$r['cnt'];
                    if ($k === 'HADIR') $total['hadir'] += $cnt;
                    elseif ($k === 'IZIN') $total['izin'] += $cnt;
                    elseif ($k === 'SAKIT') $total['sakit'] += $cnt;
                    elseif ($k === 'ALFA') $total['alfa'] += $cnt;
                    elseif ($k === 'CUTI') $total['cuti'] += $cnt;
                }
                $stmtDays = $pdo->prepare("SELECT COUNT(DISTINCT date) AS days FROM acad_attendance_daily WHERE class_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
                $stmtDays->execute([$class_id, $m['year'], $m['month']]);
                $total['active_days'] += (int)$stmtDays->fetchColumn();
            }
        }
        echo json_encode(['success' => true, 'data' => $total, 'months_included' => $months]);
    }
    elseif ($action == 'get_homeroom_class') {
        $personId = $_SESSION['person_id'] ?? null;
        if (!$personId) { echo json_encode(['success' => false, 'message' => 'No person']); exit; }
        $stmtYear = $pdo->query("SELECT id FROM acad_years WHERE status = 'ACTIVE' LIMIT 1");
        $activeYear = $stmtYear->fetch(PDO::FETCH_ASSOC);
        $yearId = $activeYear ? (int)$activeYear['id'] : null;
        $sql = "SELECT c.id, c.name, c.slug, u.code AS unit_code
                FROM acad_classes c
                JOIN acad_class_levels l ON c.level_id = l.id
                JOIN core_units u ON l.unit_id = u.id
                WHERE c.homeroom_teacher_id = ?
                " . ($yearId ? "AND c.academic_year_id = ?" : "") . "
                ORDER BY c.id DESC LIMIT 1";
        $params = $yearId ? [$personId, $yearId] : [$personId];
        
        // Removed allowed_units check for homeroom teacher - if you are the teacher, you have access
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $row ?: null]);
    }
    elseif ($action == 'missing_class_recaps_by_month') {
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $unit = $_GET['unit'] ?? 'all';
        $where = "";
        $params = [];
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if ($unit !== 'all') {
            $where = "WHERE (UPPER(u.code) = ? OR UPPER(u.receipt_code) = ?)";
            $unitUp = strtoupper(trim((string)$unit));
            $params[] = $unitUp;
            $params[] = $unitUp;
            if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
                $map = $_SESSION['allowed_units'] ?? [];
                $codes = array_keys(array_filter(is_array($map) ? $map : []));
                $up = array_map(function($s){ return strtoupper(trim((string)$s)); }, $codes);
                if (!in_array($unitUp, $up)) {
                    echo json_encode(['success' => true, 'data' => []]);
                    exit;
                }
            }
        } else {
            if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
                $map = $_SESSION['allowed_units'] ?? [];
                $codes = array_keys(array_filter(is_array($map) ? $map : []));
                $up = array_map(function($s){ return strtoupper(trim((string)$s)); }, $codes);
                if (count($up) === 0) { echo json_encode(['success' => true, 'data' => []]); exit; }
                $where = "WHERE (UPPER(u.code) IN (" . implode(',', array_fill(0, count($up), '?')) . ") OR UPPER(u.receipt_code) IN (" . implode(',', array_fill(0, count($up), '?')) . "))";
                $params = array_merge($up, $up);
            }
        }
        $sqlTotal = "SELECT COUNT(*) AS total_classes
                     FROM acad_classes c
                     JOIN acad_class_levels cl ON c.level_id = cl.id
                     JOIN core_units u ON cl.unit_id = u.id
                     $where";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $totalClasses = (int)$stmtTotal->fetchColumn();
        $curMonth = (int)date('n');
        $endMonth = max(1, $curMonth - 1);
        $out = [];
        for ($m = 1; $m <= $endMonth; $m++) {
            $missingWhere = $where ? ($where . " AND am.class_id IS NULL") : "WHERE am.class_id IS NULL";
            $sqlMissing = "SELECT COUNT(*) AS missing_count
                           FROM acad_classes c
                           JOIN acad_class_levels cl ON c.level_id = cl.id
                           JOIN core_units u ON cl.unit_id = u.id
                           LEFT JOIN acad_attendance_monthly am ON am.class_id = c.id AND am.month = ? AND am.year = ?
                           $missingWhere";
            $stmtMissing = $pdo->prepare($sqlMissing);
            $stmtMissing->execute(array_merge([$m, $year], $params));
            $missing = (int)$stmtMissing->fetchColumn();
            $out[] = ['year' => $year, 'month' => $m, 'missing' => $missing, 'total' => $totalClasses];
        }
        echo json_encode(['success' => true, 'data' => $out]);
    }
    elseif ($action == 'delete_attendance_month') {
        $class_id = $_GET['class_id'] ?? '';
        $month = $_GET['month'] ?? '';
        $year = $_GET['year'] ?? '';
        if (!$class_id || !$month || !$year) {
            throw new Exception("Missing class_id, month, or year");
        }
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(COALESCE(u.receipt_code, u.code)) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
            $stmtU->execute([$class_id]);
            $unitCode = $stmtU->fetchColumn();
            $allowedMap = $_SESSION['allowed_units'] ?? [];
            $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
            $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
            if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                exit;
            }
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM acad_attendance_monthly WHERE class_id = ? AND month = ? AND year = ?");
        $stmt->execute([$class_id, $month, $year]);
        $affected = $stmt->rowCount();
        $pdo->commit();
        // Log activity: Attendance recap deleted (with class name)
        try {
            $stmtCls = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
            $stmtCls->execute([$class_id]);
            $className = $stmtCls->fetchColumn();
            if (!$className) { $className = (string)$class_id; }
            log_activity(
                $pdo,
                'ACADEMIC',
                'ATTENDANCE_RECAP',
                'RECAP_DELETE',
                'CLASS',
                $class_id,
                'Rekap Presensi Dihapus • ' . $className,
                "Kelas: [{$class_id}] {$className}, Bulan: {$month}, Tahun: {$year}, Baris dihapus: {$affected}"
            );
        } catch (\Throwable $e) { /* swallow logging errors */ }
        
        echo json_encode(['success' => true, 'message' => 'Deleted', 'data' => ['class_id' => $class_id, 'month' => $month, 'year' => $year, 'rows_deleted' => $affected]]);
    }
    else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
