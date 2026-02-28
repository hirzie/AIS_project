<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function jsonResponse($success, $message = '', $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
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

try {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS cleaning_checklists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT DEFAULT NULL,
            room_id INT NOT NULL,
            template_id INT DEFAULT NULL,
            checklist_date DATE NOT NULL,
            filled_by INT NOT NULL,
            items_filled_json JSON,
            notes TEXT,
            request_id VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $chkTpl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cleaning_checklists' AND COLUMN_NAME = 'template_id'");
        $chkTpl->execute();
        if ((int)$chkTpl->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE cleaning_checklists ADD COLUMN template_id INT DEFAULT NULL AFTER room_id");
        }
        $chkReq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cleaning_checklists' AND COLUMN_NAME = 'request_id'");
        $chkReq->execute();
        if ((int)$chkReq->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE cleaning_checklists ADD COLUMN request_id VARCHAR(64) DEFAULT NULL AFTER notes");
        }
        $idx = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cleaning_checklists' AND INDEX_NAME = 'uniq_request_id'");
        $idx->execute();
        if ((int)$idx->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE cleaning_checklists ADD UNIQUE INDEX uniq_request_id (request_id)");
        }
    } catch (\Throwable $e) {}
    if ($action === 'assign_person' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $staff_id = $input['staff_id'] ?? '';
        $room_id = $input['room_id'] ?? '';
        $date = $input['date'] ?? date('Y-m-d');
        if (empty($staff_id) || empty($room_id)) {
            jsonResponse(false, 'Staf dan ruangan wajib diisi');
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO cleaning_assignments (staff_id, room_id, assignment_date) VALUES (?, ?, ?)");
            $stmt->execute([$staff_id, $room_id, $date]);
            jsonResponse(true, 'Penempatan berhasil disimpan');
        } catch (PDOException $e) {
            jsonResponse(false, 'Tabel cleaning_assignments belum tersedia atau terjadi kesalahan');
        }
    }
    elseif ($action === 'list_rooms') {
        try {
            $stmt = $pdo->query("SELECT id, code, name, building FROM cleaning_rooms ORDER BY building, name");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, '', $rooms);
        } catch (PDOException $e) {
            jsonResponse(true, 'Belum ada tabel cleaning_rooms', []);
        }
    }
    elseif ($action === 'list_checklist_templates') {
        try {
            $stmt = $pdo->query("SELECT id, name, items_json FROM cleaning_checklist_templates ORDER BY name");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, '', $rows);
        } catch (PDOException $e) {
            jsonResponse(true, 'Belum ada tabel cleaning_checklist_templates', []);
        }
    }
    elseif ($action === 'list_assignments') {
        try {
            $stmt = $pdo->query("SELECT staff_id, room_id, assignment_date FROM cleaning_assignments ORDER BY assignment_date DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, '', $rows);
        } catch (PDOException $e) {
            jsonResponse(true, 'Belum ada tabel cleaning_assignments', []);
        }
    }
    elseif ($action === 'start_checklist_run' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $template_id = (int)($input['template_id'] ?? 0);
        $room_id = (int)($input['room_id'] ?? 0);
        $notes = trim($input['notes'] ?? '');
        $dateStr = trim($input['date'] ?? date('Y-m-d'));
        $reqId = isset($input['request_id']) ? trim((string)$input['request_id']) : '';
        if ($template_id <= 0 || $room_id <= 0) jsonResponse(false, 'Template dan ruangan wajib');
        $run_id = null;
        if ($reqId !== '') {
            $sCheck = $pdo->prepare("SELECT id FROM cleaning_checklists WHERE request_id = ?");
            $sCheck->execute([$reqId]);
            $foundId = (int)($sCheck->fetchColumn() ?: 0);
            if ($foundId > 0) {
                $run_id = $foundId;
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO cleaning_checklists (template_id, room_id, checklist_date, filled_by, notes, request_id) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$template_id, $room_id, $dateStr, $_SESSION['user_id'] ?? null, $notes ?: null, $reqId]);
                    $run_id = (int)$pdo->lastInsertId();
                } catch (\PDOException $e) {
                    $sCheck2 = $pdo->prepare("SELECT id FROM cleaning_checklists WHERE request_id = ?");
                    $sCheck2->execute([$reqId]);
                    $run_id = (int)($sCheck2->fetchColumn() ?: 0);
                }
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO cleaning_checklists (template_id, room_id, checklist_date, filled_by, notes) VALUES (?,?,?,?,?)");
            $stmt->execute([$template_id, $room_id, $dateStr, $_SESSION['user_id'] ?? null, $notes ?: null]);
            $run_id = (int)$pdo->lastInsertId();
        }
        try {
            $tplName = null;
            $sTpl = $pdo->prepare("SELECT name FROM cleaning_checklist_templates WHERE id = ?");
            $sTpl->execute([$template_id]);
            $tplName = $sTpl->fetchColumn();
            $title = $tplName ? ('Mulai Checklist: ' . $tplName) : 'Mulai Checklist';
            logActivity($pdo, 'CLEANING', 'CHECKLIST', 'START', 'cleaning_checklists', $run_id, $title, null);
        } catch (\Throwable $e) {}
        jsonResponse(true, '', ['run_id' => $run_id]);
    }
    elseif ($action === 'save_checklist_result' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $run_id = (int)($input['run_id'] ?? 0);
        $template_id = (int)($input['template_id'] ?? 0);
        $room_id = (int)($input['room_id'] ?? 0);
        $notes = trim($input['notes'] ?? '');
        $dateStr = trim($input['date'] ?? date('Y-m-d'));
        $reqId = isset($input['request_id']) ? trim((string)$input['request_id']) : '';
        $answers = $input['answers'] ?? [];
        if (!is_array($answers)) $answers = [];
        if ($run_id <= 0) {
            if ($template_id <= 0 || $room_id <= 0) jsonResponse(false, 'Template dan ruangan wajib');
            if ($reqId !== '') {
                $sCheck = $pdo->prepare("SELECT id FROM cleaning_checklists WHERE request_id = ?");
                $sCheck->execute([$reqId]);
                $foundId = (int)($sCheck->fetchColumn() ?: 0);
                if ($foundId > 0) {
                    $run_id = $foundId;
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO cleaning_checklists (template_id, room_id, checklist_date, filled_by, notes, request_id) VALUES (?,?,?,?,?,?)");
                        $stmt->execute([$template_id, $room_id, $dateStr, $_SESSION['user_id'] ?? null, $notes ?: null, $reqId]);
                        $run_id = (int)$pdo->lastInsertId();
                    } catch (\PDOException $e) {
                        $sCheck2 = $pdo->prepare("SELECT id FROM cleaning_checklists WHERE request_id = ?");
                        $sCheck2->execute([$reqId]);
                        $run_id = (int)($sCheck2->fetchColumn() ?: 0);
                    }
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO cleaning_checklists (template_id, room_id, checklist_date, filled_by, notes) VALUES (?,?,?,?,?)");
                $stmt->execute([$template_id, $room_id, $dateStr, $_SESSION['user_id'] ?? null, $notes ?: null]);
                $run_id = (int)$pdo->lastInsertId();
            }
        }
        $stmtUp = $pdo->prepare("UPDATE cleaning_checklists SET items_filled_json = ?, notes = ? WHERE id = ?");
        $stmtUp->execute([json_encode($answers), $notes ?: null, $run_id]);
        try {
            $cnt = is_array($answers) ? count($answers) : 0;
            $sTpl = $pdo->prepare("SELECT t.name FROM cleaning_checklists c JOIN cleaning_checklist_templates t ON c.template_id = t.id WHERE c.id = ?");
            $sTpl->execute([$run_id]);
            $tplName = $sTpl->fetchColumn();
            $title = $tplName ? ('Checklist disimpan: ' . $tplName) : 'Checklist disimpan';
            $desc = 'Jumlah jawaban: ' . $cnt;
            logActivity($pdo, 'CLEANING', 'CHECKLIST', 'SAVE', 'cleaning_checklists', $run_id, $title, $desc);
        } catch (\Throwable $e) {}
        jsonResponse(true, 'Checklist disimpan', ['run_id' => $run_id]);
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
            SELECT c.id, c.template_id, c.room_id, c.filled_by, c.notes, c.created_at,
                   t.name AS template_name,
                   u.username, p.name AS filled_name, p.custom_attributes AS people_custom_attributes, u.role AS filled_role
            FROM cleaning_checklists c
            LEFT JOIN cleaning_checklist_templates t ON c.template_id = t.id
            LEFT JOIN core_users u ON c.filled_by = u.id
            LEFT JOIN core_people p ON u.people_id = p.id
            WHERE c.created_at >= ? AND c.created_at <= ?
        ";
        $params = [$start, $end];
        if ($userId) {
            $sql .= " AND c.filled_by = ?";
            $params[] = $userId;
        }
        $sql .= " ORDER BY c.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsAll as &$row) {
            try {
                $row['created_at_plus7'] = date('Y-m-d H:i:s', strtotime($row['created_at'].' +7 hours'));
            } catch (\Throwable $e) {
                $row['created_at_plus7'] = $row['created_at'];
            }
        }
        jsonResponse(true, '', $rowsAll);
    }
    elseif ($action === 'get_checklist_answers' && $method === 'GET') {
        $runId = (int)($_GET['run_id'] ?? 0);
        if (!$runId) jsonResponse(false, 'Run ID invalid');
        $stmt = $pdo->prepare("SELECT items_filled_json FROM cleaning_checklists WHERE id = ?");
        $stmt->execute([$runId]);
        $val = $stmt->fetchColumn();
        $arr = [];
        try { $arr = json_decode($val ?: '[]', true) ?: []; } catch (\Throwable $e) { $arr = []; }
        jsonResponse(true, '', $arr);
    }
    else {
        jsonResponse(false, 'Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
