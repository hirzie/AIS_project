<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

 

function log_activity($pdo, $module, $category, $action, $entity_type, $entity_id, $title, $description) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        entity_id VARCHAR(64) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$module, $category, $action, 'STUDENT', (string)$entity_id, $title, $description, $userId]);
}

function logDebug($msg) {
    file_put_contents('debug_student.log', date('[Y-m-d H:i:s] ') . print_r($msg, true) . "\n", FILE_APPEND);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

logDebug("Action: $action");
// logDebug($input);

try {
    if ($action === 'audit_stats') {
        $unit = $_GET['unit'] ?? 'ALL';
        
        $params = [];
        $unitFilter = "";
        if ($unit !== 'ALL') {
            $unitFilter = " AND cp.unit_id = (SELECT id FROM core_units WHERE code = ? OR name LIKE ? LIMIT 1)";
            $params[] = $unit;
            $params[] = "%$unit%";
        }

        // Totals
        $stats = [
            'totals' => [
                'students_active' => 0,
                'students_in_class_active' => 0,
                'students_not_in_class_active' => 0,
                'students_inactive' => 0,
                'male_active' => 0,
                'female_active' => 0
            ],
            'flags' => [
                'duplicate_nis' => 0,
                'multi_active_classes' => 0,
                'missing_nisn_active' => 0
            ]
        ];

        // Active Students
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_people cp WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter");
        $stmt->execute($params);
        $stats['totals']['students_active'] = $stmt->fetchColumn();

        // Inactive Students
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_people cp WHERE cp.type = 'STUDENT' AND cp.status != 'ACTIVE' $unitFilter");
        $stmt->execute($params);
        $stats['totals']['students_inactive'] = $stmt->fetchColumn();

        // Gender
        $stmt = $pdo->prepare("SELECT gender, COUNT(*) as cnt FROM core_people cp WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter GROUP BY gender");
        $stmt->execute($params);
        while ($row = $stmt->fetch()) {
            if ($row['gender'] == 'L') $stats['totals']['male_active'] = $row['cnt'];
            if ($row['gender'] == 'P') $stats['totals']['female_active'] = $row['cnt'];
        }

        // In Class vs Not In Class (Active Students)
        // Students with at least one active class
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT cp.id) FROM core_people cp 
                               JOIN acad_student_classes asc_ INNER JOIN acad_classes ac ON asc_.class_id = ac.id
                               ON cp.id = asc_.student_id 
                               WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' AND asc_.status = 'ACTIVE' $unitFilter");
        $stmt->execute($params);
        $inClass = $stmt->fetchColumn();
        $stats['totals']['students_in_class_active'] = $inClass;
        $stats['totals']['students_not_in_class_active'] = $stats['totals']['students_active'] - $inClass;

        // Flags
        // 1. Duplicate NIS (Active)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM (
                                SELECT identity_number FROM core_people cp 
                                WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter
                                GROUP BY identity_number HAVING COUNT(*) > 1
                               ) as sub");
        $stmt->execute($params);
        $stats['flags']['duplicate_nis'] = $stmt->fetchColumn();

        // 2. Multi Active Classes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM (
                                SELECT student_id FROM acad_student_classes asc_
                                JOIN core_people cp ON asc_.student_id = cp.id
                                WHERE asc_.status = 'ACTIVE' AND cp.status = 'ACTIVE' $unitFilter
                                GROUP BY student_id HAVING COUNT(*) > 1
                               ) as sub");
        $stmt->execute($params);
        $stats['flags']['multi_active_classes'] = $stmt->fetchColumn();

        // 3. Missing NISN (Active)
        // Check acad_student_details
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_people cp 
                               LEFT JOIN acad_student_details asd ON cp.id = asd.student_id
                               WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter
                               AND (asd.nisn IS NULL OR asd.nisn = '')");
        $stmt->execute($params);
        $stats['flags']['missing_nisn_active'] = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'data' => $stats]);
        exit;

    } elseif ($action === 'audit_class_counts') {
         $unit = $_GET['unit'] ?? 'ALL';
         $params = [];
         $unitFilter = "";
         if ($unit !== 'ALL') {
            $unitFilter = " AND cp.unit_id = (SELECT id FROM core_units WHERE code = ? OR name LIKE ? LIMIT 1)";
            $params[] = $unit;
            $params[] = "%$unit%";
        }

        // Get class counts
        $sql = "SELECT ac.id as class_id, ac.name as class_name, al.name as level_name,
                COUNT(asc_.student_id) as students_active
                FROM acad_classes ac
                LEFT JOIN acad_levels al ON ac.level_id = al.id
                LEFT JOIN acad_student_classes asc_ ON ac.id = asc_.class_id AND asc_.status = 'ACTIVE'
                LEFT JOIN core_people cp ON asc_.student_id = cp.id AND cp.status = 'ACTIVE'
                WHERE 1=1
                GROUP BY ac.id
                ORDER BY al.order_index, ac.name";
        
        // Note: The unit filter logic for classes is tricky if we filter by student unit.
        // Better to filter classes by unit if possible, but acad_classes doesn't have unit_id directly usually, 
        // it relies on level or we infer from students?
        // Let's assume we filter students by unit, so the count reflects students in that unit.
        // BUT if we want to show ALL classes for that unit...
        // Let's verify table structure. Assuming acad_levels has unit... or core_people has unit.
        // User asked for "audit_class_counts".
        // Let's try to filter by students' unit for the count.
        
        // Actually, usually classes belong to a unit implicitly or explicitly.
        // Let's stick to the query above but apply student filter in the LEFT JOIN if needed?
        // If we filter by unit 'SD', we only count 'SD' students in the class.
        
        $sql = "SELECT ac.id as class_id, ac.name as class_name, al.name as level_name,
                COUNT(CASE WHEN cp.status = 'ACTIVE' $unitFilter THEN 1 END) as students_active
                FROM acad_classes ac
                LEFT JOIN acad_class_levels al ON ac.level_id = al.id
                LEFT JOIN acad_student_classes asc_ ON ac.id = asc_.class_id AND asc_.status = 'ACTIVE'
                LEFT JOIN core_people cp ON asc_.student_id = cp.id
                GROUP BY ac.id
                HAVING students_active > 0 OR '$unit' = 'ALL'
                ORDER BY al.order_index, ac.name";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params); // Params for unit filter inside count
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;

    } elseif ($action === 'audit_integrity') {
         $unit = $_GET['unit'] ?? 'ALL';
         $params = [];
         $unitFilter = "";
         if ($unit !== 'ALL') {
            $unitFilter = " AND cp.unit_id = (SELECT id FROM core_units WHERE code = ? OR name LIKE ? LIMIT 1)";
            $params[] = $unit;
            $params[] = "%$unit%";
        }

        $samples = [];

        // 1. Duplicates
        $stmt = $pdo->prepare("SELECT identity_number, COUNT(*) as cnt 
                               FROM core_people cp 
                               WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter
                               GROUP BY identity_number HAVING COUNT(*) > 1
                               LIMIT 10");
        $stmt->execute($params);
        $samples['duplicates_identity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Limbo (Active but no class)
        $stmt = $pdo->prepare("SELECT cp.id, cp.name 
                               FROM core_people cp
                               LEFT JOIN acad_student_classes asc_ ON cp.id = asc_.student_id AND asc_.status = 'ACTIVE'
                               WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter
                               AND asc_.id IS NULL
                               LIMIT 10");
        $stmt->execute($params);
        $samples['limbo_students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Missing NISN
        $stmt = $pdo->prepare("SELECT cp.id, cp.name, ac.name as class_name, al.name as level_name
                               FROM core_people cp
                               LEFT JOIN acad_student_details asd ON cp.id = asd.student_id
                               LEFT JOIN acad_student_classes asc_ ON cp.id = asc_.student_id AND asc_.status = 'ACTIVE'
                               LEFT JOIN acad_classes ac ON asc_.class_id = ac.id
                               LEFT JOIN acad_class_levels al ON ac.level_id = al.id
                               WHERE cp.type = 'STUDENT' AND cp.status = 'ACTIVE' $unitFilter
                               AND (asd.nisn IS NULL OR asd.nisn = '')
                               LIMIT 10");
        $stmt->execute($params);
        $samples['missing_nisn_active'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => ['samples' => $samples]]);
        exit;
    }

    $pdo->beginTransaction();

    $logAction = null;
    $logTitle = null;
    $logDesc = null;
    $logStudentId = null;

    if ($action === 'create') {
        $nis = $input['nis'] ?? '';
        $name = $input['name'] ?? '';
        $gender = $input['gender'] ?? 'L';
        $unit_id = $input['unit_id'] ?? null;
        $class_id = $input['class_id'] ?? null;
        $status = $input['status'] ?? 'ACTIVE';

        if (!$name || !$nis || !$unit_id) {
            throw new Exception("Data tidak lengkap (Nama, NIS, Unit wajib)");
        }

        // 1. Insert Person
        $stmt = $pdo->prepare("INSERT INTO core_people (name, identity_number, gender, type, unit_id, status, created_at) VALUES (?, ?, ?, 'STUDENT', ?, ?, NOW())");
        $stmt->execute([$name, $nis, $gender, $unit_id, $status]);
        $student_id = $pdo->lastInsertId();

        // 2. Assign Class (Optional)
        if ($class_id) {
            $stmt = $pdo->prepare("INSERT INTO acad_student_classes (student_id, class_id, status) VALUES (?, ?, 'ACTIVE')");
            $stmt->execute([$student_id, $class_id]);
        }
        
        // Prepare ID for details insertion
        $id = $student_id;

        // Continue to details insertion below...
        // Need to set flag to insert details
        $isNew = true;

        $logAction = 'CREATE';
        $logStudentId = $student_id;
        $className = null;
        if ($class_id) {
            $stc = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
            $stc->execute([$class_id]);
            $className = $stc->fetchColumn();
        }
        $logTitle = 'Siswa Ditambahkan • ' . $name;
        $logDesc = "NIS: {$nis}, UnitID: {$unit_id}" . ($class_id ? ", Kelas: [{$class_id}] " . ($className ?: '-') : "");

    } elseif ($action === 'update') {
        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("ID Siswa tidak ditemukan");

        $stmtPrev = $pdo->prepare("SELECT name, identity_number, gender, status FROM core_people WHERE id = ?");
        $stmtPrev->execute([$id]);
        $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC) ?: ['name'=>'','identity_number'=>'','gender'=>'','status'=>''];

        $nis = $input['nis'] ?? '';
        $name = $input['name'] ?? '';
        $gender = $input['gender'] ?? 'L';
        $class_id = $input['class_id'] ?? null;
        $status = $input['status'] ?? 'ACTIVE';
        
        // 1. Update Person
        $stmt = $pdo->prepare("UPDATE core_people SET name = ?, identity_number = ?, gender = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $nis, $gender, $status, $id]);

        // 2. Handle Class Move
        $stmt = $pdo->prepare("SELECT id, class_id FROM acad_student_classes WHERE student_id = ? AND status = 'ACTIVE'");
        $stmt->execute([$id]);
        $current = $stmt->fetch();

        if ($class_id) {
            if (!$current) {
                // Assign new
                $stmt = $pdo->prepare("INSERT INTO acad_student_classes (student_id, class_id, status) VALUES (?, ?, 'ACTIVE')");
                $stmt->execute([$id, $class_id]);
            } elseif ($current['class_id'] != $class_id) {
                // Move class: Deactivate old, Insert new
                $stmt = $pdo->prepare("UPDATE acad_student_classes SET status = 'HISTORY' WHERE id = ?");
                $stmt->execute([$current['id']]);

                $stmt = $pdo->prepare("INSERT INTO acad_student_classes (student_id, class_id, status) VALUES (?, ?, 'ACTIVE')");
                $stmt->execute([$id, $class_id]);
            }
        }

        $isNew = false;

        $logAction = 'UPDATE';
        $logStudentId = $id;
        $changes = [];
        if ($prev['name'] !== $name) $changes[] = "Nama: {$prev['name']} → {$name}";
        if ($prev['identity_number'] !== $nis) $changes[] = "NIS: {$prev['identity_number']} → {$nis}";
        if ($prev['gender'] !== $gender) $changes[] = "Gender: {$prev['gender']} → {$gender}";
        if ($prev['status'] !== $status) $changes[] = "Status: {$prev['status']} → {$status}";
        $classChangeText = null;
        if ($class_id && $current && $current['class_id'] != $class_id) {
            $oldName = null; $newName = null;
            $stOld = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
            $stOld->execute([$current['class_id']]);
            $oldName = $stOld->fetchColumn();
            $stNew = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
            $stNew->execute([$class_id]);
            $newName = $stNew->fetchColumn();
            $classChangeText = "Kelas: [" . $current['class_id'] . "] " . ($oldName ?: '-') . " → [" . $class_id . "] " . ($newName ?: '-');
        }
        $detailKeysProvided = 0;
        $customKeysProvided = 0;
    } elseif ($action === 'delete') {
         // ... delete logic ...
         $id = $input['id'] ?? null;
         if (!$id) throw new Exception("ID Siswa tidak ditemukan");
         
         // ... (Keep existing delete validation logic) ...
         // 1. Check Transactions
        $stmt = $pdo->query("SHOW TABLES LIKE 'fin_transactions'");
        if ($stmt->rowCount() > 0) {
             $tablesToCheck = ['fin_student_bills', 'fin_student_savings'];
             foreach ($tablesToCheck as $table) {
                $checkTable = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($checkTable->rowCount() > 0) {
                    $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                    if (in_array('student_id', $cols)) {
                        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE student_id = ?");
                        $countStmt->execute([$id]);
                        if ($countStmt->fetchColumn() > 0) throw new Exception("Tidak dapat menghapus siswa karena memiliki data keuangan.");
                    }
                }
             }
        }

         $stmt = $pdo->prepare("DELETE FROM acad_student_classes WHERE student_id = ?");
         $stName = $pdo->prepare("SELECT name, identity_number FROM core_people WHERE id = ?");
         $stName->execute([$id]);
         $rowName = $stName->fetch(PDO::FETCH_ASSOC);

         $stmt->execute([$id]);
         $stmt = $pdo->prepare("DELETE FROM core_people WHERE id = ?");
         $stmt->execute([$id]);
         
         $pdo->commit();
         echo json_encode(['success' => true, 'message' => 'Siswa berhasil dihapus']);
         try {
             log_activity($pdo, 'ACADEMIC', 'STUDENT', 'DELETE', 'STUDENT', $id, 'Siswa Dihapus • ' . ($rowName['name'] ?? ('#' . $id)), "NIS: " . ($rowName['identity_number'] ?? '-'));
         } catch (\Throwable $e) {}
         exit;

    } elseif ($action === 'remove_from_class') {
        // ... (Keep existing remove logic) ...
        $student_id = $input['student_id'] ?? null;
        $class_id = $input['class_id'] ?? null;
        if (!$student_id || !$class_id) throw new Exception("Data tidak lengkap");

        $stmt = $pdo->prepare("DELETE FROM acad_student_classes WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$student_id, $class_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Siswa berhasil dikeluarkan dari kelas']);
         try {
             $stStu = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
             $stStu->execute([$student_id]);
             $stuName = $stStu->fetchColumn();
             $stCls = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
             $stCls->execute([$class_id]);
             $clsName = $stCls->fetchColumn();
             log_activity($pdo, 'ACADEMIC', 'STUDENT', 'CLASS_REMOVE', 'STUDENT', $student_id, 'Siswa Dikeluarkan dari Kelas • ' . ($stuName ?: ('#'.$student_id)), "Kelas: [" . $class_id . "] " . ($clsName ?: '-'));
         } catch (\Throwable $e) {}
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
         $numericFields = ['weight', 'height', 'distance_to_school', 'child_order', 'siblings_total', 'siblings_biological', 'siblings_step', 'father_income', 'mother_income', 'admission_year'];

        $updateData = [];
        $sqlParts = [];
        foreach ($detailFields as $field) {
            if (isset($input[$field])) {
                $val = $input[$field];
                
                // Convert empty strings to NULL for date and numeric fields
                if ($val === '') {
                    if (in_array($field, $dateFields) || in_array($field, $numericFields)) {
                        $val = null;
                    }
                }

                $sqlParts[] = "$field = ?";
                $updateData[] = $val;
                $detailKeysProvided++;
            }
        }

        if (!empty($sqlParts)) {
            if ($details) {
                $updateData[] = $details['id'];
                $sql = "UPDATE acad_student_details SET " . implode(', ', $sqlParts) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateData);
            } else {
                $cols = [];
                $vals = [];
                $params = [];
                foreach ($detailFields as $field) {
                    if (isset($input[$field])) {
                        $val = $input[$field];
                        
                        // Convert empty strings to NULL for date and numeric fields
                        if ($val === '') {
                            if (in_array($field, $dateFields) || in_array($field, $numericFields)) {
                                $val = null;
                            }
                        }

                        $cols[] = $field;
                        $vals[] = "?";
                        $params[] = $val;
                        $detailKeysProvided++;
                    }
                }
                $cols[] = "student_id";
                $vals[] = "?";
                $params[] = $id;

                $sql = "INSERT INTO acad_student_details (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
        }

        // --- SAVE CUSTOM VALUES ---
        if (!empty($input['custom_values'])) {
            $customValues = $input['custom_values'];
            logDebug("Saving Custom Values for Student ID $id: " . json_encode($customValues));

            // Fetch Map: field_key => id
            $fieldMap = $pdo->query("SELECT field_key, id FROM core_custom_fields WHERE entity_type='STUDENT'")->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $insStmt = $pdo->prepare("INSERT INTO core_custom_values (custom_field_id, entity_id, field_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)");
            
            foreach ($customValues as $key => $val) {
                if (isset($fieldMap[$key])) {
                    $fieldId = $fieldMap[$key];
                    // Skip if value is null
                    if ($val === null) $val = '';
                    
                    $insStmt->execute([$fieldId, $id, $val]);
                    logDebug("Saved custom field '$key' (ID: $fieldId) = $val");
                    $customKeysProvided++;
                } else {
                    logDebug("Custom field key '$key' not found in definition");
                }
            }
        } else {
            logDebug("No custom values provided");
        }
    }

    $pdo->commit();

    if ($logAction && $logStudentId) {
        try {
            if ($logAction === 'UPDATE') {
                $title = 'Data Siswa Diperbarui • ' . ($name ?: ('#'.$logStudentId));
                $descParts = [];
                if (!empty($changes)) $descParts[] = implode('; ', $changes);
                if ($classChangeText) $descParts[] = $classChangeText;
                if ($detailKeysProvided > 0) $descParts[] = "Bidang detail diubah: " . $detailKeysProvided;
                if ($customKeysProvided > 0) $descParts[] = "Bidang custom diubah: " . $customKeysProvided;
                log_activity($pdo, 'ACADEMIC', 'STUDENT', 'UPDATE', 'STUDENT', $logStudentId, $title, implode(' • ', $descParts));
            } elseif ($logAction === 'CREATE') {
                log_activity($pdo, 'ACADEMIC', 'STUDENT', 'CREATE', 'STUDENT', $logStudentId, $logTitle, $logDesc);
            }
        } catch (\Throwable $e) {}
    }
    echo json_encode(['success' => true, 'message' => $action === 'create' ? 'Siswa berhasil ditambahkan' : 'Data siswa berhasil diperbarui']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    logDebug("Error: " . $e->getMessage());
}
?>
