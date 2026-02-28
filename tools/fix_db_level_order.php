<?php
// tools/fix_db_level_order.php
// Script untuk memperbaiki kolom 'level_order' yang menyebabkan error 1364
require_once __DIR__ . '/../config/database.php';

echo "<h2>🛠️ PERBAIKAN DATABASE: Level Order</h2>";
echo "<pre>";

try {
    // 1. Ambil daftar kolom tabel acad_class_levels
    $stmt = $pdo->query("DESCRIBE acad_class_levels");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Kolom saat ini: " . implode(", ", $columns) . "\n\n";

    $hasLevelOrder = in_array('level_order', $columns);
    $hasOrderIndex = in_array('order_index', $columns);

    if ($hasLevelOrder && $hasOrderIndex) {
        // KASUS 1: Ada dua-duanya. 'level_order' adalah sisa masa lalu yang bikin error.
        // Solusi: Hapus 'level_order' atau buat dia NULLABLE.
        // Kita pilih Hapus agar bersih.
        echo "⚠️ Ditemukan kolom ganda: 'level_order' dan 'order_index'.\n";
        echo "   Kolom 'level_order' menyebabkan error karena tidak punya default value.\n";
        echo "   -> Menghapus kolom 'level_order'...\n";
        
        $pdo->exec("ALTER TABLE acad_class_levels DROP COLUMN level_order");
        echo "✅ BERHASIL: Kolom 'level_order' dihapus. Masalah teratasi.\n";

    } elseif ($hasLevelOrder && !$hasOrderIndex) {
        // KASUS 2: Hanya ada 'level_order'. Kode PHP minta 'order_index'.
        // Solusi: Rename 'level_order' jadi 'order_index'.
        echo "⚠️ Ditemukan kolom 'level_order', tapi kode membutuhkan 'order_index'.\n";
        echo "   -> Mengubah nama kolom 'level_order' menjadi 'order_index'...\n";
        
        $pdo->exec("ALTER TABLE acad_class_levels CHANGE COLUMN level_order order_index INT DEFAULT 0");
        echo "✅ BERHASIL: Kolom direname menjadi 'order_index'. Masalah teratasi.\n";

    } elseif (!$hasLevelOrder && $hasOrderIndex) {
        // KASUS 3: Sudah benar.
        echo "✅ Struktur database terlihat SUDAH BENAR ('order_index' ada, 'level_order' tidak ada).\n";
        echo "   Jika masih error, coba refresh cache browser Anda.\n";
    } else {
        // KASUS 4: Dua-duanya tidak ada??
        echo "⚠️ Kolom urutan tidak ditemukan sama sekali.\n";
        echo "   -> Membuat kolom 'order_index'...\n";
        $pdo->exec("ALTER TABLE acad_class_levels ADD COLUMN order_index INT DEFAULT 0");
        echo "✅ BERHASIL: Kolom 'order_index' dibuat.\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\nSelesai. Silakan coba simpan data lagi.";
echo "</pre>";
?>