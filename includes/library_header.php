<?php
require_once __DIR__ . '/guard.php';
require_login_and_module('library');
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = ($current_page === 'index.php');
?>

<?php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$port = $_SERVER['SERVER_PORT'] ?? 80;
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($host, 'localhost') !== false || $host === '127.0.0.1') {
    if ($port == 8000) {
        $baseUrl = '/';
    } else {
        if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
            $baseUrl = '/' . $m[1] . '/';
        } else {
            $baseUrl = '/AIS/';
        }
    }
} else {
    if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
        $baseUrl = '/' . $m[1] . '/';
    } else {
        $baseUrl = '/';
    }
}
?>

<!-- TOP NAVIGATION BAR -->
<script>
    window.BASE_URL = '<?php echo $baseUrl; ?>';
    window.ALLOWED_MODULES = <?php echo json_encode($_SESSION['allowed_modules'] ?? []); ?>;
    window.USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
</script>
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative flex-none">
    <?php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1');
        $isTest = (!$isLocalEnv) && (preg_match('#^/AIStest/#i', $scriptName) || stripos($serverName, 'test') !== false);
        $envLabel = $isLocalEnv ? 'LOCAL' : ($isTest ? 'TESTING' : 'PRODUCTION');
        $envColor = $isLocalEnv ? 'bg-slate-600' : ($isTest ? 'bg-red-600' : 'bg-emerald-600');
    ?>
    <div class="hidden md:block absolute left-1/2 -translate-x-1/2 top-0 z-20 mt-1 pointer-events-none">
        <span class="<?php echo $envColor; ?> text-white font-bold px-4 py-1.5 rounded-full text-[12px] shadow-md tracking-wide"><?php echo $envLabel; ?></span>
    </div>
    <div class="flex items-center gap-3">
        <?php if (!$is_dashboard): ?>
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
        <?php else: ?>
            <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                <i class="fas fa-book-reader text-xl"></i>
            </div>
        <?php endif; ?>
        
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Perpustakaan</h1>
            <span class="text-xs text-slate-500 font-medium">SekolahOS Library</span>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <div class="hidden md:flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
            <a href="catalog.php" class="px-3 py-1.5 rounded-md text-xs transition-all <?= $current_page === 'catalog.php' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700' ?>">Katalog</a>
            <a href="visits.php" class="px-3 py-1.5 rounded-md text-xs transition-all <?= $current_page === 'visits.php' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700' ?>">Kunjungan</a>
            <a href="schedule.php" class="px-3 py-1.5 rounded-md text-xs transition-all <?= $current_page === 'schedule.php' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700' ?>">Jadwal</a>
            <a href="members.php" class="px-3 py-1.5 rounded-md text-xs transition-all <?= $current_page === 'members.php' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700' ?>">Anggota</a>
            <a href="reports.php" class="px-3 py-1.5 rounded-md text-xs transition-all <?= $current_page === 'reports.php' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700' ?>">Sirkulasi</a>
        </div>

        <div class="h-8 w-px bg-slate-200"></div>

        <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>
