<?php
// api/manage_subject.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'create') {
        $unit_id = $input['unit_id'] ?? null;
        $code = $input['code'] ?? '';
        $name = $input['name'] ?? '';
        $category = $input['category'] ?? 'CORE'; // CORE, MULOK, EKSTRA, CUSTOM
        $default_weekly_count = isset($input['default_weekly_count']) ? (int)$input['default_weekly_count'] : 1;
        $default_session_length = isset($input['default_session_length']) ? (int)$input['default_session_length'] : 2;

        if (!$unit_id || !$code || !$name) {
            echo json_encode(['error' => 'Data tidak lengkap']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_subjects WHERE unit_id = ? AND code = ?");
        $stmt->execute([$unit_id, $code]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Kode Mapel sudah digunakan di unit ini']);
            exit;
        }
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $map = $_SESSION['allowed_units'] ?? [];
            $codes = array_keys(array_filter(is_array($map) ? $map : []));
            $up = array_map(function($s){ return strtoupper(trim((string)$s)); }, $codes);
            if (count($up) > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_units WHERE id = ? AND UPPER(code) IN (" . implode(',', array_fill(0, count($up), '?')) . ")");
                $stmt->execute(array_merge([$unit_id], $up));
                $ok = $stmt->fetchColumn() > 0;
                if (!$ok) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                    exit;
                }
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO acad_subjects (unit_id, code, name, category, default_weekly_count, default_session_length) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$unit_id, $code, $name, $category, $default_weekly_count, $default_session_length]);
        echo json_encode(['success' => true, 'message' => 'Mata Pelajaran berhasil ditambahkan']);

    } elseif ($action === 'update') {
        $id = $input['id'] ?? null;
        $code = $input['code'] ?? '';
        $name = $input['name'] ?? '';
        $category = $input['category'] ?? 'CORE';
        $default_weekly_count = isset($input['default_weekly_count']) ? (int)$input['default_weekly_count'] : 1;
        $default_session_length = isset($input['default_session_length']) ? (int)$input['default_session_length'] : 2;

        if (!$id) {
            echo json_encode(['error' => 'ID Mapel tidak ditemukan']);
            exit;
        }
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(u.code) AS unit_code FROM acad_subjects s JOIN core_units u ON s.unit_id = u.id WHERE s.id = ?");
            $stmtU->execute([$id]);
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

        $stmt = $pdo->prepare("UPDATE acad_subjects SET code = ?, name = ?, category = ?, default_weekly_count = ?, default_session_length = ? WHERE id = ?");
        $stmt->execute([$code, $name, $category, $default_weekly_count, $default_session_length, $id]);

        // CASCADING UPDATE: Update also existing subject teachers assignments
        // This ensures that when defaults change, all connected class settings follow suit
        $stmtUpdateTeachers = $pdo->prepare("
            UPDATE acad_subject_teachers 
            SET weekly_count = ?, 
                session_length = ? 
            WHERE subject_id = ?
        ");
        $stmtUpdateTeachers->execute([$default_weekly_count, $default_session_length, $id]);

        // --- CONFLICT DETECTION & WARNING SYSTEM ---
        // Check if existing schedules violate the new settings
        // This is non-blocking but informs the user
        
        $warnings = [];

        // Check 1: Classes that now have MORE scheduled sessions than the new weekly limit
        // We sum the duration (in slots) of all sessions for this subject per class
        // Note: We approximate session count by counting slots. If 1 session = 2 slots, then count/2 = sessions.
        // But simpler: just check total slots used vs total slots allowed (weekly * session_length)
        
        // CORRECTION: weekly_count is NUMBER OF SESSIONS. session_length is DURATION PER SESSION (IN SLOTS).
        // Total slots allowed = weekly_count * session_length
        $totalSlotsAllowed = $default_weekly_count * $default_session_length;
        
        $sqlCheckOverload = "
            SELECT 
                c.name as class_name,
                COUNT(*) as slots_used
            FROM acad_schedules s
            JOIN acad_classes c ON s.class_id = c.id
            WHERE s.subject_id = ?
            GROUP BY s.class_id
            HAVING slots_used > ?
        ";
        $stmtOverload = $pdo->prepare($sqlCheckOverload);
        $stmtOverload->execute([$id, $totalSlotsAllowed]);
        $overloadedClasses = $stmtOverload->fetchAll(PDO::FETCH_ASSOC);

        if (count($overloadedClasses) > 0) {
            $classNames = array_column($overloadedClasses, 'class_name');
            $warnings[] = "Beban Mengajar Berlebih: Kelas " . implode(", ", $classNames) . " memiliki jadwal melebihi " . $totalSlotsAllowed . " slot (JP).";
        }

        // Check 2: Sessions that have different length than new default
        // This is trickier because schedules are individual slots. 
        // We can check if any class has a block of slots that doesn't match the new session_length?
        // For simplicity in this warning system, we might just warn that *any* existing schedule might be inconsistent.
        
        $stmtCheckAnySchedule = $pdo->prepare("SELECT COUNT(*) FROM acad_schedules WHERE subject_id = ?");
        $stmtCheckAnySchedule->execute([$id]);
        if ($stmtCheckAnySchedule->fetchColumn() > 0) {
            $warnings[] = "Perhatian: Mapel ini sudah memiliki jadwal aktif. Perubahan durasi/beban mungkin menyebabkan ketidaksesuaian pada jadwal yang sudah ada.";
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Mata Pelajaran berhasil diupdate',
            'warnings' => $warnings
        ]);

    } elseif ($action === 'delete') {
        $id = $input['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['error' => 'ID Mapel tidak ditemukan']);
            exit;
        }
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            $stmtU = $pdo->prepare("SELECT UPPER(u.code) AS unit_code FROM acad_subjects s JOIN core_units u ON s.unit_id = u.id WHERE s.id = ?");
            $stmtU->execute([$id]);
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
        
        // Cek dependencies
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_schedules WHERE subject_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['error' => 'Mapel tidak bisa dihapus karena sudah ada di jadwal']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM acad_subjects WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Mata Pelajaran dihapus']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

