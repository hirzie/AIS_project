<?php
require_once '../../includes/guard.php';
require_login_and_module();
require_once '../../config/database.php';

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

$versionInfo = file_exists('../../config/version.php') ? require '../../config/version.php' : ['version' => 'dev', 'app_name' => 'SekolahOS'];
$changelogContent = file_exists('../../CHANGELOG.md') ? file_get_contents('../../CHANGELOG.md') : "# Changelog not found";

function parseChangelog($markdown) {
    $html = '';
    $lines = explode("\n", $markdown);
    $inList = false;
    $hasStarted = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (preg_match('/^## \[(.*?)\] - (.*)$/', $line, $matches)) {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            if ($hasStarted) { $html .= "</div>"; }
            
            $version = $matches[1];
            $date = $matches[2];
            $html .= "<div class='bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-6'>";
            $html .= "<div class='flex items-center justify-between mb-4 border-b border-slate-100 pb-4'>";
            $html .= "<div class='flex items-center gap-3'>";
            $html .= "<div class='bg-blue-100 text-blue-700 p-2 rounded-lg'><i class='fas fa-code-branch text-xl'></i></div>";
            $html .= "<div>";
            $html .= "<h2 class='text-xl font-bold text-slate-800'>$version</h2>";
            $html .= "<p class='text-xs text-slate-500'>Released on <span class='font-mono'>$date</span></p>";
            $html .= "</div>";
            $html .= "</div>";
            $html .= "<span class='px-3 py-1 rounded-full bg-green-50 text-green-600 text-xs font-bold border border-green-200'>Released</span>";
            $html .= "</div>";
            $hasStarted = true;
        } elseif (preg_match('/^### (.*)$/', $line, $matches)) {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            
            $type = $matches[1];
            $color = 'text-slate-700';
            $bg = 'bg-slate-100';
            
            if ($type == 'Added') { $color = 'text-emerald-600'; $bg = 'bg-emerald-50'; }
            if ($type == 'Fixed') { $color = 'text-red-600'; $bg = 'bg-red-50'; }
            if ($type == 'Changed') { $color = 'text-blue-600'; $bg = 'bg-blue-50'; }
            if ($type == 'Deprecated') { $color = 'text-orange-600'; $bg = 'bg-orange-50'; }
            
            $html .= "<div class='mb-3'>";
            $html .= "<span class='inline-block px-2 py-1 rounded text-xs font-bold uppercase tracking-wider $color $bg mb-2'>$type</span>";
            $html .= "<ul class='space-y-2'>";
            $inList = true;
        } elseif (preg_match('/^- (.*)$/', $line, $matches)) {
            $content = $matches[1];
            $content = preg_replace('/\*\*(.*?)\*\*/', '<span class="font-bold text-slate-800">$1</span>', $content);
            $content = preg_replace('/`(.*?)`/', '<code class="bg-slate-100 px-1 py-0.5 rounded text-red-500 font-mono text-xs">$1</code>', $content);
            
            $html .= "<li class='flex items-start gap-2 text-sm text-slate-600'>";
            $html .= "<i class='fas fa-circle text-[6px] mt-2 text-slate-300'></i>";
            $html .= "<span class='flex-1 leading-relaxed'>$content</span>";
            $html .= "</li>";
        }
    }
    if ($inList) { $html .= "</ul></div>"; }
    if ($hasStarted) { $html .= "</div>"; }
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Version Log - <?php echo $versionInfo['app_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
    
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?php echo $baseUrl; ?>index.php" class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">S</a>
                <h1 class="font-bold text-lg text-slate-800">Version Log</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?php echo $baseUrl; ?>modules/admin/backup.php" class="text-sm font-bold text-slate-600 hover:text-blue-600 flex items-center gap-2 mr-2">
                    <i class="fas fa-history"></i> Backup & Restore
                </a>
                <a href="<?php echo $baseUrl; ?>modules/admin/dev_whiteboard.php" class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center gap-2">
                    <i class="fas fa-chalkboard"></i> Whiteboard Pengembangan
                </a>
                <a href="<?php echo $baseUrl; ?>index.php" class="text-sm font-medium text-slate-500 hover:text-blue-600 flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 py-10">
        <div class="text-center mb-12">
            <span class="text-blue-600 font-bold tracking-wider text-xs uppercase mb-2 block">Development History</span>
            <h2 class="text-3xl font-bold text-slate-900 mb-4">Catatan Perubahan Sistem</h2>
            <p class="text-slate-500 max-w-lg mx-auto leading-relaxed">
                Berikut adalah rekam jejak pembaruan, perbaikan bug, dan penambahan fitur pada aplikasi SekolahOS.
            </p>
        </div>

        <?php echo parseChangelog($changelogContent); ?>
    </main>
</body>
</html>
