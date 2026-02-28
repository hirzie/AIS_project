<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Support JSON payload OR Multipart/Form-data (File)
    // If JSON payload (from SheetJS frontend conversion)
    if ($input && isset($input['data'])) {
        $rows = $input['data'];
        $unit_id = $input['unit_id'] ?? null;
        $class_id = $input['class_id'] ?? null;
    } 
    // Fallback to CSV File Upload (Legacy)
    elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $unit_id = $_POST['unit_id'] ?? null;
        $class_id = $_POST['class_id'] ?? null;
        
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) throw new Exception('Cannot open file');
        
        // Use the existing CSV logic, but convert to array of assoc arrays first
        $headers = fgetcsv($handle);
        if (!$headers) throw new Exception('Empty CSV file');
        
        // Clean headers
        $headers = array_map(function($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // BOM
            return trim($h);
        }, $headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($headers)) continue; // Skip malformed
            // Normalize row length
            if (count($row) > count($headers)) $row = array_slice($row, 0, count($headers));
            $rows[] = array_combine($headers, $row);
        }
        fclose($handle);
    } else {
        throw new Exception('No valid data provided (JSON or CSV File)');
    }

    if (!$unit_id && $class_id) {
        // Fallback: Fetch unit_id from class if missing
        $stmt = $pdo->prepare("SELECT l.unit_id FROM acad_classes c JOIN acad_class_levels l ON c.level_id = l.id WHERE c.id = ?");
        $stmt->execute([$class_id]);
        $unit_id = $stmt->fetchColumn();
    }

    if (!$unit_id) {
        throw new Exception('Unit ID is required');
    }

        // Helper function to find key case-insensitive and trim
    $findKey = function($needle, $haystack) {
        $needle = trim(strtolower($needle));
        foreach ($haystack as $key => $val) {
            if (trim(strtolower($key)) === $needle) return $val;
        }
        return null;
    };

    $pdo->beginTransaction();

    $successCount = 0;
    $errors = [];
    $debug_info = [];

    foreach ($rows as $index => $data) {
        // Clean values (trim, remove leading quotes)
        $data = array_map(function($val) {
            if (is_string($val)) {
                $val = trim($val);
                if (strpos($val, "'") === 0) {
                    $val = substr($val, 1);
                }
            }
            return $val;
        }, $data);

        // Extract Core Data using Case-Insensitive Search
        // Note: CSV Header might be "No.", "NIS", "Nama", etc.
        // Try multiple variations for critical fields
        $nis = $findKey('nis', $data);
        if (!$nis) $nis = $findKey('no induk', $data);
        if (!$nis) $nis = $findKey('nomor induk', $data);
        // Fallback: If NIS empty, try NISN as NIS (Unique ID)
        if (!$nis) $nis = $findKey('nisn', $data);
        
        $name = $findKey('nama', $data);
        if (!$name) $name = $findKey('nama lengkap', $data);
        if (!$name) $name = $findKey('nama siswa', $data);
        
        // Remove "No." column interference
        // We don't use "No." but sometimes it might shift data if header parsing is wrong.
        // But here we use associative array so "No." key just gets ignored.

        $nis = $nis ?? '';
        $name = $name ?? '';
        
        // RELAXED VALIDATION: Allow Name-only import if NIS missing (generate dummy NIS?)
        // Or strict NIS? User said "Key menjadi NIS saja".
        // Let's stick to NIS required.

        if (!$nis) {
             // Try to find ANY numeric column that looks like NIS (length > 3)
             foreach ($data as $k => $v) {
                 if (is_numeric($v) && strlen((string)$v) >= 4 && stripos($k, 'no') === false) {
                     // Potential NIS candidate if key is not "No"
                     // $nis = $v; break; 
                 }
             }
        }
        
        if (!$nis || !$name) {
            // Debugging info for first few errors
            if (count($errors) < 5) {
                 // Clean data for debug log to avoid massive output
                 $debugDataKeys = array_keys($data);
                 $debug_info[] = "Row " . ($index+1) . ": Missing NIS or Name. Found Headers: " . implode(', ', $debugDataKeys) . ". Values: NIS='$nis', Name='$name'";
            }
            $errors[] = "Skipped row " . ($index+1) . ": Missing NIS or Name.";
            continue;
        }

        // 1. Upsert Core People
        $stmt = $pdo->prepare("SELECT id FROM core_people WHERE identity_number = ? AND type = 'STUDENT'");
        $stmt->execute([$nis]);
        $person = $stmt->fetch();

        $gender = $findKey('kelamin', $data) ?? 'L';
        // Normalize Gender
        $gender = strtoupper(substr($gender, 0, 1)); 
        if ($gender !== 'P') $gender = 'L';

        $phone = $findKey('telpon', $data) ?? '';
        $email = $findKey('email', $data) ?? '';
        $address = $findKey('alamat', $data) ?? '';

        if ($person) {
            $person_id = $person['id'];
            // UPDATE: Check if status is inactive, if so, reactivate? 
            // User said: "saya hapus manual siswa di db" -> if soft deleted, we might need to reactivate.
            // But code says "DELETE FROM core_people" in manage_student.php, so it should be hard delete.
            // If hard deleted, $person should be false.
            
            // If person exists, update it.
            $stmt = $pdo->prepare("UPDATE core_people SET name = ?, gender = ?, phone = ?, email = ?, address = ?, status = 'ACTIVE' WHERE id = ?");
            $stmt->execute([$name, $gender, $phone, $email, $address, $person_id]);
        } else {
            // INSERT
            $stmt = $pdo->prepare("INSERT INTO core_people (name, identity_number, gender, type, unit_id, phone, email, address, status, created_at) VALUES (?, ?, ?, 'STUDENT', ?, ?, ?, ?, 'ACTIVE', NOW())");
            $stmt->execute([$name, $nis, $gender, $unit_id, $phone, $email, $address]);
            $person_id = $pdo->lastInsertId();
        }

        // 2. Insert/Update Student Details
        // Helper to get data safely
        $get = function($key) use ($data, $findKey) { return $findKey($key, $data) ?? ''; };
        
        // Split Birth Place/Date if combined
        $birth_place = '';
        $birth_date = null;
        if ($combined = $get('Tempat, Tanggal Lahir')) {
            $parts = explode(',', $combined);
            $birth_place = trim($parts[0]);
            if (isset($parts[1])) {
                $dateStr = trim($parts[1]);
                // Try to parse date (assuming d-m-Y or Y-m-d or d F Y or d/m/Y)
                $time = strtotime($dateStr);
                // Handle d/m/Y or d F Y format issues if strtotime fails or returns 1970
                if (!$time) {
                     // Simple regex check for DD Fmmm YYYY (e.g., 12 Juni 2018)
                     $months = [
                        'Januari'=>'01', 'Februari'=>'02', 'Maret'=>'03', 'April'=>'04', 'Mei'=>'05', 'Juni'=>'06',
                        'Juli'=>'07', 'Agustus'=>'08', 'September'=>'09', 'Oktober'=>'10', 'November'=>'11', 'Desember'=>'12'
                     ];
                     foreach($months as $ind => $num) {
                         if (stripos($dateStr, $ind) !== false) {
                             $dateStr = str_ireplace($ind, $num, $dateStr); // Replace month name with number
                             // Now it might look like "12 06 2018", let's try to standardize
                             $dateStr = str_replace(' ', '-', $dateStr);
                             break;
                         }
                     }
                     $time = strtotime($dateStr);
                }
                if ($time) $birth_date = date('Y-m-d', $time);
            }
        }

        // Check if details exist
        $stmt = $pdo->prepare("SELECT id FROM acad_student_details WHERE student_id = ?");
        $stmt->execute([$person_id]);
        $details = $stmt->fetch();

        $fields = [
            'nisn' => $get('NISN'),
            'nik' => $get('NIK'),
            'prev_exam_number' => $get('No UN Sebelumnya'),
            'pin' => $get('PIN'),
            'nickname' => $get('Panggilan'),
            'admission_year' => (int)$get('Tahun Masuk'),
            'school_origin' => $get('Asal Sekolah'),
            'diploma_number' => $get('No Ijasah'),
            'diploma_date' => $get('Tgl Ijasah') ? date('Y-m-d', strtotime($get('Tgl Ijasah'))) : null,
            'birth_place' => $birth_place,
            'birth_date' => $birth_date,
            'postal_code' => $get('Kode Pos'),
            'distance_to_school' => (float)$get('Jarak'),
            'mobile_phone' => $get('HP'),
            'special_needs' => $get('Kondisi'),
            'health_history' => $get('Kesehatan'),
            'daily_language' => $get('Bahasa'),
            'ethnicity' => $get('Suku'),
            'religion' => $get('Agama'),
            'citizenship' => $get('Warga'),
            'weight' => (float)$get('Berat'),
            'height' => (float)$get('Tinggi'),
            'blood_type' => $get('Gol.Darah'),
            'child_order' => (int)$get('Anak Ke'),
            'siblings_total' => (int)$get('Bersaudara'),
            'child_status' => $get('Status Anak'),
            'siblings_biological' => (int)$get('Jml Saudara Kandung'),
            'siblings_step' => (int)$get('Jml Saudara Tiri'),
            'father_name' => $get('Ayah'), // Or 'Nama Ayah' if header varies
            'father_status' => $get('Status Ayah'),
            'father_birth_place' => $get('Tmp Lahir Ayah'),
            'father_birth_date' => $get('Tgl Lahir Ayah') ? (strtotime($get('Tgl Lahir Ayah')) ? date('Y-m-d', strtotime($get('Tgl Lahir Ayah'))) : null) : null,
            'father_email' => $get('Email Ayah'),
            'father_pin' => $get('PIN Ayah'),
            'father_education' => $get('Pendidikan Ayah'),
            'father_job' => $get('Pekerjaan Ayah'),
            'father_income' => $get('Penghasilan Ayah'),
            'mother_name' => $get('Ibu'), // Or 'Nama Ibu'
            'mother_status' => $get('Status Ibu'),
            'mother_birth_place' => $get('Tmp Lahir Ibu'),
            'mother_birth_date' => $get('Tgl Lahir Ibu') ? (strtotime($get('Tgl Lahir Ibu')) ? date('Y-m-d', strtotime($get('Tgl Lahir Ibu'))) : null) : null,
            'mother_email' => $get('Email Ibu'),
            'mother_pin' => $get('PIN Ibu'),
            'mother_education' => $get('Pendidikan Ibu'),
            'mother_job' => $get('Pekerjaan Ibu'),
            'mother_income' => $get('Penghasilan Ibu'),
            'guardian_name' => $get('Nama Wali'),
            'guardian_address' => $get('Alamat Wali'),
            'guardian_phone' => $get('Telpon Wali'),
            'guardian_mobile_1' => $get('HP #1'),
            'guardian_mobile_2' => $get('HP #2'),
            'guardian_mobile_3' => $get('HP #3'),
            'hobbies' => $get('Hobi'),
            'remarks' => $get('Keterangan')
        ];

        if ($details) {
            // Update
            $sql = "UPDATE acad_student_details SET ";
            $update_fields = [];
            foreach ($fields as $key => $val) {
                $update_fields[] = "$key = :$key";
            }
            $sql .= implode(', ', $update_fields) . " WHERE id = :id";
            $fields['id'] = $details['id'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($fields);
        } else {
            // Insert
            $fields['student_id'] = $person_id;
            $cols = array_keys($fields);
            $sql = "INSERT INTO acad_student_details (" . implode(', ', $cols) . ") VALUES (:" . implode(', :', $cols) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($fields);
        }

        // 3. Assign Class
        if ($class_id) {
            // Check existing assignment
            $stmt = $pdo->prepare("SELECT id, class_id FROM acad_student_classes WHERE student_id = ? AND status = 'ACTIVE'");
            $stmt->execute([$person_id]);
            $currentClass = $stmt->fetch();

            if (!$currentClass) {
                $stmt = $pdo->prepare("INSERT INTO acad_student_classes (student_id, class_id, status) VALUES (?, ?, 'ACTIVE')");
                $stmt->execute([$person_id, $class_id]);
            } else {
                // If student is already in THIS class, do nothing.
                // If student is in ANOTHER class, MOVE them? 
                // Or maybe the user wants to re-import to fix data, so we ensure they are in the target class.
                if ($currentClass['class_id'] != $class_id) {
                    // Move class logic
                    $stmt = $pdo->prepare("UPDATE acad_student_classes SET status = 'HISTORY' WHERE id = ?");
                    $stmt->execute([$currentClass['id']]);
                    
                    $stmt = $pdo->prepare("INSERT INTO acad_student_classes (student_id, class_id, status) VALUES (?, ?, 'ACTIVE')");
                    $stmt->execute([$person_id, $class_id]);
                }
            }
        }

        $successCount++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Import successful. $successCount students processed.",
        'errors' => $errors,
        'debug' => $debug_info // Add debug info to response
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
