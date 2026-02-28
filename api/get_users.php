<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $users = [];
    try {
        $stmt = $pdo->query("
            SELECT 
                u.id, u.username, u.role, u.email, u.status, u.access_modules, u.last_login, 
                u.people_id, p.name AS person_name, p.custom_attributes AS people_custom_attributes
            FROM core_users u
            LEFT JOIN core_people p ON u.people_id = p.id
            ORDER BY u.id DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stmt = $pdo->query("SELECT id, username, role, last_login FROM core_users ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$u) {
            $u['email'] = $u['email'] ?? '';
            $u['status'] = $u['status'] ?? 'ACTIVE';
            $u['access_modules'] = $u['access_modules'] ?? null;
            $u['people_id'] = $u['people_id'] ?? null;
            $u['person_name'] = $u['person_name'] ?? '';
        }
    }

    // Ensure consistent output types + derive division from custom_attributes
    foreach ($users as &$u) {
        if (isset($u['access_modules']) && $u['access_modules']) {
            $decoded = json_decode($u['access_modules'], true);
            $u['access_modules'] = is_array($decoded) ? $decoded : [];
        } else {
            $u['access_modules'] = [];
        }
        if (!isset($u['email'])) $u['email'] = '';
        if (!isset($u['status'])) $u['status'] = 'ACTIVE';
        // division from people_custom_attributes
        $div = '';
        if (!empty($u['people_custom_attributes'])) {
            $pc = json_decode($u['people_custom_attributes'], true);
            if (is_array($pc) && isset($pc['division'])) {
                $div = strtoupper((string)$pc['division']);
            }
        }
        $u['division'] = $div;
    }

    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
