<?php
// api/get_employee_detail.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Employee ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.id as employee_id,
            e.employee_number,
            e.join_date,
            e.employment_status,
            e.employee_type as department,
            e.position_id,
            p.name,
            p.gender,
            p.birth_place,
            p.birth_date,
            p.address,
            p.phone,
            p.email,
            p.status,
            p.identity_number,
            p.custom_attributes,
            (SELECT GROUP_CONCAT(unit_id) FROM hr_unit_access WHERE employee_id = e.id) as unit_access_ids
        FROM hr_employees e
        JOIN core_people p ON e.person_id = p.id
        WHERE e.id = ?
    ");
    
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        // Decode custom_attributes
        $custom = json_decode($employee['custom_attributes'] ?? '{}', true) ?? [];
        
        // Merge custom attributes into the main array for easier frontend handling
        $employee = array_merge($employee, $custom);
        
        // Normalize Department for frontend (ACADEMIC -> Akademik, NON_ACADEMIC -> Non Akademik)
        $employee['department'] = ($employee['department'] === 'ACADEMIC') ? 'Akademik' : 'Non Akademik';
        
        echo json_encode($employee);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Pegawai tidak ditemukan']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
