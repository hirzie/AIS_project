<?php
// api/get_staff_list.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
if (function_exists('ais_ensure_allowed_units')) { ais_ensure_allowed_units($pdo); }

try {
    $unit_id = $_GET['unit_id'] ?? null;
    $role = $_GET['role'] ?? null;

    $isAdmin = in_array(strtoupper(trim($_SESSION['role'] ?? '')), ['SUPERADMIN','ADMIN']);
    if (!$isAdmin && ($unit_id === null || $unit_id === 'all' || $unit_id === '')) {
        $map = $_SESSION['allowed_units'] ?? [];
        $allowedCodes = array_keys(array_filter(is_array($map) ? $map : []));
        $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowedCodes);
        if (count($allowedUp) === 0) {
            echo json_encode([]);
            exit;
        }
        $inPlaceholders = implode(',', array_fill(0, count($allowedUp), '?'));
        $stmtIds = $pdo->prepare("SELECT id FROM core_units WHERE UPPER(code) IN ($inPlaceholders)");
        $stmtIds->execute($allowedUp);
        $unitIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
        if (count($unitIds) === 0) {
            echo json_encode([]);
            exit;
        }
        $place = implode(',', array_fill(0, count($unitIds), '?'));
        $sql = "
            SELECT 
                e.id, 
                p.name, 
                e.employee_number,
                e.employee_type as role
            FROM hr_employees e
            JOIN core_people p ON e.person_id = p.id
            JOIN hr_unit_access hua ON e.id = hua.employee_id
            WHERE e.employment_status != 'RESIGNED'
            AND hua.unit_id IN ($place)
            ORDER BY p.name ASC
        ";
        $params = $unitIds;
    } elseif ($unit_id && $unit_id !== 'all') {
        // MODIFIED: Filter based on hr_unit_access (Explicit Unit Access)
        // This is much simpler and decoupled from organizational chart (positions)
        $sql = "
            SELECT 
                e.id, 
                p.name, 
                e.employee_number,
                e.employee_type as role
            FROM hr_employees e
            JOIN core_people p ON e.person_id = p.id
            JOIN hr_unit_access hua ON e.id = hua.employee_id
            WHERE e.employment_status != 'RESIGNED'
            AND hua.unit_id = ?
            ORDER BY p.name ASC
        ";
        $params = [$unit_id];
    } else {
        // If admin or explicit 'all', return all staff
        $sql = "
            SELECT 
                e.id, 
                p.name, 
                e.employee_number,
                e.employee_type as role
            FROM hr_employees e
            JOIN core_people p ON e.person_id = p.id
            WHERE e.employment_status != 'RESIGNED'
            ORDER BY p.name ASC
        ";
        $params = [];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize role for JS compatibility
    foreach ($staff as &$s) {
        if ($s['role'] === 'ACADEMIC') $s['role'] = 'TEACHER';
    }
    
    echo json_encode($staff);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
