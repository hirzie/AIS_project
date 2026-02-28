<?php
// api/approval.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

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

function logActivity($pdo, $category, $action, $entityType, $entityId, $title, $description) {
    ensureActivityTable($pdo);
    $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'EXECUTIVE',
        $category,
        $action,
        $entityType,
        $entityId,
        $title,
        $description,
        $_SESSION['user_id'] ?? null
    ]);
}

function ensureMeetingColumns($pdo) {
    $checkId = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_approvals' AND COLUMN_NAME = 'meeting_id'");
    $checkId->execute();
    $hasId = (bool)$checkId->fetchColumn();
    if (!$hasId) {
        $pdo->exec("ALTER TABLE sys_approvals ADD COLUMN meeting_id INT NULL");
    }
    $checkTitle = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_approvals' AND COLUMN_NAME = 'meeting_title'");
    $checkTitle->execute();
    $hasTitle = (bool)$checkTitle->fetchColumn();
    if (!$hasTitle) {
        $pdo->exec("ALTER TABLE sys_approvals ADD COLUMN meeting_title VARCHAR(255) NULL");
    }
}

try {
    if ($action == 'get_employees') {
        $stmt = $pdo->query("
            SELECT p.name, e.employee_number, pos.name as position 
            FROM hr_employees e 
            JOIN core_people p ON e.person_id = p.id 
            LEFT JOIN hr_positions pos ON e.position_id = pos.id
            WHERE e.status = 'ACTIVE' 
            ORDER BY p.name ASC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif ($action == 'get_list') {
        $status = $_GET['status'] ?? 'PENDING';
        
        // Reverted to simple query + PHP Lookup to prevent SQL errors
        $sql = "SELECT a.* FROM sys_approvals a ";
        
        if ($status !== 'ALL') {
            $sql .= "WHERE a.status = :status ";
        }
        $sql .= "ORDER BY a.created_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($status !== 'ALL') {
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // PHP Lookup for Payout Info (Retroactive Kas Bon)
        foreach ($rows as &$row) {
            if (empty($row['payout_trans_number'])) {
                // Check fin_cash_advances
                $stmtCa = $pdo->prepare("SELECT id, request_date FROM fin_cash_advances WHERE proposal_ref = ? LIMIT 1");
                $stmtCa->execute([$row['reference_no']]);
                $ca = $stmtCa->fetch(PDO::FETCH_ASSOC);
                
                if ($ca) {
                    $row['payout_trans_number'] = "KASBON #" . $ca['id'];
                    $row['payout_date'] = $ca['request_date'];
                    // $row['payout_pic'] = 'System'; // Optional
                }
            }
        }
        
        echo json_encode(['success' => true, 'data' => $rows]);
    }
    elseif ($action == 'get_by_ref') {
        $ref = $_GET['ref'];
        $stmt = $pdo->prepare("SELECT * FROM sys_approvals WHERE reference_no = ? AND status = 'APPROVED'");
        $stmt->execute([$ref]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found or not approved']);
        }
    } 
    elseif ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $module = $data['module'] ?? 'UMUM';
        $title = $data['title'];
        $description = $data['description'];
        $requester = $data['requester'];
        $amount = $data['amount'] ?? 0;
        $attachment = $data['attachment'] ?? null;
        $meeting_id = $data['meeting_id'] ?? null;
        $meeting_title = $data['meeting_title'] ?? null;
        
        // Generate Reference No
        $year = date('Y');
        $ref = strtoupper(substr($module, 0, 3)) . "-$year-" . time(); // Simple Unique ID
        
        ensureMeetingColumns($pdo);
        $sql = "INSERT INTO sys_approvals (module, reference_no, title, description, requester, amount, attachment, meeting_id, meeting_title, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$module, $ref, $title, $description, $requester, $amount, $attachment, $meeting_id, $meeting_title]);
        
        logActivity($pdo, 'APPROVAL', 'CREATE', 'sys_approvals', $ref, $title, $description);
        echo json_encode(['success' => true, 'message' => 'Pengajuan berhasil dibuat']);
    }
    elseif ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'];
        $title = $data['title'];
        $description = $data['description'];
        $requester = $data['requester'];
        $amount = $data['amount'] ?? 0;
        $attachment = $data['attachment'] ?? null;
        $module = $data['module'] ?? 'UMUM';
        $meeting_id = $data['meeting_id'] ?? null;
        $meeting_title = $data['meeting_title'] ?? null;

        // Security: only allow update if status is PENDING
        $check = $pdo->prepare("SELECT status FROM sys_approvals WHERE id = ?");
        $check->execute([$id]);
        $current = $check->fetch();
        
        if (!$current || $current['status'] !== 'PENDING') {
            throw new Exception("Hanya pengajuan PENDING yang dapat diubah.");
        }
        
        ensureMeetingColumns($pdo);
        $sql = "UPDATE sys_approvals SET module = ?, title = ?, description = ?, requester = ?, amount = ?, attachment = ?, meeting_id = ?, meeting_title = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$module, $title, $description, $requester, $amount, $attachment, $meeting_id, $meeting_title, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Pengajuan berhasil diperbarui']);
    }
    elseif ($action == 'delete' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];

        // Security: only allow delete if status is PENDING
        $check = $pdo->prepare("SELECT status FROM sys_approvals WHERE id = ?");
        $check->execute([$id]);
        $current = $check->fetch();
        
        if (!$current || $current['status'] !== 'PENDING') {
            throw new Exception("Hanya pengajuan PENDING yang dapat dihapus.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM sys_approvals WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Pengajuan berhasil dihapus']);
    }
    elseif ($action == 'update_status' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'];
        $status = $data['status'];
        $approvedBy = $_SESSION['username'] ?? null;
        $userRole = strtoupper($_SESSION['role'] ?? '');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        if ($status !== 'PENDING' && $userRole !== 'MANAGERIAL') {
            echo json_encode(['success' => false, 'message' => 'Hanya manajer yang dapat melakukan persetujuan/penolakan']);
            exit;
        }
        
        if (!in_array($status, ['APPROVED', 'REJECTED', 'PENDING'])) {
            throw new Exception("Invalid status");
        }

        if ($status === 'APPROVED') {
            $stmtInfo = $pdo->prepare("SELECT reference_no, title, description FROM sys_approvals WHERE id = ?");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC) ?: ['reference_no' => $id, 'title' => null, 'description' => null];
            $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_approvals' AND COLUMN_NAME = 'approved_by'");
            $colCheck->execute();
            $hasApprovedBy = (bool)$colCheck->fetchColumn();
            if (!$hasApprovedBy) {
                $pdo->exec("ALTER TABLE sys_approvals ADD COLUMN approved_by VARCHAR(100) DEFAULT NULL");
            }
            $colCheck2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_approvals' AND COLUMN_NAME = 'approved_at'");
            $colCheck2->execute();
            $hasApprovedAt = (bool)$colCheck2->fetchColumn();
            if (!$hasApprovedAt) {
                $pdo->exec("ALTER TABLE sys_approvals ADD COLUMN approved_at DATETIME DEFAULT NULL");
            }
            $stmt = $pdo->prepare("UPDATE sys_approvals SET status = 'APPROVED', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$approvedBy, $id]);
            logActivity($pdo, 'APPROVAL', 'APPROVE', 'sys_approvals', $info['reference_no'], $info['title'], "Disetujui oleh $approvedBy");
        } elseif ($status === 'PENDING') {
            $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_approvals' AND COLUMN_NAME = 'approved_by'");
            $colCheck->execute();
            $hasApprovedBy = (bool)$colCheck->fetchColumn();
            $colCheck2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_approvals' AND COLUMN_NAME = 'approved_at'");
            $colCheck2->execute();
            $hasApprovedAt = (bool)$colCheck2->fetchColumn();
            if ($hasApprovedBy && $hasApprovedAt) {
                $stmt = $pdo->prepare("UPDATE sys_approvals SET status = 'PENDING', approved_by = NULL, approved_at = NULL WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $pdo->prepare("UPDATE sys_approvals SET status = 'PENDING' WHERE id = ?");
                $stmt->execute([$id]);
            }
        } else {
            $stmtInfo = $pdo->prepare("SELECT reference_no, title, description FROM sys_approvals WHERE id = ?");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC) ?: ['reference_no' => $id, 'title' => null, 'description' => null];
            $stmt = $pdo->prepare("UPDATE sys_approvals SET status = 'REJECTED' WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, 'APPROVAL', 'REJECT', 'sys_approvals', $info['reference_no'], $info['title'], "Ditolak oleh $approvedBy");
        }
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
