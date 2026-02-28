<?php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$port = $_SERVER['SERVER_PORT'] ?? 80;
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($host, 'localhost') !== false || $host === '127.0.0.1') {
    if ($port == 8000 || $port == 8080) {
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
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1');
$isTest = (!$isLocalEnv) && (preg_match('#^/AIStest/#i', $scriptName) || stripos($serverName, 'test') !== false);
$vueFile = $isLocalEnv || $isTest ? 'vue.global.js' : 'vue.global.prod.js';
if (!file_exists(dirname(__DIR__) . '/assets/js/' . $vueFile)) {
    $vueFile = 'vue.global.js';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SekolahOS - Sistem Manajemen Sekolah Terintegrasi</title>
    <script>
        const BASE_URL = '<?php echo $baseUrl; ?>';
        window.BASE_URL = BASE_URL;
        window.ALLOWED_MODULES = <?php echo json_encode($_SESSION['allowed_modules'] ?? []); ?>;
        window.USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
        window.USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
        window.PERSON_ID = <?php echo json_encode($_SESSION['person_id'] ?? null); ?>;
        window.ALLOWED_UNITS = <?php echo json_encode(array_keys(array_filter($_SESSION['allowed_units'] ?? []))); ?>;
        window.USE_GLOBAL_APP = true;
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (empty($SKIP_VUE_IN_HEADER)): ?>
    <script src="<?php echo $baseUrl; ?>assets/js/<?php echo $vueFile; ?>"></script>
    <?php endif; ?>
    <!-- Local Font Awesome -->
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <style>
        [v-cloak] { display: none !important; }
        
        /* SIDEBAR FALLBACK CSS (Prevents FOUC/Crash appearance) */
        aside[v-cloak] {
            display: flex !important;
            flex-direction: column;
            background-color: #0f172a; /* bg-slate-900 */
            width: 16rem; /* w-64 */
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 30;
        }
        @media (min-width: 1024px) {
            aside[v-cloak] {
                position: static;
                transform: none !important;
            }
        }

        .fade-enter-active, .fade-leave-active { transition: opacity 0.5s; }
        .fade-enter-from, .fade-leave-to { opacity: 0; }
        
        /* SKELETON LOADER CSS */
        .skeleton {
            background-color: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .skeleton::after {
            content: "";
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            transform: translateX(-100%);
            background-image: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0,
                rgba(255, 255, 255, 0.2) 20%,
                rgba(255, 255, 255, 0.5) 60%,
                rgba(255, 255, 255, 0)
            );
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ORG CHART CSS */
        .org-tree ul { padding-top: 20px; position: relative; transition: all 0.5s; }
        .org-tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 20px 5px 0 5px; transition: all 0.5s; }
        .org-tree li::before, .org-tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #cbd5e1; width: 50%; height: 20px; }
        .org-tree li::after { right: auto; left: 50%; border-left: 2px solid #cbd5e1; }
        .org-tree li:only-child::after, .org-tree li:only-child::before { display: none; }
        .org-tree li:only-child { padding-top: 0; }
        .org-tree li:first-child::before, .org-tree li:last-child::after { border: 0 none; }
        .org-tree li:last-child::before { border-right: 2px solid #cbd5e1; border-radius: 0 5px 0 0; }
        .org-tree li:first-child::after { border-radius: 5px 0 0 0; }
        .org-tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #cbd5e1; width: 0; height: 20px; }
        .org-node { display: inline-block; background: white; border: 1px solid #e2e8f0; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); min-width: 150px; position: relative; z-index: 2; }
        .org-node:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-color: #3b82f6; transform: translateY(-2px); transition: all 0.3s; }
        
        /* HYBRID LAYOUT FOR LARGE TEAMS */
        .org-grid-wrapper { padding-top: 20px; position: relative; display: flex; justify-content: center; }
        .org-grid-wrapper::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #cbd5e1; width: 0; height: 20px; }
        .org-grid-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
            gap: 15px; 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 12px; 
            border: 1px dashed #cbd5e1; 
            max-width: 900px;
        }
        .org-node-compact { 
            background: white; border: 1px solid #e2e8f0; padding: 8px; border-radius: 6px; text-align: left; 
            display: flex; align-items: center; gap: 8px; font-size: 11px;
        }
        .env-badge {
            position: fixed;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            padding: 8px 16px;
            border-radius: 9999px;
            color: #fff;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            letter-spacing: 0.4px;
            font-size: 12px;
            pointer-events: none;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var check = function () {
                var appEl = document.querySelector('#app');
                var mounted = appEl && appEl.__vue_app__;
                if (!mounted && window.__GLOBAL_APP_MOUNTED) mounted = true;
                if (mounted) {
                    document.querySelectorAll('[v-cloak]').forEach(function (el) { el.removeAttribute('v-cloak'); });
                    return true;
                }
                return false;
            };
            if (!check()) {
                var attempts = 0;
                var timer = setInterval(function () {
                    attempts++;
                    if (check() || attempts >= 30) {
                        clearInterval(timer);
                    }
                }, 200);
                // Fallback: uncloak after grace period to avoid blank screen
                setTimeout(function () {
                    if (!check()) {
                        // Only remove if we are sure it's stuck, but 2s is too fast for mobile
                        // Increasing to 10s to prevent FOUC on slow networks
                        console.warn('Vue mount timed out, forcing uncloak.');
                        document.querySelectorAll('[v-cloak]').forEach(function (el) { el.removeAttribute('v-cloak'); });
                    }
                }, 10000);
            }
        });
    </script>
</head>
<body class="bg-gray-100 font-sans text-slate-800">
<?php
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1');
    $isTest = (!$isLocalEnv) && (preg_match('#^/AIStest/#i', $scriptName) || stripos($serverName, 'test') !== false);
    $envLabel = $isLocalEnv ? 'LOCAL' : ($isTest ? 'TESTING' : 'PRODUCTION');
    $envColor = $isLocalEnv ? '#475569' : ($isTest ? '#dc2626' : '#10b981');
?>
<div class="env-badge hidden md:block" style="background: <?php echo $envColor; ?>"><?php echo $envLabel; ?></div>
<div id="js-error-overlay" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-[2000]">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center">
            <div id="js-error-title" class="font-bold text-slate-800">Terjadi Kesalahan Aplikasi</div>
            <button onclick="document.getElementById('js-error-overlay').classList.add('hidden')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4">
            <pre id="js-error-details" class="text-xs text-slate-600 overflow-auto max-h-64 whitespace-pre-wrap"></pre>
        </div>
        <div class="p-4 border-t border-slate-100 text-right">
            <button onclick="document.getElementById('js-error-overlay').classList.add('hidden')" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Tutup</button>
        </div>
    </div>
</div>
