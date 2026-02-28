<?php
// api/workspace_academic.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();

$action = $_GET['action'] ?? 'summary';
$unit_code = $_GET['unit'] ?? '';

if (!$unit_code && $action !== 'get_best_unit') {
    echo json_encode(['success' => false, 'error' => 'Unit code required']);
    exit;
}

if ($action === 'get_best_unit') {
    $personId = $_SESSION['person_id'] ?? 0;
    $role = $_SESSION['role'] ?? '';
    $isAdmin = in_array(strtoupper($role), ['SUPERADMIN', 'ADMIN']);

    if (!$personId && !$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'User not identified']);
        exit;
    }
    
    // Get all units first
    $stmt = $pdo->query("SELECT id, code FROM core_units ORDER BY id ASC");
    $allUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $bestUnit = null;
    $bestScore = -1; // 10=Principal, 8=Vice, 6=Wali, 5=Employee, 3=Guru, 1=GlobalAccess
    
    // Check allowed_units from session
    $allowedMap = $_SESSION['allowed_units'] ?? [];
    $allowedCodes = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
    $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowedCodes);

    // Global Role Check
    $uRole = strtoupper($role);
    $isGlobalPrincipal = in_array($uRole, ['PRINCIPAL', 'KEPALA_SEKOLAH', 'KEPALA', 'VICE_PRINCIPAL', 'WAKASEK']);
    
    // Pre-fetch employee units (Optimized)
    $employeeUnitIds = [];
    if ($personId) {
        try {
            // Check hr_unit_access
            $stmtEmp = $pdo->prepare("SELECT unit_id FROM hr_unit_access hua JOIN hr_employees he ON hua.employee_id = he.id WHERE he.person_id = ?");
            $stmtEmp->execute([$personId]);
            $employeeUnitIds = $stmtEmp->fetchAll(PDO::FETCH_COLUMN);
            
            // Check hr_positions
            $stmtPos = $pdo->prepare("SELECT unit_id FROM hr_positions hp JOIN hr_employees he ON hp.id = he.position_id WHERE he.person_id = ? AND hp.unit_id IS NOT NULL");
            $stmtPos->execute([$personId]);
            $posUnitIds = $stmtPos->fetchAll(PDO::FETCH_COLUMN);
            
            $employeeUnitIds = array_unique(array_merge($employeeUnitIds, $posUnitIds));
        } catch(Exception $e) {}
    }

    foreach ($allUnits as $u) {
        $uc = strtoupper($u['code']);
        if (!$isAdmin && !$isGlobalPrincipal && !in_array($uc, $allowedUp)) continue;
        
        $score = 0;
        $uid = $u['id'];

        // Global Principal -> Base Score 1 (Access allowed, but not preferred unless matched)
        if ($isGlobalPrincipal) $score = 1;
        
        // 1. Check KEPALA (Explicit)
        if ($score < 10) {
            $stmtPos = $pdo->prepare("SELECT COUNT(*) FROM acad_unit_positions WHERE unit_id = ? AND person_id = ? AND position = 'PRINCIPAL'");
            $stmtPos->execute([$uid, $personId]);
            if ($stmtPos->fetchColumn() > 0) $score = 10;
        }
        
        // 2. Check WAKASEK (Explicit)
        if ($score < 8) {
             $stmtPos = $pdo->prepare("SELECT COUNT(*) FROM acad_unit_positions WHERE unit_id = ? AND person_id = ? AND position = 'VICE_PRINCIPAL'");
             $stmtPos->execute([$uid, $personId]);
             if ($stmtPos->fetchColumn() > 0) $score = 8;
        }
        
        // 3. Check WALI
        if ($score < 6) {
             $stmtWali = $pdo->prepare("
                SELECT COUNT(*) FROM acad_classes c 
                JOIN acad_class_levels l ON c.level_id = l.id 
                LEFT JOIN hr_employees e ON c.homeroom_teacher_id = e.id
                WHERE l.unit_id = ? 
                AND (c.homeroom_teacher_id = ? OR e.person_id = ?)
             ");
             $stmtWali->execute([$uid, $personId, $personId]);
             if ($stmtWali->fetchColumn() > 0) $score = 6;
        }

        // 4. Check Employee / Unit Access (HR Link)
        if ($score < 5 && in_array($uid, $employeeUnitIds)) {
            $score = 5;
        }
        
        // 5. Check GURU
        if ($score < 3) {
             try {
                 // Check subject teachers
                 $stmtST = $pdo->prepare("SELECT COUNT(*) FROM acad_subject_teachers ast JOIN acad_subjects s ON ast.subject_id = s.id WHERE s.unit_id = ? AND ast.teacher_id = ?");
                 $stmtST->execute([$uid, $personId]);
                 if ($stmtST->fetchColumn() > 0) $score = 3;
                 
                 // Check direct subjects
                 if ($score < 3) {
                     $stmtSub = $pdo->prepare("SELECT COUNT(*) FROM acad_subjects WHERE unit_id = ? AND teacher_id = ?");
                     $stmtSub->execute([$uid, $personId]);
                     if ($stmtSub->fetchColumn() > 0) $score = 3;
                 }
                 
                 // Check schedules
                 if ($score < 3) {
                    $stmtSch = $pdo->prepare("
                        SELECT COUNT(*) FROM acad_schedules sch
                        JOIN acad_classes c ON sch.class_id = c.id
                        JOIN acad_class_levels l ON c.level_id = l.id
                        WHERE l.unit_id = ? AND sch.teacher_id = ?
                    ");
                    $stmtSch->execute([$uid, $personId]);
                    if ($stmtSch->fetchColumn() > 0) $score = 3;
                 }
             } catch (\Throwable $e) {}
        }
        
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestUnit = $u;
        }
        
        // If we found a definitive Principal match (10), we can stop, unless we want to find multiple Principals? 
        // Assuming one Principal position is enough to be 'best'.
        if ($bestScore == 10) break; 
    }
    
    if ($bestUnit) {
        echo json_encode(['success' => true, 'unit' => $bestUnit]);
    } else {
        // Fallback to first allowed unit
        if (count($allUnits) > 0) {
             foreach ($allUnits as $u) {
                 $uc = strtoupper($u['code']);
                 if ($isAdmin || in_array($uc, $allowedUp)) {
                     echo json_encode(['success' => true, 'unit' => $u]);
                     exit;
                 }
             }
        }
        echo json_encode(['success' => false, 'error' => 'No unit found']);
    }
    exit;
}

