<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-red-200">
            <i class="fas fa-book text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Jurnal Pengeluaran</h1>
            <span class="text-xs text-slate-500 font-medium">Laporan Transaksi Pengeluaran</span>
        </div>
    </div>
</nav>

<main class="flex-1 overflow-hidden flex flex-col bg-slate-50 relative">
    
    <div class="bg-white p-4 border-b border-slate-200 flex flex-wrap gap-4 items-end shadow-sm z-10">
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Mulai</label>
            <input type="date" v-model="filter.startDate" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-red-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Akhir</label>
            <input type="date" v-model="filter.endDate" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-red-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Unit</label>
            <select v-model="filter.unitPrefix" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-red-500 min-w-[150px]">
                <option value="">Semua Unit</option>
                <option v-for="u in units" :key="u.id" :value="u.receipt_code">{{ u.receipt_code }}</option>
            </select>
        </div>
        <button @click="fetchData" class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-red-700 text-sm shadow-lg shadow-red-200 h-[38px]">
            <i class="fas fa-search mr-2"></i> Tampilkan
        </button>
        <button onclick="window.print()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg font-bold hover:bg-slate-200 text-sm h-[38px]">
            <i class="fas fa-print mr-2"></i> Cetak
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-800 text-white font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4 w-16 text-center">No</th>
                        <th class="px-6 py-4 w-48">No. Jurnal / Tanggal</th>
                        <th class="px-6 py-4">Transaksi</th>
                        <th class="px-6 py-4 w-1/3">Detail Jurnal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="(item, index) in list" :key="item.id" class="hover:bg-slate-50 transition-colors align-top">
                        <td class="px-6 py-4 text-center text-slate-500 font-mono">{{ index + 1 }}</td>
                        
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ item.journal_no }}</div>
                            <div class="text-xs text-slate-500 mt-1">
                                <i class="far fa-calendar-alt mr-1"></i> {{ formatDate(item.journal_date) }}
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800 text-base mb-1">{{ item.description }}</div>
                            <div class="flex gap-4 text-xs text-slate-500 mt-2">
                                <div v-if="item.pic">
                                    <span class="font-bold">Petugas:</span> {{ item.pic || '-' }}
                                </div>
                                <div v-if="item.receiver">
                                    <span class="font-bold">Dibayar ke:</span> {{ item.receiver || '-' }}
                                </div>
                            </div>
                        </td>

                        <td class="px-0 py-0">
                            <table class="w-full">
                                <tbody class="divide-y divide-slate-5">
                                    <tr v-for="j in item.items" :key="j.id" class="bg-slate-50/50">
                                        <td class="px-4 py-2 w-16 text-xs font-mono text-slate-500">{{ j.code }}</td>
                                        <td class="px-2 py-2 text-xs font-medium text-slate-700">{{ j.account_name }}</td>
                                        <td class="px-4 py-2 text-right text-xs w-28 font-mono">
                                            <span v-if="j.debit > 0" class="text-slate-800 font-bold">{{ formatMoney(j.debit) }}</span>
                                            <span v-else class="text-slate-300">-</span>
                                        </td>
                                        <td class="px-4 py-2 text-right text-xs w-28 font-mono">
                                            <span v-if="j.credit > 0" class="text-slate-800 font-bold">{{ formatMoney(j.credit) }}</span>
                                            <span v-else class="text-slate-300">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr v-if="list.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">
                            Belum ada jurnal pengeluaran pada periode ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                list: [],
                units: [],
                filter: {
                    startDate: new Date().toISOString().slice(0, 8) + '01', 
                    endDate: new Date().toISOString().slice(0, 10),
                    unitPrefix: ''
                }
            }
        },
        methods: {
            async fetchInit() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.units = data.data.units.filter(u => u.receipt_code);
                    }
                } catch (e) { console.error(e); }
            },
            async fetchData() {
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_expense_journals&start_date=${this.filter.startDate}&end_date=${this.filter.endDate}&unit_prefix=${this.filter.unitPrefix}`);
                    const data = await res.json();
                    if (data.success) {
                        this.list = data.data;
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            formatDate(date) {
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = String(d.getFullYear()).slice(-2);
                const hour = String(d.getHours()).padStart(2, '0');
                const min = String(d.getMinutes()).padStart(2, '0');
                return `${day}/${month}/${year} ${hour}:${min}`;
            },
            formatMoney(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency', currency: 'IDR', minimumFractionDigits: 0
                }).format(amount);
            }
        },
        mounted() {
            this.fetchInit();
            this.fetchData();
        }
    }).mount('#app')
</script>

<?php require_once '../../includes/footer_finance.php'; ?>
</body>
</html>
