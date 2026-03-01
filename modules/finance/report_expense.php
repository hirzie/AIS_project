<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative shrink-0">
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-200 hover:text-blue-600 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-800 leading-tight">Laporan Pengeluaran</h1>
                <p class="text-xs text-slate-500 font-medium">Rekapitulasi dan Detail Pengeluaran</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-slate-100 rounded-lg p-1">
                <input v-model="filter.startDate" type="date" class="bg-white border border-slate-200 rounded px-3 py-1.5 text-xs font-bold text-slate-600 focus:border-blue-500 outline-none shadow-sm">
                <span class="text-slate-400 text-xs">s/d</span>
                <input v-model="filter.endDate" type="date" class="bg-white border border-slate-200 rounded px-3 py-1.5 text-xs font-bold text-slate-600 focus:border-blue-500 outline-none shadow-sm">
            </div>
            <div class="h-8 w-px bg-slate-200 mx-1"></div>
            <select v-model="filter.unitPrefix" class="border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:border-blue-500 outline-none bg-white shadow-sm min-w-[150px]">
                <option value="">-- Semua Unit --</option>
                <option v-for="u in units" :key="u.id" :value="u.receipt_code">{{ u.receipt_code }} - {{ u.name }}</option>
            </select>
            <button @click="fetchData" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                <i class="fas fa-filter"></i> Tampilkan
            </button>
            <button @click="printReport" class="bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </nav>

    <div class="flex-1 flex overflow-hidden">
        <div class="w-full md:w-4/12 lg:w-3/12 bg-white border-r border-slate-200 flex flex-col z-10 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
            <div class="p-4 bg-slate-50/50 border-b border-slate-100">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Kategori</h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Pilih kategori untuk melihat detail</p>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <div v-if="categories.length === 0" class="p-8 text-center text-slate-400 italic text-xs">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-300">
                        <i class="fas fa-inbox text-xl"></i>
                    </div>
                    Tidak ada data pengeluaran pada periode ini.
                </div>
                <ul class="divide-y divide-slate-50">
                    <li v-for="cat in categories" :key="cat.id" 
                        @click.stop="selectCategory(cat)"
                        class="px-5 py-4 cursor-pointer hover:bg-blue-50/50 transition-all group relative border-b border-slate-50"
                        :class="{'bg-blue-50 border-r-4 border-blue-600': selectedCategory && selectedCategory.id == cat.id}">
                        <div class="flex justify-between items-start mb-1 pointer-events-none">
                            <span class="text-sm font-bold w-2/3 transition-colors" :class="selectedCategory && selectedCategory.id == cat.id ? 'text-blue-700' : 'text-slate-700 group-hover:text-blue-600'">{{ cat.name }}</span>
                            <i v-if="selectedCategory && selectedCategory.id == cat.id" class="fas fa-chevron-right text-blue-600 text-xs mt-1"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-500 group-hover:text-slate-600 flex justify-between items-center mt-2 pointer-events-none">
                            <span>Total</span>
                            <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 group-hover:bg-white group-hover:shadow-sm">{{ formatMoney(cat.total_amount) }}</span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="p-5 bg-slate-800 text-white mt-auto shadow-[0_-4px_20px_rgba(0,0,0,0.1)] relative z-20">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-widest mb-1">Grand Total</p>
                        <span class="text-xl font-bold font-mono tracking-tight">{{ formatMoney(grandTotal) }}</span>
                    </div>
                    <div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center text-slate-300">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 bg-slate-50 flex flex-col overflow-hidden relative">
            <div v-if="selectedCategory" class="flex-1 flex flex-col h-full">
                <div class="px-8 py-6 bg-white border-b border-slate-200 shadow-sm flex justify-between items-end shrink-0">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-tags"></i>
                            </div>
                            <h3 class="font-bold text-2xl text-slate-800">{{ selectedCategory.name }}</h3>
                        </div>
                        <p class="text-sm text-slate-500">Menampilkan seluruh transaksi pengeluaran untuk kategori ini.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-400 font-bold uppercase mb-1">Total Kategori Ini</p>
                        <h2 class="text-3xl font-bold text-blue-600 font-mono tracking-tight">{{ formatMoney(selectedCategory.total_amount) }}</h2>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[11px] tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 w-32">Tanggal</th>
                                    <th class="px-6 py-4 w-40">No. Transaksi</th>
                                    <th class="px-6 py-4 w-48">Pemohon / Penerima</th>
                                    <th class="px-6 py-4">Keperluan</th>
                                    <th class="px-6 py-4 text-right w-40">Jumlah</th>
                                    <th class="px-6 py-4 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(tx, idx) in details" :key="idx" class="hover:bg-blue-50/30 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-700">{{ formatDate(tx.trans_date) }}</div>
                                        <div class="text-[10px] text-slate-400">{{ formatTime(tx.trans_date) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-slate-500 bg-slate-50/50 rounded">{{ tx.trans_number }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-700 text-xs">{{ tx.pic || '-' }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fas fa-arrow-right text-[9px] mr-1"></i> {{ tx.receiver || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 font-medium">{{ tx.description }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-mono font-bold text-slate-700 group-hover:text-blue-700">{{ formatMoney(tx.amount) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click.stop="deleteTransaction(tx)" class="text-slate-300 hover:text-red-600 transition-colors p-2" title="Hapus Transaksi">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="details.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic bg-slate-50/50">
                                        <div class="mb-2"><i class="far fa-file-alt text-2xl text-slate-300"></i></div>
                                        Tidak ada data detail untuk kategori ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-400 bg-slate-50/50">
                <div class="w-24 h-24 bg-white rounded-full shadow-sm flex items-center justify-center mb-6 text-slate-200 border border-slate-100">
                    <i class="fas fa-chart-pie text-4xl"></i>
                </div>
                <h3 class="font-bold text-lg text-slate-600 mb-2">Belum ada kategori dipilih</h3>
                <p class="text-sm max-w-xs text-center">Silakan pilih salah satu kategori pengeluaran di panel sebelah kiri untuk melihat rincian transaksi.</p>
            </div>
        </div>
    </div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                units: [],
                categories: [],
                details: [],
                selectedCategory: null,
                filter: {
                    startDate: '<?= date('Y-01-01') ?>',
                    endDate: '<?= date('Y-m-d') ?>',
                    unitPrefix: ''
                }
            }
        },
        computed: {
            grandTotal() {
                return this.categories.reduce((sum, cat) => sum + Number(cat.total_amount), 0);
            }
        },
        mounted() {
            this.fetchUnits();
            this.fetchData();
        },
        methods: {
            async fetchUnits() {
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_settings`);
                    const data = await res.json();
                    if (data.success) {
                        this.units = data.data.units;
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            async fetchData() {
                this.selectedCategory = null;
                this.details = [];
                this.categories = [];
                try {
                    const params = new URLSearchParams({
                        action: 'get_expense_summary_by_category',
                        start_date: this.filter.startDate,
                        end_date: this.filter.endDate,
                        unit_prefix: this.filter.unitPrefix
                    });
                    const res = await fetch(`${window.BASE_URL}api/finance.php?${params.toString()}`);
                    const data = await res.json();
                    if (data.success) {
                        this.categories = data.data;
                        if (this.categories.length > 0) {
                            this.selectCategory(this.categories[0]);
                        }
                    }
                } catch (e) {
                    console.error("Fetch Summary Error:", e);
                }
            },
            async selectCategory(cat) {
                if (!cat) return;
                this.selectedCategory = cat;
                this.details = [];
                try {
                    const params = new URLSearchParams({
                        action: 'get_expense_details_by_category',
                        start_date: this.filter.startDate,
                        end_date: this.filter.endDate,
                        unit_prefix: this.filter.unitPrefix,
                        category_id: cat.id
                    });
                    const res = await fetch(`${window.BASE_URL}api/finance.php?${params.toString()}`);
                    const data = await res.json();
                    if (data.success) {
                        this.details = data.data;
                    } else {
                        console.warn("API Error:", data.message);
                    }
                } catch (e) {
                    console.error("Fetch Details Error:", e);
                }
            },
            formatMoney(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
            },
            formatDate(dateString) {
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            },
            formatTime(dateString) {
                return new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            },
            printReport() {
                window.print();
            },
            async deleteTransaction(tx) {
                if (!confirm('Hapus transaksi ini?\n\n' + tx.description + '\n' + tx.trans_number)) return;
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_expense_transaction');
                    formData.append('trans_number', tx.trans_number);
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=delete_expense_transaction`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Transaksi berhasil dihapus.');
                        this.fetchData();
                        if (this.selectedCategory) {
                            this.selectCategory(this.selectedCategory);
                        }
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error: ' + e.message);
                }
            }
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
