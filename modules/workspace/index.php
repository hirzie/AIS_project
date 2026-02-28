<?php
require_once '../../includes/guard.php';
require_once '../../config/database.php';

// Force refresh allowed units to ensure Workspace access is recognized
if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['allowed_units']);
}

// Custom Access Check: Allow if 'workspace' module is allowed OR if user is a Homeroom Teacher
ais_init_session();
$hasAccess = false;

// Custom Access Check: Allow if 'workspace' module is allowed OR if user is a Homeroom Teacher
if (isset($_SESSION['allowed_modules']['workspace']) && $_SESSION['allowed_modules']['workspace']) {
    $hasAccess = true;
} 
// 2. Check if user is a Homeroom Teacher (Wali Kelas)
else {
    $pid = $_SESSION['person_id'] ?? null;
    if ($pid) {
        try {
            // Robust check: Check both direct person_id and via hr_employees (employee_id)
            $stmtWali = $pdo->prepare("
                SELECT COUNT(*) 
                FROM acad_classes c 
                LEFT JOIN hr_employees e ON c.homeroom_teacher_id = e.id 
                WHERE c.homeroom_teacher_id = ? OR e.person_id = ?
            ");
            $stmtWali->execute([$pid, $pid]);
            if ($stmtWali->fetchColumn() > 0) {
                $hasAccess = true;
                // Grant temporary access to 'workspace' module for this session/request
                $_SESSION['allowed_modules']['workspace'] = true;
            }
        } catch (\Throwable $e) {}
    }
}

if (!$hasAccess) {
    // This will redirect if not allowed
    require_login_and_module('workspace');
}

// Standard check (will pass now if we granted access above)
require_login_and_module('workspace');

$SKIP_VUE_IN_HEADER = true; // Optimize: Load Vue at bottom
require_once '../../includes/header.php';
?>
<script>window.SKIP_GLOBAL_APP = true;</script>
<?php
$__displayName = $_SESSION['username'] ?? 'Pengguna';
if (!empty($_SESSION['person_id'])) {
    $st = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
    $st->execute([$_SESSION['person_id']]);
    $nm = $st->fetchColumn();
    if ($nm) { $__displayName = $nm; }
}

// Check if user is explicitly assigned as Principal for a Unit (Fixed Unit)
$__workspaceUnitCode = '';
$__isWorkspaceFixed = false;
$__role = strtoupper($_SESSION['role'] ?? '');

if (!in_array($__role, ['SUPERADMIN', 'ADMIN'])) {
    // Check core_units for principal_id
    // FIX: Column principal_id does not exist in core_units. 
    // This check is disabled until the schema is updated or the logic is fixed.
    /*
    $stmtP = $pdo->prepare("SELECT code FROM core_units WHERE principal_id = ? LIMIT 1");
    $stmtP->execute([$_SESSION['person_id'] ?? 0]);
    $uCode = $stmtP->fetchColumn();
    
    if ($uCode) {
        $__workspaceUnitCode = strtolower($uCode);
        $__isWorkspaceFixed = true;
    }
    */
}

// --- SERVER-SIDE PRE-FETCHING (Compliance with STANDAR_REFACTORING.md) ---
// Note: Disabled to prevent 3s page load delay (TTFB). Client-side fetching is used instead.
$initialData = [
    'unitStats' => null,
    'homeroomClass' => null,
    'userRole' => $__role,
    'fixedUnit' => $__workspaceUnitCode,
    'isFixed' => $__isWorkspaceFixed
];

/* 
// 1. Pre-fetch Homeroom Class (Wali)
if (!empty($_SESSION['person_id'])) {
    try {
        $stmtWali = $pdo->prepare("
            SELECT c.id, c.name, l.unit_id, u.code as unit_code
            FROM acad_classes c 
            JOIN hr_employees e ON c.homeroom_teacher_id = e.id 
            JOIN acad_class_levels l ON c.level_id = l.id
            JOIN core_units u ON l.unit_id = u.id
            WHERE e.person_id = ?
            LIMIT 1
        ");
        $stmtWali->execute([$_SESSION['person_id']]);
        $waliClass = $stmtWali->fetch(PDO::FETCH_ASSOC);
        if ($waliClass) {
            $initialData['homeroomClass'] = $waliClass;
        }
    } catch (\Throwable $e) {}
}

// 2. Pre-fetch Unit Stats (Kepala) - Only if fixed unit
if ($__isWorkspaceFixed && $__workspaceUnitCode) {
    try {
        $unitCode = $__workspaceUnitCode;
        $stmtUnit = $pdo->prepare("SELECT id FROM core_units WHERE code = ? LIMIT 1");
        $stmtUnit->execute([strtoupper($unitCode)]);
        $uID = $stmtUnit->fetchColumn();
        
        if ($uID) {
             // Students Count
             $stmtSt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM acad_student_classes sc 
                JOIN acad_classes c ON sc.class_id = c.id
                JOIN acad_class_levels l ON c.level_id = l.id
                WHERE l.unit_id = ? AND sc.status = 'ACTIVE'
             ");
             $stmtSt->execute([$uID]);
             $studentCount = $stmtSt->fetchColumn();
             
             // Classes Count
             $stmtCl = $pdo->prepare("
                SELECT COUNT(*) 
                FROM acad_classes c
                JOIN acad_class_levels l ON c.level_id = l.id
                WHERE l.unit_id = ?
             ");
             $stmtCl->execute([$uID]);
             $classCount = $stmtCl->fetchColumn();
             
             $initialData['unitStats'] = [
                 'studentCount' => (int)$studentCount,
                 'classCount' => (int)$classCount,
                 'unitId' => $uID,
                 'unitCode' => $unitCode
             ];
        }
    } catch (\Throwable $e) {}
}
*/
?>
<style>
    [v-cloak] { display: none !important; }
    #loading-screen {
        position: fixed;
        inset: 0;
        background: #f8fafc;
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        gap: 1rem;
    }
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #e2e8f0;
        border-top-color: #4f46e5;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
    window.INITIAL_DATA = <?php echo json_encode($initialData); ?>;
    window.WORKSPACE_FIXED_UNIT = "<?php echo $__workspaceUnitCode; ?>";
    window.IS_WORKSPACE_FIXED = <?php echo $__isWorkspaceFixed ? 'true' : 'false'; ?>;
    window.USER_ROLE = "<?php echo $__role; ?>";
