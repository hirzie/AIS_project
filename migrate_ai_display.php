<?php
// migrate_ai_display.php
// Script migrasi khusus untuk fitur AI Assistant & Display Override
// Menjalankan perintah SQL dari migration_ai_display.sql

header('Content-Type: text/plain');
require_once 'config/database.php';

echo "=== MIGRATION TOOL: AI & DISPLAY FEATURES ===\n";
echo "Database: $dbname\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$sqlFile = 'migration_ai_display.sql';

if (!file_exists($sqlFile)) {
    die("Error: File $sqlFile tidak ditemukan.\n");
}

$sqlContent = file_get_contents($sqlFile);

// Split SQL by semicolon, but be careful with triggers/procedures if any (none in this simple migration)
// A simple split by semicolon is sufficient for this specific file.
$queries = explode(';', $sqlContent);

$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;

    echo "Exec: " . substr(str_replace("\n", " ", $query), 0, 60) . "...\n";

    try {
        $pdo->exec($query);
        echo "  -> OK\n";
        $successCount++;
    } catch (PDOException $e) {
        // Ignore specific harmless errors
        if (
            strpos($e->getMessage(), "Duplicate column name") !== false || 
            strpos($e->getMessage(), "Column already exists") !== false
        ) {
            echo "  -> SKIPPED (Column already exists)\n";
            $successCount++; // Count as success because end state is correct
        } elseif (strpos($e->getMessage(), "Duplicate entry") !== false) {
            echo "  -> SKIPPED (Entry already exists)\n";
            $successCount++;
        } else {
            echo "  -> ERROR: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
}

echo "\nResult: $successCount commands executed successfully.\n";
if ($errorCount > 0) {
    echo "Warning: $errorCount commands failed (check output above).\n";
} else {
    echo "Status: MIGRATION SUCCESSFUL.\n";
}
?>
