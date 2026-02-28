<?php
// api/manage_year.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$year_id = $input['id'] ?? null;

try {
    if ($action === 'delete') {
        // Cek Usage
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_classes WHERE academic_year_id = ?");
        $stmt->execute([$year_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['error' => 'Tidak bisa menghapus tahun ajaran ini karena masih digunakan oleh kelas.']);
            exit;
        }
        
        $pdo->prepare("DELETE FROM acad_years WHERE id = ?")->execute([$year_id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'save') {
        $name = $input['name'];
        $start = $input['start_date'];
        $end = $input['end_date'];
        $status = $input['status'];
        $semester = $input['semester_active'];

        if ($year_id) {
            // Update
            $stmt = $pdo->prepare("UPDATE acad_years SET name=?, start_date=?, end_date=?, status=?, semester_active=? WHERE id=?");
            $stmt->execute([$name, $start, $end, $status, $semester, $year_id]);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO acad_years (name, start_date, end_date, status, semester_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $start, $end, $status, $semester]);
        }
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

