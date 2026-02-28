<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID Siswa diperlukan']);
    exit;
}

try {
    // 1. Fetch Core Data
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.name, p.identity_number as nis, p.gender, p.phone, p.email, p.address, p.status,
            c.id as class_id, c.name as class_name
        FROM core_people p
        LEFT JOIN acad_student_classes sc ON p.id = sc.student_id AND sc.status = 'ACTIVE'
        LEFT JOIN acad_classes c ON sc.class_id = c.id
        WHERE p.id = ? AND p.type = 'STUDENT'
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        throw new Exception("Siswa tidak ditemukan");
    }

    // 2. Fetch Details
    $stmt = $pdo->prepare("SELECT * FROM acad_student_details WHERE student_id = ?");
    $stmt->execute([$id]);
    $details = $stmt->fetch() ?: [];

    // 3. Fetch Custom Values
    $stmt = $pdo->prepare("
        SELECT f.field_key, v.field_value 
        FROM core_custom_values v
        JOIN core_custom_fields f ON v.custom_field_id = f.id
        WHERE v.entity_id = ?
    ");
    $stmt->execute([$id]);
    $customValues = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['key' => 'value']

    // Merge data
    $fullData = array_merge($details, $student); // Student (core) overwrites details keys if same (id is same)
    
    // Append custom values with prefix to avoid collision, or separate object
    // Ideally separate object, but for simplicity in flat form we can merge if keys are unique.
    // However, to be safe, let's put them in a 'custom_fields' property AND merge them for direct access if needed
    // But frontend expects flat structure usually. 
    // Let's attach 'custom_values' object.
    $fullData['custom_values'] = $customValues;

    echo json_encode($fullData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
