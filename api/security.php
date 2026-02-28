<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function jsonResponse($success, $message = '', $data = []) {
    // if (ob_get_length()) ob_end_clean();
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

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
    $token = trim((string)getSetting($pdo, 'wa_api_token')); // Client ID: 'ais'
    $target = $targetOverride ?: trim((string)getSetting($pdo, 'wa_security_target'));
    
    if ($url === '' || $token === '' || $target === '' || $text === '') {
        return ['success' => false, 'error' => 'Konfigurasi tidak lengkap (URL, Token, Target)'];
    }
    
    // Auto-format nomor HP (0812... -> 62812...@c.us)
    $targetRaw = $target;
    $target = preg_replace('/[^0-9]/', '', $target);
    if (substr($target, 0, 1) === '0') {
        $target = '62' . substr($target, 1);
    }
    if (strlen($target) < 10) {
        return ['success' => false, 'error' => 'Nomor WA tidak valid/terlalu pendek: ' . $targetRaw];
    }
    if (strpos($target, '@') === false) {
        $target .= '@c.us';
    }

    // Payload sesuai standar CRM Hosting
    $payload = json_encode([
        'to' => $target, 
        'message' => $text, 
        'clientId' => $token 
    ], JSON_UNESCAPED_UNICODE);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 20, // Perpanjang timeout
            'ignore_errors' => true
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    
    try {
        $resp = @file_get_contents($url, false, $context);
        
        if ($resp === false) {
             $err = error_get_last();
             return ['success' => false, 'error' => 'Koneksi gagal: ' . ($err['message'] ?? 'Unknown error')];
        }

        $json = json_decode($resp, true);
        
        // Tangani jika respon bukan JSON (misal HTML Error dari server)
        if ($json === null) {
            return ['success' => false, 'error' => 'Respon server tidak valid (Bukan JSON). Cek URL API.'];
        }
        
        if (isset($json['success']) && $json['success']) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => $json['error'] ?? 'Gagal mengirim pesan'];

    } catch (\Throwable $e) { 
        return ['success' => false, 'error' => $e->getMessage()]; 
    }
}

function parseManagerialTargets($pdo, $override = null) {
    $raw = $override;
    if ($raw === null) {
        $raw = (string)getSetting($pdo, 'wa_managerial_targets');
    }
    $arr = [];
    if (is_array($raw)) {
        $arr = $raw;
    } else {
        $s = trim((string)$raw);
        if ($s !== '') {
            $s = str_replace(["\r\n", "\r"], "\n", $s);
            $parts = preg_split('/[,\n]+/', $s);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') $arr[] = $p;
            }
        }
    }
    return $arr;
}
function loadWaRecipientsMap($pdo) {
    try {
        $j = (string)getSetting($pdo, 'wa_recipients_map_security');
        $m = $j ? json_decode($j, true) : [];
        if (is_array($m) && count($m) > 0) {
            if (isset($m['SECURITY']) && is_array($m['SECURITY'])) return ['SECURITY' => $m['SECURITY']];
            if (array_values($m) === $m) return ['SECURITY' => $m];
            return $m;
        }
        $jl = (string)getSetting($pdo, 'wa_recipients_map');
        $ml = $jl ? json_decode($jl, true) : [];
        if (is_array($ml)) {
            if (isset($ml['SECURITY']) && is_array($ml['SECURITY'])) return ['SECURITY' => $ml['SECURITY']];
            if (array_values($ml) === $ml) return ['SECURITY' => $ml];
        }
        return [];
    } catch (\Throwable $e) { return []; }
}
function saveWaRecipientsMap($pdo, $map) {
    try {
        $d = [];
        if (isset($map['SECURITY']) && is_array($map['SECURITY'])) $d = ['SECURITY' => $map['SECURITY']];
        elseif (is_array($map)) $d = ['SECURITY' => $map];
        else $d = ['SECURITY' => []];
        $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['wa_recipients_map_security', json_encode($d)]);
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
    } catch (\Throwable $e) { /* ignore */ }
}

function logActivity($pdo, $module, $category, $action, $entityType, $entityId, $title = null, $description = null) {
    ensureActivityTable($pdo);
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([$module, $category, $action, $entityType, $entityId ? (string)$entityId : null, $title, $description, $_SESSION['user_id'] ?? null]);
    } catch (\Throwable $e) { /* ignore */ }
}

