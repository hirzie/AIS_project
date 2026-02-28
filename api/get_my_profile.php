<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id, 
            u.username, 
            u.email as user_email, 
            u.role, 
            u.people_id,
            p.id as person_id, 
            p.name as person_name, 
            p.identity_number, 
            p.phone as person_phone, 
            p.address as person_address, 
            p.email as person_email,
            p.gender, 
            p.birth_place, 
            p.birth_date, 
            p.photo_url,
            p.custom_attributes
        FROM core_users u 
        LEFT JOIN core_people p ON u.people_id = p.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
