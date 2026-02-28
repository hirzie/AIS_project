<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/executive/index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Tren Keuangan</h1>
                <span class="text-xs text-slate-500 font-medium">Executive View</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-chart-line text-emerald-500"></i> Tren 6 Bulan
                    </h3>
                    <button @click="fetchData" class="text-xs font-bold text-blue-600 hover:text-blue-700"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                </div>
                <div class="h-96 w-full">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return { baseUrl: window.BASE_URL || '/', chartData: null }
    },
    methods: {
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        async fetchData() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_executive_summary.php');
                const data = await res.json();
                if (data.success) {
                    this.chartData = data.data.chart;
                    this.renderChart();
                }
            } catch (_) {}
        },
        renderChart() {
            if(!this.chartData) return;
            const ctx = document.getElementById('financeChart');
            if(!ctx) return;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.chartData.labels,
                    datasets: [
                        { label: 'Pemasukan', data: this.chartData.income, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3 },
                        { label: 'Pengeluaran', data: this.chartData.expense, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    },
    mounted() { this.fetchData(); }
}).mount('#app');
</script>
