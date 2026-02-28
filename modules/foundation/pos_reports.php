<?php
require_once '../../includes/guard.php';
require_login_and_module('foundation');
$baseIncluded = require_once '../../includes/header.php';
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
        <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-orange-200">
            <i class="fas fa-cash-register text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Harian POS</h1>
            <span class="text-xs text-slate-500 font-medium">Monitoring Unit Usaha (Kantin & Koperasi)</span>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?php echo $currentDate; ?></span>
    </div>
</nav>

<main id="app" class="flex-1 overflow-y-auto p-8 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="bg-orange-50 border border-orange-100 rounded-xl p-6 flex items-start gap-4">
            <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center flex-none">
                <i class="fas fa-info-circle text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-orange-800 mb-1">Informasi Laporan POS</h3>
                <p class="text-sm text-orange-700 leading-relaxed">
                    Data di bawah ini merupakan laporan omzet dan saldo harian yang dikirimkan oleh kasir unit usaha.
                    Pastikan saldo fisik sesuai dengan saldo yang dilaporkan.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Riwayat Laporan POS</h3>
                <button @click="fetchData" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-sync-alt mr-1"></i> Refresh Data</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Tanggal Laporan</th>
                            <th class="px-6 py-4 text-right">Pemasukan</th>
                            <th class="px-6 py-4 text-right">Pengeluaran</th>
                            <th class="px-6 py-4 text-right">Saldo Hari Ini</th>
                            <th class="px-6 py-4">Catatan</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in reports" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ formatDate(item.report_date) }}</div>
                                <div class="text-xs text-slate-400 mt-1">Lapor: {{ formatDateTime(item.created_at) }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600 font-medium">{{ formatCurrency(item.total_income) }}</td>
                            <td class="px-6 py-4 text-right font-mono text-red-600 font-medium">{{ formatCurrency(item.total_expense) }}</td>
                            <td class="px-6 py-4 text-right font-mono text-blue-600 font-bold">{{ formatCurrency(item.surplus_deficit) }}</td>
                            <td class="px-6 py-4 text-slate-500 italic text-xs max-w-xs truncate" :title="item.notes">{{ item.notes }}</td>
                            <td class="px-6 py-4 text-center">
                                <button @click="deleteReport(item)" class="text-slate-400 hover:text-red-600 transition-colors" title="Hapus Laporan"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="reports.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data laporan POS yang masuk.</td>
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
        return { reports: [] }
    },
    computed: {
        currentDate() {
            return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=get_foundation_pos_reports');
                const json = await res.json();
                if (json.success) { this.reports = json.data; }
            } catch (e) {}
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
        },
        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        formatDateTime(dateStr) {
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) + ' ' + new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
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
