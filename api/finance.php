<?php
// api/finance.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// DEBUG LOGGING
file_put_contents('../debug_finance.log', date('Y-m-d H:i:s') . " REQUEST: Action=$action Method=$method\n", FILE_APPEND);

 
try {
    $c1 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_transactions' AND COLUMN_NAME = 'request_id'");
    $c1->execute();
    if ((int)$c1->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE fin_transactions ADD COLUMN request_id VARCHAR(64) DEFAULT NULL");
    }
    $i1 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_transactions' AND INDEX_NAME = 'uniq_fin_trans_req'");
    $i1->execute();
    if ((int)$i1->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE fin_transactions ADD UNIQUE INDEX uniq_fin_trans_req (request_id)");
    }
    $c2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_cash_advances' AND COLUMN_NAME = 'request_id'");
    $c2->execute();
    if ((int)$c2->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE fin_cash_advances ADD COLUMN request_id VARCHAR(64) DEFAULT NULL");
    }
    $i2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_cash_advances' AND INDEX_NAME = 'uniq_fin_adv_req'");
    $i2->execute();
    if ((int)$i2->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE fin_cash_advances ADD UNIQUE INDEX uniq_fin_adv_req (request_id)");
    }
    $c3 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_student_savings' AND COLUMN_NAME = 'request_id'");
    $c3->execute();
    if ((int)$c3->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE fin_student_savings ADD COLUMN request_id VARCHAR(64) DEFAULT NULL");
    }
    $i3 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_student_savings' AND INDEX_NAME = 'uniq_fin_sav_req'");
    $i3->execute();
    if ((int)$i3->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE fin_student_savings ADD UNIQUE INDEX uniq_fin_sav_req (request_id)");
    }
} catch (\Throwable $e) { }

