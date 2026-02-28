<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
if ($action === 'list' && $method === 'GET') {
    $limit = (int)($_GET['limit'] ?? 50);
    if ($limit <= 0 || $limit > 200) { $limit = 50; }
    $pdo->exec("CREATE TABLE IF NOT EXISTS executive_discussions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        message TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $stmt = $pdo->query("SELECT d.id, d.user_id, d.message, d.created_at, u.username, p.name AS people_name
                         FROM executive_discussions d
                         LEFT JOIN core_users u ON d.user_id = u.id
                         LEFT JOIN core_people p ON u.people_id = p.id
                         ORDER BY d.created_at DESC, d.id DESC
                         LIMIT $limit");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if ($action === 'send' && $method === 'POST') {
    $pdo->exec("CREATE TABLE IF NOT EXISTS executive_discussions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        message TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $data = json_decode(file_get_contents('php://input'), true);
    $msg = trim($data['message'] ?? '');
    if ($msg === '') { echo json_encode(['success' => false, 'message' => 'Pesan kosong']); exit; }
    $ins = $pdo->prepare("INSERT INTO executive_discussions (user_id, message) VALUES (?, ?)");
    $ins->execute([$_SESSION['user_id'], $msg]);
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT d.id, d.user_id, d.message, d.created_at, u.username, p.name AS people_name
                           FROM executive_discussions d
                           LEFT JOIN core_users u ON d.user_id = u.id
                           LEFT JOIN core_people p ON u.people_id = p.id
                           WHERE d.id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
