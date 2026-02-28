<?php
require_once __DIR__ . '/config/database.php';

$files = [
    'database/full_schema.sql',
    'database/update_schema_1.sql',
    'database/update_subject_teacher.sql',
    'database/update_student_details.sql',
    'database/add_schedule_params.sql',
    'database/finance_schema.sql',
    'database/accounting_schema.sql',
    'database/inventory_schema.sql',
    'database/library_schema.sql',
    'database/pos_schema.sql',
    'database/manual_update_full.sql'
];

foreach ($files as $file) {
    $abs = __DIR__ . '/' . $file;
    if (!file_exists($abs)) {
        echo "File not found: $file<br>";
        continue;
    }
    
    $sql = file_get_contents($abs);
    
    try {
        $pdo->exec($sql);
        echo "Executed $file successfully.<br>";
    } catch (PDOException $e) {
        echo "Error executing $file: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS core_settings (setting_key varchar(50) NOT NULL, setting_value text, PRIMARY KEY (setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Ensured core_settings table.<br>";
} catch (PDOException $e) {
    echo "core_settings check: " . $e->getMessage() . "<br>";
}

try {
    $cols = $pdo->query("DESCRIBE core_users")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('email', $cols)) { $pdo->exec("ALTER TABLE core_users ADD COLUMN email varchar(100) DEFAULT NULL AFTER password_hash"); echo "Added email column to core_users.<br>"; }
    if (!in_array('status', $cols)) { $pdo->exec("ALTER TABLE core_users ADD COLUMN status enum('ACTIVE','SUSPENDED') DEFAULT 'ACTIVE' AFTER email"); echo "Added status column to core_users.<br>"; }
    if (!in_array('access_modules', $cols)) { $pdo->exec("ALTER TABLE core_users ADD COLUMN access_modules text DEFAULT NULL AFTER status"); echo "Added access_modules column to core_users.<br>"; }
} catch (PDOException $e) {
    echo "core_users columns check: " . $e->getMessage() . "<br>";
}

// Additional fix: Ensure core_people has status if not exists
try {
    $pdo->exec("ALTER TABLE core_people ADD COLUMN status ENUM('ACTIVE', 'INACTIVE', 'GRADUATED', 'MOVED') DEFAULT 'ACTIVE'");
    echo "Added status column to core_people.<br>";
} catch (PDOException $e) {
    // Column might already exist
    echo "Status column check: " . $e->getMessage() . "<br>";
}

// Additional fix: Ensure hr_employees has status if not exists
try {
    $pdo->exec("ALTER TABLE hr_employees ADD COLUMN status ENUM('ACTIVE', 'RESIGNED', 'RETIRED', 'SUSPENDED') DEFAULT 'ACTIVE'");
    echo "Added status column to hr_employees.<br>";
} catch (PDOException $e) {
    // Column might already exist
    echo "Status column check: " . $e->getMessage() . "<br>";
}

echo "Database installation complete.";
// Ensure acad_subject_teachers schedule params columns exist
try {
    $check = $pdo->query("SHOW COLUMNS FROM acad_subject_teachers LIKE 'weekly_count'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE acad_subject_teachers ADD COLUMN weekly_count INT DEFAULT 1");
        echo "Added weekly_count column to acad_subject_teachers.<br>";
    }
    $check = $pdo->query("SHOW COLUMNS FROM acad_subject_teachers LIKE 'session_length'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE acad_subject_teachers ADD COLUMN session_length INT DEFAULT 1");
        echo "Added session_length column to acad_subject_teachers.<br>";
    }
} catch (PDOException $e) {
    echo "acad_subject_teachers schedule params check: " . $e->getMessage() . "<br>";
}

// Ensure core_users.role enum includes operational roles (SECURITY/CLEANING/etc)
try {
    $colInfo = $pdo->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'core_users' 
          AND COLUMN_NAME = 'role'
    ");
    $ctype = $colInfo ? $colInfo->fetchColumn() : null;
    if ($ctype && stripos($ctype, 'enum') !== false) {
        $pdo->exec("
            ALTER TABLE core_users 
            MODIFY COLUMN role ENUM(
                'SUPERADMIN','ADMIN','ADMIN_UNIT','SUPERUSER','ADMINISTRATOR',
                'STAFF','ACADEMIC','FOUNDATION','MANAGERIAL','FINANCE','POS',
                'TEACHER','STUDENT','PARENT',
                'SECURITY','CLEANING','LIBRARY','BOARDING','PRINCIPAL'
            ) NOT NULL
        ");
        echo "Updated core_users.role enum to include SECURITY/CLEANING/others.<br>";
    }
} catch (PDOException $e) {
    echo "core_users.role enum update: " . $e->getMessage() . "<br>";
}

// Ensure core_users.id is AUTO_INCREMENT (fix MySQL 1364 error on insert)
try {
    $q = $pdo->query("
        SELECT COLUMN_TYPE, EXTRA 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'core_users' 
          AND COLUMN_NAME = 'id'
    ");
    $info = $q ? $q->fetch(PDO::FETCH_ASSOC) : null;
    $extra = $info['EXTRA'] ?? '';
    $ctype = strtoupper($info['COLUMN_TYPE'] ?? '');
    if (stripos($extra, 'auto_increment') === false) {
        if (strpos($ctype, 'BIGINT') !== false) {
            $pdo->exec("ALTER TABLE core_users MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        } else {
            $pdo->exec("ALTER TABLE core_users MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        }
        echo "Fixed core_users.id to AUTO_INCREMENT.<br>";
    } else {
        echo "core_users.id already AUTO_INCREMENT.<br>";
    }
} catch (PDOException $e) {
    echo "core_users.id auto_increment fix: " . $e->getMessage() . "<br>";
}
?>
