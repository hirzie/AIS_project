<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$unit_id = $_GET['unit'] ?? null;
$limit = $_GET['limit'] ?? 100;

try {
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if ($unit_id && $unit_id !== 'all' && !in_array($role, ['SUPERADMIN','ADMIN'])) {
        if (!function_exists('ais_is_unit_allowed') || !ais_is_unit_allowed($pdo, $unit_id)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
            exit;
        }
    }
    $sql = "
        SELECT 
            p.id,
            p.identity_number as nis,
            p.name,
            p.gender,
            p.status,
            p.unit_id,
            c.name as class_name,
            c.id as class_id
        FROM core_people p
        LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
        LEFT JOIN acad_classes c ON sc.class_id = c.id
        WHERE p.type = 'STUDENT'
    ";

    $params = [];
    if ($unit_id && $unit_id !== 'all') {
        $stmtUnit = $pdo->prepare("SELECT id FROM core_units WHERE UPPER(code) = ? LIMIT 1");
        $up = strtoupper($unit_id);
        $stmtUnit->execute([$up]);
        $u = $stmtUnit->fetch();
        if ($u) {
            $sql .= " AND p.unit_id = ?";
            $params[] = $u['id'];
        }
    }

    $sql .= " ORDER BY p.name ASC LIMIT " . intval($limit);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($students);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
