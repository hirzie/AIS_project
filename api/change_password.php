<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$current = $input['current_password'] ?? '';
$new = $input['new_password'] ?? '';

if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'message' => 'Minimal 6 karakter']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password_hash FROM core_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) {
        echo json_encode(['success' => false, 'message' => 'Password saat ini salah']);
        exit;
    }
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE core_users SET password_hash = ? WHERE id = ?");
    $upd->execute([$newHash, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
