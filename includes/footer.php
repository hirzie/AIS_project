<?php
if (!isset($baseUrl)) {
    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? 80;
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (($serverName === 'localhost' || $serverName === '127.0.0.1') && $port != 8000 && $port != 8080) {
        if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
            $baseUrl = '/' . $m[1] . '/';
        } else {
            $baseUrl = '/AIS/';
        }
    } else {
        if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
            $baseUrl = '/' . $m[1] . '/';
        } else {
            $baseUrl = '/';
        }
    }
}
?>
    </div> <!-- End of #app -->

    <?php 
    $versionInfo = file_exists(__DIR__ . '/../config/version.php') ? require __DIR__ . '/../config/version.php' : ['version' => 'dev'];
    ?>
    <footer class="text-center py-4 text-xs text-slate-400">
        &copy; <?php echo date('Y'); ?> <?php echo $versionInfo['app_name'] ?? 'SekolahOS'; ?> 
        <a href="<?php echo $baseUrl; ?>modules/admin/version_log.php" class="ml-2 px-2 py-0.5 rounded bg-slate-200 text-slate-600 font-mono hover:bg-slate-300 transition-colors" title="Lihat Log Perubahan">v<?php echo $versionInfo['version']; ?></a>
    </footer>

    <!-- SHEETJS for Excel Import -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- Vue Application -->
    <script>
        (function() {
            try {
                var hasAppEl = !!document.querySelector('#app');
                var useGlobal = (typeof window.USE_GLOBAL_APP === 'boolean') ? window.USE_GLOBAL_APP : true;
                var hasLocalMount = false;
                if (useGlobal) {
                    var scripts = document.getElementsByTagName('script');
                    for (var i = 0; i < scripts.length; i++) {
                        var txt = scripts[i].text || scripts[i].textContent || '';
                        if (txt.indexOf("mount('#app'") !== -1) { hasLocalMount = true; break; }
                    }
                }
                window.SKIP_GLOBAL_APP = (!hasAppEl) || (!useGlobal) || hasLocalMount;
            } catch (e) {
                window.SKIP_GLOBAL_APP = true;
            }
        })();
    </script>
    <script>
        (function () {
            var uncloak = function () {
                var nodes = document.querySelectorAll('[v-cloak]');
                for (var i = 0; i < nodes.length; i++) { nodes[i].removeAttribute('v-cloak'); }
            };
            window.addEventListener('error', uncloak);
            window.addEventListener('unhandledrejection', uncloak);
            setTimeout(uncloak, 600);
        })();
    </script>
    <script>
        (function () {
            if (window.SKIP_GLOBAL_APP) return;
            var ensureVue = function () {
                return new Promise(function (resolve) {
                    if (window.Vue && window.Vue.createApp) { resolve(); return; }
                    var s = document.createElement('script');
                    s.src = '<?php echo $baseUrl; ?>assets/js/vue.global.js';
                    s.onload = function () { resolve(); };
                    s.onerror = function () { resolve(); };
                    document.head.appendChild(s);
                });
            };
            ensureVue().then(function () {
                import('<?php echo $baseUrl; ?>assets/js/app.js?ts=' + Date.now()).catch(function (e) { console.error('Import app.js error:', e); var nodes = document.querySelectorAll('[v-cloak]'); for (var i = 0; i < nodes.length; i++) { nodes[i].removeAttribute('v-cloak'); } });
            });
        })();
    </script>
</body>
</html>
