<?php
require_once __DIR__ . '/guard.php';
require_login_and_module('finance');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SekolahOS - Keuangan</title>
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
    <script src="https://cdn.tailwindcss.com"></script>
    <?php
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1' || strpos($host, '192.168.') !== false);
        
        // FORCE VUE DEV VERSION TO PREVENT BLANK SCREEN
        // Because vue.global.prod.js does not exist in assets/js
        $vueFile = 'vue.global.js'; 
    ?>
    <script src="<?php echo $baseUrl; ?>assets/js/<?php echo $vueFile; ?>"></script>
    
    <!-- FAILSAFE: If Vue fails to load locally, try CDN -->
    <script>
        if (typeof Vue === 'undefined') {
            document.write('<script src="https://unpkg.com/vue@3/dist/vue.global.js"><\/script>');
        }
    </script>

    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <!-- Fallback FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" onerror="this.style.display='none'">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        /* V-CLOAK HANDLING */
        [v-cloak] { display: none !important; }
        
        /* LOADING SPINNER WHEN CLOAKED */
        [v-cloak]::before {
            content: "Memuat Aplikasi...";
            display: block;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            color: #64748b;
            font-weight: 600;
            z-index: 9999;
        }
        .fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
        .fade-enter-from, .fade-leave-to { opacity: 0; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Modern Glass Effect */
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        /* App Container */
        #app { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    </style>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
        window.ALLOWED_MODULES = <?php echo json_encode($_SESSION['allowed_modules'] ?? []); ?>;
        window.USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
    </script>
</head>
<body class="text-slate-800">
<?php
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1');
    $isTest = (!$isLocalEnv) && (preg_match('#^/AIStest/#i', $scriptName) || stripos($serverName, 'test') !== false);
    $envLabel = $isLocalEnv ? 'LOCAL' : ($isTest ? 'TESTING' : 'PRODUCTION');
    $envColor = $isLocalEnv ? '#475569' : ($isTest ? '#dc2626' : '#10b981');
?>
<div class="hidden md:block" style="position:fixed;top:8px;left:50%;transform:translateX(-50%);z-index:1000;padding:8px 16px;border-radius:9999px;color:#fff;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,0.15);letter-spacing:0.4px;font-size:12px;pointer-events:none;background: <?php echo $envColor; ?>"><?php echo $envLabel; ?></div>

<!-- SAFETY NET SCRIPT: Remove Blank Screen if Vue Fails -->
<script>
    setTimeout(function() {
        var app = document.getElementById('app');
        if (app && app.hasAttribute('v-cloak')) {
            console.warn('Vue.js took too long to load. Removing v-cloak manually.');
            app.removeAttribute('v-cloak');
            
            // Optional: Show Error
            var errDiv = document.createElement('div');
            errDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;background:#fee2e2;color:#b91c1c;padding:10px;text-align:center;z-index:10000;border-bottom:1px solid #ef4444;';
            errDiv.innerHTML = '<strong>Warning:</strong> Aplikasi berjalan lambat atau ada error. Cek koneksi internet Anda.';
            document.body.appendChild(errDiv);
            setTimeout(() => errDiv.remove(), 5000);
        }
    }, 3000); // 3 Seconds Timeout
</script>

<div id="app" v-cloak>
