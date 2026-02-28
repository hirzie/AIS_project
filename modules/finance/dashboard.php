<?php
// finance_dashboard.php
require_once '../../includes/guard.php';
require_login_and_module('finance');
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<!-- TOP NAVIGATION BAR -->
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-wallet text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Keuangan Sekolah</h1>
            <span class="text-xs text-slate-500 font-medium">Finance Dashboard</span>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
        <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>

<!-- MAIN CONTENT (LANDING MENU) -->
<main class="flex-1 overflow-y-auto p-8 flex items-center justify-center bg-slate-50 relative" id="app">
    <!-- Background Decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
        <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-blue-100/50 blur-3xl"></div>
        <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-indigo-100/50 blur-3xl"></div>
    </div>

    <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative z-10">
        
        <!-- CARD 1: PENERIMAAN -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/income.php" class="block">
                    <div class="w-14 h-14 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-green-600 group-hover:text-white transition-colors">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Penerimaan</h3>
                    <p class="text-sm text-slate-500 mb-4">Pembayaran SPP, Uang Gedung, dan penerimaan siswa lainnya.</p>
                </a>
                
                <!-- Sub Menu Compact -->
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/finance/ref_receivables.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                        <i class="fas fa-cog w-5 text-center"></i> Jenis Penerimaan
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/set_tariffs.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                        <i class="fas fa-money-check-alt w-5 text-center"></i> Set Tarif Tagihan
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/report_class.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                        <i class="fas fa-clipboard-list w-5 text-center"></i> Laporan Kelas
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/income_journal.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                        <i class="fas fa-book-open w-5 text-center"></i> Jurnal Penerimaan
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD 2: PENGELUARAN -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/expense.php" class="block">
                    <div class="w-14 h-14 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-red-600 group-hover:text-white transition-colors">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Pengeluaran</h3>
                    <p class="text-sm text-slate-500 mb-4">Catat biaya operasional, gaji, dan belanja sekolah.</p>
                </a>
                
                <!-- Sub Menu Compact -->
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/finance/ref_expenses.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-red-600 transition-colors">
                        <i class="fas fa-tags w-5 text-center"></i> Kategori Pengeluaran
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/expense_journal.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-red-600 transition-colors">
                        <i class="fas fa-book w-5 text-center"></i> Jurnal Pengeluaran
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/report_expense.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-red-600 transition-colors">
                        <i class="fas fa-chart-bar w-5 text-center"></i> Laporan Pengeluaran
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD 3: TABUNGAN -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/savings.php" class="block">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Tabungan Siswa</h3>
                    <p class="text-sm text-slate-500 mb-4">Kelola simpanan dan penarikan tabungan siswa.</p>
                </a>
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/finance/savings_journal.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-book w-5 text-center"></i> Jurnal Tabungan
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/report_savings.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-chart-pie w-5 text-center"></i> Laporan Saldo
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/import_savings.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-file-excel w-5 text-center"></i> Import Saldo Tabungan
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD 4: LAPORAN -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php?tab=reports" class="block">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Laporan</h3>
                    <p class="text-sm text-slate-500 mb-4">Lihat neraca, arus kas, dan rekapitulasi keuangan.</p>
                </a>

                <!-- Sub Menu Compact -->
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/finance/report_trial_balance.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-purple-600 transition-colors">
                        <i class="fas fa-balance-scale w-5 text-center"></i> Neraca Percobaan
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/report_expense.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-purple-600 transition-colors">
                        <i class="fas fa-chart-bar w-5 text-center"></i> Laporan Pengeluaran
                    </a>
                    <a href="#" class="flex items-center text-xs font-medium text-slate-500 hover:text-purple-600 transition-colors">
                        <i class="fas fa-file-invoice w-5 text-center"></i> Jurnal Umum
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD 5: PENGATURAN UMUM (Merged) -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-slate-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/ref_general.php" class="block">
                    <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Pengaturan Umum</h3>
                    <p class="text-sm text-slate-500 mb-4">Konfigurasi tahun buku, kode rekening (COA), dan parameter sistem.</p>
                </a>
                
                <!-- Sub Menu Compact -->
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/finance/ref_general.php?tab=fiscal" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fas fa-calendar-alt w-5 text-center"></i> Tahun Buku
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/ref_accounts.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fas fa-book w-5 text-center"></i> Kode Rekening (COA)
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD 6: KAS BON / PANJAR (NEW) -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/cash_advance.php" class="block">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Kas Bon / Panjar</h3>
                    <p class="text-sm text-slate-500 mb-4">Catatan uang keluar sementara (belum ada nota/SPJ).</p>
                </a>
                
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/finance/proposal_payout.php" class="flex items-center text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1.5 rounded hover:bg-amber-100 transition-colors mb-1">
                        <i class="fas fa-file-signature w-5 text-center"></i> Proposal Disetujui
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/cash_advance.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                        <i class="fas fa-list w-5 text-center"></i> Daftar Kas Bon
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/finance/cash_advance.php?action=create" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                        <i class="fas fa-plus-circle w-5 text-center"></i> Buat Baru
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD 7: LOG LAPORAN YAYASAN -->
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/finance/report_logs.php" class="block">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Log Laporan Yayasan</h3>
                    <p class="text-sm text-slate-500 mb-4">Riwayat laporan yang dikirim ke yayasan.</p>
                </a>
                <div class="mt-auto pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Klik untuk melihat log</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    const { createApp } = Vue
    createApp({
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        }
    }).mount('#app')
</script>
</body>
