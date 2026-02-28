<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$class_id = $_GET['id'] ?? 0;

try {
    if (!$class_id) {
        throw new Exception("Class ID required");
    }

    $sql = "
        SELECT 
            c.id, c.name, c.level_id, c.capacity,
            l.name as level_name, 
            l.unit_id,
            u.name as unit_name,
            u.code as unit_code,
            p.name as homeroom,
            p.id as homeroom_teacher_person_id,
            he.id as homeroom_teacher_id
        FROM acad_classes c
        JOIN acad_class_levels l ON c.level_id = l.id
        JOIN core_units u ON l.unit_id = u.id
        LEFT JOIN core_people p ON c.homeroom_teacher_id = p.id
        LEFT JOIN hr_employees he ON p.id = he.person_id AND he.employment_status != 'RESIGNED'
        WHERE c.id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        http_response_code(404);
        echo json_encode(['error' => 'Class not found']);
        exit;
    }

    echo json_encode($class);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
