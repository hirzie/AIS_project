<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false, 'error' => 'Not logged in']); exit; }
$action = $_GET['action'] ?? '';
function ensure_table(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_incidents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT DEFAULT NULL,
        reporter_user_id INT NOT NULL,
        category ENUM('VIOLATION','MEDICAL','OTHER') NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        severity ENUM('LOW','MEDIUM','HIGH') DEFAULT 'LOW',
        status ENUM('PENDING','HANDLED','ESCALATED') DEFAULT 'PENDING',
        request_id VARCHAR(100) UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student (student_id),
        INDEX idx_class (class_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}
function ensure_incident_extra_columns(PDO $pdo) {
    $cols = $pdo->query("DESCRIBE student_incidents")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('handled_note', $cols)) {
        $pdo->exec("ALTER TABLE student_incidents ADD COLUMN handled_note VARCHAR(500) DEFAULT NULL");
    }
    if (!in_array('handled_by', $cols)) {
        $pdo->exec("ALTER TABLE student_incidents ADD COLUMN handled_by INT DEFAULT NULL");
    }
    if (!in_array('handled_at', $cols)) {
        $pdo->exec("ALTER TABLE student_incidents ADD COLUMN handled_at DATETIME DEFAULT NULL");
    }
}
function ensure_counseling_extra_columns(PDO $pdo) {
    try {
        $cols = $pdo->query("DESCRIBE counseling_tickets")->fetchAll(PDO::FETCH_COLUMN);
        if ($cols && is_array($cols)) {
            if (!in_array('intro_note', $cols)) {
                $pdo->exec("ALTER TABLE counseling_tickets ADD COLUMN intro_note TEXT DEFAULT NULL");
            }
            if (!in_array('created_by', $cols)) {
                $pdo->exec("ALTER TABLE counseling_tickets ADD COLUMN created_by INT DEFAULT NULL");
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}
function getSetting(PDO $pdo, $key) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    } catch (Throwable $e) { return null; }
}
function sendWhatsAppMessage(PDO $pdo, $text, $targetOverride = null) {
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
function parseManagerialTargets(PDO $pdo, $override = null) {
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
function loadWaRecipientsMap(PDO $pdo) {
    try {
        $json = (string)getSetting($pdo, 'wa_recipients_map');
        $map = $json ? json_decode($json, true) : [];
        return is_array($map) ? $map : [];
    } catch (Throwable $e) { return []; }
}
function resolveEmployeeNumbers(PDO $pdo, $employeeIds) {
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
            try { $custom = json_decode($r['custom_attributes'] ?? '{}', true) ?: []; } catch (Throwable $e) { $custom = []; }
            $mobile = trim((string)($custom['mobile_phone'] ?? ''));
            $num = $mobile !== '' ? $mobile : $phone;
            if ($num !== '') $nums[] = $num;
        }
    } catch (Throwable $e) {}
    return $nums;
}
function ensureActivityTable(PDO $pdo) {
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
    } catch (Throwable $e) {}
}
function logActivity(PDO $pdo, $module, $category, $action, $entityType, $entityId, $title = null, $description = null) {
    ensureActivityTable($pdo);
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([$module, $category, $action, $entityType, $entityId ? (string)$entityId : null, $title, $description, $_SESSION['user_id'] ?? null]);
    } catch (Throwable $e) {}
}
function renderWaTemplate(PDO $pdo, $module, $event, $payload) {
    $tplsRaw = (string)getSetting($pdo, 'wa_templates');
    $tpl = '';
    if ($tplsRaw) {
        try {
            $tpls = json_decode($tplsRaw, true);
            if (is_array($tpls) && isset($tpls[$module]) && isset($tpls[$module][$event]) && is_string($tpls[$module][$event])) {
                $tpl = $tpls[$module][$event];
            }
        } catch (Throwable $e) {}
    }
    if ($tpl === '') {
        if (strtoupper($module) === 'COUNSELING' && strtoupper($event) === 'ESCALATION') {
            $tpl = "BK Ticket: {title} • Siswa: {student_name}\nStatus: {status}\nWaktu: {created_at}";
        }
    }
    $msg = (string)$tpl;
    foreach ((array)$payload as $k => $v) {
        $msg = str_replace('{'.$k.'}', (string)$v, $msg);
    }
    return $msg;
}
function resolveRecipientsNumbers(PDO $pdo, $module, $event) {
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
            $nums = resolveEmployeeNumbers($pdo, array_map('intval', $targets));
            return $nums;
        } else {
            $nums = [];
            foreach ($targets as $t) { $t = trim((string)$t); if ($t !== '') $nums[] = $t; }
            return $nums;
        }
    }
    return parseManagerialTargets($pdo, null);
}
try {
    if ($action === 'submit') {
        ensure_table($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $student_id = isset($input['student_id']) ? (int)$input['student_id'] : 0;
        $category = strtoupper(trim($input['category'] ?? ''));
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $severity = strtoupper(trim($input['severity'] ?? 'LOW'));
        $request_id = trim($input['request_id'] ?? '');
        if (!$student_id || $title === '' || $category === '' || $request_id === '') { echo json_encode(['success' => false, 'error' => 'Missing fields']); exit; }
        $stmtC = $pdo->prepare("SELECT sc.class_id FROM acad_student_classes sc WHERE sc.student_id = ? AND sc.status = 'ACTIVE' LIMIT 1");
        $stmtC->execute([$student_id]);
        $class_id = $stmtC->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO student_incidents (student_id, class_id, reporter_user_id, category, title, description, severity, status, request_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)");
        $stmt->execute([$student_id, $class_id, $_SESSION['user_id'], $category, $title, $description, $severity, $request_id]);
        $id = (int)$pdo->lastInsertId();
        
        // --- SEND WHATSAPP NOTIFICATION TO WALIKELAS ---
        try {
            // 1. Get Student Name & Homeroom Teacher
            $stSql = "SELECT p.name AS student_name, c.name AS class_name, hr.phone AS wali_phone, hr.custom_attributes, hr.name AS wali_name
                      FROM core_people p
                      JOIN acad_classes c ON c.id = ?
                      LEFT JOIN core_people hr ON c.homeroom_teacher_id = hr.id
                      WHERE p.id = ?";
            $stStmt = $pdo->prepare($stSql);
            $stStmt->execute([$class_id, $student_id]);
            $stData = $stStmt->fetch(PDO::FETCH_ASSOC);

            if ($stData) {
                $studentName = $stData['student_name'];
                $className = $stData['class_name'];
                $waliName = $stData['wali_name'];
                
                // Priority: Custom Attribute 'mobile_phone' > core_people.phone
                $waliPhone = '';
                if (!empty($stData['custom_attributes'])) {
                    $attrs = json_decode($stData['custom_attributes'], true);
                    if (is_array($attrs) && !empty($attrs['mobile_phone'])) {
                        $waliPhone = trim((string)$attrs['mobile_phone']);
                    }
                }
                if (empty($waliPhone) && !empty($stData['wali_phone'])) {
                    $waliPhone = trim((string)$stData['wali_phone']);
                }

                if (!empty($waliPhone)) {
                    // Format Message
                    $msg = "*LAPORAN KEJADIAN SISWA*\n\n";
                    $msg .= "Kepada Yth. Ustadz/Ustadzah *$waliName* (Wali Kelas $className)\n\n";
                    $msg .= "Diberitahukan bahwa siswa Anda:\n";
                    $msg .= "Nama: *$studentName*\n";
                    $msg .= "Kategori: *$category*\n";
                    $msg .= "Judul: *$title*\n";
                    $msg .= "Keparahan: *$severity*\n";
                    $msg .= "Keterangan: $description\n\n";
                    $msg .= "Mohon segera ditindaklanjuti. Terima kasih.";
    
                    // Send WA
                    sendWhatsAppMessage($pdo, $msg, $waliPhone);
                }
            }
        } catch (Exception $e) { /* Ignore WA errors */ }
        // ------------------------------------------------

        $stmt2 = $pdo->prepare("SELECT si.*, p.name AS student_name, p.identity_number AS nis FROM student_incidents si JOIN core_people p ON si.student_id = p.id WHERE si.id = ?");
        $stmt2->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt2->fetch(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'submit_batch') {
        ensure_table($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $student_ids = isset($input['student_ids']) && is_array($input['student_ids']) ? $input['student_ids'] : [];
        $category = strtoupper(trim($input['category'] ?? ''));
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $severity = strtoupper(trim($input['severity'] ?? 'LOW'));
        $base_request_id = trim($input['base_request_id'] ?? '');
        if (empty($student_ids) || $title === '' || $category === '') { echo json_encode(['success' => false, 'error' => 'Missing fields']); exit; }
        $result = [];
        $pdo->beginTransaction();
        try {
            foreach ($student_ids as $sidRaw) {
                $student_id = (int)$sidRaw;
                if ($student_id <= 0) { continue; }
                $stmtC = $pdo->prepare("SELECT sc.class_id FROM acad_student_classes sc WHERE sc.student_id = ? AND sc.status = 'ACTIVE' LIMIT 1");
                $stmtC->execute([$student_id]);
                $class_id = $stmtC->fetchColumn();
                $req = $base_request_id !== '' ? ($base_request_id . '-' . $student_id . '-' . substr(uniqid('', true), -6)) : ('INC-' . $student_id . '-' . substr(uniqid('', true), -8));
                $stmt = $pdo->prepare("INSERT INTO student_incidents (student_id, class_id, reporter_user_id, category, title, description, severity, status, request_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)");
                $stmt->execute([$student_id, $class_id, $_SESSION['user_id'], $category, $title, $description, $severity, $req]);
                $id = (int)$pdo->lastInsertId();
                $stmt2 = $pdo->prepare("SELECT si.*, p.name AS student_name, p.identity_number AS nis FROM student_incidents si JOIN core_people p ON si.student_id = p.id WHERE si.id = ?");
                $stmt2->execute([$id]);
                $result[] = $stmt2->fetch(PDO::FETCH_ASSOC);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'count' => count($result), 'data' => $result]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    elseif ($action === 'list_pending_for_homeroom') {
        ensure_table($pdo);
        $person_id = isset($_SESSION['person_id']) ? (int)$_SESSION['person_id'] : 0;
        if (!$person_id) { echo json_encode(['success' => true, 'data' => []]); exit; }
        $sql = "
            SELECT 
                si.*,
                p.name AS student_name, 
                p.identity_number AS nis,
                COALESCE(c.name, c2.name) AS class_name,
                COALESCE(si.class_id, sc.class_id) AS resolved_class_id
            FROM student_incidents si 
            JOIN core_people p ON si.student_id = p.id
            LEFT JOIN acad_classes c ON si.class_id = c.id
            LEFT JOIN acad_student_classes sc ON sc.student_id = si.student_id AND sc.status = 'ACTIVE'
            LEFT JOIN acad_classes c2 ON sc.class_id = c2.id
            WHERE si.status = 'PENDING'
              AND (
                    (c.homeroom_teacher_id = :pid) 
                 OR (c2.homeroom_teacher_id = :pid)
              )
            ORDER BY si.created_at DESC
            LIMIT 50
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $person_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }
    elseif ($action === 'escalate_to_bk') {
        ensure_table($pdo);
        ensure_incident_extra_columns($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $intro_note = trim($input['intro_note'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Missing incident id']); exit; }
        $stmt = $pdo->prepare("SELECT * FROM student_incidents WHERE id = ?");
        $stmt->execute([$id]);
        $inc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inc) { echo json_encode(['success' => false, 'error' => 'Incident not found']); exit; }
        // Update status to ESCALATED
        $stmtU = $pdo->prepare("UPDATE student_incidents SET status = 'ESCALATED' WHERE id = ?");
        $stmtU->execute([$id]);
        // Ensure BK tickets table
        $pdo->exec("CREATE TABLE IF NOT EXISTS counseling_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            incident_id INT UNIQUE,
            student_id INT NOT NULL,
            class_id INT DEFAULT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            severity ENUM('LOW','MEDIUM','HIGH') DEFAULT 'LOW',
            status ENUM('OPEN','IN_PROGRESS','CLOSED') DEFAULT 'OPEN',
            created_by INT DEFAULT NULL,
            intro_note TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_student (student_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        ensure_counseling_extra_columns($pdo);
        // Insert ticket if not exists
        try {
            $stmtT = $pdo->prepare("INSERT INTO counseling_tickets (incident_id, student_id, class_id, title, description, severity, status, created_by, intro_note) VALUES (?, ?, ?, ?, ?, ?, 'OPEN', ?, ?)");
            $stmtT->execute([$id, $inc['student_id'], $inc['class_id'], $inc['title'], $inc['description'], $inc['severity'], $_SESSION['user_id'], $intro_note]);
        } catch (Exception $e) {
            if ($intro_note !== '') {
                $stUp = $pdo->prepare("UPDATE counseling_tickets SET intro_note = ? WHERE incident_id = ?");
                $stUp->execute([$intro_note, $id]);
            }
        }
        // Return ticket info
        $stmtGet = $pdo->prepare("SELECT ct.*, p.name AS student_name FROM counseling_tickets ct JOIN core_people p ON ct.student_id = p.id WHERE ct.incident_id = ?");
        $stmtGet->execute([$id]);
        $ticket = $stmtGet->fetch(PDO::FETCH_ASSOC);
        if ($ticket) {
            $payload = [
                'title' => (string)($ticket['title'] ?? ''),
                'student_name' => (string)($ticket['student_name'] ?? ''),
                'status' => (string)($ticket['status'] ?? 'OPEN'),
                'created_at' => (string)($ticket['created_at'] ?? date('Y-m-d H:i:s'))
            ];
            $msg = renderWaTemplate($pdo, 'COUNSELING', 'ESCALATION', $payload);
            
            // 1. Notify Counseling Team (Existing Logic)
            $numbers = resolveRecipientsNumbers($pdo, 'COUNSELING', 'ESCALATION');
            
            // ALWAYS try to find employees with 'COUNSELING' or 'BK' division to ensure they receive it
            // (Merged with any configured recipients)
            try {
                 // Find employees via hr_employees join core_people
                 // Check: Division=BK/COUNSELING OR Teams has BK
                 $sqlBk = "
                     SELECT p.phone, p.custom_attributes 
                     FROM hr_employees e 
                     JOIN core_people p ON e.person_id = p.id
                 ";
                 $stmtBk = $pdo->query($sqlBk);
                 while ($row = $stmtBk->fetch(PDO::FETCH_ASSOC)) {
                     $ca = json_decode($row['custom_attributes'] ?? '{}', true);
                     $div = strtoupper(trim($ca['division'] ?? ''));
                     $teams = isset($ca['teams']) && is_array($ca['teams']) ? array_map('strtoupper', $ca['teams']) : [];
                     
                     $isBk = ($div === 'BK' || $div === 'COUNSELING' || in_array('BK', $teams) || in_array('COUNSELING', $teams));
                     
                     if ($isBk) {
                         $phone = !empty($ca['mobile_phone']) ? trim($ca['mobile_phone']) : trim($row['phone']);
                         if ($phone) $numbers[] = $phone;
                     }
                 }
            } catch (Exception $e) {}

            $okCount = 0; $failCount = 0;
            foreach (array_unique($numbers) as $num) {
                $res = sendWhatsAppMessage($pdo, $msg, $num);
                $sc = is_array($res) ? !!$res['success'] : !!$res;
                if ($sc) $okCount++; else $failCount++;
            }

            // 2. Notify Principal (Kepala Sekolah) of the Unit
            // Need to find unit_id from class_id
            $principalPhone = '';
            try {
                $stmtPrin = $pdo->prepare("
                    SELECT p.phone, p.custom_attributes 
                    FROM core_units u
                    JOIN acad_class_levels l ON u.id = l.unit_id
                    JOIN acad_classes c ON l.id = c.level_id
                    JOIN core_people p ON u.principal_id = p.id
                    WHERE c.id = ?
                ");
                $stmtPrin->execute([$inc['class_id']]);
                $prinData = $stmtPrin->fetch(PDO::FETCH_ASSOC);
                
                if ($prinData) {
                    // Check custom attributes first
                    if (!empty($prinData['custom_attributes'])) {
                        $attrs = json_decode($prinData['custom_attributes'], true);
                        if (is_array($attrs) && !empty($attrs['mobile_phone'])) {
                            $principalPhone = trim((string)$attrs['mobile_phone']);
                        }
                    }
                    // Fallback to core phone
                    if (empty($principalPhone) && !empty($prinData['phone'])) {
                        $principalPhone = trim((string)$prinData['phone']);
                    }
                    
                    if (!empty($principalPhone)) {
                        $prinMsg = "*ESKALASI KASUS BK (INFO KEPALA SEKOLAH)*\n\n" . $msg;
                        $resP = sendWhatsAppMessage($pdo, $prinMsg, $principalPhone);
                        if (is_array($resP) && $resP['success']) $okCount++;
                    }
                }
            } catch (Exception $e) {}

            logActivity($pdo, 'COUNSELING', 'NOTIFY', 'WA_ESCALATION', 'ticket', (string)($ticket['id'] ?? ''), 'BK Escalation', 'OK=' . $okCount . ' FAIL=' . $failCount);
        }
        echo json_encode(['success' => true, 'data' => $ticket]);
    }
    elseif ($action === 'resolve_internal') {
        ensure_table($pdo);
        ensure_incident_extra_columns($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $note = trim($input['note'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Missing incident id']); exit; }
        $stmt = $pdo->prepare("UPDATE student_incidents SET status = 'HANDLED', handled_by = ?, handled_note = ?, handled_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $note, $id]);
        $stmtGet = $pdo->prepare("SELECT * FROM student_incidents WHERE id = ?");
        $stmtGet->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmtGet->fetch(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'list_pending_for_class') {
        ensure_table($pdo);
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        if (!$class_id) { echo json_encode(['success' => true, 'data' => []]); exit; }
        $stmt = $pdo->prepare("SELECT si.*, p.name AS student_name, p.identity_number AS nis FROM student_incidents si JOIN core_people p ON si.student_id = p.id WHERE si.class_id = ? AND si.status = 'PENDING' ORDER BY si.created_at DESC");
        $stmt->execute([$class_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($action === 'update_status') {
        ensure_table($pdo);
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $status = strtoupper(trim($input['status'] ?? ''));
        if (!$id || !in_array($status, ['PENDING','HANDLED','ESCALATED'])) { echo json_encode(['success' => false, 'error' => 'Invalid status']); exit; }
        $stmt = $pdo->prepare("UPDATE student_incidents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'debug_status') {
        ensure_table($pdo);
        $out = [];
        $stmtSi = $pdo->query("SHOW TABLES LIKE 'student_incidents'");
        $out['student_incidents_exists'] = $stmtSi && $stmtSi->rowCount() > 0;
        $out['student_incidents_count'] = 0;
        if ($out['student_incidents_exists']) {
            $out['student_incidents_count'] = (int)$pdo->query("SELECT COUNT(*) FROM student_incidents")->fetchColumn();
            $st = $pdo->query("SELECT id, student_id, class_id, category, title, severity, status, created_at FROM student_incidents ORDER BY id DESC LIMIT 5");
            $out['student_incidents_latest'] = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        } else {
            $out['student_incidents_latest'] = [];
        }
        $stmtCt = $pdo->query("SHOW TABLES LIKE 'counseling_tickets'");
        $out['counseling_tickets_exists'] = $stmtCt && $stmtCt->rowCount() > 0;
        $out['counseling_tickets_count'] = $out['counseling_tickets_exists'] ? (int)$pdo->query("SELECT COUNT(*) FROM counseling_tickets")->fetchColumn() : 0;
        if ($out['counseling_tickets_exists']) {
            $st2 = $pdo->query("SELECT id, incident_id, student_id, class_id, title, severity, status, created_at FROM counseling_tickets ORDER BY id DESC LIMIT 5");
            $out['counseling_tickets_latest'] = $st2 ? $st2->fetchAll(PDO::FETCH_ASSOC) : [];
        } else {
            $out['counseling_tickets_latest'] = [];
        }
        $pid = isset($_SESSION['person_id']) ? (int)$_SESSION['person_id'] : 0;
        $out['session_person_id'] = $pid;
        $out['homeroom_classes'] = [];
        if ($pid) {
            $st3 = $pdo->prepare("SELECT id, name FROM acad_classes WHERE homeroom_teacher_id = ? ORDER BY id DESC LIMIT 5");
            $st3->execute([$pid]);
            $out['homeroom_classes'] = $st3->fetchAll(PDO::FETCH_ASSOC);
            $sql = "
                SELECT COUNT(*) 
                FROM student_incidents si
                LEFT JOIN acad_classes c ON si.class_id = c.id
                LEFT JOIN acad_student_classes sc ON sc.student_id = si.student_id AND sc.status = 'ACTIVE'
                LEFT JOIN acad_classes c2 ON sc.class_id = c2.id
                WHERE si.status = 'PENDING'
                  AND (
                        (c.homeroom_teacher_id = :pid) 
                     OR (c2.homeroom_teacher_id = :pid)
                  )
            ";
            $st4 = $pdo->prepare($sql);
            $st4->execute([':pid' => $pid]);
            $out['pending_for_homeroom_count'] = (int)$st4->fetchColumn();
        } else {
            $out['pending_for_homeroom_count'] = 0;
        }
        echo json_encode(['success' => true, 'data' => $out]);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
