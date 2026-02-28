<?php
// api/import_employees.php
require_once __DIR__ . '/../config/database.php';

// Disable timeout for large imports
set_time_limit(0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid file uploaded']);
    exit;
}

$file = $_FILES['file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not read file']);
    exit;
}

// Map CSV headers to expected keys
// Expected CSV Header: 
// No.,NIP,Nama,Panggilan,NUPTK,NRG,Tgl Mulai Kerja,Bagian,Status,Aktif,Jenis Kelamin,Tempat Lahir,Tanggal Lahir,Nikah,Agama,Suku,No Identitas,Alamat,Telpon,Handphone,Email,Facebook,Twitter,Website,Keterangan

$success_count = 0;
$error_count = 0;
$errors = [];

try {
    $pdo->beginTransaction();

    // Skip header row
    $headers = fgetcsv($handle, 0, ","); 
    // We assume the order matches the user's provided format:
    // 0:No, 1:NIP, 2:Nama, 3:Panggilan, 4:NUPTK, 5:NRG, 6:Tgl Mulai Kerja, 7:Bagian, 
    // 8:Status, 9:Aktif, 10:Jenis Kelamin, 11:Tempat Lahir, 12:Tanggal Lahir, 13:Nikah, 
    // 14:Agama, 15:Suku, 16:No Identitas, 17:Alamat, 18:Telpon, 19:Handphone, 20:Email, 
    // 21:Facebook, 22:Twitter, 23:Website, 24:Keterangan

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Skip empty rows
        if (empty($data[1]) && empty($data[2])) continue;

        try {
            // Mapping Data
            $nip = trim($data[1]);
            $nama = trim($data[2]);
            $panggilan = trim($data[3]);
            $nuptk = trim($data[4]);
            $nrg = trim($data[5]);
            
            // Date Parsing (MM/DD/YYYY or DD/MM/YYYY)
            // User sample: 02/03/2022 (Feb 3rd or Mar 2nd?), 06/25/2025 (June 25th -> MM/DD/YYYY)
            // So format is likely MM/DD/YYYY
            $join_date_raw = trim($data[6]);
            $join_date = null;
            if (!empty($join_date_raw)) {
                $dt = DateTime::createFromFormat('m/d/Y', $join_date_raw);
                if ($dt) $join_date = $dt->format('Y-m-d');
            }

            $bagian_raw = trim($data[7]); // Non Akademik, Akademik
            $employee_type = (stripos($bagian_raw, 'Non') !== false) ? 'NON_ACADEMIC' : 'ACADEMIC';

            $status_raw = trim($data[8]); // SWASTA, PNS
            // Map to ENUM('PERMANENT','CONTRACT','INTERN') - Defaulting to CONTRACT if SWASTA, PERMANENT if PNS
            // Note: Schema might need update if 'SWASTA' is required as ENUM. 
            // For now, let's map: SWASTA -> CONTRACT, PNS -> PERMANENT, HONORER -> CONTRACT
            if (stripos($status_raw, 'PNS') !== false || stripos($status_raw, 'TETAP') !== false) {
                $employment_status = 'PERMANENT';
            } elseif (stripos($status_raw, 'HONOR') !== false) {
                $employment_status = 'INTERN'; // or CONTRACT?
            } else {
                $employment_status = 'CONTRACT'; // Default for SWASTA
            }

            $aktif_raw = trim($data[9]); // 1
            $status_active = ($aktif_raw == '1') ? 'ACTIVE' : 'INACTIVE';

            $gender_raw = trim($data[10]); // l, p
            $gender = (strtolower($gender_raw) == 'l') ? 'L' : 'P';

            $birth_place = trim($data[11]);
            
            $birth_date_raw = trim($data[12]);
            $birth_date = null;
            if (!empty($birth_date_raw)) {
                $dt = DateTime::createFromFormat('m/d/Y', $birth_date_raw);
                if ($dt) $birth_date = $dt->format('Y-m-d');
            }

            $nikah = trim($data[13]);
            $agama = trim($data[14]);
            $suku = trim($data[15]);
            $ktp = trim($data[16]);
            $alamat = trim($data[17]);
            $telpon = trim($data[18]);
            $hp = trim($data[19]);
            $email = trim($data[20]);
            $fb = trim($data[21]);
            $tw = trim($data[22]);
            $web = trim($data[23]);
            $ket = trim($data[24]);

            // Prepare Custom Attributes
            $custom_attributes = [
                'nickname' => $panggilan,
                'nuptk' => $nuptk,
                'nrg' => $nrg,
                'marital_status' => $nikah,
                'religion' => $agama,
                'ethnicity' => $suku,
                'mobile_phone' => $hp,
                'facebook' => $fb,
                'twitter' => $tw,
                'website' => $web,
                'notes' => $ket
            ];

            // 1. Insert into core_people
            // Check if NIP or Identity Number exists to avoid dupes?
            // For bulk import, we might skip or update. Here we insert new.
            // But strict unique check on NIP is in hr_employees.
            
            $stmt = $pdo->prepare("
                INSERT INTO core_people (
                    identity_number, name, gender, birth_place, birth_date, 
                    address, phone, email, type, status, custom_attributes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'TEACHER', ?, ?, NOW())
            ");
            
            $stmt->execute([
                $ktp, $nama, $gender, $birth_place, $birth_date, 
                $alamat, $telpon, $email, $status_active, json_encode($custom_attributes)
            ]);
            
            $person_id = $pdo->lastInsertId();

            // 2. Insert into hr_employees
            $stmt = $pdo->prepare("
                INSERT INTO hr_employees (
                    person_id, employee_number, join_date, 
                    employment_status, employee_type, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $person_id, $nip, $join_date, 
                $employment_status, $employee_type
            ]);

            $success_count++;

        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Row NIP {$data[1]}: " . $e->getMessage();
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'imported' => $success_count,
        'failed' => $error_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

fclose($handle);
?>
