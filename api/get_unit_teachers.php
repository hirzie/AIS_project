<?php
// api/get_unit_teachers.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

$unit_code = $_GET['unit'] ?? 'all';
$type = $_GET['type'] ?? 'ACADEMIC'; // Default to ACADEMIC

try {
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if ($unit_code !== 'all' && !in_array($role, ['SUPERADMIN','ADMIN'])) {
        if (!function_exists('ais_is_unit_allowed') || !ais_is_unit_allowed($pdo, $unit_code)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
            exit;
        }
    }
    // Base Query
    $sql = "
        SELECT 
            e.id as employee_id,
            p.id as person_id,
            p.name,
            e.employee_number,
            e.employee_type
        FROM hr_employees e
        JOIN core_people p ON e.person_id = p.id
        LEFT JOIN hr_unit_access hua ON e.id = hua.employee_id
        LEFT JOIN core_units u ON hua.unit_id = u.id
    ";

    $where = ["e.employee_type = ?"];
    $params = [$type];

    if ($unit_code !== 'all') {
        $where[] = "UPPER(u.code) = ?";
        $params[] = strtoupper($unit_code);
    }

    $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY e.id ORDER BY p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($teachers);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

