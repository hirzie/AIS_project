<?php
require_once __DIR__ . '/guard.php';
require_login_and_module('hr');
?>
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SekolahOS - Kepegawaian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
        window.ALLOWED_MODULES = <?php echo json_encode($_SESSION['allowed_modules'] ?? []); ?>;
        window.USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
    </script>
    <script src="<?php echo $baseUrl; ?>assets/js/vue.global.js"></script>
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        [v-cloak] { display: none !important; }
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
<div id="app" v-cloak>
