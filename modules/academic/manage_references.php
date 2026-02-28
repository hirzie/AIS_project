<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Helper to get POST data
    function getJsonInput() {
        return json_decode(file_get_contents('php://input'), true);
    }

    // Ensure core_custom_fields table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `core_custom_fields` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `entity_type` varchar(50) NOT NULL DEFAULT 'STUDENT',
        `field_key` varchar(50) NOT NULL,
        `field_label` varchar(100) NOT NULL,
        `field_type` varchar(20) NOT NULL DEFAULT 'TEXT',
        `field_options` text DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_entity_key` (`entity_type`,`field_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($action === 'get_fields') {
        $entity_type = $_GET['entity_type'] ?? 'STUDENT';
        $stmt = $pdo->prepare("SELECT * FROM core_custom_fields WHERE entity_type = ? ORDER BY id ASC");
        $stmt->execute([$entity_type]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($fields as &$f) {
            if ($f['field_type'] === 'DROPDOWN' && !empty($f['field_options'])) {
                $f['field_options'] = json_decode($f['field_options'], true);
            } else {
                $f['field_options'] = [];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $fields]);
    }
    
    elseif ($action === 'save_field' && $method === 'POST') {
        $data = getJsonInput();
        $id = $data['id'] ?? null;
        $entity_type = $data['entity_type'] ?? 'STUDENT';
        $field_key = preg_replace('/[^a-z0-9_]/', '', strtolower($data['field_key']));
        $field_label = $data['field_label'];
        $field_type = $data['field_type'];
        $field_options = isset($data['field_options']) ? json_encode($data['field_options']) : null;
        $is_active = !empty($data['is_active']) ? 1 : 0;
        
        if ($id) {
            $stmt = $pdo->prepare("UPDATE core_custom_fields SET field_label=?, field_type=?, field_options=?, is_active=? WHERE id=?");
            $stmt->execute([$field_label, $field_type, $field_options, $is_active, $id]);
        } else {
            // Check key uniqueness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_custom_fields WHERE entity_type=? AND field_key=?");
            $stmt->execute([$entity_type, $field_key]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Key field sudah digunakan']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO core_custom_fields (entity_type, field_key, field_label, field_type, field_options, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$entity_type, $field_key, $field_label, $field_type, $field_options, $is_active]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Field berhasil disimpan']);
    }
    
    elseif ($action === 'delete_field' && $method === 'POST') {
        $data = getJsonInput();
        $id = $data['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM core_custom_fields WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Field dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        }
    }
    
    elseif ($action === 'get_unit_principals') {
        // Fetch all units
        $stmt = $pdo->query("SELECT id, name, code, unit_level FROM acad_units ORDER BY id ASC");
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For each unit, fetch active principal and vice principal from acad_unit_positions
        foreach ($units as &$unit) {
            $unit['principal_id'] = null;
            $unit['principal_name'] = null;
            $unit['vice_principal_id'] = null;
            $unit['vice_principal_name'] = null;
            
            // Fetch KEPALA
            $stmtP = $pdo->prepare("
                SELECT p.person_id, u.name 
                FROM acad_unit_positions p
                JOIN core_users u ON p.person_id = u.id
                WHERE p.unit_id = ? AND p.position = 'KEPALA' AND p.is_active = 1
                LIMIT 1
            ");
            $stmtP->execute([$unit['id']]);
            $resP = $stmtP->fetch(PDO::FETCH_ASSOC);
            if ($resP) {
                $unit['principal_id'] = $resP['person_id'];
                $unit['principal_name'] = $resP['name'];
            }
            
            // Fetch WAKASEK
            $stmtVP = $pdo->prepare("
                SELECT p.person_id, u.name 
                FROM acad_unit_positions p
                JOIN core_users u ON p.person_id = u.id
                WHERE p.unit_id = ? AND p.position = 'WAKASEK' AND p.is_active = 1
                LIMIT 1
            ");
            $stmtVP->execute([$unit['id']]);
            $resVP = $stmtVP->fetch(PDO::FETCH_ASSOC);
            if ($resVP) {
                $unit['vice_principal_id'] = $resVP['person_id'];
                $unit['vice_principal_name'] = $resVP['name'];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $units]);
    }
    
    elseif ($action === 'get_potential_principals') {
        // Fetch active users who could be principals/teachers
        // We assume any STAFF, TEACHER, ACADEMIC, PRINCIPAL role
        // For simplicity, just fetch all active users except PARENT/STUDENT if possible, or just all.
        // Let's filter slightly to avoid thousands of students.
        $stmt = $pdo->query("
            SELECT id, name, role 
            FROM core_users 
            WHERE status = 'active' 
              AND role NOT IN ('STUDENT', 'PARENT')
            ORDER BY name ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $users]);
    }
    
    elseif ($action === 'save_unit_principal' && $method === 'POST') {
        $data = getJsonInput();
        $unit_id = $data['unit_id'];
        $principal_id = $data['principal_id'];
        $vice_principal_id = $data['vice_principal_id']; // Can be null
        
        // Helper to update position
        function updatePosition($pdo, $unit_id, $person_id, $position) {
            // Deactivate all existing for this unit/position
            $stmt = $pdo->prepare("UPDATE acad_unit_positions SET is_active = 0 WHERE unit_id = ? AND position = ?");
            $stmt->execute([$unit_id, $position]);
            
            if ($person_id) {
                // Check if this person already has an entry (active or inactive)
                // Actually, simplest is to just insert new active record or update existing one to active
                // But we just deactivated all. So we can insert a new one or reactivate.
                // Let's insert new to keep history, or update if we want to be cleaner.
                // Let's try to reuse if exists to avoid table bloat, or just insert. 
                // Given the schema has start_date/end_date, insert is better for history.
                // But for now, let's just UPSERT logic:
                // Find if there is a record for this person+unit+position
                $stmtCheck = $pdo->prepare("SELECT id FROM acad_unit_positions WHERE unit_id = ? AND person_id = ? AND position = ?");
                $stmtCheck->execute([$unit_id, $person_id, $position]);
                $existingId = $stmtCheck->fetchColumn();
                
                if ($existingId) {
                    $stmtUpd = $pdo->prepare("UPDATE acad_unit_positions SET is_active = 1, updated_at = NOW() WHERE id = ?");
                    $stmtUpd->execute([$existingId]);
                } else {
                    $stmtIns = $pdo->prepare("INSERT INTO acad_unit_positions (unit_id, person_id, position, is_active, start_date) VALUES (?, ?, ?, 1, CURDATE())");
                    $stmtIns->execute([$unit_id, $person_id, $position]);
                }
            }
        }
        
        updatePosition($pdo, $unit_id, $principal_id, 'KEPALA');
        updatePosition($pdo, $unit_id, $vice_principal_id, 'WAKASEK');
        
        echo json_encode(['success' => true, 'message' => 'Jabatan berhasil disimpan']);
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>