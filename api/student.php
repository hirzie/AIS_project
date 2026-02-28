<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/guard.php';

$action = $_GET['action'] ?? '';

if ($action == 'search') {
    $q = $_GET['q'] ?? '';
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // Search by name or identity_number (NIS)
    $sql = "SELECT p.id, p.name, p.identity_number, c.name as class_name 
            FROM core_people p 
            LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
            LEFT JOIN acad_classes c ON sc.class_id = c.id
            WHERE (p.name LIKE ? OR p.identity_number LIKE ?) 
            AND p.type = 'STUDENT'
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$q%", "%$q%"]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $students]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>