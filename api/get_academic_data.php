<?php
// api/get_academic_data.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';

// Init session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unit_code = $_GET['unit'] ?? 'all';
$category_id = $_GET['category_id'] ?? null; // New: Filter by schedule category

try {
    // Basic RBAC check if needed (skipped for brevity as per existing pattern)
    
    $response = [
        'subjects' => [],
        'timeSlots' => [],
        'classes' => [],
        'levels' => [],
        'years' => [],
        'scheduleCategories' => [], // New
        'activeCategoryId' => null // New
    ];
    
    // 1. Get Academic Years (Global)
    $stmt = $pdo->query("SELECT * FROM acad_years ORDER BY start_date DESC");
    $response['years'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($unit_code !== 'all') {
        // 2. Get Unit ID
        // Support both code (SD) and unit_level (SD)
        // ALIAS MAPPING for MTs/MA which use SMP/SMA codes in DB
        $up = strtoupper($unit_code);
        if ($up === 'MTS') $up = 'SMP';
        if ($up === 'MA') $up = 'SMA';
        
        $stmt = $pdo->prepare("SELECT id FROM core_units WHERE UPPER(code) = ? OR UPPER(name) = ? LIMIT 1");
        $stmt->execute([$up, $up]);
        $unit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($unit) {
            $unit_id = $unit['id'];

            // 2b. Get Schedule Categories
            $stmtCat = $pdo->prepare("SELECT * FROM acad_schedule_categories WHERE unit_id = ? ORDER BY id ASC");
            $stmtCat->execute([$unit_id]);
            $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
            $response['scheduleCategories'] = $categories;

            // Determine Active Category
            $activeCat = null;
            if ($category_id) {
                // User selected specific category
                foreach ($categories as $c) {
                    if ($c['id'] == $category_id) {
                        $activeCat = $c;
                        break;
                    }
                }
            }
            
            if (!$activeCat) {
                // Find default active
                foreach ($categories as $c) {
                    if ($c['is_active']) {
                        $activeCat = $c;
                        break;
                    }
                }
            }

            if (!$activeCat && count($categories) > 0) {
                // Fallback to first
                $activeCat = $categories[0];
            }

            $response['activeCategoryId'] = $activeCat ? $activeCat['id'] : null;

            // 3. Get Time Slots (Filtered by Category)
            if ($activeCat) {
                $stmt = $pdo->prepare("SELECT * FROM acad_time_slots WHERE unit_id = ? AND category_id = ? ORDER BY start_time ASC");
                $stmt->execute([$unit_id, $activeCat['id']]);
            } else {
                // Fallback for legacy data (slots without category) or no categories
                $stmt = $pdo->prepare("SELECT * FROM acad_time_slots WHERE unit_id = ? AND category_id IS NULL ORDER BY start_time ASC");
                $stmt->execute([$unit_id]);
            }
            
            $rawSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process slots (calculate duration, order)
            $order = 0;
            foreach ($rawSlots as $slot) {
                $start = new DateTime($slot['start_time']);
                $end = new DateTime($slot['end_time']);
                $diff = $start->diff($end);
                $minutes = ($diff->h * 60) + $diff->i;
                $isBreak = (bool)$slot['is_break'];
                
                if (!$isBreak) $order++;

                $response['timeSlots'][] = [
                    'id' => $slot['id'],
                    'name' => $slot['name'],
                    'start' => date('H:i', strtotime($slot['start_time'])),
                    'end' => date('H:i', strtotime($slot['end_time'])),
                    'duration' => $minutes,
                    'isBreak' => $isBreak,
                    'order' => $isBreak ? null : $order,
                    'category_id' => $slot['category_id']
                ];
            }

            // 4. Get Subjects
            $stmt = $pdo->prepare("SELECT * FROM acad_subjects WHERE unit_id = ? ORDER BY name ASC");
            $stmt->execute([$unit_id]);
            $response['subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 5. Get Levels (Tingkatan)
            $stmt = $pdo->prepare("SELECT * FROM acad_class_levels WHERE unit_id = ? ORDER BY order_index ASC");
            $stmt->execute([$unit_id]);
            $response['levels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 6. Get Classes
            // Logic similar to original file, simplifying query for brevity but keeping functionality
            // We need to know current active year from global years list
            $activeYearId = null;
            foreach ($response['years'] as $y) {
                if ($y['status'] === 'ACTIVE' || $y['status'] === 'Aktif') {
                    $activeYearId = $y['id'];
                    break;
                }
            }
            if (!$activeYearId && !empty($response['years'])) {
                $activeYearId = $response['years'][0]['id'];
            }

            $sql = "
                SELECT 
                    c.id, c.name, c.level_id, c.capacity, c.sort_order,
                    l.name as level_name, 
                    p.name as homeroom,
                    c.homeroom_teacher_id,
                    (SELECT COUNT(*) FROM acad_student_classes sc WHERE sc.class_id = c.id AND sc.status = 'ACTIVE') as student_count
                FROM acad_classes c
                JOIN acad_class_levels l ON c.level_id = l.id
                LEFT JOIN core_people p ON c.homeroom_teacher_id = p.id
                WHERE l.unit_id = ?
            ";
            
            $params = [$unit_id];
            
            if ($activeYearId) {
                $sql .= " AND c.academic_year_id = ?";
                $params[] = $activeYearId;
            }
            
            $sql .= " ORDER BY l.order_index ASC, c.sort_order ASC, c.name ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $response['classes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>