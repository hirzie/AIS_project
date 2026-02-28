<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? 'get_records';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'get_records') {
        $gender = $_GET['gender'] ?? 'all';
        $type = $_GET['type'] ?? 'all'; // VIOLATION or ACHIEVEMENT
        
        $sql = "
            SELECT 
                dr.*,
                p.name as student_name,
                p.identity_number as nis,
                p.gender,
                c.name as class_name
            FROM boarding_discipline_records dr
            JOIN core_people p ON dr.student_id = p.id
            LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
            LEFT JOIN acad_classes c ON sc.class_id = c.id
            WHERE 1=1
        ";
        
        $params = [];
        if ($gender !== 'all') {
            $sql .= " AND p.gender = ?";
            $params[] = $gender;
        }
        if ($type !== 'all') {
            $sql .= " AND dr.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY dr.record_date DESC, dr.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $records]);

    } elseif ($action === 'save_record' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['student_id']) || empty($input['type']) || empty($input['category'])) {
            throw new Exception("Data tidak lengkap");
        }

        $sql = "INSERT INTO boarding_discipline_records 
                (student_id, type, category, points, description, record_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['student_id'],
            $input['type'],
            $input['category'],
            $input['points'] ?? 0,
            $input['description'] ?? null,
            $input['record_date'] ?? date('Y-m-d')
        ]);

        echo json_encode(['success' => true, 'message' => 'Catatan berhasil disimpan']);

    } elseif ($action === 'get_summary') {
        // Mendapatkan ringkasan poin per santri
        $gender = $_GET['gender'] ?? 'all';
        
        $sql = "
            SELECT 
                p.id, p.name, p.identity_number as nis, p.gender,
                c.name as class_name,
                COALESCE(SUM(CASE WHEN dr.type = 'VIOLATION' THEN dr.points ELSE 0 END), 0) as total_violations,
                COALESCE(SUM(CASE WHEN dr.type = 'ACHIEVEMENT' THEN dr.points ELSE 0 END), 0) as total_achievements
            FROM core_people p
            JOIN core_custom_values cv ON p.id = cv.entity_id 
            JOIN core_custom_fields cf ON cv.custom_field_id = cf.id AND cf.field_key = 'asrama_status'
            LEFT JOIN boarding_discipline_records dr ON p.id = dr.student_id
            LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
            LEFT JOIN acad_classes c ON sc.class_id = c.id
            WHERE p.type = 'STUDENT' AND cv.field_value = '1'
        ";
        
        $params = [];
        if ($gender !== 'all') {
            $sql .= " AND p.gender = ?";
            $params[] = $gender;
        }
        
        $sql .= " GROUP BY p.id, p.name, p.identity_number, p.gender, c.name ORDER BY total_violations DESC, total_achievements DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $summary]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
