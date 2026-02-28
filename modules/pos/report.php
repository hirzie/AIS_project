                      LEFT JOIN acad_student_details d ON d.student_id = p.id
                       WHERE p.type='STUDENT' AND p.status='ACTIVE' 
                         AND (d.nisn IS NULL OR d.nisn = '')";
        if ($unitId) { $sqlMissing .= " AND p.unit_id = ?"; $paramsMissing[] = $unitId; }
        $stMissing = $pdo->prepare($sqlMissing);
        $stMissing->execute($paramsMissing);
        $missingNisn = (int)$stMissing->fetchColumn();
        if ($pdo->inTransaction()) $pdo->commit();
        echo json_encode([
            'success' => true,
            'data' => [
                'scope' => $unitId ? $unit : 'ALL',
                'totals' => [
                    'students_active' => $totalActive,
                    'students_in_class_active' => $inClassActive,
                    'students_not_in_class_active' => $notInClassActive,
                    'students_inactive' => $inactive,
                    'male_active' => $male,
                    'female_active' => $female
                ],
                'flags' => [
                    'duplicate_nis' => $dupNis,
                    'multi_active_classes' => $multiClass,
                    'missing_nisn_active' => $missingNisn
                ]
            ]
        ]);
        exit;
    } elseif ($action === 'audit_class_counts') {
        $unit = strtoupper(trim($_REQUEST['unit'] ?? ($input['unit'] ?? 'ALL')));
        $unitId = null;
        if ($unit && $unit !== 'ALL') {
            $stUnit = $pdo->prepare("SELECT id FROM core_units WHERE UPPER(code)=? OR UPPER(receipt_code)=? LIMIT 1");
            $stUnit->execute([$unit, $unit]);
            $uid = $stUnit->fetchColumn();
            if ($uid) $unitId = (int)$uid;
        }
        $params = [];
        $sql = "SELECT c.id AS class_id, c.name AS class_name, l.name AS level_name,
                       COALESCE(SUM(CASE WHEN UPPER(p.status)='ACTIVE' THEN 1 ELSE 0 END),0) AS students_active
                FROM acad_classes c
                JOIN acad_class_levels l ON l.id = c.level_id
                LEFT JOIN acad_student_classes s ON s.class_id = c.id AND s.status='ACTIVE'
                LEFT JOIN core_people p ON p.id = s.student_id AND p.type='STUDENT'
                WHERE 1=1";
        if ($unitId) { $sql .= " AND l.unit_id = ?"; $params[] = $unitId; }
        $sql .= " GROUP BY c.id, c.name, l.name
                  ORDER BY l.name ASC, c.sort_order ASC, c.name ASC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($pdo->inTransaction()) $pdo->commit();
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    } elseif ($action === 'audit_integrity') {
        $unit = strtoupper(trim($_REQUEST['unit'] ?? ($input['unit'] ?? 'ALL')));
        $unitId = null;
        if ($unit && $unit !== 'ALL') {
            $stUnit = $pdo->prepare("SELECT id FROM core_units WHERE UPPER(code)=? OR UPPER(receipt_code)=? LIMIT 1");
            $stUnit->execute([$unit, $unit]);
            $uid = $stUnit->fetchColumn();
            if ($uid) $unitId = (int)$uid;
        }
        $dupCountStmt = $pdo->query("SELECT COUNT(*) FROM (SELECT identity_number FROM core_people WHERE type='STUDENT' GROUP BY identity_number HAVING COUNT(*)>1) t");
        $dupCount = (int)$dupCountStmt->fetchColumn();
        $limboCountStmtSql = "SELECT COUNT(*) FROM core_people p LEFT JOIN acad_student_classes s ON s.student_id = p.id AND s.status='ACTIVE' WHERE p.type='STUDENT' AND p.status='ACTIVE' AND s.student_id IS NULL";
        if ($unitId) $limboCountStmtSql .= " AND p.unit_id = " . (int)$unitId;
        $limboCountStmt = $pdo->query($limboCountStmtSql);
        $limboCount = (int)$limboCountStmt->fetchColumn();
        $brokenFkCountStmt = $pdo->query("SELECT COUNT(*) FROM acad_student_classes s LEFT JOIN core_people p ON p.id = s.student_id WHERE p.id IS NULL");
        $brokenFkCount = (int)$brokenFkCountStmt->fetchColumn();
        $mismatchCountStmt = $pdo->query("SELECT COUNT(*) FROM acad_student_classes s JOIN core_people p ON p.id = s.student_id WHERE p.type <> 'STUDENT'");
        $mismatchCount = (int)$mismatchCountStmt->fetchColumn();
        $studentsTotal = (int)$pdo->query("SELECT COUNT(*) FROM core_people WHERE type='STUDENT'")->fetchColumn();
        $classesTotal = (int)$pdo->query("SELECT COUNT(*) FROM acad_classes")->fetchColumn();
        $assignActiveTotal = (int)$pdo->query("SELECT COUNT(*) FROM acad_student_classes WHERE status='ACTIVE'")->fetchColumn();
        $dupSamplesSql = "SELECT identity_number, COUNT(*) AS cnt FROM core_people WHERE type='STUDENT' GROUP BY identity_number HAVING COUNT(*)>1 ORDER BY cnt DESC LIMIT 20";
        $dupSamples = $pdo->query($dupSamplesSql)->fetchAll(PDO::FETCH_ASSOC);
        $limboSamplesSql = "SELECT p.id, p.name, p.unit_id FROM core_people p LEFT JOIN acad_student_classes s ON s.student_id = p.id AND s.status='ACTIVE' WHERE p.type='STUDENT' AND p.status='ACTIVE' AND s.student_id IS NULL";
        if ($unitId) $limboSamplesSql .= " AND p.unit_id = " . (int)$unitId;
        $limboSamplesSql .= " LIMIT 20";
        $limboSamples = $pdo->query($limboSamplesSql)->fetchAll(PDO::FETCH_ASSOC);
        $brokenFkSamples = $pdo->query("SELECT s.id, s.student_id, s.class_id FROM acad_student_classes s LEFT JOIN core_people p ON p.id = s.student_id WHERE p.id IS NULL LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        $mismatchSamples = $pdo->query("SELECT s.id, s.student_id, s.class_id FROM acad_student_classes s JOIN core_people p ON p.id = s.student_id WHERE p.type <> 'STUDENT' LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        $paramsMissing = [];
        $missingNisnSql = "SELECT p.id, p.name, p.identity_number, c.id AS class_id, c.name AS class_name, l.name AS level_name
                           FROM core_people p
                           LEFT JOIN acad_student_details d ON d.student_id = p.id
                           JOIN acad_student_classes s ON s.student_id = p.id AND s.status='ACTIVE'
                           JOIN acad_classes c ON c.id = s.class_id
                           JOIN acad_class_levels l ON l.id = c.level_id
                           WHERE p.type='STUDENT' AND p.status='ACTIVE'
                             AND (d.nisn IS NULL OR d.nisn = '')";
        if ($unitId) $missingNisnSql .= " AND l.unit_id = " . (int)$unitId;
        $missingNisnSql .= " ORDER BY l.name ASC, c.name ASC LIMIT 50";
        $missingNisnSamples = $pdo->query($missingNisnSql)->fetchAll(PDO::FETCH_ASSOC);
        if ($pdo->inTransaction()) $pdo->commit();
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => [
                    'students_total' => $studentsTotal,
                    'classes_total' => $classesTotal,
                    'assign_active_total' => $assignActiveTotal,
                    'duplicates_identity' => $dupCount,
                    'limbo_students' => $limboCount,
                    'broken_fk_assignments' => $brokenFkCount,
                    'mismatch_assignments' => $mismatchCount
                ],
                'samples' => [
                    'duplicates_identity' => $dupSamples,
                    'limbo_students' => $limboSamples,
                    'broken_fk_assignments' => $brokenFkSamples,
                    'mismatch_assignments' => $mismatchSamples,
                    'missing_nisn_active' => $missingNisnSamples
                ]
            ]
        ]);
        exit;
    } elseif ($action === 'reset_academic_module') {
        require_once __DIR__ . '/../includes/guard.php';
        ais_init_session();
        $role = strtoupper(trim($_SESSION['role'] ?? ''));
        if (!in_array($role, ['ADMIN','SUPERADMIN','MANAGERIAL'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocal = in_array($host, ['localhost','127.0.0.1']) || strpos($host, '192.168.') === 0;
        $site = null;
        if (preg_match('~^/(AIS|AIStest)/~', $uri, $m)) { $site = $m[1]; }
        $allowReset = $isLocal || $site === 'AIStest';
        if (!$allowReset) {
            echo json_encode(['success' => false, 'message' => 'Reset hanya diizinkan di Localhost atau AIStest']); exit;
        }
        $includeClasses = !!($input['include_classes'] ?? ($_REQUEST['include_classes'] ?? false));
        $deleteStudents = !!($input['delete_students'] ?? ($_REQUEST['delete_students'] ?? false));
        if (!$pdo->inTransaction()) { $pdo->beginTransaction(); }
        $deletedSchedules = 0;
        $deletedClasses = 0;
        $deletedAssignments = 0;
        $hasSchedules = $pdo->query("SHOW TABLES LIKE 'acad_schedules'")->rowCount() > 0;
        $hasClasses = $pdo->query("SHOW TABLES LIKE 'acad_classes'")->rowCount() > 0;
        if ($hasSchedules) { $deletedSchedules = (int)$pdo->exec("DELETE FROM acad_schedules"); }
        $deletedAssignments = (int)$pdo->exec("DELETE FROM acad_student_classes");
        if ($includeClasses && $hasClasses) { $deletedClasses = (int)$pdo->exec("DELETE FROM acad_classes"); }
        $deletedStudents = 0; $skippedStudents = 0;
        if ($deleteStudents) {
            $hasFin = $pdo->query("SHOW TABLES LIKE 'fin_transactions'")->rowCount() > 0;
            $candidates = $pdo->query("
                SELECT p.id
                FROM core_people p
                LEFT JOIN core_users u ON u.people_id = p.id
                LEFT JOIN acad_student_classes s ON s.student_id = p.id AND s.status='ACTIVE'
                ".($hasFin ? "LEFT JOIN fin_student_bills fb ON fb.student_id = p.id LEFT JOIN fin_student_savings fs ON fs.student_id = p.id" : "")."
                WHERE p.type='STUDENT'
                  AND u.people_id IS NULL
                  AND s.student_id IS NULL
                  ".($hasFin ? "AND fb.student_id IS NULL AND fs.student_id IS NULL" : "")."
            ")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($candidates)) {
                $in = implode(',', array_fill(0, count($candidates), '?'));
                $stDel = $pdo->prepare("DELETE FROM core_people WHERE id IN ($in)");
                $stDel->execute(array_map('intval', $candidates));
                $deletedStudents = $stDel->rowCount();
            } else {
                $skippedStudents = 0;
            }
        }
        if ($pdo->inTransaction()) $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Reset Akademik selesai',
            'data' => [
                'deleted_schedules' => $deletedSchedules,
                'deleted_assignments' => $deletedAssignments,
                'deleted_classes' => $deletedClasses,
                'deleted_students' => $deletedStudents,
                'skipped_students' => $skippedStudents
            ]
        ]);
        exit;
    }

    // --- SAVE DETAILS (Create & Update) ---
    if (isset($id)) {
        // Check if details exist
        // Check if details exist
        $stmt = $pdo->prepare("SELECT id FROM acad_student_details WHERE student_id = ?");
        $stmt->execute([$id]);
        $details = $stmt->fetch();

        $detailFields = [
            'nisn', 'nik', 'prev_exam_number', 'pin', 'nickname', 'admission_year', 'school_origin',
            'diploma_number', 'diploma_date', 'birth_place', 'birth_date', 'special_needs', 'health_history',
            'weight', 'height', 'blood_type', 'postal_code', 'distance_to_school', 'mobile_phone',
            'daily_language', 'ethnicity', 'religion', 'citizenship', 'child_order', 'siblings_total',
            'child_status', 'siblings_biological', 'siblings_step', 'father_name', 'father_status',
            'father_birth_place', 'father_birth_date', 'father_email', 'father_pin', 'father_education',
            'father_job', 'father_income', 'mother_name', 'mother_status', 'mother_birth_place',
            'mother_birth_date', 'mother_email', 'mother_pin', 'mother_education', 'mother_job',
            'mother_income', 'guardian_name', 'guardian_address', 'guardian_phone', 'guardian_mobile_1',
            'guardian_mobile_2', 'guardian_mobile_3', 'hobbies', 'remarks'
        ];

        // Define date fields to handle empty strings as NULL
         $dateFields = ['diploma_date', 'birth_date', 'father_birth_date', 'mother_birth_date'];
         // Define numeric fields to handle empty strings as NULL
         $numericFields = ['weight', 'height', 'distance_to_school', 'child_order', 'siblings_total', 'siblings_b