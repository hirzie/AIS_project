<?php
// api/generate_schedule_global.php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
set_time_limit(300); // Allow longer execution for complex shuffles

$data = json_decode(file_get_contents('php://input'), true);
$academic_year_id = $data['academic_year_id'] ?? null;
$target_unit_ids = $data['target_unit_ids'] ?? []; // Array of Unit IDs to shuffle
$scope = $data['scope'] ?? 'selected_units'; // 'single_class' or 'selected_units'
$single_class_id = $data['class_id'] ?? null;

if (!$academic_year_id) {
    echo json_encode(['success' => false, 'error' => 'Missing academic_year_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Determine Target Classes & Units
    $targetClasses = [];
    $unitsInScope = [];

    if ($scope === 'single_class') {
        if (!$single_class_id) throw new Exception("Class ID required");
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, l.unit_id 
            FROM acad_classes c
            JOIN acad_class_levels l ON c.level_id = l.id
            WHERE c.id = ?
        ");
        $stmt->execute([$single_class_id]);
        $cls = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cls) throw new Exception("Class not found");
        $targetClasses[] = $cls;
        $unitsInScope[] = $cls['unit_id'];
    } else {
        if (empty($target_unit_ids)) throw new Exception("Select at least one unit");
        $unitsInScope = $target_unit_ids;
        $inQuery = implode(',', array_fill(0, count($unitsInScope), '?'));
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, l.unit_id 
            FROM acad_classes c
            JOIN acad_class_levels l ON c.level_id = l.id
            WHERE l.unit_id IN ($inQuery)
        ");
        $stmt->execute($unitsInScope);
        $targetClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Load Resources

    // A. Time Slots per Unit
    // Map: $unitSlots[unit_id] = [ {start, end, is_break} ]
    $unitSlots = [];
    $inUnits = implode(',', array_fill(0, count($unitsInScope), '?'));
    $stmt = $pdo->prepare("SELECT * FROM acad_time_slots WHERE unit_id IN ($inUnits) ORDER BY unit_id, start_time");
    $stmt->execute($unitsInScope);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unitSlots[$row['unit_id']][] = $row;
    }

    foreach ($unitSlots as $uid => $slots) {
        $seen = [];
        $clean = [];
        foreach ($slots as $s) {
            $key = $s['start_time'] . '|' . $s['end_time'] . '|' . $s['is_break'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $clean[] = $s;
        }
        $unitSlots[$uid] = $clean;
    }

    // B. Constraints (Global + Unit Specific)
    // We load ALL constraints to be safe, or filter by involved units + null
    $stmt = $pdo->query("SELECT * FROM acad_schedule_constraints"); 
    $rawConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $teacherConstraints = []; // [TeacherID][Day][]
    $subjectConstraints = []; // [SubjectID][Day][]

    foreach ($rawConstraints as $c) {
        // Index by entity
        $target = ($c['type'] === 'TEACHER') ? 'teacherConstraints' : 'subjectConstraints';
        
        // Structure: {start, end, whole, unit_id}
        ${$target}[$c['entity_id']][$c['day_name']][] = $c;
    }

    // C. Global Occupancy Map (Real Time Busy Intervals)
    // Structure: $teacherBusy[TeacherID][Day] = [ {start: '07:00', end: '08:00'} ]
    $teacherBusy = [];
    $days = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'];

    // Load Existing Schedules from NON-TARGET Classes/Units
    // We must exclude the classes we are about to shuffle
    $excludeClassIds = array_column($targetClasses, 'id');
    if (empty($excludeClassIds)) $excludeClassIds = [0];
    $inExclude = implode(',', array_fill(0, count($excludeClassIds), '?'));
    
    // We fetch ALL schedules for current academic year, excluding target classes
    // Note: acad_schedules doesn't have academic_year_id directly, but classes do.
    $stmt = $pdo->prepare("
        SELECT s.teacher_id, s.day_name, s.start_time, s.end_time
        FROM acad_schedules s
        JOIN acad_classes c ON s.class_id = c.id
        WHERE c.academic_year_id = ? 
        AND s.class_id NOT IN ($inExclude)
    ");
    $params = [$academic_year_id, ...$excludeClassIds]; // PHP 7.4+ spread
    // Fixed param merging for older PHP if needed:
    // array_unshift($excludeClassIds, $academic_year_id); $params = $excludeClassIds;
    
    $stmt->execute(array_merge([$academic_year_id], $excludeClassIds));
    $existingSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existingSchedules as $ex) {
        if ($ex['teacher_id']) {
            $teacherBusy[$ex['teacher_id']][$ex['day_name']][] = [
                'start' => $ex['start_time'],
                'end' => $ex['end_time']
            ];
        }
    }

    // Helper: Check Time Overlap
    function checkOverlap($start1, $end1, $start2, $end2) {
        return ($start1 < $end2 && $end1 > $start2);
    }

    // 3. Process Shuffle
    $logs = [];

    // Clear Target Schedules
    // Logic Correction: We need to delete existing schedules for the TARGET classes only.
    $targetClassIds = array_column($targetClasses, 'id');
    if (!empty($targetClassIds)) {
        $inTarget = implode(',', array_fill(0, count($targetClassIds), '?'));
        // Make sure to bind parameters correctly. 
        // Previously we executed on $targetClassIds directly which is correct for one placeholders set.
        $stmtDelete = $pdo->prepare("DELETE FROM acad_schedules WHERE class_id IN ($inTarget)");
        $stmtDelete->execute($targetClassIds);
    }

    // Sort classes by "difficulty"? (Optional, maybe random is fine)
    shuffle($targetClasses);

    foreach ($targetClasses as $targetClass) {
        $cid = $targetClass['id'];
        $cname = $targetClass['name'];
        $uid = $targetClass['unit_id'];
        
        $mySlots = $unitSlots[$uid] ?? [];
        if (empty($mySlots)) {
            $logs[] = "[$cname] Skip: Tidak ada slot waktu di unit ini (Unit ID: $uid).";
            continue;
        }

        // Get Assignments
        $stmt = $pdo->prepare("
            SELECT 
                ast.subject_id, ast.teacher_id, s.name as subject_name,
                COALESCE(ast.weekly_count, 1) as weekly_count,
                COALESCE(ast.session_length, 1) as session_length
            FROM acad_subject_teachers ast
            JOIN acad_subjects s ON ast.subject_id = s.id
            WHERE ast.class_id = ? AND ast.academic_year_id = ?
        ");
        $stmt->execute([$cid, $academic_year_id]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($assignments)) {
            $logs[] = "[$cname] Skip: Belum ada mapel yang ditugaskan ke kelas ini.";
            continue;
        }
        $blocks = [];
        // Summary log for debugging parameter usage
        foreach ($assignments as $a) {
            $wc = max(1, (int)$a['weekly_count']);
            $sl = max(1, (int)$a['session_length']);
            $logs[] = "[$cname] Param: {$a['subject_name']} -> weekly_count=$wc, session_length=$sl, teacher_id=" . (int)$a['teacher_id'];
        }

        foreach ($assignments as $assign) {
            // Validate session length against max slots to avoid infinite loops
            $maxLen = count($mySlots);
            if ($assign['session_length'] > $maxLen) {
                $logs[] = "[$cname] Warning: Durasi mapel {$assign['subject_name']} ({$assign['session_length']}) melebihi total slot ($maxLen). Diskip.";
                continue;
            }

            $wc = max(1, (int)$assign['weekly_count']);
            $sl = max(1, (int)$assign['session_length']);
            for ($i = 0; $i < $wc; $i++) {
                $assign['block_id'] = uniqid(); // Unique ID for this block instance
                $assign['session_length'] = $sl;
                $blocks[] = $assign;
            }
        }
        
        // MCV Heuristic: Sort by most constrained first (Teacher busy-ness)
        // Simplified: Just shuffle for randomness
        shuffle($blocks);

        // Class Schedule Map (to prevent class internal conflict)
        $classBusy = []; // [Day][] = {start, end}

        $unallocated = [];

        foreach ($blocks as $block) {
            $placed = false;
            $tid = $block['teacher_id'];
            $sid = $block['subject_id'];
            $len = $block['session_length'];
            
            // Randomize Days to prevent same pattern for every class
            $tryDays = $days;
            shuffle($tryDays);

            foreach ($tryDays as $day) {
                // Constraint Check: Day Blocker
                // Teacher
                if ($tid && isset($teacherConstraints[$tid][$day])) {
                    foreach ($teacherConstraints[$tid][$day] as $c) {
                        if (($c['unit_id'] === null || $c['unit_id'] == $uid) && $c['is_whole_day']) {
                             // $logs[] = "[$cname] $day: Guru berhalangan seharian."; 
                             continue 2;
                        }
                    }
                }
                // Subject
                if (isset($subjectConstraints[$sid][$day])) {
                    foreach ($subjectConstraints[$sid][$day] as $c) {
                        if (($c['unit_id'] === null || $c['unit_id'] == $uid) && $c['is_whole_day']) {
                            // $logs[] = "[$cname] $day: Mapel diblokir seharian.";
                            continue 2;
                        }
                    }
                }

                $slotsCount = count($mySlots);
                
                // Try slots
                for ($start = 0; $start <= $slotsCount - $len; $start++) {
                    
                    // Define candidate time range
                    $startSlot = $mySlots[$start];
                    $endSlot = $mySlots[$start + $len - 1];
                    
                    // Verify continuity (no breaks in between)
                    $hasBreak = false;
                    for ($k = 0; $k < $len; $k++) {
                        if ($mySlots[$start + $k]['is_break']) { $hasBreak = true; break; }
                    }
                    if ($hasBreak) continue;

                    $timeStart = $startSlot['start_time'];
                    $timeEnd = $endSlot['end_time'];

                    // 1. Class Availability Check
                    $fitsClass = true;
                    if (isset($classBusy[$day])) {
                        foreach ($classBusy[$day] as $busy) {
                            if (checkOverlap($timeStart, $timeEnd, $busy['start'], $busy['end'])) {
                                $fitsClass = false; break;
                            }
                        }
                    }
                    if (!$fitsClass) continue;

                    // 2. Teacher Availability Check (Global Busy)
                    $fitsTeacher = true;
                    if ($tid && isset($teacherBusy[$tid][$day])) {
                        foreach ($teacherBusy[$tid][$day] as $busy) {
                            if (checkOverlap($timeStart, $timeEnd, $busy['start'], $busy['end'])) {
                                $fitsTeacher = false; break;
                            }
                        }
                    }
                    if (!$fitsTeacher) continue;


                    // 3. Teacher Constraints (Time based)
                    $fitsTCon = true;
                    if ($tid && isset($teacherConstraints[$tid][$day])) {
                        foreach ($teacherConstraints[$tid][$day] as $c) {
                            if ($c['is_whole_day']) continue; // Handled above
                            if ($c['unit_id'] !== null && $c['unit_id'] != $uid) continue; // Constraint for other unit
                            
                            if (checkOverlap($timeStart, $timeEnd, $c['start_time'], $c['end_time'])) {
                                $fitsTCon = false; break;
                            }
                        }
                    }
                    if (!$fitsTCon) continue;

                    // 4. Subject Constraints
                    $fitsSCon = true;
                    if (isset($subjectConstraints[$sid][$day])) {
                        foreach ($subjectConstraints[$sid][$day] as $c) {
                            if ($c['is_whole_day']) continue;
                            if ($c['unit_id'] !== null && $c['unit_id'] != $uid) continue;
                            
                            if (checkOverlap($timeStart, $timeEnd, $c['start_time'], $c['end_time'])) {
                                $fitsSCon = false; break;
                            }
                        }
                    }
                    if (!$fitsSCon) continue;

                    // PLACE IT!
                    // Add to DB (per slot)
                    for ($k = 0; $k < $len; $k++) {
                        $s = $mySlots[$start + $k];
                        $pdo->prepare("
                            INSERT INTO acad_schedules (class_id, subject_id, teacher_id, day_name, start_time, end_time)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ")->execute([$cid, $sid, $tid, $day, $s['start_time'], $s['end_time']]);
                    }

                    // Update Maps
                    $classBusy[$day][] = ['start' => $timeStart, 'end' => $timeEnd];
                    if ($tid) {
                        $teacherBusy[$tid][$day][] = ['start' => $timeStart, 'end' => $timeEnd];
                    }

                    $placed = true;
                    break; // Break loop slots
                }
                if ($placed) break; // Break loop days
            }

            if (!$placed) {
                $unallocated[] = $block['subject_name'];
            }
        }

        if (!empty($unallocated)) {
            $logs[] = "[$cname] Gagal muat: " . implode(', ', array_unique($unallocated));
        } else {
            $logs[] = "[$cname] OK.";
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'logs' => $logs]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage() . " Line: " . $e->getLine()]);
}
?>
