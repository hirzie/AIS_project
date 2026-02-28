<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

function ensureZeroReportTable($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS zero_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        reporter_name VARCHAR(100) DEFAULT NULL,
        period_type VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL,
        incident_count INT DEFAULT 0,
        details JSON DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Ensure request_id column & unique index for idempotency
    try {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'zero_reports' AND COLUMN_NAME = 'request_id'");
        $chk->execute();
        if ((int)$chk->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE zero_reports ADD COLUMN request_id VARCHAR(64) DEFAULT NULL AFTER user_id");
        }
        $idx = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'zero_reports' AND INDEX_NAME = 'uniq_zero_request'");
        $idx->execute();
        if ((int)$idx->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE zero_reports ADD UNIQUE INDEX uniq_zero_request (request_id)");
        }
    } catch (\Throwable $e) { /* ignore */ }
}

function ensureActivityTable($pdo) {
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function logActivity($pdo, $module, $category, $action, $entityType, $entityId, $title, $description) {
    ensureActivityTable($pdo);
    $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $module,
        $category,
        $action,
        $entityType,
        $entityId,
        $title,
        $description,
        $_SESSION['user_id'] ?? null
    ]);
}

function createApprovalTask($pdo, $module, $title, $description, $requester, $amount = 0, $attachment = null) {
    $year = date('Y');
    $ref = strtoupper(substr($module, 0, 3)) . "-$year-" . time();
    $sql = "INSERT INTO sys_approvals (module, reference_no, title, description, requester, amount, attachment, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$module, $ref, $title, $description, $requester, $amount, $attachment]);
    logActivity($pdo, 'EXECUTIVE', 'APPROVAL', 'CREATE', 'sys_approvals', $ref, $title, $description);
    return $ref;
}

try {
    ensureZeroReportTable($pdo);

    if ($action === 'status') {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $stmt = $pdo->prepare("SELECT status, incident_count FROM zero_reports WHERE user_id = ? AND period_type = 'DAILY' AND created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$uid, $start, $end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'data' => ['reported' => true, 'status' => $row['status'], 'incident_count' => (int)$row['incident_count']]]);
        } else {
            echo json_encode(['success' => true, 'data' => ['reported' => false, 'status' => null, 'incident_count' => 0]]);
        }
    }
    elseif ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $period = strtoupper($data['period'] ?? 'DAILY');
        $status = strtolower($data['status'] ?? 'safe');
        $incidentCount = (int)($data['incident_count'] ?? 0);
        $details = isset($data['details']) ? json_encode($data['details']) : null;
        $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        $uid = $_SESSION['user_id'] ?? null;
        $uname = $_SESSION['username'] ?? 'User';
        if (!$uid) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        if (!in_array($period, ['DAILY','WEEKLY','MONTHLY'])) { throw new Exception('Invalid period'); }
        if (!in_array($status, ['safe','incident'])) { throw new Exception('Invalid status'); }

        if ($reqId !== '') {
            $sCheck = $pdo->prepare("SELECT id FROM zero_reports WHERE request_id = ?");
            $sCheck->execute([$reqId]);
            $foundId = (int)($sCheck->fetchColumn() ?: 0);
            if ($foundId > 0) {
                echo json_encode(['success' => true, 'message' => 'Duplicate ignored', 'data' => ['id' => $foundId]]);
                exit;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO zero_reports (user_id, reporter_name, period_type, status, incident_count, details, request_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$uid, $uname, $period, strtoupper($status), $incidentCount, $details, $reqId]);
            } catch (\PDOException $e) {
                // Duplicate request_id
                $sCheck2 = $pdo->prepare("SELECT id FROM zero_reports WHERE request_id = ?");
                $sCheck2->execute([$reqId]);
                $dupId = (int)($sCheck2->fetchColumn() ?: 0);
                echo json_encode(['success' => true, 'message' => 'Duplicate ignored', 'data' => ['id' => $dupId]]);
                exit;
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO zero_reports (user_id, reporter_name, period_type, status, incident_count, details, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$uid, $uname, $period, strtoupper($status), $incidentCount, $details]);
        }

        if ($period === 'WEEKLY' && $status === 'incident') {
            $desc = 'Laporan kerusakan aset: ' . ($details ?: '');
            createApprovalTask($pdo, 'BOARDING', 'Kerusakan Aset Asrama', $desc, $uname, 0, null);
        }
        if ($period === 'MONTHLY' && $status === 'incident') {
            $desc = 'Pengajuan restock logistik: ' . ($details ?: '');
            createApprovalTask($pdo, 'BOARDING', 'Pengajuan Restock Asrama', $desc, $uname, 0, null);
        }

        logActivity($pdo, 'BOARDING', 'ZERO_REPORT', 'SAVE', 'zero_reports', null, "Zero Report {$period} - {$status}", $details ?: null);
        echo json_encode(['success' => true, 'message' => 'Saved']);
    }
    elseif ($action === 'list_today') {
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $stmt = $pdo->prepare("SELECT reporter_name AS name, status, incident_count, created_at FROM zero_reports WHERE period_type = 'DAILY' AND created_at BETWEEN ? AND ? ORDER BY created_at DESC");
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    }
    elseif ($action === 'summary') {
        $weeklyStart = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $monthlyStart = date('Y-m-d 00:00:00', strtotime('-31 days'));
        $stmtW = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM zero_reports WHERE period_type = 'WEEKLY' AND created_at >= ? GROUP BY status");
        $stmtW->execute([$weeklyStart]);
        $rowsW = $stmtW->fetchAll(PDO::FETCH_ASSOC);
        $wSafe = 0; $wIncident = 0;
        foreach ($rowsW as $r) {
            $st = strtoupper($r['status'] ?? '');
            if ($st === 'SAFE') $wSafe += (int)$r['cnt'];
            elseif ($st === 'INCIDENT') $wIncident += (int)$r['cnt'];
        }
        $stmtM = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM zero_reports WHERE period_type = 'MONTHLY' AND created_at >= ? GROUP BY status");
        $stmtM->execute([$monthlyStart]);
        $rowsM = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        $mSafe = 0; $mIncident = 0;
        foreach ($rowsM as $r) {
            $st = strtoupper($r['status'] ?? '');
            if ($st === 'SAFE') $mSafe += (int)$r['cnt'];
            elseif ($st === 'INCIDENT') $mIncident += (int)$r['cnt'];
        }
        echo json_encode(['success' => true, 'data' => [
            'weekly' => ['safe' => $wSafe, 'incident' => $wIncident],
            'monthly' => ['safe' => $mSafe, 'incident' => $mIncident]
        ]]);
    }
    else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
