<?php
// api/manage_class.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

 

try {
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
} catch (\Throwable $e) {}

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
    if ($action === 'create') {
        // Create Class
        $name = $input['name'] ?? '';
        $level_id = $input['level_id'] ?? null;
        $academic_year_id = $input['academic_year_id'] ?? null; // Perlu dikirim dari frontend
        $homeroom_id = !empty($input['homeroom_teacher_id']) ? $input['homeroom_teacher_id'] : null;
        
        // Convert homeroom (employee_id) to person_id
        if ($homeroom_id) {
            $stmt = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
            $stmt->execute([$homeroom_id]);
            $pid = $stmt->fetchColumn();
            if ($pid) {
                $homeroom_id = $pid;
            } else {
                // Optional: Throw error or set to null if not found
                // throw new Exception("Wali kelas tidak ditemukan");
                $homeroom_id = null; 
            }
        }

        $capacity = $input['capacity'] ?? 30;

        if (!$name || !$level_id || !$academic_year_id) {
            throw new Exception("Data tidak lengkap (Nama, Level, Tahun Ajaran wajib)");
        }

        $stmt = $pdo->prepare("INSERT INTO acad_classes (name, level_id, academic_year_id, homeroom_teacher_id, capacity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $level_id, $academic_year_id, $homeroom_id, $capacity]);
        
        $log('ACADEMIC', 'DATA_CHANGE', 'CLASS_CREATE', 'CLASS', $pdo->lastInsertId(), 'Kelas Dibuat', "Nama: {$name}, Level: {$level_id}, Kapasitas: {$capacity}");
        echo json_encode(['success' => true, 'message' => 'Kelas berhasil dibuat']);

    } elseif ($action === 'update') {
        // Update Class
        $class_id = $input['class_id'] ?? null;
        if (!$class_id) throw new Exception("Class ID required for update");

        $name = $input['name'] ?? '';
        $homeroom_id = !empty($input['homeroom_teacher_id']) ? $input['homeroom_teacher_id'] : null;
        
        // Convert homeroom (employee_id) to person_id
        if ($homeroom_id) {
            $stmt = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
            $stmt->execute([$homeroom_id]);
            $pid = $stmt->fetchColumn();
            if ($pid) {
                $homeroom_id = $pid;
            } else {
                $homeroom_id = null;
            }
        }

        $level_id = $input['level_id'] ?? null;
        $capacity = $input['capacity'] ?? 30;
        $sort_order = isset($input['sort_order']) ? (int)$input['sort_order'] : 0;

        $stmt = $pdo->prepare("UPDATE acad_classes SET name = ?, level_id = ?, homeroom_teacher_id = ?, capacity = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$name, $level_id, $homeroom_id, $capacity, $sort_order, $class_id]);

        $log('ACADEMIC', 'DATA_CHANGE', 'CLASS_UPDATE', 'CLASS', $class_id, 'Kelas Diperbarui', "Nama: {$name}, Level: {$level_id}, Kapasitas: {$capacity}");
        echo json_encode(['success' => true, 'message' => 'Kelas berhasil diperbarui']);

    } elseif ($action === 'delete') {
        // Delete Class
        $class_id = $input['class_id'] ?? null;
        if (!$class_id) throw new Exception("Class ID required for delete");

        // --- VALIDATION: Prevent Deletion if Linked Data Exists ---
        $blockingReasons = [];
        
        // 1. Check Students (Active or History)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_student_classes WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $studentCount = $stmt->fetchColumn();

        if ($studentCount > 0) {
            $blockingReasons[] = "Masih terdapat $studentCount siswa terdaftar (Aktif/Riwayat).";
        }

        // 2. Check Schedules
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_schedules WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $scheduleCount = $stmt->fetchColumn();

        if ($scheduleCount > 0) {
            $blockingReasons[] = "Masih terdapat $scheduleCount jadwal pelajaran.";
        }

        // 3. Check Subject Teachers
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_subject_teachers WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $teacherCount = $stmt->fetchColumn();
        
        if ($teacherCount > 0) {
            $blockingReasons[] = "Masih terdapat $teacherCount guru pengampu mata pelajaran.";
        }

        // If there are blocking reasons, throw exception with details
        if (!empty($blockingReasons)) {
            throw new Exception("Tidak dapat menghapus kelas ini karena:\n- " . implode("\n- ", $blockingReasons) . "\n\nSilakan kosongkan data tersebut terlebih dahulu.");
        }

        // --- PROCEED TO DELETE (If checks passed) ---

        // Since we validated, there should be nothing to clean up in student_classes/schedules/subject_teachers
        // But to be safe against race conditions or hidden data, we can keep the delete queries OR just delete the class
        // and let the DB foreign keys handle it (if set) or just leave them orphan (bad).
        // Best practice: If we validated 0 rows, running DELETE is harmless and safe.
        
        // 1. Clean up Student Assignments (Should be 0 rows affected)
        $stmt = $pdo->prepare("DELETE FROM acad_student_classes WHERE class_id = ?");
        $stmt->execute([$class_id]);

        // 2. Clean up Subject Assignments (Should be 0 rows affected)
        $stmt = $pdo->prepare("DELETE FROM acad_subject_teachers WHERE class_id = ?");
        $stmt->execute([$class_id]);

        // 3. Clean up Schedules (Should be 0 rows affected)
        $stmt = $pdo->prepare("DELETE FROM acad_schedules WHERE class_id = ?");
        $stmt->execute([$class_id]);

        // 4. Finally delete class
        $stmt = $pdo->prepare("DELETE FROM acad_classes WHERE id = ?");
        $stmt->execute([$class_id]);

        $log('ACADEMIC', 'DATA_CHANGE', 'CLASS_DELETE', 'CLASS', $class_id, 'Kelas Dihapus', "Class ID: {$class_id}");
        echo json_encode(['success' => true, 'message' => 'Kelas berhasil dihapus']);
    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
