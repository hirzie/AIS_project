<?php
// api/add_position.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);

$name = $input['name'] ?? '';
$parent_id = $input['parent_id'] ?? null;
$unit_id = $input['unit_id'] ?? null;

if (empty($name) || empty($parent_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nama jabatan dan atasan harus diisi']);
    exit;
}

try {
    // Tentukan Level: Level Parent + 1
    $stmt = $pdo->prepare("SELECT level FROM hr_positions WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    $newLevel = ($parent['level'] ?? 0) + 1;

    // Insert Jabatan Baru
    $sql = "INSERT INTO hr_positions (name, parent_id, unit_id, level) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $parent_id, $unit_id, $newLevel]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

