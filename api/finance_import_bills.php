<?php
// api/finance_import_bills.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Disable Time Limit for large imports
set_time_limit(0);

// Helper Response
function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// 1. RECEIVE PAYLOAD
$input = file_get_contents('php://input');
$payload = json_decode($input, true);

if (!$payload || !isset($payload['data'])) {
    jsonResponse(false, 'Invalid JSON Data');
}

$rows = $payload['data'];
$processed = 0;
$skipped = 0;
$errors = [];

try {
    $pdo->beginTransaction();

    // CACHE LOOKUPS TO OPTIMIZE
    // 1. Students (Map NIS -> ID)
    $stmtStud = $pdo->query("SELECT id, identity_number, name FROM core_people WHERE type='STUDENT'");
    $studentsMap = []; // NIS -> ID
    $studentNamesMap = []; // Name -> ID (Fallback)
    foreach ($stmtStud->fetchAll() as $s) {
        if ($s['identity_number']) $studentsMap[trim($s['identity_number'])] = $s['id'];
        $studentNamesMap[strtolower(trim($s['name']))] = $s['id'];
    }

    // 2. Academic Years (Map Name -> ID)
    $stmtYears = $pdo->query("SELECT id, name FROM acad_years");
    $yearsMap = [];
    foreach ($stmtYears->fetchAll() as $y) {
        $yearsMap[trim($y['name'])] = $y['id'];
    }

    // 3. Payment Types (Map Name -> ID)
    $stmtTypes = $pdo->query("SELECT id, name FROM fin_payment_types");
    $typesMap = [];
    foreach ($stmtTypes->fetchAll() as $t) {
        $typesMap[strtolower(trim($t['name']))] = $t['id'];
    }

    // PREPARE STATEMENTS
    $insBill = $pdo->prepare("INSERT INTO fin_student_bills (student_id, payment_type_id, academic_year_id, bill_name, amount, amount_paid, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    // PROCESS ROWS
    foreach ($rows as $index => $row) {
        $line = $index + 1;
        
        // 1. EXTRACT DATA
        $nis = trim($row['nis'] ?? '');
        $name = trim($row['nama_siswa'] ?? '');
        $yearName = trim($row['tahun_ajar'] ?? '');
        $typeName = trim($row['jenis_tagihan'] ?? '');
        $amount = (float)($row['tagihan'] ?? 0);
        $paid = (float)($row['terbayar'] ?? 0);
        
        // Validation
        if (empty($nis) && empty($name)) {
            $skipped++; continue; // Empty row
        }

        // 2. FIND STUDENT
        $studentId = null;
        if (!empty($nis) && isset($studentsMap[$nis])) {
            $studentId = $studentsMap[$nis];
        } elseif (!empty($name) && isset($studentNamesMap[strtolower($name)])) {
            $studentId = $studentNamesMap[strtolower($name)];
        }

        if (!$studentId) {
            $errors[] = "Baris $line: Siswa tidak ditemukan (NIS: $nis, Nama: $name)";
            $skipped++;
            continue;
        }

        // 3. FIND/CREATE ACADEMIC YEAR
        $yearId = null;
        if (isset($yearsMap[$yearName])) {
            $yearId = $yearsMap[$yearName];
        } else {
            // Create New Year if missing?
            // Yes, user said "Tahun Ajar Lampau" which might not exist.
            // But usually we want structure. Let's auto-create if valid format YYYY/YYYY
            if (preg_match('/^\d{4}\/\d{4}$/', $yearName)) {
                $parts = explode('/', $yearName);
                $start = $parts[0] . '-07-01';
                $end = $parts[1] . '-06-30';
                
                $stmtInsYear = $pdo->prepare("INSERT INTO acad_years (name, start_date, end_date, is_active) VALUES (?, ?, ?, 0)");
                $stmtInsYear->execute([$yearName, $start, $end]);
                $yearId = $pdo->lastInsertId();
                $yearsMap[$yearName] = $yearId; // Cache it
            } else {
                $errors[] = "Baris $line: Format Tahun Ajar salah (Harus YYYY/YYYY, cth: 2023/2024)";
                $skipped++;
                continue;
            }
        }

        // 4. FIND/CREATE PAYMENT TYPE
        $typeId = null;
        $typeNameLower = strtolower($typeName);
        
        if (isset($typesMap[$typeNameLower])) {
            $typeId = $typesMap[$typeNameLower];
        } else {
            // Auto Create Payment Type
            $newTypeParams = [
                $typeName,
                strtoupper(substr(str_replace(' ', '', $typeName), 0, 5)), // Generate Code
                (strpos($typeNameLower, 'spp') !== false) ? 'MONTHLY' : 'ONCE',
                0
            ];
            
            // Check if code exists? Maybe just random suffix
            $checkCode = $pdo->prepare("SELECT id FROM fin_payment_types WHERE code = ?");
            $checkCode->execute([$newTypeParams[1]]);
            if ($checkCode->fetch()) {
                $newTypeParams[1] .= rand(10,99);
            }

            $stmtInsType = $pdo->prepare("INSERT INTO fin_payment_types (name, code, type, default_amount) VALUES (?, ?, ?, ?)");
            try {
                $stmtInsType->execute($newTypeParams);
                $typeId = $pdo->lastInsertId();
                $typesMap[$typeNameLower] = $typeId; // Cache
            } catch (Exception $e) {
                $errors[] = "Baris $line: Gagal membuat Tipe Tagihan '$typeName': " . $e->getMessage();
                $skipped++;
                continue;
            }
        }

        // 5. INSERT BILL
        $status = ($paid >= $amount) ? 'PAID' : (($paid > 0) ? 'PARTIAL' : 'UNPAID');
        $billName = $typeName . " (" . $yearName . ")"; // e.g. SPP (2023/2024)

        // Check Duplicate? (Student + Type + Year + Amount)
        // Maybe allow duplicates if different months? 
        // But this tool is usually "Total Tagihan Satu Tahun".
        // Let's assume one bill per type per year per student for legacy.
        // OR just insert blindly. Let's Insert.
        
        $insBill->execute([
            $studentId,
            $typeId,
            $yearId,
            $billName,
            $amount,
            $paid,
            $status
        ]);

        $processed++;
    }

    $pdo->commit();
    
    $msg = "Berhasil mengimport $processed data.";
    if ($skipped > 0) $msg .= " ($skipped dilewati/gagal)";
    
    jsonResponse(true, $msg, ['errors' => $errors]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Database Error: ' . $e->getMessage());
}
?>