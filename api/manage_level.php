<?php
// api/manage_level.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($method === 'POST') {
        // Create or Update Level
        $id = $input['id'] ?? null;
        $unit_id = $input['unit_id'];
        $name = $input['name'];
        $order_index = $input['order_index'] ?? 0;

        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE acad_class_levels SET name = ?, order_index = ? WHERE id = ?");
            $stmt->execute([$name, $order_index, $id]);
            echo json_encode(['success' => true, 'message' => 'Tingkatan berhasil diperbarui']);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO acad_class_levels (unit_id, name, order_index) VALUES (?, ?, ?)");
            $stmt->execute([$unit_id, $name, $order_index]);
            echo json_encode(['success' => true, 'message' => 'Tingkatan berhasil ditambahkan']);
        }
    } elseif ($method === 'DELETE') {
        // Delete Level
        $id = $input['id'];

        // Cek dependencies (classes)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_classes WHERE level_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Tidak dapat menghapus tingkatan yang memiliki kelas aktif']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM acad_class_levels WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Tingkatan berhasil dihapus']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

