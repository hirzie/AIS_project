<?php
// --- ROUTING BYPASS FOR SHORT LINKS (DISPLAY MODULE) ---
// Menangani redirect slug (misal /DfP6M) tanpa bergantung pada Nginx rewrite rule
// Ini berguna di lingkungan hosting yang membatasi konfigurasi Nginx/Apache
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// --- ROUTING BYPASS (ROBUST VERSION) ---
// Menangani /DfP6M atau /index.php/DfP6M
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$reqPath = parse_url($reqUri, PHP_URL_PATH);

// Ambil segmen terakhir dari URL
$pathParts = explode('/', trim($reqPath, '/'));
$lastSegment = end($pathParts);

// Cek apakah segmen terakhir adalah slug (5+ karakter alfanumerik)
// Dan pastikan bukan nama file script utama (index.php) atau folder sistem
if (
    $lastSegment && 
    preg_match('/^[a-zA-Z0-9-]{5,}$/', $lastSegment) && 
    strcasecmp($lastSegment, 'index.php') !== 0 &&
    !preg_match('/^(api|modules|assets|config|includes|vendor)$/i', $lastSegment)
) {
    // Cek apakah ini bukan permintaan file fisik yang ada
    // (Misal: jangan override folder 'AIS' jika itu dianggap slug)
    if (!file_exists(__DIR__ . '/' . $lastSegment) && !is_dir(__DIR__ . '/' . $lastSegment)) {
        $_GET['slug'] = $lastSegment;
        $displayFile = __DIR__ . '/modules/display/index.php';
        
        if (file_exists($displayFile)) {
            require $displayFile;
            exit;
        }
    }
}
// -------------------------------------------------------

require_once __DIR__ . '/includes/guard.php';
ais_init_session();
require_once 'config/database.php';

