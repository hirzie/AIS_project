<?php
// api/manage_employee.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'update_custom_attributes') {
        require_once __DIR__ . '/../includes/guard.php';
        ais_init_session();
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
        if (!$employee_id) { echo json_encode(['success' => false, 'message' => 'Employee ID required']); exit; }
        $stmt = $pdo->prepare("SELECT p.id AS person_id, p.custom_attributes FROM hr_employees e JOIN core_people p ON e.person_id = p.id WHERE e.id = ?");
        $stmt->execute([$employee_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success' => false, 'message' => 'Pegawai tidak ditemukan']); exit; }
        $person_id = (int)$row['person_id'];
        $custom = [];
        try { $custom = json_decode($row['custom_attributes'] ?? '{}', true) ?: []; } catch (\Throwable $e) { $custom = []; }
        $division = isset($data['division']) ? trim((string)$data['division']) : null;
        $teams = $data['teams'] ?? null;
        $set_bk = isset($data['set_bk']) ? !!$data['set_bk'] : null;
        $mobile_phone = isset($data['mobile_phone']) ? trim((string)$data['mobile_phone']) : null;
        if ($division !== null) { $custom['division'] = $division !== '' ? $division : null; }
        if ($mobile_phone !== null) { $custom['mobile_phone'] = $mobile_phone !== '' ? $mobile_phone : null; }
        if ($teams !== null) {
            $custom['teams'] = is_array($teams) ? $teams : array_filter(array_map('trim', preg_split('/[,\s]+/', (string)$teams)));
        }
        if ($set_bk !== null) {
            $arr = $custom['teams'] ?? [];
            if (!is_array($arr)) $arr = [];
            $hasBk = in_array('BK', $arr);
            if ($set_bk && !$hasBk) { $arr[] = 'BK'; }
            if (!$set_bk && $hasBk) { $arr = array_values(array_filter($arr, function($t){ return strtoupper($t) !== 'BK'; })); }
            $custom['teams'] = $arr;
        }
        $st = $pdo->prepare("UPDATE core_people SET custom_attributes = ? WHERE id = ?");
        $st->execute([json_encode($custom), $person_id]);
        echo json_encode(['success' => true, 'data' => ['employee_id' => $employee_id, 'person_id' => $person_id, 'custom_attributes' => $custom]]);
        exit;
    }
    if ($action === 'create') {
        $pdo->beginTransaction();

        // Prepare Custom Attributes
        $custom_attributes = [
            'nickname' => $data['nickname'] ?? null,
            'nuptk' => $data['nuptk'] ?? null,
            'nrg' => $data['nrg'] ?? null,
            'mobile_phone' => $data['mobile_phone'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'religion' => $data['religion'] ?? null,
            'ethnicity' => $data['ethnicity'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'website' => $data['website'] ?? null,
            'notes' => $data['notes'] ?? null,
            'division' => $data['division'] ?? null
        ];

        $allowedTypes = ['ACADEMIC','NON_ACADEMIC','SECURITY'];
        $employee_type = isset($data['employee_type']) && in_array($data['employee_type'], $allowedTypes, true)
            ? $data['employee_type']
            : (($data['department'] ?? '') === 'Akademik' ? 'ACADEMIC' : 'NON_ACADEMIC');
        $people_type = ($employee_type === 'ACADEMIC') ? 'TEACHER' : 'STAFF';
        $stmt = $pdo->prepare("
            INSERT INTO core_people (
                identity_number, name, gender, birth_place, birth_date, 
                address, phone, email, type, status, custom_attributes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['identity_number'] ?? null,
            $data['name'],
            $data['gender'],
            $data['birth_place'] ?? null,
            $data['birth_date'] ?? null,
            $data['address'] ?? null,
            $data['phone'] ?? null, // Home Phone
            $data['email'] ?? null,
            $people_type,
            $data['status'],
            json_encode($custom_attributes)
        ]);
        
        $person_id = $pdo->lastInsertId();

        // 2. Insert into hr_employees
        $stmt = $pdo->prepare("
            INSERT INTO hr_employees (
                person_id, employee_number, sk_number, join_date, 
                employment_status, employee_type, position_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $position_id = !empty($data['position_id']) ? $data['position_id'] : null;
        
        $stmt->execute([
            $person_id,
            $data['employee_number'],
            null, // sk_number not in form yet
            $data['join_date'] ?? null,
            $data['employment_status'],
            $employee_type,
            $position_id
        ]);
        
        $employee_id = $pdo->lastInsertId();

        // 3. Insert Unit Access
        if (!empty($data['unit_access']) && is_array($data['unit_access'])) {
            $uaRole = ($employee_type === 'ACADEMIC') ? 'TEACHER' : (($employee_type === 'SECURITY') ? 'SECURITY' : 'STAFF');
            $stmt = $pdo->prepare("INSERT INTO hr_unit_access (employee_id, unit_id, role, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($data['unit_access'] as $unit_id) {
                $stmt->execute([$employee_id, $unit_id, $uaRole]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $person_id]);

    } elseif ($action === 'update') {
        $employee_id = $data['employee_id'];
        
        if (!$employee_id) {
            throw new Exception("Employee ID is required for update");
        }

        $pdo->beginTransaction();

        // 1. Get person_id
        $stmt = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            throw new Exception("Pegawai tidak ditemukan");
        }
        $person_id = $emp['person_id'];

        // Prepare Custom Attributes
        $custom_attributes = [
            'nickname' => $data['nickname'] ?? null,
            'nuptk' => $data['nuptk'] ?? null,
            'nrg' => $data['nrg'] ?? null,
            'mobile_phone' => $data['mobile_phone'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'religion' => $data['religion'] ?? null,
            'ethnicity' => $data['ethnicity'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'website' => $data['website'] ?? null,
            'notes' => $data['notes'] ?? null,
            'division' => $data['division'] ?? null
        ];

        // 2. Update core_people
        $stmt = $pdo->prepare("
            UPDATE core_people SET
                identity_number = ?, name = ?, gender = ?, birth_place = ?, birth_date = ?, 
                address = ?, phone = ?, email = ?, status = ?, custom_attributes = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['identity_number'] ?? null,
            $data['name'],
            $data['gender'],
            $data['birth_place'] ?? null,
            $data['birth_date'] ?? null,
            $data['address'] ?? null,
            $data['phone'] ?? null, // Home Phone
            $data['email'] ?? null,
            $data['status'],
            json_encode($custom_attributes),
            $person_id
        ]);

        // 3. Update hr_employees
        $stmt = $pdo->prepare("
            UPDATE hr_employees SET
                employee_number = ?, join_date = ?, employment_status = ?, employee_type = ?, position_id = ?
            WHERE id = ?
        ");

        $allowedTypes = ['ACADEMIC','NON_ACADEMIC','SECURITY'];
        $employee_type = isset($data['employee_type']) && in_array($data['employee_type'], $allowedTypes, true)
            ? $data['employee_type']
            : (($data['department'] ?? '') === 'Akademik' ? 'ACADEMIC' : 'NON_ACADEMIC');
        $position_id = !empty($data['position_id']) ? $data['position_id'] : null;
        
        $stmt->execute([
            $data['employee_number'],
            $data['join_date'] ?? null,
            $data['employment_status'],
            $employee_type,
            $position_id,
            $employee_id
        ]);

        // 4. Update Unit Access (Delete all, then re-insert)
        $stmt = $pdo->prepare("DELETE FROM hr_unit_access WHERE employee_id = ?");
        $stmt->execute([$employee_id]);

        if (!empty($data['unit_access']) && is_array($data['unit_access'])) {
            $uaRole = ($employee_type === 'ACADEMIC') ? 'TEACHER' : (($employee_type === 'SECURITY') ? 'SECURITY' : 'STAFF');
            $stmt = $pdo->prepare("INSERT INTO hr_unit_access (employee_id, unit_id, role, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($data['unit_access'] as $unit_id) {
                $stmt->execute([$employee_id, $unit_id, $uaRole]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Data pegawai berhasil diperbarui']);

    } elseif ($action === 'delete') {
        $employee_id = $data['employee_id'];
        
        if (!$employee_id) {
            throw new Exception("Employee ID is required");
        }

        $pdo->beginTransaction();

        // 1. Get person_id first
        $stmt = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            throw new Exception("Pegawai tidak ditemukan");
        }
        $person_id = $emp['person_id'];

        // 2. Delete from hr_employees
        $stmt = $pdo->prepare("DELETE FROM hr_employees WHERE id = ?");
        $stmt->execute([$employee_id]);

        // 3. Delete from core_people
        $stmt = $pdo->prepare("DELETE FROM core_people WHERE id = ?");
        $stmt->execute([$person_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Data pegawai berhasil dihapus']);

    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Check for Integrity Constraint Violation
    if ($e instanceof PDOException && $e->getCode() == '23000') {
        // Return 200 OK but with error field to handle it gracefully in frontend logic if needed,
        // OR return 400. Let's return 400 to match standard API practices.
        http_response_code(400); 
        echo json_encode([
            'success' => false,
            'error' => 'Tidak dapat menghapus data ini karena masih digunakan di modul lain (Jadwal, Kelas, atau Akses Unit). Silakan non-aktifkan statusnya saja.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
