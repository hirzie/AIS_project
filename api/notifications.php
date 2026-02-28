<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/guard.php';
ais_init_session();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$action = $_GET['action'] ?? '';
$role = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'];
$allowedModules = $_SESSION['allowed_modules'] ?? [];

// Helper to check if user has access to a module
function hasModuleAccess($module) {
    global $role, $allowedModules;
    if (in_array($role, ['SUPERADMIN', 'ADMIN'])) return true;
    return isset($allowedModules[strtolower($module)]);
}

if ($action == 'get_counts') {
    $counts = [
        'total' => 0,
        'student_incidents' => 0,
        'counseling_tickets' => 0,
        'facility_tickets' => 0,
        'vehicle_lending' => 0,
        'resource_lending' => 0
    ];

    // 1. Student Incidents (Pending)
    // Access: Admin, Counseling, Teacher, Principal
    if (hasModuleAccess('counseling') || in_array($role, ['TEACHER', 'PRINCIPAL', 'SUPERADMIN', 'ADMIN'])) {
        try {
            // Check if table exists first
            $stmt = $pdo->query("SHOW TABLES LIKE 'student_incidents'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM student_incidents WHERE status = 'PENDING'");
                $counts['student_incidents'] = (int)$stmt->fetchColumn();
            }
        } catch (Throwable $e) {}
    }

    // 2. Counseling Tickets (Open)
    // Access: Admin, Counseling
    if (hasModuleAccess('counseling') || in_array($role, ['SUPERADMIN', 'ADMIN'])) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'counseling_tickets'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM counseling_tickets WHERE status = 'OPEN'");
                $counts['counseling_tickets'] = (int)$stmt->fetchColumn();
            }
        } catch (Throwable $e) {}
    }

    // 3. Facility Tickets (Open)
    // Access: Admin, Inventory, Foundation
    if (hasModuleAccess('inventory') || in_array($role, ['SUPERADMIN', 'ADMIN', 'FOUNDATION'])) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'inv_facility_tickets'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM inv_facility_tickets WHERE status = 'OPEN'");
                $counts['facility_tickets'] = (int)$stmt->fetchColumn();
            }
        } catch (Throwable $e) {}
    }

    // 4. Vehicle Lending (Pending Approval)
    // Access: Admin, Inventory, Foundation
    if (hasModuleAccess('inventory') || in_array($role, ['SUPERADMIN', 'ADMIN', 'FOUNDATION'])) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'inv_vehicle_lending'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM inv_vehicle_lending WHERE status = 'PENDING'");
                $counts['vehicle_lending'] = (int)$stmt->fetchColumn();
            }
        } catch (Throwable $e) {}
    }

    // 5. Resource Lending (Pending Approval)
    // Access: Admin, Inventory, Foundation
    if (hasModuleAccess('inventory') || in_array($role, ['SUPERADMIN', 'ADMIN', 'FOUNDATION'])) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'inv_resource_lending'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM inv_resource_lending WHERE status = 'PENDING'");
                $counts['resource_lending'] = (int)$stmt->fetchColumn();
            }
        } catch (Throwable $e) {}
    }

    $counts['total'] = array_sum($counts);
    echo json_encode(['success' => true, 'data' => $counts]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
