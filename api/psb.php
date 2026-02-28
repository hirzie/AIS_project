<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function getActiveAcademicYearId($pdo) {
    try {
        $stmt = $pdo->query("SELECT id FROM core_academic_years WHERE is_active = 1 LIMIT 1");
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
    } catch (\Throwable $e) {}
    try {
        $stmt = $pdo->query("SELECT id FROM core_academic_years ORDER BY start_date DESC, id DESC LIMIT 1");
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
    } catch (\Throwable $e) {}
    return null;
}

function getYearSuffix($pdo, $yearId) {
    try {
        $stmt = $pdo->prepare("SELECT name, start_date, end_date FROM core_academic_years WHERE id = ?");
        $stmt->execute([$yearId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $name = trim((string)($row['name'] ?? ''));
            if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', $name, $m)) {
                $yy = intval($m[2]);
                return substr((string)$yy, -2);
            }
            $end = trim((string)($row['end_date'] ?? ''));
            if ($end !== '') {
                $yy = intval(substr($end, 0, 4));
                return substr((string)$yy, -2);
            }
            $start = trim((string)($row['start_date'] ?? ''));
            if ($start !== '') {
                $yy = intval(substr($start, 0, 4));
                return substr((string)$yy, -2);
            }
        }
    } catch (\Throwable $e) {}
    return date('y');
}

