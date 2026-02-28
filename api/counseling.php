<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/guard.php';
ais_init_session();

$action = $_GET['action'] ?? '';

function getSetting($pdo, $key) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    } catch (\Throwable $e) { return null; }
}
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
    } catch (\Throwable $e) {}
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
    } catch (\Throwable $e) { return ['success' => false, 'error' => $e->getMessage()]; }
}
function parseManagerialTargets($pdo, $override = null) {
    $raw = $override;
    if ($raw === null) $raw = (string)getSetting($pdo, 'wa_managerial_targets');
    $arr = [];
    if (is_array($raw)) {
        $arr = $raw;
    } else {
        $s = trim((string)$raw);
        if ($s !== '') {
            $s = str_replace(["\r\n", "\r"], "\n", $s);
            $parts = preg_split('/[,\n]+/', $s);
            foreach ($parts as $p) { $p = trim($p); if ($p !== '') $arr[] = $p; }
        }
    }
    return $arr;
}
function loadWaRecipientsMap($pdo) {
    try {
        $j = (string)getSetting($pdo, 'wa_recipients_map_counseling');
        $m = $j ? json_decode($j, true) : [];
        if (is_array($m) && count($m) > 0) {
            if (isset($m['COUNSELING']) && is_array($m['COUNSELING'])) return ['COUNSELING' => $m['COUNSELING']];
            if (array_values($m) === $m) return ['COUNSELING' => $m];
            return $m;
        }
        $jl = (string)getSetting($pdo, 'wa_recipients_map');
        $ml = $jl ? json_decode($jl, true) : [];
        if (is_array($ml)) {
            if (isset($ml['COUNSELING']) && is_array($ml['COUNSELING'])) return ['COUNSELING' => $ml['COUNSELING']];
            if (array_values($ml) === $ml) return ['COUNSELING' => $ml];
        }
        return [];
    } catch (\Throwable $e) { return []; }
}
function saveWaRecipientsMap($pdo, $map) {
    try {
        $d = [];
        if (isset($map['COUNSELING']) && is_array($map['COUNSELING'])) $d = ['COUNSELING' => $map['COUNSELING']];
        elseif (is_array($map)) $d = ['COUNSELING' => $map];
        else $d = ['COUNSELING' => []];
        $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['wa_recipients_map_counseling', json_encode($d)]);
        return true;
    } catch (\Throwable $e) { return false; }
}
function resolveEmployeeNumbers($pdo, $employeeIds) {
    $nums = [];
    if (!is_array($employeeIds) || count($employeeIds) === 0) return $nums;
    $place = implode(',', array_fill(0, count($employeeIds), '?'));
    try {
        $stmt = $pdo->prepare("SELECT e.id AS employee_id, p.id AS person_id, p.phone, p.custom_attributes FROM hr_employees e JOIN core_people p ON e.person_id = p.id WHERE e.id IN ($place)");
        $stmt->execute($employeeIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $phone = trim((string)($r['phone'] ?? ''));
            $custom = [];
            try { $custom = json_decode($r['custom_attributes'] ?? '{}', true) ?: []; } catch (\Throwable $e) { $custom = []; }
            $mobile = trim((string)($custom['mobile_phone'] ?? ''));
            $num = $mobile !== '' ? $mobile : $phone;
            if ($num !== '') $nums[] = $num;
        }
    } catch (\Throwable $e) {}
    return $nums;
}
function resolveRecipientsNumbers($pdo, $module, $event) {
    $map = loadWaRecipientsMap($pdo);
    $mod = strtoupper(trim($module));
    $ev = strtoupper(trim($event));
    $targets = [];
    if (isset($map[$mod]) && is_array($map[$mod])) {
        if (isset($map[$mod][$ev]) && is_array($map[$mod][$ev])) {
            $targets = $map[$mod][$ev];
        } elseif (!empty($map[$mod])) {
            $targets = $map[$mod];
        }
    }
    if (is_array($targets) && count($targets) > 0) {
        $allInt = true;
        foreach ($targets as $t) { if (!is_int($t) && !ctype_digit((string)$t)) { $allInt = false; break; } }
        if ($allInt) {
            return resolveEmployeeNumbers($pdo, array_map('intval', $targets));
        } else {
            $nums = [];
            foreach ($targets as $t) { $t = trim((string)$t); if ($t !== '') $nums[] = $t; }
            return $nums;
        }
    }
    return parseManagerialTargets($pdo, null);
}
function computeCounselingTeamIds($pdo) {
    $ids = [];
    try {
        $stmt = $pdo->query("SELECT e.id AS employee_id, p.custom_attributes, u.role AS user_role FROM hr_employees e JOIN core_people p ON e.person_id = p.id LEFT JOIN core_users u ON u.people_id = p.id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $custom = [];
            try { $custom = json_decode($r['custom_attributes'] ?? '{}', true) ?: []; } catch (\Throwable $e) { $custom = []; }
            $teams = $custom['teams'] ?? [];
            $uRole = strtoupper(trim((string)($r['user_role'] ?? '')));
            $isBk = (is_array($teams) && in_array('BK', $teams)) || in_array($uRole, ['COUNSELING','BK']);
            if ($isBk) $ids[] = (int)$r['employee_id'];
        }
    } catch (\Throwable $e) {}
    // Merge with saved list if exists
    try {
        $raw = (string)getSetting($pdo, 'counseling_team_employee_ids');
        $arr = $raw ? json_decode($raw, true) : [];
        if (!is_array($arr)) {
            $s = trim($raw);
            $arr = $s !== '' ? array_filter(array_map('intval', preg_split('/[,\n]+/', $s))) : [];
        }
        foreach ($arr as $x) { $ids[] = (int)$x; }
    } catch (\Throwable $e) {}
    $ids = array_values(array_unique(array_filter($ids, function($v){ return is_int($v) && $v > 0; })));
    return $ids;
}
function filterAllowedBkEmployees($pdo, $employeeIds) {
    $allowed = computeCounselingTeamIds($pdo);
    $set = [];
    foreach ($allowed as $a) { $set[$a] = true; }
    $out = [];
    foreach ((array)$employeeIds as $id) {
        $x = (int)$id;
        if ($x > 0 && isset($set[$x])) $out[] = $x;
    }
    return array_values(array_unique($out));
}
function ensureActivityTable($pdo) {
    try {
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
    } catch (\Throwable $e) {}
}
function logActivity($pdo, $module, $category, $action, $entityType, $entityId, $title = null, $description = null) {
    ensureActivityTable($pdo);
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([$module, $category, $action, $entityType, $entityId ? (string)$entityId : null, $title, $description, $_SESSION['user_id'] ?? null]);
    } catch (\Throwable $e) {}
}

