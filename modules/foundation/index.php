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
        <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
            <i class="fas fa-building text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Portal Yayasan</h1>
            <span class="text-xs text-slate-500 font-medium">Manajemen Terpadu Yayasan & Sekolah</span>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?php echo $currentDate; ?></span>
        <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>

<main class="flex-1 overflow-y-auto p-8 bg-slate-50 relative" id="app">
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
        <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-indigo-100/50 blur-3xl"></div>
        <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-purple-100/50 blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10 space-y-8">
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Selamat Datang, Pengurus Yayasan</h2>
                <p class="text-slate-500">Berikut adalah ringkasan kinerja operasional dan keuangan sekolah periode ini.</p>
                <p v-if="summary.report_date" class="text-xs text-slate-400 mt-1"><i class="fas fa-clock mr-1"></i> Data per: {{ formatDate(summary.report_date) }}</p>
            </div>
            <div class="flex gap-4">
                <div class="text-right">
                    <p class="text-xs font-bold text-slate-400 uppercase">Total Aset</p>
                    <p class="text-xl font-bold text-indigo-600">{{ formatCurrency(summary.total_assets) }}</p>
                </div>
                <div class="w-px bg-slate-200 h-12"></div>
                <div class="text-right">
                    <p class="text-xs font-bold text-slate-400 uppercase">Surplus Tahun Ini</p>
                    <p class="text-xl font-bold" :class="summary.surplus_deficit >= 0 ? 'text-emerald-600' : 'text-red-600'">
                        {{ summary.surplus_deficit >= 0 ? '+' : '' }} {{ formatCurrency(summary.surplus_deficit) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="#" class="block">
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Tata Usaha Yayasan</h3>
                        <p class="text-sm text-slate-500 mb-4">Administrasi surat menyurat, legalitas, dan kepegawaian yayasan.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-envelope-open-text w-5 text-center"></i> Surat Masuk/Keluar
                        </a>
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-users w-5 text-center"></i> Data Pengurus
                        </a>
                    </div>
                </div>
            </div>

            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-teal-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="<?php echo $baseUrl; ?>modules/foundation/loans.php" class="block">
                        <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Simpan Pinjam Karyawan</h3>
                        <p class="text-sm text-slate-500 mb-4">Pengelolaan koperasi simpan pinjam untuk kesejahteraan karyawan.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="<?php echo $baseUrl; ?>modules/foundation/loans.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-teal-600 transition-colors">
                            <i class="fas fa-user-friends w-5 text-center"></i> Data Peminjaman
                        </a>
                        <a href="<?php echo $baseUrl; ?>modules/foundation/loans.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-teal-600 transition-colors">
                            <i class="fas fa-file-invoice-dollar w-5 text-center"></i> Pengajuan Baru
                        </a>
                    </div>
                </div>
            </div>

            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="<?php echo $baseUrl; ?>modules/foundation/finance_reports.php" class="block">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Laporan Keuangan</h3>
                        <p class="text-sm text-slate-500 mb-4">Konsolidasi laporan keuangan dari semua unit sekolah, POS, dan yayasan.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="<?php echo $baseUrl; ?>modules/foundation/finance_reports.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-school w-5 text-center"></i> Laporan Sekolah
                        </a>
                        <a href="<?php echo $baseUrl; ?>modules/foundation/pos_reports.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-orange-600 transition-colors">
                            <i class="fas fa-cash-register w-5 text-center"></i> Laporan POS
                        </a>
                    </div>
                </div>
            </div>

            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="#" class="block">
                        <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Laporan Tahunan</h3>
                        <p class="text-sm text-slate-500 mb-4">Arsip laporan pertanggungjawaban tahunan yayasan.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                            <i class="fas fa-folder-open w-5 text-center"></i> Arsip Laporan
                        </a>
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                            <i class="fas fa-print w-5 text-center"></i> Cetak Laporan
                        </a>
                    </div>
                </div>
            </div>

            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="#" class="block">
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="fas fa-school"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Operasional Sekolah</h3>
                        <p class="text-sm text-slate-500 mb-4">Monitoring kegiatan operasional dan sarana prasarana unit sekolah.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-clipboard-list w-5 text-center"></i> Laporan Bulanan
                        </a>
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-tools w-5 text-center"></i> Sarana Prasarana
                        </a>
                    </div>
                </div>
            </div>

            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-slate-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="#" class="block">
                        <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-slate-600 group-hover:text-white transition-colors">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Pengaturan</h3>
                        <p class="text-sm text-slate-500 mb-4">Konfigurasi sistem dan manajemen user yayasan.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                            <i class="fas fa-users-cog w-5 text-center"></i> User Management
                        </a>
                        <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                            <i class="fas fa-database w-5 text-center"></i> Backup Data
                        </a>
                    </div>
                </div>
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
            summary: { total_assets: 0, surplus_deficit: 0, report_date: null }
        }
    },
    computed: {
        currentDate() {
            return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    },
    mounted() {
        this.fetchSummary();
    },
    methods: {
        async fetchSummary() {
            try {
                const res = await fetch(baseUrl + 'api/finance.php?action=get_foundation_summary');
                const json = await res.json();
                if (json.success) { this.summary = json.data; }
            } catch (e) {}
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
        },
        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }
    }
}).mount('#app');
</script>
</body>
</html>
