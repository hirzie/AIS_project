<?php
// api/ensure_active_year.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $today = new DateTime();
    $currentYear = (int)$today->format('Y');
    $currentMonth = (int)$today->format('n');

    // Calculate Academic Year
    if ($currentMonth >= 7) {
        // July - December: Start of Academic Year
        $startYear = $currentYear;
        $endYear = $currentYear + 1;
        $semester = 'GANJIL';
    } else {
        // January - June: End of Academic Year
        $startYear = $currentYear - 1;
        $endYear = $currentYear;
        $semester = 'GENAP';
    }

    $name = "$startYear/$endYear";
    $startDate = "$startYear-07-01";
    $endDate = "$endYear-06-30";

    // Check if this year exists
    $stmt = $pdo->prepare("SELECT * FROM acad_years WHERE name = ?");
    $stmt->execute([$name]);
    $existingYear = $stmt->fetch(PDO::FETCH_ASSOC);

    $activeYearId = null;

    if ($existingYear) {
        // Year exists
        $activeYearId = $existingYear['id'];
        
        // Check if needs update (Status or Semester)
        // We force it to be ACTIVE and Correct Semester if it matches current date logic
        $needsUpdate = false;
        
        if ($existingYear['status'] !== 'ACTIVE') {
            $needsUpdate = true;
        }
        if ($existingYear['semester_active'] !== $semester) {
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $stmt = $pdo->prepare("UPDATE acad_years SET status = 'ACTIVE', semester_active = ? WHERE id = ?");
            $stmt->execute([$semester, $activeYearId]);
        }

    } else {
        // Create new Year
        $stmt = $pdo->prepare("INSERT INTO acad_years (name, start_date, end_date, status, semester_active) VALUES (?, ?, ?, 'ACTIVE', ?)");
        $stmt->execute([$name, $startDate, $endDate, $semester]);
        $activeYearId = $pdo->lastInsertId();
    }

    // Archive all OTHER years
    if ($activeYearId) {
        $stmt = $pdo->prepare("UPDATE acad_years SET status = 'ARCHIVED' WHERE id != ? AND status = 'ACTIVE'");
        $stmt->execute([$activeYearId]);
    }

    // Return list of all years
    $stmt = $pdo->query("SELECT * FROM acad_years ORDER BY start_date DESC");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Tahun ajaran berhasil disinkronisasi otomatis.',
        'active_year_name' => $name,
        'active_semester' => $semester,
        'data' => $years
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
