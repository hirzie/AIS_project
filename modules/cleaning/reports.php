<?php
require_once '../../includes/guard.php';
require_login_and_module('cleaning');
require_once '../../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-chart-bar text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan & Statistik Kebersihan</h1>
                <span class="text-xs text-slate-500 font-medium">Per ruangan & per pegawai</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-2">Statistik Performa Staf</h3>
                <canvas id="performanceChart" width="400" height="200"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-2">Ringkasan Per Ruangan</h3>
                <p class="text-sm text-slate-600">Grafik detail akan ditambahkan setelah data tersedia.</p>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Budi', 'Siti', 'Ahmad'],
            datasets: [{
                label: 'Rata-rata Skor Kebersihan',
                data: [85, 92, 78],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
});
</script>
<?php require_once '../../includes/footer.php'; ?>