function generateRegNumber($pdo, $unitId, $unitCode, $yearId, $seqLenDefault = 5) {
    $stmt = $pdo->prepare("SELECT prefix, seq_length FROM psb_numbering_prefixes WHERE unit_id = ? AND academic_year_id = ? LIMIT 1");
    $stmt->execute([$unitId, $yearId]);
    $conf = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($conf) {
        $prefix = trim((string)$conf['prefix']);
        $seqLen = max(1, (int)($conf['seq_length'] ?? $seqLenDefault));
    } else {
        $prefix = strtoupper(trim((string)$unitCode));
        if ($prefix === '') $prefix = 'UN';
        $yy = getYearSuffix($pdo, $yearId);
        $prefix = $prefix . $yy;
        $seqLen = $seqLenDefault;
    }
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM psb_applicants WHERE unit_id = ? AND academic_year_id = ?");
    $countStmt->execute([$unitId, $yearId]);
    $seq = intval($countStmt->fetchColumn()) + 1;
    $maxTries = 20;
    while ($maxTries-- > 0) {
        $reg = $prefix . ' ' . str_pad((string)$seq, $seqLen, '0', STR_PAD_LEFT);
        $check = $pdo->prepare("SELECT id FROM psb_applicants WHERE reg_number = ? LIMIT 1");
        $check->execute([$reg]);
        if (!$check->fetchColumn()) return $reg;
        $seq++;
    }
    return $prefix . ' ' . str_pad((string)$seq, $seqLen, '0', STR_PAD_LEFT);
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS psb_unit_quotas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unit_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            quota INT NOT NULL DEFAULT 0,
            updated_by INT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_unit_year (unit_id, academic_year_id),
            FOREIGN KEY (unit_id) REFERENCES core_units(id),
            FOREIGN KEY (academic_year_id) REFERENCES core_academic_years(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS psb_applicants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unit_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            request_id VARCHAR(150) NOT NULL,
            reg_number VARCHAR(20) DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            gender ENUM('L','P') DEFAULT 'L',
            birth_date DATE DEFAULT NULL,
            guardian_name VARCHAR(150) DEFAULT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            email VARCHAR(100) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            completeness JSON DEFAULT NULL,
            status ENUM('REGISTERED','ACCEPTED','REJECTED') DEFAULT 'REGISTERED',
            souvenir_received TINYINT(1) DEFAULT 0,
            souvenir_received_at DATETIME DEFAULT NULL,
            program_id INT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_request (request_id),
            UNIQUE KEY uniq_reg_number (reg_number),
            INDEX idx_unit_year (unit_id, academic_year_id),
            FOREIGN KEY (unit_id) REFERENCES core_units(id),
            FOREIGN KEY (academic_year_id) REFERENCES core_academic_years(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    try { $pdo->exec("ALTER TABLE psb_applicants ADD COLUMN reg_number VARCHAR(20) DEFAULT NULL"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE psb_applicants ADD UNIQUE KEY uniq_reg_number (reg_number)"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE psb_applicants ADD COLUMN souvenir_received TINYINT(1) DEFAULT 0"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE psb_applicants ADD COLUMN souvenir_received_at DATETIME DEFAULT NULL"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE psb_applicants ADD COLUMN program_id INT DEFAULT NULL"); } catch (\Throwable $e) {}
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS psb_programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            academic_year_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            gender ENUM('L','P','ALL') DEFAULT 'ALL',
            created_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_year_name_gender (academic_year_id, name, gender),
            FOREIGN KEY (academic_year_id) REFERENCES core_academic_years(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS psb_program_quotas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            unit_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            quota INT NOT NULL DEFAULT 0,
            updated_by INT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_program_unit_year (program_id, unit_id, academic_year_id),
            FOREIGN KEY (program_id) REFERENCES psb_programs(id),
            FOREIGN KEY (unit_id) REFERENCES core_units(id),
            FOREIGN KEY (academic_year_id) REFERENCES core_academic_years(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS psb_numbering_prefixes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unit_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            prefix VARCHAR(30) NOT NULL,
            seq_length INT NOT NULL DEFAULT 4,
            updated_by INT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_unit_year_prefix (unit_id, academic_year_id),
            FOREIGN KEY (unit_id) REFERENCES core_units(id),
            FOREIGN KEY (academic_year_id) REFERENCES core_academic_years(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $userRole = strtoupper(trim($_SESSION['role'] ?? ''));
    $userId = $_SESSION['user_id'] ?? null;

    if ($action === 'list_quota' && $method === 'GET') {
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');

        $stmt = $pdo->query("SELECT id, code, COALESCE(receipt_code, code) AS prefix, name FROM core_units ORDER BY id ASC");
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($units as $u) {
            $code = $u['code'];
            if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
                if (!ais_is_unit_allowed($pdo, $code)) continue;
            }
            $qs = $pdo->prepare("SELECT quota FROM psb_unit_quotas WHERE unit_id = ? AND academic_year_id = ?");
            $qs->execute([$u['id'], $yearId]);
            $quota = (int)($qs->fetchColumn() ?: 0);

            $cs = $pdo->prepare("SELECT COUNT(*) FROM psb_applicants WHERE unit_id = ? AND academic_year_id = ? AND status = 'REGISTERED'");
            $cs->execute([$u['id'], $yearId]);
            $used = (int)$cs->fetchColumn();

            $rows[] = [
                'unit_id' => (int)$u['id'],
                'unit_code' => $u['code'],
                'unit_prefix' => $u['prefix'],
                'unit_name' => $u['name'],
                'quota' => $quota,
                'used' => $used,
                'remaining' => max(0, $quota - $used)
            ];
        }
        jsonResponse(true, 'Kuota diambil', $rows);
    }

    if ($action === 'save_quota' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN','ACADEMIC','ADMIN_UNIT'])) {
            jsonResponse(false, 'Tidak memiliki hak untuk menyetel kuota');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $items = $payload['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            jsonResponse(false, 'Data kuota kosong');
        }
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $ups = $pdo->prepare("INSERT INTO psb_unit_quotas (unit_id, academic_year_id, quota, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quota = VALUES(quota), updated_by = VALUES(updated_by)");
        $count = 0;
        foreach ($items as $it) {
            $unitId = (int)($it['unit_id'] ?? 0);
            $quota = max(0, (int)($it['quota'] ?? 0));
            if ($unitId <= 0) continue;
            $stmtUnit->execute([$unitId]);
            $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
            if (!$u) continue;
            $code = $u['code'];
            if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
                if (!ais_is_unit_allowed($pdo, $code)) continue;
            }
            $ups->execute([$unitId, $yearId, $quota, $userId]);
            $count++;
        }
        jsonResponse(true, 'Kuota disimpan', ['updated' => $count]);
    }

    if ($action === 'save_applicant' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $unitId = (int)($data['unit_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $gender = strtoupper(trim((string)($data['gender'] ?? 'L')));
        $birth_date = trim((string)($data['birth_date'] ?? ''));
        $guardian = trim((string)($data['guardian_name'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));
        $completeness = $data['completeness'] ?? [];
        $request_id = trim((string)($data['request_id'] ?? ''));
        $programId = (int)($data['program_id'] ?? 0);

        if ($unitId <= 0 || $name === '') {
            jsonResponse(false, 'unit_id dan nama wajib diisi');
        }
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $stmtUnit->execute([$unitId]);
        $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
        if (!$u) jsonResponse(false, 'Unit tidak valid');
        $code = $u['code'];
        if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            if (!ais_is_unit_allowed($pdo, $code)) {
                jsonResponse(false, 'Unit tidak diizinkan untuk role Anda');
            }
        }
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');

        // Enforce quota: Prefer program quota if program selected, else unit quota
        if ($programId > 0) {
            $stmtProg = $pdo->prepare("SELECT id FROM psb_programs WHERE id = ? AND academic_year_id = ?");
            $stmtProg->execute([$programId, $yearId]);
            $prog = $stmtProg->fetch(PDO::FETCH_ASSOC);
            if (!$prog) jsonResponse(false, 'Program penerimaan tidak ditemukan');
            // Check program quota
            $qs = $pdo->prepare("SELECT quota FROM psb_program_quotas WHERE program_id = ? AND unit_id = ? AND academic_year_id = ?");
            $qs->execute([$programId, $unitId, $yearId]);
            $quota = (int)($qs->fetchColumn() ?: 0);
            $cs = $pdo->prepare("SELECT COUNT(*) FROM psb_applicants WHERE program_id = ? AND unit_id = ? AND academic_year_id = ? AND status = 'REGISTERED'");
            $cs->execute([$programId, $unitId, $yearId]);
            $used = (int)$cs->fetchColumn();
            if ($quota > 0 && $used >= $quota) {
                jsonResponse(false, 'Kuota program sudah penuh');
            }
        } else {
            // Fallback to unit quota
            $qs = $pdo->prepare("SELECT quota FROM psb_unit_quotas WHERE unit_id = ? AND academic_year_id = ?");
            $qs->execute([$unitId, $yearId]);
            $quota = (int)($qs->fetchColumn() ?: 0);
            $cs = $pdo->prepare("SELECT COUNT(*) FROM psb_applicants WHERE unit_id = ? AND academic_year_id = ? AND status = 'REGISTERED'");
            $cs->execute([$unitId, $yearId]);
            $used = (int)$cs->fetchColumn();
            if ($quota > 0 && $used >= $quota) {
                jsonResponse(false, 'Kuota unit sudah penuh');
            }
        }

        if ($request_id === '') {
            $sig = strtolower(preg_replace('/\s+/', ' ', $name)) . '|' . ($birth_date ?: '-') . '|' . ($phone ?: '-');
            $request_id = 'PSB-' . $unitId . '-' . substr(sha1($sig), 0, 16);
        }
        $reg_number = generateRegNumber($pdo, $unitId, $code, $yearId, 5);

        try {
            $stmt = $pdo->prepare("INSERT INTO psb_applicants (unit_id, academic_year_id, request_id, reg_number, name, gender, birth_date, guardian_name, phone, email, address, completeness, status, program_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$unitId, $yearId, $request_id, $reg_number, $name, ($gender === 'P' ? 'P' : 'L'), ($birth_date ?: null), ($guardian ?: null), ($phone ?: null), ($email ?: null), ($address ?: null), json_encode($completeness), 'REGISTERED', ($programId ?: null), $userId]);
            jsonResponse(true, 'Pendaftar disimpan', ['request_id' => $request_id, 'reg_number' => $reg_number]);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'uniq_request') !== false) {
                $stmt = $pdo->prepare("SELECT reg_number FROM psb_applicants WHERE request_id = ? LIMIT 1");
                $stmt->execute([$request_id]);
                $existingReg = $stmt->fetchColumn();
                jsonResponse(true, 'Pendaftar sudah tercatat', ['request_id' => $request_id, 'reg_number' => $existingReg]);
            }
            jsonResponse(false, 'Gagal menyimpan pendaftar: ' . $e->getMessage());
        }
    }

    if ($action === 'list_programs' && $method === 'GET') {
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $unitId = (int)($_GET['unit_id'] ?? 0);
        $params = [$yearId];
        $sql = "SELECT p.id, p.name, p.gender FROM psb_programs p WHERE p.academic_year_id = ? ORDER BY p.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($unitId > 0) {
            $qs = $pdo->prepare("SELECT program_id, quota FROM psb_program_quotas WHERE unit_id = ? AND academic_year_id = ?");
            $qs->execute([$unitId, $yearId]);
            $qmap = [];
            foreach ($qs->fetchAll(PDO::FETCH_ASSOC) as $row) { $qmap[(int)$row['program_id']] = (int)$row['quota']; }
            $us = $pdo->prepare("SELECT program_id, COUNT(*) AS used FROM psb_applicants WHERE unit_id = ? AND academic_year_id = ? AND status = 'REGISTERED' GROUP BY program_id");
            $us->execute([$unitId, $yearId]);
            $umap = [];
            foreach ($us->fetchAll(PDO::FETCH_ASSOC) as $row) { $umap[(int)$row['program_id']] = (int)$row['used']; }
            foreach ($programs as &$p) {
                $pid = (int)$p['id'];
                $p['quota'] = $qmap[$pid] ?? 0;
                $p['used'] = $umap[$pid] ?? 0;
            }
        }
        jsonResponse(true, 'Program diambil', $programs);
    }

    if ($action === 'save_program' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN','ACADEMIC','ADMIN_UNIT'])) {
            jsonResponse(false, 'Tidak memiliki hak untuk menyetel program');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') jsonResponse(false, 'Nama program wajib diisi');
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        try {
            $stmt = $pdo->prepare("INSERT INTO psb_programs (academic_year_id, name, gender, created_by) VALUES (?,?,?,?)");
            $stmt->execute([$yearId, $name, 'ALL', $userId]);
            jsonResponse(true, 'Program disimpan', ['id' => (int)$pdo->lastInsertId(), 'name' => $name]);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'uniq_year_name_gender') !== false) {
                jsonResponse(false, 'Program dengan nama tersebut sudah ada untuk tahun aktif');
            }
            jsonResponse(false, 'Gagal menyimpan program: ' . $e->getMessage());
        }
    }

    if ($action === 'update_program' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN','ACADEMIC','ADMIN_UNIT'])) {
            jsonResponse(false, 'Tidak memiliki hak untuk mengubah program');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $programId = (int)($payload['program_id'] ?? 0);
        $name = trim((string)($payload['name'] ?? ''));
        if ($programId <= 0) jsonResponse(false, 'program_id wajib diisi');
        if ($name === '') jsonResponse(false, 'Nama program wajib diisi');
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $chk = $pdo->prepare("SELECT id FROM psb_programs WHERE id = ? AND academic_year_id = ? LIMIT 1");
        $chk->execute([$programId, $yearId]);
        if (!$chk->fetchColumn()) jsonResponse(false, 'Program tidak ditemukan');
        try {
            $stmt = $pdo->prepare("UPDATE psb_programs SET name = ? WHERE id = ?");
            $stmt->execute([$name, $programId]);
            jsonResponse(true, 'Program diperbarui', ['id' => $programId, 'name' => $name]);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'uniq_year_name_gender') !== false) {
                jsonResponse(false, 'Nama program sudah digunakan pada tahun aktif');
            }
            jsonResponse(false, 'Gagal memperbarui program: ' . $e->getMessage());
        }
    }

    if ($action === 'delete_program' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            jsonResponse(false, 'Hanya ADMIN dapat menghapus program');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $programId = (int)($payload['program_id'] ?? 0);
        if ($programId <= 0) jsonResponse(false, 'program_id wajib diisi');
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $chk = $pdo->prepare("SELECT id FROM psb_programs WHERE id = ? AND academic_year_id = ? LIMIT 1");
        $chk->execute([$programId, $yearId]);
        if (!$chk->fetchColumn()) jsonResponse(false, 'Program tidak ditemukan');
        $used = $pdo->prepare("SELECT COUNT(*) FROM psb_applicants WHERE program_id = ? AND academic_year_id = ?");
        $used->execute([$programId, $yearId]);
        if ((int)$used->fetchColumn() > 0) {
            jsonResponse(false, 'Tidak bisa menghapus, sudah ada pendaftar pada program ini');
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM psb_program_quotas WHERE program_id = ? AND academic_year_id = ?")->execute([$programId, $yearId]);
            $pdo->prepare("DELETE FROM psb_programs WHERE id = ?")->execute([$programId]);
            $pdo->commit();
            jsonResponse(true, 'Program dihapus');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal menghapus program: ' . $e->getMessage());
        }
    }

    if ($action === 'save_program_quotas' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN','ACADEMIC','ADMIN_UNIT'])) {
            jsonResponse(false, 'Tidak memiliki hak untuk menyetel kuota program');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $unitId = (int)($payload['unit_id'] ?? 0);
        $items = $payload['items'] ?? [];
        if ($unitId <= 0) jsonResponse(false, 'unit_id wajib diisi');
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $stmtUnit->execute([$unitId]);
        $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
        if (!$u) jsonResponse(false, 'Unit tidak valid');
        if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            if (!ais_is_unit_allowed($pdo, $u['code'])) {
                jsonResponse(false, 'Unit tidak diizinkan untuk role Anda');
            }
        }
        if (!is_array($items) || empty($items)) {
            jsonResponse(false, 'Data kuota program kosong');
        }
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $up = $pdo->prepare("INSERT INTO psb_program_quotas (program_id, unit_id, academic_year_id, quota, updated_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE quota = VALUES(quota), updated_by = VALUES(updated_by)");
        $count = 0;
        foreach ($items as $it) {
            $pid = (int)($it['program_id'] ?? 0);
            $q = max(0, (int)($it['quota'] ?? 0));
            if ($pid <= 0) continue;
            $chk = $pdo->prepare("SELECT id FROM psb_programs WHERE id = ? AND academic_year_id = ? LIMIT 1");
            $chk->execute([$pid, $yearId]);
            if (!$chk->fetchColumn()) continue;
            $up->execute([$pid, $unitId, $yearId, $q, $userId]);
            $count++;
        }
        jsonResponse(true, 'Kuota program disimpan', ['updated' => $count]);
    }

    if ($action === 'save_programs_and_quotas' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN','ACADEMIC','ADMIN_UNIT'])) {
            jsonResponse(false, 'Tidak memiliki hak untuk menyetel kategori & kuota');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $unitId = (int)($payload['unit_id'] ?? 0);
        $items = $payload['items'] ?? [];
        if ($unitId <= 0) jsonResponse(false, 'unit_id wajib diisi');
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $stmtUnit->execute([$unitId]);
        $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
        if (!$u) jsonResponse(false, 'Unit tidak valid');
        if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            if (!ais_is_unit_allowed($pdo, $u['code'])) {
                jsonResponse(false, 'Unit tidak diizinkan untuk role Anda');
            }
        }
        if (!is_array($items) || empty($items)) {
            jsonResponse(false, 'Data kategori/kuota kosong');
        }
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $upQuota = $pdo->prepare("INSERT INTO psb_program_quotas (program_id, unit_id, academic_year_id, quota, updated_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE quota = VALUES(quota), updated_by = VALUES(updated_by)");
        $getProg = $pdo->prepare("SELECT id FROM psb_programs WHERE academic_year_id = ? AND name = ? AND gender = ? LIMIT 1");
        $insProg = $pdo->prepare("INSERT INTO psb_programs (academic_year_id, name, gender, created_by) VALUES (?,?,?,?)");
        $count = 0;
        foreach ($items as $it) {
            $pid = (int)($it['program_id'] ?? 0);
            $name = trim((string)($it['name'] ?? ''));
            $gender = strtoupper(trim((string)($it['gender'] ?? 'ALL')));
            $quota = max(0, (int)($it['quota'] ?? 0));
            if ($pid <= 0) {
                if ($name === '') continue;
                if (!in_array($gender, ['L','P','ALL'])) $gender = 'ALL';
                try {
                    $insProg->execute([$yearId, $name, $gender, $userId]);
                    $pid = (int)$pdo->lastInsertId();
                } catch (\Throwable $e) {
                    // If duplicate, fetch existing
                    $getProg->execute([$yearId, $name, $gender]);
                    $pid = (int)($getProg->fetchColumn() ?: 0);
                    if ($pid <= 0) continue;
                }
            } else {
                $chk = $pdo->prepare("SELECT id FROM psb_programs WHERE id = ? AND academic_year_id = ? LIMIT 1");
                $chk->execute([$pid, $yearId]);
                if (!$chk->fetchColumn()) continue;
            }
            $upQuota->execute([$pid, $unitId, $yearId, $quota, $userId]);
            $count++;
        }
        jsonResponse(true, 'Kategori & Kuota disimpan', ['updated' => $count]);
    }

    if ($action === 'get_numbering_prefix' && $method === 'GET') {
        $unitId = (int)($_GET['unit_id'] ?? 0);
        $yearId = getActiveAcademicYearId($pdo);
        if ($unitId <= 0 || !$yearId) jsonResponse(false, 'unit_id/year tidak valid');
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $stmtUnit->execute([$unitId]);
        $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
        if (!$u) jsonResponse(false, 'Unit tidak valid');
        if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            if (!ais_is_unit_allowed($pdo, $u['code'])) jsonResponse(false, 'Unit tidak diizinkan untuk role Anda');
        }
        $stmt = $pdo->prepare("SELECT prefix, seq_length FROM psb_numbering_prefixes WHERE unit_id = ? AND academic_year_id = ? LIMIT 1");
        $stmt->execute([$unitId, $yearId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            jsonResponse(true, 'Prefix diambil', ['prefix' => $row['prefix'], 'seq_length' => (int)$row['seq_length']]);
        } else {
            $yy = getYearSuffix($pdo, $yearId);
            jsonResponse(true, 'Prefix default', ['prefix' => strtoupper($u['code']) . $yy, 'seq_length' => 5]);
        }
    }

    if ($action === 'save_numbering_prefix' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN','ACADEMIC','ADMIN_UNIT'])) {
            jsonResponse(false, 'Tidak memiliki hak untuk menyetel awalan penomoran');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $unitId = (int)($payload['unit_id'] ?? 0);
        $prefix = trim((string)($payload['prefix'] ?? ''));
        $seqLen = max(1, (int)($payload['seq_length'] ?? 4));
        if ($unitId <= 0 || $prefix === '') jsonResponse(false, 'unit_id dan prefix wajib diisi');
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $stmtUnit->execute([$unitId]);
        $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
        if (!$u) jsonResponse(false, 'Unit tidak valid');
        if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            if (!ais_is_unit_allowed($pdo, $u['code'])) jsonResponse(false, 'Unit tidak diizinkan untuk role Anda');
        }
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        $up = $pdo->prepare("INSERT INTO psb_numbering_prefixes (unit_id, academic_year_id, prefix, seq_length, updated_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE prefix = VALUES(prefix), seq_length = VALUES(seq_length), updated_by = VALUES(updated_by)");
        $up->execute([$unitId, $yearId, $prefix, $seqLen, $userId]);
        jsonResponse(true, 'Awalan penomoran disimpan', ['prefix' => $prefix, 'seq_length' => $seqLen]);
    }

    if ($action === 'reset_psb' && $method === 'POST') {
        if (!$userRole || !in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            jsonResponse(false, 'Hanya ADMIN dapat reset PSB');
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $scope = strtolower(trim((string)($payload['scope'] ?? 'all'))); // 'all' or 'unit'
        $unitId = (int)($payload['unit_id'] ?? 0);
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        if ($scope === 'unit') {
            if ($unitId <= 0) jsonResponse(false, 'unit_id wajib untuk reset unit');
            $pdo->prepare("DELETE FROM psb_applicants WHERE academic_year_id = ? AND unit_id = ?")->execute([$yearId, $unitId]);
            $pdo->prepare("DELETE FROM psb_program_quotas WHERE academic_year_id = ? AND unit_id = ?")->execute([$yearId, $unitId]);
            $pdo->prepare("DELETE FROM psb_unit_quotas WHERE academic_year_id = ? AND unit_id = ?")->execute([$yearId, $unitId]);
            jsonResponse(true, 'Reset PSB unit selesai');
        } else {
            $pdo->prepare("DELETE FROM psb_applicants WHERE academic_year_id = ?")->execute([$yearId]);
            $pdo->prepare("DELETE FROM psb_program_quotas WHERE academic_year_id = ?")->execute([$yearId]);
            $pdo->prepare("DELETE FROM psb_unit_quotas WHERE academic_year_id = ?")->execute([$yearId]);
            $pdo->prepare("DELETE FROM psb_programs WHERE academic_year_id = ?")->execute([$yearId]);
            jsonResponse(true, 'Reset PSB tahun aktif selesai');
        }
    }

    if ($action === 'list_applicants' && $method === 'GET') {
        $unitId = (int)($_GET['unit_id'] ?? 0);
        $programId = (int)($_GET['program_id'] ?? 0);
        $yearId = getActiveAcademicYearId($pdo);
        if (!$yearId) jsonResponse(false, 'Tahun ajaran aktif tidak ditemukan');
        if ($unitId <= 0) jsonResponse(false, 'unit_id wajib diisi');
        $stmtUnit = $pdo->prepare("SELECT id, code FROM core_units WHERE id = ?");
        $stmtUnit->execute([$unitId]);
        $u = $stmtUnit->fetch(PDO::FETCH_ASSOC);
        if (!$u) jsonResponse(false, 'Unit tidak valid');
        if (!in_array($userRole, ['SUPERADMIN','ADMIN'])) {
            if (!ais_is_unit_allowed($pdo, $u['code'])) {
                jsonResponse(false, 'Unit tidak diizinkan untuk role Anda');
            }
        }
        $sql = "SELECT a.id, a.reg_number, a.name, a.gender, a.birth_date, a.guardian_name, a.phone, a.email, a.status, a.souvenir_received, a.created_at, p.name AS program_name FROM psb_applicants a LEFT JOIN psb_programs p ON a.program_id = p.id WHERE a.unit_id = ? AND a.academic_year_id = ?";
        $params = [$unitId, $yearId];
        if ($programId > 0) {
            $sql .= " AND a.program_id = ?";
            $params[] = $programId;
        }
        $sql .= " ORDER BY a.id DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, 'Data pendaftar diambil', $rows);
    }

    if ($action === 'update_souvenir' && $method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $applicantId = (int)($payload['applicant_id'] ?? 0);
        $received = !!($payload['received'] ?? false);
        if ($applicantId <= 0) jsonResponse(false, 'applicant_id wajib');
        $stmt = $pdo->prepare("UPDATE psb_applicants SET souvenir_received = ?, souvenir_received_at = ? WHERE id = ?");
        $stmt->execute([$received ? 1 : 0, $received ? date('Y-m-d H:i:s') : null, $applicantId]);
        jsonResponse(true, 'Status souvenir diperbarui', ['applicant_id' => $applicantId, 'received' => $received]);
    }

    jsonResponse(false, 'Action tidak dikenali');
} catch (\Throwable $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?> 
