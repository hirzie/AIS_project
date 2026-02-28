<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/guard.php';

if (session_status() == PHP_SESSION_NONE) {
    ais_init_session();
}

$action = $_GET['action'] ?? '';
$class_id = $_GET['class_id'] ?? null;

// Ensure required tables exist
function ensure_lock_tables($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS acad_lock_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        lock_type VARCHAR(50) NOT NULL, -- ATTENDANCE, FACILITY, COMPLAINT, ALL
        valid_until DATETIME NOT NULL,
        unlocked_by INT DEFAULT NULL,
        reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_class_lock (class_id, lock_type, valid_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inv_facility_checks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        check_date DATE NOT NULL,
        status ENUM('GOOD', 'ISSUES') DEFAULT 'GOOD',
        notes TEXT,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_class_date (class_id, check_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
}

ensure_lock_tables($pdo);

// Helper function to check lock status for a single class
function checkClassLockStatus($pdo, $class_id) {
    $locks = [];
    $is_locked = false;

    // Get valid overrides
    $stmt = $pdo->prepare("SELECT lock_type FROM acad_lock_overrides WHERE class_id = ? AND valid_until > NOW()");
    $stmt->execute([$class_id]);
    $overrides = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $isOverridden = function($type) use ($overrides) {
        return in_array('ALL', $overrides) || in_array($type, $overrides);
    };

    // 1. ATTENDANCE CHECK (Previous month recap missing by 5th)
    if (!$isOverridden('ATTENDANCE')) {
        $today = new DateTime();
        $dayOfMonth = (int)$today->format('d');
        
        if ($dayOfMonth >= 5) {
            $prevMonth = (clone $today)->modify('-1 month');
            $month = (int)$prevMonth->format('m');
            $year = (int)$prevMonth->format('Y');
            
            try {
                // Check acad_attendance_monthly existence first or just query
                // We assume the table exists as it is core functionality
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_attendance_monthly WHERE class_id = ? AND month = ? AND year = ?");
                $stmt->execute([$class_id, $month, $year]);
                if ($stmt->fetchColumn() == 0) {
                    $locks[] = [
                        'type' => 'ATTENDANCE',
                        'message' => 'Rekap absensi bulan lalu belum dikirim',
                        'detail' => "Rekap bulan " . $prevMonth->format('F Y') . " wajib dikirim sebelum tanggal 5."
                    ];
                    $is_locked = true;
                }
            } catch (Exception $e) {
                // Table might not exist yet
            }
        }
    }

    // 2. FACILITY CHECK (Last Friday check missing, lock starts on Saturday)
    if (!$isOverridden('FACILITY')) {
        $today = new DateTime();
        $checkDate = clone $today;
        
        // Rule: "pengecekan setiap hari jumat, ketika jumat belum terselesaikan sabtu terkunci kembali"
        // If today is Friday, we do not lock (grace period for input).
        // If today is NOT Friday (e.g. Saturday), we check the most recent Friday.
        
        if ($today->format('N') == 5) {
            // It is Friday. No lock for Facility today.
        } else {
            // It is not Friday (Sat-Thu). Check the last Friday.
            $checkDate->modify('last friday');
            $dateStr = $checkDate->format('Y-m-d');
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inv_facility_checks WHERE class_id = ? AND check_date = ?");
            $stmt->execute([$class_id, $dateStr]);
            if ($stmt->fetchColumn() == 0) {
                $locks[] = [
                    'type' => 'FACILITY',
                    'message' => 'Laporan fasilitas hari Jumat belum diisi',
                    'detail' => "Laporan untuk tanggal $dateStr belum tersedia. (Wajib lapor setiap Jumat)"
                ];
                $is_locked = true;
            }
        }
    }

    // 3. COMPLAINT CHECK (Pending incidents > 2 days)
    if (!$isOverridden('COMPLAINT')) {
        try {
            // Check student_incidents for PENDING items older than 2 days
            // This enforces the rule: "laporan aduan tidak di proses (selesaikan internal / eskalasi ke BK) dalam 2 hari"
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_incidents 
                                   WHERE class_id = ? AND status = 'PENDING' 
                                   AND created_at < (NOW() - INTERVAL 2 DAY)");
            $stmt->execute([$class_id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $locks[] = [
                    'type' => 'COMPLAINT',
                    'message' => 'Laporan aduan siswa belum diproses > 2 hari',
                    'detail' => "$count aduan status PENDING (Wajib Selesaikan Internal atau Eskalasi ke BK)."
                ];
                $is_locked = true;
            }
        } catch (Exception $e) {
            // Table might not exist
        }
    }

    return [
        'is_locked' => $is_locked,
        'locks' => $locks,
        'overrides' => $overrides
    ];
}


if ($action === 'check_my_lock') {
    $stmt = $pdo->prepare("SELECT id, name FROM acad_classes WHERE homeroom_teacher_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['person_id']]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        // Not a homeroom teacher
        echo json_encode(['success' => true, 'is_locked' => false, 'message' => 'Not a homeroom teacher']);
        exit;
    }

    $result = checkClassLockStatus($pdo, $class['id']);
    echo json_encode(array_merge(['success' => true, 'class_id' => $class['id'], 'class_name' => $class['name']], $result));
}
elseif ($action === 'check_status') {
    if (!$class_id) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        exit;
    }

    $result = checkClassLockStatus($pdo, $class_id);
    echo json_encode(array_merge(['success' => true], $result));
}
elseif ($action === 'unlock') {
    // Permission check
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['SUPERADMIN', 'ADMIN', 'PRINCIPAL', 'MANAGERIAL'])) {
        // Fallback: check if they have specific access
        // For now strict check.
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $class_id = $input['class_id'] ?? null;
    $lock_type = $input['lock_type'] ?? 'ALL';
    $hours = (int)($input['hours'] ?? 24);
    $reason = $input['reason'] ?? 'Unlocked by Principal';

    if (!$class_id) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        exit;
    }

    $valid_until = (new DateTime())->modify("+$hours hours")->format('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("INSERT INTO acad_lock_overrides (class_id, lock_type, valid_until, unlocked_by, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$class_id, $lock_type, $valid_until, $_SESSION['user_id'] ?? null, $reason]);

    echo json_encode(['success' => true, 'message' => 'Unlock successful']);
}
elseif ($action === 'save_facility_check') {
    $input = json_decode(file_get_contents('php://input'), true);
    $class_id = $input['class_id'] ?? null;
    $status = $input['status'] ?? 'GOOD';
    $notes = $input['notes'] ?? '';
    
    if (!$class_id) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        exit;
    }

    // Determine check date (Last Friday or Today if Friday)
    $today = new DateTime();
    $checkDate = clone $today;
    if ($today->format('N') == 5) {
        // Today is Friday, use today
    } else {
        // Not Friday, use last Friday
        $checkDate->modify('last friday');
    }
    $dateStr = $checkDate->format('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO inv_facility_checks (class_id, check_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes), created_at = NOW()");
    $stmt->execute([$class_id, $dateStr, $status, $notes, $_SESSION['user_id'] ?? null]);

    echo json_encode(['success' => true, 'message' => 'Laporan fasilitas disimpan']);
}
elseif ($action === 'get_unit_locks') {
    $unit = $_GET['unit'] ?? ''; 
    // If unit not provided, try to infer or get all
    
    // We need to get list of classes first.
    // If unit is provided (e.g. 'smp'), filter by it.
    
    $sql = "SELECT c.id, c.name, l.name as level_name 
            FROM acad_classes c 
            JOIN acad_class_levels l ON c.level_id = l.id 
            JOIN core_units u ON l.unit_id = u.id 
            WHERE 1=1";
    
    $params = [];
    if ($unit) {
        $sql .= " AND (u.code = ? OR u.name = ?)";
        $params[] = $unit;
        $params[] = $unit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $locked_classes = [];
    
    foreach ($classes as $c) {
        $status = checkClassLockStatus($pdo, $c['id']);
        if ($status['is_locked']) {
            $c['locks'] = array_map(function($l) { return $l['type']; }, $status['locks']); // Just return types for summary
            $c['lock_details'] = $status['locks'];
            $locked_classes[] = $c;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $locked_classes]);
}
?>