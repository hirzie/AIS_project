<?php
// tools/add_column_order.php
require_once __DIR__ . '/../config/database.php';

echo "MEMPERBAIKI DATABASE (Field Missing)...\n";

try {
    // 1. Cek apakah kolom order_index ada di acad_class_levels
    $stmt = $pdo->query("SHOW COLUMNS FROM acad_class_levels LIKE 'order_index'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Kolom 'order_index' TIDAK DITEMUKAN. Menambahkan...\n";
        $pdo->exec("ALTER TABLE acad_class_levels ADD COLUMN order_index INT DEFAULT 0");
        echo "BERHASIL: Kolom order_index ditambahkan.\n";
    } else {
        echo "Kolom 'order_index' SUDAH ADA. Aman.\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>