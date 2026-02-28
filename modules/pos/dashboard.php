<?php
require_once '../../includes/guard.php';
require_login_and_module('pos');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>
<div id="app" v-cloak class="flex flex-col h-screen">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-orange-200">
                <i class="fas fa-cash-register text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Point of Sale (POS)</h1>
                <span class="text-xs text-slate-500 font-medium">Kantin & Koperasi Sekolah</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
            <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </button>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-8 bg-slate-50 relative">
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
            <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-orange-100/50 blur-3xl"></div>
            <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-yellow-100/50 blur-3xl"></div>
        </div>
        <div class="max-w-6xl mx-auto relative z-10 space-y-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-calendar-day text-orange-500"></i> Laporan Harian ({{ currentDate }})
                    </h2>
                    <div class="flex gap-2">
                        <button @click="reportToFoundation" class="text-sm bg-orange-600 text-white px-3 py-1 rounded-lg hover:bg-orange-700 transition-colors shadow-sm">
                            <i class="fas fa-paper-plane mr-1"></i> Laporkan
                        </button>
                        <button @click="fetchDailyReport" class="text-sm text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1 rounded-lg">
                            <i class="fas fa-sync-alt mr-1"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Pemasukan</p>
                            <p class="text-xl font-bold text-slate-800">{{ formatCurrency(dailyReport.income) }}</p>
                        </div>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 border border-red-100 flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-red-600 uppercase tracking-wider">Pengeluaran</p>
                            <p class="text-xl font-bold text-slate-800">{{ formatCurrency(dailyReport.expense) }}</p>
                        </div>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Saldo Hari Ini</p>
                            <p class="text-xl font-bold text-slate-800">{{ formatCurrency(dailyReport.balance) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="#" class="block">
                            <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Kasir / Transaksi</h3>
                            <p class="text-sm text-slate-500 mb-4">Buka menu kasir untuk transaksi penjualan barang.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-orange-600 transition-colors">
                                <i class="fas fa-desktop w-5 text-center"></i> Buka Kasir
                            </a>
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-orange-600 transition-colors">
                                <i class="fas fa-history w-5 text-center"></i> Riwayat Penjualan
                            </a>
                        </div>
                    </div>
                </div>
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="#" class="block">
                            <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Stok Barang</h3>
                            <p class="text-sm text-slate-500 mb-4">Kelola inventaris barang, harga jual, dan stok masuk.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-list w-5 text-center"></i> Daftar Barang
                            </a>
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-truck-loading w-5 text-center"></i> Stok Masuk
                            </a>
                        </div>
                    </div>
                </div>
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="<?php echo $baseUrl; ?>modules/pos/report.php" class="block">
                            <div class="w-14 h-14 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-green-600 group-hover:text-white transition-colors">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Laporan Penjualan</h3>
                            <p class="text-sm text-slate-500 mb-4">Rekapitulasi penjualan harian, bulanan, dan laba rugi.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="<?php echo $baseUrl; ?>modules/pos/report.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                                <i class="fas fa-file-invoice-dollar w-5 text-center"></i> Laporan Harian
                            </a>
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                                <i class="fas fa-chart-pie w-5 text-center"></i> Grafik Penjualan
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
                            <p class="text-sm text-slate-500 mb-4">Konfigurasi printer, kategori barang, dan metode pembayaran.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                                <i class="fas fa-tags w-5 text-center"></i> Kategori
                            </a>
                            <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                                <i class="fas fa-print w-5 text-center"></i> Printer
                            </a>
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
        return {
            baseUrl: window.BASE_URL || '/',
            dailyReport: { income: 0, expense: 0, balance: 0 }
        }
    },
    computed: {
        currentDate() {
            return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    },
    mounted() {
        this.fetchDailyReport();
    },
    methods: {
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
        },
        async fetchDailyReport() {
            try {
                this.normalizeBaseUrl();
                const response = await fetch(this.baseUrl + 'api/pos.php?action=get_daily_summary');
                const result = await response.json();
                if (result.success) {
                    this.dailyReport = result.data;
                }
            } catch (error) {
                console.error('Error fetching daily report:', error);
            }
        },
        async reportToFoundation() {
            if (!confirm('Laporkan posisi keuangan hari ini ke Yayasan?')) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/pos.php?action=capture_daily_report', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        report_date: new Date().toISOString().split('T')[0],
                        notes: 'Laporan POS Harian'
                    })
                });
                const json = await res.json();
                alert(json.message);
            } catch (e) {
                console.error(e);
                alert('Gagal melaporkan data.');
            }
        }
    }
}).mount('#app');
</script>
