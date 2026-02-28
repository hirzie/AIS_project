<?php
// api/get_teacher_schedule.php
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$teacher_id = $_GET['teacher_id'] ?? 0;
$academic_year_id = $_GET['academic_year_id'] ?? 0;

try {
    if (!$teacher_id) {
        echo json_encode([]);
        exit;
    }

    // Convert Employee ID (from frontend) to Person ID (for acad_schedules)
    $stmt_pid = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
    $stmt_pid->execute([$teacher_id]);
    $person_id = $stmt_pid->fetchColumn();

    if (!$person_id) {
        // Jika tidak ditemukan relasi, return kosong
        echo json_encode([]);
        exit;
    }

    // Ambil jadwal lengkap dengan nama Mapel, Kelas, dan Waktu
    $sql = "
        SELECT 
            s.id,
            s.day_name,
            s.start_time,
            s.end_time,
            s.subject_id,
            s.class_id,
            sub.name as subject_name,
            sub.code as subject_code,
            sub.category as subject_category,
            c.name as class_name
        FROM acad_schedules s
        JOIN acad_subjects sub ON s.subject_id = sub.id
        JOIN acad_classes c ON s.class_id = c.id
        JOIN acad_class_levels l ON c.level_id = l.id
        JOIN core_units u ON l.unit_id = u.id
        WHERE (s.teacher_id = ? OR s.teacher_id IN (SELECT id FROM hr_employees WHERE person_id = ?))
    ";
    
    // Filter by academic year if provided
    // FIND ACTIVE YEAR IF NOT PROVIDED
    if (!$academic_year_id) {
        $stmtYear = $pdo->query("SELECT id FROM acad_years WHERE status = 'ACTIVE' LIMIT 1");
        $activeYear = $stmtYear->fetch();
        if ($activeYear) {
            $academic_year_id = $activeYear['id'];
        }
    }
    
    $params = [$person_id, $person_id];
    
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
        $map = $_SESSION['allowed_units'] ?? [];
        $codes = array_keys(array_filter(is_array($map) ? $map : []));
        $up = array_map(function($s){ return strtoupper(trim((string)$s)); }, $codes);
        if (count($up) > 0) {
            $place = implode(',', array_fill(0, count($up), '?'));
            $sql .= " AND UPPER(u.code) IN ($place)";
            $params = array_merge($params, $up);
        } else {
            echo json_encode([]); 
            exit;
        }
    }
    
    if ($academic_year_id) {
        $sql .= " AND c.academic_year_id = ?";
        $params[] = $academic_year_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grouping by Day and Start Time
    $grouped = [];
    foreach ($schedules as $sch) {
        $timeKey = date('H:i', strtotime($sch['start_time']));
        $grouped[$sch['day_name']][$timeKey] = $sch;
    }

    echo json_encode($grouped);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
