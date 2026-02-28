<?php
require_once '../../includes/guard.php';
require_login_and_module('cleaning');
require_once '../../includes/header.php';
?>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Data Pegawai Kebersihan</h1>
                <span class="text-xs text-slate-500 font-medium">Read-only dari HRD</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Daftar Staf</h3>
                <span id="count" class="text-xs text-slate-500"></span>
            </div>
            <div class="p-4">
                <div id="list" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    function getBaseUrl() {
        let baseUrl = window.BASE_URL || '/';
        if (baseUrl === '/' || !baseUrl) {
            const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
            baseUrl = m ? `/${m[1]}/` : '/';
        }
        return baseUrl;
    }
    const res = await fetch(getBaseUrl() + 'api/get_all_staff.php');
    let data = [];
    try { data = await res.json(); } catch(_) {}
    const list = document.getElementById('list');
    const count = document.getElementById('count');
    count.textContent = (data && data.length) ? (data.length + ' pegawai') : 'Tidak ada data';
    (data || []).forEach(s => {
        const el = document.createElement('div');
        el.className = 'border border-slate-200 rounded-lg p-3 flex items-center justify-between';
        el.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="font-bold text-slate-800">${s.name || '-'}</div>
                    <div class="text-xs text-slate-500">${s.identity_number || ''}</div>
                </div>
            </div>
        `;
        list.appendChild(el);
    });
});
</script>
<?php require_once '../../includes/footer.php'; ?>
