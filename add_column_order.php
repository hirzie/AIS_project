<?php
// add_column_order.php
require_once 'config/database.php';

try {
    echo "Adding column order_index...\n";
    // Pakai try-catch khusus untuk ALTER TABLE agar tidak stop jika kolom sudah ada
    try {
        $pdo->exec("ALTER TABLE acad_class_levels ADD COLUMN order_index INT DEFAULT 0");
        echo "Kolom order_index berhasil ditambahkan.\n";
    } catch (PDOException $e) {
        echo "Kolom order_index mungkin sudah ada (Error: " . $e->getMessage() . ")\n";
    }
    
    // Jalankan ulang seed levels
    $sql = "
    INSERT IGNORE INTO acad_class_levels (unit_id, name, order_index) VALUES
    (2, 'Kelas 1', 1), (2, 'Kelas 2', 2), (2, 'Kelas 3', 3), 
    (2, 'Kelas 4', 4), (2, 'Kelas 5', 5), (2, 'Kelas 6', 6);
    
    INSERT IGNORE INTO acad_class_levels (unit_id, name, order_index) VALUES
    (4, 'Kelas X', 10), (4, 'Kelas XI', 11), (4, 'Kelas XII', 12);
    ";
    $pdo->exec($sql);
    echo "Data Level berhasil di-seed ulang.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
