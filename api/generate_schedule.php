<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$class_id = $data['class_id'] ?? null;
$academic_year_id = $data['academic_year_id'] ?? null;

if (!$class_id || !$academic_year_id) {
    echo json_encode(['success' => false, 'error' => 'Missing class_id or academic_year_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Get Unit ID
    $stmt = $pdo->prepare("
        SELECT l.unit_id 
        FROM acad_classes c
        JOIN acad_class_levels l ON c.level_id = l.id
        WHERE c.id = ?
    ");
    $stmt->execute([$class_id]);
    $unit_id = $stmt->fetchColumn();

    // 2. Get Time Slots (Ordered)
    // We need 'is_break' info to handle breaks
    $stmt = $pdo->prepare("
        SELECT id, start_time, end_time, is_break
        FROM acad_time_slots 
        WHERE unit_id = ? 
        ORDER BY start_time
    ");
    $stmt->execute([$unit_id]);
    $allSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $seenSlots = [];
    $uniqueSlots = [];
    foreach ($allSlots as $s) {
        $k = $s['start_time'] . '|' . $s['end_time'] . '|' . $s['is_break'];
        if (isset($seenSlots[$k])) continue;
        $seenSlots[$k] = true;
        $uniqueSlots[] = $s;
    }
    $allSlots = $uniqueSlots;

    if (empty($allSlots)) {
        throw new Exception("Belum ada slot waktu (Jam Pelajaran) yang diatur.");
    }

    // Separate Teaching Slots and Break Slots
    // But we need the structure to know continuity
    // Strategy: Map slots by index 0..N per day

    // 3. Get Subject Assignments (with Count and Length)
    $stmt = $pdo->prepare("
        SELECT 
            ast.subject_id, 
            ast.teacher_id, 
            s.name as subject_name,
            COALESCE(ast.weekly_count, 1) as weekly_count,
            COALESCE(ast.session_length, 1) as session_length
        FROM acad_subject_teachers ast
        JOIN acad_subjects s ON ast.subject_id = s.id
        WHERE ast.class_id = ? AND ast.academic_year_id = ?
    ");
    $stmt->execute([$class_id, $academic_year_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($assignments)) {
        throw new Exception("Belum ada Guru Mapel yang diatur untuk kelas ini.");
    }

    // 4. Expand Assignments into Allocatable Blocks
    // Example: Math (Count=2, Length=2) -> [Math(2), Math(2)]
    $blocks = [];
    foreach ($assignments as $assign) {
        $wc = max(1, (int)$assign['weekly_count']);
        $sl = max(1, (int)$assign['session_length']);
        for ($i = 0; $i < $wc; $i++) {
            $blocks[] = [
                'subject_id' => (int)$assign['subject_id'],
                'teacher_id' => (int)$assign['teacher_id'],
                'length' => $sl,
                'subject_name' => $assign['subject_name']
            ];
        }
    }

    // Shuffle blocks
    shuffle($blocks);

    // 5. Delete existing schedule
    $stmt = $pdo->prepare("DELETE FROM acad_schedules WHERE class_id = ?");
    $stmt->execute([$class_id]);

    // 6. Distribution Logic
    $days = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'];
    $scheduleMap = []; // [Day][SlotIndex] = Block

    // Initialize schedule map with nulls and breaks
    foreach ($days as $day) {
        $scheduleMap[$day] = [];
        foreach ($allSlots as $index => $slot) {
            $scheduleMap[$day][$index] = $slot['is_break'] ? 'BREAK' : null;
        }
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO acad_schedules (class_id, subject_id, teacher_id, day_name, start_time, end_time)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $unallocated = [];
    $paramSummary = [];
    foreach ($assignments as $a) {
        $paramSummary[] = "{$a['subject_name']}: weekly_count=" . max(1,(int)$a['weekly_count']) . ", session_length=" . max(1,(int)$a['session_length']);
    }

    foreach ($blocks as $block) {
        $allocated = false;
        
        // Try to find a spot
        // Strategy: Iterate days randomly to spread load
        $tryDays = $days;
        shuffle($tryDays);

        foreach ($tryDays as $day) {
            // Find consecutive slots of length $block['length']
            $slotsCount = count($allSlots);
            
            // Try every possible start position
            // To add randomness within the day, we can shuffle start positions or just iterate
            // Let's iterate linearly for simplicity first, but maybe random start offset?
            
            for ($start = 0; $start <= $slotsCount - $block['length']; $start++) {
                // Check if slots $start to $start + length - 1 are available and NOT breaks
                $fits = true;
                for ($k = 0; $k < $block['length']; $k++) {
                    $currentSlotIdx = $start + $k;
                    if ($scheduleMap[$day][$currentSlotIdx] !== null) { // Occupied or Break
                        $fits = false;
                        break;
                    }
                }

                if ($fits) {
                    // Place the block
                    for ($k = 0; $k < $block['length']; $k++) {
                        $currentSlotIdx = $start + $k;
                        $scheduleMap[$day][$currentSlotIdx] = $block; // Assign reference
                        
                        // Insert to DB
                        $slotData = $allSlots[$currentSlotIdx];
                        $stmtInsert->execute([
                            $class_id, 
                            $block['subject_id'], 
                            $block['teacher_id'], 
                            $day, 
                            $slotData['start_time'], 
                            $slotData['end_time']
                        ]);
                    }
                    $allocated = true;
                    break; // Break day loop
                }
            }
            if ($allocated) break;
        }

        if (!$allocated) {
            $unallocated[] = $block['subject_name'];
        }
    }

    $pdo->commit();

    if (count($unallocated) > 0) {
        $msg = "Jadwal dibuat tapi ada mapel yang tidak muat: " . implode(", ", $unallocated);
        echo json_encode(['success' => true, 'message' => $msg, 'logs' => ["Param: " . implode(" | ", $paramSummary)]]); // Include param logs
    } else {
        echo json_encode(['success' => true, 'message' => 'Jadwal berhasil di-shuffle dengan parameter Count & Length!', 'logs' => ["Param: " . implode(" | ", $paramSummary) ]]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
