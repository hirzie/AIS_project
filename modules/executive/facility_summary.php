<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../includes/header.php';
?>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/executive/index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-200">
                <i class="fas fa-building text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Summary Fasilitas</h1>
                <span class="text-xs text-slate-500 font-medium">Aset & Pengingat Kendaraan</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Total Aset</p>
                        <h4 class="text-xl font-bold text-slate-800">{{ stats.total_assets }}</h4>
                    </div>
                </div>
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Status Kendaraan</h4>
                <div class="space-y-3">
                    <div v-for="car in stats.fleet" :key="car.id" class="flex items-center justify-between p-2 hover:bg-slate-50 rounded transition-colors border border-transparent hover:border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                                <i class="fas fa-car"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-700">{{ car.name }}</p>
                                <p class="text-[10px] text-slate-400">{{ car.license_plate }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded" :class="car.status === 'AVAILABLE' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">{{ car.status }}</span>
                    </div>
                </div>
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-6 mb-3">Pengingat Service & Pajak</h4>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div class="bg-amber-50 p-3 rounded-lg border border-amber-100 text-center">
                        <p class="text-[10px] font-bold text-amber-600 uppercase">Service ≤14 hari</p>
                        <h4 class="text-xl font-bold text-amber-700">
                            {{ vehicles.filter(v => { const d = daysUntil(v.next_service); return d !== null && d > 0 && d <= 14; }).length }}
                        </h4>
                    </div>
                    <div class="bg-red-50 p-3 rounded-lg border border-red-100 text-center">
                        <p class="text-[10px] font-bold text-red-600 uppercase">Pajak Lewat</p>
                        <h4 class="text-xl font-bold text-red-700">
                            {{ vehicles.filter(v => daysOverdue(v.tax_expiry_date) > 0).length }}
                        </h4>
                    </div>
                </div>
                <div class="space-y-2">
                    <div v-for="v in vehicles" :key="v.id" class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px]">
                                <i class="fas fa-car"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-700">{{ v.name }}</p>
                                <p class="text-[10px] text-slate-400">{{ v.license_plate }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p v-if="daysUntil(v.next_service) !== null" :class="(daysUntil(v.next_service) <= 14 && daysUntil(v.next_service) > 0) ? 'text-amber-600 font-bold' : (daysUntil(v.next_service) <= 0 ? 'text-red-600 font-bold' : 'text-slate-600')" class="text-[10px]">
                                {{ daysUntil(v.next_service) <= 0 ? 'Service Lewat ' + Math.abs(daysUntil(v.next_service)) + ' hari' : daysUntil(v.next_service) + ' hari lagi service' }}
                            </p>
                            <p :class="daysOverdue(v.tax_expiry_date) > 0 ? 'text-red-600 font-bold' : 'text-slate-400'" class="text-[10px]">
                                Pajak {{ daysOverdue(v.tax_expiry_date) > 0 ? ('lewat ' + daysOverdue(v.tax_expiry_date) + ' hari') : 'aman' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return { baseUrl: window.BASE_URL || '/', stats: { total_assets: 0, fleet: [] }, vehicles: [] }
    },
    methods: {
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        async fetchSummary() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_executive_summary.php');
                const data = await res.json();
                if (data.success) this.stats = { total_assets: data.data.total_assets, fleet: data.data.fleet || [] };
            } catch (_) {}
        },
        async fetchVehicles() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/inventory.php?action=get_vehicles');
                const data = await res.json();
                if (data.success) this.vehicles = data.data || [];
            } catch (_) {}
        },
        daysUntil(dateStr) {
            if (!dateStr || dateStr === '-') return null;
            const target = new Date(dateStr);
            const today = new Date();
            const diffMs = target.getTime() - today.getTime();
            return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
        },
        daysOverdue(dateStr) {
            if (!dateStr || dateStr === '-') return 0;
            const d = new Date(dateStr);
            const today = new Date();
            const diffMs = today.getTime() - d.getTime();
            return diffMs > 0 ? Math.ceil(diffMs / (1000 * 60 * 60 * 24)) : 0;
        }
    },
    mounted() { this.fetchSummary(); this.fetchVehicles(); }
}).mount('#app');
</script>
