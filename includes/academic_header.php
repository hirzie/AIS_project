<?php
require_once __DIR__ . '/guard.php';
require_login_and_module('academic');
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = ($current_page === 'index.php' || $current_page === 'academic.php');
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
<?php
$__ts = time();
$__days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$__months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$__formattedDate = $__days[intval(date('w', $__ts))] . ', ' . str_pad(date('d', $__ts), 2, '0', STR_PAD_LEFT) . ' ' . $__months[intval(date('n', $__ts))] . ' ' . date('Y', $__ts);
?>

<!-- TOP NAVIGATION BAR -->
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
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
                <i class="fas fa-graduation-cap text-xl"></i>
            </div>
        <?php endif; ?>
        
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Akademik Sekolah</h1>
            <span class="text-xs text-slate-500 font-medium">Academic Portal</span>
        </div>
    </div>
    
    <div class="flex items-center gap-2 md:gap-4">
        <?php if (!$is_dashboard && $current_page !== 'class_detail.php'): ?>
            <!-- Unit Selector Responsive -->
            <div v-if="availableUnits && availableUnits.length > 0" v-cloak>
                <!-- Desktop: Buttons -->
                <div class="hidden md:flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
                    <button 
                        @click="currentUnit = 'all'" 
                        :class="currentUnit === 'all' ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-700'" 
                        class="px-3 py-1.5 rounded-md text-xs transition-all"
                    >
                        Semua
                    </button>
                    <button 
                        v-for="unit in availableUnits.filter(u => (u.code || u.unit_level || '').toUpperCase() !== 'YAYASAN' && (u.name || '').toUpperCase() !== 'YAYASAN')" 
                        :key="unit.id" 
                        @click="currentUnit = (unit.code || unit.unit_level)" 
                        :class="currentUnit === (unit.code || unit.unit_level) ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-700'" 
                        class="px-3 py-1.5 rounded-md text-xs transition-all"
                    >
                        {{ unit.prefix || unit.code || unit.unit_level || unit.name }}
                    </button>
                </div>
                <!-- Mobile: Dropdown -->
                <div class="block md:hidden">
                    <select v-model="currentUnit" class="bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-28 p-1.5 font-medium">
                        <option value="all">Semua Unit</option>
                        <option 
                            v-for="unit in availableUnits.filter(u => (u.code || u.unit_level || '').toUpperCase() !== 'YAYASAN' && (u.name || '').toUpperCase() !== 'YAYASAN')" 
                            :key="unit.id" 
                            :value="unit.unit_level"
                        >
                            {{ unit.prefix || unit.code || unit.unit_level || unit.name }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200" v-else>
                <button class="px-3 py-1.5 rounded-md text-xs bg-white shadow text-slate-800 font-bold">Loading...</button>
            </div>
        <?php endif; ?>

        <div class="hidden md:block h-8 w-px bg-slate-200"></div>

        <span class="hidden md:inline text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?php echo $__formattedDate; ?></span>
        <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>