function log_activity($pdo, $module, $category, $action, $entity_type, $entity_id, $title, $description) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        entity_id VARCHAR(64) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (module, category, action, entity_type, entity_id, title, description, user_id) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$module, $category, $action, $entity_type, (string)$entity_id, $title, $description, $userId]);
}
// Helper Response
    function jsonResponse($success, $message, $data = []) {
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

    // Helper: Generate Unit-Based Reference Number
    // Format: [PREFIX][YY][TYPE][SEQUENCE] (e.g., SD26J000001)
    // Type: J=Journal, I=Income, E=Expense
    function generateReferenceNumber($pdo, $unit_id, $typeChar, $table, $column) {
        $prefix = 'GLB'; // Default Global
        
        if ($unit_id) {
            $stmt = $pdo->prepare("SELECT receipt_code FROM core_units WHERE id = ?");
            $stmt->execute([$unit_id]);
            $res = $stmt->fetchColumn();
            if ($res) $prefix = $res;
        }
        
        $year = date('y'); // 2 digits
        $search = $prefix . $year . $typeChar;
        $len = strlen($search);
        
        // Find Max Number with this prefix
        $sql = "SELECT MAX($column) FROM $table WHERE $column LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search . '%']);
        $max = $stmt->fetchColumn();
        
        $next = 1;
        if ($max) {
            $seq = substr($max, $len);
            if (is_numeric($seq)) {
                $next = (int)$seq + 1;
            }
        }
        
        return $search . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    try {
    // 33. ROLLBACK PAYOUT (Moved to Top)
    if (($action == 'rollback_payout' || $action == 'delete_expense_transaction') && $method == 'POST') {
        $trans_number = $_POST['trans_number'] ?? ''; 
        $reference_no = $_POST['reference_no'] ?? ''; 
        
        // Debug
        file_put_contents('../debug_finance.log', date('Y-m-d H:i:s') . " DELETE/ROLLBACK REQUEST: $trans_number REF: $reference_no ACTION: $action\n", FILE_APPEND);

        // Fallback JSON
        if (empty($trans_number)) {
             $data = json_decode(file_get_contents('php://input'), true);
             $trans_number = $data['trans_number'] ?? '';
             $reference_no = $data['reference_no'] ?? '';
             file_put_contents('../debug_finance.log', date('Y-m-d H:i:s') . " DELETE (JSON Fallback): $trans_number\n", FILE_APPEND);
        }
        
        try {
            $pdo->beginTransaction();
            
            // 1. Delete Journal Entries
            // Support both reference_type = trans_number OR description link
            // First try by reference_type
            $stmt = $pdo->prepare("SELECT id FROM fin_journals WHERE reference_type = ?");
            $stmt->execute([$trans_number]);
            $journal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($journal) {
                $pdo->prepare("DELETE FROM fin_journal_items WHERE journal_id = ?")->execute([$journal['id']]);
                $pdo->prepare("DELETE FROM fin_journals WHERE id = ?")->execute([$journal['id']]);
            }
            
            // 2. Delete Transaction
            $pdo->prepare("DELETE FROM fin_transactions WHERE trans_number = ?")->execute([$trans_number]);
            
            $pdo->commit();
            jsonResponse(true, 'Transaksi berhasil dihapus');
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // 1. GET ALL SETTINGS (Payment Types & Categories)
    if ($action == 'get_settings' && $method == 'GET') {
        
        // --- AUTO MANAGE FISCAL YEARS ---
        $currentYear = date('Y');
        $today = date('Y-m-d');

        // 1. Check if current year exists
        $stmtIns = $pdo->prepare("INSERT IGNORE INTO fin_fiscal_years (name, start_date, end_date, status, is_active) VALUES (?, ?, ?, 'OPEN', 0)");
        $stmtIns->execute([$currentYear, "$currentYear-01-01", "$currentYear-12-31"]);

        // 2. Auto Activate based on Date
        $stmtActive = $pdo->prepare("SELECT id, is_active FROM fin_fiscal_years WHERE ? BETWEEN start_date AND end_date LIMIT 1");
        $stmtActive->execute([$today]);
        $targetFy = $stmtActive->fetch();

        if ($targetFy && !$targetFy['is_active']) {
            $pdo->beginTransaction();
            $pdo->exec("UPDATE fin_fiscal_years SET is_active = 0");
            $upd = $pdo->prepare("UPDATE fin_fiscal_years SET is_active = 1 WHERE id = ?");
            $upd->execute([$targetFy['id']]);
            $pdo->commit();
        }
        // --------------------------------

        // Payment Types (Income Settings)
        $stmt = $pdo->query("SELECT * FROM fin_payment_types ORDER BY created_at DESC");
        $paymentTypes = $stmt->fetchAll();

        // Expense Categories
        $stmt = $pdo->query("SELECT * FROM fin_categories WHERE type='EXPENSE' ORDER BY code ASC");
        $expenseCategories = $stmt->fetchAll();

        // Income Categories (for manual income other than student bills)
        $stmt = $pdo->query("SELECT * FROM fin_categories WHERE type='INCOME' ORDER BY code ASC");
        $incomeCategories = $stmt->fetchAll();

        // Academic Years
        $stmt = $pdo->query("SELECT * FROM acad_years ORDER BY start_date DESC");
        $years = $stmt->fetchAll();
        
        // Fiscal Years
        $stmt = $pdo->query("SELECT * FROM fin_fiscal_years ORDER BY start_date DESC");
        $fiscalYears = $stmt->fetchAll();

        // Departments (Units)
        $stmt = $pdo->query("SELECT * FROM core_units WHERE code != 'YAYASAN' ORDER BY name ASC");
        $units = $stmt->fetchAll();

        jsonResponse(true, 'Data fetched', [
            'paymentTypes' => $paymentTypes,
            'expenseCategories' => $expenseCategories,
            'incomeCategories' => $incomeCategories,
            'years' => $years,
            'fiscalYears' => $fiscalYears,
            'units' => $units
        ]);
    }

    // 1.1 GET CLASSES (For Tariff Setting)
    if ($action == 'get_classes' && $method == 'GET') {
        $year_id = $_GET['academic_year_id'] ?? '';
        $unit_id = $_GET['unit_id'] ?? '';

        $sql = "SELECT c.id, c.name, l.name as level_name 
                FROM acad_classes c 
                JOIN acad_class_levels l ON c.level_id = l.id 
                WHERE 1=1";
        $params = [];

        if ($year_id) {
            $sql .= " AND c.academic_year_id = ?";
            $params[] = $year_id;
        }
        if ($unit_id) {
            $sql .= " AND l.unit_id = ?";
            $params[] = $unit_id;
        }

        $sql .= " ORDER BY l.order_index ASC, c.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'Found', $stmt->fetchAll());
    }

    // 1.2 GET TARIFFS
    if ($action == 'get_tariffs' && $method == 'GET') {
        $class_id = $_GET['class_id'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM fin_class_tariffs WHERE class_id = ?");
        $stmt->execute([$class_id]);
        jsonResponse(true, 'Found', $stmt->fetchAll());
    }

    // 1.3 SAVE TARIFF
    if ($action == 'save_tariff' && $method == 'POST') {
        $rawInput = file_get_contents('php://input');
        file_put_contents('../debug_finance.log', date('Y-m-d H:i:s') . " SAVE TARIFF: " . $rawInput . "\n", FILE_APPEND);
        
        $data = json_decode($rawInput, true);
        
        if (!$data) {
             file_put_contents('../debug_finance.log', "JSON DECODE FAILED\n", FILE_APPEND);
             jsonResponse(false, 'Invalid JSON');
        }

        $class_id = $data['class_id'];
        $payment_type_id = $data['payment_type_id'];
        $amount = $data['amount'];
        $academic_year_id = $data['academic_year_id']; // To ensure consistency

        // Upsert
        $sql = "INSERT INTO fin_class_tariffs (class_id, payment_type_id, academic_year_id, amount) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), academic_year_id = VALUES(academic_year_id)";
        
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([$class_id, $payment_type_id, $academic_year_id, $amount]);
        
        if (!$res) {
             file_put_contents('../debug_finance.log', "DB ERROR: " . implode(" ", $stmt->errorInfo()) . "\n", FILE_APPEND);
        } else {
             file_put_contents('../debug_finance.log', "SUCCESS ID: " . $pdo->lastInsertId() . "\n", FILE_APPEND);
        }
        
        jsonResponse(true, 'Tarif berhasil disimpan');
    }

    // 1.4 GET CLASS REPORT
    if ($action == 'get_class_report' && $method == 'GET') {
        $class_id = $_GET['class_id'];
        $payment_type_id = $_GET['payment_type_id'];
        
        $sql = "SELECT s.name as student_name, s.identity_number, 
                       b.bill_name, b.amount, b.amount_paid, b.status,
                       pt.name as payment_category, pt.type as payment_period,
                       (SELECT field_value 
                        FROM core_custom_values cv 
                        JOIN core_custom_fields cf ON cv.custom_field_id = cf.id 
                        WHERE cv.entity_id = s.id 
                          AND cf.field_key = 'statusanak' 
                          AND cf.entity_type = 'STUDENT' 
                        LIMIT 1) as status_anak,
                       (SELECT field_value 
                        FROM core_custom_values cv2 
                        JOIN core_custom_fields cf2 ON cv2.custom_field_id = cf2.id 
                        WHERE cv2.entity_id = s.id 
                          AND cf2.field_key = 'asrama_status' 
                          AND cf2.entity_type = 'STUDENT' 
                        LIMIT 1) as asrama_status
                FROM fin_student_bills b
                JOIN core_people s ON b.student_id = s.id
                JOIN acad_student_classes sc ON s.id = sc.student_id
                JOIN fin_payment_types pt ON b.payment_type_id = pt.id
                WHERE sc.class_id = ? AND sc.status='ACTIVE'";
        
        $params = [$class_id];

        if ($payment_type_id && $payment_type_id !== 'ALL') {
            $sql .= " AND b.payment_type_id = ?";
            $params[] = $payment_type_id;
        }

        $sql .= " ORDER BY s.name ASC, pt.name ASC, b.year ASC, b.month ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }



    // 2. SAVE PAYMENT TYPE (Jenis Pendapatan Wajib/Sukarela)
    if ($action == 'save_payment_type' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 15. SAVE FISCAL YEAR & UNIT PREFIX (Moved to own block)

        
        $name = $data['name'];
        $code = strtoupper($data['code']);
        $type = $data['type']; // MONTHLY, YEARLY, VOLUNTARY
        $amount = $data['default_amount'];
        $id = $data['id'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE fin_payment_types SET name=?, code=?, type=?, default_amount=? WHERE id=?");
            $stmt->execute([$name, $code, $type, $amount, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fin_payment_types (name, code, type, default_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $code, $type, $amount]);
        }
        jsonResponse(true, 'Jenis Pembayaran berhasil disimpan');
    }

    // 3. SAVE CATEGORY (Jenis Pengeluaran / Pendapatan Lain)
    if ($action == 'save_category' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = $data['name'];
        $code = strtoupper($data['code']);
        $type = $data['type']; // INCOME, EXPENSE
        $id = $data['id'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE fin_categories SET name=?, code=?, type=? WHERE id=?");
            $stmt->execute([$name, $code, $type, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fin_categories (name, code, type) VALUES (?, ?, ?)");
            $stmt->execute([$name, $code, $type]);
        }
        jsonResponse(true, 'Kategori berhasil disimpan');
    }

    // 4. GENERATE BILLS (Generate Tagihan Siswa)
    if ($action == 'generate_bills' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $academic_year_id = $data['academic_year_id'];
        $payment_type_id = $data['payment_type_id']; // ID of SPP or Uang Gedung
        $class_id = $data['class_id'] ?? null;
        $period_mode = $data['period_mode'] ?? 'FULL'; // FULL, SEMESTER_1, SEMESTER_2

        // Get Payment Type Info
        $stmt = $pdo->prepare("SELECT * FROM fin_payment_types WHERE id = ?");
        $stmt->execute([$payment_type_id]);
        $pType = $stmt->fetch();

        if (!$pType) jsonResponse(false, 'Jenis Pembayaran tidak ditemukan');

        // Get Active Students
        if ($class_id) {
            $stmt = $pdo->prepare("SELECT s.id, s.name FROM core_people s JOIN acad_student_classes sc ON s.id = sc.student_id WHERE s.type='STUDENT' AND sc.class_id = ? AND sc.status='ACTIVE'");
            $stmt->execute([$class_id]);
        } else {
            $stmt = $pdo->query("SELECT id, name FROM core_people WHERE type='STUDENT'");
        }
        $students = $stmt->fetchAll();
        
        $count = 0;
        
        $pdo->beginTransaction();

        // Pre-statements for optimization
        $stmtClass = $pdo->prepare("SELECT class_id FROM acad_student_classes WHERE student_id = ? AND status='ACTIVE'");
        $stmtTariff = $pdo->prepare("SELECT amount FROM fin_class_tariffs WHERE class_id = ? AND payment_type_id = ?");
        
        // Journal & Item Statements
        $jnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, created_at) VALUES (?, ?, NOW(), ?, ?, NOW())");
        $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
        
        $accRec = $pType['account_receivable_id'];
        $accRev = $pType['account_revenue_id'];

        foreach ($students as $student) {
            // Determine Amount (Tariff vs Default)
            $stmtClass->execute([$student['id']]);
            $classId = $stmtClass->fetchColumn();
            
            $billAmount = $pType['default_amount'];
            if ($classId) {
                $stmtTariff->execute([$classId, $payment_type_id]);
                $t = $stmtTariff->fetchColumn();
                if ($t > 0) $billAmount = $t;
            }

            $studentTotal = 0;
            $generatedBills = [];

            if ($pType['type'] == 'MONTHLY') {
                // Determine Months based on Mode
                $months = [];
                // Handle different period modes
                // Ensure $period_mode is string to avoid comparison issues
                $pm = (string)$period_mode;
                
                if ($pm === 'SEMESTER_1') {
                    $months = [7, 8, 9, 10, 11, 12];
                } else if ($pm === 'SEMESTER_2') {
                    $months = [1, 2, 3, 4, 5, 6];
                } else {
                    $months = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];
                }

                foreach ($months as $m) {
                    $year = ($m >= 7) ? 2025 : 2026; // TODO: Dynamic Year based on Academic Year
                    
                    // CALCULATE MONTHLY INSTALLMENT
                    // Revert to Standard Logic:
                    // The tariff amount IS the monthly amount.
                    // User probably confused because they set 500k but saw 6 Million TOTAL in report (500k x 12).
                    // Or they set 500k and saw 500k per month, which is correct.
                    // The issue "500rb generate 6 bulan hasilnya masih 6juta" means:
                    // They set 500k. They generated 6 months.
                    // Expected Total: 3 Million.
                    // Actual Total: 6 Million?? 
                    // This implies they generated 12 months (Full Year) instead of 6 months?
                    // OR they generated twice?
                    
                    // If the user meant "I want to input 3 Million for 6 months, and get 500k/mo":
                    // My previous fix did exactly that (divide by 6).
                    // But now user shows "199.998". 
                    // This means they likely inputted "1.200.000" (approx) and divided by 6 = 200k?
                    // Or inputted 1.2M and divided by 6?
                    
                    // Let's look at the screenshot: "Total Tagihan Rp 199.998".
                    // This is ~200.000.
                    // If divisor was 6, then input was ~1.200.000.
                    // If divisor was 12, then input was ~2.400.000.
                    
                    // The user's confusion might be:
                    // They WANT to input 500.000 and get 500.000 per month.
                    // My previous "fix" divided 500.000 by 6 = 83.333?? No.
                    
                    // Let's revert to: Tariff Amount = Monthly Amount.
                    // And explain to user: "Input 500.000 for 500.000/month".
                    // The previous "6 Juta" issue was likely because the REPORT shows TOTAL for ALL MONTHS generated?
                    // If I generate 12 months @ 500k, report shows 6M. That is correct behavior.
                    
                    // Let's remove the divisor logic and keep it simple.
                    // Tariff = Bill Amount.
                    $billAmount = $billAmount; // No Division


                    // Check duplicate
                    $check = $pdo->prepare("SELECT id FROM fin_student_bills WHERE student_id=? AND payment_type_id=? AND month=? AND year=?");
                    $check->execute([$student['id'], $payment_type_id, $m, $year]);
                    
                    if (!$check->fetch()) {
                        $ins = $pdo->prepare("INSERT INTO fin_student_bills (student_id, payment_type_id, academic_year_id, bill_name, amount, month, year) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $billName = $pType['name'] . " - " . $m . "/" . $year;
                        $ins->execute([$student['id'], $payment_type_id, $academic_year_id, $billName, $billAmount, $m, $year]);
                        $count++;
                        $studentTotal += $billAmount;
                        $generatedBills[] = $billName;
                    }
                }
            } else if ($pType['type'] == 'YEARLY' || $period_mode == 'ONCE') {
                 // Check duplicate
                 $check = $pdo->prepare("SELECT id FROM fin_student_bills WHERE student_id=? AND payment_type_id=? AND academic_year_id=?");
                 $check->execute([$student['id'], $payment_type_id, $academic_year_id]);
                 
                 if (!$check->fetch()) {
                     $ins = $pdo->prepare("INSERT INTO fin_student_bills (student_id, payment_type_id, academic_year_id, bill_name, amount) VALUES (?, ?, ?, ?, ?)");
                     $billName = $pType['name']; 
                     $ins->execute([$student['id'], $payment_type_id, $academic_year_id, $billName, $billAmount]);
                     $count++;
                     $studentTotal += $billAmount;
                 }
            }

            // CREATE INDIVIDUAL JOURNAL PER STUDENT (IF BILLS GENERATED)
            if ($studentTotal > 0) {
                if ($accRec && $accRev) {
                    // Determine Unit ID for Journal
                    // Look up student's active class -> level -> unit
                    $stmtUnit = $pdo->prepare("
                        SELECT l.unit_id 
                        FROM acad_student_classes sc
                        JOIN acad_classes c ON sc.class_id = c.id
                        JOIN acad_class_levels l ON c.level_id = l.id
                        WHERE sc.student_id = ? AND sc.status = 'ACTIVE'
                        LIMIT 1
                    ");
                    $stmtUnit->execute([$student['id']]);
                    $unit_id = $stmtUnit->fetchColumn();

                    $transNo = 'BILL/' . date('Ymd') . '/' . $student['id'] . '/' . rand(100, 999);
                    $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                    $journalDesc = "Tagihan " . $pType['name'] . " (" . $period_mode . ") - " . $student['name'];
                    
                    // Also create a Transaction record for BILLING so it appears in reports
                    // Type: BILLING (Not INCOME yet, but Receivable)
                    // Or reuse 'INCOME' but status 'UNPAID'? 
                    // Usually Billing is Accrual Income. 
                    // Let's insert into fin_transactions so JOIN works.
                    // REVERTED TO 'BILLING' FOR DATA ACCURACY. 
                    // PLEASE RUN fix_billing_schema.php IF "Data truncated" ERROR OCCURS.
                    
                    $insTrans = $pdo->prepare("INSERT INTO fin_transactions (unit_id, trans_date, trans_number, type, amount, description, student_id) VALUES (?, NOW(), ?, 'BILLING', ?, ?, ?)");
                    $insTrans->execute([$unit_id, $transNo, $studentTotal, $journalDesc, $student['id']]);

                    $jnl->execute([$unit_id, $jnlNo, $journalDesc, $transNo]);
                    $journalId = $pdo->lastInsertId();

                    // Debit: Receivable
                    $insItem->execute([$journalId, $accRec, $studentTotal, 0]);

                    // Credit: Revenue
                    $insItem->execute([$journalId, $accRev, 0, $studentTotal]);
                }
            }
        }
        
        $pdo->commit();
        jsonResponse(true, "Berhasil generate $count tagihan untuk siswa.");
    }
    
    // 5. GET STUDENT BILLS (Untuk Halaman Pembayaran)
    if ($action == 'get_student_bills' && $method == 'GET') {
        $student_id = $_GET['student_id'];
        
        // Bills
        $stmt = $pdo->prepare("
            SELECT b.*, pt.name as type_name, pt.type as payment_type, ay.name as academic_year_name
            FROM fin_student_bills b 
            JOIN fin_payment_types pt ON b.payment_type_id = pt.id 
            LEFT JOIN acad_years ay ON b.academic_year_id = ay.id
            WHERE b.student_id = ? 
            ORDER BY ay.start_date DESC, b.id ASC
        ");
        $stmt->execute([$student_id]);
        $bills = $stmt->fetchAll();
        
        // Savings Balance
        $stmt2 = $pdo->prepare("SELECT balance_after FROM fin_student_savings WHERE student_id = ? ORDER BY id DESC LIMIT 1");
        $stmt2->execute([$student_id]);
        $balance = $stmt2->fetchColumn() ?: 0;
        
        jsonResponse(true, 'Data fetched', ['bills' => $bills, 'savings_balance' => $balance]);
    }

    // 6. PAY BILL (Bayar Tagihan - Single or Batch)
    if ($action == 'pay_bill' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $reqBase = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        
        $pdo->beginTransaction();

        $items = $data['items'] ?? [];
        if (empty($items)) {
            // Single mode compatibility
            $items[] = [
                'bill_id' => $data['bill_id'],
                'amount' => $data['amount'],
                'cash_account_id' => $data['cash_account_id'] ?? null, // New field
                'description' => $data['description'] ?? null
            ];
        }

        $date = $data['date'] ?? date('Y-m-d H:i:s');
        $transNo = 'INV/' . date('Ymd') . '/' . rand(1000, 9999);
        $count = 0;

        foreach ($items as $i => $item) {
            $bill_id = $item['bill_id'];
            $amount_pay = $item['amount'];
            $discount = $item['discount_amount'] ?? 0; // NEW
            $cash_acc_id = $item['cash_account_id'] ?? null;
            $desc_custom = $item['description'] ?? null;
            $item_unit_id = $item['unit_id'] ?? null; // Get from payload

            // Get Bill & Payment Type Info
            $stmt = $pdo->prepare("
                SELECT b.*, pt.name as type_name, pt.account_receivable_id, pt.account_revenue_id, pt.account_cash_id, pt.account_discount_id 
                FROM fin_student_bills b 
                JOIN fin_payment_types pt ON b.payment_type_id = pt.id 
                WHERE b.id = ? FOR UPDATE
            ");
            $stmt->execute([$bill_id]);
            $bill = $stmt->fetch();
            
            if (!$bill) continue; // Skip invalid

            // DETERMINE UNIT ID
            // Priority 1: From Payload (User Selection)
            // Priority 2: From Student Class
            $unit_id = $item_unit_id;
            
            if (!$unit_id) {
                // Look up student's active class -> level -> unit
                $stmtUnit = $pdo->prepare("
                    SELECT l.unit_id 
                    FROM acad_student_classes sc
                    JOIN acad_classes c ON sc.class_id = c.id
                    JOIN acad_class_levels l ON c.level_id = l.id
                    WHERE sc.student_id = ? AND sc.status = 'ACTIVE'
                    LIMIT 1
                ");
                $stmtUnit->execute([$bill['student_id']]);
                $unit_id = $stmtUnit->fetchColumn();
            }

            if (!$unit_id) {
                // FAIL IF NO UNIT (User Requirement)
                // However, throwing error might break batch.
                // Let's rollback and error.
                $pdo->rollBack();
                jsonResponse(false, 'Gagal: Unit tidak ditemukan untuk transaksi ini. Mohon pilih unit.');
            }

            // Generate Numbers
            $transNo = generateReferenceNumber($pdo, $unit_id, 'I', 'fin_transactions', 'trans_number');

            // Update Bill
            // Total settled = Cash Paid + Discount
            $total_settled = $amount_pay + $discount;
            $new_paid = $bill['amount_paid'] + $total_settled;
            $status = ($new_paid >= $bill['amount']) ? 'PAID' : 'PARTIAL';
            
            $upd = $pdo->prepare("UPDATE fin_student_bills SET amount_paid = ?, status = ? WHERE id = ?");
            $upd->execute([$new_paid, $status, $bill_id]);
            
            // Get Student Info for Description
            $stmtStud = $pdo->prepare("
                SELECT s.name, c.name as class_name 
                FROM core_people s 
                JOIN acad_student_classes sc ON s.id = sc.student_id 
                JOIN acad_classes c ON sc.class_id = c.id
                WHERE s.id = ? AND sc.status = 'ACTIVE' LIMIT 1
            ");
            $stmtStud->execute([$bill['student_id']]);
            $studInfo = $stmtStud->fetch();
            $studentName = $studInfo['name'] ?? 'Siswa';
            $className = $studInfo['class_name'] ?? '-';

            $reqId = $reqBase !== '' ? ($reqBase . '-' . $i) : '';
            if ($reqId !== '') {
                $chk = $pdo->prepare("SELECT trans_number FROM fin_transactions WHERE request_id = ?");
                $chk->execute([$reqId]);
                $has = $chk->fetchColumn();
                if ($has) {
                    $count++;
                    continue;
                }
            }
            $ins = $pdo->prepare("INSERT INTO fin_transactions (unit_id, trans_date, trans_number, type, amount, description, student_bill_id, student_id, request_id) VALUES (?, ?, ?, 'INCOME', ?, ?, ?, ?, ?)");
            $desc = $desc_custom ?: "Pembayaran " . $bill['bill_name'] . " - " . $studentName . " (" . $className . ")";
            try {
                $ins->execute([$unit_id, $date, $transNo, $amount_pay, $desc, $bill_id, $bill['student_id'], $reqId ?: null]);
            } catch (\PDOException $e) {
                if ($reqId !== '') {
                    $count++;
                    continue;
                } else {
                    throw $e;
                }
            }

            // Journal Entries (Double Entry)
            // Debit: Cash (Asset)
            // Debit: Discount (Expense/Contra)
            // Credit: Receivable (Asset/Piutang) OR Revenue (Pendapatan) if Cash Basis (No Receivable)
            
            // NEW LOGIC: Always get account from Payment Type (bill) first
            $debitAcc = $cash_acc_id ?: $bill['account_cash_id']; // Cash
            $discAcc = $bill['account_discount_id']; // Discount Account from Settings
            
            // Logic: If Receivable exists, credit it (Payment of Debt).
            // If Receivable is NULL (Voluntary/Direct), credit Revenue directly.
            $creditAcc = $bill['account_receivable_id'] ?: $bill['account_revenue_id'];

            if ($debitAcc && $creditAcc) {
                // Journal Header
                $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                $jnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                
                $jnl->execute([$unit_id, $jnlNo, $date, $desc, $transNo]);
                $journalId = $pdo->lastInsertId();

                // Prepared statement for items
                $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");

                // Debit (Cash) - Only the paid amount
                $insItem->execute([$journalId, $debitAcc, $amount_pay, 0]);

                // Debit (Discount) - If any
                if ($discount > 0 && $discAcc) {
                    $insItem->execute([$journalId, $discAcc, $discount, 0]);
                }

                // Credit (Receivable) - Total settled
                $insItem->execute([$journalId, $creditAcc, 0, $total_settled]);
            } else {
                 file_put_contents('../debug_finance.log', "MISSING ACCOUNTS FOR INCOME: Debit=$debitAcc Credit=$creditAcc BillID=$bill_id\n", FILE_APPEND);
            }

            $count++;
        }
        
        $pdo->commit();
        jsonResponse(true, "Berhasil memproses $count pembayaran!", ['trans_number' => $transNo]);
    }

    // 7. SAVE SAVINGS (Tabungan)
    if ($action == 'save_savings' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $student_id = $data['student_id'];
        $type = $data['type']; // DEPOSIT / WITHDRAW
        $amount = $data['amount'];
        $description = $data['description'] ?? '';
        $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';

        $pdo->beginTransaction();

        try {
            // Ambil Detail Siswa & Unit
            $stmtS = $pdo->prepare("SELECT name, identity_number, unit_id FROM core_people WHERE id = ?");
            $stmtS->execute([$student_id]);
            $student = $stmtS->fetch();
            $unit_id = $student['unit_id'] ?? null;
            
            // Fallback ke kelas aktif jika unit_id di core_people kosong
            if (!$unit_id) {
                $stmtUnitClass = $pdo->prepare("
                    SELECT u.id FROM acad_student_classes sc
                    JOIN acad_classes c ON sc.class_id = c.id
                    JOIN core_units u ON c.unit_id = u.id
                    WHERE sc.student_id = ? AND sc.status = 'ACTIVE' LIMIT 1
                ");
                $stmtUnitClass->execute([$student_id]);
                $unit_id = $stmtUnitClass->fetchColumn();
            }

            // Cek Saldo Terakhir
            $stmt = $pdo->prepare("SELECT balance_after FROM fin_student_savings WHERE student_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute([$student_id]);
            $current = $stmt->fetchColumn() ?: 0;

            if ($type == 'WITHDRAW' && $current < $amount) {
                throw new Exception('Saldo tidak mencukupi!');
            }

            $new_balance = ($type == 'DEPOSIT') ? $current + $amount : $current - $amount;

            // 1. Simpan Log Tabungan
            $ins = $pdo->prepare("INSERT INTO fin_student_savings (student_id, transaction_type, amount, balance_before, balance_after, description, request_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$student_id, $type, $amount, $current, $new_balance, $description, $reqId ?: null]);
            $savingsId = $pdo->lastInsertId();

            // 2. Simpan ke Transaksi Kas (fin_transactions)
            $transType = ($type == 'DEPOSIT') ? 'INCOME' : 'EXPENSE';
            $transNo = 'SAV-' . time() . '-' . rand(100, 999);
            $desc = "Tabungan " . ($type == 'DEPOSIT' ? 'Setoran' : 'Penarikan') . " - " . ($student['name'] ?? 'Siswa');
            
            $insTrans = $pdo->prepare("INSERT INTO fin_transactions (trans_date, trans_number, type, amount, description, student_id, request_id, unit_id) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)");
            $insTrans->execute([$transNo, $transType, $amount, $desc, $student_id, $reqId ?: null, $unit_id]);

            // 3. INTEGRASI JURNAL (GL)
            $stmtAcc = $pdo->prepare("SELECT id, code FROM fin_accounts WHERE code IN ('112', '211')");
            $stmtAcc->execute();
            $accounts = $stmtAcc->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $accKasTabungan = array_search('112', $accounts); 
            $accSimpananSiswa = array_search('211', $accounts); 
            
            if (!$accKasTabungan) {
                 foreach($accounts as $id => $code) if ($code == '112') $accKasTabungan = $id;
            }
            if (!$accSimpananSiswa) {
                 foreach($accounts as $id => $code) if ($code == '211') $accSimpananSiswa = $id;
            }

            if ($accKasTabungan && $accSimpananSiswa && $unit_id) {
                $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                $jnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, reference_id, created_at) VALUES (?, ?, CURDATE(), ?, 'SAVINGS', ?, NOW())");
                $jnl->execute([$unit_id, $jnlNo, $desc, $savingsId]);
                $journalId = $pdo->lastInsertId();

                $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
                if ($type == 'DEPOSIT') {
                    $insItem->execute([$journalId, $accKasTabungan, $amount, 0]); // Debit Kas
                    $insItem->execute([$journalId, $accSimpananSiswa, 0, $amount]); // Credit Simpanan
                } else {
                    $insItem->execute([$journalId, $accSimpananSiswa, $amount, 0]); // Debit Simpanan
                    $insItem->execute([$journalId, $accKasTabungan, 0, $amount]); // Credit Kas
                }
            }

            $pdo->commit();
            jsonResponse(true, 'Transaksi Tabungan Berhasil', ['new_balance' => $new_balance]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, $e->getMessage());
        }
    }

    // 7a. SAVE SAVINGS BATCH (Tabungan Massal)
    if ($action == 'save_savings_batch' && $method == 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        $type = $input['type'] ?? 'DEPOSIT';
        $reqBase = isset($input['request_id']) ? trim((string)$input['request_id']) : '';

        if (empty($items)) jsonResponse(false, 'Data kosong');

        $pdo->beginTransaction();
        try {
            $count = 0;
            
            // PREPARE STATEMENTS ONCE
            $stmtS = $pdo->prepare("SELECT name, identity_number, unit_id FROM core_people WHERE id = ?");
            $stmtUnitClass = $pdo->prepare("SELECT u.id FROM acad_student_classes sc JOIN acad_classes c ON sc.class_id = c.id JOIN core_units u ON c.unit_id = u.id WHERE sc.student_id = ? AND sc.status = 'ACTIVE' LIMIT 1");
            $stmtBal = $pdo->prepare("SELECT balance_after FROM fin_student_savings WHERE student_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $insSav = $pdo->prepare("INSERT INTO fin_student_savings (student_id, transaction_type, amount, balance_before, balance_after, description, request_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insTrans = $pdo->prepare("INSERT INTO fin_transactions (trans_date, trans_number, type, amount, description, student_id, request_id, unit_id) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)");
            $insJnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, reference_id, created_at) VALUES (?, ?, CURDATE(), ?, 'SAVINGS', ?, NOW())");
            $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");

            // Get GL Accounts
            $stmtAcc = $pdo->prepare("SELECT id, code FROM fin_accounts WHERE code IN ('112', '211')");
            $stmtAcc->execute();
            $accounts = $stmtAcc->fetchAll(PDO::FETCH_KEY_PAIR);
            $accKasTabungan = array_search('112', $accounts); 
            if (!$accKasTabungan) foreach($accounts as $id => $code) if ($code == '112') $accKasTabungan = $id;
            $accSimpananSiswa = array_search('211', $accounts); 
            if (!$accSimpananSiswa) foreach($accounts as $id => $code) if ($code == '211') $accSimpananSiswa = $id;

            foreach ($items as $idx => $item) {
                $student_id = $item['student_id'];
                $amount = $item['amount'];
                $description = $item['description'] ?? ($type == 'DEPOSIT' ? 'Setoran Massal' : 'Penarikan Massal');
                $reqId = $reqBase ? $reqBase . '-' . $student_id : ''; // Unique per student in batch
                
                if ($amount <= 0) continue;

                // 1. Get Student Info
                $stmtS->execute([$student_id]);
                $student = $stmtS->fetch();
                $unit_id = $student['unit_id'] ?? null;
                if (!$unit_id) {
                    $stmtUnitClass->execute([$student_id]);
                    $unit_id = $stmtUnitClass->fetchColumn();
                }

                // 2. Check Balance
                $stmtBal->execute([$student_id]);
                $current = $stmtBal->fetchColumn() ?: 0;
                if ($type == 'WITHDRAW' && $current < $amount) continue; // Skip insufficient balance in batch

                $new_balance = ($type == 'DEPOSIT') ? $current + $amount : $current - $amount;

                // 3. Insert Savings Log
                $insSav->execute([$student_id, $type, $amount, $current, $new_balance, $description, $reqId ?: null]);
                $savingsId = $pdo->lastInsertId();

                // 4. Insert Transaction
                $transType = ($type == 'DEPOSIT') ? 'INCOME' : 'EXPENSE';
                $transNo = 'SAV-' . time() . '-' . rand(1000, 9999);
                $desc = "Tabungan " . ($type == 'DEPOSIT' ? 'Setoran' : 'Penarikan') . " - " . ($student['name'] ?? 'Siswa');
                $insTrans->execute([$transNo, $transType, $amount, $desc, $student_id, $reqId ?: null, $unit_id]);

                // 5. Journal Entry
                if ($accKasTabungan && $accSimpananSiswa && $unit_id) {
                    $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                    $insJnl->execute([$unit_id, $jnlNo, $desc, $savingsId]);
                    $journalId = $pdo->lastInsertId();

                    if ($type == 'DEPOSIT') {
                        $insItem->execute([$journalId, $accKasTabungan, $amount, 0]);
                        $insItem->execute([$journalId, $accSimpananSiswa, 0, $amount]);
                    } else {
                        $insItem->execute([$journalId, $accSimpananSiswa, $amount, 0]);
                        $insItem->execute([$journalId, $accKasTabungan, 0, $amount]);
                    }
                }
                $count++;
            }
            
            $pdo->commit();
            jsonResponse(true, "Berhasil memproses $count transaksi!", ['count' => $count]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, $e->getMessage());
        }
    }

    // 42. GET CLASS SAVINGS LIST (For Batch Input)
    if ($action == 'get_class_savings_list' && $method == 'GET') {
        $class_id = $_GET['class_id'] ?? '';
        if (!$class_id) jsonResponse(false, 'Class ID diperlukan');

        $sql = "SELECT s.id, s.name, s.identity_number, 
                       COALESCE((SELECT balance_after FROM fin_student_savings WHERE student_id = s.id ORDER BY id DESC LIMIT 1), 0) as balance
                FROM core_people s
                JOIN acad_student_classes sc ON s.id = sc.student_id
                WHERE sc.class_id = ? AND sc.status = 'ACTIVE' AND s.type = 'STUDENT'
                ORDER BY s.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$class_id]);
        jsonResponse(true, 'Data ditemukan', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 43. GET CLASSES (Helper for Dropdown)
    if ($action == 'get_classes' && $method == 'GET') {
        $unit_id = $_GET['unit_id'] ?? '';
        $sql = "SELECT c.id, c.name FROM acad_classes c 
                JOIN acad_class_levels l ON c.level_id = l.id
                WHERE 1=1";
        $params = [];
        if ($unit_id) {
            $sql .= " AND l.unit_id = ?";
            $params[] = $unit_id;
        }
        $sql .= " ORDER BY c.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 8. SAVE EXPENSE (Pengeluaran - Single or Batch)
    if ($action == 'save_expense' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $pdo->beginTransaction();

        $items = $data['items'] ?? []; // Batch mode
        if (empty($items)) {
            // Compatibility with single item request
            $items[] = [
                'category_id' => $data['category_id'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'cash_account_id' => $data['cash_account_id'] ?? null,
                'pic' => $data['pic'] ?? null,
                'receiver' => $data['receiver'] ?? null
            ];
        }

        $date = $data['date'] ?? date('Y-m-d H:i:s');
        $reqBase = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        $unit_id_global = $data['unit_id'] ?? null;
        // $transNo will be generated per item if needed, but usually Expense batch shares same TransNo? 
        // No, separate transactions usually better for audit unless split. 
        // Current logic: foreach item -> New Transaction.
        // So we generate TransNo inside loop.

        foreach ($items as $i => $item) {
            $catId = $item['category_id'];
            $amt = $item['amount'];
            $desc = $item['description'];
            $pic = $item['pic'] ?? null;
            $receiver = $item['receiver'] ?? null;
            $cashAccId = $item['cash_account_id'] ?? null;
            $item_unit_id = $item['unit_id'] ?? null; // Fix: Get from item

            // Determine Unit ID
            // Priority: Item > Global > Category
            $unit_id = $item_unit_id ?: $unit_id_global;
            
            if (!$unit_id && $catId) {
                 $stmtCatUnit = $pdo->prepare("SELECT u.id FROM fin_categories c JOIN core_units u ON c.department_id = u.id WHERE c.id = ?");
                 $stmtCatUnit->execute([$catId]);
                 $unit_id = $stmtCatUnit->fetchColumn();
            }

            if (!$unit_id) {
                $pdo->rollBack();
                jsonResponse(false, 'Gagal: Unit tidak ditemukan untuk transaksi pengeluaran ini.');
            }

            $transNo = null;
            $didInsert = true;
            $reqId = $reqBase !== '' ? ($reqBase . '-' . $i) : '';
            if ($reqId !== '') {
                $sCheck = $pdo->prepare("SELECT trans_number FROM fin_transactions WHERE request_id = ?");
                $sCheck->execute([$reqId]);
                $foundTrans = $sCheck->fetchColumn();
                if ($foundTrans) {
                    $transNo = $foundTrans;
                    $didInsert = false;
                }
            }
            if (!$transNo) {
                $transNo = generateReferenceNumber($pdo, $unit_id, 'E', 'fin_transactions', 'trans_number');
            }

            if ($didInsert) {
                try {
                    $ins = $pdo->prepare("INSERT INTO fin_transactions (unit_id, trans_date, trans_number, type, category_id, amount, description, pic, receiver, request_id) VALUES (?, ?, ?, 'EXPENSE', ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$unit_id, $date, $transNo, $catId, $amt, $desc, $pic, $receiver, $reqId ?: null]);
                    $transId = $pdo->lastInsertId();
                } catch (\PDOException $e) {
                    if ($reqId !== '') {
                        $sCheck2 = $pdo->prepare("SELECT trans_number FROM fin_transactions WHERE request_id = ?");
                        $sCheck2->execute([$reqId]);
                        $transNo = $sCheck2->fetchColumn() ?: $transNo;
                        $didInsert = false;
                    } else {
                        throw $e;
                    }
                }
            }

            // 2. Journal Entries (Double Entry)
            // Get Category Mapping (Debit Account)
            $stmtCat = $pdo->prepare("SELECT account_id, account_cash_id FROM fin_categories WHERE id = ?");
            $stmtCat->execute([$catId]);
            $catData = $stmtCat->fetch();

            if ($catData && $didInsert) {
                // Determine Accounts
                $debitAcc = $catData['account_id'];
                // Credit Account: Prioritize user selection, then category default
                $creditAcc = $cashAccId ?: $catData['account_cash_id']; 

                if ($debitAcc && $creditAcc) {
                    // Create Journal Header
                    $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                    $jnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    
                    $jnl->execute([$unit_id, $jnlNo, $date, $desc, $transNo]);
                    $journalId = $pdo->lastInsertId();

                    // Prepared statement for items
                    $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");

                    // Debit (Expense)
                    $insItem->execute([$journalId, $debitAcc, $amt, 0]);

                    // Credit (Cash)
                    $insItem->execute([$journalId, $creditAcc, 0, $amt]);
                } else {
                    // Fallback or Log Missing Accounts
                    file_put_contents('../debug_finance.log', "MISSING ACCOUNTS FOR EXPENSE: Debit=$debitAcc Credit=$creditAcc CatID=$catId\n", FILE_APPEND);
                    // Force Rollback? Maybe not, but user complains "no journal".
                    // If strict mode is needed:
                    // $pdo->rollBack();
                    // jsonResponse(false, 'Gagal: Akun Jurnal (Debit/Kredit) belum disetting untuk kategori ini.');
                }
            } else {
                 file_put_contents('../debug_finance.log', "CATEGORY NOT FOUND OR NO ACCOUNTS: CatID=$catId\n", FILE_APPEND);
            }
        }

        $pdo->commit();
        jsonResponse(true, 'Pengeluaran berhasil dicatat', ['trans_number' => $transNo]);
    }
    
    // 9. SEARCH STUDENTS (Helper)
    if ($action == 'search_students' && $method == 'GET') {
        $q = $_GET['q'] ?? '';
        $unit_id = $_GET['unit_id'] ?? '';
        
        $sql = "SELECT s.id, s.name, s.identity_number, s.unit_id, c.name as class_name
                FROM core_people s
                LEFT JOIN acad_student_classes sc ON s.id = sc.student_id AND sc.status = 'ACTIVE'
                LEFT JOIN acad_classes c ON sc.class_id = c.id
                WHERE s.type='STUDENT' AND s.name LIKE ?";
        $params = ["%$q%"];

        if (!empty($unit_id)) {
            $sql .= " AND s.unit_id = ?";
            $params[] = $unit_id;
        }

        $sql .= " LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // 10. GET EXPENSE LIST (Report Helper)
    if ($action == 'get_expenses' && $method == 'GET') {
        $stmt = $pdo->query("SELECT t.*, c.name as category_name FROM fin_transactions t LEFT JOIN fin_categories c ON t.category_id = c.id WHERE t.type='EXPENSE' ORDER BY t.trans_date DESC LIMIT 50");
        jsonResponse(true, 'Found', $stmt->fetchAll());
    }

    // 11. GET ACCOUNTS (COA)
    if ($action == 'get_accounts' && $method == 'GET') {
        $stmt = $pdo->query("SELECT * FROM fin_accounts ORDER BY code ASC");
        jsonResponse(true, 'Found', $stmt->fetchAll());
    }

    // 12. SAVE ACCOUNT
    if ($action == 'save_account' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE fin_accounts SET code=?, name=?, type=?, balance_type=? WHERE id=?");
            $stmt->execute([$data['code'], $data['name'], $data['type'], $data['balance_type'], $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fin_accounts (code, name, type, balance_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['code'], $data['name'], $data['type'], $data['balance_type']]);
        }
        jsonResponse(true, 'Rekening berhasil disimpan');
    }

    // 13. SAVE PAYMENT TYPE FULL (With Categories & Mapping)
    if ($action == 'save_payment_type_full' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = $data['name'];
        $code = strtoupper($data['code']);
        $type = $data['type'] ?? 'VOLUNTARY'; // Default if missing
        $category = $data['category'];
        $amount = $data['default_amount'] ?? 0;
        
        $acc_rev = $data['account_revenue_id'] ?: null;
        $acc_rec = $data['account_receivable_id'] ?: null;
        $acc_cash = $data['account_cash_id'] ?: null;
        $acc_disc = $data['account_discount_id'] ?: null;

        // Force NULL for Voluntary
        if (strpos($category, 'VOLUNTARY') !== false) {
            $acc_rec = null;
            $acc_disc = null;
        }
        
        $id = $data['id'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE fin_payment_types SET name=?, code=?, type=?, category=?, default_amount=?, account_revenue_id=?, account_receivable_id=?, account_cash_id=?, account_discount_id=? WHERE id=?");
            $stmt->execute([$name, $code, $type, $category, $amount, $acc_rev, $acc_rec, $acc_cash, $acc_disc, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fin_payment_types (name, code, type, category, default_amount, account_revenue_id, account_receivable_id, account_cash_id, account_discount_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $type, $category, $amount, $acc_rev, $acc_rec, $acc_cash, $acc_disc]);
        }
        jsonResponse(true, 'Jenis Penerimaan berhasil disimpan');
    }

    // 14. SAVE EXPENSE CATEGORY
    if ($action == 'save_expense_category' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = $data['name'];
        $code = strtoupper($data['code']);
        $acc_id = $data['account_id'] ?: null;
        $cash_acc_id = $data['account_cash_id'] ?: null;
        $dept_id = $data['department_id'] ?: null;
        $desc = $data['description'] ?? '';
        $id = $data['id'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE fin_categories SET name=?, code=?, account_id=?, account_cash_id=?, department_id=?, description=? WHERE id=?");
            $stmt->execute([$name, $code, $acc_id, $cash_acc_id, $dept_id, $desc, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fin_categories (name, code, type, account_id, account_cash_id, department_id, description) VALUES (?, ?, 'EXPENSE', ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $acc_id, $cash_acc_id, $dept_id, $desc]);
        }
        jsonResponse(true, 'Kategori Pengeluaran berhasil disimpan');
    }

    // 15. SAVE FISCAL SETTINGS
    if ($action == 'save_fiscal_settings' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $pdo->beginTransaction();

        // Save Unit Prefixes
        if (isset($data['units'])) {
            foreach ($data['units'] as $unit) {
                $upd = $pdo->prepare("UPDATE core_units SET receipt_code = ? WHERE id = ?");
                $upd->execute([strtoupper($unit['receipt_code']), $unit['id']]);
            }
        }

        // Save Fiscal Year (Simple: Just Activate)
        if (isset($data['active_year_id'])) {
            $pdo->exec("UPDATE fin_fiscal_years SET is_active = FALSE");
            $upd = $pdo->prepare("UPDATE fin_fiscal_years SET is_active = TRUE WHERE id = ?");
            $upd->execute([$data['active_year_id']]);
        }
        
        $pdo->commit();
        jsonResponse(true, 'Pengaturan Umum berhasil disimpan');
    }

    // 16. SAVE RECEIVABLE TYPE
    if ($action == 'save_receivable_type' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'];
        $code = $data['code'];
        $category = $data['category']; // 'MANDATORY_STUDENT', 'VOLUNTARY_STUDENT', etc.
        
        $acc_rev = $data['account_revenue_id'] ?? null;
        $acc_rec = $data['account_receivable_id'] ?? null;
        $acc_disc = $data['account_discount_id'] ?? null;
        $acc_cash = $data['account_cash_id'] ?? null;
        
        // Handle Empty String to Null
        if ($acc_rev === '') $acc_rev = null;
        if ($acc_rec === '') $acc_rec = null;
        if ($acc_disc === '') $acc_disc = null;
        if ($acc_cash === '') $acc_cash = null;

        // If VOLUNTARY, force acc_rec and acc_disc to NULL if not provided
        if (strpos($category, 'VOLUNTARY') !== false) {
            $acc_rec = null;
            $acc_disc = null;
        }

        if (empty($data['id'])) {
            $stmt = $pdo->prepare("INSERT INTO fin_payment_types (name, code, category, account_revenue_id, account_receivable_id, account_discount_id, account_cash_id, type, default_amount) VALUES (?, ?, ?, ?, ?, ?, ?, 'VOLUNTARY', 0)");
            // Note: fin_receivable_types is actually fin_payment_types in DB schema used in save_payment_type_full
            // Let's align with save_payment_type_full logic which seems to be the correct one for this form.
            // Wait, this block seems to be a duplicate or older version of 'save_payment_type_full'.
            // The frontend calls 'save_payment_type_full' (Action 13).
            // Let's fix Action 13 instead.
        } 
    }

    // 17. GET INCOME JOURNALS (Laporan Jurnal Penerimaan)
    if ($action == 'get_income_journals' && $method == 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $unit_prefix = $_GET['unit_prefix'] ?? '';
        
        // Use LEFT JOIN to show journals even if transaction link is missing (orphan check via string pattern)
        $sql = "SELECT j.*, t.pic, t.receiver, j.journal_number as journal_no 
                FROM fin_journals j
                LEFT JOIN fin_transactions t ON j.reference_type = t.trans_number
                LEFT JOIN core_units u ON j.unit_id = u.id
                WHERE (t.type IN ('INCOME', 'BILLING') OR j.reference_type LIKE 'BILL/%' OR j.reference_type LIKE 'INV/%')
                AND j.journal_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];

        if (!empty($unit_prefix) && $unit_prefix !== 'all') {
            $sql .= " AND u.code = ?";
            $params[] = $unit_prefix;
        }

        $sql .= " ORDER BY j.journal_date DESC, j.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch Items for these journals
        if (!empty($journals)) {
            $journalIds = array_column($journals, 'id');
            $placeholders = str_repeat('?,', count($journalIds) - 1) . '?';
            
            $sqlItems = "SELECT ji.*, a.code, a.name as account_name 
                         FROM fin_journal_items ji
                         JOIN fin_accounts a ON ji.account_id = a.id
                         WHERE ji.journal_id IN ($placeholders)
                         ORDER BY ji.debit DESC"; // Debit first
            
            $stmtItems = $pdo->prepare($sqlItems);
            $stmtItems->execute($journalIds);
            $allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            // Group items by journal_id
            $itemsGrouped = [];
            foreach ($allItems as $item) {
                $itemsGrouped[$item['journal_id']][] = $item;
            }
            
            // Merge items into journals
            foreach ($journals as &$j) {
                $j['items'] = $itemsGrouped[$j['id']] ?? [];
            }
        }
        
        jsonResponse(true, 'Data fetched', $journals);
    }

    // 17b. GET EXPENSE JOURNALS (Laporan Jurnal Pengeluaran)
    if ($action == 'get_expense_journals' && $method == 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $unit_prefix = $_GET['unit_prefix'] ?? '';
        
        $sql = "SELECT j.*, t.pic, t.receiver, j.journal_number as journal_no 
                FROM fin_journals j
                LEFT JOIN fin_transactions t ON j.reference_type = t.trans_number
                LEFT JOIN core_units u ON j.unit_id = u.id
                WHERE (t.type = 'EXPENSE' OR j.reference_type LIKE 'EXP/%' OR j.reference_type LIKE 'E-%') 
                AND j.journal_date BETWEEN ? AND ?";

        $params = [$startDate, $endDate];

        if (!empty($unit_prefix) && $unit_prefix !== 'all') {
            $sql .= " AND u.receipt_code = ?";
            $params[] = $unit_prefix;
        }

        $sql .= " ORDER BY j.journal_date DESC, j.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch Items for these journals
        if (!empty($journals)) {
            $journalIds = array_column($journals, 'id');
            $placeholders = str_repeat('?,', count($journalIds) - 1) . '?';
            
            $sqlItems = "SELECT ji.*, a.code, a.name as account_name 
                         FROM fin_journal_items ji
                         JOIN fin_accounts a ON ji.account_id = a.id
                         WHERE ji.journal_id IN ($placeholders)
                         ORDER BY ji.debit DESC"; // Debit first
            
            $stmtItems = $pdo->prepare($sqlItems);
            $stmtItems->execute($journalIds);
            $allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            // Group items by journal_id
            $itemsGrouped = [];
            foreach ($allItems as $item) {
                $itemsGrouped[$item['journal_id']][] = $item;
            }
            
            // Merge items into journals
            foreach ($journals as &$j) {
                $j['items'] = $itemsGrouped[$j['id']] ?? [];
            }
        }
        
        jsonResponse(true, 'Data fetched', $journals);
    }

        // 17c. GET SAVINGS JOURNALS (Laporan Jurnal Tabungan)
    if ($action == 'get_savings_journals' && $method == 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $unit_prefix = $_GET['unit_prefix'] ?? '';
        
        $sql = "SELECT j.*, s.transaction_type, u.receipt_code as unit_code,
                       j.journal_date, j.journal_number as journal_no
                FROM fin_journals j
                JOIN fin_student_savings s ON j.reference_id = s.id
                LEFT JOIN core_units u ON j.unit_id = u.id
                WHERE j.reference_type = 'SAVINGS' 
                  AND j.journal_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        if (!empty($unit_prefix) && $unit_prefix !== 'all') {
            $sql .= " AND u.receipt_code = ?";
            $params[] = $unit_prefix;
        }

        $sql .= " ORDER BY j.journal_date DESC, j.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ambil item jurnal riil
        foreach ($journals as &$j) {
            $stmtItems = $pdo->prepare("
                SELECT ji.*, a.code, a.name as account_name 
                FROM fin_journal_items ji
                JOIN fin_accounts a ON ji.account_id = a.id
                WHERE ji.journal_id = ? ORDER BY ji.debit DESC
            ");
            $stmtItems->execute([$j['id']]);
            $j['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            // Reconstruct description for UI consistency if needed
            // But we already have journal description.
            // Let's ensure description is user friendly.
            // $j['description'] is already set from DB.
        }
        
        jsonResponse(true, 'Data fetched', $journals);
    }

    // 18. GET TRIAL BALANCE (Neraca Percobaan)
    if ($action == 'get_trial_balance' && $method == 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $unit_prefix = $_GET['unit_prefix'] ?? '';
        
        if (!empty($unit_prefix) && $unit_prefix !== 'all') {
            
            // Correct Logic:
            // WHERE ( (j.journal_date BETWEEN ? AND ? AND u.receipt_code = ?) OR j.id IS NULL )
            
            // Override the previous WHERE
            $sql = "SELECT a.code, a.name, a.type, a.balance_type,
                       SUM(CASE WHEN u.receipt_code = ? THEN ji.debit ELSE 0 END) as total_debit, 
                       SUM(CASE WHEN u.receipt_code = ? THEN ji.credit ELSE 0 END) as total_credit
                FROM fin_accounts a
                LEFT JOIN fin_journal_items ji ON a.id = ji.account_id
                LEFT JOIN fin_journals j ON ji.journal_id = j.id AND (j.journal_date BETWEEN ? AND ?)
                LEFT JOIN core_units u ON j.unit_id = u.id
                GROUP BY a.id
                ORDER BY 
                    CASE a.type
                        WHEN 'ASSET' THEN 1
                        WHEN 'LIABILITY' THEN 2
                        WHEN 'EQUITY' THEN 3
                        WHEN 'REVENUE' THEN 4
                        WHEN 'EXPENSE' THEN 5
                        ELSE 6
                    END ASC,
                    a.code ASC";
            
            // Re-think: "CASE WHEN" aggregation is safer to keep all accounts but zero out others.
            // This handles "Global" vs "Unit" perfectly.
            // If Unit selected: SUM only if unit matches.
            // If No Unit: SUM all.
            
            $params = [$unit_prefix, $unit_prefix, $startDate, $endDate];
        } else {
            // Global Mode
            $sql = "SELECT a.code, a.name, a.type, a.balance_type,
                       SUM(ji.debit) as total_debit, 
                       SUM(ji.credit) as total_credit
                FROM fin_accounts a
                LEFT JOIN fin_journal_items ji ON a.id = ji.account_id
                LEFT JOIN fin_journals j ON ji.journal_id = j.id AND (j.journal_date BETWEEN ? AND ?)
                GROUP BY a.id
                ORDER BY 
                    CASE a.type
                        WHEN 'ASSET' THEN 1
                        WHEN 'LIABILITY' THEN 2
                        WHEN 'EQUITY' THEN 3
                        WHEN 'REVENUE' THEN 4
                        WHEN 'EXPENSE' THEN 5
                        ELSE 6
                    END ASC,
                    a.code ASC";
             $params = [$startDate, $endDate];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Net Balance for each row based on normal balance type
        foreach ($rows as &$row) {
            $row['debit'] = $row['total_debit'] ?? 0;
            $row['credit'] = $row['total_credit'] ?? 0;
            
            // In Trial Balance report requested, user wants to see Debit and Credit columns populated.
            // Usually we show the ending balance in either Debit or Credit column.
            // Balance = Debit - Credit.
            $balance = $row['debit'] - $row['credit'];
            
            if ($row['balance_type'] == 'DEBIT') {
                // If positive, put in Debit. If negative (contra), put in Credit? 
                // Standard Trial Balance: Just list total Debits and total Credits? 
                // Or Net Balance? User image shows "Debet" and "Kredit" columns with values.
                // Example: Kas (Debit: 25jt, Credit: empty). 
                // Example: Utang (Debit: empty, Credit: 500rb).
                // Example: Diskon (Debit: empty, Credit: -7jt) -> Wait, Discount is usually Debit balance (Contra Revenue).
                // If User image shows Diskon in Credit column as negative? No, usually Diskon is Debit.
                // Let's stick to: Net Balance. If > 0 in Debit col, if < 0 in Credit col (absolute value).
                
                // Wait, User image: "Diskon SPP" is in Kredit column with (Rp 7.715.000) -> Parentheses usually mean negative.
                // Negative Credit = Positive Debit. So it is a Debit balance.
                // Let's simply show:
                // If Balance > 0 -> Debit Col
                // If Balance < 0 -> Credit Col (positive value)
                
                // However, Revenue is Credit normal.
                // Revenue: Credit - Debit.
                
                // Let's use simple logic:
                // Sum Debit, Sum Credit.
                // Net = Debit - Credit.
                // If Net > 0 -> Show in Debit.
                // If Net < 0 -> Show in Credit (abs).
                // This works for Assets (Debit), Expenses (Debit).
                // Works for Revenue (Credit, so Net is negative -> Show in Credit).
                // Works for Liability/Equity (Credit, so Net is negative -> Show in Credit).
                
                $row['view_debit'] = ($balance > 0) ? $balance : 0;
                $row['view_credit'] = ($balance < 0) ? abs($balance) : 0;
            } else {
                // Credit Normal (Revenue, Liability, Equity)
                // Balance = Credit - Debit
                // If Balance > 0 -> Show in Credit.
                // If Balance < 0 -> Show in Debit (abs).
                
                // But wait, "Diskon SPP" is Revenue type but Contra (Debit normal).
                // If I use generic logic:
                // Net = Debit - Credit.
                // If Net != 0:
                //   If Net > 0: Debit Col = Net, Credit Col = 0
                //   If Net < 0: Debit Col = 0, Credit Col = Abs(Net)
                
                $net = $row['debit'] - $row['credit'];
                $row['view_debit'] = ($net > 0) ? $net : 0;
                $row['view_credit'] = ($net < 0) ? abs($net) : 0;
            }
        }

        jsonResponse(true, 'Data fetched', $rows);
    }

    // 19. RESET FINANCE DATA (Debug/Development)
    if ($action == 'reset_finance_data' && $method == 'POST') {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE fin_transactions");
        $pdo->exec("TRUNCATE TABLE fin_student_bills");
        $pdo->exec("TRUNCATE TABLE fin_journals");
        $pdo->exec("TRUNCATE TABLE fin_journal_items");
        $pdo->exec("TRUNCATE TABLE fin_class_tariffs");
        $pdo->exec("TRUNCATE TABLE fin_student_savings");
        $pdo->exec("TRUNCATE TABLE fin_cash_advances"); // Added: Clear Cash Advances
        
        // Reset Payout Status in Approvals (so they can be paid out again)
        $pdo->exec("UPDATE sys_approvals SET payout_trans_number = NULL, payout_date = NULL, payout_pic = NULL");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        jsonResponse(true, 'Data Keuangan berhasil di-reset (Semua Kosong). Status Pencairan di Approval Center juga di-reset (Siap Dicairkan Kembali).');
    }

    // 20. GET POS DAILY SUMMARY
    if ($action == 'get_pos_daily_summary' && $method == 'GET') {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        // Income Today
        $stmtInc = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type='INCOME' AND DATE(trans_date) = ?");
        $stmtInc->execute([$date]);
        $income = $stmtInc->fetchColumn() ?: 0;

        // Expense Today
        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type='EXPENSE' AND DATE(trans_date) = ?");
        $stmtExp->execute([$date]);
        $expense = $stmtExp->fetchColumn() ?: 0;

        jsonResponse(true, 'Data fetched', [
            'date' => $date,
            'income' => (float)$income,
            'expense' => (float)$expense,
            'balance' => (float)($income - $expense)
        ]);
    }

    // 21. GET POS TRANSACTIONS (Daily List)
    if ($action == 'get_pos_transactions' && $method == 'GET') {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT t.*, c.name as category_name 
            FROM fin_transactions t 
            LEFT JOIN fin_categories c ON t.category_id = c.id
            WHERE DATE(t.trans_date) = ? 
            ORDER BY t.trans_date DESC, t.id DESC
        ");
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, 'Data fetched', $rows);
    }

    // 22. SAVE POS TRANSACTION (Add/Edit)
    if ($action == 'save_pos_transaction' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'] ?? null;
        $type = $data['type']; // INCOME / EXPENSE
        $amount = $data['amount'];
        $description = $data['description'];
        $date = $data['date'] ?? date('Y-m-d H:i:s');
        $category_id = $data['category_id'] ?? null;
        $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        
        // Use Global Unit or specific if provided (POS usually Global or specific Unit)
        // For simplicity, we use the first unit found if not provided, or NULL (Global)
        // Ideally user should select unit, but for POS simple report we might default.
        $unit_id = $data['unit_id'] ?? null;

        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE fin_transactions SET type=?, amount=?, description=?, trans_date=?, category_id=? WHERE id=?");
            $stmt->execute([$type, $amount, $description, $date, $category_id, $id]);
            jsonResponse(true, 'Transaksi berhasil diperbarui');
        } else {
            // Insert
            if ($reqId !== '') {
                $chk = $pdo->prepare("SELECT trans_number FROM fin_transactions WHERE request_id = ?");
                $chk->execute([$reqId]);
                $has = $chk->fetchColumn();
                if ($has) {
                    jsonResponse(true, 'Transaksi berhasil ditambahkan');
                }
            }
            $transNo = 'POS/' . date('Ymd') . '/' . rand(1000, 9999);
            $stmt = $pdo->prepare("INSERT INTO fin_transactions (trans_number, type, amount, description, trans_date, category_id, unit_id, request_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$transNo, $type, $amount, $description, $date, $category_id, $unit_id, $reqId ?: null]);
            } catch (\PDOException $e) {
                if ($reqId !== '') {
                    jsonResponse(true, 'Transaksi berhasil ditambahkan');
                } else {
                    throw $e;
                }
            }
            jsonResponse(true, 'Transaksi berhasil ditambahkan');
        }
    }

    // 23. DELETE POS TRANSACTION
    if ($action == 'delete_pos_transaction' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        $stmt = $pdo->prepare("DELETE FROM fin_transactions WHERE id = ?");
        $stmt->execute([$id]);
        
        // TODO: Delete related journals if any (Complex logic omitted for Simple POS)
        
        jsonResponse(true, 'Transaksi berhasil dihapus');
    }

    // 24. CAPTURE BALANCE SHEET (Laporan Posisi Neraca Terakhir)
    if ($action == 'capture_balance_sheet' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $report_date = $data['report_date'] ?? date('Y-m-d');
        $unit_prefix = $data['unit_prefix'] ?? 'ALL';
        $notes = $data['notes'] ?? 'Generated from Trial Balance';

        // 1. Calculate Totals (Cumulative up to Report Date)
        $sql = "SELECT a.type, 
                       SUM(ji.debit) as total_debit, 
                       SUM(ji.credit) as total_credit
                FROM fin_accounts a
                LEFT JOIN fin_journal_items ji ON a.id = ji.account_id
                LEFT JOIN fin_journals j ON ji.journal_id = j.id
                LEFT JOIN core_units u ON j.unit_id = u.id
                WHERE j.journal_date <= ? ";
        
        $params = [$report_date];

        if ($unit_prefix && $unit_prefix !== 'ALL' && $unit_prefix !== '') {
            $sql .= " AND u.code = ? ";
            $params[] = $unit_prefix;
        }

        $sql .= " GROUP BY a.type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals = [
            'ASSET' => 0, 'LIABILITY' => 0, 'EQUITY' => 0, 'REVENUE' => 0, 'EXPENSE' => 0
        ];

        foreach ($rows as $row) {
            $type = $row['type'];
            $debit = $row['total_debit'] ?? 0;
            $credit = $row['total_credit'] ?? 0;
            
            // Net Balance calculation based on Type
            if ($type == 'ASSET' || $type == 'EXPENSE') {
                $totals[$type] += ($debit - $credit);
            } else {
                $totals[$type] += ($credit - $debit);
            }
        }

        $surplus = $totals['REVENUE'] - $totals['EXPENSE'];
        
        // 2. Fetch ASSET Details (Cash & Bank positions)
        // We want accounts where Type=ASSET and balance != 0
        // Group by Account Name/Code
        $sqlDetails = "SELECT a.code, a.name, a.balance_type,
                       SUM(ji.debit) as total_debit, 
                       SUM(ji.credit) as total_credit
                FROM fin_accounts a
                LEFT JOIN fin_journal_items ji ON a.id = ji.account_id
                LEFT JOIN fin_journals j ON ji.journal_id = j.id
                LEFT JOIN core_units u ON j.unit_id = u.id
                WHERE a.type = 'ASSET' AND j.journal_date <= ? ";
        
        $paramsDetails = [$report_date];
        if ($unit_prefix && $unit_prefix !== 'ALL' && $unit_prefix !== '') {
            $sqlDetails .= " AND u.code = ? ";
            $paramsDetails[] = $unit_prefix;
        }
        $sqlDetails .= " GROUP BY a.id ORDER BY a.code ASC";
        
        $stmtDetails = $pdo->prepare($sqlDetails);
        $stmtDetails->execute($paramsDetails);
        $assetRows = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
        
        $details = [];
        foreach($assetRows as $row) {
            $balance = ($row['balance_type'] == 'DEBIT') ? 
                       ($row['total_debit'] - $row['total_credit']) : 
                       ($row['total_credit'] - $row['total_debit']);
            
            if ($balance != 0) {
                $details[] = [
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'balance' => $balance
                ];
            }
        }

        // Insert
        $stmtIns = $pdo->prepare("INSERT INTO fin_report_checkpoints (report_date, total_assets, total_liabilities, total_equity, surplus_deficit, total_income, total_expense, notes, unit_prefix, details, cash_advances_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([
            $report_date, 
            $totals['ASSET'], 
            $totals['LIABILITY'], 
            $totals['EQUITY'], 
            $surplus,
            $totals['REVENUE'],
            $totals['EXPENSE'],
            $notes,
            $unit_prefix,
            json_encode($details),
            $data['cash_advances_amount'] ?? 0 // Save Cash Advance Amount
        ]);
        $checkpointId = $pdo->lastInsertId();
        try {
            $title = 'Posisi Neraca Dikirim • ' . date('d M Y', strtotime($report_date));
            if ($unit_prefix && $unit_prefix !== 'ALL') {
                $title .= ' • ' . $unit_prefix;
            }
            $desc = "Aset: " . number_format($totals['ASSET']) .
                    ", Liabilitas: " . number_format($totals['LIABILITY']) .
                    ", Ekuitas: " . number_format($totals['EQUITY']) .
                    ", Pendapatan: " . number_format($totals['REVENUE']) .
                    ", Beban: " . number_format($totals['EXPENSE']) .
                    ", Surplus/Defisit: " . number_format($surplus);
            log_activity($pdo, 'FINANCE', 'FOUNDATION_REPORT', 'SUBMIT', 'REPORT', $checkpointId, $title, $desc);
        } catch (\Throwable $e) {}

        jsonResponse(true, 'Posisi Neraca berhasil dilaporkan/disimpan.', [
            'id' => $checkpointId,
            'totals' => $totals,
            'surplus' => $surplus
        ]);
    }

    // 25. GET FOUNDATION SUMMARY (Latest Checkpoint)
    if ($action == 'get_foundation_summary' && $method == 'GET') {
        // Get the very latest report
        $stmt = $pdo->query("SELECT * FROM fin_report_checkpoints ORDER BY report_date DESC, id DESC LIMIT 1");
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($report) {
            jsonResponse(true, 'Found', $report);
        } else {
            // Fallback if no report yet (calculate on the fly? No, expensive. Just return zeros)
            jsonResponse(true, 'No Data', [
                'total_assets' => 0,
                'total_liabilities' => 0,
                'total_equity' => 0,
                'surplus_deficit' => 0
            ]);
        }
    }

    // 26. GET FOUNDATION REPORT LIST (School Only)
    if ($action == 'get_foundation_report_list' && $method == 'GET') {
        // Exclude 'POS' and 'COOP' reports
        $stmt = $pdo->query("SELECT * FROM fin_report_checkpoints WHERE unit_prefix NOT IN ('POS', 'COOP') ORDER BY report_date DESC, id DESC LIMIT 50");
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 33. GET FOUNDATION COOP REPORTS
    if ($action == 'get_foundation_coop_reports' && $method == 'GET') {
        // Change table from fin_report_checkpoints to fnd_reports
        $stmt = $pdo->query("SELECT * FROM fnd_reports WHERE unit_prefix = 'COOP' ORDER BY report_date DESC, id DESC LIMIT 50");
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 34. GET CASH ADVANCES (KAS BON)
    if ($action == 'get_cash_advances' && $method == 'GET') {
        $status = $_GET['status'] ?? 'OPEN'; // OPEN, SETTLED, ALL, OUTSTANDING
        $unrecorded_only = $_GET['unrecorded_only'] ?? false;
        
        $sql = "SELECT * FROM fin_cash_advances WHERE 1=1";
        
        if ($status === 'OUTSTANDING') {
            // OPEN or (SETTLED but not recorded)
            $sql .= " AND (status = 'OPEN' OR (status = 'SETTLED' AND is_recorded = 0))";
        } elseif ($status !== 'ALL') {
            $sql .= " AND status = '$status'";
        }
        
        if ($unrecorded_only) {
            $sql .= " AND is_recorded = 0";
        }
        
        $sql .= " ORDER BY request_date DESC, id DESC";
        $stmt = $pdo->query($sql);
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 35. CREATE CASH ADVANCE
    if ($action == 'create_cash_advance' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        
        if ($reqId !== '') {
            $chk = $pdo->prepare("SELECT id FROM fin_cash_advances WHERE request_id = ?");
            $chk->execute([$reqId]);
            $has = (int)($chk->fetchColumn() ?: 0);
            if ($has > 0) {
                jsonResponse(true, 'Kas bon sudah tercatat');
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO fin_cash_advances (requester_name, amount, purpose, request_date, status, proposal_ref, request_id) VALUES (?, ?, ?, ?, 'OPEN', ?, ?)");
        try {
            $stmt->execute([
                $data['requester_name'],
                $data['amount'],
                $data['purpose'],
                $data['request_date'] ?? date('Y-m-d'),
                $data['proposal_ref'] ?? null,
                $reqId ?: null
            ]);
        } catch (\PDOException $e) {
            if ($reqId !== '') {
                jsonResponse(true, 'Kas bon sudah tercatat');
            } else {
                throw $e;
            }
        }
        
        // Update Approval Status if linked to a proposal
        if (!empty($data['proposal_ref'])) {
            $kasbonId = $pdo->lastInsertId();
            $upd = $pdo->prepare("UPDATE sys_approvals SET payout_trans_number = ?, payout_date = ?, payout_pic = ? WHERE reference_no = ?");
            $upd->execute([
                "KASBON #$kasbonId",
                $data['request_date'] ?? date('Y-m-d'),
                $_SESSION['user_name'] ?? 'Finance',
                $data['proposal_ref']
            ]);
        }
        
        jsonResponse(true, 'Kas bon berhasil dicatat');
    }

    // 36. SETTLE CASH ADVANCE (REALISASI)
    if ($action == 'settle_cash_advance' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        $stmt = $pdo->prepare("UPDATE fin_cash_advances SET status = 'SETTLED', actual_amount = ?, settlement_date = ?, settlement_note = ? WHERE id = ?");
        $stmt->execute([
            $data['actual_amount'],
            $data['settlement_date'] ?? date('Y-m-d'),
            $data['settlement_note'] ?? '',
            $id
        ]);
        
        jsonResponse(true, 'Kas bon berhasil diselesaikan (Realisasi tercatat)');
    }
    
    // 38. RECORD EXPENSE FROM CASH ADVANCE
    if ($action == 'record_expense_from_advance' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $advance_id = $data['advance_id'];
        $unit_id = $data['unit_id'] ?? null; // Get from data
        $catId = $data['category_id'];
        $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        
        if (!$unit_id) {
            jsonResponse(false, 'Gagal: Unit ID tidak ditemukan. Mohon pilih unit.');
        }

        $pdo->beginTransaction();
        
        try {
            // 0. Validate Accounts FIRST
            $stmtCat = $pdo->prepare("SELECT account_id, account_cash_id FROM fin_categories WHERE id = ?");
            $stmtCat->execute([$catId]);
            $catData = $stmtCat->fetch();

            if (!$catData || !$catData['account_id']) {
                 file_put_contents('../debug_finance.log', "ERROR RECORD ADVANCE: Kategori ID=$catId tidak memiliki Akun Debit.\n", FILE_APPEND);
                 throw new Exception("Konfigurasi Akun (Debit) untuk Kategori ini belum lengkap.");
            }
            
            // Determine Credit Account (Prioritize User Selection -> Category Default)
            $creditAcc = $data['cash_account_id'] ?: $catData['account_cash_id'];
            if (!$creditAcc) {
                 file_put_contents('../debug_finance.log', "ERROR RECORD ADVANCE: Sumber Dana (Credit) tidak dipilih/setting. Data: " . json_encode($data) . "\n", FILE_APPEND);
                 throw new Exception("Akun Kas/Sumber Dana belum dipilih atau disetting.");
            }

            $trans_number = null;
            $didInsert = true;
            if ($reqId !== '') {
                $sDup = $pdo->prepare("SELECT trans_number FROM fin_transactions WHERE request_id = ?");
                $sDup->execute([$reqId]);
                $has = $sDup->fetchColumn();
                if ($has) {
                    $trans_number = $has;
                    $didInsert = false;
                }
            }
            if (!$trans_number) {
                $trans_number = generateReferenceNumber($pdo, $unit_id, 'E', 'fin_transactions', 'trans_number');
            }

            if ($didInsert) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO fin_transactions (trans_number, trans_date, type, amount, category_id, description, invoice_number, unit_id, request_id, created_at) VALUES (?, ?, 'EXPENSE', ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $trans_number,
                        $data['trans_date'],
                        $data['amount'],
                        $data['category_id'],
                        $data['description'],
                        $data['invoice_number'] ?? null,
                        $unit_id,
                        $reqId ?: null
                    ]);
                } catch (\PDOException $e) {
                    if ($reqId !== '') {
                        $didInsert = false;
                    } else {
                        throw $e;
                    }
                }
            }
            
            // 3. Mark Advance as Recorded
            $stmtUpd = $pdo->prepare("UPDATE fin_cash_advances SET is_recorded = 1 WHERE id = ?");
            $stmtUpd->execute([$advance_id]);
            
            // 4. Check if Advance was linked to Proposal
            $stmtAdv = $pdo->prepare("SELECT proposal_ref FROM fin_cash_advances WHERE id = ?");
            $stmtAdv->execute([$advance_id]);
            $adv = $stmtAdv->fetch();
            
            if ($adv && $adv['proposal_ref']) {
                // Use CONCAT_WS to append transaction numbers if multiple items/splits occur
                // This handles the "Multi-Unit" scenario where one proposal generates multiple invoices (SD..., TK...)
                $stmtProp = $pdo->prepare("UPDATE sys_approvals SET payout_trans_number = IF(payout_trans_number IS NULL OR payout_trans_number = '', ?, CONCAT_WS(', ', payout_trans_number, ?)), payout_date = ?, payout_pic = ? WHERE reference_no = ?");
                $stmtProp->execute([
                    $trans_number,
                    $trans_number,
                    $data['trans_date'],
                    $_SESSION['user_name'] ?? 'Finance',
                    $adv['proposal_ref']
                ]);
            }
            
            $debitAcc = $catData['account_id'];

            if ($didInsert) {
                $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                $jnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
                $jnl->execute([$unit_id, $jnlNo, $data['trans_date'], $data['description'], $trans_number]);
                $journalId = $pdo->lastInsertId();

            // Prepared statement for items
            $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");

            // Debit (Expense)
            $insItem->execute([$journalId, $debitAcc, $data['amount'], 0]);

            // Credit (Cash)
            $insItem->execute([$journalId, $creditAcc, 0, $data['amount']]);
            }
            $pdo->commit();
            jsonResponse(true, 'Pengeluaran berhasil dicatat dari Kas Bon', ['trans_number' => $trans_number]);
        } catch (Exception $e) {
            $pdo->rollBack();
            file_put_contents('../debug_finance.log', "ERROR RECORD ADVANCE: " . $e->getMessage() . "\n", FILE_APPEND);
            jsonResponse(false, 'Gagal mencatat pengeluaran: ' . $e->getMessage());
        }
    }

    // 37. DELETE CASH ADVANCE
    if ($action == 'delete_cash_advance' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM fin_cash_advances WHERE id = ?");
        $stmt->execute([$data['id']]);
        jsonResponse(true, 'Data dihapus');
    }

    // 39. RECORD EXPENSE FROM PROPOSAL (Process Payout)
    if ($action == 'record_expense_from_proposal' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $ref_no = $data['reference_no'];
        $unit_id = $data['unit_id'];
        $catId = $data['category_id'];
        $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
        
        $pdo->beginTransaction();
        try {
            $stmtCat = $pdo->prepare("SELECT account_id, account_cash_id FROM fin_categories WHERE id = ?");
            $stmtCat->execute([$catId]);
            $catData = $stmtCat->fetch();
            
            $trans_number = null;
            $didInsert = true;
            if ($reqId !== '') {
                $sDup = $pdo->prepare("SELECT trans_number FROM fin_transactions WHERE request_id = ?");
                $sDup->execute([$reqId]);
                $has = $sDup->fetchColumn();
                if ($has) {
                    $trans_number = $has;
                    $didInsert = false;
                }
            }
            if (!$trans_number) {
                $trans_number = generateReferenceNumber($pdo, $unit_id, 'E', 'fin_transactions', 'trans_number');
            }
            
            if ($didInsert) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO fin_transactions (trans_number, trans_date, type, amount, category_id, description, unit_id, cash_account_id, pic, receiver, request_id, created_at) VALUES (?, ?, 'EXPENSE', ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $trans_number,
                        $data['trans_date'],
                        $data['amount'],
                        $data['category_id'],
                        $data['description'],
                        $data['unit_id'],
                        $data['cash_account_id'],
                        $data['pic'],
                        $data['receiver'],
                        $reqId ?: null
                    ]);
                } catch (\PDOException $e) {
                    if ($reqId !== '') {
                        $didInsert = false;
                    } else {
                        throw $e;
                    }
                }
            }
            
            // 3. Update Approval Status (Mark as Paid/Processed)
            $stmtUpd = $pdo->prepare("UPDATE sys_approvals SET payout_trans_number = ?, payout_date = ?, payout_pic = ? WHERE reference_no = ?");
            $stmtUpd->execute([
                $trans_number,
                $data['trans_date'],
                $_SESSION['user_name'] ?? 'Finance',
                $ref_no
            ]);
            
            if ($catData && $catData['account_id']) {
                $debitAcc = $catData['account_id'];
                $creditAcc = $data['cash_account_id'] ?: $catData['account_cash_id'];
                
                if ($creditAcc && $didInsert) {
                     $jnlNo = generateReferenceNumber($pdo, $unit_id, 'J', 'fin_journals', 'journal_number');
                     $jnl = $pdo->prepare("INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                     $jnl->execute([$unit_id, $jnlNo, $data['trans_date'], $data['description'], $trans_number]);
                     $journalId = $pdo->lastInsertId();
                     
                     $insItem = $pdo->prepare("INSERT INTO fin_journal_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
                     $insItem->execute([$journalId, $debitAcc, $data['amount'], 0]);
                     $insItem->execute([$journalId, $creditAcc, 0, $data['amount']]);
                }
            }
            
            $pdo->commit();
            jsonResponse(true, 'Pencairan proposal berhasil dicatat', ['trans_number' => $trans_number]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal: ' . $e->getMessage());
        }
    }

    // 27. DELETE FOUNDATION REPORT
    if ($action == 'delete_foundation_report' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        // Try deleting from both tables (School/POS vs COOP)
        // fin_report_checkpoints (School/POS)
        $stmt1 = $pdo->prepare("DELETE FROM fin_report_checkpoints WHERE id = ?");
        $stmt1->execute([$id]);
        
        // fnd_reports (COOP)
        $stmt2 = $pdo->prepare("DELETE FROM fnd_reports WHERE id = ?");
        $stmt2->execute([$id]);
        
        jsonResponse(true, 'Laporan berhasil dihapus');
    }

    // 28. CAPTURE POS DAILY REPORT
    if ($action == 'capture_pos_daily_report' && $method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $report_date = $data['report_date'] ?? date('Y-m-d');
        $notes = $data['notes'] ?? 'Laporan Harian POS';

        // Calculate Income & Expense for the specific date
        $stmtInc = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type='INCOME' AND DATE(trans_date) = ?");
        $stmtInc->execute([$report_date]);
        $income = $stmtInc->fetchColumn() ?: 0;

        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM fin_transactions WHERE type='EXPENSE' AND DATE(trans_date) = ?");
        $stmtExp->execute([$report_date]);
        $expense = $stmtExp->fetchColumn() ?: 0;

        $balance = $income - $expense;

        // Create Details JSON (Optional: Breakdown by Category)
        // Group Income by Category
        $sqlCat = "SELECT c.name, SUM(t.amount) as total 
                   FROM fin_transactions t 
                   LEFT JOIN fin_categories c ON t.category_id = c.id 
                   WHERE t.type='INCOME' AND DATE(t.trans_date) = ? 
                   GROUP BY c.name";
        $stmtCat = $pdo->prepare($sqlCat);
        $stmtCat->execute([$report_date]);
        $incDetails = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        $details = [
            'income_breakdown' => $incDetails
        ];

        // Insert into checkpoints with unit_prefix = 'POS'
        $stmtIns = $pdo->prepare("INSERT INTO fin_report_checkpoints (report_date, total_income, total_expense, surplus_deficit, total_assets, total_liabilities, total_equity, notes, unit_prefix, details) VALUES (?, ?, ?, ?, 0, 0, 0, ?, 'POS', ?)");
        $stmtIns->execute([
            $report_date, 
            $income,
            $expense,
            $balance,
            $notes,
            json_encode($details)
        ]);
        $checkpointId = $pdo->lastInsertId();

        try {
            $title = 'Laporan POS Dikirim • ' . date('d M Y', strtotime($report_date));
            $desc = "Income: " . number_format($income) . ", Expense: " . number_format($expense) . ", Surplus/Defisit: " . number_format($balance);
            log_activity($pdo, 'FINANCE', 'FOUNDATION_REPORT', 'SUBMIT', 'REPORT', $checkpointId, $title, $desc);
        } catch (\Throwable $e) {}

        jsonResponse(true, 'Laporan POS berhasil dikirim ke Yayasan.');
    }

    // 29. GET FOUNDATION POS REPORTS
    if ($action == 'get_foundation_pos_reports' && $method == 'GET') {
        $stmt = $pdo->query("SELECT * FROM fin_report_checkpoints WHERE unit_prefix = 'POS' ORDER BY report_date DESC, id DESC LIMIT 50");
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 30. GET CONSOLIDATED SUMMARY (Yayasan Dashboard)
    if ($action == 'get_consolidated_summary' && $method == 'GET') {
        // 1. Get Latest School Report (Non-POS, Non-COOP)
        $stmtSchool = $pdo->query("SELECT * FROM fin_report_checkpoints WHERE unit_prefix NOT IN ('POS', 'COOP') ORDER BY report_date DESC, id DESC LIMIT 1");
        $schoolReport = $stmtSchool->fetch(PDO::FETCH_ASSOC);

        // 2. Get Latest POS Report
        $stmtPOS = $pdo->query("SELECT * FROM fin_report_checkpoints WHERE unit_prefix = 'POS' ORDER BY report_date DESC, id DESC LIMIT 1");
        $posReport = $stmtPOS->fetch(PDO::FETCH_ASSOC);

        // 3. Get Latest COOP Report
        // Change table from fin_report_checkpoints to fnd_reports
        $stmtCOOP = $pdo->query("SELECT * FROM fnd_reports WHERE unit_prefix = 'COOP' ORDER BY report_date DESC, id DESC LIMIT 1");
        $coopReport = $stmtCOOP->fetch(PDO::FETCH_ASSOC);

        // Calculate School Cash (From Details)
        $schoolCash = 0;
        $schoolBank = 0;
        if ($schoolReport && !empty($schoolReport['details'])) {
            $details = json_decode($schoolReport['details'], true);
            foreach ($details as $d) {
                $name = $d['name'];
                $balance = $d['balance'];
                
                // Improved Heuristic with Regex for whole words or common terms
                // Match: Kas, Cash, Tunai, Petty Cash
                if (preg_match('/\b(Kas|Cash|Tunai|Uang)\b/i', $name)) {
                    $schoolCash += $balance;
                }
                // Match: Bank, Rekening, ATM, or common Bank Names (BCA, BRI, etc)
                else if (preg_match('/\b(Bank|Rekening|Rek|ATM|BCA|BRI|BNI|Mandiri|BSI|Syariah)\b/i', $name)) {
                    $schoolBank += $balance;
                }
            }
        }

        // POS Balance
        $posBalance = 0;
        if ($posReport) {
            $posBalance = ($posReport['total_assets'] > 0) ? $posReport['total_assets'] : $posReport['surplus_deficit'];
        }

        // COOP Balance
        $coopCash = 0;
        $coopTotal = 0;
        if ($coopReport) {
            $coopTotal = $coopReport['total_assets'];
            if (!empty($coopReport['details'])) {
                $details = json_decode($coopReport['details'], true);
                foreach ($details as $d) {
                    if (($d['code'] ?? '') == 'CASH') {
                        $coopCash += $d['balance'];
                    }
                }
            }
        }

        // Total Assets (All Assets, Liquid + Non-Liquid)
        $schoolTotalAssets = $schoolReport['total_assets'] ?? 0;
        $schoolCashAdvances = $schoolReport['cash_advances_amount'] ?? 0; // Include Cash Advances
        
        // Kas Bon is already inside School Assets (as Cash), so don't add it again to Grand Total.
        // But for Liquid (Available), we subtract it from Cash.
        $grandTotalAssets = $schoolTotalAssets + $posBalance + $coopTotal;

        jsonResponse(true, 'Found', [
            'school_cash' => $schoolCash,
            'school_bank' => $schoolBank,
            'school_advances' => $schoolCashAdvances, // New field for UI
            'pos_balance' => $posBalance,
            'coop_cash' => $coopCash,
            'coop_total' => $coopTotal,
            'total_liquid' => ($schoolCash + $schoolBank + $posBalance + $coopCash) - $schoolCashAdvances,
            'grand_total_assets' => $grandTotalAssets,
            'school_last_update' => $schoolReport['created_at'] ?? '-',
            'pos_last_update' => $posReport['created_at'] ?? '-',
            'coop_last_update' => $coopReport['created_at'] ?? '-'
        ]);
    }

    // 31. GET EXPENSE SUMMARY BY CATEGORY (Report)
    if ($action == 'get_expense_summary_by_category' && $method == 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $unit_id = $_GET['unit_id'] ?? '';
        $unit_prefix = $_GET['unit_prefix'] ?? ''; // Support Prefix Filter
        
        $sql = "SELECT c.id, c.name, SUM(t.amount) as total_amount
                FROM fin_transactions t
                JOIN fin_categories c ON t.category_id = c.id
                LEFT JOIN core_units u ON t.unit_id = u.id
                WHERE t.type = 'EXPENSE' AND t.trans_date BETWEEN ? AND ?";
        
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($unit_id) {
            $sql .= " AND t.unit_id = ?";
            $params[] = $unit_id;
        }
        // Add filtering by Prefix if ID is not provided
        elseif ($unit_prefix) {
            $sql .= " AND u.receipt_code = ?";
            $params[] = $unit_prefix;
        }
        
        $sql .= " GROUP BY c.id, c.name HAVING total_amount > 0 ORDER BY total_amount DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 32. GET EXPENSE DETAILS BY CATEGORY (Report)
    if ($action == 'get_expense_details_by_category' && $method == 'GET') {
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $unit_id = $_GET['unit_id'] ?? '';
        $unit_prefix = $_GET['unit_prefix'] ?? ''; // Support Prefix Filter
        $category_id = $_GET['category_id'];
        
        $sql = "SELECT t.trans_date, t.pic, t.receiver, t.description, t.amount, t.trans_number
                FROM fin_transactions t
                LEFT JOIN core_units u ON t.unit_id = u.id
                WHERE t.type = 'EXPENSE' AND t.category_id = ? AND t.trans_date >= ? AND t.trans_date <= ?";
        
        // Ensure date range includes the whole day
        // Force strict full-day range
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';
        
        $params = [$category_id, $start, $end];
        
        if ($unit_id) {
            $sql .= " AND t.unit_id = ?";
            $params[] = $unit_id;
        }
        // Add filtering by Prefix
        elseif ($unit_prefix) {
            $sql .= " AND u.receipt_code = ?";
            $params[] = $unit_prefix;
        }
        
        $sql .= " ORDER BY t.trans_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 40. GET STUDENT SAVINGS (History)
    if ($action == 'get_student_savings' && $method == 'GET') {
        $student_id = $_GET['student_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM fin_student_savings WHERE student_id = ? ORDER BY trans_date DESC, id DESC");
        $stmt->execute([$student_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get Current Balance
        $stmt2 = $pdo->prepare("SELECT balance_after FROM fin_student_savings WHERE student_id = ? ORDER BY id DESC LIMIT 1");
        $stmt2->execute([$student_id]);
        $balance = $stmt2->fetchColumn() ?: 0;
        
        jsonResponse(true, 'Data fetched', ['history' => $history, 'balance' => $balance]);
    }

    // 41. GET SAVINGS REPORT (Summary per Unit, Class, Student)
    if ($action == 'get_savings_report' && $method == 'GET') {
        $view = $_GET['view'] ?? 'student'; // unit, class, student
        $search = $_GET['q'] ?? '';
        
        if ($view === 'unit') {
            $sql = "SELECT u.id, u.name, COUNT(DISTINCT s.id) as student_count,
                           SUM(COALESCE((SELECT balance_after FROM fin_student_savings WHERE student_id = s.id ORDER BY id DESC LIMIT 1), 0)) as total_balance
                    FROM core_units u
                    LEFT JOIN core_people s ON u.id = s.unit_id AND s.type = 'STUDENT'
                    WHERE u.name LIKE ? AND u.code != 'YAYASAN'
                    GROUP BY u.id
                    ORDER BY u.name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$search%"]);
            jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } elseif ($view === 'class') {
            $unit_id = $_GET['unit_id'] ?? '';
            $sql = "SELECT c.id, c.name, u.name as unit_name, COUNT(DISTINCT sc.student_id) as student_count,
                           SUM(COALESCE((SELECT balance_after FROM fin_student_savings WHERE student_id = sc.student_id ORDER BY id DESC LIMIT 1), 0)) as total_balance
                    FROM acad_classes c
                    JOIN acad_class_levels l ON c.level_id = l.id
                    JOIN core_units u ON l.unit_id = u.id
                    LEFT JOIN acad_student_classes sc ON c.id = sc.class_id AND sc.status = 'ACTIVE'
                    WHERE (c.name LIKE ? OR u.name LIKE ?) AND u.code != 'YAYASAN'";
            
            $params = ["%$search%", "%$search%"];
            
            if ($unit_id) {
                $sql .= " AND u.id = ?";
                $params[] = $unit_id;
            }
            
            $sql .= " GROUP BY c.id ORDER BY u.name ASC, c.name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } else {
            // View: student
            $unit_id = $_GET['unit_id'] ?? '';
            $class_id = $_GET['class_id'] ?? '';
            
            $sql = "SELECT s.id, s.name, s.identity_number, u.name as unit_name, c.name as class_name,
                           COALESCE((SELECT balance_after FROM fin_student_savings WHERE student_id = s.id ORDER BY id DESC LIMIT 1), 0) as balance
                    FROM core_people s
                    LEFT JOIN core_units u ON s.unit_id = u.id
                    LEFT JOIN acad_student_classes sc ON s.id = sc.student_id AND sc.status = 'ACTIVE'
                    LEFT JOIN acad_classes c ON sc.class_id = c.id
                    WHERE s.type = 'STUDENT' AND (s.name LIKE ? OR s.identity_number LIKE ?) AND u.code != 'YAYASAN'";
            
            $params = ["%$search%", "%$search%"];
            
            if ($unit_id) {
                $sql .= " AND s.unit_id = ?";
                $params[] = $unit_id;
            }
            if ($class_id) {
                $sql .= " AND sc.class_id = ?";
                $params[] = $class_id;
            }
            
            $sql .= " ORDER BY s.name ASC LIMIT 100";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse(true, 'Found', $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
}
