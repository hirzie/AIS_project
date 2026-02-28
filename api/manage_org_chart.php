<?php
// api/manage_org_chart.php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'create') {
        $name = $input['position_name'];
        $parent_id = $input['parent_id'] ?? null; // Can be null for Root
        $unit_id = $input['unit_id'] ?? null;
        $sub_unit = $input['sub_unit'] ?? null;
        $sort_order = $input['sort_order'] ?? 0;
        $vertical_spacer = $input['vertical_spacer'] ?? 0;
        $horizontal_spacer = $input['horizontal_spacer'] ?? 0;
        
        // Auto-determine level
        $level = 1;
        if ($parent_id) {
            $stmt = $pdo->prepare("SELECT level FROM hr_positions WHERE id = ?");
            $stmt->execute([$parent_id]);
            $parentLevel = $stmt->fetchColumn();
            $level = $parentLevel + 1;
        }

        $stmt = $pdo->prepare("INSERT INTO hr_positions (name, parent_id, unit_id, sub_unit, level, sort_order, vertical_spacer, horizontal_spacer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $parent_id, $unit_id, $sub_unit, $level, $sort_order, $vertical_spacer, $horizontal_spacer]);
        
        echo json_encode(['success' => true]);

    } elseif ($action === 'update') {
        $id = $input['id'];
        $name = $input['position_name'];
        $unit_id = $input['unit_id'] ?? null; 
        $sub_unit = $input['sub_unit'] ?? null;
        $parent_id = $input['parent_id'] ?? null; 
        $sort_order = $input['sort_order'] ?? 0;
        $vertical_spacer = $input['vertical_spacer'] ?? 0;
        $horizontal_spacer = $input['horizontal_spacer'] ?? 0;

        // Optional: Check for circular dependency if needed, but skipping for now for speed.
        
        $stmt = $pdo->prepare("UPDATE hr_positions SET name = ?, unit_id = ?, sub_unit = ?, parent_id = ?, sort_order = ?, vertical_spacer = ?, horizontal_spacer = ? WHERE id = ?");
        $stmt->execute([$name, $unit_id, $sub_unit, $parent_id, $sort_order, $vertical_spacer, $horizontal_spacer, $id]);
        
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id = $input['id'];
        
        // Check for children
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM hr_positions WHERE parent_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus jabatan ini karena memiliki bawahan.']);
            exit;
        }

        // Check for assigned employees
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM hr_employees WHERE position_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus jabatan ini karena sedang diisi oleh pegawai.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM hr_positions WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