// Fetch School Settings for SSR (Logo LCP Optimization)
$schoolSettingsPHP = [];
try {
    // Check if table exists to avoid errors on fresh install
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM core_settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $schoolSettingsPHP[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Table might not exist yet
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_login_and_module();

// --- PRE-FETCH DATA FOR PERFORMANCE (Server-Side Rendering) ---
// 1. Fetch Agenda (Current Month) to avoid blocking AJAX call
$initialAgenda = [];
try {
    $startMonth = date('Y-m-01');
    $endMonth = date('Y-m-t');
    
    // Fetch local agenda
    $stmtAg = $pdo->prepare("
        SELECT id, title, description, start_date, end_date, location, type, google_event_id, NULL as color 
        FROM acad_school_agenda 
        WHERE DATE(start_date) <= ? AND DATE(end_date) >= ? 
        ORDER BY start_date ASC
    ");
    $stmtAg->execute([$endMonth, $startMonth]);
    $initialAgenda = $stmtAg->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Google cache (simplified merge)
    $stmtGC = $pdo->prepare("
        SELECT google_event_id, title, description, location, start_date, end_date, color, calendar_id 
        FROM acad_school_agenda_google_cache
        WHERE DATE(start_date) <= ? AND DATE(end_date) >= ? 
        ORDER BY start_date ASC
    ");
    $stmtGC->execute([$endMonth, $startMonth]);
    $cached = $stmtGC->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge: Add cached items that are NOT in local (by google_event_id)
    $localGids = [];
    foreach ($initialAgenda as $ev) {
        if (!empty($ev['google_event_id'])) $localGids[$ev['google_event_id']] = true;
    }
    
    foreach ($cached as $c) {
        if (empty($localGids[$c['google_event_id']])) {
            $c['id'] = 'google:' . $c['google_event_id'];
            $c['type'] = 'EVENT'; 
            $initialAgenda[] = $c;
        }
    }
    
    // Re-sort by start_date
    usort($initialAgenda, function($a, $b) {
        return strtotime($a['start_date']) - strtotime($b['start_date']);
    });
} catch (Throwable $e) { /* ignore */ }

// 2. Fetch Full Profile
$initialProfile = null;
try {
    if (!empty($_SESSION['person_id'])) {
        $stmtProf = $pdo->prepare("
            SELECT p.*, u.username, u.role, u.email as user_email 
            FROM core_people p 
            LEFT JOIN core_users u ON p.id = u.people_id 
            WHERE p.id = ?
        ");
        $stmtProf->execute([$_SESSION['person_id']]);
        $initialProfile = $stmtProf->fetch(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { /* ignore */ }

$displayName = $_SESSION['username'] ?? 'Pengguna';
// Ensure person_id reflects latest link from core_users without requiring re-login
$personId = $_SESSION['person_id'] ?? null;
if (empty($personId) && !empty($_SESSION['user_id'])) {
    $stmtP = $pdo->prepare("SELECT people_id FROM core_users WHERE id = ?");
    $stmtP->execute([$_SESSION['user_id']]);
    $personId = $stmtP->fetchColumn() ?: null;
    if ($personId) {
        $_SESSION['person_id'] = $personId;
    }
}
if (!empty($personId)) {
    $stmtN = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
    $stmtN->execute([$personId]);
    $n = $stmtN->fetchColumn();
    if ($n) { $displayName = $n; }
}
$role = $_SESSION['role'] ?? '';
if (in_array($role, ['SUPERADMIN','ADMIN'])) {
    $modsCfg = require __DIR__ . '/config/modules.php';
    $allowed = $_SESSION['allowed_modules'] ?? [];
    foreach ($modsCfg as $m) {
        $k = strtolower($m['code'] ?? '');
        if ($k) { $allowed[$k] = true; }
    }
    foreach (['people','kiosk','payroll','counseling'] as $ex) { $allowed[$ex] = true; }
    $allowed['core'] = true;
    $_SESSION['allowed_modules'] = $allowed;
}
$allowedUnits = $_SESSION['allowed_units'] ?? null;
if (!is_array($allowedUnits)) {
    $allowedUnits = [];
    try {
        if (in_array(strtoupper($role), ['SUPERADMIN','ADMIN'])) {
            // Admin: all units allowed; leave empty to indicate no restriction
            $allowedUnits = [];
        } else {
            // Derive unit access from HR employee mapping
            if (!empty($personId)) {
                $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE person_id = ? LIMIT 1");
                $stmtEmp->execute([$personId]);
                $empId = $stmtEmp->fetchColumn();
                if ($empId) {
                    $stmtUA = $pdo->prepare("
                        SELECT COALESCE(u.receipt_code, u.code) AS unit_code
                        FROM hr_unit_access hua
                        JOIN core_units u ON hua.unit_id = u.id
                        WHERE hua.employee_id = ?
                    ");
                    $stmtUA->execute([$empId]);
                    $rows = $stmtUA->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($rows as $uc) {
                        $uc = strtolower(trim($uc));
                        if ($uc !== '') { $allowedUnits[$uc] = true; }
                    }
                }
            }
        }
    } catch (Throwable $e) {}
    $_SESSION['allowed_units'] = $allowedUnits;
}
$allowedMods = array_keys(array_filter($_SESSION['allowed_modules'] ?? []));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SekolahOS - Dashboard Utama</title>
    <!-- CRITICAL CSS: Renders layout immediately before Tailwind loads -->
    <style>
        /* Reset & Base */
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: sans-serif; }
        
        /* Layout Structure */
        #app { display: flex; min-height: 100vh; overflow: hidden; }
        
        /* Sidebar Critical */
        .static-sidebar {
            width: 16rem; /* w-64 */
            background-color: #0f172a; /* bg-slate-900 */
            color: white;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
        }
        
        /* Main Content Critical */
        .static-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            background-color: #f8fafc;
        }

        /* Skeleton Helpers */
        .skeleton-loading { padding: 1.5rem; flex: 1; overflow-y: auto; }
        .skeleton-header { height: 4rem; background-color: #f1f5f9; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; }
        .skeleton-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 1.5rem; }
        .skeleton-box { height: 8rem; background-color: #f8fafc; border-radius: 0.75rem; border: 1px solid #e2e8f0; }
        .skeleton-card { background-color: #f8fafc; border-radius: 0.75rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; }
        
        /* Responsive Grid Fallback */
        @media (max-width: 1024px) { .skeleton-grid-4 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .skeleton-grid-4 { grid-template-columns: 1fr; } .static-sidebar { display: none; } }

        /* Animation - Subtler Shimmer */
        @keyframes shimmer {
            0% { background-color: #f8fafc; }
            50% { background-color: #f1f5f9; }
            100% { background-color: #f8fafc; }
        }
        .animate-pulse-custom { animation: shimmer 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        [v-cloak] { display: none !important; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* PRE-CLOAK STYLES TO PREVENT FLICKER */
        /* Ensure sidebar has correct width and color before Vue loads */
        /* Fallback for sidebar is handled via inline style now */
        
        /* Hide dynamic content until Vue is ready */
        [v-cloak] { display: none !important; }
        
        #app { visibility: visible; }
        
        /* Ensure main content is visible even if Vue is not mounted yet */
        .static-main { visibility: visible !important; opacity: 1 !important; }
        
        /* Dashboard Skeleton Specifics */
        .skeleton-loading { display: block; opacity: 1; transition: opacity 0.3s ease-out; }
        
        /* Ensure skeleton is visible */
        .skeleton-loading:not([v-cloak]) { display: block !important; }
        
        /* Skeleton Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .bg-blue-600-skeleton { background-color: #2563eb; } /* Matches blue-600 */
        .bg-white-skeleton { background-color: #ffffff; }
        .border-slate-200-skeleton { border: 1px solid #e2e8f0; }

        /* Hide skeletons when Vue is ready (Vue handles this via v-if="!isMounted", but CSS backup helps) */
        /* Note: We can't purely rely on CSS to hide because Vue removes elements. 
           But we can ensure they are visible initially. */
        .org-tree ul { padding-top: 20px; position: relative; transition: all 0.5s; }
        .org-tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 20px 5px 0 5px; transition: all 0.5s; }

        /* CRITICAL SKELETON CSS (Fallback if Tailwind is slow) */
        @keyframes pulse-fallback {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .animate-pulse { animation: pulse-fallback 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .bg-slate-50 { background-color: #f8fafc; }
        .bg-slate-200 { background-color: #e2e8f0; }
        .bg-slate-700 { background-color: #334155; }
        .bg-slate-800 { background-color: #1e293b; }
        .bg-slate-900 { background-color: #0f172a; }
        .bg-white { background-color: #ffffff; }
        .rounded { border-radius: 0.25rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .rounded-full { border-radius: 9999px; }
        /* Dimensions fallback (approximate) */
        .h-3 { height: 0.75rem; }
        .h-4 { height: 1rem; }
        .h-8 { height: 2rem; }
        .h-10 { height: 2.5rem; }
        .h-32 { height: 8rem; }
        .h-64 { height: 16rem; }
        .w-20 { width: 5rem; }
        .w-8 { width: 2rem; }
        .w-full { width: 100%; }
        .w-1\/3 { width: 33.333333%; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-4 { gap: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
    </style>

    <?php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
            $baseUrl = '/' . $m[1] . '/';
        } else {
            $baseUrl = '/';
        }
    ?>
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans text-slate-800">
    <!-- JS ERROR BOX FOR DEBUGGING -->
    <div id="js-error-box" style="display:none; position:fixed; top:0; left:0; right:0; background:red; color:white; padding:10px; z-index:9999; font-family:monospace; font-size:12px;"></div>

    <div id="app" class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR NAVIGATION (INJECTED) -->
        <!-- Use v-cloak on sidebar container to hide text until ready, but keep background color via class -->
        <?php require_once 'includes/sidebar.php'; ?>

        <!-- MOBILE OVERLAY -->
        <div v-show="isSidebarOpen" @click="closeSidebar" class="fixed inset-0 bg-black/40 z-20 lg:hidden"></div>

        <!-- MAIN CONTENT -->
        <main class="static-main flex-1 flex flex-col relative overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
                <div class="flex items-center gap-3">
                    <button @click="toggleSidebar" class="lg:hidden p-2 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-100">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="text-xl font-bold text-slate-800">Dashboard Portal</h2>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500" v-cloak>{{ currentDate }}</span>
                    <!-- NOTIFICATION HIDDEN TEMPORARILY
                    <div class="relative">
                        <button @click="toggleNotifications" class="p-2 text-slate-400 hover:text-blue-600 relative">
                            <i class="fas fa-bell"></i>
                            <span v-if="notificationCounts && notificationCounts.total > 0" class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white">
                                {{ notificationCounts.total }}
                            </span>
                        </button>
                        <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-100 z-50 overflow-hidden" v-cloak>
                            <div class="p-3 border-b border-slate-100 font-bold text-sm text-slate-700">Notifikasi</div>
                            <div class="max-h-96 overflow-y-auto">
                                <a v-if="notificationCounts.student_incidents > 0" href="modules/counseling/index.php?tab=incidents" class="block p-3 hover:bg-slate-50 border-b border-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center"><i class="fas fa-user-injured text-xs"></i></div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">Laporan Siswa</div>
                                            <div class="text-xs text-slate-500">{{ notificationCounts.student_incidents }} laporan baru</div>
                                        </div>


                                    </div>
                                </a>
                                <a v-if="notificationCounts.counseling_tickets > 0" href="modules/counseling/index.php?tab=tickets" class="block p-3 hover:bg-slate-50 border-b border-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center"><i class="fas fa-heart text-xs"></i></div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">Tiket Konseling</div>
                                            <div class="text-xs text-slate-500">{{ notificationCounts.counseling_tickets }} tiket pending</div>
                                        </div>
                                    </div>
                                </a>
                                <a v-if="notificationCounts.facility_tickets > 0" href="modules/inventory/dashboard.php?tab=tickets" class="block p-3 hover:bg-slate-50 border-b border-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-tools text-xs"></i></div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">Kerusakan Fasilitas</div>
                                            <div class="text-xs text-slate-500">{{ notificationCounts.facility_tickets }} laporan pending</div>
                                        </div>
                                    </div>
                                </a>
                                <a v-if="notificationCounts.vehicle_lending > 0" href="modules/inventory/dashboard.php?tab=vehicle" class="block p-3 hover:bg-slate-50 border-b border-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-car text-xs"></i></div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">Peminjaman Kendaraan</div>
                                            <div class="text-xs text-slate-500">{{ notificationCounts.vehicle_lending }} permohonan pending</div>
                                        </div>
                                    </div>
                                </a>
                                <a v-if="notificationCounts.resource_lending > 0" href="modules/inventory/dashboard.php?tab=room" class="block p-3 hover:bg-slate-50 border-b border-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center"><i class="fas fa-chalkboard text-xs"></i></div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">Peminjaman Ruang/Alat</div>
                                            <div class="text-xs text-slate-500">{{ notificationCounts.resource_lending }} permohonan pending</div>
                                        </div>
                                    </div>
                                </a>
                                <div v-if="notificationCounts.total === 0" class="p-4 text-center text-slate-400 italic text-xs">
                                    Tidak ada notifikasi baru
                                </div>
                            </div>
                        </div>
                    </div>
                    -->
                    <button @click="openIncidentModal" class="p-2 text-red-600 hover:bg-red-50 rounded"><i class="fas fa-exclamation-circle"></i></button>
                    <button class="p-2 text-slate-400 hover:text-blue-600"><i class="fas fa-cog"></i></button>
                </div>
            </header>
            <?php
                $serverName = $_SERVER['SERVER_NAME'] ?? '';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                $isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1');
                $isTest = (!$isLocalEnv) && (preg_match('#^/AIStest/#i', $scriptName) || stripos($serverName, 'test') !== false);
                $envLabel = $isLocalEnv ? 'LOCAL' : ($isTest ? 'TESTING' : 'PRODUCTION');
                $envColor = $isLocalEnv ? 'bg-slate-600' : ($isTest ? 'bg-red-600' : 'bg-emerald-600');
            ?>
            <div class="hidden md:block absolute left-1/2 -translate-x-1/2 top-0 z-20 mt-1">
                <span class="px-4 py-1.5 rounded-full text-sm font-mono font-bold text-white shadow-sm <?php echo $envColor; ?>">
                    <?php echo $envLabel; ?>
                </span>
            </div>

            <!-- DASHBOARD SKELETON (Visible before Vue mounts) -->
            <!-- MATCHING THE EXACT LAYOUT OF THE DASHBOARD TO PREVENT CLS -->
            <div class="skeleton-loading p-6" v-if="!isMounted" style="padding: 1.5rem;">
                <!-- 1. Allowed Modules Banner (Matches Blue Banner) -->
                <div class="mb-6 bg-blue-600-skeleton rounded-xl shadow-sm px-6 py-4 flex items-center justify-between" style="margin-bottom: 1.5rem; border-radius: 0.75rem; padding: 1rem 1.5rem; height: auto; min-height: 4rem;">
                     <div class="flex flex-wrap gap-2" style="display: flex; gap: 0.5rem;">
                        <div class="h-6 w-20 bg-white-skeleton rounded-full animate-pulse" style="height: 1.5rem; width: 5rem; opacity: 0.3; border-radius: 9999px;"></div>
                        <div class="h-6 w-24 bg-white-skeleton rounded-full animate-pulse" style="height: 1.5rem; width: 6rem; opacity: 0.3; border-radius: 9999px;"></div>
                        <div class="h-6 w-16 bg-white-skeleton rounded-full animate-pulse" style="height: 1.5rem; width: 4rem; opacity: 0.3; border-radius: 9999px;"></div>
                        <div class="h-6 w-20 bg-white-skeleton rounded-full animate-pulse" style="height: 1.5rem; width: 5rem; opacity: 0.3; border-radius: 9999px;"></div>
                        <div class="h-6 w-14 bg-white-skeleton rounded-full animate-pulse" style="height: 1.5rem; width: 3.5rem; opacity: 0.3; border-radius: 9999px;"></div>
                     </div>
                </div>

                <!-- 2. Personal Dashboard Welcome Banner (White) -->
                <div class="bg-white-skeleton rounded-xl p-6 mb-8 shadow-sm border border-slate-200-skeleton flex justify-between items-center relative overflow-hidden" style="margin-bottom: 2rem; border-radius: 0.75rem; padding: 1.5rem; height: 130px; background-color: white; border: 1px solid #e2e8f0;">
                     <div class="z-10 relative w-full">
                        <div class="h-4 w-32 bg-slate-200 rounded mb-3 animate-pulse" style="height: 1rem; width: 8rem; margin-bottom: 0.75rem; background-color: #e2e8f0;"></div>
                        <div class="h-8 w-64 bg-slate-300 rounded mb-2 animate-pulse" style="height: 2rem; width: 16rem; margin-bottom: 0.5rem; background-color: #cbd5e1;"></div>
                        <div class="h-4 w-48 bg-slate-100 rounded animate-pulse" style="height: 1rem; width: 12rem; background-color: #f1f5f9;"></div>
                     </div>
                     <div class="hidden md:block h-16 w-16 bg-slate-200 rounded-lg animate-pulse" style="height: 4rem; width: 4rem; background-color: #e2e8f0;"></div>
                </div>
                
                <!-- 3. Main Grid Skeleton (Matches lg:grid-cols-3) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                    
                    <!-- Left Column (Agenda & Tasks) -->
                    <div class="lg:col-span-2 space-y-8" style="grid-column: span 2;">
                        <!-- Agenda Skeleton -->
                        <div class="bg-white-skeleton rounded-xl shadow-sm border border-slate-200-skeleton p-6 h-96" style="border-radius: 0.75rem; padding: 1.5rem; height: 24rem; margin-bottom: 2rem; background-color: white; border: 1px solid #e2e8f0;">
                            <div class="flex justify-between items-center mb-6" style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                                <div class="h-6 w-40 bg-slate-200 rounded" style="height: 1.5rem; width: 10rem; background-color: #e2e8f0;"></div>
                                <div class="flex gap-1">
                                    <div class="h-6 w-12 bg-slate-200 rounded" style="height: 1.5rem; width: 3rem;"></div>
                                    <div class="h-6 w-12 bg-slate-100 rounded" style="height: 1.5rem; width: 3rem;"></div>
                                </div>
                            </div>
                            <!-- Calendar Grid Mockup -->
                            <div class="border border-slate-100 rounded-xl overflow-hidden mb-4" style="border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem; border: 1px solid #f1f5f9;">
                                <div class="h-8 bg-slate-50 border-b border-slate-200 flex" style="height: 2rem; background-color: #f8fafc;">
                                    <div style="flex:1; border-right:1px solid #e2e8f0"></div><div style="flex:1; border-right:1px solid #e2e8f0"></div><div style="flex:1; border-right:1px solid #e2e8f0"></div><div style="flex:1; border-right:1px solid #e2e8f0"></div><div style="flex:1; border-right:1px solid #e2e8f0"></div><div style="flex:1; border-right:1px solid #e2e8f0"></div><div style="flex:1"></div>
                                </div>
                                <div class="h-24 bg-white flex" style="height: 6rem; background-color: white;">
                                     <div style="flex:1; border-right:1px solid #f1f5f9"></div><div style="flex:1; border-right:1px solid #f1f5f9"></div><div style="flex:1; border-right:1px solid #f1f5f9"></div><div style="flex:1; border-right:1px solid #f1f5f9"></div><div style="flex:1; border-right:1px solid #f1f5f9"></div><div style="flex:1; border-right:1px solid #f1f5f9"></div><div style="flex:1"></div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="h-4 w-32 bg-slate-200 rounded mb-2" style="height: 1rem; width: 8rem; margin-bottom: 0.5rem;"></div>
                                <div class="h-10 bg-slate-50 rounded border border-slate-100" style="height: 2.5rem; background-color: #f8fafc; border: 1px solid #f1f5f9; margin-bottom: 0.5rem;"></div>
                                <div class="h-10 bg-slate-50 rounded border border-slate-100" style="height: 2.5rem; background-color: #f8fafc; border: 1px solid #f1f5f9;"></div>
                            </div>
                        </div>

                        <!-- Tasks Skeleton -->
                        <div class="bg-white-skeleton rounded-xl shadow-sm border border-slate-200-skeleton p-6 h-64" style="border-radius: 0.75rem; padding: 1.5rem; height: 16rem; background-color: white; border: 1px solid #e2e8f0;">
                            <div class="flex justify-between items-center mb-6" style="margin-bottom: 1.5rem;">
                                <div class="h-6 w-40 bg-slate-200 rounded" style="height: 1.5rem; width: 10rem; background-color: #e2e8f0;"></div>
                            </div>
                            <div class="space-y-3">
                                <div class="h-10 bg-slate-50 rounded border border-slate-100" style="height: 2.5rem; margin-bottom: 0.75rem; background-color: #f8fafc;"></div>
                                <div class="h-10 bg-slate-50 rounded border border-slate-100" style="height: 2.5rem; background-color: #f8fafc;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (Announcements & Classes) -->
                    <div class="space-y-8">
                        <!-- Announcements Skeleton -->
                        <div class="bg-white-skeleton rounded-xl shadow-sm border border-slate-200-skeleton p-6 h-80" style="border-radius: 0.75rem; padding: 1.5rem; height: 20rem; margin-bottom: 2rem; background-color: white; border: 1px solid #e2e8f0;">
                            <div class="h-6 w-48 bg-slate-200 rounded mb-6" style="height: 1.5rem; width: 12rem; margin-bottom: 1.5rem; background-color: #e2e8f0;"></div>
                            <div class="space-y-4">
                                <div class="p-3 border border-slate-100 rounded-lg" style="padding: 0.75rem; border: 1px solid #f1f5f9; border-radius: 0.5rem; margin-bottom: 1rem;">
                                    <div class="h-4 w-full bg-slate-200 rounded mb-2" style="height: 1rem; width: 100%; margin-bottom: 0.5rem; background-color: #e2e8f0;"></div>
                                    <div class="h-3 w-2/3 bg-slate-50 rounded" style="height: 0.75rem; width: 66%; background-color: #f8fafc;"></div>
                                </div>
                                <div class="p-3 border border-slate-100 rounded-lg" style="padding: 0.75rem; border: 1px solid #f1f5f9; border-radius: 0.5rem;">
                                    <div class="h-4 w-full bg-slate-200 rounded mb-2" style="height: 1rem; width: 100%; margin-bottom: 0.5rem; background-color: #e2e8f0;"></div>
                                    <div class="h-3 w-2/3 bg-slate-50 rounded" style="height: 0.75rem; width: 66%; background-color: #f8fafc;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Mandatory Classes Skeleton -->
                        <div class="bg-white-skeleton rounded-xl shadow-sm border border-slate-200-skeleton p-6 h-64" style="border-radius: 0.75rem; padding: 1.5rem; height: 16rem; background-color: white; border: 1px solid #e2e8f0;">
                             <div class="flex justify-between mb-6">
                                <div class="h-6 w-48 bg-slate-200 rounded" style="height: 1.5rem; width: 12rem; background-color: #e2e8f0;"></div>
                             </div>
                             <div class="space-y-3">
                                <div class="h-12 bg-slate-50 rounded border border-slate-100" style="height: 3rem; margin-bottom: 0.75rem; background-color: #f8fafc;"></div>
                                <div class="h-12 bg-slate-50 rounded border border-slate-100" style="height: 3rem; background-color: #f8fafc;"></div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <!-- Removing animate-fade-in to prevent layout shift during transition -->
            <div class="flex-1 overflow-y-auto p-6 bg-slate-50" v-cloak v-if="isMounted">
                <div class="mb-6 bg-blue-600 text-white rounded-xl shadow-sm px-6 py-4 flex items-center justify-between">
               
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($allowedMods as $mod): ?>
                            <span class="bg-white text-blue-700 px-3 py-1 rounded-full text-xs font-bold"><?php echo strtoupper($mod); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                
                <!-- VIEW: JAM PELAJARAN (NEW) -->
                <div v-if="currentPage === 'timeslots'">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded uppercase">{{ getUnitName(currentUnit) }}</span>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-800">Pengaturan Jam Pelajaran</h2>
                        </div>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Tambah Slot</button>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase">
                                <tr>
                                    <th class="px-6 py-3">Jam Ke-</th>
                                    <th class="px-6 py-3">Waktu</th>
                                    <th class="px-6 py-3">Durasi</th>
                                    <th class="px-6 py-3">Jenis</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="slot in activeTimeslots" :key="slot.id" :class="slot.type === 'BREAK' ? 'bg-orange-50' : 'hover:bg-slate-50'">
                                    <td class="px-6 py-4 font-bold text-slate-700">{{ slot.order }}</td>
                                    <td class="px-6 py-4 font-mono">{{ slot.start }} - {{ slot.end }}</td>
                                    <td class="px-6 py-4">{{ slot.duration }} Menit</td>
                                    <td class="px-6 py-4">
                                        <span v-if="slot.type === 'KBM'" class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">Pelajaran</span>
                                        <span v-else class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs font-bold">Istirahat</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-slate-400 hover:text-blue-600"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VIEW: PENGATURAN SEKOLAH (CORE) -->
                <div v-if="currentPage === 'settings'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-800">Pengaturan Sekolah</h2>
                        <button @click="saveSettings" :disabled="adminLoading" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                            <i class="fas fa-save mr-2" :class="{'fa-spin': adminLoading}"></i>
                            {{ adminLoading ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Sidebar Settings -->
                        <div class="space-y-2">
                            <button @click="activeSettingTab = 'general'" :class="activeSettingTab === 'general' ? 'bg-white border-blue-500 text-blue-600 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-200'" class="w-full text-left px-4 py-3 rounded-lg border-l-4 font-medium transition-all">
                                <i class="fas fa-school w-6"></i> Identitas Sekolah
                            </button>
                            <button @click="activeSettingTab = 'foundation'" :class="activeSettingTab === 'foundation' ? 'bg-white border-blue-500 text-blue-600 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-200'" class="w-full text-left px-4 py-3 rounded-lg border-l-4 font-medium transition-all">
                                <i class="fas fa-building w-6"></i> Data Yayasan
                            </button>
                            <button @click="activeSettingTab = 'units'" :class="activeSettingTab === 'units' ? 'bg-white border-blue-500 text-blue-600 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-200'" class="w-full text-left px-4 py-3 rounded-lg border-l-4 font-medium transition-all">
                                <i class="fas fa-sitemap w-6"></i> Manajemen Unit
                            </button>
                            <button @click="activeSettingTab = 'appearance'" :class="activeSettingTab === 'appearance' ? 'bg-white border-blue-500 text-blue-600 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-200'" class="w-full text-left px-4 py-3 rounded-lg border-l-4 font-medium transition-all">
                                <i class="fas fa-paint-brush w-6"></i> Tampilan (Header/Footer)
                            </button>
                            <button @click="activeSettingTab = 'integrasi-wa'" :class="activeSettingTab === 'integrasi-wa' ? 'bg-white border-green-500 text-green-600 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-200'" class="w-full text-left px-4 py-3 rounded-lg border-l-4 font-medium transition-all">
                                <i class="fas fa-comments w-6"></i> Integrasi WhatsApp
                            </button>
                            <button @click="activeSettingTab = 'integrasi-google'" :class="activeSettingTab === 'integrasi-google' ? 'bg-white border-purple-500 text-purple-600 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-200'" class="w-full text-left px-4 py-3 rounded-lg border-l-4 font-medium transition-all">
                                <i class="fas fa-brain w-6"></i> Integrasi Google AI
                            </button>
                        </div>

                        <!-- Form Content -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- TAB: GENERAL -->
                            <div v-if="activeSettingTab === 'general'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 animate-fade">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Identitas Umum</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Logo Sekolah</label>
                                        <div class="flex items-center gap-4">
                                            <div class="w-20 h-20 bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                                <img v-if="schoolSettings.logo_url" :src="schoolSettings.logo_url" class="w-full h-full object-contain">
                                                <i v-else class="fas fa-image text-slate-400 text-2xl"></i>
                                            </div>
                                            <div>
                                                <input type="file" @change="handleLogoUpload" accept=".jpg,.jpeg,.png" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                <p class="text-xs text-slate-400 mt-1">Format: .jpg, .png (Max 2MB)</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Sekolah / Kampus</label>
                                        <input type="text" v-model="schoolSettings.name" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">NPSN (Induk)</label>
                                        <input type="text" v-model="schoolSettings.npsn" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Alamat Lengkap</label>
                                        <textarea rows="3" v-model="schoolSettings.address" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">No. Telepon</label>
                                            <input type="text" v-model="schoolSettings.phone" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">Email Resmi</label>
                                            <input type="email" v-model="schoolSettings.email" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: UNITS -->
                            <div v-if="activeSettingTab === 'units'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 animate-fade">
                                <div class="flex justify-between items-center mb-4 border-b pb-2">
                                    <h3 class="font-bold text-lg">Unit Pendidikan</h3>
                                    <button @click="openAddUnitModal" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded text-slate-600"><i class="fas fa-plus mr-1"></i> Tambah Unit</button>
                                </div>
                                <div class="space-y-3">
                                    <!-- Filter out FOUNDATION type units -->
                                    <div v-for="unit in unitList.filter(u => u.unit_level !== 'YAYASAN')" :key="unit.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-lg hover:bg-slate-50">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded flex items-center justify-center font-bold text-xs">{{ unit.prefix || unit.unit_level }}</div>
                                            <div>
                                                <h4 class="font-bold text-sm">{{ unit.name }}</h4>
                                                <p class="text-xs text-slate-500">Level: {{ unit.unit_level }} | Alamat: {{ unit.address || '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="editUnit(unit)" class="text-slate-400 hover:text-blue-600"><i class="fas fa-edit"></i></button>
                                            <button @click="deleteUnit(unit.id)" class="text-slate-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div v-if="unitList.filter(u => u.unit_level !== 'YAYASAN').length === 0" class="text-center py-4 text-slate-500 text-sm">Belum ada data unit sekolah.</div>
                                </div>
                            </div>

                            <!-- TAB: FOUNDATION -->
                            <div v-if="activeSettingTab === 'foundation'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 animate-fade">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Data Yayasan</h3>
                                <div v-for="unit in unitList.filter(u => u.unit_level === 'YAYASAN')" :key="unit.id" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Yayasan</label>
                                        <div class="flex gap-2">
                                            <input type="text" disabled :value="unit.name" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50">
                                            <button @click="editUnit(unit)" class="bg-blue-600 text-white px-4 rounded-lg text-sm">Edit Data</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Alamat Kantor Yayasan</label>
                                        <textarea disabled :value="unit.address" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50" rows="3"></textarea>
                                    </div>
                                </div>
                                <div v-if="unitList.filter(u => u.unit_level === 'YAYASAN').length === 0" class="text-center py-8 text-slate-500">
                                    <p>Data Yayasan belum diset.</p>
                                    <button @click="openAddUnitModal" class="mt-2 text-blue-600 hover:underline text-sm">Buat Data Yayasan</button>
                                </div>
                            </div>

                            <!-- TAB: APPEARANCE (HEADER/FOOTER) -->
                            <div v-if="activeSettingTab === 'appearance'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 animate-fade">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Kustomisasi Tampilan</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Logo Sekolah (URL)</label>
                                        <div class="flex gap-2">
                                            <input type="text" v-model="schoolSettings.logo_url" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="https://...">
                                            <button class="bg-slate-100 px-3 rounded-lg border border-slate-300 text-sm">Upload</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Footer Text</label>
                                        <input type="text" v-model="schoolSettings.footer_text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                        <p class="text-xs text-slate-400 mt-1">Teks yang muncul di bagian bawah setiap halaman/laporan.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: INTEGRASI WHATSAPP -->
                            <div v-if="activeSettingTab === 'integrasi-wa'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 animate-fade">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Integrasi WhatsApp Gateway</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">WA API URL</label>
                                        <input type="text" v-model="schoolSettings.wa_api_url" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="http(s)://server-gateway/path">
                                        <p class="text-xs text-slate-400 mt-1">Contoh: https://wa-gateway.example.com/api/send</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">WA API Token</label>
                                        <input type="text" v-model="schoolSettings.wa_api_token" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Bearer Token">
                                        <p class="text-xs text-slate-400 mt-1">Token otentikasi ke gateway (Bearer).</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Target WA (Grup/Nomor)</label>
                                        <input type="text" v-model="schoolSettings.wa_security_target" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="628xxxx atau ID grup">
                                        <p class="text-xs text-slate-400 mt-1">Target default untuk notifikasi Security.</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2">
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Pesan Uji Kirim</label>
                                        <input type="text" v-model="waTestMessage" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Contoh: Uji coba notifikasi dari AIS">
                                        <div>
                                            <button @click="testWaNotification" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700">
                                                <i class="fas fa-paper-plane mr-2"></i> Uji Kirim WA
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: INTEGRASI GOOGLE AI -->
                            <div v-if="activeSettingTab === 'integrasi-google'" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 animate-fade">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Integrasi Google Gemini AI</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Google Gemini API Key</label>
                                        <div class="flex gap-2">
                                            <input type="text" v-model="schoolSettings.google_gemini_api_key" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono" placeholder="AIza...">
                                            <button class="bg-slate-100 px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-200" title="Test Connection (Future)">
                                                <i class="fas fa-plug"></i>
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">API Key untuk layanan AI Google Gemini. Dapatkan di <a href="https://aistudio.google.com/" target="_blank" class="text-blue-600 hover:underline">Google AI Studio</a>.</p>
                                    </div>
                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-info-circle text-blue-500"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-blue-700">
                                                    Fitur AI akan menggunakan model <strong>gemini-pro</strong> untuk membantu analisis data sekolah, pembuatan soal, dan asisten virtual.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW: MANAJEMEN USER -->
                <div v-if="currentPage === 'users'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-800">Manajemen User</h2>
                        <button @click="openAddUserModal" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-user-plus mr-2"></i>Tambah User</button>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <!-- Filter Bar -->
                        <div class="p-4 border-b border-slate-100 flex gap-4 bg-slate-50">
                            <input type="text" placeholder="Cari nama atau username..." class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <select class="border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white"><option>Semua Role</option><option>Super Admin</option><option>Guru</option><option>Staff</option></select>
                        </div>
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase">
                                <tr>
                                    <th class="px-6 py-3">User Info</th>
                                    <th class="px-6 py-3">Pegawai</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="adminLoading">
                                    <td colspan="5" class="p-4 text-center">Loading...</td>
                                </tr>
                                <tr v-for="user in userList" :key="user.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 flex items-center gap-3">
                                        <img :src="'https://ui-avatars.com/api/?name=' + user.username + '&background=random'" class="w-8 h-8 rounded-full">
                                        <div>
                                            <div class="font-bold text-slate-800">{{ user.username }}</div>
                                            <div class="text-xs text-slate-500">{{ user.email || '-' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-700">{{ user.person_name || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-slate-800 text-white px-2 py-1 rounded text-[10px] font-bold uppercase">{{ user.role }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="user.status === 'ACTIVE' ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full inline-block mr-1"></span> 
                                        {{ user.status }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs text-slate-500">
                                        <button @click="editUser(user)" class="text-blue-600 hover:underline mr-2">Edit</button>
                                        <button @click="deleteUser(user.id)" class="text-red-600 hover:underline">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VIEW: PROFILE -->
                <div v-if="currentPage === 'profile'">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Ubah Password</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Password Saat Ini</label>
                                        <input type="password" v-model="passwordForm.current" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Password Baru</label>
                                        <input type="password" v-model="passwordForm.new1" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                                        <input type="password" v-model="passwordForm.new2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div class="pt-2">
                                        <button @click="changePassword" :disabled="profileLoading" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                                            <i class="fas fa-key mr-2" :class="{'fa-spin': profileLoading}"></i>
                                            {{ profileLoading ? 'Memproses...' : 'Simpan Password' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Ubah Username</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Username Baru</label>
                                        <input type="text" v-model="usernameForm.value" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                    <div class="pt-2">
                                        <button @click="changeUsername" :disabled="profileLoading" class="bg-amber-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-700 disabled:opacity-50">
                                            <i class="fas fa-user-edit mr-2" :class="{'fa-spin': profileLoading}"></i>
                                            {{ profileLoading ? 'Memproses...' : 'Simpan Username' }}
                                        </button>
                                    </div>
                                    <p class="text-[12px] text-amber-700 bg-amber-50 border border-amber-100 rounded p-3">
                                        Mengubah username dapat memengaruhi proses login. Pastikan Anda mengingat username baru.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-4">
                                        <img :src="'https://ui-avatars.com/api/?name=' + (myProfile.person_name || myProfile.username || 'User') + '&background=0D8ABC&color=fff&size=64'" class="w-14 h-14 rounded-lg shadow-sm">
                                        <div>
                                            <h3 class="font-bold text-lg text-slate-800">{{ myProfile.person_name || myProfile.username }}</h3>
                                            <p class="text-xs text-slate-500">User name: {{ myProfile.username }} • Role: {{ myProfile.role }}</p>
                                        </div>
                                    </div>
                                    <button v-if="!isProfileEditing" @click="toggleEditProfile" class="text-sm bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 rounded-lg transition-colors">
                                        <i class="fas fa-edit mr-1"></i> Edit Data
                                    </button>
                                </div>

                                <!-- VIEW MODE -->
                                <div v-if="!isProfileEditing" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-1">Nama</label>
                                        <div class="text-sm font-medium text-slate-800">{{ myProfile.person_name || '-' }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-1">NIK/NIP</label>
                                        <div class="text-sm font-medium text-slate-800">{{ myProfile.identity_number || '-' }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
                                        <div class="text-sm font-medium text-slate-800">{{ myProfile.person_email || myProfile.user_email || '-' }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-1">No. HP</label>
                                        <div class="text-sm font-medium text-slate-800">{{ myProfile.person_phone || '-' }}</div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-slate-500 mb-1">Alamat</label>
                                        <div class="text-sm font-medium text-slate-800">{{ myProfile.person_address || '-' }}</div>
                                    </div>
                                </div>

                                <!-- EDIT MODE -->
                                <div v-else class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">No. HP</label>
                                            <input type="text" v-model="profileForm.phone" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="08...">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">Email</label>
                                            <input type="email" v-model="profileForm.email" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-bold text-slate-700 mb-1">Alamat</label>
                                            <textarea v-model="profileForm.address" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                                        <button @click="cancelEditProfile" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                                        <button @click="saveProfileData" :disabled="profileLoading" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
                                            {{ profileLoading ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- VIEW: DASHBOARD (PERSONAL) -->
                <div v-if="currentPage === 'dash'">
                    <!-- Welcome Banner -->
                    <div class="bg-white rounded-xl p-6 mb-8 shadow-sm border border-slate-200 flex justify-between items-center">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded uppercase">PERSONAL DASHBOARD</span>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-1"><?php echo htmlspecialchars($displayName); ?></h3>
                            <p class="text-slate-500">Berikut adalah agenda dan fokus kerja Anda hari ini.</p>
                        </div>
                        <div class="hidden md:block">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($displayName); ?>&background=0D8ABC&color=fff&size=64" class="rounded-lg shadow-sm">
                        </div>
                    </div>

                   

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-12">
                        <!-- Left Column: Agenda & Focus -->
                        <div class="lg:col-span-2 space-y-8">
                            
                            <!-- Agenda Kegiatan -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="font-bold text-lg text-slate-800"><i class="far fa-calendar-alt mr-2 text-blue-600"></i>Agenda Kegiatan</h3>
                                    <div class="flex bg-slate-100 rounded-lg p-1">
                                        <button class="px-3 py-1 text-xs font-bold rounded-md bg-white shadow-sm text-slate-800">Bulan</button>
                                        <button class="px-3 py-1 text-xs font-bold rounded-md text-slate-500 hover:bg-white hover:shadow-sm transition-all">Minggu</button>
                                    </div>
                                </div>
                                <!-- Calendar Mockup -->
                                <div class="border border-slate-100 rounded-xl overflow-hidden">
                                    <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-center py-2">
                                        <div class="text-xs font-bold text-slate-500">SEN</div>
                                        <div class="text-xs font-bold text-slate-500">SEL</div>
                                        <div class="text-xs font-bold text-slate-500">RAB</div>
                                        <div class="text-xs font-bold text-slate-500">KAM</div>
                                        <div class="text-xs font-bold text-slate-500">JUM</div>
                                        <div class="text-xs font-bold text-red-500">SAB</div>
                                        <div class="text-xs font-bold text-red-500">MIN</div>
                                    </div>
                                    <div class="grid grid-cols-7 text-center bg-white">
                                        <!-- Week 1 -->
                                        <div class="h-24 border-b border-r border-slate-100 p-1 text-slate-300">29</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 text-slate-300">30</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">1</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">2</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">3</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-red-400 bg-slate-50">4</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-red-400 bg-slate-50">5</div>
                                         <!-- Week 2 -->
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">6</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">7</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">8</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">9</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-slate-700">10</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-red-400 bg-slate-50">11</div>
                                        <div class="h-24 border-b border-r border-slate-100 p-1 font-bold text-red-400 bg-slate-50">12</div>
                                    </div>
                                </div>
                                <!-- Agenda List (Auto from Akademik) -->
                                <div class="mt-4">
                                    <h4 class="text-sm font-bold text-slate-700 mb-2">Agenda Bulan Ini</h4>
                                    <div id="agendaList" class="space-y-2">
                                        <div class="text-slate-400 italic text-sm">Memuat agenda...</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Focus / Tasks -->
                             <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="font-bold text-lg text-slate-800"><i class="fas fa-check-square mr-2 text-green-600"></i>Task & Fokus Saya</h3>
                                    <button class="text-sm text-blue-600 hover:underline">Lihat Semua</button>
                                </div>
                                <div id="tasksContainer" class="space-y-2">
                                    <div class="text-center text-slate-400 italic py-4">Memuat tugas...</div>
                                </div>
                             </div>

                        </div>

                        <!-- Right Column: Announcements & Others -->
                        <div class="space-y-8">
                             <!-- Pengumuman Penting -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <h3 class="font-bold text-lg text-slate-800 mb-4"><i class="fas fa-bullhorn mr-2 text-orange-500"></i>Pengumuman Penting</h3>
                                <div id="annBox" class="space-y-2">
                                    <div class="text-center text-slate-400 italic py-4">Memuat pengumuman...</div>
                                </div>
                            </div>

                            

                            <!-- Indikator Kelas Wajib Pegawai -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="font-bold text-lg text-slate-800"><i class="fas fa-graduation-cap mr-2 text-indigo-600"></i>Indikator Kelas Wajib Pegawai</h3>
                                    <span class="text-[10px] font-bold text-slate-400">Terhubung ke LMS</span>
                                </div>
                                <div id="mandatoryClassesBox" class="space-y-2">
                                    <div class="text-center text-slate-400 italic py-4">Memuat daftar kelas...</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- VIEW: DATA SISWA -->
                <div v-if="currentPage === 'students'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-800">Data Siswa</h2>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Siswa Baru</button>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase">
                                <tr>
                                    <th class="px-6 py-3">NIS</th>
                                    <th class="px-6 py-3">Nama Siswa</th>
                                    <th class="px-6 py-3">Kelas</th>
                                    <th class="px-6 py-3">Unit</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono text-slate-500">SD25001</td>
                                    <td class="px-6 py-4 font-medium text-slate-800">Adit Sopo</td>
                                    <td class="px-6 py-4"><span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">1-Abu Bakar</span></td>
                                    <td class="px-6 py-4">SD</td>
                                    <td class="px-6 py-4"><span class="text-green-600 text-xs font-bold"><i class="fas fa-check-circle"></i> Aktif</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button>
                                        <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono text-slate-500">SMA25001</td>
                                    <td class="px-6 py-4 font-medium text-slate-800">Cinta Laura</td>
                                    <td class="px-6 py-4"><span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold">X-MIPA-1</span></td>
                                    <td class="px-6 py-4">SMA</td>
                                    <td class="px-6 py-4"><span class="text-green-600 text-xs font-bold"><i class="fas fa-check-circle"></i> Aktif</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button>
                                        <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VIEW: ACADEMIC PORTAL (GRID) -->
                <div v-if="currentPage === 'academic-portal'">
                    <div class="space-y-8">
                        <!-- Perencanaan (Blue) -->
                        <div class="rounded-xl overflow-hidden shadow-md">
                            <div class="bg-gradient-to-r from-blue-400 to-indigo-500 p-4 text-white">
                                <h3 class="text-lg font-bold tracking-wide">Perencanaan Kegiatan Sekolah</h3>
                            </div>
                            <div class="bg-white p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <a href="#" @click.prevent="navigate('academic-year')" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-blue-200 group-hover:text-blue-600 transition-all"><i class="fas fa-calendar-alt"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Tahun Ajaran</span>
                                </a>
                                <a href="#" @click.prevent="navigate('classes')" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-blue-200 group-hover:text-blue-600 transition-all"><i class="fas fa-chalkboard"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Data Kelas</span>
                                </a>
                                <a href="#" @click.prevent="navigate('subjects')" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-blue-200 group-hover:text-blue-600 transition-all"><i class="fas fa-list"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">List Mapel</span>
                                </a>
                                <a href="#" @click.prevent="navigate('time-slots')" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-blue-200 group-hover:text-blue-600 transition-all"><i class="fas fa-clock"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Jam Pelajaran</span>
                                </a>
                                <a href="#" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-blue-200 group-hover:text-blue-600 transition-all"><i class="fas fa-tasks"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Program Asesmen</span>
                                </a>
                            </div>
                        </div>

                        <!-- Pelaksanaan (Green) -->
                        <div class="rounded-xl overflow-hidden shadow-md">
                            <div class="bg-gradient-to-r from-emerald-400 to-teal-500 p-4 text-white">
                                <h3 class="text-lg font-bold tracking-wide">Pelaksanaan dan Evaluasi KBM</h3>
                            </div>
                            <div class="bg-white p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <a href="#" @click.prevent="navigate('schedule')" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-emerald-200 group-hover:text-emerald-600 transition-all"><i class="far fa-calendar-alt"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Jadwal Pelajaran</span>
                                </a>
                                <a href="#" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-emerald-200 group-hover:text-emerald-600 transition-all"><i class="fas fa-user-check"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Presensi Harian</span>
                                </a>
                                <a href="#" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-emerald-200 group-hover:text-emerald-600 transition-all"><i class="fas fa-file-contract"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">Nilai Mapel</span>
                                </a>
                                <a href="#" class="group flex flex-col items-center text-center p-3 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 hover:shadow-sm">
                                    <div class="w-12 h-12 mb-3 rounded-lg bg-white border-2 border-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 group-hover:border-emerald-200 group-hover:text-emerald-600 transition-all"><i class="fas fa-file-alt"></i></div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">e-Raport</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-VIEW: ACADEMIC YEAR -->
                <div v-if="currentPage === 'academic-year'">
                    <div class="flex items-center gap-4 mb-6">
                        <button @click="navigate('academic-portal')" class="bg-white border border-slate-300 text-slate-600 w-10 h-10 rounded-lg hover:bg-slate-50"><i class="fas fa-arrow-left"></i></button>
                        <h2 class="text-2xl font-bold text-slate-800">Tahun Ajaran</h2>
                        <div class="flex-1 text-right">
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Buat Baru</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase"><tr><th class="px-6 py-3">Nama</th><th class="px-6 py-3">Mulai</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="ay in academicYears" :key="ay.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold">{{ ay.name }}</td>
                                    <td class="px-6 py-4">{{ ay.start }}</td>
                                    <td class="px-6 py-4"><span :class="ay.status === 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'" class="px-2 py-1 rounded text-xs font-bold">{{ ay.status }}</span></td>
                                    <td class="px-6 py-4 text-right"><button class="text-blue-600"><i class="fas fa-edit"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-VIEW: SUBJECTS -->
                <div v-if="currentPage === 'subjects'">
                    <div class="flex items-center gap-4 mb-6">
                        <button @click="navigate('academic-portal')" class="bg-white border border-slate-300 text-slate-600 w-10 h-10 rounded-lg hover:bg-slate-50"><i class="fas fa-arrow-left"></i></button>
                        <h2 class="text-2xl font-bold text-slate-800">Mata Pelajaran</h2>
                        <div class="flex-1 text-right">
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Tambah Mapel</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase"><tr><th class="px-6 py-3">Kode</th><th class="px-6 py-3">Nama Mapel</th><th class="px-6 py-3">Tipe</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="sub in activeSubjects" :key="sub.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono text-slate-500">{{ sub.code }}</td>
                                    <td class="px-6 py-4 font-bold">{{ sub.name }}</td>
                                    <td class="px-6 py-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-bold">{{ sub.type }}</span></td>
                                    <td class="px-6 py-4 text-right"><button class="text-blue-600"><i class="fas fa-edit"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-VIEW: TIME SLOTS (JAM PELAJARAN) -->
                <div v-if="currentPage === 'time-slots'">
                    <div class="flex items-center gap-4 mb-6">
                        <button @click="navigate('academic-portal')" class="bg-white border border-slate-300 text-slate-600 w-10 h-10 rounded-lg hover:bg-slate-50"><i class="fas fa-arrow-left"></i></button>
                        <h2 class="text-2xl font-bold text-slate-800">Setup Jam Pelajaran</h2>
                        <div class="flex-1 text-right">
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Tambah Slot</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase">
                                <tr>
                                    <th class="px-6 py-3">Jam Ke-</th>
                                    <th class="px-6 py-3">Waktu Mulai</th>
                                    <th class="px-6 py-3">Waktu Selesai</th>
                                    <th class="px-6 py-3">Durasi</th>
                                    <th class="px-6 py-3">Keterangan</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="slot in activeTimeslots" :key="slot.id" :class="slot.isBreak ? 'bg-amber-50' : 'hover:bg-slate-50'">
                                    <td class="px-6 py-4 font-bold text-slate-700">{{ slot.order }}</td>
                                    <td class="px-6 py-4 font-mono">{{ slot.start }}</td>
                                    <td class="px-6 py-4 font-mono">{{ slot.end }}</td>
                                    <td class="px-6 py-4">{{ slot.duration }} Menit</td>
                                    <td class="px-6 py-4">
                                        <span v-if="slot.isBreak" class="text-amber-600 font-bold uppercase text-xs tracking-wider"><i class="fas fa-coffee mr-1"></i> Istirahat</span>
                                        <span v-else class="text-slate-500 text-xs">KBM Efektif</span>
                                    </td>
                                    <td class="px-6 py-4 text-right"><button class="text-blue-600"><i class="fas fa-edit"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VIEW: KELAS & ROMBEL -->
                <div v-if="currentPage === 'classes'">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-4">
                            <button @click="navigate('academic-portal')" class="bg-white border border-slate-300 text-slate-600 w-10 h-10 rounded-lg hover:bg-slate-50"><i class="fas fa-arrow-left"></i></button>
                            <h2 class="text-2xl font-bold text-slate-800">Manajemen Kelas</h2>
                        </div>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Buat Kelas</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="cls in activeClasses" :key="cls.id" class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center font-bold">{{ cls.level }}</div>
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-bold uppercase">{{ currentUnit === 'all' ? 'SD' : currentUnit }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">{{ cls.name }}</h3>
                            <p class="text-sm text-slate-500 mb-4"><i class="fas fa-user-tie mr-1"></i> Wali: {{ cls.homeroom }}</p>
                            <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-3">
                                <span class="text-slate-600">{{ cls.students }} Siswa</span>
                                <button class="text-blue-600 font-medium hover:underline">Detail</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW: JADWAL PELAJARAN -->
                <div v-if="currentPage === 'schedule'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-800">Jadwal Pelajaran</h2>
                        <div class="flex gap-2">
                            <select class="bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm"><option>1-Abu Bakar (SD)</option><option>X-MIPA-1 (SMA)</option></select>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-print mr-2"></i>Cetak</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-6 divide-x divide-slate-200 border-b border-slate-200 bg-slate-50 text-center font-bold text-slate-600 text-sm">
                            <div class="py-3">JAM</div>
                            <div class="py-3">SENIN</div>
                            <div class="py-3">SELASA</div>
                            <div class="py-3">RABU</div>
                            <div class="py-3">KAMIS</div>
                            <div class="py-3">JUMAT</div>
                        </div>
                        <!-- Row 1 -->
                        <div class="grid grid-cols-6 divide-x divide-slate-200 border-b border-slate-100 text-sm">
                            <div class="py-4 text-center font-mono text-slate-500 bg-slate-50">07:30 - 09:00</div>
                            <div class="p-2">
                                <div class="bg-blue-100 text-blue-700 p-2 rounded border border-blue-200 text-xs">
                                    <div class="font-bold">Matematika</div>
                                    <div>Pak Budi</div>
                                </div>
                            </div>
                            <div class="p-2">
                                <div class="bg-green-100 text-green-700 p-2 rounded border border-green-200 text-xs">
                                    <div class="font-bold">Olahraga</div>
                                    <div>Pak Raga</div>
                                </div>
                            </div>
                            <div class="p-2"></div>
                            <div class="p-2"></div>
                            <div class="p-2"></div>
                        </div>
                        <!-- Row 2 -->
                        <div class="grid grid-cols-6 divide-x divide-slate-200 border-b border-slate-100 text-sm">
                            <div class="py-4 text-center font-mono text-slate-500 bg-slate-50">09:00 - 09:30</div>
                            <div class="col-span-5 bg-slate-100 flex items-center justify-center text-slate-400 text-xs uppercase tracking-widest font-bold">Istirahat</div>
                        </div>
                    </div>
                </div>

                <!-- APP FOOTER -->
                <div class="mt-12 py-6 border-t border-slate-200 text-center">
                    <?php 
                    $v = file_exists('config/version.php') ? require 'config/version.php' : ['version' => '1.0.0', 'build_date' => date('Y-m-d')]; 
                    ?>
                    <p class="text-xs text-slate-400 font-medium">
                        <?php echo $v['app_name'] ?? 'SekolahOS'; ?> 
                        <a href="<?php echo (($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') && (($_SERVER['SERVER_PORT'] ?? 80) != 8000) ? '/AIS/' : '/'); ?>modules/admin/version_log.php" class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 font-mono text-[10px] ml-1 hover:bg-slate-300 transition-colors" title="Lihat Log Perubahan">v<?php echo $v['version']; ?></a>
                    </p>
                    <p class="text-[10px] text-slate-300 mt-1">Build: <?php echo $v['build_date']; ?> | <?php echo $v['codename'] ?? ''; ?></p>
                </div>
            </div>

        </main>

        <!-- USER MODAL -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ isUserEdit ? 'Edit User' : 'Tambah User Baru' }}</h3>
                <button @click="showUserModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Username</label>
                    <input type="text" v-model="userForm.username" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Email</label>
                    <input type="email" v-model="userForm.email" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Password</label>
                    <input type="password" v-model="userForm.password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" :placeholder="isUserEdit ? 'Kosongkan jika tidak diubah' : ''">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                    <select v-model="userForm.role" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="SUPERADMIN">Super Admin</option>
                        <option value="ADMIN">Admin</option>
                        <option value="ACADEMIC">Akademik</option>
                        <option value="FOUNDATION">Yayasan</option>
                        <option value="MANAGERIAL">Managerial</option>
                        <option value="FINANCE">Keuangan</option>
                        <option value="POS">POS</option>
                        <option value="SECURITY">Security</option>
                        <option value="CLEANING">Kebersihan</option>
                        <option value="STAFF">Staff</option>
                        <option value="TEACHER">Guru</option>
                        <option value="STUDENT">Siswa</option>
                        <option value="PARENT">Orang Tua</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Status</label>
                    <select v-model="userForm.status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ACTIVE">Active</option>
                        <option value="SUSPENDED">Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Pegawai Terkait (Link ke Personnel)</label>
                    <select v-model="userForm.people_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option :value="null">Tidak terhubung</option>
                        <option v-for="p in staffLookup" :key="p.id" :value="p.id">{{ p.name }}{{ p.division ? (' — ' + p.division) : '' }}</option>
                    </select>
                </div>
                <div v-if="(userForm.role || '').toUpperCase() !== 'SUPERADMIN' && (userForm.role || '').toUpperCase() !== 'ADMIN'">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Modul Akses Khusus (override)</label>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'academic'" v-model="userForm.access_modules"> Akademik</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'finance'" v-model="userForm.access_modules"> Keuangan</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'hr'" v-model="userForm.access_modules"> Kepegawaian</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'library'" v-model="userForm.access_modules"> Perpustakaan</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'executive'" v-model="userForm.access_modules"> Executive</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'foundation'" v-model="userForm.access_modules"> Yayasan</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'boarding'" v-model="userForm.access_modules"> Asrama</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'principal'" v-model="userForm.access_modules"> Principal</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'pos'" v-model="userForm.access_modules"> POS</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'payroll'" v-model="userForm.access_modules"> Payroll</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'counseling'" v-model="userForm.access_modules"> BK</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'kiosk'" v-model="userForm.access_modules"> Kiosk</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'inventory'" v-model="userForm.access_modules"> Inventory</label>
                        <label class="flex items-center gap-2"><input type="checkbox" :value="'workspace'" v-model="userForm.access_modules"> Workspace</label>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Kosongkan untuk mengikuti izin default sesuai role.</p>
                </div>
                <div v-else class="text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded p-3">
                    Role ADMIN/SUPERADMIN memiliki akses penuh. Modul override tidak diperlukan.
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-2">
                <button @click="showUserModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="saveUser" :disabled="adminLoading" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
                    {{ adminLoading ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </div>
    </div>

    <!-- CONFIRMATION MODAL -->
    <div v-if="confirmModal && confirmModal.show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden animate-fade transform transition-all scale-100">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">{{ confirmModal.title }}</h3>
                <p class="text-sm text-slate-500">{{ confirmModal.message }}</p>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-3">
                <button @click="confirmModal.show = false" class="flex-1 px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="executeConfirm" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors shadow-sm">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <!-- UNIT MODAL -->
    <div v-if="showUnitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ isUnitEdit ? 'Edit Unit' : 'Tambah Unit Baru' }}</h3>
                <button @click="showUnitModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nama Unit</label>
                    <input type="text" v-model="unitForm.name" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Jenjang / Level</label>
                    <select v-model="unitForm.unit_level" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="TK">TK / PAUD</option>
                        <option value="SD">SD / MI</option>
                        <option value="SMP">SMP / MTs</option>
                        <option value="SMA">SMA / MA</option>
                        <option value="SMK">SMK</option>
                        <option value="YAYASAN">YAYASAN</option>
                        <option value="OTHER">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Prefix / Singkatan (Max 10)</label>
                    <input type="text" v-model="unitForm.prefix" maxlength="10" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: SDIT, SMA">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Alamat Unit</label>
                    <textarea v-model="unitForm.address" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" rows="2"></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-2">
                <button @click="showUnitModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="saveUnit" :disabled="adminLoading" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
                    {{ adminLoading ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </div>
    </div>
    <!-- INCIDENT MODAL -->
    <div v-if="showIncidentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade flex flex-col max-h-[80vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-red-50">
                <h3 class="font-bold text-lg text-slate-800">Pelaporan Kejadian Siswa</h3>
                <button @click="showIncidentModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div v-if="incidentSelected && incidentSelected.length > 0" class="sticky top-0 z-10 p-3 rounded-lg border border-slate-200 bg-slate-50">
                    <div class="text-[12px] font-bold text-slate-700 mb-2">Siswa Dipilih ({{ incidentSelected.length }})</div>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="s in incidentSelected" :key="s.id" class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-[11px] flex items-center gap-2">
                            <span>{{ s.name }} ({{ s.identity_number }})</span>
                            <button @click="removeIncidentStudent(s)" class="text-blue-700 hover:text-blue-900"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-xs font-bold text-slate-600 mb-1">Cari Siswa (Nama/NIS)</label>
                    <input type="text" v-model="incidentQuery" @input="searchIncidentStudents" placeholder="Contoh: Ahmad, SD25001" ref="incidentInput" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 incident-input">
                    <div v-if="incidentSuggestions.length > 0" class="mt-2 border border-slate-200 rounded-lg bg-white shadow-sm max-h-40 overflow-auto">
                        <button v-for="s in incidentSuggestions" :key="s.id" @click="pickIncidentStudent(s)" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50">
                            <div class="font-bold text-slate-800">{{ s.name }}</div>
                            <div class="text-[11px] text-slate-500">NIS: {{ s.identity_number }} • Kelas: {{ s.class_name || '-' }}</div>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Kategori</label>
                        <select v-model="incidentForm.category" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="VIOLATION">Pelanggaran</option>
                            <option value="MEDICAL">Medis (Luka Ringan)</option>
                            <option value="OTHER">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Severity</label>
                        <select v-model="incidentForm.severity" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="LOW">Rendah</option>
                            <option value="MEDIUM">Sedang</option>
                            <option value="HIGH">Tinggi</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Judul</label>
                    <input type="text" v-model="incidentForm.title" placeholder="Ringkas, misal: Melanggar tata tertib kelas" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Deskripsi</label>
                    <textarea rows="3" v-model="incidentForm.description" placeholder="Tuliskan detail kejadian secara ringkas" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-2 shrink-0">
                <button @click="showIncidentModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="submitIncidentReport" :disabled="incidentSubmitting" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                    {{ incidentSubmitting ? 'Mengirim...' : 'Kirim Laporan' }}
                </button>
            </div>
        </div>
    </div>
    </div>

    <?php
        // Ensure $baseUrl computed above remains available here
        if (!isset($baseUrl)) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
                $baseUrl = '/' . $m[1] . '/';
            } else {
                $baseUrl = '/';
            }
        }
    ?>
    <!-- Blocking Scripts Moved to Bottom for Performance -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo $baseUrl; ?>assets/js/vue.global.js"></script>
    <script>
        window.onerror = function(msg, url, line, col, error) {
            // Ignore ResizeObserver loop limit exceeded
            if (msg.includes('ResizeObserver loop')) return false;
            
            const errBox = document.getElementById('js-error-box');
            if (errBox) {
                errBox.style.display = 'block';
                errBox.innerHTML += `<div><strong>Error:</strong> ${msg} <br><small>${url}:${line}</small></div>`;
            }
            console.error('Global Error:', msg, url, line, error);
            return false;
        };
    </script>
    <script>
        // Force Tailwind to process immediately
        if (typeof tailwind !== 'undefined') {
             tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            slate: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a' }
                        }
                    }
                }
            };
        }
    </script>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
        window.INITIAL_SETTINGS = <?php echo json_encode($schoolSettingsPHP); ?>;
        window.ALLOWED_MODULES = <?php echo json_encode($_SESSION['allowed_modules'] ?? []); ?>;
        window.USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
        window.USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
        window.PERSON_ID = <?php echo json_encode($_SESSION['person_id'] ?? null); ?>;
        window.ALLOWED_UNITS = <?php echo json_encode(array_keys(array_filter($_SESSION['allowed_units'] ?? []))); ?>;
        window.DASHBOARD_LAZY_LOAD = true;
        window.MANUAL_FETCH_ONLY = true;
        
        // Pre-fetched Server Data (No blocking AJAX needed)
        window.INITIAL_AGENDA = <?php echo json_encode($initialAgenda); ?>;
        window.INITIAL_PROFILE = <?php echo json_encode($initialProfile); ?>;
    </script>
    <script>
        // --- INLINE COMPONENTS & MIXINS FOR PERFORMANCE ---
        
        // 1. TreeItem Component
        const TreeItem = {
            name: 'tree-item',
            template: `
                <li>
                    <div class="org-node group relative min-w-[180px]">
                        <div class="font-bold text-slate-800 text-sm mb-1 pb-1 border-b border-slate-100">{{ model.position_name }}</div>
                        <div v-if="model.officials && model.officials.length > 1" class="text-left max-h-[120px] overflow-y-auto pr-1 custom-scrollbar">
                            <div v-for="(off, idx) in model.officials" :key="idx" class="mb-2 pb-1 border-b border-dashed border-slate-100 last:border-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-slate-400 w-4 text-center">{{ idx + 1 }}.</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium text-slate-700 truncate" :title="off.official_name">{{ off.official_name }}</div>
                                        <div class="text-[9px] text-blue-500 truncate">{{ off.sk_number }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="model.official_name" class="mt-2">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <img :src="'https://ui-avatars.com/api/?name=' + model.official_name + '&background=random&size=32'" class="w-8 h-8 rounded-full border border-white shadow-sm">
                                <div class="text-left">
                                    <div class="text-xs text-slate-700 font-bold leading-tight">{{ model.official_name }}</div>
                                    <div class="text-[9px] text-slate-400">{{ model.employee_number }}</div>
                                </div>
                            </div>
                            <div v-if="model.sk_number" class="text-[9px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full inline-block mt-1">
                                SK: {{ model.sk_number }}
                            </div>
                        </div>
                        <div v-else class="py-2 text-xs text-slate-400 italic">
                            - Vacant -
                        </div>
                        <button @click.stop="$emit('edit-node', model)" class="absolute -top-2 -right-2 w-6 h-6 bg-white border border-slate-200 text-slate-500 rounded-full flex items-center justify-center text-xs hover:text-blue-600 hover:border-blue-500 shadow-sm z-20" title="Edit Jabatan & Pejabat"><i class="fas fa-pencil-alt"></i></button>
                        <button @click.stop="$emit('add-node', model)" class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity shadow-md z-10" title="Tambah Bawahan"><i class="fas fa-plus"></i></button>
                    </div>
                    <ul v-if="model.children && model.children.length > 0 && model.children.length <= 8">
                        <tree-item v-for="child in model.children" :key="child.id" :model="child" @add-node="$emit('add-node', $event)" @edit-node="$emit('edit-node', $event)"></tree-item>
                    </ul>
                    <div v-if="model.children && model.children.length > 8" class="org-grid-wrapper">
                        <div class="org-grid-container">
                            <div v-for="child in model.children" :key="child.id" class="org-node-compact group relative hover:shadow-md transition-shadow cursor-pointer" @click.stop="$emit('edit-node', child)">
                                <img :src="'https://ui-avatars.com/api/?name=' + (child.official_name || 'X') + '&background=random&size=32'" class="w-8 h-8 rounded-full flex-shrink-0">
                                <div class="overflow-hidden">
                                    <div class="font-bold text-slate-700 truncate" :title="child.position_name">{{ child.position_name }}</div>
                                    <div class="text-slate-500 truncate" :title="child.official_name">{{ child.official_name || 'Kosong' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            `,
            props: { model: Object },
            emits: ['add-node', 'edit-node']
        };

        // 2. Utils Mixin
        const utilsMixin = {
            methods: {
                formatDate(dateString) {
                    if (!dateString) return '-';
                    const options = { day: 'numeric', month: 'long', year: 'numeric' };
                    return new Date(dateString).toLocaleDateString('id-ID', options);
                },
                getUnitName(unitCode) {
                    if (unitCode === 'all') return 'Yayasan (Global)';
                    const units = this.availableUnits || (this.$root && this.$root.availableUnits) || [];
                    const unit = units.find(u => u.id == unitCode || (u.unit_level && u.unit_level.toLowerCase() === unitCode.toString().toLowerCase()));
                    if (unit) return unit.prefix || unit.name;
                    const staticUnits = { tk: 'TK Al-Amanah', sd: 'SD Al-Amanah', smp: 'SMP Al-Amanah', sma: 'SMA Al-Amanah' };
                    return staticUnits[unitCode] || unitCode;
                },
                getModuleName(key) {
                    const names = { core: 'Core System', people: 'Data Induk (People)', academic: 'Akademik', library: 'Perpustakaan', finance: 'Keuangan', boarding: 'Asrama', pos: 'POS (Kantin/Toko)', hr: 'HR Basic', counseling: 'BK & Kesiswaan', executive: 'Executive View (Custom)', payroll: 'Payroll (Add-on)', workspace: 'Workspace' };
                    return names[key] || key;
                },
                getUnitId(code) {
                    if (!code) return null;
                    const units = this.availableUnits || (this.$root && this.$root.availableUnits) || [];
                    const unit = units.find(u => u.unit_level && u.unit_level.toLowerCase() === code.toString().toLowerCase());
                    return unit ? unit.id : null;
                },
                getTagClass(tag) {
                    if (tag === 'core') return 'bg-blue-900 text-blue-200 border border-blue-700';
                    if (tag === 'custom') return 'bg-purple-900 text-purple-200 border border-purple-700';
                    if (tag === 'add-on') return 'bg-emerald-900 text-emerald-200 border border-emerald-700';
                    if (tag === 'admin') return 'bg-indigo-900 text-indigo-200 border border-indigo-700';
                    if (tag === 'system') return 'bg-slate-700 text-slate-300 border border-slate-600';
                    return 'bg-slate-700 text-slate-400';
                },
                isUrlActive(url) {
                    if (!url) return false;
                    try {
                        return window.location.href.includes(url);
                    } catch (e) { return false; }
                },
                async loadNotifications() {
                    try {
                        const baseUrl = window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/');
                        const response = await fetch(baseUrl + 'api/notifications.php?action=get_counts');
                        const result = await response.json();
                        if (result.success && this.notificationCounts) {
                            this.notificationCounts = result.data;
                        }
                    } catch (error) { console.error('Error loading notifications:', error); }
                }
            }
        };

        // 3. Employee Mixin
        const employeeMixin = {
            data() {
                return {
                    employees: [], staffList: [], orgStructure: [],
                    showAddPositionModal: false, newPositionName: '', targetParentPosition: null,
                    showEditPositionModal: false, editPositionData: { id: null, name: '', officials: [] },
                    showEmployeeModal: false, employeeForm: { id: null, name: '', identity_number: '', employee_number: '', gender: 'L', employee_type: 'ACADEMIC', status: 'CONTRACT', join_date: new Date().toISOString().split('T')[0], access_units: [] },
                };
            },
            methods: {
                async fetchEmployees(unit) {
                    try {
                        let baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + `api/get_employees.php?unit=${unit}`);
                        this.employees = await response.json();
                    } catch (error) { console.error("Gagal mengambil data pegawai:", error); }
                },
                async fetchOrgStructure(unit) {
                    try {
                        let baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + `api/get_org_structure.php?unit=${unit}`);
                        this.orgStructure = await response.json();
                        this.fetchStaffList();
                    } catch (error) { console.error("Gagal mengambil struktur:", error); }
                },
                async fetchStaffList() {
                    try {
                        let baseUrl = window.BASE_URL || '/';
                        let url = baseUrl + 'api/get_unit_teachers.php?type=ACADEMIC';
                        if (this.currentUnit && this.currentUnit !== 'all') url += `&unit=${this.currentUnit}`;
                        const response = await fetch(url);
                        this.staffList = await response.json();
                    } catch (error) { console.error("Gagal load staff:", error); }
                },
                openAddPositionModal(parentModel) { this.targetParentPosition = parentModel; this.newPositionName = ''; this.showAddPositionModal = true; },
                openEditPositionModal(model) {
                    let currentOfficials = [];
                    if (model.officials && model.officials.length > 0) {
                        currentOfficials = model.officials.map(o => ({ person_id: this.findPersonIdByName(o.official_name), sk_number: o.sk_number }));
                    } else if (model.official_name) {
                        currentOfficials = [{ person_id: this.findPersonIdByName(model.official_name), sk_number: model.sk_number || '' }];
                    }
                    if (currentOfficials.length === 0) currentOfficials.push({ person_id: '', sk_number: '' });
                    this.editPositionData = { id: model.id, name: model.position_name, officials: currentOfficials };
                    this.showEditPositionModal = true;
                },
                findPersonIdByName(name) { const found = this.staffList.find(s => s.name === name); return found ? found.id : ''; },
                findPersonIdByEmpId(eid) { return ''; },
                addOfficialRow() { this.editPositionData.officials.push({ person_id: '', sk_number: '' }); },
                removeOfficialRow(index) { this.editPositionData.officials.splice(index, 1); },
                async updatePosition() {
                    try {
                        const payload = { action: 'update', position_id: this.editPositionData.id, name: this.editPositionData.name, officials: this.editPositionData.officials.filter(o => o.person_id) };
                        const baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + 'api/manage_position.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                        const result = await response.json();
                        if (result.success) { alert('Data Jabatan Berhasil Diupdate!'); this.showEditPositionModal = false; this.fetchOrgStructure(this.currentUnit); } else { alert('Gagal: ' + result.error); }
                    } catch (error) { console.error(error); }
                },
                async deletePosition() {
                    if (!confirm('Apakah Anda yakin ingin menghapus jabatan ini?')) return;
                    try {
                        const payload = { position_id: this.editPositionData.id, action: 'delete' };
                        const baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + 'api/manage_position.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                        const result = await response.json();
                        if (result.success) { alert('Jabatan Berhasil Dihapus!'); this.showEditPositionModal = false; this.fetchOrgStructure(this.currentUnit); } else { alert('Gagal: ' + result.error); }
                    } catch (error) { console.error(error); }
                },
                async saveNewPosition() {
                    if (!this.newPositionName || !this.targetParentPosition) return;
                    try {
                        const payload = { name: this.newPositionName, parent_id: this.targetParentPosition.id, unit_id: this.targetParentPosition.unit_id };
                        const baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + 'api/add_position.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                        const result = await response.json();
                        if (result.success) { alert('Berhasil menambahkan jabatan baru!'); this.showAddPositionModal = false; this.fetchOrgStructure(this.currentUnit); } else { alert('Gagal menambah jabatan: ' + result.error); }
                    } catch (error) { console.error("Error saving position:", error); }
                },
                openEmployeeModal(emp = null) {
                    if (emp) { this.employeeForm = { employee_id: emp.employee_id, name: emp.name, identity_number: '', employee_number: emp.employee_number, gender: emp.gender, employee_type: 'ACADEMIC', status: 'CONTRACT', join_date: '', access_units: emp.access_units ? emp.access_units.split(', ') : [] }; }
                    else { this.employeeForm = { id: null, name: '', identity_number: '', employee_number: '', gender: 'L', employee_type: 'ACADEMIC', status: 'CONTRACT', join_date: new Date().toISOString().split('T')[0], access_units: [] }; }
                    this.showEmployeeModal = true;
                },
                async saveEmployee() {
                    try {
                        const payload = { ...this.employeeForm, action: this.employeeForm.employee_id ? 'update' : 'create' };
                        const baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + 'api/manage_employee.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                        const result = await response.json();
                        if (result.success) { alert(result.message); this.showEmployeeModal = false; this.fetchEmployees(this.currentUnit); } else { alert(result.error); }
                    } catch(e) { console.error(e); }
                }
            }
        };

        // 4. Kiosk Mixin
        const kioskMixin = {
            data() { return { activeTab: 'tamu', saving: false, settings: { tamu: { welcome_text: '', running_text: '' }, kantor: { teacher_agenda: [] }, masjid: { prayer_times: { subuh:'', dzuhur:'', ashar:'', maghrib:'', isya:'' } }, aula: { news_items: [] } } } },
            mounted() { if (window.SERVER_SETTINGS) { if (window.SERVER_SETTINGS.tamu) this.settings.tamu = window.SERVER_SETTINGS.tamu; if (window.SERVER_SETTINGS.kantor) this.settings.kantor = window.SERVER_SETTINGS.kantor; if (window.SERVER_SETTINGS.masjid) this.settings.masjid = window.SERVER_SETTINGS.masjid; if (window.SERVER_SETTINGS.aula) this.settings.aula = window.SERVER_SETTINGS.aula; } },
            methods: {
                addAgenda() { this.settings.kantor.teacher_agenda.push({ time: '', activity: '', location: '' }); },
                removeAgenda(index) { this.settings.kantor.teacher_agenda.splice(index, 1); },
                addNews() { this.settings.aula.news_items.push({ title: '', content: '' }); },
                removeNews(index) { this.settings.aula.news_items.splice(index, 1); },
                async saveSettings() {
                    this.saving = true;
                    const payload = [
                        { zone: 'tamu', key: 'welcome_text', value: this.settings.tamu.welcome_text },
                        { zone: 'tamu', key: 'running_text', value: this.settings.tamu.running_text },
                        { zone: 'kantor', key: 'teacher_agenda', value: this.settings.kantor.teacher_agenda },
                        { zone: 'masjid', key: 'prayer_times', value: this.settings.masjid.prayer_times },
                        { zone: 'aula', key: 'news_items', value: this.settings.aula.news_items }
                    ];
                    try {
                        const targetUrl = window.location && window.location.href ? window.location.href : (window.BASE_URL || '/') + 'modules/kiosk/settings.php';
                        const response = await fetch(targetUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                        const result = await response.json();
                        if (result.status === 'success') { alert('Pengaturan berhasil disimpan!'); } else { alert('Gagal menyimpan: ' + result.message); }
                    } catch (error) { alert('Terjadi kesalahan koneksi.'); console.error(error); } finally { this.saving = false; }
                }
            }
        };

        // 5. Academic Mixin
        const academicMixin = {
            data() {
                return {
                    academicYears: [{ id: 1, name: '2025/2026', status: 'Aktif', start: '15 Juli 2025' }, { id: 2, name: '2024/2025', status: 'Arsip', start: '15 Juli 2024' }],
                    unitData: { subjects: [], timeSlots: [], classes: [], levels: [], years: [] },
                    selectedClassId: '', scheduleData: {}, days: ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'],
                    selectedClass: null, classMembers: [], classAttendanceSummary: [], monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    activeTab: 'students', searchQuery: '', showEditClassModal: false, editClassData: { id: null, name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
                    showModalYear: false, yearForm: { id: null, name: '', start_date: '', end_date: '', semester_active: 'GANJIL', status: 'PLANNED' },
                    showLevelModal: false, levelForm: { id: null, name: '', order_index: '' }, showCreateClassModal: false, newClassForm: { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
                    showTimeSlotModal: false, timeSlotForm: { id: null, name: '', start_time: '', end_time: '', is_break: false },
                    showScheduleModal: false, scheduleForm: { id: null, day: '', slot_name: '', start_time: '', end_time: '', time_slot_id: '', subject_id: '', teacher_id: '' },
                    showSubjectModal: false, subjectForm: { id: null, code: '', name: '', category: 'CORE' },
                    classSubjects: [], selectedSubjectId: '', subjectAssignments: [], showSubjectTeachersListModal: false, showAssignTeacherModal: false, assignTeacherForm: { class_id: null, subject_id: null, subject_name: '', code: '', teacher_id: '', weekly_count: 1, session_length: 2 },
                    selectedTeacherId: '', teacherScheduleData: {}, staffList: [], allUnits: [], currentUnit: '', currentPage: '',
                    showShuffleWizard: false, shuffleStep: 1, shuffleScope: 'selected_units', shuffleSelectedUnits: [], shuffleConfirmText: '', shuffleLoading: false, shuffleResult: { success: false, error: '', logs: [] },
                    showConstraintModal: false, constraintForm: { type: 'TEACHER', entity_id: '', unit_id: '', day: 'SENIN', start_time: '', end_time: '', is_whole_day: false, reason: '' }, constraintList: [], years: [], yearLoading: false,
                };
            },
            computed: {
                currentDate() { try { const d = new Date(); const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']; const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; const pad = (n) => String(n).padStart(2, '0'); return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`; } catch (_) { return ''; } },
                filteredClassMembers() { if (!this.searchQuery) return this.classMembers; const lower = this.searchQuery.toLowerCase(); return this.classMembers.filter(s => (s.name && s.name.toLowerCase().includes(lower)) || (s.identity_number && s.identity_number.toLowerCase().includes(lower))); },
                availableUnits() { return this.allUnits || []; }, teachers() { return this.staffList; },
                constraintEntityList() { if (this.constraintForm.type === 'TEACHER') return this.teachers; if (this.constraintForm.type === 'SUBJECT') return this.activeSubjects; return []; },
                activeSubjects() { return this.unitData.subjects || []; }, activeTimeslots() { return this.unitData.timeSlots || []; }, activeClasses() { return this.unitData.classes || []; }, activeLevels() { return this.unitData.levels || []; }, activeYears() { return this.unitData.years || []; },
                assignedTeacher() { try { if (!this.scheduleForm || !this.scheduleForm.subject_id) return null; const match = this.classSubjects.find(s => s.subject_id == this.scheduleForm.subject_id); if (match && match.teacher_id) { return { id: match.teacher_id, name: match.teacher_name }; } return null; } catch (_) { return null; } }
            },
            watch: {
                selectedClassId(newVal) { if (newVal) { localStorage.setItem('ais_selected_class_id', newVal); if (this.currentPage === 'schedule') { const catId = this.activeCategoryId || (this.unitData && this.unitData.activeCategoryId) || null; this.fetchSchedule(newVal, catId); } } },
                selectedTeacherId(newVal) { if (newVal) { this.fetchTeacherSchedule(newVal); } },
                currentPage(val) { if (val === 'teacher-schedule') { this.fetchStaffList(); if (this.selectedTeacherId) this.fetchTeacherSchedule(this.selectedTeacherId); } },
                currentUnit(newVal) { if (this.manualFetchOnly) return; if (newVal && newVal !== 'all') { this.fetchAcademicData(newVal); this.fetchStaffList(); } }
            },
            async mounted() {
                const path = window.location.pathname;
                if (path.includes('schedule.php')) this.currentPage = 'schedule'; else if (path.includes('teacher_schedule.php')) this.currentPage = 'teacher-schedule'; else if (path.includes('class_detail.php')) this.currentPage = 'class-detail';
                this.fetchAllUnits();
                if (this.manualFetchOnly) { if (!this.currentUnit) this.currentUnit = 'all'; return; }
                if (this.currentPage === 'class-detail') { const urlParams = new URLSearchParams(window.location.search); const classIdParam = urlParams.get('id'); if (classIdParam) { this.loadClassDetailDirectly(classIdParam); } } else if (!this.currentUnit) { const savedUnit = localStorage.getItem('ais_selected_unit'); const allowedRaw = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : []; const allowed = allowedRaw.map(s => String(s).toUpperCase()); let candidate = savedUnit || ''; if (candidate) { const candUp = String(candidate).toUpperCase(); const ok = allowed.length === 0 || allowed.includes(candUp); this.currentUnit = ok ? candidate : ''; } if (!this.currentUnit) { if (Array.isArray(this.allUnits) && this.allUnits.length > 0) { const first = this.allUnits[0]; this.currentUnit = first.code || first.unit_level || first.name || 'SD'; } else if (allowedRaw.length > 0) { this.currentUnit = allowedRaw[0]; } else { this.currentUnit = 'SD'; } } } else { this.fetchAcademicData(this.currentUnit); this.fetchStaffList(); }
            },
            methods: {
                async loadClassDetailDirectly(classId) {
                    try {
                        let baseUrl = window.BASE_URL || '/';
                        const res = await fetch(baseUrl + `api/get_class_detail.php?id=${classId}`);
                        let classInfo = null;
                        if (res.ok) { classInfo = await res.json(); } else { try { const relRes = await fetch(`../../api/get_class_detail.php?id=${classId}`); if (relRes.ok) { classInfo = await relRes.json(); } } catch (_) {} }
                        if (!classInfo || !classInfo.id) { this.selectedClass = { id: Number(classId), name: `Kelas #${classId}`, level_name: '-' }; this.selectedClassId = Number(classId); await this.fetchClassAttendanceSummary(classId); try { const memResFallback = await fetch(baseUrl + `api/get_class_members.php?class_id=${classId}`); if (memResFallback.ok) this.classMembers = await memResFallback.json(); } catch (_) {} return; }
                        this.selectedClass = classInfo; this.selectedClassId = classInfo.id;
                        if (classInfo.unit_code) { if (this.currentUnit !== classInfo.unit_code) { this.currentUnit = classInfo.unit_code; } else { this.fetchAcademicData(this.currentUnit); } } else if (classInfo.unit_name) { if (this.currentUnit !== classInfo.unit_name) { this.currentUnit = classInfo.unit_name; } else { this.fetchAcademicData(this.currentUnit); } }
                        const memRes = await fetch(baseUrl + `api/get_class_members.php?class_id=${classId}`); this.classMembers = await memRes.json();
                        this.fetchSchedule(classId); this.fetchClassAttendanceSummary(classId); this.activeTab = 'students';
                    } catch (e) { console.error("Failed to load class detail:", e); try { await this.fetchClassAttendanceSummary(classId); } catch (_) {} }
                },
                async fetchStaffList() {
                    try {
                         let baseUrl = window.BASE_URL || '/';
                         const unitId = this.getUnitId(this.currentUnit);
                         let url = baseUrl + 'api/get_staff_list.php?role=TEACHER';
                         if (unitId) { url += `&unit_id=${unitId}`; }
                        const res = await fetch(url);
                        let data; const ct = String(res.headers.get('content-type') || '');
                        if (ct.includes('application/json')) { data = await res.json(); } else { const txt = await res.text(); try { data = JSON.parse(txt); } catch(_) { console.warn('Non-JSON response from get_staff_list:', txt.slice(0, 160)); data = []; } }
                        this.staffList = Array.isArray(data) ? data : [];
                    } catch(e) { console.error("Gagal ambil data guru:", e); }
                },
                async fetchAllUnits() {
                    try {
                        let baseUrl = window.BASE_URL || '/';
                        const res = await fetch(baseUrl + 'api/get_units.php');
                        this.allUnits = await res.json();
                        const allowedRaw = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                        const allowed = allowedRaw.map(s => String(s).toUpperCase());
                        if (Array.isArray(this.allUnits) && allowed.length > 0) {
                            this.allUnits = this.allUnits.filter(u => { const code = String(u.code || u.unit_level || '').toUpperCase(); const prefix = String(u.prefix || '').toUpperCase(); return allowed.includes(code) || (prefix && allowed.includes(prefix)); });
                        }
                    } catch(e) { console.warn("Using fallback units"); this.allUnits = [{id: 1, name: 'TK', code: 'TK'}, {id: 2, name: 'SD', code: 'SD'}, {id: 3, name: 'SMP', code: 'SMP'}, {id: 4, name: 'SMA', code: 'SMA'}]; }
                },
                async fetchAcademicData(unit, categoryId = null) {
                    try {
                        if (!unit || unit === 'all') return;
                        if (!categoryId && window.PREFETCHED_SCHEDULE && window.PREFETCHED_SCHEDULE.unit === unit && !window.PREFETCHED_SCHEDULE.consumed) {
                            const pre = window.PREFETCHED_SCHEDULE;
                            this.unitData = { classes: pre.classes || [], timeSlots: pre.timeSlots || [], subjects: pre.subjects || [], years: pre.years || [], levels: [], scheduleCategories: pre.scheduleCategories || [], activeCategoryId: pre.activeCategoryId || null };
                            window.PREFETCHED_SCHEDULE.consumed = true;
                            if (pre.class_id) { this.selectedClassId = pre.class_id; } else if (this.unitData.classes.length > 0) { this.selectedClassId = this.unitData.classes[0].id; }
                            return;
                        }
                        let baseUrl = window.BASE_URL || '/';
                        const response = await fetch(baseUrl + `api/get_academic_data.php?unit=${unit}${categoryId ? '&category_id=' + categoryId : ''}`);
                        let data; const ct = String(response.headers.get('content-type') || '');
                        if (ct.includes('application/json')) { data = await response.json(); } else { const txt = await response.text(); throw new Error('Invalid JSON response: ' + txt.slice(0, 80)); }
                        this.unitData = data;
                        const urlParams = new URLSearchParams(window.location.search);
                        const classIdParam = urlParams.get('id');
                        if (classIdParam && data.classes) { const targetClass = data.classes.find(c => c.id == classIdParam); if (targetClass) { this.openClassDetail(targetClass); } }
                        else if (data.classes && data.classes.length > 0 && !window.location.pathname.includes('class_detail.php')) {
                            const savedClassId = localStorage.getItem('ais_selected_class_id');
                            if (savedClassId && data.classes.find(c => c.id == savedClassId)) { this.selectedClassId = savedClassId; } else { this.selectedClassId = data.classes[0].id; }
                        }
                    } catch (error) { console.error("Gagal mengambil data akademik:", error); }
                },
                openModalYear(year = null) { if (year) { this.yearForm = { ...year }; } else { this.yearForm = { id: null, name: '', start_date: '', end_date: '', semester_active: 'GANJIL', status: 'ACTIVE' }; } this.showModalYear = true; },
                async saveYear() { try { const baseUrl = window.BASE_URL || '/'; const payload = { ...this.yearForm, action: 'save' }; const response = await fetch(baseUrl + 'api/manage_year.php', { method: 'POST', body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Tahun Ajaran Berhasil Disimpan!'); this.showModalYear = false; this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch (e) { console.error(e); } },
                openEditClassModal(cls) { this.fetchStaffList(); this.editClassData = { id: cls.id, name: cls.name, level_id: cls.level_id, homeroom_teacher_id: cls.homeroom_teacher_id, capacity: cls.capacity || 30, sort_order: cls.sort_order || 0 }; this.showEditClassModal = true; },
                async updateClass() { try { const baseUrl = window.BASE_URL || '/'; const payload = { ...this.editClassData, action: 'update', class_id: this.editClassData.id }; const response = await fetch(baseUrl + 'api/manage_class.php', { method: 'POST', body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Kelas Berhasil Diupdate!'); this.showEditClassModal = false; this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch (e) { console.error(e); } },
                async deleteClass(cls) { if (this.confirmAction) { this.confirmAction('Hapus Kelas?', `Apakah Anda yakin ingin menghapus kelas <b>${cls.name}</b>?<br>Tindakan ini tidak dapat dibatalkan.`, async () => { await this.executeDeleteClass(cls); }); } else { if (!confirm(`Hapus kelas ${cls.name}?`)) return; this.executeDeleteClass(cls); } },
                async executeDeleteClass(cls) { try { const baseUrl = window.BASE_URL || '/'; const payload = { class_id: cls.id, action: 'delete' }; const response = await fetch(baseUrl + 'api/manage_class.php', { method: 'POST', body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Kelas Berhasil Dihapus!'); this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch (e) { console.error(e); alert("Terjadi kesalahan sistem."); } },
                async openClassDetail(cls) { if (!window.location.pathname.includes('class_detail.php')) { window.location.href = `class_detail.php?id=${cls.id}`; return; } this.selectedClass = cls; this.currentPage = 'class-detail'; this.classMembers = []; try { const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + `api/get_class_members.php?class_id=${cls.id}`); this.classMembers = await response.json(); } catch (error) { console.error("Gagal ambil anggota kelas:", error); } },
                async saveLevel() { if (!this.getUnitId(this.currentUnit)) { alert('Silakan pilih unit spesifik (TK/SD/SMP/SMA) terlebih dahulu untuk menambah tingkatan.'); return; } try { const baseUrl = window.BASE_URL || '/'; const payload = { ...this.levelForm, unit_id: this.getUnitId(this.currentUnit) }; const response = await fetch(baseUrl + 'api/manage_level.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert(result.message); this.levelForm = { id: null, name: '', order_index: '' }; this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch(e) { console.error(e); } },
                async deleteLevel(lvl) { if(!confirm('Hapus tingkatan ini?')) return; try { const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_level.php', { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: lvl.id }) }); const result = await response.json(); if (result.success) { alert(result.message); this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch(e) { console.error(e); } },
                openTimeSlotModal(slot = null) { if (slot) { this.timeSlotForm = { id: slot.id, name: slot.name, start_time: slot.start, end_time: slot.end, is_break: slot.isBreak }; } else { this.timeSlotForm = { id: null, name: '', start_time: '', end_time: '', is_break: false }; } this.showTimeSlotModal = true; },
                async saveTimeSlot() { if (!this.getUnitId(this.currentUnit)) { alert('Silakan pilih unit spesifik (TK/SD/SMA) terlebih dahulu!'); return; } try { const baseUrl = window.BASE_URL || '/'; const payload = { ...this.timeSlotForm, unit_id: this.getUnitId(this.currentUnit), action: this.timeSlotForm.id ? 'update' : 'create' }; const response = await fetch(baseUrl + 'api/manage_timeslot.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert(result.message); this.showTimeSlotModal = false; this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch(e) { console.error(e); } },
                async generateTimeslots() { if (!this.getUnitId(this.currentUnit)) { alert('Pilih unit terlebih dahulu!'); return; } if (!confirm('Generate otomatis akan menghapus pengaturan jam pelajaran yang ada untuk unit ini dan menggantinya dengan default. Lanjutkan?')) return; try { const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/generate_timeslots.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ unit_id: this.getUnitId(this.currentUnit) }) }); const result = await response.json(); if (result.success) { alert(result.message); this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch(e) { console.error(e); } },
                async deleteTimeSlot(slot) { if(!confirm('Hapus slot waktu ini?')) return; try { const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_timeslot.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: slot.id, action: 'delete' }) }); const result = await response.json(); if (result.success) { alert(result.message); this.fetchAcademicData(this.currentUnit); } else { alert(result.error); } } catch(e) { console.error(e); } },
                async fetchSchedule(classId, categoryId = null) { if (window.PREFETCHED_SCHEDULE && window.PREFETCHED_SCHEDULE.class_id == classId && window.PREFETCHED_SCHEDULE.schedule) { const prefetchedCatId = window.PREFETCHED_SCHEDULE.activeCategoryId; if (!categoryId || categoryId == prefetchedCatId) { this.scheduleData = window.PREFETCHED_SCHEDULE.schedule; window.PREFETCHED_SCHEDULE.schedule = null; return; } } try { const baseUrl = window.BASE_URL || '/'; const url = baseUrl + `api/get_schedule.php?class_id=${classId}&_t=${new Date().getTime()}` + (categoryId ? `&category_id=${categoryId}` : ''); const response = await fetch(url); this.scheduleData = await response.json(); } catch (error) { console.error("Gagal mengambil jadwal:", error); } },
                async fetchClassAttendanceSummary(classId) { try { let baseUrl = window.BASE_URL || '/'; const res = await fetch(baseUrl + `api/attendance.php?action=get_class_attendance_summary&class_id=${classId}`); const data = await res.json(); if (data.success) { this.classAttendanceSummary = data.data; } } catch (e) { console.error("Gagal mengambil rekap absensi:", e); } },
                async fetchTeacherSchedule(teacherId) { try { console.log('Fetching schedule for teacher:', teacherId); const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + `api/get_teacher_schedule.php?teacher_id=${teacherId}&_t=${new Date().getTime()}`); const data = await response.json(); console.log('Teacher schedule received:', data); this.teacherScheduleData = data; } catch (error) { console.error("Gagal mengambil jadwal guru:", error); } },
                getScheduleItem(day, startTime) { if (this.scheduleData[day] && this.scheduleData[day][startTime]) { return this.scheduleData[day][startTime]; } return null; },
                getTeacherScheduleItem(day, startTime) { if (this.teacherScheduleData[day] && this.teacherScheduleData[day][startTime]) { return this.teacherScheduleData[day][startTime]; } return null; },
                getTeacherSessionLength(day, startTime) { try { const startIndex = this.activeTimeslots.findIndex(ts => ts.start === startTime); if (startIndex === -1) return 1; const first = this.getTeacherScheduleItem(day, startTime); if (!first) return 0; let length = 0; for (let k = 0; ; k++) { const ts = this.activeTimeslots[startIndex + k]; if (!ts || ts.isBreak) break; const item = this.getTeacherScheduleItem(day, ts.start); if (!item) break; if (item.subject_id === first.subject_id && item.class_id === first.class_id) { length++; } else break; } return length; } catch (_) { return 1; } },
                isFirstSessionSlot(day, startTime) { try { const idx = this.activeTimeslots.findIndex(ts => ts.start === startTime); if (idx <= 0) return true; const prevTs = this.activeTimeslots[idx - 1]; if (!prevTs || prevTs.isBreak) return true; const curr = this.getTeacherScheduleItem(day, startTime); const prev = this.getTeacherScheduleItem(day, prevTs.start); if (!curr) return false; if (!prev) return true; return !(prev.subject_id === curr.subject_id && prev.class_id === curr.class_id); } catch (_) { return true; } },
                openScheduleModal(day, slot, existingItem = null) { this.fetchClassSubjects(); if (existingItem) { this.scheduleForm = { id: existingItem.id, day: day, slot_name: slot.name, start_time: slot.start, end_time: slot.end, time_slot_id: slot.id, subject_id: existingItem.subject_id, teacher_id: existingItem.teacher_id }; } else { this.scheduleForm = { id: null, day: day, slot_name: slot.name, start_time: slot.start, end_time: slot.end, time_slot_id: slot.id, subject_id: '', teacher_id: '' }; } this.showScheduleModal = true; },
                async saveSchedule() { try { const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null); if (!activeYear) { alert('Harap set Tahun Ajaran Aktif terlebih dahulu!'); return; } const assignment = await this.getAssignmentForSubject(this.scheduleForm.subject_id, activeYear.id, this.selectedClassId); if (!assignment || !assignment.teacher_id) { alert('Belum ada Guru Mapel terpasang untuk kelas ini. Atur di menu "Guru Mapel" terlebih dahulu.'); return; } const sessionLen = assignment ? Number(assignment.session_length || 1) : 1; const weeklyCount = assignment ? Number(assignment.weekly_count || 1) : 1; const maxSlots = weeklyCount * sessionLen; await this.fetchSchedule(this.selectedClassId); const usedSlots = this.countScheduledSlotsForSubject(this.scheduleForm.subject_id); if (usedSlots >= maxSlots) { alert(`Pembatasan JP tercapai: ${weeklyCount} JP/minggu dengan durasi ${sessionLen} JP per sesi. Tidak dapat menambah jadwal lagi untuk mapel ini.`); return; } const startIndex = this.activeTimeslots.findIndex(ts => ts.id == this.scheduleForm.time_slot_id); if (startIndex === -1) { alert('Slot waktu tidak ditemukan.'); return; } for (let k = 0; k < sessionLen; k++) { const ts = this.activeTimeslots[startIndex + k]; if (!ts || ts.isBreak) { alert('Slot berurutan tidak cukup untuk durasi sesi yang ditentukan.'); return; } const occupied = !!this.getScheduleItem(this.scheduleForm.day, ts.start); if (occupied) { alert('Sebagian slot sudah terisi. Pilih waktu lain atau kosongkan terlebih dahulu.'); return; } } const baseUrl = window.BASE_URL || '/'; let successAll = true, lastError = ''; for (let k = 0; k < sessionLen; k++) { const ts = this.activeTimeslots[startIndex + k]; const payload = { action: 'create', class_id: this.selectedClassId, subject_id: this.scheduleForm.subject_id, teacher_id: this.scheduleForm.teacher_id, day: this.scheduleForm.day, time_slot_id: ts.id, academic_year_id: activeYear.id }; const response = await fetch(baseUrl + 'api/manage_schedule.php', { method: 'POST', body: JSON.stringify(payload) }); const result = await response.json(); if (!result.success) { successAll = false; lastError = result.error || 'Gagal menyimpan sebagian jadwal'; break; } } if (successAll) { this.showScheduleModal = false; await this.fetchSchedule(this.selectedClassId); } else { alert(lastError); } } catch(e) { console.error(e); } },
                async shuffleSchedule() { if (!this.selectedClassId) { alert('Pilih kelas terlebih dahulu!'); return; } if (!confirm('Apakah Anda yakin ingin men-shuffle jadwal? Jadwal yang sudah ada akan dihapus dan diganti dengan jadwal acak baru.')) return; try { const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null); if (!activeYear) { alert('Harap set Tahun Ajaran Aktif terlebih dahulu!'); return; } const payload = { class_id: this.selectedClassId, academic_year_id: activeYear.id }; const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/generate_schedule.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert(result.message); this.fetchSchedule(this.selectedClassId); } else { alert(result.error); } } catch(e) { console.error(e); } },
                async deleteSchedule() { if(!confirm('Hapus jadwal ini?')) return; try { const payload = { action: 'delete', schedule_id: this.scheduleForm.id }; const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_schedule.php', { method: 'POST', body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { this.showScheduleModal = false; this.fetchSchedule(this.selectedClassId); } else { alert(result.error); } } catch(e) { console.error(e); } },
                countScheduledSlotsForSubject(subjectId) { try { let count = 0; const days = this.days || ['SENIN','SELASA','RABU','KAMIS','JUMAT']; for (const day of days) { const map = this.scheduleData[day] || {}; for (const timeKey in map) { const item = map[timeKey]; if (item && item.subject_id == subjectId) count++; } } return count; } catch (_) { return 0; } },
                async getAssignmentForSubject(subjectId, academicYearId, classId) { try { const baseUrl = window.BASE_URL || '/'; const res = await fetch(baseUrl + `api/get_subject_assignments.php?subject_id=${subjectId}&academic_year_id=${academicYearId}`); const list = await res.json(); if (Array.isArray(list)) { return list.find(a => a.class_id == classId) || null; } return null; } catch (e) { console.error('Gagal ambil assignment mapel:', e); return null; } },
                openShuffleWizard() { if (!this.selectedClassId && Array.isArray(this.activeClasses) && this.activeClasses.length > 0) { this.selectedClassId = this.activeClasses[0].id; } if (!this.allUnits || this.allUnits.length === 0) { this.fetchAllUnits(); } this.shuffleStep = 1; this.shuffleScope = 'single_class'; this.shuffleSelectedUnits = []; const currentUnitId = this.getUnitId(this.currentUnit); if (currentUnitId && !this.shuffleSelectedUnits.includes(currentUnitId)) this.shuffleSelectedUnits.push(currentUnitId); this.shuffleConfirmText = ''; this.shuffleResult = { success: false, error: '', logs: [] }; this.showShuffleWizard = true; },
                closeShuffleWizard() { this.showShuffleWizard = false; if (this.shuffleResult.success) { this.fetchSchedule(this.selectedClassId); } },
                async runSmartShuffle() { this.shuffleLoading = true; this.shuffleStep = 3; try { let activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null); if (!activeYear) { await this.syncYears(); activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null); if (!activeYear) throw new Error("Tahun ajaran tidak aktif."); } if (this.shuffleScope === 'single_class') { if (!this.selectedClassId) { if (Array.isArray(this.activeClasses) && this.activeClasses.length > 0) { this.selectedClassId = this.activeClasses[0].id; } } if (!this.selectedClassId) throw new Error("Pilih kelas terlebih dahulu."); } else { if (!this.shuffleSelectedUnits || this.shuffleSelectedUnits.length === 0) { const currentUnitId = this.getUnitId(this.currentUnit); if (currentUnitId) this.shuffleSelectedUnits = [currentUnitId]; } if (!this.shuffleSelectedUnits || this.shuffleSelectedUnits.length === 0) throw new Error("Pilih unit terlebih dahulu."); } const payload = { class_id: this.selectedClassId, target_unit_ids: this.shuffleSelectedUnits, academic_year_id: activeYear.id, scope: this.shuffleScope }; const baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/generate_schedule_global.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) }); const result = await response.json(); this.shuffleResult = result; } catch (e) { this.shuffleResult = { success: false, error: e.message, logs: [] }; } finally { this.shuffleLoading = false; } }
            }
        };

        // 6. Admin Mixin
        const adminMixin = {
            data() { return { schoolSettings: Object.assign({ name: '', npsn: '', address: '', phone: '', email: '', logo_url: '', footer_text: '', wa_api_url: '', wa_api_token: '', wa_security_target: '', google_gemini_api_key: '' }, (window.INITIAL_SETTINGS || {})), userList: [], userForm: { id: null, username: '', password: '', role: 'ADMIN', status: 'ACTIVE', email: '', access_modules: [], people_id: null }, staffLookup: [], showUserModal: false, isUserEdit: false, adminLoading: false, unitList: [], unitForm: { id: null, name: '', unit_level: 'SD', prefix: '', headmaster: '', address: '' }, showUnitModal: false, isUnitEdit: false, confirmModal: { show: false, title: '', message: '', onConfirm: null } }; },
            methods: {
                confirmAction(title, message, callback) { this.confirmModal = { show: true, title: title, message: message, onConfirm: callback }; },
                executeConfirm() { if (this.confirmModal.onConfirm) { this.confirmModal.onConfirm(); } this.confirmModal.show = false; },
                async fetchSettings() { try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/get_settings.php'); const data = await response.json(); if (data) { this.schoolSettings = { ...this.schoolSettings, ...data }; } this.fetchUnits(); } catch (error) { console.error('Error fetching settings:', error); } },
                async saveSettings() { this.confirmAction('Simpan Pengaturan?', 'Apakah Anda yakin ingin menyimpan perubahan pengaturan sekolah?', async () => { this.adminLoading = true; try { const baseUrl = window.BASE_URL || '/'; const formData = new FormData(); for (const key in this.schoolSettings) { if (key !== 'logo_file') { formData.append(key, this.schoolSettings[key] || ''); } } if (this.schoolSettings.logo_file) { formData.append('logo_file', this.schoolSettings.logo_file); } const response = await fetch(baseUrl + 'api/save_settings.php', { method: 'POST', body: formData }); const result = await response.json(); if (result.success) { alert('Pengaturan berhasil disimpan!'); if (result.logo_url) { this.schoolSettings.logo_url = result.logo_url; if (this.$root) this.$root.schoolLogo = result.logo_url; } } else { alert('Gagal menyimpan: ' + result.message); } } catch (error) { console.error('Error saving settings:', error); alert('Terjadi kesalahan sistem.'); } finally { this.adminLoading = false; } }); },
                async testWaNotification() { try { let baseUrl = window.BASE_URL || '/'; const msg = (this.waTestMessage || '').trim(); if (!msg) { alert('Isi pesan uji WA terlebih dahulu'); return; } const target = (this.schoolSettings.wa_security_target || '').trim(); const res = await fetch(baseUrl + 'api/security.php?action=send_wa_test', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message: msg, target }) }); const j = await res.json(); alert(j.success ? 'Uji kirim WA berhasil' : ('Uji kirim WA gagal: ' + (j.message || ''))); } catch (e) { alert('Terjadi kesalahan sistem saat mengirim WA'); console.error(e); } },
                handleLogoUpload(event) { const file = event.target.files[0]; if (file) { this.schoolSettings.logo_file = file; this.schoolSettings.logo_url = URL.createObjectURL(file); } },
                async fetchUnits() { try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/get_units.php'); this.unitList = await response.json(); } catch (error) { console.error('Error fetching units:', error); } },
                openAddUnitModal() { this.unitForm = { id: null, name: '', unit_level: 'SD', prefix: '', headmaster: '', address: '' }; this.isUnitEdit = false; this.showUnitModal = true; },
                editUnit(unit) { this.unitForm = { ...unit }; this.isUnitEdit = true; this.showUnitModal = true; },
                async saveUnit() { if (!this.unitForm.name) return alert('Nama unit wajib diisi'); try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_unit.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: this.isUnitEdit ? 'update' : 'create', ...this.unitForm }) }); const result = await response.json(); if (result.success) { alert('Data unit berhasil disimpan!'); this.showUnitModal = false; this.fetchUnits(); } else { alert('Gagal: ' + result.message); } } catch (error) { console.error('Error saving unit:', error); } },
                async deleteUnit(id) { this.confirmAction('Hapus Unit?', 'Apakah Anda yakin ingin menghapus unit ini? Data yang terkait mungkin akan error.', async () => { try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_unit.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', id: id }) }); if ((await response.json()).success) this.fetchUnits(); } catch (e) { console.error(e); } }); },
                async fetchUsers() { this.adminLoading = true; try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/get_users.php'); const data = await response.json(); this.userList = (data || []).map(u => { const modules = Array.isArray(u.access_modules) ? u.access_modules : []; return { ...u, email: u.email || '', status: (u.status || 'ACTIVE').toUpperCase(), access_modules: modules }; }); } catch (error) { console.error('Error fetching users:', error); } finally { this.adminLoading = false; } },
                openAddUserModal() { this.userForm = { id: null, username: '', password: '', role: 'STAFF', status: 'ACTIVE', email: '', access_modules: [], people_id: null }; this.isUserEdit = false; this.showUserModal = true; this.fetchStaffLookup(); },
                editUser(user) { const role = (user.role || 'ADMIN').toUpperCase(); const mods = (user.access_modules || []); this.userForm = { ...user, password: '', role: role, status: (user.status || 'ACTIVE'), email: (user.email || ''), access_modules: (['ADMIN','SUPERADMIN'].includes(role) ? [] : mods), people_id: (user.people_id || null) }; this.isUserEdit = true; this.showUserModal = true; this.fetchStaffLookup(); },
                async fetchStaffLookup() { try { let baseUrl = window.BASE_URL || '/'; const res = await fetch(baseUrl + 'api/get_all_staff.php'); if (!res.ok) { this.staffLookup = []; return; } const ct = res.headers.get('content-type') || ''; if (!ct.includes('application/json')) { this.staffLookup = []; return; } this.staffLookup = await res.json(); } catch (e) { console.error(e); } },
                async saveUser() { if (!this.userForm.username) return alert('Username wajib diisi'); if (!this.isUserEdit && !this.userForm.password) return alert('Password wajib diisi'); this.adminLoading = true; try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: this.isUserEdit ? 'update' : 'create', id: this.userForm.id, username: this.userForm.username, password: this.userForm.password, role: this.userForm.role, status: this.userForm.status, email: this.userForm.email, access_modules: this.userForm.access_modules, people_id: this.userForm.people_id }) }); const result = await response.json(); if (result.success) { alert('Data user berhasil disimpan!'); this.showUserModal = false; this.fetchUsers(); } else { alert('Gagal: ' + result.message); } } catch (error) { console.error('Error saving user:', error); alert('Terjadi kesalahan sistem.'); } finally { this.adminLoading = false; } },
                async deleteUser(id) { this.confirmAction('Hapus User?', 'Apakah Anda yakin ingin menghapus user ini?', async () => { try { let baseUrl = window.BASE_URL || '/'; const response = await fetch(baseUrl + 'api/manage_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', id: id }) }); const result = await response.json(); if (result.success) { this.fetchUsers(); } else { alert('Gagal menghapus: ' + result.message); } } catch (error) { console.error('Error deleting user:', error); } }); }
            },
            mounted() { if (this.currentPage === 'settings') this.fetchSettings(); if (this.currentPage === 'users') this.fetchUsers(); if (!this.schoolSettings.name && this.currentPage !== 'settings') { this.fetchSettings(); } },
            watch: { currentPage(newVal) { if (newVal === 'settings') this.fetchSettings(); if (newVal === 'users') this.fetchUsers(); } }
        };

        const { createApp } = Vue;

        const app = createApp({
            mixins: [utilsMixin, employeeMixin, academicMixin, kioskMixin, adminMixin],
            components: { 'tree-item': TreeItem },
            data() {
                return {
                    isMounted: false, // Controls Skeleton vs Content visibility
                    currentPage: 'dash',
                    activeSettingTab: 'general',
                    currentPreset: 'all',
                    currentUnit: (window.MANUAL_FETCH_ONLY ? 'all' : (localStorage.getItem('currentUnit') || 'all')), 
                    showControls: false,
                    isSidebarOpen: false,
                    modules: { core: true, academic: true, finance: true, hr: true, library: true, executive: true, foundation: true, boarding: true, workspace: true, pos: true, payroll: true, counseling: true, people: true, inventory: true, kiosk: true, cleaning: true, security: true },
                    myProfile: (window.INITIAL_PROFILE || {}),
                    isProfileEditing: false,
                    profileForm: { phone: '', email: '', address: '' },
                    usernameForm: { value: '' },
                    passwordForm: { current: '', new1: '', new2: '' },
                    profileLoading: false,
                    waTestMessage: '',
                    allowedUnits: Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS.map(u => String(u).toLowerCase()) : [],
                    notificationCounts: { total: 0, student_incidents: 0, counseling_tickets: 0, facility_tickets: 0, vehicle_lending: 0, resource_lending: 0 },
                    showNotifications: false,
                    incidentQuery: '', incidentSuggestions: [], incidentStudent: null, incidentSelected: [], incidentForm: { category: 'VIOLATION', title: '', description: '', severity: 'LOW' }, incidentSubmitting: false, showIncidentModal: false,
                    manualFetchOnly: (typeof window.MANUAL_FETCH_ONLY !== 'undefined' ? window.MANUAL_FETCH_ONLY : false),
                    portalModules: [
                        { title: 'Pelaksanaan & Evaluasi KBM', module: 'academic', gradient: 'from-emerald-400 to-teal-500', items: [ { label: 'Jadwal Pelajaran', icon: 'fas fa-calendar-alt', action: 'modules/academic/schedule.php' }, { label: 'Jadwal Guru Mengajar', icon: 'fas fa-chalkboard-teacher', action: 'modules/academic/teacher_schedule.php' }, { label: 'Presensi Harian', icon: 'fas fa-user-check' }, { label: 'Jurnal Mengajar', icon: 'fas fa-book-open' }, { label: 'Input Nilai', icon: 'fas fa-marker' }, { label: 'e-Raport', icon: 'fas fa-print' }, ] },
                        { title: 'Perencanaan Akademik', module: 'academic', gradient: 'from-blue-400 to-indigo-500', items: [ { label: 'Tahun Ajaran', icon: 'fas fa-calendar-check', action: 'modules/academic/years.php' }, { label: 'Pengaturan & Referensi', icon: 'fas fa-sliders-h', action: 'modules/academic/references.php' }, { label: 'Data Kelas', icon: 'fas fa-chalkboard', action: 'modules/academic/classes.php' }, { label: 'List Mapel', icon: 'fas fa-list-ul', action: 'modules/academic/subjects.php' }, { label: 'Jadwal Guru', icon: 'fas fa-chalkboard-teacher', action: 'modules/academic/teacher_schedule.php' }, { label: 'Jam Pelajaran', icon: 'fas fa-clock', action: 'modules/academic/time_slots.php' }, { label: 'Kalender Akademik', icon: 'far fa-calendar', action: 'modules/academic/calendar.php' }, ] },
                        { title: 'Bimbingan & Konseling', module: 'counseling', gradient: 'from-pink-500 to-rose-500', items: [ { label: 'Buku Kasus', icon: 'fas fa-book-dead' }, { label: 'Poin Pelanggaran', icon: 'fas fa-gavel' }, { label: 'Jadwal Konseling', icon: 'fas fa-calendar-check' }, { label: 'Home Visit', icon: 'fas fa-home' }, { label: 'Peta Kerawanan', icon: 'fas fa-map-marked-alt' }, { label: 'Prestasi & Lomba', icon: 'fas fa-medal' }, { label: 'Minat Bakat', icon: 'fas fa-fingerprint' }, { label: 'Karir & Alumni', icon: 'fas fa-user-graduate' }, { label: 'Lapor Bullying', icon: 'fas fa-user-shield' }, ] },
                        { title: 'Keuangan', module: 'finance', gradient: 'from-orange-400 to-amber-500', items: [ { label: 'Setup Keuangan', icon: 'fas fa-cogs' }, { label: 'Status Pembayaran', icon: 'fas fa-wallet' }, { label: 'Dashboard FLIP', icon: 'fas fa-exchange-alt' }, { label: 'Pembayaran Orang Tua', icon: 'fas fa-hand-holding-usd' }, { label: 'Riwayat Penerimaan', icon: 'fas fa-history' }, ] },
                        { title: 'Inventory & Asset', module: 'inventory', gradient: 'from-emerald-500 to-green-600', items: [ { label: 'Aset Tetap', icon: 'fas fa-building', action: 'modules/inventory/dashboard.php' }, { label: 'Aset Bergerak', icon: 'fas fa-chair', action: 'modules/inventory/dashboard.php' }, { label: 'Kendaraan', icon: 'fas fa-car', action: 'modules/inventory/dashboard.php' }, ] },
                        { title: 'Kepegawaian', module: 'hr', gradient: 'from-violet-500 to-purple-600', items: [ { label: 'Presensi Geolokasi', icon: 'fas fa-map-marker-alt' }, { label: 'Daftar Pegawai', icon: 'fas fa-users' }, { label: 'Perizinan Staf', icon: 'fas fa-envelope-open-text' }, ] },
                        { title: 'Admin', module: 'core', gradient: 'from-cyan-500 to-blue-500', items: [ { label: 'List Akun', icon: 'fas fa-users-cog', action: 'users' }, { label: 'Bagan Lembaga', icon: 'fas fa-sitemap' }, { label: 'Master Lembar Data', icon: 'fas fa-database' }, ] }
                    ],
                    menuStructure: {
                        "MENU UTAMA": [ { id: 'executive-view', label: 'Managerial View', icon: 'fas fa-chart-line', required: 'executive', url: 'modules/executive/index.php', tag: 'custom' }, { id: 'foundation-portal', label: 'Portal Yayasan', icon: 'fas fa-building', required: 'foundation', url: 'modules/foundation/index.php', tag: 'custom' }, { id: 'workspace-portal', label: 'Workspace', icon: 'fas fa-chalkboard-teacher', required: 'workspace', url: 'modules/workspace/index.php', tag: 'custom' }, { id: 'dash', label: 'Dashboard Utama', icon: 'fas fa-th-large', required: 'core', url: 'index.php', tag: 'core' }, { id: 'academic-portal', label: 'Akademik', icon: 'fas fa-graduation-cap', required: 'academic', url: 'modules/academic/index.php', tag: 'core' }, { id: 'finance-portal', label: 'Keuangan Sekolah', icon: 'fas fa-wallet', required: 'finance', url: 'modules/finance/dashboard.php', tag: 'core' }, { id: 'hr-portal', label: 'Kepegawaian Basic', icon: 'fas fa-users', required: 'hr', url: 'modules/personnel/dashboard.php', tag: 'core' }, { id: 'library-portal', label: 'Perpustakaan', icon: 'fas fa-book', required: 'library', url: 'modules/library/index.php', tag: 'core' }, { id: 'inventory-portal', label: 'Inventory & Aset', icon: 'fas fa-boxes', required: 'inventory', url: 'modules/inventory/dashboard.php', tag: 'add-on' }, { id: 'kiosk-portal', label: 'Info Kiosk Display', icon: 'fas fa-tv', required: 'kiosk', url: 'modules/kiosk/settings.php', tag: 'add-on' }, { id: 'boarding-portal', label: 'Asrama', icon: 'fas fa-bed', required: 'boarding', url: 'modules/boarding/index.php', tag: 'add-on' }, { id: 'pos-portal', label: 'POS (Kantin/Toko)', icon: 'fas fa-cash-register', required: 'pos', url: 'modules/pos/dashboard.php', tag: 'add-on' }, { id: 'hr-payroll', label: 'HR & Payroll', icon: 'fas fa-money-check-alt', required: 'hr', tag: 'add-on' }, { id: 'counseling-portal', label: 'BK & Kesiswaan', icon: 'fas fa-user-friends', required: 'counseling', url: 'modules/counseling/index.php', tag: 'add-on' }, { id: 'cleaning-portal', label: 'Kebersihan', icon: 'fas fa-broom', required: 'cleaning', url: 'modules/cleaning/index.php', tag: 'add-on' }, { id: 'security-portal', label: 'Keamanan', icon: 'fas fa-shield-alt', required: 'security', url: 'modules/security/index.php', tag: 'add-on' } ],
                        "PENGATURAN": [ { id: 'profile', label: 'Pengaturan Profile', icon: 'fas fa-id-card', required: 'core', url: 'index.php?page=profile', tag: 'system' }, { id: 'settings', label: 'Pengaturan Sekolah', icon: 'fas fa-school', required: 'core', url: 'index.php?page=settings', tag: 'system' }, { id: 'users', label: 'Manajemen User', icon: 'fas fa-users-cog', required: 'core', url: 'index.php?page=users', tag: 'system' }, { id: 'backup', label: 'Backup & Restore', icon: 'fas fa-history', required: 'core', url: 'modules/admin/backup.php', tag: 'admin' } ]
                    }
                };
            },
            computed: {
                currentDate() { return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }); },
                currentPresetName() { if (this.currentPreset === 'basic') return 'Basic (Core Only)'; if (this.currentPreset === 'partial') return 'Partial (Akademik + Perpus)'; if (this.currentPreset === 'all') return 'All Rounder (Enterprise)'; return 'Custom Configuration'; },
                activeMenuGroups() { const activeModules = this.modules; const allowed = (window.ALLOWED_MODULES || {}); const result = {}; for (const [groupName, items] of Object.entries(this.menuStructure)) { const role = (window.USER_ROLE || '').toUpperCase(); let activeItems = []; if (groupName === 'PENGATURAN' && !['ADMIN','SUPERADMIN'].includes(role)) { activeItems = items.filter(item => item.id === 'profile' && activeModules[item.required] === true); } else { activeItems = items.filter(item => { const key = item.required; return activeModules[key] === true || allowed[key] === true; }); } if (activeItems.length > 0) { result[groupName] = activeItems; } } return result; },
                activePortalModules() { return this.portalModules.filter(section => this.modules[section.module]); }
            },
            watch: {
                currentUnit(newVal) { localStorage.setItem('currentUnit', newVal); this.scheduleData = {}; this.selectedClassId = ''; if (this.manualFetchOnly && (!newVal || newVal === 'all')) return; this.fetchAcademicData(newVal); this.fetchEmployees(newVal); this.fetchOrgStructure(newVal); this.fetchStaffList(); },
                currentPage(newVal) { if (newVal === 'profile') { this.fetchMyProfile(); } if (newVal === 'settings') { this.fetchSettings(); } if (newVal === 'users') { this.fetchUsers(); } }
            },
            mounted() {
                // Apply allowed modules filter (Critical for RBAC)
                if (window.ALLOWED_MODULES) {
                    const keys = Object.keys(this.modules);
                    const allowed = window.ALLOWED_MODULES;
                    const next = {};
                    for (const k of keys) {
                        next[k] = k === 'core' ? true : !!allowed[k];
                    }
                    this.modules = next;
                }

                // Ensure Manual Fetch Mode is respected
                if (window.MANUAL_FETCH_ONLY) { console.log("Manual Fetch Mode Active"); } 
                else { this.fetchAcademicData(this.currentUnit); }
                
                // Hydrate Initial Data
                if (window.INITIAL_AGENDA) { this.renderAgendaHTML(window.INITIAL_AGENDA); }
                else { this.loadDashboardAgenda(); }
                
                if (window.INITIAL_PROFILE) { this.myProfile = window.INITIAL_PROFILE; }
                else { this.fetchMyProfile(); }

                // Remove Skeleton and Show Content
                this.isMounted = true; 
                
                // Lazy Load non-critical data
                setTimeout(() => {
                     this.loadNotifications();
                     this.loadMandatoryClasses(); // Dummy function if not exists
                     this.loadAnnouncements(); // Dummy function if not exists
                }, 1000);
            },
            methods: {
                getBaseUrl() { const fromHeader = window.BASE_URL; if (fromHeader && typeof fromHeader === 'string' && fromHeader.length > 0) { return fromHeader; } const p = window.location.pathname || ''; const m = p.match(/^\/(AIS|AIStest)\//i); return m ? `/${m[1]}/` : '/'; },
                async sendWaTest() { try { const base = this.getBaseUrl(); const r = await fetch(base + 'api/security.php?action=send_wa_test', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message: this.waTestMessage || 'Test WA dari AIS', target: this.schoolSettings.wa_security_target || '' }) }); const j = await r.json(); if (j.success) { alert('Pesan WA terkirim'); } else { alert('Gagal mengirim WA: ' + (j.message || '')); } } catch (e) { alert('Terjadi kesalahan saat mengirim WA'); } },
                toggleNotifications() { this.showNotifications = !this.showNotifications; },
                async loadNotifications() { try { const base = this.getBaseUrl(); const res = await fetch(base + 'api/notifications.php?action=get_counts'); const ct = res.headers.get('content-type') || ''; if (ct.includes('application/json')) { const data = await res.json(); if (data && data.success && data.data) { this.notificationCounts = data.data; } } } catch (_) {} },
                async loadDashboardAgenda() {
                    if (window.INITIAL_AGENDA) { this.renderAgendaHTML(window.INITIAL_AGENDA); return; }
                    try { const now = new Date(); const y = now.getFullYear(); const m = now.getMonth() + 1; const start = `${y}-${String(m).padStart(2, '0')}-01`; const end = new Date(y, m, 0).toISOString().slice(0,10); const base = this.getBaseUrl(); const res = await fetch(base + `api/manage_agenda.php?action=get_agenda&start=${start}&end=${end}&unit=${encodeURIComponent(this.currentUnit)}`); const ct = res.headers.get('content-type') || ''; if (!ct.includes('application/json')) { return; } const data = await res.json(); if (data.success) { this.renderAgendaHTML(data.data); } } catch (e) { console.error("Gagal load agenda:", e); const el = document.getElementById('agendaList'); if(el) el.innerHTML = '<div class="text-red-400 text-sm">Gagal memuat agenda.</div>'; }
                },
                renderAgendaHTML(events) {
                     const container = document.getElementById('agendaList');
                     if (!container) return;
                     if (!events || events.length === 0) { container.innerHTML = '<div class="text-slate-400 italic text-sm">Tidak ada agenda bulan ini.</div>'; return; }
                     let html = '';
                     events.slice(0, 5).forEach(ev => {
                         const d = new Date(ev.start_date);
                         const dateStr = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                         const isGoogle = (String(ev.id).startsWith('google:'));
                         const icon = isGoogle ? '<i class="fab fa-google text-blue-500"></i>' : '<i class="fas fa-calendar-day text-blue-500"></i>';
                         html += `
                            <div class="flex items-start gap-3 p-2 hover:bg-slate-50 rounded transition-colors">
                                <div class="w-10 text-center flex-shrink-0">
                                    <div class="text-xs font-bold text-slate-500 uppercase">${dateStr.split(' ')[1]}</div>
                                    <div class="text-lg font-bold text-slate-800">${dateStr.split(' ')[0]}</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-bold text-slate-800 truncate">${ev.title}</div>
                                    <div class="text-xs text-slate-500 truncate flex items-center gap-1">${icon} ${ev.location || 'Kampus Al-Amanah'}</div>
                                </div>
                            </div>
                         `;
                     });
                     container.innerHTML = html;
                },
                loadMandatoryClasses() { 
                    const el = document.getElementById('mandatoryClassesBox');
                    if (el) el.innerHTML = '<div class="text-slate-400 italic text-sm">Tidak ada kelas wajib saat ini.</div>'; 
                },
                loadAnnouncements() { 
                    const el = document.getElementById('annBox');
                    if (el) el.innerHTML = '<div class="text-slate-400 italic text-sm">Belum ada pengumuman terbaru.</div>'; 
                },
                toggleSidebar() { this.isSidebarOpen = !this.isSidebarOpen; },
                closeSidebar() { this.isSidebarOpen = false; },
                navigate(target) {
                    // Check if target is a simple page ID or a full URL
                    if (target.includes('.php')) {
                        // It's a full URL module, navigate directly
                        window.location.href = target;
                    } else {
                        // It's an internal SPA page (like 'dash', 'settings', 'profile')
                        this.currentPage = target;
                        window.scrollTo(0,0);
                        if (window.history.pushState) {
                            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=' + target;
                            window.history.pushState({path:newUrl},'',newUrl);
                        }
                    }
                },
                async fetchMyProfile() {
                    try {
                        const base = this.getBaseUrl();
                        const res = await fetch(base + 'api/get_profile.php');
                        const data = await res.json();
                        if (data.success) { this.myProfile = data.data; }
                    } catch (e) { console.error(e); }
                },
                toggleEditProfile() {
                    this.profileForm.phone = this.myProfile.person_phone || '';
                    this.profileForm.email = this.myProfile.person_email || this.myProfile.user_email || '';
                    this.profileForm.address = this.myProfile.person_address || '';
                    this.isProfileEditing = true;
                },
                cancelEditProfile() { this.isProfileEditing = false; },
                async saveProfileData() {
                    this.profileLoading = true;
                    try {
                        const base = this.getBaseUrl();
                        const res = await fetch(base + 'api/manage_profile.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'update_bio', ...this.profileForm }) });
                        const d = await res.json();
                        if (d.success) { alert('Profil berhasil diperbarui'); this.fetchMyProfile(); this.isProfileEditing = false; } else { alert(d.message); }
                    } catch (e) { alert('Gagal menyimpan profil'); } finally { this.profileLoading = false; }
                },
                async changePassword() {
                    if (this.passwordForm.new1 !== this.passwordForm.new2) { alert('Konfirmasi password tidak cocok'); return; }
                    this.profileLoading = true;
                    try {
                        const base = this.getBaseUrl();
                        const res = await fetch(base + 'api/manage_profile.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'change_password', current_password: this.passwordForm.current, new_password: this.passwordForm.new1 }) });
                        const d = await res.json();
                        if (d.success) { alert('Password berhasil diubah'); this.passwordForm = { current: '', new1: '', new2: '' }; } else { alert(d.message); }
                    } catch (e) { alert('Gagal mengubah password'); } finally { this.profileLoading = false; }
                },
                async changeUsername() {
                    if (!confirm('Apakah Anda yakin ingin mengubah username?')) return;
                    this.profileLoading = true;
                    try {
                        const base = this.getBaseUrl();
                        const res = await fetch(base + 'api/manage_profile.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'change_username', new_username: this.usernameForm.value }) });
                        const d = await res.json();
                        if (d.success) { alert('Username berhasil diubah. Silakan login ulang.'); window.location.href = 'login.php'; } else { alert(d.message); }
                    } catch (e) { alert('Gagal mengubah username'); } finally { this.profileLoading = false; }
                },
                openIncidentModal() { this.showIncidentModal = true; },
                async submitIncidentReport() {
                    if (!this.incidentForm.title) { alert('Judul laporan wajib diisi'); return; }
                    this.incidentSubmitting = true;
                    try {
                        const base = this.getBaseUrl();
                        const res = await fetch(base + 'api/counseling/incidents.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'create_report', ...this.incidentForm }) });
                        const d = await res.json();
                        if (d.success) { alert('Laporan berhasil dikirim ke BK/Kesiswaan'); this.showIncidentModal = false; this.incidentForm = { category: 'VIOLATION', title: '', description: '', severity: 'LOW' }; } else { alert(d.message || 'Gagal mengirim laporan'); }
                    } catch (e) { alert('Terjadi kesalahan sistem'); } finally { this.incidentSubmitting = false; }
                }
            }
        });

        app.mount('#app');
    </script>
</body>
</html>
