<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? 'get_rooms';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'get_rooms') {
        $gender = $_GET['gender'] ?? 'all';
        $sql = "
            SELECT r.*, p.name as musyrif_name 
            FROM boarding_rooms r
            LEFT JOIN core_people p ON r.musyrif_id = p.id
        ";
        $params = [];
        
        if ($gender !== 'all') {
            $sql .= " WHERE r.gender = ?";
            $params[] = $gender;
        }
        
        $sql .= " ORDER BY r.gender ASC, r.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count occupants for each room
        foreach ($rooms as &$room) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM boarding_students WHERE room_name = ?");
            $stmtCount->execute([$room['name']]);
            $room['occupants_count'] = $stmtCount->fetchColumn();
        }
        
        echo json_encode(['success' => true, 'data' => $rooms]);

    } elseif ($action === 'save_room' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (empty($input['name']) || empty($input['gender'])) {
            throw new Exception("Nama Kamar dan Jenis Kelamin wajib diisi");
        }

        if ($id) {
            $sql = "UPDATE boarding_rooms SET name = ?, gender = ?, capacity = ?, musyrif_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['gender'], $input['capacity'] ?? 0, $input['musyrif_id'] ?? null, $id]);
        } else {
            $sql = "INSERT INTO boarding_rooms (name, gender, capacity, musyrif_id) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['gender'], $input['capacity'] ?? 0, $input['musyrif_id'] ?? null]);
        }

        echo json_encode(['success' => true, 'message' => 'Kamar berhasil disimpan']);

    } elseif ($action === 'delete_room' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) throw new Exception("ID Kamar diperlukan");

        $stmt = $pdo->prepare("DELETE FROM boarding_rooms WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Kamar berhasil dihapus']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
