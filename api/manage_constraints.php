<?php
// api/manage_constraints.php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'create') {
        $type = $input['type'];
        $entity_id = $input['entity_id'];
        
        // Convert employee_id to person_id for TEACHER
        if ($type === 'TEACHER') {
            $stmt = $pdo->prepare("SELECT person_id FROM hr_employees WHERE id = ?");
            $stmt->execute([$entity_id]);
            $pid = $stmt->fetchColumn();
            if (!$pid) {
                echo json_encode(['success' => false, 'error' => 'Pegawai tidak ditemukan']);
                exit;
            }
            $entity_id = $pid;
        }

        $day = $input['day'];
        $start = $input['start_time'] ?: null;
        $end = $input['end_time'] ?: null;
        $whole = !empty($input['is_whole_day']) ? 1 : 0;
        $reason = $input['reason'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO acad_schedule_constraints (type, entity_id, day_name, start_time, end_time, is_whole_day, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type, $entity_id, $day, $start, $end, $whole, $reason]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id = $input['id'];
        $stmt = $pdo->prepare("DELETE FROM acad_schedule_constraints WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'list') {
        $type = $_GET['type'] ?? 'TEACHER';
        $entity_id = $_GET['entity_id'] ?? null;
        
        $sql = "SELECT c.* ";
        $params = [];
        
        if ($type === 'TEACHER') {
            $sql .= ", p.name as entity_name, he.id as employee_id 
                     FROM acad_schedule_constraints c
                     LEFT JOIN core_people p ON c.entity_id = p.id
                     LEFT JOIN hr_employees he ON p.id = he.person_id
                     WHERE c.type = 'TEACHER'";
                     
            if ($entity_id) {
                $sql .= " AND he.id = ?";
                $params[] = $entity_id;
            }
        } else {
            $sql .= ", s.name as entity_name 
                     FROM acad_schedule_constraints c
                     LEFT JOIN acad_subjects s ON c.entity_id = s.id
                     WHERE c.type = 'SUBJECT'";
                     
            if ($entity_id) {
                $sql .= " AND c.entity_id = ?";
                $params[] = $entity_id;
            }
        }
        
        $sql .= " ORDER BY c.day_name, c.start_time";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
