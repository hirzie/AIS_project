<?php
require_once 'config/database.php';

try {
    // Create kiosk_settings table
    $sql = "CREATE TABLE IF NOT EXISTS kiosk_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        zone VARCHAR(50) NOT NULL,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_zone_key (zone, setting_key)
    )";
    
    $pdo->exec($sql);
    echo "Table 'kiosk_settings' created successfully.<br>";

    // Seed default data
    $defaults = [
        ['zone' => 'tamu', 'key' => 'welcome_text', 'value' => 'Selamat Datang di Ruang Tamu AIS'],
        ['zone' => 'tamu', 'key' => 'running_text', 'value' => 'Silahkan lapor ke resepsionis untuk keperluan tamu.'],
        ['zone' => 'aula', 'key' => 'news_items', 'value' => json_encode([
            ['title' => 'Prestasi Robotik', 'content' => 'Juara 1 Nasional'],
            ['title' => 'Libur Ramadhan', 'content' => 'Dimulai 10 Maret']
        ])],
        ['zone' => 'masjid', 'key' => 'prayer_times', 'value' => json_encode([
            'subuh' => '04:42', 'dzuhur' => '12:05', 'ashar' => '15:15', 'maghrib' => '18:10', 'isya' => '19:25'
        ])],
        ['zone' => 'kantor', 'key' => 'teacher_agenda', 'value' => json_encode([
            ['time' => '08:00', 'activity' => 'Rapat Koordinasi', 'location' => 'R. Meeting'],
            ['time' => '10:00', 'activity' => 'Supervisi Kelas', 'location' => 'Kelas X-1']
        ])]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO kiosk_settings (zone, setting_key, setting_value) VALUES (:zone, :key, :value)");
    
    foreach ($defaults as $data) {
        $stmt->execute([
            ':zone' => $data['zone'],
            ':key' => $data['key'],
            ':value' => $data['value']
        ]);
    }
    echo "Default data seeded.<br>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
