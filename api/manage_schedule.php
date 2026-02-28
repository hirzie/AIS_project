<?php
// api/manage_schedule.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

 

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

$log = function($module, $category, $action, $entity_type, $entity_id, $title, $description) use ($pdo) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$module, $category, $action, $entity_type, (string)$entity_id, $title, $description, $userId]);
    } catch (\Throwable $e) {}
};

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'create' || $action === 'update') {
        $class_id = $input['class_id'];
        $subject_id = $input['subject_id'];
        $teacher_id = $input['teacher_id'] ?: null; // Allow NULL for custom subjects
        $day_name = $input['day'];
        $time_slot_id = $input['time_slot_id'];
        $academic_year_id = $input['academic_year_id'];
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['SUPERADMIN','ADMIN'])) {
            try {
                $stmtU = $pdo->prepare("SELECT UPPER(u.code) AS unit_code FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id JOIN core_units u ON l.unit_id = u.id WHERE c.id = ?");
                $stmtU->execute([$class_id]);
                $unitCode = $stmtU->fetchColumn();
                $allowedMap = $_SESSION['allowed_units'] ?? [];
                $allowed = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
                $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowed);
                if (!$unitCode || !in_array($unitCode, $allowedUp)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Unit not allowed']);
                    exit;
                }
            } catch (\Throwable $e) {}
        }

        // Cek apakah sudah ada jadwal di slot ini (untuk update/overwrite)
        // Kita pakai strategi: Hapus dulu jika ada, lalu insert baru (Upsert logic sederhana)
        
        $pdo->beginTransaction();

        // 69. Menggunakan start_time dan end_time sebagai kunci unik
        $stmtTime = $pdo->prepare("SELECT start_time, end_time, category_id FROM acad_time_slots WHERE id = ?");
        $stmtTime->execute([$time_slot_id]);
        $slot = $stmtTime->fetch();

        if (!$slot) {
            throw new Exception("Slot waktu tidak ditemukan");
        }

        $start_time = $slot['start_time'];
        $end_time = $slot['end_time'];
        $category_id = $slot['category_id'];

        // Hapus jadwal lama di jam yang sama DAN kategori yang sama
        $stmt = $pdo->prepare("DELETE FROM acad_schedules WHERE class_id = ? AND day_name = ? AND start_time = ? AND category_id = ?");
        $stmt->execute([$class_id, $day_name, $start_time, $category_id]);

        // Insert baru
        $stmt = $pdo->prepare("INSERT INTO acad_schedules (class_id, subject_id, teacher_id, day_name, start_time, end_time, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $subject_id, $teacher_id, $day_name, $start_time, $end_time, $category_id]);

        $pdo->commit();
        $log('ACADEMIC', 'DATA_CHANGE', 'SCHEDULE_SAVE', 'SCHEDULE', "{$class_id}-{$day_name}-{$start_time}", 'Perubahan Jadwal Disimpan', "Kelas: {$class_id}, Hari: {$day_name}, Slot: {$start_time}-{$end_time}, Mapel: {$subject_id}, Guru: {$teacher_id}");
        echo json_encode(['success' => true, 'message' => 'Jadwal berhasil disimpan']);

    } elseif ($action === 'delete') {
        $schedule_id = $input['schedule_id'];
        $stmt = $pdo->prepare("DELETE FROM acad_schedules WHERE id = ?");
        $stmt->execute([$schedule_id]);
        $log('ACADEMIC', 'DATA_CHANGE', 'SCHEDULE_DELETE', 'SCHEDULE', $schedule_id, 'Jadwal Dihapus', "ID Jadwal: {$schedule_id}");
        echo json_encode(['success' => true, 'message' => 'Jadwal dihapus']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
