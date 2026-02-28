<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "--- Checking Relations ---\n";
    $tables = ['students', 'classes', 'personnel_employees'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$t'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$t' exists.\n";
            $stmt = $pdo->query("DESCRIBE $t");
            // print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
            // Just check if there is a 'unit_id' or similar
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('unit_id', $cols)) echo " - Has unit_id\n";
            elseif (in_array('unit', $cols)) echo " - Has unit\n";
            else echo " - No obvious unit column\n";
        } else {
            echo "Table '$t' does NOT exist.\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

