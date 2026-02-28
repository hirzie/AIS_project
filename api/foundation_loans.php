<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // 1. GET EMPLOYEES LIST (For Dropdown)
    if ($action == 'get_employees' && $method == 'GET') {
        // Fetch people who are likely employees (e.g., have user account or specific role)
        // Adjust query based on actual schema. Assuming core_people exists.
        $stmt = $pdo->query("SELECT id, name, type FROM core_people WHERE type IN ('TEACHER', 'STAFF', 'EMPLOYEE') ORDER BY name ASC");
        $employees = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $employees]);
    }

    // 2. GET LOANS LIST
    elseif ($action == 'get_loans' && $method == 'GET') {
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        
        $sql = "SELECT l.*, p.name as employee_name 
                FROM fnd_loans l 
                JOIN core_people p ON l.employee_id = p.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND l.status = ?";
            $params[] = $status;
        }
        if ($type) {
            $sql .= " AND l.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY l.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $loans = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $loans]);
    }

    // 3. CREATE NEW LOAN REQUEST
    elseif ($action == 'create_loan' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $employee_id = $data['employee_id'];
        $type = $data['type']; // MONEY or ITEM
        $amount = $data['amount']; // Principal amount
        $tenor_months = $data['tenor_months'];
        $description = $data['description'] ?? '';
        $item_name = $data['item_name'] ?? null;
        
        // Calculate Repayment
        // Simple logic: If ITEM, amount includes markup? Or separate?
        // Let's assume input 'amount' is the Total Loan Amount requested.
        // Interest: Flat rate? Or input manually?
        // Let's assume 0% interest for now unless specified.
        $interest_rate = $data['interest_rate'] ?? 0;
        
        $total_repayment = $amount + ($amount * ($interest_rate / 100));
        $monthly_installment = $total_repayment / $tenor_months;
        
        // Generate Loan Number: LN/YYYYMM/RAND
        $loan_number = 'LN/' . date('Ym') . '/' . rand(1000, 9999);
        
        $stmt = $pdo->prepare("INSERT INTO fnd_loans (
            employee_id, loan_number, type, amount, item_name, 
            interest_rate, total_repayment, tenor_months, monthly_installment, 
            request_date, status, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'PENDING', ?)");
        
        $stmt->execute([
            $employee_id, $loan_number, $type, $amount, $item_name,
            $interest_rate, $total_repayment, $tenor_months, $monthly_installment,
            $description
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Pengajuan pinjaman berhasil dibuat']);
    }

    // 4. APPROVE LOAN
    elseif ($action == 'approve_loan' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        $start_date = $data['start_date'] ?? date('Y-m-d');
        
        $pdo->beginTransaction();
        
        // Update Status
        $stmt = $pdo->prepare("UPDATE fnd_loans SET status = 'ACTIVE', approved_date = CURDATE(), start_date = ? WHERE id = ?");
        $stmt->execute([$start_date, $id]);
        
        // Get Loan Details
        $stmt = $pdo->prepare("SELECT * FROM fnd_loans WHERE id = ?");
        $stmt->execute([$id]);
        $loan = $stmt->fetch();
        
        // Generate Installments
        $installment_amt = $loan['monthly_installment'];
        $due_date = new DateTime($start_date);
        
        $ins = $pdo->prepare("INSERT INTO fnd_loan_installments (loan_id, installment_number, due_date, amount, status) VALUES (?, ?, ?, ?, 'UNPAID')");
        
        for ($i = 1; $i <= $loan['tenor_months']; $i++) {
            // Add 1 month for next due date
            $due_date->modify('+1 month');
            $ins->execute([$id, $i, $due_date->format('Y-m-d'), $installment_amt]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pinjaman disetujui dan jadwal cicilan dibuat']);
    }

    // 5. REJECT LOAN
    elseif ($action == 'reject_loan' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        $reason = $data['reason'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE fnd_loans SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Pinjaman ditolak']);
    }

    // 6. GET INSTALLMENTS (For specific loan OR for monthly view)
    elseif ($action == 'get_installments' && $method == 'GET') {
        if (isset($_GET['loan_id'])) {
            // Get detail for specific loan
            $loan_id = $_GET['loan_id'];
            $stmt = $pdo->prepare("SELECT * FROM fnd_loan_installments WHERE loan_id = ? ORDER BY installment_number ASC");
            $stmt->execute([$loan_id]);
            $installments = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $installments]);
        } 
        else {
            // Get list of installments due (for Payment Menu)
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            $status = $_GET['status'] ?? 'UNPAID'; // Default unpaid
            
            $sql = "SELECT i.*, l.loan_number, l.type, p.name as employee_name,
                    (SELECT COUNT(*) FROM fnd_loan_installments WHERE loan_id = l.id AND status = 'UNPAID') as remaining_installments
                    FROM fnd_loan_installments i
                    JOIN fnd_loans l ON i.loan_id = l.id
                    JOIN core_people p ON l.employee_id = p.id
                    WHERE MONTH(i.due_date) = ? AND YEAR(i.due_date) = ?";
            
            if ($status !== 'ALL') {
                $sql .= " AND i.status = '$status'";
            }
            
            $sql .= " ORDER BY i.due_date ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$month, $year]);
            $installments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $installments]);
        }
    }
    
    // 8. GET LOAN STATS
    elseif ($action == 'get_stats' && $method == 'GET') {
        // Total Active Loans Amount (Outstanding Principal + Interest)
        $stmtOut = $pdo->query("SELECT SUM(amount) FROM fnd_loan_installments WHERE status != 'PAID'");
        $outstanding = $stmtOut->fetchColumn() ?: 0;
        
        // Total Paid (Repayment Received)
        $stmtPaid = $pdo->query("SELECT SUM(paid_amount) FROM fnd_loan_installments WHERE status = 'PAID'");
        $totalPaid = $stmtPaid->fetchColumn() ?: 0;
        
        // Total Active Borrowers
        $stmtBorrowers = $pdo->query("SELECT COUNT(DISTINCT employee_id) FROM fnd_loans WHERE status = 'ACTIVE'");
        $activeBorrowers = $stmtBorrowers->fetchColumn() ?: 0;
        
        // Count Active Loans
        $stmtActiveCount = $pdo->query("SELECT COUNT(*) FROM fnd_loans WHERE status = 'ACTIVE'");
        $activeLoansCount = $stmtActiveCount->fetchColumn() ?: 0;
        
        echo json_encode(['success' => true, 'data' => [
            'outstanding' => $outstanding,
            'total_paid' => $totalPaid,
            'active_borrowers' => $activeBorrowers,
            'active_loans_count' => $activeLoansCount
        ]]);
    }

    // 9. GET FINANCE REPORT (Coop Capital & Cashflow)
    elseif ($action == 'get_finance_report' && $method == 'GET') {
        // 1. Capital (Modal)
        $stmtCap = $pdo->query("SELECT 
            COALESCE(SUM(CASE WHEN type = 'INJECTION' THEN amount ELSE 0 END), 0) as total_injection,
            COALESCE(SUM(CASE WHEN type = 'WITHDRAWAL' THEN amount ELSE 0 END), 0) as total_withdrawal
            FROM fnd_coop_capital");
        $cap = $stmtCap->fetch();
        $netCapital = $cap['total_injection'] - $cap['total_withdrawal'];
        
        // 2. Loans Disbursed (Principal Out)
        $stmtDisbursed = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fnd_loans WHERE status IN ('ACTIVE', 'PAID')");
        $totalDisbursed = $stmtDisbursed->fetchColumn();
        
        // 3. Repayments Received (Principal + Interest In)
        $stmtRepaid = $pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM fnd_loan_installments WHERE status = 'PAID'");
        $totalRepaid = $stmtRepaid->fetchColumn();
        
        // 4. Current Outstanding (Principal + Interest pending)
        $stmtOut = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fnd_loan_installments WHERE status != 'PAID'");
        $outstanding = $stmtOut->fetchColumn();
        
        // 5. Get Expenses
        $stmtExp = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fnd_coop_expenses");
        $totalExpenses = $stmtExp->fetchColumn();
        
        // Saldo Calculation
        $cashOnHand = ($netCapital + $totalRepaid) - $totalDisbursed - $totalExpenses;
        
        // 6. CONSOLIDATED HISTORY (Capital, Expenses, Loans Out, Repayments In)
        $sqlHistory = "
            (SELECT trans_date, type, amount, description, 'CAPITAL' as category 
             FROM fnd_coop_capital)
            UNION ALL
            (SELECT trans_date, 'EXPENSE' as type, amount, CONCAT('Beli: ', item_name, ' - ', IFNULL(description,'')) as description, 'EXPENSE' as category 
             FROM fnd_coop_expenses)
            UNION ALL
            (SELECT start_date as trans_date, 'DISBURSEMENT' as type, amount, CONCAT('Pinjaman Keluar: ', loan_number) as description, 'LOAN_OUT' as category
             FROM fnd_loans 
             WHERE status IN ('ACTIVE', 'PAID') AND start_date IS NOT NULL)
            UNION ALL
            (SELECT DATE(paid_date) as trans_date, 'REPAYMENT' as type, paid_amount as amount, CONCAT('Cicilan Masuk: #', installment_number) as description, 'LOAN_IN' as category
             FROM fnd_loan_installments
             WHERE status = 'PAID' AND paid_date IS NOT NULL)
            ORDER BY trans_date DESC LIMIT 100
        ";
        
        $stmtHist = $pdo->query($sqlHistory);
        $history = $stmtHist->fetchAll();
        
        echo json_encode(['success' => true, 'data' => [
            'capital' => $netCapital,
            'disbursed' => $totalDisbursed,
            'repaid' => $totalRepaid,
            'outstanding' => $outstanding,
            'expenses' => $totalExpenses,
            'saldo' => $cashOnHand,
            'history' => $history
        ]]);
    }

    // 10. ADD CAPITAL (Modal)
    elseif ($action == 'add_capital' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $date = $data['date'] ?? date('Y-m-d');
        $type = $data['type']; // INJECTION / WITHDRAWAL
        $amount = $data['amount'];
        $desc = $data['description'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO fnd_coop_capital (trans_date, type, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$date, $type, $amount, $desc]);
        
        echo json_encode(['success' => true, 'message' => 'Data modal berhasil disimpan']);
    }
    
    // 11. ADD EXPENSE (Pembelian Barang)
    elseif ($action == 'add_expense' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $date = $data['date'] ?? date('Y-m-d');
        $item_name = $data['item_name'];
        $amount = $data['amount'];
        $desc = $data['description'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO fnd_coop_expenses (trans_date, item_name, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$date, $item_name, $amount, $desc]);
        
        echo json_encode(['success' => true, 'message' => 'Pembelian barang berhasil dicatat']);
    }

    // 7. PAY INSTALLMENT
    elseif ($action == 'pay_installment' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id']; // Installment ID
        
        $stmt = $pdo->prepare("UPDATE fnd_loan_installments SET status = 'PAID', paid_amount = amount, paid_date = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        // Check if all paid
        // Get loan_id first
        $stmtLoan = $pdo->prepare("SELECT loan_id FROM fnd_loan_installments WHERE id = ?");
        $stmtLoan->execute([$id]);
        $loanId = $stmtLoan->fetchColumn();
        
        // Check remaining unpaid
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM fnd_loan_installments WHERE loan_id = ? AND status != 'PAID'");
        $stmtCheck->execute([$loanId]);
        $unpaidCount = $stmtCheck->fetchColumn();
        
        if ($unpaidCount == 0) {
            $pdo->prepare("UPDATE fnd_loans SET status = 'PAID', end_date = CURDATE() WHERE id = ?")->execute([$loanId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cicilan berhasil dibayar']);
    }

    // 12. SUBMIT REPORT TO FOUNDATION (CONSOLIDATION)
    elseif ($action == 'submit_report_to_foundation' && $method == 'POST') {
        // Calculate Current Position
        // Capital
        $stmtCap = $pdo->query("SELECT COALESCE(SUM(CASE WHEN type = 'INJECTION' THEN amount ELSE -amount END), 0) FROM fnd_coop_capital");
        $netCapital = $stmtCap->fetchColumn();
        
        // Loans Disbursed
        $stmtDisbursed = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fnd_loans WHERE status IN ('ACTIVE', 'PAID')");
        $totalDisbursed = $stmtDisbursed->fetchColumn();
        
        // Repayments
        $stmtRepaid = $pdo->query("SELECT COALESCE(SUM(paid_amount), 0) FROM fnd_loan_installments WHERE status = 'PAID'");
        $totalRepaid = $stmtRepaid->fetchColumn();
        
        // Expenses
        $stmtExp = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fnd_coop_expenses");
        $totalExpenses = $stmtExp->fetchColumn();
        
        // Saldo (Cash Asset)
        $cashOnHand = ($netCapital + $totalRepaid) - $totalDisbursed - $totalExpenses;
        
        // Outstanding (Receivable Asset)
        $stmtOut = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fnd_loan_installments WHERE status != 'PAID'");
        $outstanding = $stmtOut->fetchColumn();
        
        // Inventory Asset (Expenses considered as Asset for now, or just Expense?)
        // Let's treat Expenses as "Inventory/Aset Barang" for consolidation purpose
        $inventory = $totalExpenses; 
        
        // TOTAL ASSETS = Cash + Receivables + Inventory
        $totalAssets = $cashOnHand + $outstanding + $inventory;
        
        // Prepare JSON Details
        $details = json_encode([
            ['code' => 'CASH', 'name' => 'Kas Koperasi', 'balance' => $cashOnHand],
            ['code' => 'AR', 'name' => 'Piutang Anggota', 'balance' => $outstanding],
            ['code' => 'INV', 'name' => 'Aset Barang/Stok', 'balance' => $inventory]
        ]);
        
        // Insert into fnd_reports (Using unit_prefix 'COOP')
        // Ensure 'COOP' is handled in foundation_finance_reports.php display
        $stmt = $pdo->prepare("INSERT INTO fnd_reports (report_date, unit_prefix, total_assets, details, notes, created_at) VALUES (CURDATE(), 'COOP', ?, ?, 'Laporan Konsolidasi Koperasi', NOW())");
        $stmt->execute([$totalAssets, $details]);
        
        echo json_encode(['success' => true, 'message' => 'Laporan berhasil dikirim']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
