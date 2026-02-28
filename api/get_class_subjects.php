<?php
// api/get_class_subjects.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$class_id = $_GET['class_id'];
$academic_year_id = $_GET['academic_year_id']; // Wajib

try {
    // 1. Get Unit ID of the class
    $stmt = $pdo->prepare("SELECT l.unit_id, UPPER(u.code) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
    $stmt->execute([$class_id]);
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

    // 2. Get Subjects and Assigned Teachers
    $sql = "
        SELECT 
            s.id as subject_id, 
            s.code, 
            s.name as subject_name, 
            s.category,
            st.teacher_id, 
            p.name as teacher_name
        FROM acad_subjects s
        LEFT JOIN acad_subject_teachers st 
            ON s.id = st.subject_id 
            AND st.class_id = ? 
            AND st.academic_year_id = ?
        LEFT JOIN core_people p ON st.teacher_id = p.id
        WHERE s.unit_id = ?
        ORDER BY s.category, s.name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id, $academic_year_id, $unit_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

