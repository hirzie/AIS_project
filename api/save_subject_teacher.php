<?php
// api/save_subject_teacher.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$class_id = $input['class_id'];
$academic_year_id = $input['academic_year_id'];
$subject_id = $input['subject_id'];
$teacher_id = $input['teacher_id']; // Bisa null/empty string jika unassign

try {
    $weekly_count = isset($input['weekly_count']) ? (int)$input['weekly_count'] : 1;
    $session_length = isset($input['session_length']) ? (int)$input['session_length'] : 1;

    if (empty($teacher_id)) {
        // Unassign (Delete)
        $stmt = $pdo->prepare("DELETE FROM acad_subject_teachers WHERE class_id = ? AND subject_id = ? AND academic_year_id = ?");
        $stmt->execute([$class_id, $subject_id, $academic_year_id]);
        echo json_encode(['success' => true, 'message' => 'Guru mapel dihapus']);
    } else {
        // Convert employee_id (teacher_id from frontend) to person_id
        $stmt = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
        $stmt->execute([$teacher_id]);
        $person_id = $stmt->fetchColumn();

        if (!$person_id) {
            echo json_encode(['success' => false, 'message' => 'Data pegawai tidak ditemukan']);
            exit;
        }

        // Assign (Upsert) using person_id
        $stmt = $pdo->prepare("
            INSERT INTO acad_subject_teachers (class_id, subject_id, teacher_id, academic_year_id, weekly_count, session_length)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                teacher_id = VALUES(teacher_id),
                weekly_count = VALUES(weekly_count),
                session_length = VALUES(session_length)
        ");
        $stmt->execute([$class_id, $subject_id, $person_id, $academic_year_id, $weekly_count, $session_length]);
        echo json_encode(['success' => true, 'message' => 'Guru mapel disimpan']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

