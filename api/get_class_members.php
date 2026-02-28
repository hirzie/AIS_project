<?php
// api/get_class_members.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$class_id = $_GET['class_id'] ?? 0;

try {
    if (!$class_id) {
        echo json_encode([]);
        exit;
    }
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
        $stmtU = $pdo->prepare("SELECT UPPER(u.code) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
        $stmtU->execute([$class_id]);
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

    $sql = "
        SELECT 
            p.id,
            p.identity_number,
            p.name,
            p.gender,
            sd.nisn,
            sd.birth_place,
            sd.birth_date,
            sd.school_origin,
            sc.status
        FROM acad_student_classes sc
        JOIN core_people p ON sc.student_id = p.id
        LEFT JOIN acad_student_details sd ON p.id = sd.student_id
        WHERE sc.class_id = ? AND sc.status = 'ACTIVE' AND p.type = 'STUDENT'
        ORDER BY p.name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id]);
    $members = $stmt->fetchAll();

    echo json_encode($members);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

