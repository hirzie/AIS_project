<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? 'get_permissions';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'get_permissions') {
        $gender = $_GET['gender'] ?? 'all';
        $status = $_GET['status'] ?? 'all';
        
        $sql = "
            SELECT 
                bp.*,
                p.name as student_name,
                p.identity_number as nis,
                p.gender
            FROM boarding_permissions bp
            JOIN core_people p ON bp.student_id = p.id
            WHERE 1=1
        ";
        
        $params = [];
        if ($gender !== 'all') {
            $sql .= " AND p.gender = ?";
            $params[] = $gender;
        }
        if ($status !== 'all') {
            $sql .= " AND bp.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY bp.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $permissions]);

    } elseif ($action === 'save_permission' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['student_id']) || empty($input['permission_type'])) {
            throw new Exception("Data tidak lengkap");
        }

        $sql = "INSERT INTO boarding_permissions 
                (student_id, permission_type, reason, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['student_id'],
            $input['permission_type'],
            $input['reason'],
            $input['start_date'],
            $input['end_date'],
            $input['status'] ?? 'PENDING'
        ]);

        echo json_encode(['success' => true, 'message' => 'Izin berhasil dicatat']);

    } elseif ($action === 'update_status' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$id || !$status) throw new Exception("ID dan Status diperlukan");

        $sql = "UPDATE boarding_permissions SET status = ?";
        $params = [$status];
        
        if ($status === 'RETURNED') {
            $sql .= ", return_date = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Status izin diperbarui']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
