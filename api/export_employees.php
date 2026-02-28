<?php
// api/export_employees.php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="data_pegawai_' . date('Y-m-d') . '.xls"');

// Fetch data
$sql = "
    SELECT 
        e.employee_number as NIP,
        p.name as Nama_Lengkap,
        p.gender as Jenis_Kelamin,
        p.birth_place as Tempat_Lahir,
        p.birth_date as Tanggal_Lahir,
        p.address as Alamat,
        p.phone as Telepon,
        p.email as Email,
        e.join_date as Tgl_Mulai_Kerja,
        e.employment_status as Status_Kepegawaian,
        e.employee_type as Bagian,
        p.status as Status_Aktif,
        p.identity_number as No_Identitas,
        p.custom_attributes
    FROM hr_employees e
    JOIN core_people p ON e.person_id = p.id
    ORDER BY p.name ASC
";

$stmt = $pdo->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define headers based on user request + standard fields
$headers = [
    'NIP', 'Nama Lengkap', 'Nama Panggilan', 'NUPTK', 'NRG', 
    'Tgl Mulai Kerja', 'Bagian', 'Status Aktif', 'Jenis Kelamin', 
    'Tempat Lahir', 'Tanggal Lahir', 'Status Pernikahan', 'Agama', 
    'Suku', 'No Identitas', 'Alamat', 'Telepon', 'Handphone', 
    'Email', 'Facebook', 'Twitter', 'Website', 'Keterangan'
];

// Output headers
echo implode("\t", $headers) . "\n";

// Output data
foreach ($employees as $emp) {
    $custom = json_decode($emp['custom_attributes'] ?? '{}', true) ?? [];
    
    $row = [
        $emp['NIP'],
        $emp['Nama_Lengkap'],
        $custom['nickname'] ?? '',
        $custom['nuptk'] ?? '',
        $custom['nrg'] ?? '',
        $emp['Tgl_Mulai_Kerja'],
        $emp['Bagian'],
        $emp['Status_Aktif'],
        $emp['Jenis_Kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan',
        $emp['Tempat_Lahir'],
        $emp['Tanggal_Lahir'],
        $custom['marital_status'] ?? '',
        $custom['religion'] ?? '',
        $custom['ethnicity'] ?? '',
        $emp['No_Identitas'],
        $emp['Alamat'],
        $emp['Telepon'], // Telepon Rumah
        $custom['mobile_phone'] ?? '', // Handphone might be separate in custom if phone is home
        $emp['Email'],
        $custom['facebook'] ?? '',
        $custom['twitter'] ?? '',
        $custom['website'] ?? '',
        $custom['notes'] ?? ''
    ];
    
    // Clean data for CSV/Excel to prevent breaking format
    $clean_row = array_map(function($str) {
        $str = (string)$str;
        $str = str_replace(["\t", "\n", "\r"], " ", $str);
        return $str;
    }, $row);
    
    echo implode("\t", $clean_row) . "\n";
}
?>
