<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="data_siswa.csv"');

require_once __DIR__ . '/../config/database.php';

$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    echo "Class ID Required";
    exit;
}

// Fetch Class Info
$stmt = $pdo->prepare("SELECT name FROM acad_classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();
$className = $class ? $class['name'] : 'Unknown_Class';

// Update Filename
header('Content-Disposition: attachment; filename="Siswa_Kelas_' . $className . '.csv"');

// Open Output Stream
$output = fopen('php://output', 'w');

// Headers based on user request (Full Format)
$headers = [
    'No.', 'NIS', 'NISN', 'NIK', 'No UN Sebelumnya', 'PIN', 'Nama', 'Panggilan', 'Kelamin', 'Tahun Masuk',
    'Asal Sekolah', 'No Ijasah', 'Tgl Ijasah', 'Tempat, Tanggal Lahir', 'Alamat', 'Kode Pos', 'Jarak',
    'Telpon', 'HP', 'Email', 'Status', 'Kondisi', 'Kesehatan', 'Bahasa', 'Suku', 'Agama', 'Warga',
    'Berat', 'Tinggi', 'Gol.Darah', 'Anak Ke', 'Bersaudara', 'Status Anak', 'Jml Saudara Kandung', 'Jml Saudara Tiri',
    'Ayah', 'Status Ayah', 'Tmp Lahir Ayah', 'Tgl Lahir Ayah', 'Email', 'PIN Ayah', 'Pendidikan', 'Pekerjaan', 'Penghasilan',
    'Ibu', 'Status Ibu', 'Tmp Lahir Ibu', 'Tgl Lahir Ibu', 'Email', 'PIN Ibu', 'Pendidikan', 'Pekerjaan', 'Penghasilan',
    'Nama Wali', 'Alamat', 'Telpon', 'HP #1', 'HP #2', 'HP #3', 'Hobi', 'Keterangan',
    'Akta Kelahiran', 'Kartu Keluarga', 'Scan Hasil Observasi'
];
fputcsv($output, $headers);

// Fetch Students with ALL Details
$sql = "
    SELECT 
        p.identity_number, 
        p.name, 
        p.gender,
        sd.birth_place,
        sd.birth_date,
        p.status
    FROM acad_student_classes asc_join
    JOIN core_people p ON asc_join.student_id = p.id
    LEFT JOIN acad_student_details sd ON p.id = sd.student_id
    WHERE asc_join.class_id = ? AND asc_join.status = 'ACTIVE'
    ORDER BY p.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$no = 1;
foreach ($students as $s) {
    // Format "Tempat, Tanggal Lahir"
    $ttl = ($s['birth_place'] ?? '') . ', ' . ($s['birth_date'] ? date('d F Y', strtotime($s['birth_date'])) : '');
    
    // Format full address if possible, currently just p.address or details
    $alamat = $s['address'] ?? ''; // Core address usually stores the full string
    
    $row = [
        $no++,
        "'".$s['nis'], // Add quote to prevent Excel auto-format
        "'".($s['nisn'] ?? ''),
        "'".($s['nik'] ?? ''),
        "'".($s['prev_exam_number'] ?? '0'),
        "'".($s['pin'] ?? '0'),
        $s['name'],
        $s['nickname'] ?? '',
        $s['gender'],
        $s['admission_year'] ?? date('Y'),
        $s['school_origin'] ?? '',
        "'".($s['diploma_number'] ?? '0'),
        $s['diploma_date'] ?? '', // Format date if needed
        $ttl,
        $alamat,
        $s['postal_code'] ?? '',
        $s['distance_to_school'] ?? '0',
        "'".($s['phone'] ?? '0'), // Telpon Rumah
        "'".($s['mobile_phone'] ?? '0'), // HP Siswa
        $s['email'] ?? '',
        $s['status'], // Status (Reguler/Active etc)
        $s['special_needs'] ?? 'Reguler', // Kondisi (Assuming special_needs maps to Kondisi)
        $s['health_history'] ?? 'Tidak ada',
        $s['daily_language'] ?? 'Indonesia',
        $s['ethnicity'] ?? '',
        $s['religion'] ?? 'Islam',
        $s['citizenship'] ?? 'WNI',
        $s['weight'] ?? '0.0',
        $s['height'] ?? '0.0',
        $s['blood_type'] ?? '',
        $s['child_order'] ?? '',
        $s['siblings_total'] ?? '',
        $s['child_status'] ?? 'Kandung',
        $s['siblings_biological'] ?? '0',
        $s['siblings_step'] ?? '0',
        
        // Ayah
        $s['father_name'] ?? '',
        $s['father_status'] ?? 'Hidup',
        $s['father_birth_place'] ?? '',
        $s['father_birth_date'] ?? '',
        $s['father_email'] ?? '',
        "'".($s['father_pin'] ?? ''),
        $s['father_education'] ?? '',
        $s['father_job'] ?? '',
        $s['father_income'] ?? '0',

        // Ibu
        $s['mother_name'] ?? '',
        $s['mother_status'] ?? 'Hidup',
        $s['mother_birth_place'] ?? '',
        $s['mother_birth_date'] ?? '',
        $s['mother_email'] ?? '',
        "'".($s['mother_pin'] ?? ''),
        $s['mother_education'] ?? '',
        $s['mother_job'] ?? '',
        $s['mother_income'] ?? '0',

        // Wali
        $s['guardian_name'] ?? '',
        $s['guardian_address'] ?? '',
        "'".($s['guardian_phone'] ?? '0'),
        "'".($s['guardian_mobile_1'] ?? '0'),
        "'".($s['guardian_mobile_2'] ?? '0'),
        "'".($s['guardian_mobile_3'] ?? '0'),

        $s['hobbies'] ?? '',
        $s['remarks'] ?? '',

        // Files (Just yes/no or path? Request showed empty columns mostly)
        $s['file_birth_cert'] ? 'Ada' : '-',
        $s['file_family_card'] ? 'Ada' : '-',
        $s['file_observation'] ? 'Ada' : '-'
    ];

    fputcsv($output, $row);
}

fclose($output);
?>