function ensureCounselingTicketsExists(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS counseling_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        incident_id INT UNIQUE,
        student_id INT NOT NULL,
        class_id INT DEFAULT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        severity ENUM('LOW','MEDIUM','HIGH') DEFAULT 'LOW',
        status ENUM('OPEN','IN_PROGRESS','CLOSED','REOPEN') DEFAULT 'OPEN',
        created_by INT DEFAULT NULL,
        intro_note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_student (student_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    try {
        $cols = $pdo->query("DESCRIBE counseling_tickets")->fetchAll(PDO::FETCH_COLUMN);
        if ($cols && is_array($cols)) {
            if (!in_array('intro_note', $cols)) {
                $pdo->exec("ALTER TABLE counseling_tickets ADD COLUMN intro_note TEXT DEFAULT NULL");
            }
            if (!in_array('created_by', $cols)) {
                $pdo->exec("ALTER TABLE counseling_tickets ADD COLUMN created_by INT DEFAULT NULL");
            }
            if (!in_array('updated_at', $cols)) {
                $pdo->exec("ALTER TABLE counseling_tickets ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE counseling_tickets MODIFY COLUMN status ENUM('OPEN','IN_PROGRESS','CLOSED','REOPEN') DEFAULT 'OPEN'");
    } catch (Exception $e) {}
}

function ensureCounselingNotesExists(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS counseling_ticket_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        note TEXT NOT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket (ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function ensureAchievementsTableExists(PDO $pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS student_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100) DEFAULT NULL,
        level VARCHAR(100) DEFAULT NULL,
        rank VARCHAR(100) DEFAULT NULL,
        organizer VARCHAR(255) DEFAULT NULL,
        date DATE DEFAULT NULL,
        points INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_student_id (student_id),
        INDEX idx_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
}

try {
    if ($action === 'search_students') {
        $q = $_GET['q'] ?? '';
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        // Search by name or identity_number (NIS)
        $sql = "SELECT p.id, p.name, p.identity_number, c.name as class_name 
                FROM core_people p 
                LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
                LEFT JOIN acad_classes c ON sc.class_id = c.id
                WHERE (p.name LIKE ? OR p.identity_number LIKE ?) 
                AND p.type = 'STUDENT'
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$q%", "%$q%"]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $students]);
    }
    elseif ($action === 'list_tickets') {
        ensureCounselingTicketsExists($pdo);
        $status = strtoupper(trim($_GET['status'] ?? 'OPEN'));
        if (!in_array($status, ['OPEN','IN_PROGRESS','CLOSED','REOPEN'])) { $status = 'OPEN'; }
        $stmt = $pdo->prepare("SELECT ct.*, p.name AS student_name, p.identity_number AS nis 
                               FROM counseling_tickets ct 
                               JOIN core_people p ON ct.student_id = p.id 
                               WHERE ct.status = ?
                               ORDER BY ct.created_at DESC
                               LIMIT 100");
        $stmt->execute([$status]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $tickets]);
    }
    elseif ($action === 'list_tickets_recent') {
        ensureCounselingTicketsExists($pdo);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        if ($limit < 1 || $limit > 100) $limit = 20;
        $stmt = $pdo->query("SELECT ct.*, p.name AS student_name, p.identity_number AS nis 
                             FROM counseling_tickets ct 
                             JOIN core_people p ON ct.student_id = p.id 
                             ORDER BY ct.created_at DESC 
                             LIMIT " . $limit);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $tickets]);
    }
    elseif ($action === 'ticket_stats') {
        ensureCounselingTicketsExists($pdo);
        $counts = ['OPEN' => 0, 'IN_PROGRESS' => 0, 'CLOSED' => 0, 'REOPEN' => 0];
        $stmt = $pdo->query("SELECT status, COUNT(*) AS c FROM counseling_tickets GROUP BY status");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $s = strtoupper(trim($row['status'] ?? ''));
            $c = (int)($row['c'] ?? 0);
            if (isset($counts[$s])) $counts[$s] = $c;
        }
        $avgMin = 0;
        try {
            $stmt2 = $pdo->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) AS avg_min FROM counseling_tickets WHERE status = 'CLOSED'");
            $avgMin = (int)($stmt2->fetchColumn() ?? 0);
        } catch (Exception $e) { $avgMin = 0; }
        $recentClosed7 = 0;
        try {
            $stmt3 = $pdo->query("SELECT COUNT(*) FROM counseling_tickets WHERE status = 'CLOSED' AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $recentClosed7 = (int)($stmt3->fetchColumn() ?? 0);
        } catch (Exception $e) {}
        echo json_encode(['success' => true, 'data' => [
            'counts' => $counts,
            'avg_completion_minutes' => $avgMin,
            'avg_completion_hours' => round($avgMin / 60, 2),
            'recent_closed_7d' => $recentClosed7
        ]]);
    }
    elseif ($action === 'update_ticket_status') {
        ensureCounselingTicketsExists($pdo);
        ensureCounselingNotesExists($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $status = strtoupper(trim($input['status'] ?? ''));
        $note = trim($input['note'] ?? '');
        if (!$id || !in_array($status, ['OPEN','IN_PROGRESS','CLOSED','REOPEN'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']); exit;
        }
        $stmt = $pdo->prepare("UPDATE counseling_tickets SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        if ($note !== '') {
            $stn = $pdo->prepare("INSERT INTO counseling_ticket_notes (ticket_id, note, created_by) VALUES (?, ?, ?)");
            $stn->execute([$id, $note, $_SESSION['user_id'] ?? null]);
        }
        $stmt2 = $pdo->prepare("SELECT ct.*, p.name AS student_name, p.identity_number AS nis FROM counseling_tickets ct JOIN core_people p ON ct.student_id = p.id WHERE ct.id = ?");
        $stmt2->execute([$id]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        // Auto WA berdasarkan event status
        try {
            $event = null;
            if ($status === 'IN_PROGRESS') $event = 'FOLLOW_UP';
            elseif ($status === 'CLOSED') $event = 'CLOSED';
            elseif ($status === 'REOPEN') $event = 'REOPEN';
            if ($event) {
                $msg = "BK Ticket: " . ($row['title'] ?? '-') . " • Siswa: " . ($row['student_name'] ?? '-') . "\nStatus: " . ($status ?: '-') . "\nWaktu: " . date('Y-m-d H:i:s');
                $numbers = resolveRecipientsNumbers($pdo, 'COUNSELING', $event);
                if (count($numbers) > 0) {
                    $okCount = 0; $failCount = 0;
                    foreach ($numbers as $t) {
                        $res = sendWhatsAppMessage($pdo, $msg, $t);
                        $sc = is_array($res) ? !!$res['success'] : !!$res;
                        if ($sc) $okCount++; else $failCount++;
                    }
                    logActivity($pdo, 'COUNSELING', 'NOTIFY', 'WA_STATUS_'.$status, 'ticket', (string)$id, 'BK Ticket Status Update', 'OK=' . $okCount . ' FAIL=' . $failCount);
                }
            }
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'data' => $row]);
    }
    elseif ($action === 'save_wa_targets') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $targetsText = trim((string)($input['targets'] ?? ''));
        try {
            $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['wa_managerial_targets', $targetsText]);
            echo json_encode(['success' => true, 'message' => 'Daftar nomor WA tersimpan']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan daftar']);
        }
    }
    elseif ($action === 'send_wa_broadcast') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $msg = trim((string)($input['message'] ?? ''));
        $useLatest = !!($input['use_latest'] ?? false);
        $testing = !!($input['testing'] ?? false);
        $targetsOverride = $input['targets'] ?? null;
        if ($useLatest) {
            try {
                ensureCounselingTicketsExists($pdo);
                $s = $pdo->query("SELECT ct.id, ct.title, ct.status, ct.created_at, p.name AS student_name FROM counseling_tickets ct JOIN core_people p ON ct.student_id = p.id ORDER BY ct.created_at DESC, ct.id DESC LIMIT 1");
                $row = $s->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $msg = "BK Ticket: " . ($row['title'] ?? '-') . " • Siswa: " . ($row['student_name'] ?? '-') . "\nStatus: " . ($row['status'] ?? '-') . "\nWaktu: " . ($row['created_at'] ?? date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $e) {}
        }
        if ($msg === '') { echo json_encode(['success' => false, 'message' => 'Pesan kosong']); exit; }
        if ($testing && strpos($msg, '[TEST] ') !== 0) { $msg = '[TEST] ' . $msg; }
        $targets = parseManagerialTargets($pdo, $targetsOverride ?? null);
        if (!is_array($targets) || count($targets) === 0) { echo json_encode(['success' => false, 'message' => 'Daftar nomor kosong']); exit; }
        $okCount = 0; $failCount = 0;
        foreach ($targets as $t) {
            $res = sendWhatsAppMessage($pdo, $msg, $t);
            $sc = is_array($res) ? !!$res['success'] : !!$res;
            if ($sc) $okCount++; else $failCount++;
        }
        logActivity($pdo, 'EXECUTIVE', 'NOTIFY', 'WA_BROADCAST', 'COUNSELING', null, 'Broadcast WA BK', 'OK=' . $okCount . ' FAIL=' . $failCount);
        echo json_encode(['success' => ($okCount > 0), 'data' => ['ok' => $okCount, 'fail' => $failCount]]);
    }
    elseif ($action === 'send_wa_broadcast_employees') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = is_array($input['employee_ids'] ?? null) ? $input['employee_ids'] : [];
        $msg = trim((string)($input['message'] ?? ''));
        $useLatest = !!($input['use_latest'] ?? false);
        $testing = !!($input['testing'] ?? false);
        if ($useLatest && $msg === '') {
            try {
                ensureCounselingTicketsExists($pdo);
                $s = $pdo->query("SELECT ct.id, ct.title, ct.status, ct.created_at, p.name AS student_name FROM counseling_tickets ct JOIN core_people p ON ct.student_id = p.id ORDER BY ct.created_at DESC, ct.id DESC LIMIT 1");
                $row = $s->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $msg = "BK Ticket: " . ($row['title'] ?? '-') . " • Siswa: " . ($row['student_name'] ?? '-') . "\nStatus: " . ($row['status'] ?? '-') . "\nWaktu: " . ($row['created_at'] ?? date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $e) {}
        }
        if ($msg === '') { echo json_encode(['success' => false, 'message' => 'Pesan kosong']); exit; }
        if ($testing && strpos($msg, '[TEST] ') !== 0) { $msg = '[TEST] ' . $msg; }
        $ids = filterAllowedBkEmployees($pdo, $ids);
        if (!is_array($ids) || count($ids) === 0) { echo json_encode(['success' => false, 'message' => 'Daftar pegawai kosong atau tidak berhak']); exit; }
        $numbers = resolveEmployeeNumbers($pdo, $ids);
        if (count($numbers) === 0) { echo json_encode(['success' => false, 'message' => 'Nomor tidak ditemukan']); exit; }
        $okCount = 0; $failCount = 0; $errors = [];
        foreach ($numbers as $t) {
            $res = sendWhatsAppMessage($pdo, $msg, $t);
            $sc = is_array($res) ? !!$res['success'] : !!$res;
            if ($sc) { $okCount++; } else { $failCount++; $errors[] = is_array($res) ? ($res['error'] ?? 'unknown') : 'unknown'; }
        }
        logActivity($pdo, 'EXECUTIVE', 'NOTIFY', 'WA_BROADCAST_EMP', 'COUNSELING', implode(',', array_map('intval', $ids)), 'Broadcast WA BK pegawai', 'OK=' . $okCount . ' FAIL=' . $failCount);
        echo json_encode(['success' => ($okCount > 0), 'data' => ['ok' => $okCount, 'fail' => $failCount, 'errors' => $errors, 'targets' => count($numbers)]]);
    }
    elseif ($action === 'get_wa_recipients_map') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $map = loadWaRecipientsMap($pdo);
        echo json_encode(['success' => true, 'data' => $map]);
    }
    elseif ($action === 'save_wa_recipients_map') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $module = strtoupper(trim((string)($input['module'] ?? 'COUNSELING')));
        $event = strtoupper(trim((string)($input['event'] ?? '')));
        $ids = is_array($input['employee_ids'] ?? null) ? $input['employee_ids'] : [];
        $numbers = is_array($input['numbers'] ?? null) ? $input['numbers'] : [];
        if ($module === '' || (count($ids) === 0 && count($numbers) === 0)) { echo json_encode(['success' => false, 'message' => 'Param invalid']); exit; }
        $map = loadWaRecipientsMap($pdo);
        if ($event !== '') {
            if (!isset($map[$module]) || !is_array($map[$module])) $map[$module] = [];
            $map[$module][$event] = count($ids) > 0 ? array_values(array_unique(array_map('intval', $ids))) : array_values(array_filter(array_map('strval', $numbers)));
        } else {
            $map[$module] = count($ids) > 0 ? array_values(array_unique(array_map('intval', $ids))) : array_values(array_filter(array_map('strval', $numbers)));
        }
        $ok = saveWaRecipientsMap($pdo, $map);
        echo json_encode(['success' => ($ok ? true : false), 'data' => $map]);
    }
    elseif ($action === 'get_counseling_team') {
        $ids = computeCounselingTeamIds($pdo);
        echo json_encode(['success' => true, 'data' => $ids]);
    }
    elseif ($action === 'save_counseling_team') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = is_array($input['employee_ids'] ?? null) ? $input['employee_ids'] : [];
        $ids = array_values(array_unique(array_map('intval', $ids)));
        try {
            $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['counseling_team_employee_ids', json_encode($ids)]);
            echo json_encode(['success' => true, 'data' => $ids]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan tim BK']);
        }
    }
    elseif ($action === 'send_wa_broadcast_employees') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $module = strtoupper(trim((string)($input['module'] ?? 'COUNSELING')));
        $ids = is_array($input['employee_ids'] ?? null) ? $input['employee_ids'] : null;
        $msg = trim((string)($input['message'] ?? ''));
        $useLatest = !!($input['use_latest'] ?? false);
        if ($useLatest) {
            try {
                ensureCounselingTicketsExists($pdo);
                $s = $pdo->query("SELECT ct.id, ct.title, ct.status, ct.created_at, p.name AS student_name FROM counseling_tickets ct JOIN core_people p ON ct.student_id = p.id ORDER BY ct.created_at DESC, ct.id DESC LIMIT 1");
                $row = $s->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $msg = "BK Ticket: " . ($row['title'] ?? '-') . " • Siswa: " . ($row['student_name'] ?? '-') . "\nStatus: " . ($row['status'] ?? '-') . "\nWaktu: " . ($row['created_at'] ?? date('Y-m-d H:i:s'));
                }
            } catch (\Throwable $e) {}
        }
        if ($msg === '') { echo json_encode(['success' => false, 'message' => 'Pesan kosong']); exit; }
        $map = loadWaRecipientsMap($pdo);
        $empIds = is_array($ids) && count($ids) > 0 ? $ids : (is_array($map[$module] ?? null) ? $map[$module] : []);
        if (!is_array($empIds) || count($empIds) === 0) { echo json_encode(['success' => false, 'message' => 'Daftar penerima kosong']); exit; }
        $numbers = resolveEmployeeNumbers($pdo, $empIds);
        if (count($numbers) === 0) { echo json_encode(['success' => false, 'message' => 'Nomor tidak ditemukan']); exit; }
        $okCount = 0; $failCount = 0;
        foreach ($numbers as $t) {
            $res = sendWhatsAppMessage($pdo, $msg, $t);
            $sc = is_array($res) ? !!$res['success'] : !!$res;
            if ($sc) $okCount++; else $failCount++;
        }
        logActivity($pdo, 'EXECUTIVE', 'NOTIFY', 'WA_BROADCAST_EMP', $module, implode(',', $empIds), 'Broadcast WA BK pegawai', 'OK=' . $okCount . ' FAIL=' . $failCount);
        echo json_encode(['success' => ($okCount > 0), 'data' => ['ok' => $okCount, 'fail' => $failCount, 'targets' => count($numbers)]]);
    }
    elseif ($action === 'add_ticket_note') {
        ensureCounselingTicketsExists($pdo);
        ensureCounselingNotesExists($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $ticket_id = isset($input['ticket_id']) ? (int)$input['ticket_id'] : 0;
        $note = trim($input['note'] ?? '');
        if (!$ticket_id || $note === '') { echo json_encode(['success' => false, 'error' => 'Invalid input']); exit; }
        $stmt = $pdo->prepare("INSERT INTO counseling_ticket_notes (ticket_id, note, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $note, $_SESSION['user_id'] ?? null]);
        $stmt2 = $pdo->prepare("SELECT * FROM counseling_ticket_notes WHERE ticket_id = ? ORDER BY created_at DESC, id DESC");
        $stmt2->execute([$ticket_id]);
        echo json_encode(['success' => true, 'data' => $stmt2->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'list_ticket_notes') {
        ensureCounselingNotesExists($pdo);
        $ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
        if (!$ticket_id) { echo json_encode(['success' => true, 'data' => []]); exit; }
        $stmt = $pdo->prepare("SELECT n.*, u.username AS user_name 
                               FROM counseling_ticket_notes n 
                               LEFT JOIN core_users u ON n.created_by = u.id 
                               WHERE n.ticket_id = ? 
                               ORDER BY n.created_at DESC, n.id DESC");
        $stmt->execute([$ticket_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'get_unit_summary') {
        $unit_code = strtoupper(trim($_GET['unit'] ?? ''));
        if ($unit_code === '') {
            echo json_encode(['success' => true, 'data' => ['achievements_recent' => 0, 'cases_recent' => 0, 'counseling_sessions_recent' => 0]]);
            exit;
        }
        ensureAchievementsTableExists($pdo);
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $sql = "
            SELECT COUNT(sa.id) AS achievements_recent
            FROM student_achievements sa
            JOIN core_people p ON sa.student_id = p.id
            LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
            LEFT JOIN acad_classes c ON sc.class_id = c.id
            LEFT JOIN acad_class_levels l ON c.level_id = l.id
            LEFT JOIN core_units u ON l.unit_id = u.id
            WHERE u.code = ? AND (sa.date IS NULL OR sa.date >= ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$unit_code, $startDate]);
        $ach = (int)($stmt->fetchColumn() ?: 0);
        echo json_encode(['success' => true, 'data' => [
            'achievements_recent' => $ach,
            'cases_recent' => 0,
            'counseling_sessions_recent' => 0
        ]]);
    }

    elseif ($action === 'submit_incident') {
        $input = json_decode(file_get_contents('php://input'), true);
        $studentIds = $input['students'] ?? [];
        $category = $input['category'] ?? 'VIOLATION';
        $severity = $input['severity'] ?? 'LOW';
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $requestId = $input['request_id'] ?? null; // Should ideally be unique per student or batch

        if (empty($studentIds) || empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }

        $reporterId = $_SESSION['user_id'] ?? 0;
        $successCount = 0;
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO student_incidents (student_id, reporter_user_id, category, title, description, severity, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())");

            foreach ($studentIds as $sid) {
                $stmt->execute([$sid, $reporterId, $category, $title, $description, $severity]);
                $successCount++;
            }
            
            $pdo->commit();
            
            // Notify counseling team (optional/future)
            
            echo json_encode(['success' => true, 'message' => "$successCount laporan berhasil dikirim"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan laporan: ' . $e->getMessage()]);
        }
    }
    elseif ($action === 'get_student_full_profile') {
        $student_id = $_GET['id'] ?? 0;
        ensureAchievementsTableExists($pdo);
        
        // 1. Basic Info from core_people + Class + Homeroom
        $sql = "SELECT p.*, c.name as class_name, c.id as class_id, l.name as level_name, u.id as unit_id, u.name as unit_name,
                e.name as teacher_name
                FROM core_people p 
                LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
                LEFT JOIN acad_classes c ON sc.class_id = c.id
                LEFT JOIN acad_class_levels l ON c.level_id = l.id
                LEFT JOIN core_units u ON l.unit_id = u.id
                LEFT JOIN core_people e ON c.homeroom_teacher_id = e.id
                WHERE p.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        $basic = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$basic) {
            echo json_encode(['success' => false, 'error' => 'Student not found']);
            exit;
        }

        // 2. Extended Details (Try different table names)
        // Fixed: Removed 'address' from this query because it's not in acad_student_details
        $details = null;
        try {
            $stmt = $pdo->prepare("SELECT birth_place, birth_date, nisn, nik, father_name, mother_name, mobile_phone FROM acad_student_details WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        
        if ($details) {
            $details_fields = ['birth_place', 'birth_date', 'nisn', 'nik', 'father_name', 'mother_name', 'mobile_phone'];
            foreach ($details_fields as $f) {
                if (isset($details[$f]) && $details[$f] !== '' && $details[$f] !== null) {
                    $basic[$f] = $details[$f];
                }
            }
        }

        // 3. Class Schedule & Timeslots
        $schedule = [];
        $timeslots = [];
        if (!empty($basic['class_id'])) {
            try {
                // Get Timeslots for the unit
                $unit_id = $basic['unit_id'];
                $sql_ts = "SELECT * FROM acad_time_slots WHERE unit_id = ? ORDER BY start_time";
                $stmt_ts = $pdo->prepare($sql_ts);
                $stmt_ts->execute([$unit_id]);
                $timeslots_raw = $stmt_ts->fetchAll(PDO::FETCH_ASSOC);
                // Deduplicate timeslots by time range and break flag
                $seen_ts = [];
                $timeslots = [];
                foreach ($timeslots_raw as $ts) {
                    $start = substr($ts['start_time'], 0, 5);
                    $end = substr($ts['end_time'], 0, 5);
                    $is_break = isset($ts['is_break']) ? (int)$ts['is_break'] : 0;
                    $key = $start . '|' . $end . '|' . $is_break . '|' . ($ts['name'] ?? '');
                    if (!isset($seen_ts[$key])) {
                        $seen_ts[$key] = true;
                        // Ensure stable id even if DB contains duplicates
                        if (!isset($ts['id']) || $ts['id'] === null) {
                            $ts['id'] = $key;
                        }
                        $timeslots[] = $ts;
                    }
                }
                // Sort by start_time after dedup
                usort($timeslots, function($a, $b) {
                    return strcmp($a['start_time'], $b['start_time']);
                });

                // Get Schedule
                $sql = "SELECT s.*, sub.name as subject_name, p.name as teacher_name
                        FROM acad_schedules s
                        JOIN acad_subjects sub ON s.subject_id = sub.id
                        LEFT JOIN core_people p ON s.teacher_id = p.id
                        WHERE s.class_id = ?
                        ORDER BY FIELD(UPPER(day_name), 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'MINGGU'), start_time";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$basic['class_id']]);
                $schedule_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Deduplicate schedule entries for the same day/start_time
                $seen_sched = [];
                $schedule = [];
                foreach ($schedule_raw as $row) {
                    $day = strtoupper(trim($row['day_name'] ?? ''));
                    $time = substr($row['start_time'] ?? '', 0, 5);
                    $key = $day . '|' . $time;
                    if ($day === '' || $time === '') {
                        $schedule[] = $row;
                        continue;
                    }
                    if (!isset($seen_sched[$key])) {
                        $seen_sched[$key] = true;
                        $schedule[] = $row;
                    }
                }
            } catch (Exception $e) {}
        }

        // 4. Library Logs
        $library = [];
        try {
            $sql = "SELECT l.read_at, b.title as book_title, v.visit_date
                    FROM lib_reading_logs l
                    JOIN lib_books b ON l.book_id = b.id
                    JOIN lib_class_visits v ON l.visit_id = v.id
                    WHERE l.student_id = ?
                    ORDER BY l.read_at DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $library = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // 5. Boarding Info
        $boarding = null;
        try {
            $sql = "SELECT bs.*, p.name as musyrif_name, bh.name as halaqoh_actual_name
                    FROM boarding_students bs
                    LEFT JOIN core_people p ON bs.musyrif_id = p.id
                    LEFT JOIN boarding_halaqoh bh ON bs.halaqoh_id = bh.id
                    WHERE bs.student_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $boarding = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($boarding) {
                // Use halaqoh_actual_name if halaqoh_name is empty
                if (empty($boarding['halaqoh_name']) && !empty($boarding['halaqoh_actual_name'])) {
                    $boarding['halaqoh_name'] = $boarding['halaqoh_actual_name'];
                }

                // Fetch discipline records
                $sql_disc = "SELECT * FROM boarding_discipline_records WHERE student_id = ? ORDER BY record_date DESC";
                $stmt_disc = $pdo->prepare($sql_disc);
                $stmt_disc->execute([$student_id]);
                $boarding['discipline'] = $stmt_disc->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Counseling API Boarding Error: " . $e->getMessage());
        }

        // 6. Achievements
        $achievements = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM student_achievements WHERE student_id = ? ORDER BY date DESC, id DESC");
            $stmt->execute([$student_id]);
            $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        $cases = [];
        try {
            ensureCounselingTicketsExists($pdo);
            $sqlc = "
                SELECT 
                    si.id AS incident_id,
                    si.title,
                    si.category,
                    si.severity,
                    si.status AS incident_status,
                    si.created_at,
                    ct.id AS ticket_id,
                    ct.status AS ticket_status
                FROM student_incidents si
                LEFT JOIN counseling_tickets ct ON ct.incident_id = si.id
                WHERE si.student_id = ?
                ORDER BY si.created_at DESC
                LIMIT 100
            ";
            $stc = $pdo->prepare($sqlc);
            $stc->execute([$student_id]);
            $cases = $stc->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $cases = []; }

        echo json_encode([
            'success' => true,
            'data' => [
                'basic' => $basic,
                'schedule' => $schedule,
                'timeslots' => $timeslots,
                'days' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
                'library' => $library,
                'boarding' => $boarding,
                'cases' => $cases,
                'counseling' => [],
                'achievements' => $achievements
            ]
        ]);
    }
    elseif ($action === 'list_achievements') {
        ensureAchievementsTableExists($pdo);
        $student_id = $_GET['student_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM student_achievements WHERE student_id = ? ORDER BY date DESC, id DESC");
        $stmt->execute([$student_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'add_achievement') {
        ensureAchievementsTableExists($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $student_id = $input['student_id'] ?? 0;
        $title = trim($input['title'] ?? '');
        if (!$student_id || $title === '') {
            echo json_encode(['success' => false, 'message' => 'student_id dan title wajib']);
            exit;
        }
        $category = $input['category'] ?? null;
        $level = $input['level'] ?? null;
        $rank = $input['rank'] ?? null;
        $organizer = $input['organizer'] ?? null;
        $date = $input['date'] ?? null;
        $points = isset($input['points']) ? (int)$input['points'] : null;
        $stmt = $pdo->prepare("INSERT INTO student_achievements (student_id, title, category, level, rank, organizer, date, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $title, $category, $level, $rank, $organizer, $date, $points]);
        $id = (int)$pdo->lastInsertId();
        $stmt2 = $pdo->prepare("SELECT * FROM student_achievements WHERE id = ?");
        $stmt2->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt2->fetch(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'delete_achievement') {
        ensureAchievementsTableExists($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM student_achievements WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'update_achievement') {
        ensureAchievementsTableExists($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'id wajib']); exit; }
        $fields = ['title','category','level','rank','organizer','date','points'];
        $sets = [];
        $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $input)) {
                $sets[] = "$f = ?";
                $vals[] = $input[$f];
            }
        }
        if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan']); exit; }
        $vals[] = $id;
        $sql = "UPDATE student_achievements SET ".implode(', ', $sets)." WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        $stmt2 = $pdo->prepare("SELECT * FROM student_achievements WHERE id = ?");
        $stmt2->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt2->fetch(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'get_bk_unit_profile') {
        $profile = [];
        try {
            $raw = (string)getSetting($pdo, 'bk_unit_profile');
            $arr = $raw ? json_decode($raw, true) : [];
            if (is_array($arr)) $profile = $arr;
        } catch (\Throwable $e) {}
        $ids = computeCounselingTeamIds($pdo);
        $team = [];
        if (count($ids) > 0) {
            $place = implode(',', array_fill(0, count($ids), '?'));
            try {
                $st = $pdo->prepare("SELECT e.id AS employee_id, p.name, p.photo_url FROM hr_employees e JOIN core_people p ON e.person_id = p.id WHERE e.id IN ($place)");
                $st->execute(array_map('intval', $ids));
                $team = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}
        }
        echo json_encode(['success' => true, 'data' => ['profile' => $profile, 'team' => $team]]);
    }
    elseif ($action === 'list_psychoedu') {
        $items = [];
        try {
            $raw = (string)getSetting($pdo, 'bk_psychoedu_articles');
            $arr = $raw ? json_decode($raw, true) : [];
            if (is_array($arr)) $items = $arr;
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'data' => $items]);
    }
    elseif ($action === 'list_bk_schedule') {
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $items = [];
        try {
            $raw = (string)getSetting($pdo, 'bk_counseling_schedule');
            $arr = $raw ? json_decode($raw, true) : [];
            if (is_array($arr)) $items = $arr;
        } catch (\Throwable $e) {}
        $out = [];
        foreach ((array)$items as $it) {
            $cid = (int)($it['class_id'] ?? 0);
            if ($class_id > 0 && $cid > 0 && $cid !== $class_id) continue;
            $out[] = $it;
        }
        usort($out, function($a, $b) {
            $da = strtotime((string)($a['date'] ?? ''));
            $db = strtotime((string)($b['date'] ?? ''));
            if ($da === $db) return strcmp((string)($a['start'] ?? ''), (string)($b['start'] ?? ''));
            return $da <=> $db;
        });
        echo json_encode(['success' => true, 'data' => $out]);
    }
    elseif ($action === 'list_activity_logs') {
        ensureActivityTable($pdo);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        if ($limit < 1 || $limit > 100) $limit = 50;
        $sql = "SELECT al.*, u.username as user_name 
                FROM activity_logs al 
                LEFT JOIN core_users u ON al.user_id = u.id
                WHERE (al.module = 'COUNSELING') 
                   OR (al.module = 'EXECUTIVE' AND al.entity_type = 'COUNSELING')
                ORDER BY al.created_at DESC 
                LIMIT $limit";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
