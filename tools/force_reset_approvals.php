<?php
// tools/force_reset_approvals.php
// Script to manually reset approval payout status
// Hardcoded DB config for CLI usage
$host = 'localhost';
$db   = 'aiscore';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Resetting Approval Payout Status...\n";
    
    // Check before
    $stmt = $pdo->query("SELECT COUNT(*) FROM sys_approvals WHERE payout_trans_number IS NOT NULL");
    $count = $stmt->fetchColumn();
    echo "Found $count approvals with Payout Status.\n";
    
    if ($count > 0) {
        // Reset
        $sql = "UPDATE sys_approvals SET payout_trans_number = NULL, payout_date = NULL, payout_pic = NULL";
        $pdo->exec($sql);
        echo "Executed UPDATE query.\n";
    }
    
    // Check after
    $stmt = $pdo->query("SELECT COUNT(*) FROM sys_approvals WHERE payout_trans_number IS NOT NULL");
    $countAfter = $stmt->fetchColumn();
    
    if ($countAfter == 0) {
        echo "SUCCESS: All approval payout statuses have been cleared.\n";
    } else {
        echo "WARNING: $countAfter approvals still have payout status.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>