<?php
// reset_org_data.php
require_once 'config/database.php';

try {
    // 1. Disable Foreign Key Checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 2. Truncate Tables
    $pdo->exec("TRUNCATE TABLE hr_positions");
    
    // 3. Reset employees' position_id to NULL (don't delete employees, just unassign them)
    $pdo->exec("UPDATE hr_employees SET position_id = NULL");
    
    // 4. Re-enable Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 5. Insert Clean Dummy Data
    
    // ROOT: Ketua Yayasan (ID 1)
    $stmt = $pdo->prepare("INSERT INTO hr_positions (id, name, parent_id, level, sort_order) VALUES (1, 'Ketua Yayasan', NULL, 0, 0)");
    $stmt->execute();
    
    // CHILD 1: Wakil Ketua (ID 2) -> Parent 1
    // Vertical Spacer 50px
    $stmt = $pdo->prepare("INSERT INTO hr_positions (id, name, parent_id, level, sort_order, vertical_spacer) VALUES (2, 'Wakil Ketua', 1, 1, 0, 50)");
    $stmt->execute();
    
    // CHILD 2: Sekretaris (ID 3) -> Parent 1
    // Horizontal Spacer 120px, Vertical 20px
    $stmt = $pdo->prepare("INSERT INTO hr_positions (id, name, parent_id, level, sort_order, horizontal_spacer, vertical_spacer) VALUES (3, 'Sekretaris', 1, 1, 0, 120, 20)");
    $stmt->execute();
    
    // CHILD 3: Bendahara (ID 4) -> Parent 1
    // Horizontal Spacer -120px (Left), Vertical 20px
    $stmt = $pdo->prepare("INSERT INTO hr_positions (id, name, parent_id, level, sort_order, horizontal_spacer, vertical_spacer) VALUES (4, 'Bendahara', 1, 1, 0, -120, 20)");
    $stmt->execute();

    echo "Database reset successfully. Clean structure created.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>