<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get raw input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$userId = $_SESSION['user_id'];
$personId = $_SESSION['person_id'] ?? null;

// If person_id is missing in session, try to find it
if (!$personId) {
    $stmt = $pdo->prepare("SELECT people_id FROM core_users WHERE id = ?");
    $stmt->execute([$userId]);
    $personId = $stmt->fetchColumn();
}

if (!$personId) {
    echo json_encode(['success' => false, 'message' => 'User is not linked to a person profile']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fields to update
    $phone = isset($input['phone']) ? trim($input['phone']) : null;
    $email = isset($input['email']) ? trim($input['email']) : null;
    $address = isset($input['address']) ? trim($input['address']) : null;

    // Update core_people
    // We update phone, email, address
    // We also want to ensure custom_attributes has mobile_phone synced with phone for consistency
    
    // First fetch current custom_attributes
    $stmtGet = $pdo->prepare("SELECT custom_attributes FROM core_people WHERE id = ?");
    $stmtGet->execute([$personId]);
    $currentCa = $stmtGet->fetchColumn();
    $ca = $currentCa ? json_decode($currentCa, true) : [];
    if (!is_array($ca)) $ca = [];

    if ($phone !== null) {
        $ca['mobile_phone'] = $phone;
    }

    $newCa = json_encode($ca);

    $sql = "UPDATE core_people SET ";
    $params = [];
    $updates = [];

    if ($phone !== null) {
        $updates[] = "phone = ?";
        $params[] = $phone;
    }
    if ($email !== null) {
        $updates[] = "email = ?";
        $params[] = $email;
    }
    if ($address !== null) {
        $updates[] = "address = ?";
        $params[] = $address;
    }

    $updates[] = "custom_attributes = ?";
    $params[] = $newCa;

    $params[] = $personId; // For WHERE clause

    $sql .= implode(', ', $updates) . " WHERE id = ?";
    
    $stmtUpdate = $pdo->prepare($sql);
    $stmtUpdate->execute($params);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
