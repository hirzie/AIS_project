<?php
// api/boarding_halaqoh.php
require_once '../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_halaqoh') {
        $gender = $_GET['gender'] ?? 'all';
        $sql = "
            SELECT h.*, p.name as ustadz_name 
            FROM boarding_halaqoh h
            LEFT JOIN core_people p ON h.ustadz_id = p.id
        ";
        $params = [];
        
        if ($gender !== 'all') {
            $sql .= " WHERE h.gender = ?";
            $params[] = $gender;
        }
        
        $sql .= " ORDER BY h.gender ASC, h.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $halaqoh = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $halaqoh]);

    } elseif ($action === 'save_halaqoh') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if ($id) {
            $sql = "UPDATE boarding_halaqoh SET name = ?, gender = ?, ustadz_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['gender'], $input['ustadz_id'] ?? null, $id]);
        } else {
            $sql = "INSERT INTO boarding_halaqoh (name, gender, ustadz_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['gender'], $input['ustadz_id'] ?? null]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_halaqoh') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM boarding_halaqoh WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
