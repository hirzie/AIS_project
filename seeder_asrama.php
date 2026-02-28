<?php
// seeder_asrama.php
require_once 'config/database.php';

try {
    $pdo->beginTransaction();

    // 1. Create Unit "Asrama"
    $stmt = $pdo->prepare("INSERT IGNORE INTO core_units (code, name, receipt_code, type, created_at) VALUES ('ASR', 'Asrama Putra Al-Amanah', 'ASR', 'BOARDING', NOW())");
    $stmt->execute();
    $asramaUnitId = $pdo->lastInsertId();
    
    if (!$asramaUnitId) {
        // If already exists, fetch it
        $stmt = $pdo->query("SELECT id FROM core_units WHERE code = 'ASR'");
        $asramaUnitId = $stmt->fetchColumn();
    }
    echo "Unit Asrama ID: $asramaUnitId\n";

    // 2. Ensure Kepala Sekolah SMP exists (Unit ID 3)
    $stmt = $pdo->prepare("SELECT id FROM hr_positions WHERE unit_id = 3 AND name LIKE '%Kepala Sekolah%' LIMIT 1");
    $stmt->execute();
    $kepsekSmpId = $stmt->fetchColumn();

    if (!$kepsekSmpId) {
        $stmt = $pdo->prepare("INSERT INTO hr_positions (name, unit_id, level, parent_id) VALUES ('Kepala Sekolah SMP', 3, 1, NULL)");
        $stmt->execute();
        $kepsekSmpId = $pdo->lastInsertId();
        echo "Created Kepala Sekolah SMP ID: $kepsekSmpId\n";
    } else {
        echo "Found Kepala Sekolah SMP ID: $kepsekSmpId\n";
    }

    // 3. Create "Kepala Asrama" (Unit Asrama, Parent Kepsek SMP)
    // This demonstrates Sub-Unit structure: Position belongs to Asrama, but reports to SMP
    $stmt = $pdo->prepare("INSERT IGNORE INTO hr_positions (name, unit_id, level, parent_id) VALUES ('Kepala Asrama', ?, 2, ?)");
    $stmt->execute([$asramaUnitId, $kepsekSmpId]);
    $kepalaAsramaId = $pdo->lastInsertId();
    
    if (!$kepalaAsramaId) {
         $stmt = $pdo->prepare("SELECT id FROM hr_positions WHERE name = 'Kepala Asrama' AND unit_id = ?");
         $stmt->execute([$asramaUnitId]);
         $kepalaAsramaId = $stmt->fetchColumn();
    }
    echo "Kepala Asrama Position ID: $kepalaAsramaId\n";

    // 4. Assign Employee "Ade Koko Kurtubi" (ID 43)
    // First clear any existing position for him
    $employeeId = 43;
    $stmt = $pdo->prepare("UPDATE hr_employees SET position_id = NULL WHERE id = ?");
    $stmt->execute([$employeeId]);

    // Assign new position
    $stmt = $pdo->prepare("UPDATE hr_employees SET position_id = ?, sk_number = 'SK/ASR/2026/001' WHERE id = ?");
    $stmt->execute([$kepalaAsramaId, $employeeId]);
    
    echo "Assigned Ade Koko Kurtubi to Kepala Asrama.\n";

    $pdo->commit();
    echo "Seeding Success!";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>