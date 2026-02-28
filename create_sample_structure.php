<?php
// create_sample_structure.php
require_once 'config/database.php';

// 1. Reset (Optional, but let's be safe and just append if not exists)
// Let's create a clear structure:
// Ketua Yayasan (ID 1)
//  -> Wakil Ketua (New ID) - with Vertical Spacer
//  -> Sekretaris (New ID) - with Horizontal Spacer

// Ensure ID 1 exists
$stmt = $pdo->query("SELECT id FROM hr_positions WHERE id = 1");
if (!$stmt->fetch()) {
    $pdo->query("INSERT INTO hr_positions (id, name, level, sort_order) VALUES (1, 'Ketua Yayasan', 0, 0)");
}

// 2. Create "Wakil Ketua" (Vertical Spacer Example)
$stmt = $pdo->prepare("INSERT INTO hr_positions (name, parent_id, level, sort_order, vertical_spacer) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['Wakil Ketua (Sample Vertical)', 1, 1, 0, 50]); // 50px turun ke bawah
$wakilId = $pdo->lastInsertId();

echo "Created 'Wakil Ketua' (ID: $wakilId) with vertical_spacer=50\n";

// 3. Create "Sekretaris" (Horizontal Spacer Example)
$stmt = $pdo->prepare("INSERT INTO hr_positions (name, parent_id, level, sort_order, horizontal_spacer, vertical_spacer) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['Sekretaris (Sample Horizontal)', 1, 1, 0, 100, 20]); // 100px ke kanan, 20px turun
$sekretarisId = $pdo->lastInsertId();

echo "Created 'Sekretaris' (ID: $sekretarisId) with horizontal_spacer=100\n";

// 4. Create "Bendahara" (Left Horizontal Spacer Example)
$stmt = $pdo->prepare("INSERT INTO hr_positions (name, parent_id, level, sort_order, horizontal_spacer, vertical_spacer) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['Bendahara (Sample Left)', 1, 1, 0, -100, 20]); // -100px ke kiri, 20px turun
$bendaharaId = $pdo->lastInsertId();

echo "Created 'Bendahara' (ID: $bendaharaId) with horizontal_spacer=-100\n";

echo "Done. Refresh chart to see changes.";
?>