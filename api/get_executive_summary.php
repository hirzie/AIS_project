<?php
// api/get_executive_summary.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $response = [];

    // 1. STUDENTS COUNT
    // Mengambil data siswa dari tabel acad_student_details
    $stmt = $pdo->query("SELECT COUNT(*) FROM acad_student_details");
    $response['total_students'] = $stmt->fetchColumn();

    // 2. STAFF COUNT
    // Mengambil data pegawai dari hr_employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM hr_employees WHERE status = 'ACTIVE'");
    $response['total_staff'] = $stmt->fetchColumn();


    // 3. ASSETS COUNT (Movable + Fixed + Vehicles)
    $movable = $pdo->query("SELECT COUNT(*) FROM inv_assets_movable WHERE condition_status != 'LOST'")->fetchColumn();
    $fixed = $pdo->query("SELECT COUNT(*) FROM inv_assets_fixed WHERE status = 'ACTIVE'")->fetchColumn();
    $vehicles = $pdo->query("SELECT COUNT(*) FROM inv_vehicles WHERE status = 'ACTIVE'")->fetchColumn();
    $response['total_assets'] = $movable + $fixed + $vehicles;

    // 4. FINANCE (Current Month)
    $startOfMonth = date('Y-m-01');
    $endOfMonth = date('Y-m-t');
    
    // Income
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type = 'INCOME' AND trans_date BETWEEN ? AND ?");
    $stmt->execute([$startOfMonth, $endOfMonth]);
    $response['finance_income'] = $stmt->fetchColumn() ?: 0;

    // Expense
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type = 'EXPENSE' AND trans_date BETWEEN ? AND ?");
    $stmt->execute([$startOfMonth, $endOfMonth]);
    $response['finance_expense'] = $stmt->fetchColumn() ?: 0;

    // 5. FLEET LIST (For the fleet tab)
    $stmt = $pdo->query("SELECT id, name, license_plate, status FROM inv_vehicles ORDER BY name ASC LIMIT 5");
    $response['fleet'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. RECENT APPROVALS (Real Data)
    $stmt = $pdo->query("SELECT * FROM sys_approvals WHERE status = 'PENDING' ORDER BY created_at DESC LIMIT 10");
    $response['approvals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. CHART DATA (Last 6 Months Income/Expense)
    $chartLabels = [];
    $chartIncome = [];
    $chartExpense = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $monthStart = date('Y-m-01', strtotime("-$i months"));
        $monthEnd = date('Y-m-t', strtotime("-$i months"));
        $monthLabel = date('M', strtotime("-$i months"));
        
        $chartLabels[] = $monthLabel;
        
        $stmtInc = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type = 'INCOME' AND trans_date BETWEEN ? AND ?");
        $stmtInc->execute([$monthStart, $monthEnd]);
        $chartIncome[] = $stmtInc->fetchColumn() ?: 0;
        
        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type = 'EXPENSE' AND trans_date BETWEEN ? AND ?");
        $stmtExp->execute([$monthStart, $monthEnd]);
        $chartExpense[] = $stmtExp->fetchColumn() ?: 0;
    }
    
    $response['chart'] = [
        'labels' => $chartLabels,
        'income' => $chartIncome,
        'expense' => $chartExpense
    ];

    echo json_encode(['success' => true, 'data' => $response]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

