<?php
require_once __DIR__ . '/../includes/guard.php';
ais_init_session();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionRole = strtoupper($_SESSION['role'] ?? '');

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action provided']);
    exit;
}
if (!$sessionUserId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($action === 'delete') {
        if (!in_array($sessionRole, ['SUPERADMIN','ADMIN'])) {
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
        $id = $data['id'];
        $stmt = $pdo->prepare("DELETE FROM core_users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    $username = $data['username'];
    $password = $data['password'] ?? '';
    $roleInput = $data['role'] ?? '';
    $statusInput = strtoupper($data['status'] ?? 'ACTIVE'); 
    $emailInput = $data['email'] ?? '';
    $modules = $data['access_modules'] ?? [];
    $peopleId = $data['people_id'] ?? null;

    $allowedRoles = ['SUPERADMIN','ADMIN','STAFF','ACADEMIC','FOUNDATION','MANAGERIAL','FINANCE','POS','TEACHER','STUDENT','PARENT','SECURITY','CLEANING','LIBRARY','BOARDING','PRINCIPAL'];
    $role = strtoupper($roleInput);
    if (!in_array($role, $allowedRoles)) {
        if ($roleInput === 'admin') $role = 'SUPERADMIN';
        elseif ($roleInput === 'guru') $role = 'TEACHER';
        elseif ($roleInput === 'siswa') $role = 'STUDENT';
        elseif ($roleInput === 'staff') $role = 'STAFF';
        elseif ($roleInput === 'manajer') $role = 'MANAGERIAL';
        elseif ($roleInput === 'kepsek') $role = 'PRINCIPAL';
        elseif ($roleInput === 'keamanan') $role = 'SECURITY';
        elseif ($roleInput === 'kebersihan') $role = 'CLEANING';
        elseif ($roleInput === 'perpustakaan') $role = 'LIBRARY';
        elseif ($roleInput === 'asrama') $role = 'BOARDING';
        else $role = 'STAFF';
    }

    if ($action === 'create') {
        if (!in_array($sessionRole, ['SUPERADMIN','ADMIN'])) {
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
        $colPwHash = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'password_hash'");
        $hasPwHash = $colPwHash && $colPwHash->fetch();
        $colPw = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'password'");
        $hasPw = $colPw && $colPw->fetch();
        if (!$hasPwHash && !$hasPw) {
            try { $pdo->exec("ALTER TABLE core_users ADD COLUMN password_hash varchar(255) DEFAULT NULL"); $hasPwHash = true; } catch (\Throwable $e) {}
        }
        $pwCol = $hasPwHash ? 'password_hash' : 'password';
        $pwVal = $hasPwHash ? password_hash($password, PASSWORD_DEFAULT) : md5($password);
        $columns = ['username', $pwCol, 'role'];
        $values = [$username, $pwVal, $role];
        $colEmail = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'email'");
        if ($colEmail && $colEmail->fetch()) {
            $columns[] = 'email';
            $values[] = $emailInput;
        }
        $colStatus = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'status'");
        if ($colStatus && $colStatus->fetch()) {
            $columns[] = 'status';
            $values[] = $statusInput;
        }
        $colModules = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'access_modules'");
        if ($colModules && $colModules->fetch()) {
            $columns[] = 'access_modules';
            $values[] = is_array($modules) ? json_encode($modules) : (is_string($modules) ? $modules : null);
        }
        if (!empty($peopleId)) {
            $columns[] = 'people_id';
            $values[] = $peopleId;
        }
        try {
            $q = $pdo->query("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'core_users' AND COLUMN_NAME = 'id'");
            $ex = $q ? (string)$q->fetchColumn() : '';
            if (stripos($ex, 'auto_increment') === false) {
                $pdo->exec("ALTER TABLE core_users MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
            }
        } catch (\Throwable $e) {}
        $sql = "INSERT INTO core_users (" . implode(',', $columns) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        echo json_encode(['success' => true]);

    } elseif ($action === 'update') {
        $id = $data['id'];
        $isAdmin = in_array($sessionRole, ['SUPERADMIN','ADMIN']);
        if (!$isAdmin && (int)$id !== (int)$sessionUserId) {
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
        $setParts = ["username = ?","role = ?"];
        $params = [$username, $role];
        if (!$isAdmin) {
            $setParts = ["username = ?"];
            $params = [$username];
        }
        if ($isAdmin && !empty($peopleId)) {
            $setParts[] = "people_id = ?";
            $params[] = $peopleId;
        }
        if ($isAdmin && !empty($password)) {
            $colPwHashU = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'password_hash'");
            $hasPwHashU = $colPwHashU && $colPwHashU->fetch();
            if ($hasPwHashU) {
                $setParts[] = "password_hash = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $setParts[] = "password = ?";
                $params[] = md5($password);
            }
        }
        $colEmail = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'email'");
        if ($colEmail && $colEmail->fetch()) {
            if ($isAdmin) {
                $setParts[] = "email = ?";
                $params[] = $emailInput;
            }
        }
        $colStatus = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'status'");
        if ($colStatus && $colStatus->fetch()) {
            if ($isAdmin) {
                $setParts[] = "status = ?";
                $params[] = $statusInput;
            }
        }
        $colModules = $pdo->query("SHOW COLUMNS FROM core_users LIKE 'access_modules'");
        if ($colModules && $colModules->fetch()) {
            if ($isAdmin) {
                $setParts[] = "access_modules = ?";
                $params[] = is_array($modules) ? json_encode($modules) : (is_string($modules) ? $modules : null);
            }
        }
        $sql = "UPDATE core_users SET " . implode(", ", $setParts) . " WHERE id = ?";
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
