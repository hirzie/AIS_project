<?php
// api/generate_timeslots.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$unit_id = $input['unit_id'] ?? null;
$category_id = $input['category_id'] ?? null;

if (!$unit_id) {
    echo json_encode(['error' => 'Unit ID diperlukan']);
    exit;
}
if (!$category_id) {
    echo json_encode(['error' => 'Category ID diperlukan']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Hapus slot lama untuk unit ini DAN kategori ini
    $stmt = $pdo->prepare("DELETE FROM acad_time_slots WHERE unit_id = ? AND category_id = ?");
    $stmt->execute([$unit_id, $category_id]);

    // 2. Insert Default Slots
    $slots = [];
    
    if ($unit_id == 1) { // TK
        $slots = [
            ['Jam Ke-1', '07:30', '08:00', 0],
            ['Jam Ke-2', '08:00', '08:30', 0],
            ['Istirahat', '08:30', '09:00', 1],
            ['Jam Ke-3', '09:00', '09:30', 0],
            ['Jam Ke-4', '09:30', '10:00', 0]
        ];
    } elseif ($unit_id == 2) { // SD
        $slots = [
            ['Jam Ke-1', '07:00', '07:35', 0],
            ['Jam Ke-2', '07:35', '08:10', 0],
            ['Jam Ke-3', '08:10', '08:45', 0],
            ['Istirahat 1', '08:45', '09:00', 1],
            ['Jam Ke-4', '09:00', '09:35', 0],
            ['Jam Ke-5', '09:35', '10:10', 0],
            ['Jam Ke-6', '10:10', '10:45', 0],
            ['Istirahat 2', '10:45', '11:00', 1],
            ['Jam Ke-7', '11:00', '11:35', 0]
        ];
    } elseif ($unit_id == 3) { // SMP
        $slots = [
            ['Jam Ke-1', '07:00', '07:40', 0],
            ['Jam Ke-2', '07:40', '08:20', 0],
            ['Jam Ke-3', '08:20', '09:00', 0],
            ['Jam Ke-4', '09:00', '09:40', 0],
            ['Istirahat 1', '09:40', '10:00', 1],
            ['Jam Ke-5', '10:00', '10:40', 0],
            ['Jam Ke-6', '10:40', '11:20', 0],
            ['Jam Ke-7', '11:20', '12:00', 0],
            ['Ishoma', '12:00', '12:40', 1],
            ['Jam Ke-8', '12:40', '13:20', 0],
            ['Jam Ke-9', '13:20', '14:00', 0]
        ];
    } elseif ($unit_id == 4) { // SMA
        $slots = [
            ['Jam Ke-1', '07:00', '07:45', 0],
            ['Jam Ke-2', '07:45', '08:30', 0],
            ['Jam Ke-3', '08:30', '09:15', 0],
            ['Jam Ke-4', '09:15', '10:00', 0],
            ['Istirahat 1', '10:00', '10:15', 1],
            ['Jam Ke-5', '10:15', '11:00', 0],
            ['Jam Ke-6', '11:00', '11:45', 0],
            ['Jam Ke-7', '11:45', '12:30', 0],
            ['Ishoma', '12:30', '13:15', 1],
            ['Jam Ke-8', '13:15', '14:00', 0],
            ['Jam Ke-9', '14:00', '14:45', 0]
        ];
    }

    $stmtInsert = $pdo->prepare("INSERT INTO acad_time_slots (unit_id, name, start_time, end_time, is_break, category_id) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($slots as $s) {
        $stmtInsert->execute([$unit_id, $s[0], $s[1], $s[2], $s[3], $category_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Jadwal standar berhasil dibuat!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
