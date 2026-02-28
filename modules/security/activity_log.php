<?php
require_once '../../includes/guard.php';
require_login_and_module('security');
require_once '../../includes/header.php';
?>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors" title="Kembali">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-200">
                <i class="fas fa-clipboard-list text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Log Aktivitas Security</h1>
                <span class="text-xs text-slate-500 font-medium">Rapat, Pengajuan, Zero Report, Checklist</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button id="refreshBtn" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200 text-slate-600 hover:text-slate-800">Refresh</button>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <button data-tab="MEETING" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200 bg-white shadow text-indigo-600 font-bold">Rapat</button>
                        <button data-tab="APPROVAL" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200 text-slate-600 hover:text-slate-800">Pengajuan</button>
                        <button data-tab="ZERO_REPORT" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200 text-slate-600 hover:text-slate-800">Zero Report</button>
                        <button data-tab="CHECKLIST" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200 text-slate-600 hover:text-slate-800">Checklist</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Entity</th>
                                <th class="px-4 py-3">Judul</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </thead>
                        <tbody id="logRows" class="divide-y divide-slate-100">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once '../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeTab = 'MEETING';
    const base = (() => {
        let b = window.BASE_URL || '/';
        if (b === '/' || !b) {
            const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
            b = m ? `/${m[1]}/` : '/';
        }
        return b;
    })();
    const tabButtons = document.querySelectorAll('button[data-tab]');
    const refreshBtn = document.getElementById('refreshBtn');
    const rowsEl = document.getElementById('logRows');
    function renderRows(arr) {
        rowsEl.innerHTML = '';
        if (!Array.isArray(arr) || arr.length === 0) {
            rowsEl.innerHTML = '<tr><td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Belum ada catatan aktivitas.</td></tr>';
            return;
        }
        for (const row of arr) {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-2 text-slate-500 text-xs">${row.created_at || '-'}</td>
                <td class="px-4 py-2 text-slate-700 text-xs">${row.category || '-'}</td>
                <td class="px-4 py-2 text-slate-700 text-xs">${row.action || '-'}</td>
                <td class="px-4 py-2 text-slate-700 text-xs">
                    <div class="font-mono">${row.entity_type || '-'}</div>
                    <div class="text-[10px] text-slate-500">${row.entity_id || '-'}</div>
                </td>
                <td class="px-4 py-2 text-slate-700 text-sm">${row.title || '-'}</td>
                <td class="px-4 py-2 text-slate-600 text-sm">${row.description || ''}</td>
                <td class="px-4 py-2 text-slate-600 text-xs">${row.people_name || row.username || '-'}</td>
            `;
            rowsEl.appendChild(tr);
        }
    }
    async function fetchLogs() {
        rowsEl.innerHTML = '<tr><td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Memuat data...</td></tr>';
        let url = '';
        if (activeTab === 'MEETING') url = base + 'api/get_activity_logs.php?module=EXECUTIVE&category=MEETING&division=SECURITY&limit=200';
        else if (activeTab === 'APPROVAL') url = base + 'api/get_activity_logs.php?module=EXECUTIVE&category=APPROVAL&division=SECURITY&limit=200';
        else if (activeTab === 'CHECKLIST') url = base + 'api/get_activity_logs.php?module=SECURITY&category=CHECKLIST&division=SECURITY&limit=200';
        else url = base + 'api/get_activity_logs.php?module=BOARDING&category=ZERO_REPORT&division=SECURITY&limit=200';
        try {
            const r = await fetch(url);
            const j = await r.json();
            const arr = j.success ? (j.data || []) : [];
            renderRows(arr);
        } catch(_) {
            renderRows([]);
        }
    }
    function setActive(t) {
        activeTab = t;
        tabButtons.forEach(b => {
            const on = b.getAttribute('data-tab') === t;
            b.className = 'px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200 ' + (on ? 'bg-white shadow text-indigo-600 font-bold' : 'text-slate-600 hover:text-slate-800');
        });
        fetchLogs();
    }
    tabButtons.forEach(b => b.addEventListener('click', () => setActive(b.getAttribute('data-tab'))));
    refreshBtn.addEventListener('click', () => fetchLogs());
    setActive('MEETING');
});
</script>
