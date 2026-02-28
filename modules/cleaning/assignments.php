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
            <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-user-check text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Penempatan Petugas</h1>
                <span class="text-xs text-slate-500 font-medium">Tentukan penugasan ruangan</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Form Penempatan</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500">Pilih Staf</label>
                        <select id="staff" class="mt-1 w-full border border-slate-300 rounded px-3 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">ID Ruangan</label>
                        <input id="room" class="mt-1 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: R-101">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Tanggal Penugasan</label>
                        <input id="date" type="date" class="mt-1 w-full border border-slate-300 rounded px-3 py-2 text-sm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button id="saveBtn" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-sm">Simpan Penempatan</button>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Catatan</h3>
                <p class="text-sm text-slate-600">Data ruangan berasal dari modul Pengaturan. Jika tabel belum tersedia, penyimpanan akan menampilkan pesan kesalahan yang aman.</p>
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
    async function loadStaff() {
        const res = await fetch(getBaseUrl() + 'api/get_all_staff.php');
        let data = [];
        try { data = await res.json(); } catch(_) {}
        const sel = document.getElementById('staff');
        sel.innerHTML = '';
        (data || []).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            sel.appendChild(opt);
        });
    }
    async function saveAssignment() {
        const staffId = document.getElementById('staff').value;
        const roomId = document.getElementById('room').value.trim();
        const date = document.getElementById('date').value;
        if (!staffId || !roomId) { alert('Staf dan ruangan wajib diisi'); return; }
        try {
            const res = await fetch(getBaseUrl() + 'api/cleaning.php?action=assign_person', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ staff_id: staffId, room_id: roomId, date })
            });
            const data = await res.json();
            if (data.success) alert('Penempatan berhasil disimpan');
            else alert(data.message || 'Gagal menyimpan');
        } catch (e) { alert('Terjadi kesalahan sistem'); }
    }
    await loadStaff();
    document.getElementById('saveBtn').addEventListener('click', saveAssignment);
});
</script>
<?php require_once '../../includes/footer.php'; ?>
