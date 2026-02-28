<?php
require_once '../../config/database.php';
require_once '../../includes/header_personnel.php';
?>

<div class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/personnel/dashboard.php" class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center hover:bg-green-600 hover:text-white transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Absensi Pegawai</h1>
                <span class="text-xs text-slate-500 font-medium">Rekap kehadiran dan manajemen jam kerja.</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-auto p-8 flex items-center justify-center">
        <div class="text-center max-w-lg">
            <div class="w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">
                <i class="fas fa-clock"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 mb-3">Fitur Dalam Pengembangan</h2>
            <p class="text-slate-500 mb-8">Halaman <strong>Absensi Pegawai</strong> ini adalah tampilan dummy. Fitur fungsional akan segera hadir dalam update berikutnya.</p>
            
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm text-left">
                <h3 class="font-bold text-slate-700 mb-3 border-b pb-2">Rencana Fitur:</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Import Data Absensi (Fingerprint)</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Rekap Keterlambatan</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Laporan Kehadiran Bulanan</li>
                </ul>
            </div>
        </div>
    </main>
</div>
</div>
<script>
    const { createApp } = Vue
    createApp({}).mount('#app')
</script>
</body>
</html>
