<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    
    // 1. GET TRANSACTIONS (Daily)
    if ($action == 'get_transactions' && $method == 'GET') {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT * FROM pos_transactions WHERE DATE(trans_date) = ? ORDER BY trans_date DESC, id DESC");
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, 'Data fetched', $rows);
    }

    // 2. SAVE TRANSACTION
    if ($action == 'save_transaction' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'] ?? null;
        $type = $data['type'];
        $amount = $data['amount'];
        $description = $data['description'];
        $date = $data['date'] ?? date('Y-m-d H:i:s');
        $category = $data['category'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE pos_transactions SET type=?, amount=?, description=?, trans_date=?, category=? WHERE id=?");
            $stmt->execute([$type, $amount, $description, $date, $category, $id]);
            jsonResponse(true, 'Transaksi POS diperbarui');
        } else {
            $transNo = 'POS/' . date('Ymd') . '/' . rand(1000, 9999);
            $stmt = $pdo->prepare("INSERT INTO pos_transactions (trans_number, type, amount, description, trans_date, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$transNo, $type, $amount, $description, $date, $category]);
            jsonResponse(true, 'Transaksi POS ditambahkan');
        }
    }

    // 3. DELETE TRANSACTION
    if ($action == 'delete_transaction' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        $stmt = $pdo->prepare("DELETE FROM pos_transactions WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'Transaksi POS dihapus');
    }

    // 4. GET DAILY SUMMARY
    if ($action == 'get_daily_summary' && $method == 'GET') {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $stmtInc = $pdo->prepare("SELECT SUM(amount) FROM pos_transactions WHERE type='INCOME' AND DATE(trans_date) = ?");
        $stmtInc->execute([$date]);
        $income = $stmtInc->fetchColumn() ?: 0;

        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM pos_transactions WHERE type='EXPENSE' AND DATE(trans_date) = ?");
        $stmtExp->execute([$date]);
        $expense = $stmtExp->fetchColumn() ?: 0;

        jsonResponse(true, 'Data fetched', [
            'date' => $date,
            'income' => (float)$income,
            'expense' => (float)$expense,
            'balance' => (float)($income - $expense)
        ]);
    }

    // 5. CAPTURE DAILY REPORT TO FOUNDATION
    if ($action == 'capture_daily_report' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $report_date = $data['report_date'] ?? date('Y-m-d');
        $notes = $data['notes'] ?? 'Laporan Harian POS';

        // Calculate DAILY Flow (for Surplus/Deficit)
        $stmtInc = $pdo->prepare("SELECT SUM(amount) FROM pos_transactions WHERE type='INCOME' AND DATE(trans_date) = ?");
        $stmtInc->execute([$report_date]);
        $income = $stmtInc->fetchColumn() ?: 0;

        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM pos_transactions WHERE type='EXPENSE' AND DATE(trans_date) = ?");
        $stmtExp->execute([$report_date]);
        $expense = $stmtExp->fetchColumn() ?: 0;

        $dailyBalance = $income - $expense;

        // Calculate CUMULATIVE Balance (for Total Assets / Cash on Hand)
        $stmtCumInc = $pdo->prepare("SELECT SUM(amount) FROM pos_transactions WHERE type='INCOME' AND DATE(trans_date) <= ?");
        $stmtCumInc->execute([$report_date]);
        $cumIncome = $stmtCumInc->fetchColumn() ?: 0;

        $stmtCumExp = $pdo->prepare("SELECT SUM(amount) FROM pos_transactions WHERE type='EXPENSE' AND DATE(trans_date) <= ?");
        $stmtCumExp->execute([$report_date]);
        $cumExpense = $stmtCumExp->fetchColumn() ?: 0;

        $accumulatedAssets = $cumIncome - $cumExpense;

        // Details
        $details = [
            'source' => 'POS Independent DB',
            'income' => $income,
            'expense' => $expense,
            'daily_net' => $dailyBalance,
            'accumulated_cash' => $accumulatedAssets
        ];

        // Insert into MAIN FINANCE Checkpoints (Shared Table)
        // Store Accumulated Assets in total_assets
        // Store Daily Net in surplus_deficit
        $stmtIns = $pdo->prepare("INSERT INTO fin_report_checkpoints (report_date, total_income, total_expense, surplus_deficit, total_assets, total_liabilities, total_equity, notes, unit_prefix, details) VALUES (?, ?, ?, ?, ?, 0, 0, ?, 'POS', ?)");
        $stmtIns->execute([
            $report_date, 
            $income,
            $expense,
            $dailyBalance,
            $accumulatedAssets,
            $notes,
            json_encode($details)
        ]);

        jsonResponse(true, 'Laporan POS (DB Terpisah) berhasil dikirim ke Yayasan.');
    }

} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}

