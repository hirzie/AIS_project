<?php
// api/boarding_schedule.php
require_once '../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_slots') {
        $stmt = $pdo->query("SELECT * FROM boarding_schedule_slots ORDER BY start_time ASC");
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $slots]);

    } elseif ($action === 'save_slot') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if ($id) {
            $sql = "UPDATE boarding_schedule_slots SET name = ?, start_time = ?, end_time = ?, is_break = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['start_time'], $input['end_time'], $input['is_break'] ? 1 : 0, $id]);
        } else {
            $sql = "INSERT INTO boarding_schedule_slots (name, start_time, end_time, is_break) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['name'], $input['start_time'], $input['end_time'], $input['is_break'] ? 1 : 0]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_slot') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM boarding_schedule_slots WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_agenda') {
        $stmt = $pdo->query("
            SELECT a.*, s.start_time, s.end_time 
            FROM boarding_agenda a
            JOIN boarding_schedule_slots s ON a.slot_id = s.id
        ");
        $agenda = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $agenda]);

    } elseif ($action === 'save_agenda') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if ($id) {
            $sql = "UPDATE boarding_agenda SET slot_id = ?, day_name = ?, activity_name = ?, description = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['slot_id'], $input['day_name'], $input['activity_name'], $input['description'] ?? '', $id]);
        } else {
            $sql = "INSERT INTO boarding_agenda (slot_id, day_name, activity_name, description) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['slot_id'], $input['day_name'], $input['activity_name'], $input['description'] ?? '']);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_agenda') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM boarding_agenda WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'generate_slots') {
        // Simple generation starting from 16:00
        $pdo->exec("DELETE FROM boarding_schedule_slots");
        
        $slots = [
            ['16:00:00', '17:00:00', 'Kegiatan Sore', 0],
            ['17:00:00', '18:00:00', 'Persiapan Maghrib', 1],
            ['18:00:00', '19:30:00', 'Halaqoh Maghrib', 0],
            ['19:30:00', '20:00:00', 'Makan Malam', 1],
            ['20:00:00', '21:30:00', 'Belajar Mandiri', 0],
            ['21:30:00', '04:00:00', 'Istirahat Malam', 1]
        ];
        
        $sql = "INSERT INTO boarding_schedule_slots (start_time, end_time, name, is_break) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        foreach ($slots as $s) {
            $stmt->execute($s);
        }
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
