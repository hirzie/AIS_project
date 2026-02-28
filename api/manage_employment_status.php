<?php
// api/manage_employment_status.php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action']);
    exit;
}

try {
    if ($action === 'delete') {
        $id = $data['id'];
        
        // 1. Check if used in hr_employees
        // Assuming hr_employees.employment_status stores the NAME or ID. 
        // Ideally we should check both or know the schema.
        // Let's fetch the name first.
        $stmt = $pdo->prepare("SELECT name FROM hr_employment_statuses WHERE id = ?");
        $stmt->execute([$id]);
        $status = $stmt->fetch();
        
        if ($status) {
            $name = $status['name'];
            
            // Check usage by Name (if legacy stores string)
            $check = $pdo->prepare("SELECT COUNT(*) FROM hr_employees WHERE employment_status = ?");
            $check->execute([$name]);
            if ($check->fetchColumn() > 0) {
                 echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus status ini karena sedang digunakan oleh data pegawai.']);
                 exit;
            }
        }

        // Proceed to Delete
        $stmt = $pdo->prepare("DELETE FROM hr_employment_statuses WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'create' || $action === 'update') {
        $name = $data['name'];
        $description = $data['description'] ?? '';
        $is_active = $data['is_active'] ?? 1;
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO hr_employment_statuses (name, description, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $is_active]);
        } else {
            $stmt = $pdo->prepare("UPDATE hr_employment_statuses SET name = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $description, $is_active, $data['id']]);
        }
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
