<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function saveSetting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE core_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO core_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    } catch (\Throwable $e) {}
}
function getSetting($pdo, $key) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    } catch (\Throwable $e) { return null; }
}
function ensureCoreSettings($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS core_settings (setting_key varchar(50) NOT NULL, setting_value text, PRIMARY KEY (setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (\Throwable $e) {}
}
ensureCoreSettings($pdo);

function getBoardDefault() {
    return [
        'columns' => ['PLANNED', 'TESTING', 'PRODUCTION'],
        'cards' => []
    ];
}

function requireDevAccess() {
    $role = strtoupper(trim($_SESSION['role'] ?? ''));
    if (!isset($_SESSION['user_id']) || !in_array($role, ['SUPERADMIN','ADMIN','MANAGERIAL','EXECUTIVE'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

try {
    if ($action === 'get_board' && $method === 'GET') {
        $json = getSetting($pdo, 'dev_whiteboard');
        $board = $json ? json_decode($json, true) : null;
        if (!is_array($board)) $board = getBoardDefault();
        echo json_encode(['success' => true, 'data' => $board]);
        exit;
    }
    if ($action === 'save_card' && $method === 'POST') {
        requireDevAccess();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = trim($data['id'] ?? '');
        $title = trim($data['title'] ?? '');
        $desc = trim($data['description'] ?? '');
        $status = strtoupper(trim($data['status'] ?? 'PLANNED'));
        $meeting_id = $data['meeting_id'] ?? null;
        $tags = is_array($data['tags'] ?? null) ? $data['tags'] : [];
        if ($title === '') { echo json_encode(['success' => false, 'message' => 'Judul wajib']); exit; }
        if (!in_array($status, ['PLANNED','TESTING','PRODUCTION'])) $status = 'PLANNED';

        $json = getSetting($pdo, 'dev_whiteboard');
        $board = $json ? json_decode($json, true) : null;
        if (!is_array($board)) $board = getBoardDefault();
        if (!is_array($board['cards'])) $board['cards'] = [];

        $now = date('Y-m-d H:i:s');
        $saved = null;
        if ($id !== '') {
            foreach ($board['cards'] as &$c) {
                if (($c['id'] ?? '') === $id) {
                    $c['title'] = $title;
                    $c['description'] = $desc;
                    $c['status'] = $status;
                    $c['meeting_id'] = $meeting_id;
                    $c['tags'] = $tags;
                    $c['updated_at'] = $now;
                    $saved = $c;
                    break;
                }
            }
            unset($c);
        }
        if ($saved === null) {
            $card = [
                'id' => $id !== '' ? $id : ('WB-' . time() . '-' . mt_rand(1000,9999)),
                'title' => $title,
                'description' => $desc,
                'status' => $status,
                'meeting_id' => $meeting_id,
                'tags' => $tags,
                'created_at' => $now,
                'created_by_user_id' => $_SESSION['user_id'] ?? null
            ];
            $board['cards'][] = $card;
            $saved = $card;
        }
        saveSetting($pdo, 'dev_whiteboard', json_encode($board));
        echo json_encode(['success' => true, 'data' => $saved]);
        exit;
    }
    if ($action === 'move_card' && $method === 'POST') {
        requireDevAccess();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = trim($data['id'] ?? '');
        $status = strtoupper(trim($data['status'] ?? 'PLANNED'));
        if ($id === '') { echo json_encode(['success' => false, 'message' => 'ID wajib']); exit; }
        if (!in_array($status, ['PLANNED','TESTING','PRODUCTION'])) { echo json_encode(['success' => false, 'message' => 'Status tidak valid']); exit; }
        $json = getSetting($pdo, 'dev_whiteboard');
        $board = $json ? json_decode($json, true) : null;
        if (!is_array($board)) $board = getBoardDefault();
        if (!is_array($board['cards'])) $board['cards'] = [];
        $now = date('Y-m-d H:i:s');
        $ok = false;
        foreach ($board['cards'] as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c['status'] = $status;
                $c['updated_at'] = $now;
                $ok = true;
                break;
            }
        }
        unset($c);
        if (!$ok) { echo json_encode(['success' => false, 'message' => 'Kartu tidak ditemukan']); exit; }
        saveSetting($pdo, 'dev_whiteboard', json_encode($board));
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete_card' && $method === 'POST') {
        requireDevAccess();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = trim($data['id'] ?? '');
        if ($id === '') { echo json_encode(['success' => false, 'message' => 'ID wajib']); exit; }
        $json = getSetting($pdo, 'dev_whiteboard');
        $board = $json ? json_decode($json, true) : null;
        if (!is_array($board)) $board = getBoardDefault();
        if (!is_array($board['cards'])) $board['cards'] = [];
        $board['cards'] = array_values(array_filter($board['cards'], function($c) use ($id) {
            return ($c['id'] ?? '') !== $id;
        }));
        saveSetting($pdo, 'dev_whiteboard', json_encode($board));
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

