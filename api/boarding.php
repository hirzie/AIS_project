<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? 'get_students';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'get_students') {
        $gender = $_GET['gender'] ?? 'all';
        
        // Query santri yang status asramanya aktif (Ya/1) di data induk
        $sql = "
            SELECT 
                p.id,
                p.identity_number as nis,
                p.name,
                p.gender,
                p.status as student_status,
                c.name as class_name,
                bs.room_name,
                bs.halaqoh_name,
                bs.halaqoh_id,
                h.name as halaqoh_db_name,
                uh.name as halaqoh_ustadz_name,
                m.name as musyrif_name,
                bs.musyrif_id,
                bs.boarding_status,
                bs.remarks
            FROM core_people p
            LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
            LEFT JOIN acad_classes c ON sc.class_id = c.id
            LEFT JOIN boarding_students bs ON p.id = bs.student_id
            LEFT JOIN boarding_halaqoh h ON bs.halaqoh_id = h.id
            LEFT JOIN core_people uh ON h.ustadz_id = uh.id
            LEFT JOIN core_people m ON bs.musyrif_id = m.id
            WHERE p.type = 'STUDENT' 
            AND EXISTS (
                SELECT 1 FROM core_custom_values cv
                JOIN core_custom_fields cf ON cv.custom_field_id = cf.id
                WHERE cv.entity_id = p.id 
                AND cf.field_key = 'asrama_status' 
                AND (cv.field_value = '1' OR cv.field_value = 'Ya')
            )
        ";

        $params = [];
        
        if ($gender !== 'all') {
            $g = strtolower(trim($gender));
            $acceptable = [];
            if (in_array($g, ['l','male','m'])) {
                $acceptable = ['L', 'Male', 'MALE'];
            } elseif (in_array($g, ['p','female','f'])) {
                $acceptable = ['P', 'Female', 'FEMALE'];
            } else {
                $acceptable = [$gender];
            }
            $placeholders = implode(',', array_fill(0, count($acceptable), '?'));
            $sql .= " AND p.gender IN ($placeholders)";
            $params = array_merge($params, $acceptable);
        }
        
        $sql .= " ORDER BY p.name ASC";

        // DEBUG: Log the final SQL and count
        // error_log("Boarding Query: " . $sql);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true, 
            'data' => $students,
            'debug' => [
                'count' => count($students),
                'gender' => $gender,
                'php_version' => PHP_VERSION
            ]
        ]);

    } elseif ($action === 'save_details' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $student_id = $input['student_id'] ?? null;
        
        if (!$student_id) throw new Exception("Student ID required");

        $sql = "INSERT INTO boarding_students 
                (student_id, room_name, halaqoh_id, musyrif_id, boarding_status, remarks) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                room_name = VALUES(room_name),
                halaqoh_id = VALUES(halaqoh_id),
                musyrif_id = VALUES(musyrif_id),
                boarding_status = VALUES(boarding_status),
                remarks = VALUES(remarks)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $student_id,
            $input['room_name'] ?? null,
            $input['halaqoh_id'] ?? null,
            $input['musyrif_id'] ?? null,
            $input['boarding_status'] ?? 'ACTIVE',
            $input['remarks'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'Data asrama berhasil disimpan']);

    } elseif ($action === 'get_musyrif') {
        // Ambil data guru/staff untuk pilihan musyrif
        $stmt = $pdo->prepare("SELECT id, name FROM core_people WHERE type IN ('TEACHER', 'STAFF') AND status = 'ACTIVE' ORDER BY name ASC");
        $stmt->execute();
        $musyrif = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $musyrif]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
