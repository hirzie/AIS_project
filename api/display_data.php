<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    echo json_encode(['status' => 'error', 'message' => 'Slug is required']);
    exit;
}

try {
    // 1. Get Class Info
    $stmt = $pdo->prepare("SELECT id, name, slug FROM acad_classes WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        echo json_encode(['status' => 'error', 'message' => 'Class not found']);
        exit;
    }

    // 2. Determine Day and Time
    $days = [
        'Sunday' => 'Ahad', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $dayName = $days[date('l')];
    $currentTime = date('H:i:s');
    $currentDate = date('Y-m-d');
    
    // Debugging Time Override
    if (isset($_GET['debug_time'])) {
        $currentTime = $_GET['debug_time'];
    }
    if (isset($_GET['debug_day'])) {
        $dayName = $_GET['debug_day'];
    }

    // 3. Get Messages (Ticker & Urgent)
    $stmtMsg = $pdo->prepare("SELECT id, type, content, is_active, metadata FROM app_display_messages WHERE target_slug = ? AND is_active = 1");
    $stmtMsg->execute([$slug]);
    $messages = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);
    
    $ticker = null;
    $urgent = null;
    $imageOverride = null;
    $aiOverride = null;
    $pdfOverrideUrl = null;
    $pdfPage = 1;
    $pdfId = null;
    $laserActive = 0;
    $laserX = 0;
    $laserY = 0;

    foreach ($messages as $msg) {
        if ($msg['type'] === 'INFO') $ticker = $msg['content'];
        if ($msg['type'] === 'URGENT') $urgent = $msg['content'];
        if ($msg['type'] === 'IMAGE_OVERRIDE') $imageOverride = $msg['content'];
        if ($msg['type'] === 'AI_OVERRIDE') $aiOverride = $msg['content'];
        if ($msg['type'] === 'PDF_OVERRIDE') {
            $pdfOverrideUrl = $msg['content'];
            $meta = json_decode($msg['metadata'] ?? '{}', true);
            $pdfPage = $meta['current_page'] ?? 1;
            $pdfId = $msg['id'];
            $laserActive = $meta['laser_active'] ?? 0;
            $laserX = $meta['laser_x'] ?? 0;
            $laserY = $meta['laser_y'] ?? 0;
        }
    }

    // 4. Get Current Material (uploaded by teacher)
    $stmtMat = $pdo->prepare("SELECT title, content FROM app_daily_materials WHERE class_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC LIMIT 1");
    $stmtMat->execute([$class['id'], $currentDate]);
    $material = $stmtMat->fetch(PDO::FETCH_ASSOC);

    // 5. Get Schedule for Today
    // We join with subjects and employees/people to get details
    // Only fetch necessary fields
    $sqlSchedule = "
        SELECT 
            s.id, s.start_time, s.end_time,
            subj.name as subject_name,
            p.name as teacher_name,
            p.photo_url as teacher_photo,
            p.gender as teacher_gender
        FROM acad_schedules s
        JOIN acad_subjects subj ON s.subject_id = subj.id
        LEFT JOIN hr_employees e ON s.teacher_id = e.id
        LEFT JOIN core_people p ON e.person_id = p.id
        WHERE s.class_id = ? 
        AND s.day_name = ?
        ORDER BY s.start_time ASC
    ";
    
    $stmtSched = $pdo->prepare($sqlSchedule);
    $stmtSched->execute([$class['id'], $dayName]);
    $todaysSessions = $stmtSched->fetchAll(PDO::FETCH_ASSOC);

    // 6. Determine State
    $state = 'HOME'; // Default
    $currentSession = null;
    $nextSession = null;
    $countdownTo = null;

    // Check for Demo Override
    $demoState = $_GET['demo_state'] ?? null;

    if ($demoState) {
        $state = $demoState;
        
        // Prepare Mock Data for Demo
        if ($state === 'CLASS') {
            // Use real subject if available, otherwise mock
            $mockSubject = !empty($todaysSessions) ? $todaysSessions[0]['subject_name'] : 'Matematika (Demo)';
            $mockTeacher = !empty($todaysSessions) ? ($todaysSessions[0]['teacher_name'] ?? 'Guru Demo') : 'Ahmad Fulan, S.Pd';
            $mockPhoto = !empty($todaysSessions) ? ($todaysSessions[0]['teacher_photo'] ?? null) : null;
            
            $currentSession = [
                'subject_name' => $mockSubject,
                'teacher_name' => $mockTeacher,
                'teacher_photo' => $mockPhoto,
                'start_time' => date('H:i:s', strtotime('-15 minutes')),
                'end_time' => date('H:i:s', strtotime('+45 minutes'))
            ];
        } elseif ($state === 'BREAK') {
            $mockNextSubject = !empty($todaysSessions) ? $todaysSessions[0]['subject_name'] : 'Bahasa Indonesia (Demo)';
            
            $nextSession = [
                'subject_name' => $mockNextSubject,
                'start_time' => date('H:i:s', strtotime('+5 minutes')) // 5 min break
            ];
            $countdownTo = $nextSession['start_time'];
        }
        // HOME doesn't need extra data
    } else {
        foreach ($todaysSessions as $session) {
            if ($currentTime >= $session['start_time'] && $currentTime < $session['end_time']) {
                $state = 'CLASS';
                $currentSession = $session;
                break;
            } elseif ($currentTime < $session['start_time']) {
                // Found a future session
                if ($nextSession === null) {
                    $nextSession = $session;
                }
            }
        }

        // Refine State Logic
        if ($state === 'CLASS') {
            // Already set
        } elseif ($nextSession) {
            // We are before a session, so it's either early morning or break
            $state = 'BREAK';
            $countdownTo = $nextSession['start_time'];
        } else {
            // No current session and no next session -> HOME
            $state = 'HOME';
        }
    }

    // Format Response Data
    $response = [
        'status' => 'success',
        'state' => $state, // CLASS, BREAK, HOME
        'timestamp' => time(),
        'class_name' => $class['name'],
        'messages' => [
            'ticker' => $ticker,
            'urgent' => $urgent,
            'image_override' => $imageOverride,
            'ai_override' => $aiOverride
        ]
    ];

    if ($pdfOverrideUrl) {
        $response['pdf_override'] = [
            'id' => $pdfId,
            'url' => $pdfOverrideUrl,
            'page' => $pdfPage,
            'laser_active' => $laserActive,
            'laser_x' => $laserX,
            'laser_y' => $laserY
        ];
    }

    if ($state === 'CLASS') {
        $end = strtotime($currentSession['end_time']);
        $start = strtotime($currentSession['start_time']);
        $now = strtotime($currentTime);
        $total = $end - $start;
        $elapsed = $now - $start;
        $percent = ($total > 0) ? round(($elapsed / $total) * 100) : 0;

        $response['data'] = [
            'subject' => $currentSession['subject_name'],
            'teacher' => $currentSession['teacher_name'] ?: 'Guru Mata Pelajaran',
            'photo' => $currentSession['teacher_photo'] ?: 'default.png', // Simplified logic in frontend
            'topic' => $material['title'] ?? 'Materi Pembelajaran Rutin',
            'subtopic' => $material['content'] ?? '',
            'start_time' => substr($currentSession['start_time'], 0, 5),
            'end_time' => substr($currentSession['end_time'], 0, 5),
            'progress' => $percent,
            'remaining_minutes' => floor(($end - $now) / 60)
        ];
    } elseif ($state === 'BREAK') {
        $response['data'] = [
            'next_subject' => $nextSession['subject_name'],
            'next_start' => substr($nextSession['start_time'], 0, 5),
            'countdown_to' => $countdownTo
        ];
    } elseif ($state === 'HOME') {
        // Random Quote/Hadith
        $quotes = [
            ['text' => 'Barangsiapa menempuh jalan untuk menuntut ilmu, Allah akan mudahkan baginya jalan menuju surga.', 'source' => 'HR. Muslim'],
            ['text' => 'Ilmu itu bagaikan binatang buruan, dan tulisan adalah ikatannya.', 'source' => 'Imam Syafi\'i'],
            ['text' => 'Tuntutlah ilmu dari buaian hingga liang lahat.', 'source' => 'Mahfudzot'],
            ['text' => 'Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.', 'source' => 'HR. Ahmad']
        ];
        $quote = $quotes[array_rand($quotes)];
        
        $response['data'] = [
            'quote' => $quote['text'],
            'source' => $quote['source']
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
