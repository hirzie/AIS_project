<?php
// api/get_subject_assignments.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$subject_id = $_GET['subject_id'];
$academic_year_id = $_GET['academic_year_id'];

try {
    // 1. Get Unit ID of the subject
    $stmt = $pdo->prepare("SELECT s.unit_id, UPPER(u.code) AS unit_code FROM acad_subjects s JOIN core_units u ON s.unit_id = u.id WHERE s.id = ?");
    $stmt->execute([$subject_id]);
    $rowUnit = $stmt->fetch(PDO::FETCH_ASSOC);
    $unit_id = $rowUnit ? $rowUnit['unit_id'] : null;
    $unit_code_up = $rowUnit ? $rowUnit['unit_code'] : null;

    if (!$unit_id) {
        echo json_encode([]);
        exit;
    }
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
        $allowedMap = $_SESSION['allowed_units'] ?? [];
        $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
        $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
        if (!$unit_code_up || !in_array($unit_code_up, $allowedUp)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
            exit;
        }
    }

    // 2. Get All Classes for this Unit and their assigned teacher for this subject
    // JOIN with acad_subjects to get defaults
    // Note: CROSS JOIN acad_subjects ensures we get 's' alias available for COALESCE and WHERE
    $sql = "
        SELECT 
            c.id as class_id, 
            c.name as class_name, 
            l.name as level_name,
            he.id as teacher_id, 
            p.name as teacher_name,
            st.id as assignment_id,
            COALESCE(st.weekly_count, s.default_weekly_count, 1) as weekly_count,
            COALESCE(st.session_length, s.default_session_length, 2) as session_length
        FROM acad_classes c
        JOIN acad_class_levels l ON c.level_id = l.id
        CROSS JOIN acad_subjects s 
        LEFT JOIN acad_subject_teachers st 
            ON c.id = st.class_id 
            AND st.subject_id = s.id 
            AND st.academic_year_id = ?
        LEFT JOIN core_people p ON st.teacher_id = p.id
        LEFT JOIN hr_employees he ON p.id = he.person_id AND he.employment_status != 'RESIGNED'
        WHERE s.id = ? AND l.unit_id = ?
        ORDER BY l.order_index ASC, c.sort_order ASC, c.name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$academic_year_id, $subject_id, $unit_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

