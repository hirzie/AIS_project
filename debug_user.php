<?php
require_once 'config/database.php';

$username = 'wali1';
$stmt = $pdo->prepare("SELECT id, username, role, access_modules, people_id FROM core_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.\n";
    exit;
}

print_r($user);

if ($user['people_id']) {
    $stmt2 = $pdo->prepare("SELECT id, custom_attributes FROM core_people WHERE id = ?");
    $stmt2->execute([$user['people_id']]);
    $person = $stmt2->fetch(PDO::FETCH_ASSOC);
    print_r($person);
}

// Check allowed_modules logic simulation
$role = strtoupper($user['role']);
echo "Role: $role\n";

$allowed = ['core' => true];
if ($role === 'TEACHER' || $role === 'GURU') {
    $allowed['workspace'] = true;
}
// ... (rest of logic)

// Check overrides
if ($user['access_modules']) {
    echo "Access Modules JSON found: " . $user['access_modules'] . "\n";
}

?>