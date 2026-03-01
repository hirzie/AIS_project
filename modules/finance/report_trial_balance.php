<?php
require_once '../../includes/guard.php';
require_login_and_module('finance');
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php?tab=reports" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-balance-scale text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Neraca Percobaan</h1>
                <span class="text-xs text-slate-500 font-medium">Trial Balance Report</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-y-auto p-6 custom-scrollbar">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Mulai</label>
                    <input type="date" v-model="filter.startDate" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Akhir</label>
                    <input type="date" v-model="filter.endDate" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Unit</label>
                    <select v-model="filter.unitPrefix" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 min-w-[150px]">
                        <option value="">Semua Unit</option>
                        <option v-for="u in units" :key="u.id" :value="u.receipt_code">{{ u.receipt_code }}</option>
                    </select>
                </div>
                <button @click="fetchData" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm shadow-lg shadow-indigo-200 h-[38px]">
                    <i class="fas fa-search mr-2"></i> Tampilkan
                </button>
                <div class="flex gap-2 ml-auto">
                    <button @click="captureBalance" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 text-sm shadow-sm h-[38px]" title="Simpan posisi neraca saat ini untuk laporan yayasan">
                        <i class="fas fa-paper-plane mr-2"></i> Laporkan
                    </button>
                     <button @click="exportExcel" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 text-sm shadow-sm h-[38px]">
                        <i class="fas fa-file-excel mr-2"></i> Excel
                    </button>
                    <button onclick="window.print()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg font-bold hover:bg-slate-200 text-sm h-[38px]">
                        <i class="fas fa-print mr-2"></i> Cetak
                    </button>
                </div>
            </div>

            <div id="foundation-logs" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-700">Riwayat Laporan Yayasan</h3>
                    <button @click="fetchFoundationLogs" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                </div>
                <div class="p-4 space-y-2 max-h-40 overflow-y-auto">
                    <div v-for="log in foundationLogs.slice(0,10)" :key="(log.created_at || '') + (log.title || '')" class="flex items-start justify-between">
                        <div class="text-xs">
                            <div class="font-bold text-slate-800">{{ log.title || '-' }}</div>
                            <div class="text-slate-500">{{ log.user_name || '-' }}</div>
                        </div>
                        <div class="text-[11px] text-slate-400">{{ formatLogDate(log.created_at) }}</div>
                    </div>
                    <div v-if="foundationLogs.length === 0" class="text-xs text-slate-400">Belum ada log laporan.</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold uppercase">Total Harta (Aset)</p>
                            <h3 class="text-2xl font-bold text-slate-800">{{ formatMoney(summaryAssets) }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold uppercase">Total Kas & Bank</p>
                            <h3 class="text-2xl font-bold text-slate-800">{{ formatMoney(summaryCashBank) }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                            <i class="fas fa-hand-holding-usd text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold uppercase">Total Piutang</p>
                            <h3 class="text-2xl font-bold text-slate-800">{{ formatMoney(summaryReceivables) }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                            <i class="fas fa-receipt text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-bold uppercase">Total Kas Bon (Open)</p>
                            <h3 class="text-2xl font-bold text-slate-800">{{ formatMoney(summaryAdvances) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-800 text-white font-bold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-4 w-12 text-center">No</th>
                                <th class="px-6 py-4 w-24">Kode</th>
                                <th class="px-6 py-4">Rekening</th>
                                <th class="px-6 py-4 text-right w-48">Debet</th>
                                <th class="px-6 py-4 text-right w-48">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, index) in list" :key="row.code" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 text-center text-slate-500">{{ index + 1 }}</td>
                                <td class="px-6 py-3 font-mono text-slate-600">{{ row.code }}</td>
                                <td class="px-6 py-3 font-bold text-slate-700">{{ row.name }}</td>
                                <td class="px-6 py-3 text-right font-mono text-slate-700">
                                    {{ row.view_debit > 0 ? formatMoney(row.view_debit) : '' }}
                                </td>
                                <td class="px-6 py-3 text-right font-mono text-slate-700">
                                    {{ row.view_credit > 0 ? formatMoney(row.view_credit) : '' }}
                                </td>
                            </tr>
                            <tr v-if="list.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data transaksi.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-100 font-bold text-slate-800 border-t-2 border-slate-300">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center uppercase tracking-widest">Total</td>
                                <td class="px-6 py-4 text-right">{{ formatMoney(totalDebit) }}</td>
                                <td class="px-6 py-4 text-right">{{ formatMoney(totalCredit) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                list: [],
                units: [],
                advancesAmount: 0,
                foundationLogs: [],
                filter: {
                    startDate: new Date().toISOString().slice(0, 8) + '01', 
                    endDate: new Date().toISOString().slice(0, 10),
                    unitPrefix: ''
                }
            }
        },
        computed: {
            totalDebit() {
                return this.list.reduce((sum, row) => sum + Number(row.view_debit), 0);
            },
            totalCredit() {
                return this.list.reduce((sum, row) => sum + Number(row.view_credit), 0);
            },
            summaryAssets() {
                return this.list.filter(r => r.type === 'ASSET').reduce((sum, row) => sum + (row.view_debit - row.view_credit), 0);
            },
            summaryCashBank() {
                return this.list.filter(r => r.type === 'ASSET' && r.code.startsWith('11')).reduce((sum, row) => sum + (row.view_debit - row.view_credit), 0);
            },
            summaryReceivables() {
                return this.list.filter(r => r.type === 'ASSET' && r.code.startsWith('15')).reduce((sum, row) => sum + (row.view_debit - row.view_credit), 0);
            },
            summaryAdvances() {
                return this.advancesAmount || 0;
            }
        },
        methods: {
            getBaseUrl() {
                return window.BASE_URL;
            },
            async fetchInit() {
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.units = data.data.units.filter(u => u.receipt_code);
                    }
                } catch (e) { console.error(e); }
            },
            async fetchFoundationLogs() {
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/get_activity_logs.php?module=FINANCE&category=FOUNDATION_REPORT&limit=20');
                    const data = await res.json();
                    if (data.success) this.foundationLogs = data.data || [];
                    else this.foundationLogs = [];
                } catch (e) {
                    this.foundationLogs = [];
                }
            },
            async fetchData() {
                try {
                    const res = await fetch(this.getBaseUrl() + `api/finance.php?action=get_trial_balance&start_date=${this.filter.startDate}&end_date=${this.filter.endDate}&unit_prefix=${this.filter.unitPrefix}`);
                    const data = await res.json();
                    if (data.success) {
                        this.list = data.data.filter(row => {
                            if (row.type === 'ASSET') return true;
                            return row.view_debit > 0 || row.view_credit > 0;
                        });
                    }
                    
                    const resAdv = await fetch(this.getBaseUrl() + `api/finance.php?action=get_cash_advances&status=OUTSTANDING`);
                    const dataAdv = await resAdv.json();
                    if (dataAdv.success) {
                        this.advancesAmount = dataAdv.data.reduce((sum, item) => {
                            if (item.status === 'OPEN') return sum + Number(item.amount);
                            if (item.status === 'SETTLED') return sum + Number(item.actual_amount);
                            return sum;
                        }, 0);
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            formatLogDate(dt) {
                if (!dt) return '-';
                const d = new Date(dt);
                const opts = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
                return d.toLocaleDateString('id-ID', opts);
            },
            formatMoney(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency', currency: 'IDR', minimumFractionDigits: 0
                }).format(amount);
            },
            exportExcel() {
                let csv = 'No,Kode,Rekening,Debet,Kredit\n';
                this.list.forEach((row, index) => {
                    csv += `${index+1},${row.code},"${row.name}",${row.view_debit},${row.view_credit}\n`;
                });
                csv += `,,TOTAL,${this.totalDebit},${this.totalCredit}\n`;
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'neraca_percobaan.csv';
                a.click();
            },
            async captureBalance() {
                if (!confirm('Apakah Anda yakin ingin melaporkan posisi neraca per tanggal ' + this.filter.endDate + ' ke Yayasan?')) return;
                
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=capture_balance_sheet', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            report_date: this.filter.endDate,
                            unit_prefix: this.filter.unitPrefix || 'ALL',
                            notes: 'Laporan manual dari Neraca Percobaan',
                            cash_advances_amount: this.advancesAmount || 0
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(data.message + '\n\nTotal Aset: ' + this.formatMoney(data.data.totals.ASSET) + '\nSurplus/Defisit: ' + this.formatMoney(data.data.surplus));
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan koneksi.');
                }
            }
        },
        mounted() {
            this.fetchInit();
            this.fetchData();
            this.fetchFoundationLogs();
        }
    }).mount('#app')
</script>
</body>
</html>
