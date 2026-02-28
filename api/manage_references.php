<?php
error_reporting(0); // Suppress warnings to ensure valid JSON response
ob_start(); // Start output buffering
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper for JSON Response
function jsonResponse($success, $message, $data = null) {
    if (ob_get_length()) ob_clean(); // Clean any previous output
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    if ($action === 'get_fields') {
        $entity_type = $_GET['entity_type'] ?? 'STUDENT';
        $stmt = $pdo->prepare("SELECT * FROM core_custom_fields WHERE entity_type = ? ORDER BY id ASC");
        $stmt->execute([$entity_type]);
        $rows = $stmt->fetchAll();
        
        // Decode options
        foreach ($rows as &$row) {
            if ($row['field_options']) {
                $row['field_options'] = json_decode($row['field_options'], true);
            }
        }
        
        jsonResponse(true, 'Fields loaded', $rows);

    } elseif ($action === 'save_field' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['field_label']) || empty($data['field_key'])) {
            jsonResponse(false, 'Label dan Key harus diisi');
        }
        
        // Clean Key (lowercase, no spaces)
        $field_key = preg_replace('/[^a-z0-9_]/', '', strtolower($data['field_key']));
        $options = isset($data['field_options']) ? json_encode($data['field_options']) : null;
        
        if (!empty($data['id'])) {
            // Update
            $sql = "UPDATE core_custom_fields SET field_label = ?, field_type = ?, field_options = ?, is_active = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['field_label'], 
                $data['field_type'], 
                $options, 
                $data['is_active'] ?? 1, 
                $data['id']
            ]);
        } else {
            // Insert
            $sql = "INSERT INTO core_custom_fields (entity_type, field_key, field_label, field_type, field_options, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['entity_type'] ?? 'STUDENT',
                $field_key,
                $data['field_label'],
                $data['field_type'],
                $options,
                $data['is_active'] ?? 1
            ]);
        }
        
        jsonResponse(true, 'Field berhasil disimpan');

    } elseif ($action === 'delete_field' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) jsonResponse(false, 'ID Required');
        
        $stmt = $pdo->prepare("DELETE FROM core_custom_fields WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        jsonResponse(true, 'Field dihapus');

    } elseif ($action === 'get_unit_principals') {
        // Fetch Units
        $stmt = $pdo->query("SELECT id, code, name FROM core_units ORDER BY id ASC");
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch Positions
        $stmtPos = $pdo->query("
            SELECT up.unit_id, up.position, up.person_id, p.name 
            FROM acad_unit_positions up 
            JOIN core_people p ON up.person_id = p.id
        ");
        $positions = $stmtPos->fetchAll(PDO::FETCH_ASSOC);
        
        // Map positions to units
        foreach ($units as &$u) {
            $u['principal_id'] = null;
            $u['principal_name'] = null;
            $u['vice_principal_id'] = null;
            $u['vice_principal_name'] = null;
            
            foreach ($positions as $p) {
                if ($p['unit_id'] == $u['id']) {
                    if ($p['position'] === 'PRINCIPAL') {
                        $u['principal_id'] = $p['person_id'];
                        $u['principal_name'] = $p['name'];
                    } elseif ($p['position'] === 'VICE_PRINCIPAL') {
                        $u['vice_principal_id'] = $p['person_id'];
                        $u['vice_principal_name'] = $p['name'];
                    }
                }
            }
        }
        
        jsonResponse(true, 'Data loaded', $units);

    } elseif ($action === 'get_potential_principals') {
        // Fetch potential candidates (Teachers, Staff, etc.)
        // Check if type column exists
        $hasType = false;
        try {
            $pdo->query("SELECT type FROM core_people LIMIT 1");
            $hasType = true;
        } catch (Exception $e) {}

        if ($hasType) {
            $stmt = $pdo->query("SELECT id, name, type FROM core_people WHERE type IN ('TEACHER','STAFF','EMPLOYEE','ACADEMIC') ORDER BY name ASC");
        } else {
            $stmt = $pdo->query("SELECT id, name FROM core_people ORDER BY name ASC");
        }
        
        $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, 'Candidates loaded', $people);

    } elseif ($action === 'save_unit_principals' && $method === 'POST') {
        // Handle FormData (since frontend uses FormData for this call)
        $unit_id = $_POST['unit_id'] ?? null;
        if (!$unit_id) jsonResponse(false, 'Unit ID required');
        
        $pdo->beginTransaction();
        
        try {
            // Handle Principal
            $principal_id = $_POST['principal_id'] ?? null;
            if ($principal_id === 'null' || $principal_id === 'undefined') $principal_id = null;
            
            // Delete existing PRINCIPAL for this unit (enforce one Principal per unit)
            $stmtDel = $pdo->prepare("DELETE FROM acad_unit_positions WHERE unit_id = ? AND position = 'PRINCIPAL'");
            $stmtDel->execute([$unit_id]);

            if ($principal_id) {
                $sql = "INSERT INTO acad_unit_positions (unit_id, person_id, position) VALUES (?, ?, 'PRINCIPAL')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$unit_id, $principal_id]);
            }
            
            // Handle Vice Principal
            $vice_principal_id = $_POST['vice_principal_id'] ?? null;
            if ($vice_principal_id === 'null' || $vice_principal_id === 'undefined') $vice_principal_id = null;
            
            // Delete existing VICE_PRINCIPAL for this unit
            $stmtDel = $pdo->prepare("DELETE FROM acad_unit_positions WHERE unit_id = ? AND position = 'VICE_PRINCIPAL'");
            $stmtDel->execute([$unit_id]);

            if ($vice_principal_id) {
                $sql = "INSERT INTO acad_unit_positions (unit_id, person_id, position) VALUES (?, ?, 'VICE_PRINCIPAL')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$unit_id, $vice_principal_id]);
            }
            
            $pdo->commit();
            jsonResponse(true, 'Jabatan berhasil disimpan');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Database error: ' . $e->getMessage());
        }

    } else {
        jsonResponse(false, 'Invalid Action');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Server Error: ' . $e->getMessage());
}