</script>

<div id="loading-screen">
    <div class="spinner"></div>
    <div class="text-slate-500 font-medium text-sm">Memuat Workspace...</div>
</div>

<div id="app" v-cloak class="flex flex-col h-screen">
    <?php include 'views/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-2 md:p-6 bg-slate-50">
        <div class="max-w-7xl mx-auto space-y-2 md:space-y-6">
            <?php include 'views/header.php'; ?>

            <?php include 'views/wali.php'; ?>
            <?php include 'views/kepala.php'; ?>
            <?php include 'views/wakasek.php'; ?>
            <?php include 'views/asrama.php'; ?>
            <?php include 'views/guru.php'; ?>
            <?php include 'views/common_activity.php'; ?>
        </div>
    </main>
    <?php include 'views/modals.php'; ?>
</div>

<!-- PDF.js for Thumbnail Preview -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.8.0/dist/mermaid.min.js"></script>
<script>mermaid.initialize({ startOnLoad: true, theme: 'default' });</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>

<!-- Load Vue.js Manually (since SKIP_VUE_IN_HEADER=true) -->
<script src="<?php echo $baseUrl; ?>assets/js/<?php echo $vueFile; ?>"></script>

<script type="module">
    window.SKIP_GLOBAL_APP = true;
    import { workspaceLogics } from '../../assets/js/modules/workspace_logic.js?v=<?php echo time(); ?>';
    
    try {
        // Mount the Vue app
        const app = Vue.createApp(workspaceLogics);
        app.mount('#app');
    } catch (e) {
        console.error('Vue Mount Error:', e);
        alert('Gagal memuat aplikasi: ' + e.message);
    } finally {
        // Hide loading screen always
        const loader = document.getElementById('loading-screen');
        if (loader) loader.style.display = 'none';
    }
</script>
