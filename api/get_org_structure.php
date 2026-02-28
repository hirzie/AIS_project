<?php
// api/get_org_structure.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $sql = "
        SELECT 
            p.id,
            p.name as position_name,
            p.unit_id,
            u.name as unit_name,
            u.receipt_code as prefix,
            
            -- Employee Info
            he.id as employee_id,
            he.employee_number as nip,
            he.sk_number,
            cp.name as official_name,
            
            -- Parent Info
            parent.name as parent_name
            
        FROM hr_positions p
        LEFT JOIN core_units u ON p.unit_id = u.id
        LEFT JOIN hr_employees he ON p.id = he.position_id AND he.employment_status != 'RESIGNED'
        LEFT JOIN core_people cp ON he.person_id = cp.id
        LEFT JOIN hr_positions parent ON p.parent_id = parent.id
        ORDER BY p.unit_id, p.level, p.name
    ";
    
    $stmt = $pdo->query($sql);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($positions);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