if ($action === 'get_available_units') {
    // Return all units for the selector (lock status determined by frontend using accessible_units)
    $stmt = $pdo->query("SELECT id, code, name FROM core_units ORDER BY id ASC");
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'units' => $units]);
    exit;
}

if ($action === 'get_accessible_units') {
    $personId = $_SESSION['person_id'] ?? 0;
    $role = $_SESSION['role'] ?? '';
    $isAdmin = in_array(strtoupper($role), ['SUPERADMIN', 'ADMIN']);

    if (!$personId && !$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'User not identified']);
        exit;
    }
    
    // Get all units
    $stmt = $pdo->query("SELECT id, code FROM core_units ORDER BY id ASC");
    $allUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $accessibleUnits = [];
    
    // Admin access all
    if ($isAdmin) {
        foreach ($allUnits as $u) {
            $accessibleUnits[] = strtolower($u['code']);
        }
        echo json_encode(['success' => true, 'units' => $accessibleUnits]);
        exit;
    }

    // Check allowed_units from session (Basic Gating)
    $allowedMap = $_SESSION['allowed_units'] ?? [];
    $allowedCodes = array_keys(array_filter(is_array($allowedMap) ? $allowedMap : []));
    $allowedUp = array_map(function($s){ return strtoupper(trim((string)$s)); }, $allowedCodes);

    // Global Role Check
    $uRole = strtoupper($role);
    $isGlobalPrincipal = in_array($uRole, ['PRINCIPAL', 'KEPALA_SEKOLAH', 'KEPALA', 'VICE_PRINCIPAL', 'WAKASEK']);

    foreach ($allUnits as $u) {
        $uc = strtoupper($u['code']);
        $uid = $u['id'];
        
        // If explicitly allowed in session, add it
        if (in_array($uc, $allowedUp)) {
            $accessibleUnits[] = strtolower($u['code']);
            continue;
        }

        // Global Principal Access
        if ($isGlobalPrincipal) {
             $accessibleUnits[] = strtolower($u['code']);
             continue;
        }

        // Deep Check (Role-based)
        $hasRole = false;

        // 1. Check Positions (Principal/Vice)
        $stmtPos = $pdo->prepare("SELECT COUNT(*) FROM acad_unit_positions WHERE unit_id = ? AND person_id = ?");
        $stmtPos->execute([$uid, $personId]);
        if ($stmtPos->fetchColumn() > 0) $hasRole = true;

        // 2. Check Wali
        if (!$hasRole) {
            $stmtWali = $pdo->prepare("
                SELECT COUNT(*) FROM acad_classes c 
                JOIN acad_class_levels l ON c.level_id = l.id 
                LEFT JOIN hr_employees e ON c.homeroom_teacher_id = e.id
                WHERE l.unit_id = ? 
                AND (c.homeroom_teacher_id = ? OR e.person_id = ?)
            ");
            $stmtWali->execute([$uid, $personId, $personId]);
            if ($stmtWali->fetchColumn() > 0) $hasRole = true;
        }

        // 3. Check Guru (Subjects/Schedule)
        if (!$hasRole) {
             try {
                 // Subject Teachers
                 $stmtST = $pdo->prepare("SELECT COUNT(*) FROM acad_subject_teachers ast JOIN acad_subjects s ON ast.subject_id = s.id WHERE s.unit_id = ? AND ast.teacher_id = ?");
                 $stmtST->execute([$uid, $personId]);
                 if ($stmtST->fetchColumn() > 0) $hasRole = true;
                 
                 // Direct Subjects
                 if (!$hasRole) {
                     $stmtSub = $pdo->prepare("SELECT COUNT(*) FROM acad_subjects WHERE unit_id = ? AND teacher_id = ?");
                     $stmtSub->execute([$uid, $personId]);
                     if ($stmtSub->fetchColumn() > 0) $hasRole = true;
                 }
                 
                 // Schedules
                 if (!$hasRole) {
                    $stmtSch = $pdo->prepare("
                        SELECT COUNT(*) FROM acad_schedules sch
                        JOIN acad_classes c ON sch.class_id = c.id
                        JOIN acad_class_levels l ON c.level_id = l.id
                        WHERE l.unit_id = ? AND sch.teacher_id = ?
                    ");
                    $stmtSch->execute([$uid, $personId]);
                    if ($stmtSch->fetchColumn() > 0) $hasRole = true;
                 }
             } catch (\Throwable $e) {}
        }

        if ($hasRole) {
            $accessibleUnits[] = strtolower($u['code']);
        }
    }
    
    echo json_encode(['success' => true, 'units' => array_unique($accessibleUnits)]);
    exit;
}

// 1. Resolve Unit ID
$stmt = $pdo->prepare("SELECT id FROM core_units WHERE code = ? OR receipt_code = ? LIMIT 1");
$stmt->execute([strtoupper($unit_code), strtoupper($unit_code)]);
$unit = $stmt->fetch();
$unit_id = $unit ? $unit['id'] : 0;

if ($action === 'get_user_roles') {
    $personId = $_SESSION['person_id'] ?? 0;
    $role = $_SESSION['role'] ?? '';
    
    // Default roles (assume false unless found)
    $roles = [
        'kepala' => false,
        'wakasek' => false,
        'wali' => false,
        'guru' => false
    ];

    // Admin override: Admin can access all views
    if (in_array(strtoupper($role), ['SUPERADMIN', 'ADMIN'])) {
        echo json_encode(['success' => true, 'data' => [
            'kepala' => true, 'wakasek' => true, 'wali' => true, 'guru' => true
        ]]);
        exit;
    }

    // Role-based Access (Fallback if not in acad_unit_positions)
    $uRole = strtoupper($role);
    if (in_array($uRole, ['PRINCIPAL', 'KEPALA_SEKOLAH', 'KEPALA'])) $roles['kepala'] = true;
    if (in_array($uRole, ['VICE_PRINCIPAL', 'WAKASEK'])) $roles['wakasek'] = true;

    if ($personId && $unit_id) {
        // 1. Check Kepala & Wakasek from acad_unit_positions
        $stmtPos = $pdo->prepare("SELECT position FROM acad_unit_positions WHERE unit_id = ? AND person_id = ?");
        $stmtPos->execute([$unit_id, $personId]);
        $positions = $stmtPos->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('PRINCIPAL', $positions)) $roles['kepala'] = true;
        if (in_array('VICE_PRINCIPAL', $positions)) $roles['wakasek'] = true;

        // 2. Check Walikelas (Homeroom Teacher)
        // Check if assigned to any class in this unit as homeroom teacher
        // Robust check: Check both direct person_id and via hr_employees (employee_id)
        $stmtWali = $pdo->prepare("
            SELECT COUNT(*) FROM acad_classes c
            JOIN acad_class_levels l ON c.level_id = l.id
            LEFT JOIN hr_employees e ON c.homeroom_teacher_id = e.id
            WHERE l.unit_id = ? 
            AND (c.homeroom_teacher_id = ? OR e.person_id = ?)
        ");
        $stmtWali->execute([$unit_id, $personId, $personId]);
        if ($stmtWali->fetchColumn() > 0) $roles['wali'] = true;

        // 3. Check Guru (Subject Teacher)
        // Check if assigned to any subject in this unit
        
        // A. Check acad_subject_teachers (Most likely source for "Guru Mapel")
        try {
            $stmtST = $pdo->prepare("
                SELECT COUNT(*) 
                FROM acad_subject_teachers ast
                JOIN acad_subjects s ON ast.subject_id = s.id
                WHERE s.unit_id = ? AND ast.teacher_id = ?
            ");
            $stmtST->execute([$unit_id, $personId]);
            if ($stmtST->fetchColumn() > 0) $roles['guru'] = true;
        } catch (Exception $e) {
            // Table might not exist in some versions
        }

        // B. Check acad_subjects (Direct assignment)
        if (!$roles['guru']) {
            try {
                $stmtGuru = $pdo->prepare("SELECT COUNT(*) FROM acad_subjects WHERE unit_id = ? AND teacher_id = ?");
                $stmtGuru->execute([$unit_id, $personId]);
                if ($stmtGuru->fetchColumn() > 0) $roles['guru'] = true;
            } catch (Exception $e) {}
        }
        
        // C. Check acad_schedules (Scheduled classes)
        if (!$roles['guru']) {
            $stmtSch = $pdo->prepare("
                SELECT COUNT(*) FROM acad_schedules sch
                JOIN acad_classes c ON sch.class_id = c.id
                JOIN acad_class_levels l ON c.level_id = l.id
                WHERE l.unit_id = ? AND sch.teacher_id = ?
            ");
            $stmtSch->execute([$unit_id, $personId]);
            if ($stmtSch->fetchColumn() > 0) $roles['guru'] = true;
        }
    }

    // Grant Guru access to Kepala/Wakasek implicitly
    if ($roles['kepala'] || $roles['wakasek']) {
        $roles['guru'] = true;
    }

    echo json_encode(['success' => true, 'data' => $roles]);
    exit;
}

if ($action === 'get_bk_cases') {
    try {
        if (!$unit_id) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $sql = "
            SELECT ct.id, ct.title, ct.category, ct.severity, ct.status, ct.created_at,
                   p.name as student_name, c.name as class_name
            FROM counseling_tickets ct
            JOIN acad_student_classes sc ON ct.student_id = sc.student_id
            JOIN acad_classes c ON sc.class_id = c.id
            JOIN acad_class_levels l ON c.level_id = l.id
            JOIN core_people p ON ct.student_id = p.id
            WHERE l.unit_id = ? AND sc.status = 'ACTIVE' AND ct.status != 'CLOSED'
            ORDER BY ct.created_at DESC
            LIMIT 50
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$unit_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_teacher_data') {
    try {
        $personId = $_SESSION['person_id'] ?? 0;
        if (!$personId) {
            echo json_encode(['success' => false, 'error' => 'User not linked to person profile']);
            exit;
        }

        $data = [
            'schedule' => [],
            'subjects' => [],
            'inventory' => [],
            'tasks' => []
        ];

        $isAllUnits = (strtolower($unit_code) === 'all');

        // 1. Schedule (Weekly) & Total JP
        // Group by Day
        $sqlSch = "
            SELECT s.day_name as day, s.start_time, s.end_time, 
                   sub.name as subject, c.name as class_name,
                   CONCAT(s.start_time, ' - ', s.end_time) as time,
                   'Ruang Kelas' as room, -- Placeholder
                   TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration_minutes,
                   u.code as unit_code
            FROM acad_schedules s
            JOIN acad_subjects sub ON s.subject_id = sub.id
            JOIN acad_classes c ON s.class_id = c.id
            JOIN acad_class_levels l ON c.level_id = l.id
            JOIN core_units u ON l.unit_id = u.id
            WHERE s.teacher_id = ? 
        ";
        
        $paramsSch = [$personId];
        if (!$isAllUnits) {
            $sqlSch .= " AND l.unit_id = ? ";
            $paramsSch[] = $unit_id;
        }

        $sqlSch .= " ORDER BY FIELD(s.day_name, 'SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU'), c.name, sub.name, s.start_time";

        $stmtSch = $pdo->prepare($sqlSch);
        $stmtSch->execute($paramsSch);
        $rawSchedule = $stmtSch->fetchAll(PDO::FETCH_ASSOC);
        
        $totalMinutes = 0;
        foreach ($rawSchedule as $row) {
            $totalMinutes += (int)$row['duration_minutes'];
        }
        // Assuming 1 JP = 35 minutes (Standard)
        // Or should we count distinct slots? Usually JP is count of slots.
        // Let's count slots since we have raw rows per slot.
        $totalJP = count($rawSchedule);
        
        $data['total_jp'] = $totalJP;

        // Merge consecutive periods
        $mergedSchedule = [];
        $last = null;

        foreach ($rawSchedule as $row) {
            $currentDay = ucfirst(strtolower($row['day']));
            $currentStart = substr($row['start_time'], 0, 5);
            $currentEnd = substr($row['end_time'], 0, 5);
            
            // Check if mergeable with previous entry
            if ($last && 
                $last['day'] === $currentDay && 
                $last['subject'] === $row['subject'] && 
                $last['class'] === $row['class_name'] &&
                $last['end_time_raw'] === $currentStart
            ) {
                // Merge: Update end time
                $last['end_time_raw'] = $currentEnd;
                $last['time'] = $last['start_time_raw'] . ' - ' . $currentEnd;
                
                // Update in array
                $mergedSchedule[count($mergedSchedule) - 1] = $last;
            } else {
                // New Entry
                $newEntry = [
                    'id' => uniqid(),
                    'day' => $currentDay,
                    'start_time_raw' => $currentStart,
                    'end_time_raw' => $currentEnd,
                    'time' => $currentStart . ' - ' . $currentEnd,
                    'subject' => $row['subject'],
                    'class' => $row['class_name'],
                    'room' => $row['room'],
                    'unit' => $row['unit_code']
                ];
                $mergedSchedule[] = $newEntry;
                $last = $newEntry;
            }
        }
        
        // Final Sort by Time (to interleave classes correctly for display)
        usort($mergedSchedule, function($a, $b) {
            // Sort by Day first (though already grouped)
            $days = ['Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6];
            $da = $days[$a['day']] ?? 99;
            $db = $days[$b['day']] ?? 99;
            if ($da !== $db) return $da - $db;
            
            // Sort by Start Time
            return strcmp($a['start_time_raw'], $b['start_time_raw']);
        });

        // Clean up internal keys
        $data['schedule'] = array_map(function($item) {
            unset($item['start_time_raw']);
            unset($item['end_time_raw']);
            return $item;
        }, $mergedSchedule);

        // 2. Subjects (Distinct subjects taught)
        $sqlSub = "
            SELECT DISTINCT sub.id, sub.name, c.name as class_name, u.code as unit_code
            FROM acad_schedules s
            JOIN acad_subjects sub ON s.subject_id = sub.id
            JOIN acad_classes c ON s.class_id = c.id
            JOIN acad_class_levels l ON c.level_id = l.id
            JOIN core_units u ON l.unit_id = u.id
            WHERE s.teacher_id = ? 
        ";
        
        $paramsSub = [$personId];
        if (!$isAllUnits) {
            $sqlSub .= " AND l.unit_id = ? ";
            $paramsSub[] = $unit_id;
        }

        $stmtSub = $pdo->prepare($sqlSub);
        $stmtSub->execute($paramsSub);
        $rawSubs = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
        
        $subjectsMap = [];
        $totalSubjectsTaught = 0; // Count of distinct Subject-Class pairs
        
        foreach ($rawSubs as $r) {
            if (!isset($subjectsMap[$r['id']])) {
                $subjectsMap[$r['id']] = [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'classes' => [],
                    'students' => 0 // Would need another query to count students
                ];
            }
            // Append unit to class name if All Units
            $className = $r['class_name'];
            if ($isAllUnits) {
                $className .= ' (' . $r['unit_code'] . ')';
            }
            $subjectsMap[$r['id']]['classes'][] = $className;
            $totalSubjectsTaught++;
        }
        $data['subjects'] = array_values($subjectsMap);
        $data['total_subjects_taught'] = $totalSubjectsTaught;

        // 3. Inventory
        // Check by holder_id (new) OR location (fallback)
        // We'll just try holder_id first. If table doesn't have it (migration failed), it throws.
        // So we wrap in try-catch or assume it exists since we ran migration.
        $sqlInv = "
            SELECT id, name, code, condition_status, location
            FROM inv_assets_movable 
            WHERE holder_id = ?
        ";
        try {
            $stmtInv = $pdo->prepare($sqlInv);
            $stmtInv->execute([$personId]);
            $data['inventory'] = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback if holder_id column missing or error
            $data['inventory'] = []; 
        }

        // 4. Tasks
        $sqlTask = "
            SELECT id, title, description, due_date, status, priority
            FROM acad_teacher_tasks
            WHERE teacher_id = ?
            ORDER BY due_date ASC
        ";
        try {
            $stmtTask = $pdo->prepare($sqlTask);
            $stmtTask->execute([$personId]);
            $data['tasks'] = $stmtTask->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
             $data['tasks'] = [];
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

try {
    $response = [
        'success' => true,
        'data' => [
            'students' => ['total' => 0, 'male' => 0, 'female' => 0, 'boarding' => 0, 'non_boarding' => 0],
            'classes' => [],
            'subjects' => ['total' => 0],
            'attendance' => ['present_pct' => 0, 'absent_pct' => 0],
            'bk' => ['high' => 0, 'medium' => 0, 'low' => 0, 'total' => 0],
            'inventory' => ['total_items' => 0, 'good' => 0, 'bad' => 0],
            'lock_status' => []
        ]
    ];

    // 1. Resolve Unit ID (Already done above)
    // $unit_id is available
    
    if ($unit_id) {
        // 2. Students & Classes
        // Get all classes for this unit
        $sqlClasses = "
            SELECT c.id, c.name, 
            (SELECT COUNT(*) FROM acad_student_classes sc WHERE sc.class_id = c.id AND sc.status = 'ACTIVE') as student_count,
            (SELECT COUNT(*) FROM acad_student_classes sc JOIN core_people cp ON sc.student_id = cp.id WHERE sc.class_id = c.id AND sc.status = 'ACTIVE' AND (cp.gender = 'L' OR cp.gender = 'MALE' OR cp.gender = 'M')) as male_count,
            (SELECT COUNT(*) FROM acad_student_classes sc JOIN core_people cp ON sc.student_id = cp.id WHERE sc.class_id = c.id AND sc.status = 'ACTIVE' AND (cp.gender = 'P' OR cp.gender = 'FEMALE' OR cp.gender = 'F')) as female_count
            FROM acad_classes c
            JOIN acad_class_levels l ON c.level_id = l.id
            WHERE l.unit_id = ?
            ORDER BY l.order_index, c.name
        ";
        $stmtClasses = $pdo->prepare($sqlClasses);
        $stmtClasses->execute([$unit_id]);
        $classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
        
        $response['data']['classes'] = $classes;

        // Aggregate Students
        foreach ($classes as $c) {
            $response['data']['students']['total'] += $c['student_count'];
            $response['data']['students']['male'] += $c['male_count'];
            $response['data']['students']['female'] += $c['female_count'];
        }

        // Boarding Count (Approximate by checking boarding_students table joined with student classes in this unit)
        // This might be expensive, so we'll do a separate query
        $sqlBoarding = "
            SELECT COUNT(DISTINCT bs.student_id)
            FROM boarding_students bs
            JOIN acad_student_classes sc ON bs.student_id = sc.student_id
            JOIN acad_classes c ON sc.class_id = c.id
            JOIN acad_class_levels l ON c.level_id = l.id
            WHERE l.unit_id = ? AND sc.status = 'ACTIVE' AND bs.boarding_status = 'ACTIVE'
        ";
        $stmtBoarding = $pdo->prepare($sqlBoarding);
        $stmtBoarding->execute([$unit_id]);
        $boardingCount = $stmtBoarding->fetchColumn();
        $response['data']['students']['boarding'] = $boardingCount;
        $response['data']['students']['non_boarding'] = $response['data']['students']['total'] - $boardingCount;

        // 3. Subjects
        $stmtSub = $pdo->prepare("SELECT COUNT(*) FROM acad_subjects WHERE unit_id = ?");
        $stmtSub->execute([$unit_id]);
        $response['data']['subjects']['total'] = $stmtSub->fetchColumn();

        // 4. Attendance (Today)
        // Logic: Count 'PRESENT' / Total for today's sessions in this unit
        // Simplified: Check daily attendance records if available
        $today = date('Y-m-d');
        // This is complex without a specific summary table. We'll leave as 0 or mock if no data.
        
        // 5. BK (Counseling)
        // Filter tickets by students in this unit
        $sqlBK = "
            SELECT ct.severity, COUNT(*) as c
            FROM counseling_tickets ct
            JOIN acad_student_classes sc ON ct.student_id = sc.student_id
            JOIN acad_classes c ON sc.class_id = c.id
            JOIN acad_class_levels l ON c.level_id = l.id
            WHERE l.unit_id = ? AND sc.status = 'ACTIVE' AND ct.status != 'CLOSED'
            GROUP BY ct.severity
        ";
        $stmtBK = $pdo->prepare($sqlBK);
        $stmtBK->execute([$unit_id]);
        $bkRows = $stmtBK->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bkRows as $r) {
            $sev = strtolower($r['severity']); // low, medium, high
            $cnt = (int)$r['c'];
            if (isset($response['data']['bk'][$sev])) {
                $response['data']['bk'][$sev] = $cnt;
            }
            $response['data']['bk']['total'] += $cnt;
        }

        // 6. Inventory
        // Filter by division_code matching unit_code
        // Note: Inventory uses 'division_code' string, not unit_id.
        $stmtInv = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN condition_status = 'GOOD' THEN 1 ELSE 0 END) as good
            FROM inv_assets_movable 
            WHERE division_code = ?
        ");
        $stmtInv->execute([$unit_code]); // e.g., 'SD'
        $invData = $stmtInv->fetch(PDO::FETCH_ASSOC);
        if ($invData) {
            $response['data']['inventory']['total_items'] = (int)$invData['total'];
            $response['data']['inventory']['good'] = (int)$invData['good'];
            $response['data']['inventory']['bad'] = (int)$invData['total'] - (int)$invData['good'];
        }
        
        // 7. Lock Status
        // Get lock status for classes in this unit
        // We can reuse the logic from api/lock_check.php but simplified
        // We'll just return the list of classes that are LOCKED
        
        // Fetch last Friday date
        $d = new DateTime();
        if ($d->format('D') != 'Fri') {
            $d->modify('last friday');
        }
        $fridayDate = $d->format('Y-m-d');
        
        // Check facilities report for this Friday
        // Assuming table 'fac_daily_reports' exists and has class_id and date
        // If not exists, we assume locked? 
        // Let's check `api/lock_check.php` logic to be consistent.
        // For now, return empty lock list to avoid errors, user can use the specific lock tab.
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
