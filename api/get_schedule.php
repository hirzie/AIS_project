<?php
// api/get_schedule.php
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
require_once __DIR__ . '/../config/database.php';

$class_id = $_GET['class_id'] ?? 0;
$category_id = $_GET['category_id'] ?? null;

try {
    if (!$class_id) {
        echo json_encode([]);
        exit;
    }

    // Determine category_id if not provided (Default to Active)
    if (!$category_id) {
        // Get unit_id of the class (via level linkage)
        $stmtUnit = $pdo->prepare("
            SELECT l.unit_id 
            FROM acad_classes c
            JOIN acad_class_levels l ON c.level_id = l.id
            WHERE c.id = ?
        ");
        $stmtUnit->execute([$class_id]);
        $unit_id = $stmtUnit->fetchColumn();

        if ($unit_id) {
            $stmtCat = $pdo->prepare("SELECT id FROM acad_schedule_categories WHERE unit_id = ? AND is_active = 1 LIMIT 1");
            $stmtCat->execute([$unit_id]);
            $category_id = $stmtCat->fetchColumn();
        }
    }

    // Ambil jadwal lengkap dengan nama Mapel, Guru, dan Waktu dari Time Slot
    $sql = "
        SELECT 
            s.id,
            s.day_name,
            s.start_time,
            s.end_time,
            s.subject_id,
            s.teacher_id,
            s.category_id,
            sub.name as subject_name,
            sub.code as subject_code,
            sub.category as subject_category,
            t.name as teacher_name
        FROM acad_schedules s
        JOIN acad_subjects sub ON s.subject_id = sub.id
        LEFT JOIN core_people t ON s.teacher_id = t.id
        WHERE s.class_id = ?
    ";
    
    $params = [$class_id];
    
    if ($category_id) {
        $sql .= " AND s.category_id = ?";
        $params[] = $category_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll();


    // Grouping by Day and Start Time
    $grouped = [];
    foreach ($schedules as $sch) {
        // Ambil jam:menit saja (07:00:00 -> 07:00)
        $timeKey = date('H:i', strtotime($sch['start_time']));
        
        // Fix for NULL teacher name
        if ($sch['teacher_id'] === null) {
            $sch['teacher_name'] = '-';
        }
        
        $grouped[$sch['day_name']][$timeKey] = $sch;
    }

    echo json_encode($grouped);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

