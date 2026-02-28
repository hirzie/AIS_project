<?php
// api/get_employees.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$unit_code = $_GET['unit'] ?? 'all';

try {
    $sql = "
        SELECT 
            e.id as employee_id,
            e.employee_number,
            p.name,
            p.phone,
            p.gender,
            p.birth_place,
            p.birth_date,
            p.status,
            e.employee_type,
            pos.name as position,
            GROUP_CONCAT(COALESCE(u.receipt_code, u.code) SEPARATOR ', ') as access_units,
            p.custom_attributes
        FROM hr_employees e
        JOIN core_people p ON e.person_id = p.id
        LEFT JOIN hr_positions pos ON e.position_id = pos.id
        LEFT JOIN hr_unit_access hua ON e.id = hua.employee_id
        LEFT JOIN core_units u ON hua.unit_id = u.id
    ";

    // Filter by Unit Access if not 'all'
    if ($unit_code !== 'all') {
        $sql .= " WHERE hua.unit_id = (SELECT id FROM core_units WHERE code = ?) ";
        $sql .= " GROUP BY e.id "; // Grouping karena join hr_unit_access bisa duplikat row
        $stmt = $pdo->prepare($sql);
        $stmt->execute([strtoupper($unit_code)]);
    } else {
        $sql .= " GROUP BY e.id ";
        $stmt = $pdo->query($sql);
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($employees as &$emp) {
        $custom = json_decode($emp['custom_attributes'] ?? '{}', true);
        $emp['division'] = $custom['division'] ?? null;
        $emp['mobile_phone'] = isset($custom['mobile_phone']) ? $custom['mobile_phone'] : null;
        $teams = $custom['teams'] ?? [];
        $emp['teams'] = is_array($teams) ? $teams : [];
        unset($emp['custom_attributes']);
    }
    echo json_encode($employees);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
