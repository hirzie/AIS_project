<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';

function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function parseSizeToBytes($s) {
    $s = trim((string)$s);
    if ($s === '') return 0;
    $unit = strtolower(substr($s, -1));
    $num = (float)$s;
    if (in_array($unit, ['g','m','k'])) {
        if ($unit === 'g') return (int)($num * 1024 * 1024 * 1024);
        if ($unit === 'm') return (int)($num * 1024 * 1024);
        if ($unit === 'k') return (int)($num * 1024);
    }
    return (int)$num;
}
function ensure_document_approval_columns($pdo) {
    try {
        $check = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mgr_documents'");
        $cols = $check->fetchAll(PDO::FETCH_COLUMN, 0);
        $needRole = !in_array('approval_role', $cols);
        $needDate = !in_array('approval_date', $cols);
        $needUser = !in_array('approval_user_id', $cols);
        if ($needRole) { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN approval_role VARCHAR(50) NULL"); }
        if ($needDate) { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN approval_date DATETIME NULL"); }
        if ($needUser) { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN approval_user_id INT NULL"); }
    } catch (\Throwable $e) {}
}

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
function get_setting($pdo, $key) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM core_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    } catch (\Throwable $e) { return null; }
}
function parse_managerial_targets($pdo, $override = null) {
    $raw = $override;
    if ($raw === null) $raw = (string)get_setting($pdo, 'wa_managerial_targets');
    $arr = [];
    if (is_array($raw)) {
        $arr = $raw;
    } else {
        $s = trim((string)$raw);
        if ($s !== '') {
            $s = str_replace(["\r\n", "\r"], "\n", $s);
            $parts = preg_split('/[,\n]+/', $s);
            foreach ($parts as $p) { $p = trim($p); if ($p !== '') $arr[] = $p; }
        }
    }
    return $arr;
}
function send_wa($pdo, $text, $targetOverride = null) {
    $url = trim((string)get_setting($pdo, 'wa_api_url'));
    $token = trim((string)get_setting($pdo, 'wa_api_token'));
    $target = $targetOverride ?: trim((string)get_setting($pdo, 'wa_security_target'));
    if ($url === '' || $token === '' || $text === '') return ['success' => false, 'error' => 'Konfigurasi tidak lengkap'];
    $t = preg_replace('/[^0-9]/', '', (string)$target);
    if ($t === '') return ['success' => false, 'error' => 'Target kosong'];
    if (substr($t, 0, 1) === '0') $t = '62' . substr($t, 1);
    if (strpos($t, '@') === false) $t .= '@c.us';
    $payload = json_encode(['to' => $t, 'message' => $text, 'clientId' => $token], JSON_UNESCAPED_UNICODE);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 20, 'ignore_errors' => true], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    try {
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) return ['success' => false, 'error' => 'Koneksi gagal'];
        $j = json_decode($resp, true);
        if ($j && isset($j['success'])) return ['success' => !!$j['success'], 'error' => $j['error'] ?? null];
        return ['success' => false, 'error' => 'Respon tidak valid'];
    } catch (\Throwable $e) { return ['success' => false, 'error' => $e->getMessage()]; }
}
try {
    // Ensure tables exist (idempotent)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mgr_meetings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_number VARCHAR(50) NOT NULL,
            title VARCHAR(200) NOT NULL,
            meeting_date DATE NOT NULL,
            module_tag VARCHAR(50) NOT NULL,
            modules VARCHAR(200) DEFAULT NULL,
            tags VARCHAR(200) DEFAULT NULL,
            location VARCHAR(200) DEFAULT NULL,
            attendees JSON DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            decisions TEXT DEFAULT NULL,
            allowed_roles JSON DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mgr_meeting_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            comment TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (meeting_id) REFERENCES mgr_meetings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mgr_meeting_participant_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_id INT NOT NULL,
            participant VARCHAR(200) NOT NULL,
            note TEXT DEFAULT NULL,
            user_id INT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_meeting_participant (meeting_id, participant),
            FOREIGN KEY (meeting_id) REFERENCES mgr_meetings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mgr_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meeting_id INT DEFAULT NULL,
            module_tag VARCHAR(50) NOT NULL,
            doc_title VARCHAR(200) NOT NULL,
            doc_url TEXT DEFAULT NULL,
            doc_type VARCHAR(50) DEFAULT NULL,
            tags VARCHAR(200) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            approval_role VARCHAR(50) DEFAULT NULL,
            approval_date DATETIME DEFAULT NULL,
            allowed_roles JSON DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (module_tag),
            FOREIGN KEY (meeting_id) REFERENCES mgr_meetings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Attempt to add missing columns if table already exists
    try { $pdo->exec("ALTER TABLE mgr_meetings ADD COLUMN allowed_roles JSON DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_meetings ADD COLUMN meeting_number VARCHAR(50) NOT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_meetings ADD COLUMN modules VARCHAR(200) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_meetings ADD COLUMN location VARCHAR(200) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN tags VARCHAR(200) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN allowed_roles JSON DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN description TEXT DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN approval_role VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE mgr_documents ADD COLUMN approval_date DATETIME DEFAULT NULL"); } catch (Exception $e) {}

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $userRole = strtoupper($_SESSION['role'] ?? '');

    // LIST meetings
    if ($action === 'list' && $method === 'GET') {
        $module = $_GET['module'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        $limit = max(1, min($limit, 100));

        $filterTag = $_GET['tag'] ?? '';
        if ($module) {
            $stmt = $pdo->prepare("SELECT * FROM mgr_meetings WHERE (module_tag = ? OR FIND_IN_SET(?, modules)) ORDER BY meeting_date DESC, id DESC LIMIT $limit");
            $stmt->execute([$module, $module]);
        } else {
            $stmt = $pdo->query("SELECT * FROM mgr_meetings ORDER BY meeting_date DESC, id DESC LIMIT $limit");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_values(array_filter($rows, function($r) use ($userRole, $filterTag) {
            $okRole = true;
            if (!empty($r['allowed_roles'])) {
                $roles = json_decode($r['allowed_roles'], true);
                if (is_array($roles) && !empty($roles)) {
                    $okRole = $userRole ? in_array(strtoupper($userRole), array_map('strtoupper', $roles)) : false;
                }
            }
            $okTag = true;
            if (!empty($filterTag)) {
                $tags = array_map('trim', explode(',', $r['tags'] ?? ''));
                $okTag = in_array($filterTag, $tags);
            }
            return $okRole && $okTag;
        }));
        jsonResponse(true, 'Meetings fetched', $rows);
    }

    if ($action === 'get' && $method === 'GET') {
        $id = (int)($_GET['meeting_id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(false, 'meeting_id wajib');
        }
        $stmt = $pdo->prepare("SELECT * FROM mgr_meetings WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            jsonResponse(true, 'Meeting fetched', $row);
        } else {
            jsonResponse(false, 'Meeting tidak ditemukan');
        }
    }

    // SAVE meeting (create or update)
    if ($action === 'save' && $method === 'POST') {
        $editorRoles = ['PRINCIPAL','MANAGERIAL','ADMIN','SUPERADMIN'];
        if (!$userRole || !in_array($userRole, $editorRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak edit');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $meeting_number = trim($data['meeting_number'] ?? '');
        $title = $data['title'] ?? '';
        $meeting_date = $data['meeting_date'] ?? date('Y-m-d');
        $module_tag = strtoupper($data['module_tag'] ?? '');
        $tags = $data['tags'] ?? '';
        $location = $data['location'] ?? '';
        $attendees = $data['attendees'] ?? [];
        $notes = $data['notes'] ?? '';
        $decisions = $data['decisions'] ?? '';
        $allowed_roles = $data['allowed_roles'] ?? [];
        $modules_arr = $data['modules'] ?? [];
        $modules_csv = '';
        if (is_array($modules_arr) && !empty($modules_arr)) {
            $modules_csv = implode(',', array_map('strtoupper', array_map('trim', $modules_arr)));
        }
        $created_by = $data['created_by'] ?? null;

        if (!$title || !$module_tag || !$meeting_number) {
            jsonResponse(false, 'meeting_number, title, dan module_tag wajib diisi');
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE mgr_meetings SET meeting_number=?, title=?, meeting_date=?, module_tag=?, modules=?, tags=?, location=?, attendees=?, notes=?, decisions=?, allowed_roles=? WHERE id=?");
            $stmt->execute([$meeting_number, $title, $meeting_date, $module_tag, $modules_csv, $tags, $location, json_encode($attendees), $notes, $decisions, json_encode($allowed_roles), $id]);
            log_activity($pdo, 'EXECUTIVE', 'MEETING', 'UPDATE', 'mgr_meetings', $id, $title, 'Rapat #' . $meeting_number . ' • ' . $module_tag);
            jsonResponse(true, 'Meeting diperbarui', ['id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO mgr_meetings (meeting_number, title, meeting_date, module_tag, modules, tags, location, attendees, notes, decisions, allowed_roles, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$meeting_number, $title, $meeting_date, $module_tag, $modules_csv, $tags, $location, json_encode($attendees), $notes, $decisions, json_encode($allowed_roles), $created_by]);
            $newId = $pdo->lastInsertId();
            log_activity($pdo, 'EXECUTIVE', 'MEETING', 'CREATE', 'mgr_meetings', $newId, $title, 'Rapat #' . $meeting_number . ' • ' . $module_tag);
            jsonResponse(true, 'Meeting dibuat', ['id' => $newId]);
        }
    }

    // ADD document
    if ($action === 'add_document' && $method === 'POST') {
        $editorRoles = ['PRINCIPAL','MANAGERIAL','ADMIN','SUPERADMIN'];
        if (!$userRole || !in_array($userRole, $editorRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak edit');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $meeting_id = $data['meeting_id'] ?? null;
        $module_tag = strtoupper($data['module_tag'] ?? '');
        $doc_title = $data['doc_title'] ?? '';
        $doc_url = $data['doc_url'] ?? '';
        $doc_type = $data['doc_type'] ?? '';
        $tags = $data['tags'] ?? '';
        $description = $data['description'] ?? '';
        $allowed_roles = $data['allowed_roles'] ?? [];
        $approval_role = strtoupper($userRole) === 'MANAGERIAL' ? 'MANAGERIAL' : null;
        $approval_date = strtoupper($userRole) === 'MANAGERIAL' ? date('Y-m-d H:i:s') : null;

        if (!$module_tag || !$doc_title) {
            jsonResponse(false, 'module_tag dan doc_title wajib diisi');
        }
        $stmt = $pdo->prepare("INSERT INTO mgr_documents (meeting_id, module_tag, doc_title, doc_url, doc_type, tags, description, approval_role, approval_date, allowed_roles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$meeting_id, $module_tag, $doc_title, $doc_url, $doc_type, $tags, $description, $approval_role, $approval_date, json_encode($allowed_roles)]);
        $newId = $pdo->lastInsertId();
        log_activity($pdo, 'EXECUTIVE', 'DOCUMENT', 'CREATE', 'mgr_documents', $newId, $doc_title, 'Dokumen • ' . $module_tag);
        jsonResponse(true, 'Dokumen ditambahkan', ['id' => $newId]);
    }

    // UPDATE document
    if ($action === 'update_document' && $method === 'POST') {
        $editorRoles = ['PRINCIPAL','MANAGERIAL','ADMIN','SUPERADMIN'];
        if (!$userRole || !in_array($userRole, $editorRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak edit');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, 'id dokumen wajib');
        }
        try {
            $ck = $pdo->prepare("SELECT approval_role, approval_date FROM mgr_documents WHERE id = ?");
            $ck->execute([$id]);
            $ap = $ck->fetch(PDO::FETCH_ASSOC);
            if (!empty($ap['approval_role']) || !empty($ap['approval_date'])) {
                $privileged = in_array(strtoupper($userRole), ['SUPERADMIN','ADMIN']);
                if (!$privileged) {
                    jsonResponse(false, 'Dokumen sudah disahkan, tidak bisa diedit');
                }
            }
        } catch (Exception $e) {}
        $module_tag = strtoupper($data['module_tag'] ?? '');
        $doc_title = $data['doc_title'] ?? '';
        $doc_url = $data['doc_url'] ?? '';
        $doc_type = $data['doc_type'] ?? '';
        $tags = $data['tags'] ?? '';
        $description = $data['description'] ?? '';
        if (!$module_tag || !$doc_title) {
            jsonResponse(false, 'module_tag dan doc_title wajib diisi');
        }
        $stmt = $pdo->prepare("UPDATE mgr_documents SET module_tag = ?, doc_title = ?, doc_url = ?, doc_type = ?, tags = ?, description = ? WHERE id = ?");
        $stmt->execute([$module_tag, $doc_title, $doc_url, $doc_type, $tags, $description, $id]);
        log_activity($pdo, 'EXECUTIVE', 'DOCUMENT', 'UPDATE', 'mgr_documents', $id, $doc_title, 'Dokumen • ' . $module_tag);
        jsonResponse(true, 'Dokumen diperbarui');
    }

    // SET/UNSET approval (MANAGERIAL only)
    if ($action === 'set_document_approval' && $method === 'POST') {
        $canApproveRoles = ['MANAGERIAL','ADMIN','SUPERADMIN'];
        if (!$userRole || !in_array(strtoupper($userRole), $canApproveRoles)) {
            jsonResponse(false, 'Role tidak diizinkan mengelola pengesahan dokumen');
        }
        ensure_document_approval_columns($pdo);
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $approve = !!($data['approve'] ?? false);
        if ($id <= 0) { jsonResponse(false, 'id dokumen wajib'); }
        if ($approve) {
            $stmt = $pdo->prepare("UPDATE mgr_documents SET approval_role = 'MANAGERIAL', approval_date = ?, approval_user_id = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), ($_SESSION['user_id'] ?? null), $id]);
            jsonResponse(true, 'Dokumen disahkan');
        } else {
            $stmt = $pdo->prepare("UPDATE mgr_documents SET approval_role = NULL, approval_date = NULL, approval_user_id = NULL WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(true, 'Pengesahan dibatalkan');
        }
    }

    // UPLOAD document file
    if ($action === 'upload_document' && $method === 'POST') {
        $editorRoles = ['PRINCIPAL','MANAGERIAL','ADMIN','SUPERADMIN'];
        if (!$userRole || !in_array($userRole, $editorRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak edit');
        }
        if (!isset($_FILES['file'])) {
            $contentLen = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMax = parseSizeToBytes(ini_get('post_max_size'));
            if ($postMax > 0 && $contentLen > $postMax) {
                jsonResponse(false, 'Ukuran data melebihi post_max_size server');
            }
            if (!filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN)) {
                jsonResponse(false, 'Fitur upload file dimatikan di server');
            }
            jsonResponse(false, 'File tidak ditemukan');
        }
        $file = $_FILES['file'];
        if (!is_uploaded_file($file['tmp_name'])) {
            jsonResponse(false, 'Upload tidak valid');
        }
        $uploadMax = parseSizeToBytes(ini_get('upload_max_filesize'));
        if ($uploadMax > 0 && (int)$file['size'] > $uploadMax) {
            jsonResponse(false, 'Ukuran file melebihi upload_max_filesize server');
        }
        $maxSize = 20 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            jsonResponse(false, 'Ukuran file melebihi batas');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['pdf','doc','docx'];
        if (!in_array($ext, $allowedExt)) {
            jsonResponse(false, 'Tipe file tidak diizinkan');
        }
        $root = dirname(__DIR__);
        $targetDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'managerial' . DIRECTORY_SEPARATOR . 'docs';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $baseName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
        $filename = uniqid('doc_', true) . '_' . $baseName;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            jsonResponse(false, 'Gagal menyimpan file');
        }
        $relativeUrl = 'uploads/managerial/docs/' . $filename;
        jsonResponse(true, 'Upload berhasil', ['url' => $relativeUrl]);
    }

    // LIST documents
    if ($action === 'list_documents' && $method === 'GET') {
        ensure_document_approval_columns($pdo);
        $module = strtoupper(trim($_GET['module'] ?? ''));
        $meetingId = $_GET['meeting_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $limit = max(1, min($limit, 200));

        $userRole = strtoupper($_SESSION['role'] ?? '');
        $filterTag = $_GET['tag'] ?? '';

        try {
            if ($meetingId) {
                $stmt = $pdo->prepare("SELECT d.*, u.username AS approval_username, p.name AS approval_people_name
                                       FROM mgr_documents d
                                       LEFT JOIN core_users u ON d.approval_user_id = u.id
                                       LEFT JOIN core_people p ON u.people_id = p.id
                                       WHERE d.meeting_id = ?
                                       ORDER BY d.id DESC
                                       LIMIT $limit");
                $stmt->execute([$meetingId]);
            } elseif ($module) {
                $stmt = $pdo->prepare("SELECT d.*, u.username AS approval_username, p.name AS approval_people_name
                                       FROM mgr_documents d
                                       LEFT JOIN core_users u ON d.approval_user_id = u.id
                                       LEFT JOIN core_people p ON u.people_id = p.id
                                       WHERE UPPER(d.module_tag) = ?
                                       ORDER BY d.id DESC
                                       LIMIT $limit");
                $stmt->execute([$module]);
            } else {
                $stmt = $pdo->query("SELECT d.*, u.username AS approval_username, p.name AS approval_people_name
                                     FROM mgr_documents d
                                     LEFT JOIN core_users u ON d.approval_user_id = u.id
                                     LEFT JOIN core_people p ON u.people_id = p.id
                                     ORDER BY d.id DESC
                                     LIMIT $limit");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Fallback without joins to avoid empty results if auxiliary tables missing
            if ($meetingId) {
                $stmt = $pdo->prepare("SELECT * FROM mgr_documents WHERE meeting_id = ? ORDER BY id DESC LIMIT $limit");
                $stmt->execute([$meetingId]);
            } elseif ($module) {
                $stmt = $pdo->prepare("SELECT * FROM mgr_documents WHERE UPPER(module_tag) = ? ORDER BY id DESC LIMIT $limit");
                $stmt->execute([$module]);
            } else {
                $stmt = $pdo->query("SELECT * FROM mgr_documents ORDER BY id DESC LIMIT $limit");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $rows = array_values(array_filter($rows, function($r) use ($userRole, $filterTag) {
            $privileged = in_array(strtoupper($userRole), ['SUPERADMIN','ADMIN','MANAGERIAL']);
            $okRole = true;
            if (!$privileged && !empty($r['allowed_roles'])) {
                $roles = json_decode($r['allowed_roles'], true);
                if (is_array($roles) && !empty($roles)) {
                    $okRole = $userRole ? in_array(strtoupper($userRole), array_map('strtoupper', $roles)) : false;
                }
            }
            $okTag = true;
            if (!empty($filterTag)) {
                $tags = array_map('trim', explode(',', $r['tags'] ?? ''));
                $okTag = in_array($filterTag, $tags);
            }
            return $okRole && $okTag;
        }));
        jsonResponse(true, 'Documents fetched', $rows);
    }

    // DELETE document (SUPERADMIN/ADMIN only)
    if ($action === 'delete_document' && $method === 'POST') {
        $deleterRoles = ['SUPERADMIN','ADMIN','MANAGERIAL'];
        if (!$userRole || !in_array($userRole, $deleterRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak hapus dokumen');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, 'id dokumen wajib diisi');
        }
        try {
            $ck = $pdo->prepare("SELECT approval_role, approval_date FROM mgr_documents WHERE id = ?");
            $ck->execute([$id]);
            $ap = $ck->fetch(PDO::FETCH_ASSOC);
            if (!empty($ap['approval_role']) || !empty($ap['approval_date'])) {
                jsonResponse(false, 'Dokumen sudah disahkan, tidak bisa dihapus');
            }
        } catch (Exception $e) {}
        $info = null;
        try {
            $s = $pdo->prepare("SELECT doc_title, module_tag FROM mgr_documents WHERE id = ?");
            $s->execute([$id]);
            $info = $s->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        $stmt = $pdo->prepare("DELETE FROM mgr_documents WHERE id = ?");
        $stmt->execute([$id]);
        if ($info) {
            log_activity($pdo, 'EXECUTIVE', 'DOCUMENT', 'DELETE', 'mgr_documents', $id, ($info['doc_title'] ?? ''), 'Dokumen • ' . ($info['module_tag'] ?? ''));
        } else {
            log_activity($pdo, 'EXECUTIVE', 'DOCUMENT', 'DELETE', 'mgr_documents', $id, '', 'Dokumen dihapus');
        }
        jsonResponse(true, 'Dokumen dihapus');
    }

    // RECENT indicator (count meetings in last N days, by module)
    if ($action === 'recent_count' && $method === 'GET') {
        $module = $_GET['module'] ?? '';
        $days = (int)($_GET['days'] ?? 7);
        $days = max(1, min($days, 90));
        $since = date('Y-m-d', strtotime("-$days days"));
        if ($module) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mgr_meetings WHERE module_tag = ? AND meeting_date >= ?");
            $stmt->execute([strtoupper($module), $since]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mgr_meetings WHERE meeting_date >= ?");
            $stmt->execute([$since]);
        }
        jsonResponse(true, 'Recent count', ['count' => (int)$stmt->fetchColumn()]);
    }

    // LIST comments for a meeting
    if ($action === 'list_comments' && $method === 'GET') {
        $meetingId = (int)($_GET['meeting_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 100);
        $limit = max(1, min($limit, 200));
        if ($meetingId <= 0) { jsonResponse(false, 'meeting_id wajib'); }
        $stmt = $pdo->prepare("SELECT c.id, c.meeting_id, c.user_id, c.comment, c.created_at, u.username, p.name AS people_name
                               FROM mgr_meeting_comments c
                               LEFT JOIN core_users u ON c.user_id = u.id
                               LEFT JOIN core_people p ON u.people_id = p.id
                               WHERE c.meeting_id = ?
                               ORDER BY c.created_at DESC, c.id DESC
                               LIMIT $limit");
        $stmt->execute([$meetingId]);
        jsonResponse(true, 'Comments fetched', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ADD comment to a meeting
    if ($action === 'add_comment' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) { jsonResponse(false, 'Unauthorized'); }
        $data = json_decode(file_get_contents('php://input'), true);
        $meetingId = (int)($data['meeting_id'] ?? 0);
        $comment = trim($data['comment'] ?? '');
        if ($meetingId <= 0 || $comment === '') { jsonResponse(false, 'meeting_id dan comment wajib'); }
        $ins = $pdo->prepare("INSERT INTO mgr_meeting_comments (meeting_id, user_id, comment) VALUES (?, ?, ?)");
        $ins->execute([$meetingId, $_SESSION['user_id'], $comment]);
        $id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT c.id, c.meeting_id, c.user_id, c.comment, c.created_at, u.username, p.name AS people_name
                               FROM mgr_meeting_comments c
                               LEFT JOIN core_users u ON c.user_id = u.id
                               LEFT JOIN core_people p ON u.people_id = p.id
                               WHERE c.id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, 'Comment added', $stmt->fetch(PDO::FETCH_ASSOC));
    }
    
    // LIST participant notes
    if ($action === 'list_participant_notes' && $method === 'GET') {
        $meetingId = (int)($_GET['meeting_id'] ?? 0);
        if ($meetingId <= 0) { jsonResponse(false, 'meeting_id wajib'); }
        $stmt = $pdo->prepare("SELECT n.meeting_id, n.participant, n.note, n.updated_at, u.username, p.name AS people_name
                               FROM mgr_meeting_participant_notes n
                               LEFT JOIN core_users u ON n.user_id = u.id
                               LEFT JOIN core_people p ON u.people_id = p.id
                               WHERE n.meeting_id = ?
                               ORDER BY n.participant ASC");
        $stmt->execute([$meetingId]);
        jsonResponse(true, 'Notes fetched', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // SAVE participant notes (bulk)
    if ($action === 'save_participant_notes' && $method === 'POST') {
        $editorRoles = ['PRINCIPAL','MANAGERIAL','ADMIN','SUPERADMIN'];
        if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), $editorRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak edit catatan peserta');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $meetingId = (int)($data['meeting_id'] ?? 0);
        $notes = $data['notes'] ?? [];
        if ($meetingId <= 0 || !is_array($notes)) { jsonResponse(false, 'Payload tidak valid'); }
        $ins = $pdo->prepare("INSERT INTO mgr_meeting_participant_notes (meeting_id, participant, note, user_id) VALUES (?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE note = VALUES(note), user_id = VALUES(user_id)");
        foreach ($notes as $n) {
            $participant = trim($n['participant'] ?? '');
            $note = trim($n['note'] ?? '');
            if ($participant === '') continue;
            $ins->execute([$meetingId, $participant, $note, $_SESSION['user_id']]);
        }
        jsonResponse(true, 'Catatan peserta disimpan');
    }

    // DELETE meeting (SUPERADMIN/ADMIN only)
    if ($action === 'delete' && $method === 'POST') {
        $deleterRoles = ['SUPERADMIN','ADMIN'];
        if (!$userRole || !in_array($userRole, $deleterRoles)) {
            jsonResponse(false, 'Anda tidak memiliki hak hapus rapat');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, 'id rapat wajib diisi');
        }
        $info = null;
        try {
            $s = $pdo->prepare("SELECT meeting_number, title, module_tag FROM mgr_meetings WHERE id = ?");
            $s->execute([$id]);
            $info = $s->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        $stmt = $pdo->prepare("DELETE FROM mgr_meetings WHERE id = ?");
        $stmt->execute([$id]);
        if ($info) {
            log_activity($pdo, 'EXECUTIVE', 'MEETING', 'DELETE', 'mgr_meetings', $id, ($info['title'] ?? ''), 'Rapat #' . ($info['meeting_number'] ?? '') . ' • ' . ($info['module_tag'] ?? ''));
        } else {
            log_activity($pdo, 'EXECUTIVE', 'MEETING', 'DELETE', 'mgr_meetings', $id, '', 'Rapat dihapus');
        }
        jsonResponse(true, 'Rapat dihapus');
    }

    // LIST activity logs for meetings
    if ($action === 'list_logs' && $method === 'GET') {
        $limit = (int)($_GET['limit'] ?? 100);
        $limit = max(1, min($limit, 200));
        $stmt = $pdo->query("SELECT a.*, u.username 
                             FROM activity_logs a
                             LEFT JOIN core_users u ON a.user_id = u.id
                             WHERE a.module = 'EXECUTIVE' AND a.category = 'MEETING'
                             ORDER BY a.created_at DESC, a.id DESC
                             LIMIT $limit");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, 'Logs fetched', $rows);
    }
    if ($action === 'send_wa_meeting' && $method === 'POST') {
        $role = strtoupper($_SESSION['role'] ?? '');
        if (!in_array($role, ['MANAGERIAL','ADMIN','SUPERADMIN'])) {
            jsonResponse(false, 'Unauthorized');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $meetingId = (int)($data['meeting_id'] ?? 0);
        $targetsOverride = $data['targets'] ?? null;
        if ($meetingId <= 0) jsonResponse(false, 'meeting_id wajib');
        $stmt = $pdo->prepare("SELECT meeting_number, title, meeting_date, module_tag, tags, location, attendees, notes, decisions FROM mgr_meetings WHERE id = ?");
        $stmt->execute([$meetingId]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$m) jsonResponse(false, 'Rapat tidak ditemukan');
        $atts = [];
        try { $atts = json_decode($m['attendees'] ?? '[]', true) ?: []; } catch (\Throwable $e) { $atts = []; }
        $attTxt = count($atts) ? implode(', ', $atts) : '-';
        $msg = "Rapat: " . ($m['title'] ?? '-') . " • No: " . ($m['meeting_number'] ?? '-') . "\nTanggal: " . ($m['meeting_date'] ?? '-') . "\nDivisi: " . ($m['module_tag'] ?? '-') . "\nLokasi: " . (($m['location'] ?? '') ?: '-') . "\nPeserta: " . $attTxt;
        $nt = trim((string)($m['notes'] ?? ''));
        $dc = trim((string)($m['decisions'] ?? ''));
        if ($nt !== '') $msg .= "\nCatatan: " . $nt;
        if ($dc !== '') $msg .= "\nKeputusan: " . $dc;
        $targets = parse_managerial_targets($pdo, $targetsOverride ?? null);
        if (!is_array($targets) || count($targets) === 0) jsonResponse(false, 'Daftar nomor kosong');
        $ok = 0; $fail = 0; $errors = [];
        foreach ($targets as $t) {
            $res = send_wa($pdo, $msg, $t);
            $sc = !!($res['success'] ?? false);
            if ($sc) $ok++; else { $fail++; $errors[] = $res['error'] ?? 'unknown'; }
        }
        log_activity($pdo, 'EXECUTIVE', 'MEETING', 'WA_SEND', 'mgr_meetings', (string)$meetingId, 'Kirim WA Rapat', 'OK=' . $ok . ' FAIL=' . $fail);
        jsonResponse(($ok > 0), ($ok > 0 ? 'Terkirim' : 'Gagal'), ['ok' => $ok, 'fail' => $fail, 'errors' => $errors]);
    }
    jsonResponse(false, 'Unknown action');
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}
