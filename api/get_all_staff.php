<?php
// api/get_all_staff.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    // Ambil semua orang tipe TEACHER atau STAFF, sertakan division dari custom_attributes
    $stmt = $pdo->query("SELECT id, name, identity_number, custom_attributes FROM core_people WHERE type IN ('TEACHER', 'STAFF') ORDER BY name ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $staff = [];
    foreach ($rows as $r) {
        $div = '';
        if (!empty($r['custom_attributes'])) {
            try {
                $attrs = json_decode($r['custom_attributes'], true);
                $div = strtoupper((string)($attrs['division'] ?? ''));
            } catch (\Throwable $e) { $div = ''; }
        }
        $staff[] = [
            'id' => $r['id'],
            'name' => $r['name'],
            'identity_number' => $r['identity_number'] ?? null,
            'division' => $div
        ];
    }
    echo json_encode($staff);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

