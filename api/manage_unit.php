<?php
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
        
        // 1. Fetch Unit Code/Name to check relations
        $stmt = $pdo->prepare("SELECT * FROM core_units WHERE id = ?");
        $stmt->execute([$id]);
        $unit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$unit) {
            echo json_encode(['success' => false, 'message' => 'Unit not found']);
            exit;
        }

        // 2. Check Relations (Students, Classes)
        $relations = [];
        
        // Check Students (Using core_people table)
        // Check if core_people has unit_id (it does based on schema)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_people WHERE unit_id = ? AND status != 'ALUMNI' AND status != 'MUTASI'"); // Count active/inactive but not archived
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) $relations[] = "Data Siswa/Pegawai";

        // Check Classes (Using acad_classes via levels or if direct link exists)
        // Since acad_classes has level_id, and we don't know for sure if levels link to units in DB schema yet,
        // we can check if there are any students in this unit assigned to classes.
        // OR simply rely on the "Student" check above which is quite strong.
        // However, user specifically mentioned "Classes". 
        // If acad_classes doesn't have unit_id, we might skip direct class check OR check levels if they have unit_id.
        // Let's check if 'acad_levels' exists and has unit_id.
        
        $hasLevelUnit = false;
        $checkLevels = $pdo->query("SHOW TABLES LIKE 'acad_levels'");
        if ($checkLevels->rowCount() > 0) {
            $cols = $pdo->query("DESCRIBE acad_levels")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('unit_id', $cols)) {
                $hasLevelUnit = true;
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_levels l JOIN acad_classes c ON l.id = c.level_id WHERE l.unit_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) $relations[] = "Data Kelas";
            }
        }
        
        // Fallback: If no direct class link, checking students is usually enough because classes contain students.
        // But to be thorough as requested:
        if (!$hasLevelUnit) {
            // Check if core_people in this unit are assigned to any class
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM acad_student_classes asc_rel
                JOIN core_people cp ON asc_rel.student_id = cp.id
                WHERE cp.unit_id = ?
            ");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) $relations[] = "Data Kelas (via Siswa)";
        }

        if (!empty($relations)) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus unit ini karena masih terhubung dengan: ' . implode(', ', $relations) . '. Silahkan hapus data terkait terlebih dahulu.']);
            exit;
        }

        // 3. Proceed to Delete
        $stmt = $pdo->prepare("DELETE FROM core_units WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'create' || $action === 'update') {
        $name = $data['name'];
        // unit_level maps to code (TK, SD)
        $code = $data['unit_level'];
        // prefix maps to receipt_code (SDIT)
        $receipt_code = substr($data['prefix'] ?? '', 0, 20); 
        $address = $data['address'] ?? ''; 
        
        // Determine type based on unit_level/code
        $type = ($code === 'YAYASAN') ? 'FOUNDATION' : 'SCHOOL';
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO core_units (name, code, receipt_code, address, type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $code, $receipt_code, $address, $type]);
        } else {
            $stmt = $pdo->prepare("UPDATE core_units SET name = ?, code = ?, receipt_code = ?, address = ?, type = ? WHERE id = ?");
            $stmt->execute([$name, $code, $receipt_code, $address, $type, $data['id']]);
        }
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

