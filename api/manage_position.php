<?php
// api/manage_position.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'assign') {
        $position_id = $input['position_id'];
        $employee_id = $input['employee_id']; // Can be null to unassign
        $sk_number = $input['sk_number'] ?? null;

        $pdo->beginTransaction();

        // 1. Clear previous assignment for this position
        // Set position_id = NULL for any employee currently holding this position
        $stmt = $pdo->prepare("UPDATE hr_employees SET position_id = NULL WHERE position_id = ?");
        $stmt->execute([$position_id]);

        // 2. If employee selected, assign them
        if ($employee_id) {
            // Check if employee already has another position? 
            // Usually one person one position, but some systems allow multiple.
            // For now, let's assume if they take this new position, they leave the old one?
            // Or maybe just overwrite position_id.
            
            // Update the employee record
            $stmt = $pdo->prepare("UPDATE hr_employees SET position_id = ?, sk_number = ? WHERE id = ?");
            $stmt->execute([$position_id, $sk_number, $employee_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
