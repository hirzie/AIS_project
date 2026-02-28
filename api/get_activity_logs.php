<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$module = isset($_GET['module']) ? trim($_GET['module']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$division = isset($_GET['division']) ? strtoupper(trim($_GET['division'])) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if ($limit <= 0 || $limit > 200) $limit = 20;

try {
    // Ensure table exists (safe guard)
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        entity_id VARCHAR(64) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($module !== '' && $category !== '') {
        $stmt = $pdo->prepare("SELECT l.created_at, l.module, l.category, l.action, l.entity_type, l.entity_id, l.title, l.description, u.username, u.role AS user_role, p.name AS people_name, p.custom_attributes
                               FROM activity_logs l
                               LEFT JOIN core_users u ON l.user_id = u.id
                               LEFT JOIN core_people p ON u.people_id = p.id
                               WHERE l.module = ? AND l.category = ?
                               ORDER BY l.created_at DESC, l.id DESC
                               LIMIT $limit");
        $stmt->execute([$module, $category]);
    } elseif ($module !== '') {
        $stmt = $pdo->prepare("SELECT l.created_at, l.module, l.category, l.action, l.entity_type, l.entity_id, l.title, l.description, u.username, u.role AS user_role, p.name AS people_name, p.custom_attributes
                               FROM activity_logs l
                               LEFT JOIN core_users u ON l.user_id = u.id
                               LEFT JOIN core_people p ON u.people_id = p.id
                               WHERE l.module = ?
                               ORDER BY l.created_at DESC, l.id DESC
                               LIMIT $limit");
        $stmt->execute([$module]);
    } else {
        $stmt = $pdo->query("SELECT l.created_at, l.module, l.category, l.action, l.entity_type, l.entity_id, l.title, l.description, u.username, u.role AS user_role, p.name AS people_name, p.custom_attributes
                             FROM activity_logs l
                             LEFT JOIN core_users u ON l.user_id = u.id
                             LEFT JOIN core_people p ON u.people_id = p.id
                             ORDER BY l.created_at DESC, l.id DESC
                             LIMIT $limit");
    }
    $rowsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = $rowsRaw;
    if ($division === 'SECURITY') {
        $filtered = [];
        foreach ($rowsRaw as $r) {
            $role = strtoupper((string)($r['user_role'] ?? ''));
            $div = '';
            if (!empty($r['custom_attributes'])) {
                try {
                    $attrs = json_decode($r['custom_attributes'], true);
                    $div = strtoupper((string)($attrs['division'] ?? ''));
                } catch (\Throwable $e) { $div = ''; }
            }
            if ($role === 'SECURITY' || $div === 'SECURITY') {
                $filtered[] = $r;
            }
        }
        $rows = $filtered;
    }
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
