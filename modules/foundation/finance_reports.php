<?php
require_once '../../includes/guard.php';
require_login_and_module('foundation');
require_once '../../includes/header.php';
$__now = new DateTime();
$__days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$__months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$currentDate = $__days[$__now->format('l')] . ', ' . $__now->format('d') . ' ' . $__months[(int)$__now->format('n')] . ' ' . $__now->format('Y');
?>
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/foundation/index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
            <i class="fas fa-file-invoice-dollar text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Keuangan Yayasan (Global)</h1>
            <span class="text-xs text-slate-500 font-medium">Arsip & Monitoring Posisi Neraca</span>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?php echo $currentDate; ?></span>
    </div>
</nav>

<main class="flex-1 overflow-y-auto p-8 bg-slate-50 relative" id="app">
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 rounded-full bg-white/10 blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold mb-1">Konsolidasi Keuangan Yayasan</h2>
                        <p class="text-blue-100 text-sm">Ringkasan total aset likuid (Kas & Bank) dari seluruh unit.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Total Aset Likuid (Kas/Bank)</p>
                        <h3 class="text-4xl font-bold">{{ formatCurrency(consolidated.total_liquid) }}</h3>
                        <div class="mt-2 text-blue-300 text-sm">
                            <span class="block text-[10px] font-bold uppercase">Total Kekayaan (Aset)</span>
                            <span class="font-bold">{{ formatCurrency(consolidated.grand_total_assets) }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-300"><i class="fas fa-school"></i></div>
                            <span class="text-sm font-medium text-blue-100">Total Harta Sekolah (Net)</span>
                        </div>
                        <p class="text-xl font-bold">{{ formatCurrency((consolidated.school_cash + consolidated.school_bank) - consolidated.school_advances) }}</p>
                        <div class="text-[10px] text-blue-200 mt-2 space-y-1">
                            <div class="flex justify-between"><span>Kas Tunai:</span> <span>{{ formatCurrency(consolidated.school_cash) }}</span></div>
                            <div class="flex justify-between"><span>Bank:</span> <span>{{ formatCurrency(consolidated.school_bank) }}</span></div>
                            <div class="flex justify-between text-red-300"><span>Kas Bon (Pending):</span> <span>- {{ formatCurrency(consolidated.school_advances) }}</span></div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center text-orange-300"><i class="fas fa-store"></i></div>
                            <span class="text-sm font-medium text-blue-100">Saldo Toko / POS</span>
                        </div>
                        <p class="text-xl font-bold">{{ formatCurrency(consolidated.pos_balance) }}</p>
                        <p class="text-xs text-blue-200 mt-1">Update: {{ formatDateTime(consolidated.pos_last_update) }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-indigo-300"><i class="fas fa-hand-holding-usd"></i></div>
                            <span class="text-sm font-medium text-blue-100">Koperasi (Likuid & Aset)</span>
                        </div>
                        <p class="text-xl font-bold">{{ formatCurrency(consolidated.coop_total) }}</p>
                        <div class="text-[10px] text-blue-200 mt-2 space-y-1">
                            <div class="flex justify-between"><span>Likuid:</span> <span>{{ formatCurrency(consolidated.coop_cash) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Riwayat Laporan Neraca Sekolah</h3>
                <button @click="fetchData" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-sync-alt mr-1"></i> Refresh Data</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Tanggal Laporan</th>
                            <th class="px-6 py-4">Unit</th>
                            <th class="px-6 py-4 text-right">Total Aset (Harta)</th>
                            <th class="px-6 py-4">Rincian Aset</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in reports" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ formatDate(item.report_date) }}</div>
                                <div class="text-xs text-slate-400 mt-1">Lapor: {{ formatDateTime(item.created_at) }}</div>
                                <div class="text-xs text-slate-500 italic mt-1" v-if="item.notes">{{ item.notes }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold uppercase">{{ item.unit_prefix }}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-indigo-600 font-medium">{{ formatCurrency(item.total_assets) }}</td>
                            <td class="px-6 py-4">
                                <div v-if="item.details" class="text-xs space-y-1">
                                    <div v-for="det in parseDetails(item.details)" :key="det.code" class="flex justify-between gap-4 border-b border-slate-50 pb-1 last:border-0">
                                        <span class="text-slate-600 truncate max-w-[150px]" :title="det.name">{{ det.name }}</span>
                                        <span class="font-mono font-medium text-slate-800">{{ formatCurrency(det.balance) }}</span>
                                    </div>
                                    <div v-if="parseDetails(item.details).length === 0" class="text-slate-400 italic">Tidak ada rincian</div>
                                </div>
                                <div v-else class="text-slate-400 italic text-xs">Tidak ada rincian</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="deleteReport(item)" class="text-slate-400 hover:text-red-600 transition-colors" title="Hapus Laporan"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="reports.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data laporan yang masuk.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Riwayat Laporan Neraca POS</h3>
                <button @click="fetchPosData" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-sync-alt mr-1"></i> Refresh Data</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Tanggal Laporan</th>
                            <th class="px-6 py-4 text-right">Pemasukan</th>
                            <th class="px-6 py-4 text-right">Pengeluaran</th>
                            <th class="px-6 py-4 text-right">Saldo Toko</th>
                            <th class="px-6 py-4">Catatan</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in posReports" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ formatDate(item.report_date) }}</div>
                                <div class="text-xs text-slate-400 mt-1">Lapor: {{ formatDateTime(item.created_at) }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600 font-medium">{{ formatCurrency(item.total_income) }}</td>
                            <td class="px-6 py-4 text-right font-mono text-red-600 font-medium">{{ formatCurrency(item.total_expense) }}</td>
                            <td class="px-6 py-4 text-right font-mono text-orange-600 font-bold">{{ formatCurrency(item.surplus_deficit) }}</td>
                            <td class="px-6 py-4 text-slate-500 italic text-xs max-w-xs truncate" :title="item.notes">{{ item.notes }}</td>
                            <td class="px-6 py-4 text-center">
                                <button @click="deleteReport(item)" class="text-slate-400 hover:text-red-600 transition-colors" title="Hapus Laporan"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="posReports.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data laporan POS yang masuk.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Riwayat Laporan Koperasi / Simpan Pinjam</h3>
                <button @click="fetchCoopData" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-sync-alt mr-1"></i> Refresh Data</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Tanggal Laporan</th>
                            <th class="px-6 py-4 text-right">Total Aset</th>
                            <th class="px-6 py-4">Rincian Posisi</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in coopReports" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ formatDate(item.report_date) }}</div>
                                <div class="text-xs text-slate-400 mt-1">Lapor: {{ formatDateTime(item.created_at) }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-indigo-600 font-medium">{{ formatCurrency(item.total_assets) }}</td>
                            <td class="px-6 py-4">
                                <div v-if="item.details" class="text-xs space-y-1">
                                    <div v-for="det in parseDetails(item.details)" :key="det.code" class="flex justify-between gap-4 border-b border-slate-50 pb-1 last:border-0">
                                        <span class="text-slate-600 truncate max-w-[150px]">{{ det.name }}</span>
                                        <span class="font-mono font-medium text-slate-800">{{ formatCurrency(det.balance) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="deleteReport(item)" class="text-slate-400 hover:text-red-600 transition-colors" title="Hapus Laporan"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="coopReports.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data laporan Koperasi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const { createApp } = Vue;
let baseUrl = window.BASE_URL || '/';
if (baseUrl === '/' || !baseUrl) {
    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
    baseUrl = m ? `/${m[1]}/` : '/';
}
createApp({
    data() {
        return {
            reports: [],
            posReports: [],
            coopReports: [],
            consolidated: {
                school_cash: 0, school_bank: 0, pos_balance: 0, coop_cash: 0,
                total_liquid: 0, grand_total_assets: 0,
                school_last_update: '-', pos_last_update: '-', coop_last_update: '-'
            }
        }
    },
    computed: {
        currentDate() {
            return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    },
    mounted() {
        this.fetchData();
        this.fetchPosData();
        this.fetchCoopData();
        this.fetchConsolidated();
    },
    methods: {
        async fetchConsolidated() {
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=get_consolidated_summary');
                const json = await res.json();
                if (json.success) { this.consolidated = json.data; }
            } catch (e) {}
        },
        async fetchData() {
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=get_foundation_report_list');
                const json = await res.json();
                if (json.success) { this.reports = json.data; }
            } catch (e) {}
        },
        async fetchPosData() {
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=get_foundation_pos_reports');
                const json = await res.json();
                if (json.success) { this.posReports = json.data; }
            } catch (e) {}
        },
        async fetchCoopData() {
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=get_foundation_coop_reports');
                const json = await res.json();
                if (json.success) { this.coopReports = json.data; }
            } catch (e) {}
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
        },
        formatDate(dateStr) {
            if (!dateStr || dateStr === '-') return '-';
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        formatDateTime(dateStr) {
            if (!dateStr || dateStr === '-') return '-';
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) + ' ' + new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },
        parseDetails(json) {
            try { return JSON.parse(json) || []; } catch(e) { return []; }
        },
        async deleteReport(item) {
            if(!confirm('Hapus laporan ini?')) return;
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=delete_foundation_report', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: item.id})
                });
                const json = await res.json();
                if(json.success) {
                    this.fetchData();
                    this.fetchPosData();
                    this.fetchCoopData();
                    this.fetchConsolidated();
                } else {
                    alert(json.message);
                }
            } catch(e) {}
        }
    }
}).mount('#app');
</script>
</body>
</html>