// Bootstrap tables if not exists (best-effort)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_shifts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        shift_name VARCHAR(100) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        days_json TEXT,
        window_minutes INT DEFAULT 60,
        default_template_id INT DEFAULT NULL,
        effective_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        // Ensure require_checklist column exists
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_shifts' AND COLUMN_NAME = 'require_checklist'");
            $chk->execute();
            if ((int)$chk->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE security_shifts ADD COLUMN require_checklist TINYINT(1) NOT NULL DEFAULT 1 AFTER days_json");
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Ensure window_minutes column exists
        try {
            $chk2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_shifts' AND COLUMN_NAME = 'window_minutes'");
            $chk2->execute();
            if ((int)$chk2->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE security_shifts ADD COLUMN window_minutes INT NOT NULL DEFAULT 60 AFTER days_json");
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Ensure default_template_id column exists
        try {
            $chk3 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_shifts' AND COLUMN_NAME = 'default_template_id'");
            $chk3->execute();
            if ((int)$chk3->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE security_shifts ADD COLUMN default_template_id INT DEFAULT NULL AFTER window_minutes");
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Ensure auto_send_on_window column exists
        try {
            $chk4 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_shifts' AND COLUMN_NAME = 'auto_send_on_window'");
            $chk4->execute();
            if ((int)$chk4->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE security_shifts ADD COLUMN auto_send_on_window TINYINT(1) NOT NULL DEFAULT 0 AFTER default_template_id");
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Ensure send_if_empty column exists
        try {
            $chk5 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_shifts' AND COLUMN_NAME = 'send_if_empty'");
            $chk5->execute();
            if ((int)$chk5->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE security_shifts ADD COLUMN send_if_empty TINYINT(1) NOT NULL DEFAULT 0 AFTER auto_send_on_window");
            }
        } catch (\Throwable $e) { /* ignore */ }

    $pdo->exec("CREATE TABLE IF NOT EXISTS security_areas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE,
        name VARCHAR(150) NOT NULL,
        building VARCHAR(150) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS security_checklist_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        items_json TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_checklist_runs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_id INT NOT NULL,
        location VARCHAR(150) DEFAULT NULL,
        officer_user_id INT DEFAULT NULL,
        request_id VARCHAR(64) DEFAULT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (template_id) REFERENCES security_checklist_templates(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // Ensure request_id column exists and unique index
    try {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_checklist_runs' AND COLUMN_NAME = 'request_id'");
        $chk->execute();
        if ((int)$chk->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE security_checklist_runs ADD COLUMN request_id VARCHAR(64) DEFAULT NULL AFTER officer_user_id");
        }
        $idx = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_checklist_runs' AND INDEX_NAME = 'uniq_request_id'");
        $idx->execute();
        if ((int)$idx->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE security_checklist_runs ADD UNIQUE INDEX uniq_request_id (request_id)");
        }
    } catch (\Throwable $e) { /* ignore */ }
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_checklist_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        run_id INT NOT NULL,
        item_label VARCHAR(200) NOT NULL,
        item_type VARCHAR(50) NOT NULL,
        value_json TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (run_id) REFERENCES security_checklist_runs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (\Throwable $e) { /* ignore */ }

try {
    if ($action === 'list_employees' && $method === 'GET') {
        $sql = "
            SELECT e.id, p.name, e.employee_number, e.employee_type, p.custom_attributes
            FROM hr_employees e
            JOIN core_people p ON e.person_id = p.id
            WHERE e.employment_status != 'RESIGNED'
            ORDER BY p.name ASC
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $custom = json_decode($r['custom_attributes'] ?? '{}', true);
            $division = $custom['division'] ?? null;
            $role = $r['employee_type'];
            $isSecurity = (strtoupper($role) === 'SECURITY') || (strtoupper((string)$division) === 'SECURITY');
            if ($isSecurity) {
                $out[] = [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'employee_number' => $r['employee_number'],
                    'employee_type' => $r['employee_type'],
                    'role' => 'SECURITY',
                    'division' => $division
                ];
            }
        }
        jsonResponse(true, '', $out);
    }
    elseif ($action === 'list_shifts' && $method === 'GET') {
        $sql = "
            SELECT s.*, p.name as employee_name, e.employee_number
            FROM security_shifts s
            LEFT JOIN hr_employees e ON s.employee_id = e.id
            LEFT JOIN core_people p ON e.person_id = p.id
            ORDER BY s.created_at DESC
        ";
        $stmt = $pdo->query($sql);
        jsonResponse(true, '', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($action === 'save_shift' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $employee_id = (int)($input['employee_id'] ?? 0);
        $shift_name = trim($input['shift_name'] ?? '');
        $start_time = $input['start_time'] ?? '';
        $end_time = $input['end_time'] ?? '';
        $days = $input['days'] ?? [];
        $effective_date = $input['effective_date'] ?? null;
        $require_checklist = isset($input['require_checklist']) ? (int)((bool)$input['require_checklist']) : 1;
        $window_minutes = isset($input['window_minutes']) ? (int)$input['window_minutes'] : 60;
        if ($window_minutes <= 0) $window_minutes = 60;
        if ($window_minutes > 240) $window_minutes = 240;
        $default_template_id = isset($input['default_template_id']) ? (int)$input['default_template_id'] : null;
        $auto_send_on_window = isset($input['auto_send_on_window']) ? (int)((bool)$input['auto_send_on_window']) : 0;
        $send_if_empty = isset($input['send_if_empty']) ? (int)((bool)$input['send_if_empty']) : 0;
        if (!$employee_id || !$shift_name || !$start_time || !$end_time) {
            jsonResponse(false, 'Data wajib diisi');
        }
        $days_json = json_encode(array_values($days));
        if ($id) {
            $stmt = $pdo->prepare("UPDATE security_shifts SET employee_id=?, shift_name=?, start_time=?, end_time=?, days_json=?, require_checklist=?, window_minutes=?, default_template_id=?, auto_send_on_window=?, send_if_empty=?, effective_date=? WHERE id=?");
            $stmt->execute([$employee_id, $shift_name, $start_time, $end_time, $days_json, $require_checklist, $window_minutes, $default_template_id, $auto_send_on_window, $send_if_empty, $effective_date, $id]);
            jsonResponse(true, 'Shift diperbarui');
        } else {
            $stmt = $pdo->prepare("INSERT INTO security_shifts (employee_id, shift_name, start_time, end_time, days_json, require_checklist, window_minutes, default_template_id, auto_send_on_window, send_if_empty, effective_date) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$employee_id, $shift_name, $start_time, $end_time, $days_json, $require_checklist, $window_minutes, $default_template_id, $auto_send_on_window, $send_if_empty, $effective_date]);
            jsonResponse(true, 'Shift disimpan');
        }
    }
    elseif ($action === 'delete_shift' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, 'ID wajib');
        $stmt = $pdo->prepare("DELETE FROM security_shifts WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'Shift dihapus');
    }
    elseif ($action === 'list_areas' && $method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM security_areas ORDER BY building, name ASC");
        jsonResponse(true, '', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($action === 'save_area' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $code = trim($input['code'] ?? '');
        $name = trim($input['name'] ?? '');
        $building = trim($input['building'] ?? '');
        if (!$name) jsonResponse(false, 'Nama area wajib');
        if ($id) {
            $stmt = $pdo->prepare("UPDATE security_areas SET code=?, name=?, building=? WHERE id=?");
            $stmt->execute([$code ?: null, $name, $building ?: null, $id]);
            jsonResponse(true, 'Area diperbarui');
        } else {
            if ($code) {
                $check = $pdo->prepare("SELECT id FROM security_areas WHERE code = ?");
                $check->execute([$code]);
                if ($check->fetch()) jsonResponse(false, 'Kode area sudah ada');
            }
            $stmt = $pdo->prepare("INSERT INTO security_areas (code, name, building) VALUES (?,?,?)");
            $stmt->execute([$code ?: null, $name, $building ?: null]);
            jsonResponse(true, 'Area disimpan');
        }
    }
    elseif ($action === 'list_checklist_templates' && $method === 'GET') {
        $stmt = $pdo->query("SELECT id, name, items_json, created_at FROM security_checklist_templates ORDER BY name ASC");
        jsonResponse(true, '', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($action === 'save_checklist_template' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $items = $input['items'] ?? [];
        if (!$name || !is_array($items) || count($items) === 0) {
            jsonResponse(false, 'Nama dan item wajib');
        }
        $items_json = json_encode(array_values($items));
        if ($id) {
            $stmt = $pdo->prepare("UPDATE security_checklist_templates SET name=?, items_json=? WHERE id=?");
            $stmt->execute([$name, $items_json, $id]);
            jsonResponse(true, 'Template diperbarui');
        } else {
            $stmt = $pdo->prepare("INSERT INTO security_checklist_templates (name, items_json) VALUES (?, ?)");
            $stmt->execute([$name, $items_json]);
            jsonResponse(true, 'Template disimpan');
        }
    }
    elseif ($action === 'zero_report_overview' && $method === 'GET') {
        $today = new DateTime('now');
        $todayDate = $today->format('Y-m-d');
        $dowEn = strtolower($today->format('l')); // monday..sunday
        $dowEnShort = substr($dowEn, 0, 3); // mon..sun
        $indoMap = ['monday'=>'senin','tuesday'=>'selasa','wednesday'=>'rabu','thursday'=>'kamis','friday'=>'jumat','saturday'=>'sabtu','sunday'=>'minggu'];
        $dowId = $indoMap[$dowEn] ?? $dowEn;
        $norms = [strtolower($dowEn), strtolower($dowEnShort), strtolower($dowId)];
        // Get today's shifts (approx: filter by days_json matching today)
        $rows = [];
        try {
            $stmt = $pdo->query("
                SELECT s.*, e.id AS employee_id, p.id AS person_id, p.name AS employee_name, u.id AS user_id, u.username
                FROM security_shifts s
                LEFT JOIN hr_employees e ON s.employee_id = e.id
                LEFT JOIN core_people p ON e.person_id = p.id
                LEFT JOIN core_users u ON p.id = u.people_id
                WHERE COALESCE(s.require_checklist, 1) = 1
                ORDER BY s.end_time ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $rows = [];
        }
        $dailyShifts = [];
        foreach ($rows as $r) {
            $days = [];
            try { $days = json_decode($r['days_json'] ?? '[]', true) ?: []; } catch (\Throwable $e) { $days = []; }
            $daysNorm = array_map(function($x){ return strtolower(trim((string)$x)); }, (array)$days);
            $isToday = false;
            if (empty($daysNorm)) { $isToday = true; }
            else {
                foreach ($daysNorm as $d) {
                    $dShort = substr($d,0,3);
                    if (in_array($d, $norms) || in_array($dShort, $norms) || $d === 'all') { $isToday = true; break; }
                }
            }
            if (!$isToday) {
                $st = trim($r['start_time'] ?? '');
                $et = trim($r['end_time'] ?? '');
                $stp = explode(':', $st); $etp = explode(':', $et);
                $stm = (int)($stp[0] ?? 0)*60 + (int)($stp[1] ?? 0);
                $etm = (int)($etp[0] ?? 0)*60 + (int)($etp[1] ?? 0);
                $cross = ($st !== '' && $et !== '' && $etm < $stm);
                if ($cross) {
                    $y = new DateTime($todayDate);
                    $y->modify('-1 day');
                    $ydEn = strtolower($y->format('l'));
                    $ydShort = substr($ydEn,0,3);
                    $ydId = $indoMap[$ydEn] ?? $ydEn;
                    $yNorms = [strtolower($ydEn), strtolower($ydShort), strtolower($ydId)];
                    foreach ($daysNorm as $d) {
                        $dShort = substr($d,0,3);
                        if (in_array($d, $yNorms) || in_array($dShort, $yNorms) || $d === 'all') { $isToday = true; break; }
                    }
                }
            }
            if (!$isToday) continue;
            $endTime = trim($r['end_time'] ?? '');
            if ($endTime === '') continue;
            $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $todayDate . ' ' . $endTime);
            if (!$endDt) {
                $endDt = DateTime::createFromFormat('Y-m-d H:i', $todayDate . ' ' . $endTime);
            }
            if (!$endDt) continue;
            $winMinDay = (int)($r['window_minutes'] ?? 60);
            if ($winMinDay <= 0) $winMinDay = 60;
            if ($winMinDay > 240) $winMinDay = 240;
            $startDt = clone $endDt;
            $startDt->modify("-$winMinDay minutes");
            // Check checklist runs in window (prefer officer match, fallback any SECURITY user)
            $hasRun = false;
            $latestRun = null;
            $userId = (int)($r['user_id'] ?? 0);
            $personId = (int)($r['person_id'] ?? 0);
            if ($personId > 0 && isset($_SESSION['person_id']) && (int)$_SESSION['person_id'] === $personId) {
                $userId = (int)($_SESSION['user_id'] ?? $userId);
            }
            // Compute UTC equivalents to handle servers storing UTC
            $startUtc = clone $startDt; $startUtc->setTimezone(new DateTimeZone('UTC'));
            $endUtc = clone $endDt; $endUtc->setTimezone(new DateTimeZone('UTC'));
            if ($userId > 0) {
                // Local timezone window
                $stmtC = $pdo->prepare("SELECT COUNT(*) FROM security_checklist_runs WHERE officer_user_id = ? AND created_at BETWEEN ? AND ?");
                $stmtC->execute([$userId, $startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
                $hasRun = ((int)$stmtC->fetchColumn()) > 0;
                if ($hasRun) {
                    $st = $pdo->prepare("SELECT id, template_id, location, notes, officer_user_id, created_at FROM security_checklist_runs WHERE officer_user_id = ? AND created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 1");
                    $st->execute([$userId, $startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
                    $latestRun = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                }
                // UTC window fallback
                if (!$hasRun) {
                    $stmtCu = $pdo->prepare("SELECT COUNT(*) FROM security_checklist_runs WHERE officer_user_id = ? AND created_at BETWEEN ? AND ?");
                    $stmtCu->execute([$userId, $startUtc->format('Y-m-d H:i:s'), $endUtc->format('Y-m-d H:i:s')]);
                    $hasRun = ((int)$stmtCu->fetchColumn()) > 0;
                    if ($hasRun && !$latestRun) {
                        $st = $pdo->prepare("SELECT id, template_id, location, notes, officer_user_id, created_at FROM security_checklist_runs WHERE officer_user_id = ? AND created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 1");
                        $st->execute([$userId, $startUtc->format('Y-m-d H:i:s'), $endUtc->format('Y-m-d H:i:s')]);
                        $latestRun = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                }
            }
            if (!$hasRun) {
                // Any SECURITY user within local window
                $stmtC2 = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM security_checklist_runs r 
                    LEFT JOIN core_users u ON r.officer_user_id = u.id
                    LEFT JOIN core_people p ON u.people_id = p.id
                    WHERE r.created_at BETWEEN ? AND ?
                ");
                $stmtC2->execute([$startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
                $hasRun = ((int)$stmtC2->fetchColumn()) > 0;
                if ($hasRun && !$latestRun) {
                    $st = $pdo->prepare("
                        SELECT r.id, r.template_id, r.location, r.notes, r.officer_user_id, r.created_at 
                        FROM security_checklist_runs r
                        WHERE r.created_at BETWEEN ? AND ?
                        ORDER BY r.created_at DESC LIMIT 1
                    ");
                    $st->execute([$startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
                    $latestRun = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$hasRun) {
                // Any SECURITY user within UTC window
                $stmtC3 = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM security_checklist_runs r 
                    LEFT JOIN core_users u ON r.officer_user_id = u.id
                    LEFT JOIN core_people p ON u.people_id = p.id
                    WHERE r.created_at BETWEEN ? AND ?
                ");
                $stmtC3->execute([$startUtc->format('Y-m-d H:i:s'), $endUtc->format('Y-m-d H:i:s')]);
                $hasRun = ((int)$stmtC3->fetchColumn()) > 0;
                if ($hasRun && !$latestRun) {
                    $st = $pdo->prepare("
                        SELECT r.id, r.template_id, r.location, r.notes, r.officer_user_id, r.created_at 
                        FROM security_checklist_runs r
                        WHERE r.created_at BETWEEN ? AND ?
                        ORDER BY r.created_at DESC LIMIT 1
                    ");
                    $st->execute([$startUtc->format('Y-m-d H:i:s'), $endUtc->format('Y-m-d H:i:s')]);
                    $latestRun = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            $isActive = ($today >= $startDt && $today <= $endDt);
            $dailyShifts[] = [
                'shift_id' => (int)($r['id'] ?? 0),
                'employee_name' => $r['employee_name'] ?? null,
                'user_id' => $userId ?: null,
                'person_id' => $personId ?: null,
                'end_time' => $endTime,
                'window_start' => $startDt->format('H:i'),
                'window_end' => $endDt->format('H:i'),
                'window_minutes' => $winMinDay,
                'default_template_id' => isset($r['default_template_id']) ? (int)$r['default_template_id'] : null,
                'status' => $hasRun ? 'REPORTED' : 'NOT_REPORTED',
                'is_active' => $isActive,
                'latest_run_id' => $latestRun ? (int)$latestRun['id'] : null,
                'latest_template_id' => $latestRun ? (int)$latestRun['template_id'] : null,
                'latest_location' => $latestRun ? ($latestRun['location'] ?? null) : null,
                'latest_notes' => $latestRun ? ($latestRun['notes'] ?? null) : null,
                'latest_officer_user_id' => $latestRun ? (isset($latestRun['officer_user_id']) ? (int)$latestRun['officer_user_id'] : null) : null
            ];
        }
        $reported = count(array_filter($dailyShifts, function($x){ return $x['status'] === 'REPORTED'; }));
        $notReported = count($dailyShifts) - $reported;
        // Weekly meeting compliance: require >=1 meeting in current week
        $weekStart = new DateTime($todayDate);
        // Set to Monday start
        $dayIdx = (int)$today->format('N'); // 1..7, Mon=1
        $weekStart->modify('-' . ($dayIdx - 1) . ' days');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        try {
            $stmtW = $pdo->prepare("SELECT COUNT(*) FROM mgr_meetings WHERE (module_tag = 'SECURITY' OR FIND_IN_SET('SECURITY', modules)) AND meeting_date BETWEEN ? AND ?");
            $stmtW->execute([$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
            $meetCnt = (int)$stmtW->fetchColumn();
        } catch (\Throwable $e) { $meetCnt = 0; }
        $weekly = [
            'required_meetings' => 1,
            'meetings_done' => $meetCnt,
            'compliance' => $meetCnt >= 1,
            'period_start' => $weekStart->format('Y-m-d'),
            'period_end' => $weekEnd->format('Y-m-d')
        ];
        // Monthly target: count expected checklist windows based on shifts (require_checklist=1)
        // and measure actual runs within those windows for the current month.
        $monthStart = new DateTime($today->format('Y-m-01'));
        $monthEnd = new DateTime($today->format('Y-m-01'));
        $monthEnd->modify('last day of this month');
        $daysMap = [
            1 => ['senin','mon'],
            2 => ['selasa','tue'],
            3 => ['rabu','wed'],
            4 => ['kamis','thu'],
            5 => ['jumat','fri'],
            6 => ['sabtu','sat'],
            7 => ['minggu','sun'],
        ];
        $expectedCount = 0;
        $metCount = 0;
        // Preload SECURITY officer criterion when user_id missing: division/role filter
        $countRunsStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM security_checklist_runs 
            WHERE officer_user_id = ? AND created_at BETWEEN ? AND ?
        ");
        $countRunsAnyStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM security_checklist_runs r
            LEFT JOIN core_users u ON r.officer_user_id = u.id
            LEFT JOIN core_people p ON u.people_id = p.id
            WHERE r.created_at BETWEEN ? AND ?
        ");
        // Load mandatory shifts for monthly evaluation with user_id if available
        $rowsMonthly = [];
        try {
            $stmtM = $pdo->query("
                SELECT s.*, e.id AS employee_id, p.name AS employee_name, u.id AS user_id
                FROM security_shifts s
                LEFT JOIN hr_employees e ON s.employee_id = e.id
                LEFT JOIN core_people p ON e.person_id = p.id
                LEFT JOIN core_users u ON p.id = u.people_id
                WHERE COALESCE(s.require_checklist, 1) = 1
            ");
            $rowsMonthly = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { $rowsMonthly = []; }
        // Iterate dates of current month
        $cursor = clone $monthStart;
        while ($cursor <= $monthEnd) {
            $dowN = (int)$cursor->format('N'); // 1..7
            $dateStr = $cursor->format('Y-m-d');
            foreach ($rowsMonthly as $r) {
                $days = [];
                try { $days = json_decode($r['days_json'] ?? '[]', true) ?: []; } catch (\Throwable $e) { $days = []; }
                $daysNorm = array_map(function($x){ return strtolower(trim((string)$x)); }, (array)$days);
                $match = false;
                if (empty($daysNorm)) {
                    $match = true;
                } else {
                    $aliases = $daysMap[$dowN] ?? [];
                    foreach ($daysNorm as $d) {
                        $dShort = substr($d, 0, 3);
                        if (in_array($d, $aliases, true) || in_array($dShort, $aliases, true) || $d === 'all') { $match = true; break; }
                    }
                }
                if (!$match) {
                    $st = trim($r['start_time'] ?? '');
                    $et = trim($r['end_time'] ?? '');
                    $stp = explode(':', $st); $etp = explode(':', $et);
                    $stm = (int)($stp[0] ?? 0)*60 + (int)($stp[1] ?? 0);
                    $etm = (int)($etp[0] ?? 0)*60 + (int)($etp[1] ?? 0);
                    $cross = ($st !== '' && $et !== '' && $etm < $stm);
                    if ($cross) {
                        $yDow = $dowN - 1;
                        if ($yDow < 1) $yDow = 7;
                        $yalias = $daysMap[$yDow] ?? [];
                        foreach ($daysNorm as $d) {
                            $dShort = substr($d, 0, 3);
                            if (in_array($d, $yalias, true) || in_array($dShort, $yalias, true) || $d === 'all') { $match = true; break; }
                        }
                    }
                }
                if (!$match) continue;
                $effStr = trim($r['effective_date'] ?? '');
                if ($effStr !== '') {
                    $eff = DateTime::createFromFormat('Y-m-d', $effStr);
                    if (!$eff) { $eff = DateTime::createFromFormat('d/m/Y', $effStr); }
                    if ($eff && $cursor < $eff) continue;
                }
                $endTime = trim($r['end_time'] ?? '');
                if ($endTime === '') continue;
                $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $endTime);
                if (!$endDt) { $endDt = DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $endTime); }
                if (!$endDt) continue;
                $winMin = (int)($r['window_minutes'] ?? 60);
                if ($winMin <= 0) $winMin = 60;
                if ($winMin > 240) $winMin = 240;
                $startDt = clone $endDt;
                $startDt->modify("-$winMin minutes");
                $expectedCount++;
                $userIdLocal = (int)($r['user_id'] ?? 0);
                $hasRun = false;
                if ($userIdLocal > 0) {
                    $countRunsStmt->execute([$userIdLocal, $startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
                    $hasRun = ((int)$countRunsStmt->fetchColumn()) > 0;
                } else {
                    $countRunsAnyStmt->execute([$startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
                    $hasRun = ((int)$countRunsAnyStmt->fetchColumn()) > 0;
                }
                if ($hasRun) $metCount++;
            }
            $cursor->modify('+1 day');
        }
        $rate = ($expectedCount > 0) ? round(($metCount / $expectedCount) * 100) : 0;
        // Monthly window: last 5 days of the month
        $windowEnd = clone $monthEnd;
        $windowStart = clone $monthEnd;
        $windowStart->modify('-4 days');
        $isWindowActive = ($today >= $windowStart && $today <= $windowEnd);
        $monthly = [
            'month' => $monthStart->format('Y-m'),
            'required_checklists' => $expectedCount,
            'checklists_done' => $metCount,
            'compliance_rate_percent' => $rate,
            'window_start' => $windowStart->format('Y-m-d'),
            'window_end' => $windowEnd->format('Y-m-d'),
            'window_active' => $isWindowActive
        ];
        $serverNowHm = null;
        try {
            $qNow = $pdo->query("SELECT DATE_FORMAT(NOW(), '%H:%i') AS hm");
            $serverNowHm = $qNow->fetchColumn();
        } catch (\Throwable $e) { $serverNowHm = (new DateTime('now'))->format('H:i'); }
        jsonResponse(true, '', [
            'daily' => [
                'today' => $todayDate,
                'server_now' => $serverNowHm,
                'window_rule_minutes' => 60,
                'total_shifts' => count($dailyShifts),
                'reported' => $reported,
                'not_reported' => $notReported,
                'shifts' => $dailyShifts
            ],
            'weekly' => $weekly,
            'monthly' => $monthly
        ]);
    }
    elseif ($action === 'start_checklist_run' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $template_id = (int)($input['template_id'] ?? 0);
        $location = trim($input['location'] ?? '');
        $notes = trim($input['notes'] ?? '');
        $reqId = isset($input['request_id']) ? trim((string)$input['request_id']) : '';
        if (!$template_id) jsonResponse(false, 'Template wajib');
        // Idempotency: if request_id provided and exists, reuse
        if ($reqId !== '') {
            $sCheck = $pdo->prepare("SELECT id FROM security_checklist_runs WHERE request_id = ?");
            $sCheck->execute([$reqId]);
            $foundId = (int)($sCheck->fetchColumn() ?: 0);
            if ($foundId > 0) {
                $run_id = $foundId;
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO security_checklist_runs (template_id, location, officer_user_id, notes, request_id) VALUES (?,?,?,?,?)");
                    $stmt->execute([$template_id, $location ?: null, $_SESSION['user_id'] ?? null, $notes ?: null, $reqId]);
                    $run_id = (int)$pdo->lastInsertId();
                } catch (\PDOException $e) {
                    // Duplicate request_id
                    $sCheck2 = $pdo->prepare("SELECT id FROM security_checklist_runs WHERE request_id = ?");
                    $sCheck2->execute([$reqId]);
                    $run_id = (int)($sCheck2->fetchColumn() ?: 0);
                }
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO security_checklist_runs (template_id, location, officer_user_id, notes) VALUES (?,?,?,?)");
            $stmt->execute([$template_id, $location ?: null, $_SESSION['user_id'] ?? null, $notes ?: null]);
            $run_id = (int)$pdo->lastInsertId();
        }
        // Log activity: start run
        try {
            $tplName = null;
            $sTpl = $pdo->prepare("SELECT name FROM security_checklist_templates WHERE id = ?");
            $sTpl->execute([$template_id]);
            $tplName = $sTpl->fetchColumn();
            $title = $tplName ? ('Mulai Checklist: ' . $tplName) : 'Mulai Checklist';
            $desc = $location ? ('Lokasi: ' . $location) : null;
            logActivity($pdo, 'SECURITY', 'CHECKLIST', 'START', 'security_checklist_runs', $run_id, $title, $desc);
        } catch (\Throwable $e) { /* ignore */ }
        jsonResponse(true, '', ['run_id' => $run_id]);
    }
    elseif ($action === 'save_checklist_result' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $run_id = (int)($input['run_id'] ?? 0);
        $reqId = isset($input['request_id']) ? trim((string)$input['request_id']) : '';
        $answers = $input['answers'] ?? [];
        if (!is_array($answers)) $answers = [];
        // If run_id not provided, create new run now (save-on-close semantics)
        if ($run_id <= 0) {
            $template_id = (int)($input['template_id'] ?? 0);
            $location = trim($input['location'] ?? '');
            $notes = trim($input['notes'] ?? '');
            if ($template_id <= 0) jsonResponse(false, 'Template wajib');
            if ($reqId !== '') {
                $sCheck = $pdo->prepare("SELECT id FROM security_checklist_runs WHERE request_id = ?");
                $sCheck->execute([$reqId]);
                $foundId = (int)($sCheck->fetchColumn() ?: 0);
                if ($foundId > 0) {
                    $run_id = $foundId;
                } else {
                    try {
                        $stmtRun = $pdo->prepare("INSERT INTO security_checklist_runs (template_id, location, officer_user_id, notes, request_id) VALUES (?,?,?,?,?)");
                        $stmtRun->execute([$template_id, $location ?: null, $_SESSION['user_id'] ?? null, $notes ?: null, $reqId]);
                        $run_id = (int)$pdo->lastInsertId();
                    } catch (\PDOException $e) {
                        $sCheck2 = $pdo->prepare("SELECT id FROM security_checklist_runs WHERE request_id = ?");
                        $sCheck2->execute([$reqId]);
                        $run_id = (int)($sCheck2->fetchColumn() ?: 0);
                    }
                }
            } else {
                $stmtRun = $pdo->prepare("INSERT INTO security_checklist_runs (template_id, location, officer_user_id, notes) VALUES (?,?,?,?)");
                $stmtRun->execute([$template_id, $location ?: null, $_SESSION['user_id'] ?? null, $notes ?: null]);
                $run_id = (int)$pdo->lastInsertId();
            }
            // Log activity: start run (implicit)
            try {
                $tplName = null;
                $sTpl = $pdo->prepare("SELECT name FROM security_checklist_templates WHERE id = ?");
                $sTpl->execute([$template_id]);
                $tplName = $sTpl->fetchColumn();
                $title = $tplName ? ('Mulai Checklist: ' . $tplName) : 'Mulai Checklist';
                $desc = $location ? ('Lokasi: ' . $location) : null;
                logActivity($pdo, 'SECURITY', 'CHECKLIST', 'START', 'security_checklist_runs', $run_id, $title, $desc);
            } catch (\Throwable $e) { /* ignore */ }
        }
        $pdo->beginTransaction();
        try {
            $stmtDel = $pdo->prepare("DELETE FROM security_checklist_answers WHERE run_id = ?");
            $stmtDel->execute([$run_id]);
            $stmtIns = $pdo->prepare("INSERT INTO security_checklist_answers (run_id, item_label, item_type, value_json) VALUES (?,?,?,?)");
            foreach ($answers as $ans) {
                $label = trim($ans['label'] ?? '');
                $type = strtoupper(trim($ans['type'] ?? 'BOOLEAN'));
                $val = json_encode($ans['value'] ?? null);
                if ($label) $stmtIns->execute([$run_id, $label, $type, $val]);
            }
            $pdo->commit();
            // Log activity: save result
            try {
                $tplName = null;
                $s = $pdo->prepare("SELECT t.name FROM security_checklist_runs r JOIN security_checklist_templates t ON r.template_id = t.id WHERE r.id = ?");
                $s->execute([$run_id]);
                $tplName = $s->fetchColumn();
                $title = $tplName ? ('Checklist disimpan: ' . $tplName) : 'Checklist disimpan';
                $desc = 'Jumlah jawaban: ' . count($answers);
                logActivity($pdo, 'SECURITY', 'CHECKLIST', 'SAVE', 'security_checklist_runs', $run_id, $title, $desc);
            try {
                $row = null;
                $sr = $pdo->prepare("SELECT r.location, r.notes, r.created_at, t.name AS template_name, p.name AS officer_name
                    FROM security_checklist_runs r
                    JOIN security_checklist_templates t ON r.template_id = t.id
                    LEFT JOIN core_users u ON r.officer_user_id = u.id
                    LEFT JOIN core_people p ON u.people_id = p.id
                    WHERE r.id = ?");
                $sr->execute([$run_id]);
                $row = $sr->fetch(PDO::FETCH_ASSOC);
                $loc = $row['location'] ?? '';
                $note = $row['notes'] ?? '';
                $tm = $row['created_at'] ?? date('Y-m-d H:i:s');
                $tpl = $row['template_name'] ?? '';
                $off = $row['officer_name'] ?? '';
                $msg = "Security: " . ($tpl ?: 'Checklist') . " • " . ($off ?: 'Petugas') . "\nWaktu: " . $tm . "\nLokasi: " . ($loc ?: '-') . "\nCatatan: " . ($note ?: '-');
                $ok = sendWhatsAppMessage($pdo, $msg, null);
                if ($ok) {
                    logActivity($pdo, 'SECURITY', 'NOTIFY', 'WA', (string)$run_id, 'Kirim WA berhasil', $tpl);
                } else {
                    logActivity($pdo, 'SECURITY', 'NOTIFY', 'WA', (string)$run_id, 'Kirim WA gagal', $tpl);
                }
            } catch (\Throwable $e) { /* ignore */ }
            } catch (\Throwable $e) { /* ignore */ }
            jsonResponse(true, 'Checklist disimpan', ['run_id' => $run_id]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal menyimpan');
        }
    }
    elseif ($action === 'list_checklist_runs_month' && $method === 'GET') {
        $month = trim($_GET['month'] ?? '');
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            jsonResponse(false, 'Param bulan invalid (YYYY-MM)');
        }
        $start = $month . '-01';
        try {
            $dt = new DateTime($start);
            $dtEnd = clone $dt;
            $dtEnd->modify('last day of this month');
            $end = $dtEnd->format('Y-m-d') . ' 23:59:59';
        } catch (\Throwable $e) {
            jsonResponse(false, 'Bulan invalid');
        }
        $sql = "
            SELECT r.id, r.template_id, r.location, r.officer_user_id, r.notes, r.created_at,
                   t.name AS template_name,
                   u.username, p.name AS officer_name, p.custom_attributes AS people_custom_attributes, u.role AS officer_role
            FROM security_checklist_runs r
            JOIN security_checklist_templates t ON r.template_id = t.id
            LEFT JOIN core_users u ON r.officer_user_id = u.id
            LEFT JOIN core_people p ON u.people_id = p.id
            WHERE r.created_at >= ? AND r.created_at <= ?
        ";
        $params = [$start, $end];
        if ($userId) {
            $sql .= " AND r.officer_user_id = ?";
            $params[] = $userId;
        }
        $sql .= " ORDER BY r.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Add timezone-adjusted timestamp (+07:00) hint for clients (assume DB may be UTC)
        foreach ($rowsAll as &$row) {
            try {
                $row['created_at_plus7'] = date('Y-m-d H:i:s', strtotime($row['created_at'].' +7 hours'));
            } catch (\Throwable $e) {
                $row['created_at_plus7'] = $row['created_at'];
            }
        }
        $rows = $rowsAll;
        if (!$userId) {
            $filtered = [];
            foreach ($rowsAll as $r) {
                $custom = json_decode($r['people_custom_attributes'] ?? '{}', true);
                $division = strtoupper((string)($custom['division'] ?? ''));
                $role = strtoupper((string)($r['officer_role'] ?? ''));
                if ($division === 'SECURITY' || $role === 'SECURITY') {
                    $filtered[] = $r;
                }
            }
            // Fallback: if no Security runs found, show all runs (helpful when Security users belum diset)
            $rows = (count($filtered) > 0) ? $filtered : $rowsAll;
        }
        jsonResponse(true, '', $rows);
    }
    elseif ($action === 'get_checklist_answers' && $method === 'GET') {
        $runId = (int)($_GET['run_id'] ?? 0);
        if (!$runId) jsonResponse(false, 'Run ID invalid');
        $stmt = $pdo->prepare("SELECT item_label, item_type, value_json, created_at FROM security_checklist_answers WHERE run_id = ? ORDER BY id ASC");
        $stmt->execute([$runId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, '', $rows);
    }
    elseif ($action === 'server_clock' && $method === 'GET') {
        $phpTz = date_default_timezone_get();
        $nowPhp = date('Y-m-d H:i:s');
        $nowDb = null; $utcDb = null; $tzGlobal = null; $tzSession = null; $diffMin = null;
        try {
            $st = $pdo->query("SELECT NOW() AS now_db, UTC_TIMESTAMP() AS utc_db, @@global.time_zone AS tz_global, @@session.time_zone AS tz_session");
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $nowDb = $row['now_db'] ?? null;
            $utcDb = $row['utc_db'] ?? null;
            $tzGlobal = $row['tz_global'] ?? null;
            $tzSession = $row['tz_session'] ?? null;
            if ($nowDb && $utcDb) {
                $diffMin = (int)round((strtotime($nowDb) - strtotime($utcDb)) / 60);
            }
        } catch (\Throwable $e) {}
        jsonResponse(true, '', [
            'php_timezone' => $phpTz,
            'php_now' => $nowPhp,
            'db_now' => $nowDb,
            'db_utc' => $utcDb,
            'db_timezone_global' => $tzGlobal,
            'db_timezone_session' => $tzSession,
            'diff_from_utc_minutes' => $diffMin
        ]);
    }
    elseif ($action === 'send_wa_test' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $msg = trim($input['message'] ?? '');
        $target = isset($input['target']) ? trim((string)$input['target']) : null;
                // ... code sebelumnya ...
        if ($msg === '') jsonResponse(false, 'Pesan kosong');
        
        $result = sendWhatsAppMessage($pdo, $msg, $target ?: null);
        
        // Support format lama (bool) dan baru (array)
        if (is_array($result)) {
            jsonResponse($result['success'], $result['success'] ? 'Terkirim' : ($result['error'] ?? 'Gagal mengirim'));
        } else {
            jsonResponse($result ? true : false, $result ? 'Terkirim' : 'Gagal mengirim');
        }
    }
    elseif ($action === 'save_wa_targets' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $targetsText = trim((string)($input['targets'] ?? ''));
        try {
            $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['wa_managerial_targets', $targetsText]);
            jsonResponse(true, 'Daftar nomor WA manajerial tersimpan');
        } catch (\Throwable $e) {
            jsonResponse(false, 'Gagal menyimpan daftar: ' . $e->getMessage());
        }
    }
    elseif ($action === 'send_wa_broadcast' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $msg = trim((string)($input['message'] ?? ''));
        $useLatest = !!($input['use_latest'] ?? false);
        $testing = !!($input['testing'] ?? false);
        $targetsOverride = $input['targets'] ?? null;
        if ($useLatest) {
            try {
                $s = $pdo->query("SELECT r.id, r.location, r.notes, r.created_at, t.name AS template_name, p.name AS officer_name
                                  FROM security_checklist_runs r
                                  JOIN security_checklist_templates t ON r.template_id = t.id
                                  LEFT JOIN core_users u ON r.officer_user_id = u.id
                                  LEFT JOIN core_people p ON u.people_id = p.id
                                  ORDER BY r.created_at DESC, r.id DESC
                                  LIMIT 1");
                $row = $s->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $loc = $row['location'] ?? '';
                    $note = $row['notes'] ?? '';
                    $tm = $row['created_at'] ?? date('Y-m-d H:i:s');
                    $tpl = $row['template_name'] ?? '';
                    $off = $row['officer_name'] ?? '';
                    $msg = "Security: " . ($tpl ?: 'Checklist') . " • " . ($off ?: 'Petugas') . "\nWaktu: " . $tm . "\nLokasi: " . ($loc ?: '-') . "\nCatatan: " . ($note ?: '-');
                }
            } catch (\Throwable $e) { /* ignore and fallback to provided msg */ }
        }
        if ($msg === '') jsonResponse(false, 'Pesan kosong');
        if ($testing && strpos($msg, '[TEST] ') !== 0) { $msg = '[TEST] ' . $msg; }
        $targets = parseManagerialTargets($pdo, $targetsOverride ?? null);
        if (!is_array($targets) || count($targets) === 0) {
            jsonResponse(false, 'Daftar nomor kosong');
        }
        $okCount = 0; $failCount = 0; $errors = [];
        foreach ($targets as $t) {
            $res = sendWhatsAppMessage($pdo, $msg, $t);
            $sc = is_array($res) ? !!$res['success'] : !!$res;
            if ($sc) { $okCount++; } else { $failCount++; $errors[] = is_array($res) ? ($res['error'] ?? 'unknown') : 'unknown'; }
        }
        logActivity($pdo, 'EXECUTIVE', 'NOTIFY', 'WA_BROADCAST', 'managerial_targets', null, 'Broadcast WA', 'OK=' . $okCount . ' FAIL=' . $failCount);
        jsonResponse(($okCount > 0), ($okCount > 0 ? 'Broadcast terkirim' : 'Broadcast gagal'), ['ok' => $okCount, 'fail' => $failCount, 'errors' => $errors]);
    }
    elseif ($action === 'get_wa_recipients_map' && $method === 'GET') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $map = loadWaRecipientsMap($pdo);
        jsonResponse(true, 'OK', $map);
    }
    elseif ($action === 'save_wa_recipients_map' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $module = strtoupper(trim((string)($input['module'] ?? '')));
        $event = strtoupper(trim((string)($input['event'] ?? '')));
        $ids = is_array($input['employee_ids'] ?? null) ? $input['employee_ids'] : [];
        $numbers = is_array($input['numbers'] ?? null) ? $input['numbers'] : [];
        if ($module === '' || (count($ids) === 0 && count($numbers) === 0)) jsonResponse(false, 'Param invalid');
        $map = loadWaRecipientsMap($pdo);
        if ($event !== '') {
            if (!isset($map[$module]) || !is_array($map[$module])) $map[$module] = [];
            $map[$module][$event] = count($ids) > 0 ? array_values(array_unique(array_map('intval', $ids))) : array_values(array_filter(array_map('strval', $numbers)));
        } else {
            $map[$module] = count($ids) > 0 ? array_values(array_unique(array_map('intval', $ids))) : array_values(array_filter(array_map('strval', $numbers)));
        }
        $ok = saveWaRecipientsMap($pdo, $map);
        jsonResponse($ok ? true : false, $ok ? 'Tersimpan' : 'Gagal simpan', $map);
    }
    elseif ($action === 'log_filter_selection' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = is_array($input['employee_ids'] ?? null) ? array_values(array_unique(array_map('intval', $input['employee_ids']))) : [];
        $names = is_array($input['names'] ?? null) ? array_values(array_filter(array_map('strval', $input['names']))) : [];
        $desc = 'IDs=' . implode(',', $ids);
        if (count($names) > 0) { $desc .= ' NAMES=' . implode('; ', $names); }
        logActivity($pdo, 'SECURITY', 'CONFIG', 'FILTER_LIST', 'security_filter', null, 'Security filter list', $desc);
        jsonResponse(true, 'Logged', ['count' => count($ids)]);
    }
    elseif ($action === 'send_wa_broadcast_employees' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $module = strtoupper(trim((string)($input['module'] ?? 'SECURITY')));
        $ids = is_array($input['employee_ids'] ?? null) ? $input['employee_ids'] : null;
        $msg = trim((string)($input['message'] ?? ''));
        $useLatest = !!($input['use_latest'] ?? false);
        $testing = !!($input['testing'] ?? false);
        if ($useLatest) {
            try {
                $s = $pdo->query("SELECT r.id, r.location, r.notes, r.created_at, t.name AS template_name, p.name AS officer_name
                                  FROM security_checklist_runs r
                                  JOIN security_checklist_templates t ON r.template_id = t.id
                                  LEFT JOIN core_users u ON r.officer_user_id = u.id
                                  LEFT JOIN core_people p ON u.people_id = p.id
                                  ORDER BY r.created_at DESC, r.id DESC
                                  LIMIT 1");
                $row = $s->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $loc = $row['location'] ?? '';
                    $note = $row['notes'] ?? '';
                    $tm = $row['created_at'] ?? date('Y-m-d H:i:s');
                    $tpl = $row['template_name'] ?? '';
                    $off = $row['officer_name'] ?? '';
                    $msg = "Security: " . ($tpl ?: 'Checklist') . " • " . ($off ?: 'Petugas') . "\nWaktu: " . $tm . "\nLokasi: " . ($loc ?: '-') . "\nCatatan: " . ($note ?: '-');
                }
            } catch (\Throwable $e) {}
        }
        if ($msg === '') jsonResponse(false, 'Pesan kosong');
        if ($testing && strpos($msg, '[TEST] ') !== 0) { $msg = '[TEST] ' . $msg; }
        $map = loadWaRecipientsMap($pdo);
        $empIds = is_array($ids) && count($ids) > 0 ? $ids : (is_array($map[$module] ?? null) ? $map[$module] : []);
        if (!is_array($empIds) || count($empIds) === 0) jsonResponse(false, 'Daftar penerima kosong');
        $numbers = resolveEmployeeNumbers($pdo, $empIds);
        if (count($numbers) === 0) jsonResponse(false, 'Nomor tidak ditemukan');
        $okCount = 0; $failCount = 0; $errors = [];
        foreach ($numbers as $t) {
            $res = sendWhatsAppMessage($pdo, $msg, $t);
            $sc = is_array($res) ? !!$res['success'] : !!$res;
            if ($sc) { $okCount++; } else { $failCount++; $errors[] = is_array($res) ? ($res['error'] ?? 'unknown') : 'unknown'; }
        }
        logActivity($pdo, 'EXECUTIVE', 'NOTIFY', 'WA_BROADCAST_EMP', $module, implode(',', $empIds), 'Broadcast WA pegawai', 'OK=' . $okCount . ' FAIL=' . $failCount);
        jsonResponse(($okCount > 0), ($okCount > 0 ? 'Broadcast terkirim' : 'Broadcast gagal'), ['ok' => $okCount, 'fail' => $failCount, 'errors' => $errors, 'targets' => count($numbers)]);
    }
    elseif ($action === 'auto_send_security' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $now = new DateTime();
        $dateStr = $now->format('Y-m-d');
        $dow = (int)$now->format('N');
        $stmt = $pdo->query("SELECT id, employee_id, shift_name, start_time, end_time, days_json, window_minutes, require_checklist, auto_send_on_window, send_if_empty FROM security_shifts");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = loadWaRecipientsMap($pdo);
        $empIds = is_array($map['SECURITY'] ?? null) ? $map['SECURITY'] : [];
        $numbers = resolveEmployeeNumbers($pdo, $empIds);
        $okTotal = 0; $failTotal = 0; $sent = [];
        foreach ($rows as $r) {
            // DEBUG
            $logPrefix = "Shift {$r['id']}: ";
            if ((int)($r['require_checklist'] ?? 1) !== 1) continue;
            if ((int)($r['auto_send_on_window'] ?? 0) !== 1) continue;
            $days = [];
            try { $days = json_decode($r['days_json'] ?? '[]', true) ?: []; } catch (\Throwable $e) { $days = []; }
            $hasDay = false;
            $dayMap = [
                'SENIN' => 1, 'SELASA' => 2, 'RABU' => 3, 'KAMIS' => 4, 'JUMAT' => 5, 'SABTU' => 6, 'MINGGU' => 7,
                'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6, 'SUN' => 7,
                'MONDAY' => 1, 'TUESDAY' => 2, 'WEDNESDAY' => 3, 'THURSDAY' => 4, 'FRIDAY' => 5, 'SATURDAY' => 6, 'SUNDAY' => 7
            ];
            foreach ($days as $d) {
                $v = null;
                if (is_numeric($d)) {
                    $v = (int)$d;
                } else {
                    $upper = strtoupper(trim((string)$d));
                    if (isset($dayMap[$upper])) $v = $dayMap[$upper];
                }
                // echo "Day $d -> $v vs Dow $dow | ";
                if ($v && $v === $dow) { $hasDay = true; break; }
            }
            if (!$hasDay) { file_put_contents(__DIR__ . '/debug_auto_send.log', $logPrefix . "No matching day (Need $dow, Has " . json_encode($days) . ")\n", FILE_APPEND); continue; }
            $endTime = trim((string)$r['end_time'] ?? '');
            if ($endTime === '') continue;
            $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $endTime);
            if (!$endDt) { $endDt = DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $endTime); }
            if (!$endDt) continue;
            $diffSec = $now->getTimestamp() - $endDt->getTimestamp();
            if ($diffSec < 0 || $diffSec > 600) continue;
            $winMin = (int)($r['window_minutes'] ?? 60);
            if ($winMin <= 0) $winMin = 60;
            if ($winMin > 240) $winMin = 240;
            $startDt = clone $endDt;
            $startDt->modify("-$winMin minutes");
            $sr = $pdo->prepare("SELECT r.id, r.location, r.notes, r.created_at, t.name AS template_name, p.name AS officer_name
                                 FROM security_checklist_runs r
                                 JOIN security_checklist_templates t ON r.template_id = t.id
                                 LEFT JOIN core_users u ON r.officer_user_id = u.id
                                 LEFT JOIN core_people p ON u.people_id = p.id
                                 WHERE r.created_at BETWEEN ? AND ?
                                 ORDER BY r.created_at DESC, r.id DESC
                                 LIMIT 1");
            $sr->execute([$startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]);
            $row = $sr->fetch(PDO::FETCH_ASSOC);
            $msg = '';
            if ($row) {
                $loc = $row['location'] ?? '';
                $note = $row['notes'] ?? '';
                $tm = $row['created_at'] ?? $endDt->format('Y-m-d H:i:s');
                $tpl = $row['template_name'] ?? '';
                $off = $row['officer_name'] ?? '';
                $msg = "Security: " . ($tpl ?: 'Checklist') . " • " . ($off ?: 'Petugas') . "\nWaktu: " . $tm . "\nLokasi: " . ($loc ?: '-') . "\nCatatan: " . ($note ?: '-');
            } else {
                if ((int)($r['send_if_empty'] ?? 0) !== 1) {
                    continue;
                }
                $msg = "Security: Window selesai " . $endDt->format('Y-m-d H:i') . " • Tidak ada checklist dalam window";
            }
            $entityId = (string)((int)$r['id']) . '|' . $dateStr . '|' . $endTime;
            $chk = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE module='SECURITY' AND category='NOTIFY' AND action='WA_WINDOW' AND entity_type='security_shift' AND entity_id = ?");
            $chk->execute([$entityId]);
            $already = ((int)$chk->fetchColumn()) > 0;
            if ($already) continue;
            
            if (count($numbers) === 0) {
                // Log jika tidak ada penerima
                logActivity($pdo, 'SECURITY', 'NOTIFY', 'WA_WINDOW_FAIL', 'security_shift', $entityId, 'WA Window Gagal', 'Tidak ada nomor penerima valid (Param Invalid)');
                $failTotal++;
                continue;
            }

            $okCount = 0; $failCount = 0;
            $errors = [];
            foreach ($numbers as $t) {
                $res = sendWhatsAppMessage($pdo, $msg, $t);
                $sc = is_array($res) ? !!$res['success'] : !!$res;
                if ($sc) { 
                    $okCount++; 
                } else { 
                    $failCount++;
                    $err = is_array($res) ? ($res['error'] ?? 'unknown') : 'unknown';
                    $errors[] = $t . ': ' . $err;
                }
            }
            $okTotal += $okCount; $failTotal += $failCount;
            $sent[] = ['shift_id' => (int)$r['id'], 'ok' => $okCount, 'fail' => $failCount, 'window' => [$startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')]];
            
            $desc = 'OK=' . $okCount . ' FAIL=' . $failCount;
            if (count($errors) > 0) {
                $desc .= ' | ERR: ' . implode(', ', array_slice($errors, 0, 3));
            }
            logActivity($pdo, 'SECURITY', 'NOTIFY', 'WA_WINDOW', 'security_shift', $entityId, 'WA Window', $desc);
        }
        jsonResponse(true, 'Auto send selesai', ['ok' => $okTotal, 'fail' => $failTotal, 'sent' => $sent]);
    }
    elseif ($action === 'send_daily_summary' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $today = date('Y-m-d');
        $stmt = $pdo->query("SELECT id, shift_name FROM security_shifts");
        $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = loadWaRecipientsMap($pdo);
        $empIds = [];
        if (isset($map['SECURITY']['DAILY_SUMMARY']) && is_array($map['SECURITY']['DAILY_SUMMARY'])) {
            $empIds = $map['SECURITY']['DAILY_SUMMARY'];
        } elseif (is_array($map['SECURITY'] ?? null)) {
            $empIds = $map['SECURITY'];
        }
        $numbers = resolveEmployeeNumbers($pdo, $empIds);
        $okTotal = 0; $failTotal = 0; $sent = [];
        foreach ($shifts as $s) {
            $sid = (int)($s['id'] ?? 0);
            if ($sid <= 0) continue;
            $chk = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE module='SECURITY' AND category='NOTIFY' AND action='WA_DAILY_SUMMARY' AND entity_type='security_daily_shift' AND entity_id = ?");
            $chk->execute([(string)$sid . '|' . $today]);
            if ((int)$chk->fetchColumn() > 0) continue;
            $sr = $pdo->prepare("SELECT r.location, r.notes, r.created_at, t.name AS template_name, p.name AS officer_name
                                 FROM security_checklist_runs r
                                 JOIN security_checklist_templates t ON r.template_id = t.id
                                 LEFT JOIN core_users u ON r.officer_user_id = u.id
                                 LEFT JOIN core_people p ON u.people_id = p.id
                                 WHERE DATE(r.created_at) = ?
                                 ORDER BY r.created_at DESC, r.id DESC
                                 LIMIT 1");
            $sr->execute([$today]);
            $row = $sr->fetch(PDO::FETCH_ASSOC);
            $msg = '';
            if ($row) {
                $loc = $row['location'] ?? '';
                $note = $row['notes'] ?? '';
                $tm = $row['created_at'] ?? ($today . ' 20:00:00');
                $tpl = $row['template_name'] ?? '';
                $off = $row['officer_name'] ?? '';
                $msg = "Security: " . ($tpl ?: 'Checklist') . " • " . ($off ?: 'Petugas') . "\nWaktu: " . $tm . "\nLokasi: " . ($loc ?: '-') . "\nCatatan: " . ($note ?: '-');
            } else {
                $msg = "Security: Ringkasan harian " . $today . " • Tidak ada checklist";
            }
            if ($msg === '' || count($numbers) === 0) continue;
            $okCount = 0; $failCount = 0;
            foreach ($numbers as $t) {
                $res = sendWhatsAppMessage($pdo, $msg, $t);
                $sc = is_array($res) ? !!$res['success'] : !!$res;
                if ($sc) { $okCount++; } else { $failCount++; }
            }
            $okTotal += $okCount; $failTotal += $failCount;
            $sent[] = ['shift_id' => $sid, 'ok' => $okCount, 'fail' => $failCount];
            logActivity($pdo, 'SECURITY', 'NOTIFY', 'WA_DAILY_SUMMARY', 'security_daily_shift', (string)$sid . '|' . $today, 'WA Daily Summary', 'OK=' . $okCount . ' FAIL=' . $failCount);
        }
        jsonResponse(true, 'Daily summary selesai', ['ok' => $okTotal, 'fail' => $failTotal, 'sent' => $sent]);
    }
    elseif ($action === 'send_wa_run_report' && $method === 'POST') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $runId = (int)($input['run_id'] ?? 0);
        $targetsOverride = $input['targets'] ?? null;
        if ($runId <= 0) jsonResponse(false, 'Run ID invalid');
        try {
            $sr = $pdo->prepare("SELECT r.location, r.notes, r.created_at, t.name AS template_name, p.name AS officer_name
                                 FROM security_checklist_runs r
                                 JOIN security_checklist_templates t ON r.template_id = t.id
                                 LEFT JOIN core_users u ON r.officer_user_id = u.id
                                 LEFT JOIN core_people p ON u.people_id = p.id
                                 WHERE r.id = ?");
            $sr->execute([$runId]);
            $row = $sr->fetch(PDO::FETCH_ASSOC);
            if (!$row) jsonResponse(false, 'Run tidak ditemukan');
            $loc = $row['location'] ?? '';
            $note = $row['notes'] ?? '';
            $tm = $row['created_at'] ?? date('Y-m-d H:i:s');
            $tpl = $row['template_name'] ?? '';
            $off = $row['officer_name'] ?? '';
            $msg = "Security: " . ($tpl ?: 'Checklist') . " • " . ($off ?: 'Petugas') . "\nWaktu: " . $tm . "\nLokasi: " . ($loc ?: '-') . "\nCatatan: " . ($note ?: '-');
            $targets = parseManagerialTargets($pdo, $targetsOverride ?? null);
            if (!is_array($targets) || count($targets) === 0) {
                jsonResponse(false, 'Daftar nomor kosong');
            }
            $okCount = 0; $failCount = 0; $errors = [];
            foreach ($targets as $t) {
                $res = sendWhatsAppMessage($pdo, $msg, $t);
                $sc = is_array($res) ? !!$res['success'] : !!$res;
                if ($sc) { $okCount++; } else { $failCount++; $errors[] = is_array($res) ? ($res['error'] ?? 'unknown') : 'unknown'; }
            }
            logActivity($pdo, 'EXECUTIVE', 'NOTIFY', 'WA_REPORT', 'security_checklist_runs', (string)$runId, 'Kirim WA Laporan', 'OK=' . $okCount . ' FAIL=' . $failCount);
            jsonResponse(($okCount > 0), ($okCount > 0 ? 'Laporan terkirim' : 'Laporan gagal'), ['ok' => $okCount, 'fail' => $failCount, 'errors' => $errors]);
        } catch (\Throwable $e) {
            jsonResponse(false, 'Kesalahan sistem: ' . $e->getMessage());
        }
    }
    elseif ($action === 'flush_opcache' && $method === 'GET') {
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $ok = false;
        if (function_exists('opcache_reset')) {
            $ok = opcache_reset();
        }
        jsonResponse($ok ? true : false, $ok ? 'OPcache reset' : 'OPcache not available');
    }
    else {
        jsonResponse(false, 'Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
