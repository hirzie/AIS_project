<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php?tab=settings" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-hand-holding-usd text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Jenis Penerimaan</h1>
            <span class="text-xs text-slate-500 font-medium">Pengaturan Pos Pendapatan</span>
        </div>
    </div>
</nav>

<main class="flex-1 overflow-hidden flex flex-col md:flex-row bg-slate-50 relative">
    <div class="w-full md:w-72 bg-white border-r border-slate-200 flex flex-col h-full shadow-lg z-10">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider mb-4">Kategori Penerimaan</h3>
            <div class="relative">
                <select v-model="filterCategory" class="w-full appearance-none bg-slate-50 border border-slate-200 text-slate-700 py-3 px-4 rounded-xl focus:outline-none focus:border-blue-500 font-medium text-sm shadow-sm">
                    <option value="ALL">Semua Kategori</option>
                    <option value="MANDATORY_STUDENT">Iuran Wajib Siswa</option>
                    <option value="VOLUNTARY_STUDENT">Iuran Sukarela Siswa</option>
                    <option value="MANDATORY_PROSPECT">Iuran Wajib Calon Siswa</option>
                    <option value="VOLUNTARY_PROSPECT">Iuran Sukarela Calon Siswa</option>
                    <option value="OTHER">Penerimaan Lainnya</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>
        </div>
        
        <div class="p-6 bg-blue-50 m-4 rounded-xl border border-blue-100">
            <h4 class="font-bold text-blue-800 text-sm mb-2"><i class="fas fa-info-circle mr-1"></i> Informasi</h4>
            <p class="text-xs text-blue-600 leading-relaxed">
                <span v-if="filterCategory === 'MANDATORY_STUDENT'">
                    Tagihan rutin yang dibebankan kepada siswa (SPP, Uang Gedung). Memerlukan akun Piutang.
                </span>
                <span v-else-if="filterCategory === 'VOLUNTARY_STUDENT'">
                    Sumbangan sukarela dari siswa (Infaq, Wakaf). Tidak mencatat piutang.
                </span>
                <span v-else>
                    Pilih kategori untuk melihat penjelasan.
                </span>
            </p>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full bg-slate-50 overflow-hidden">
        <div class="p-6 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-800">Daftar Pos Penerimaan</h2>
            <div class="flex gap-2">
                <button @click="fetchData" class="text-slate-500 hover:text-blue-600 px-3 py-2 text-sm font-bold"><i class="fas fa-sync mr-1"></i> Refresh</button>
                <button @click="openModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 shadow-sm text-sm">
                    <i class="fas fa-plus mr-2"></i> Tambah Pos
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-800 text-white font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Nama Penerimaan</th>
                            <th class="px-6 py-4">Kode Rekening (Mapping)</th>
                            <th class="px-6 py-4 text-center w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in filteredList" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-center text-slate-500">{{ index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ item.name }}</div>
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ item.code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-20 font-bold text-slate-500">Kas:</span>
                                        <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-mono">{{ getAccountCode(item.account_cash_id) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-20 font-bold text-slate-500">Pendapatan:</span>
                                        <span class="bg-green-50 px-2 py-0.5 rounded text-green-700 font-mono font-bold">{{ getAccountCode(item.account_revenue_id) }}</span>
                                    </div>
                                    <div v-if="item.category.includes('MANDATORY')" class="flex items-center gap-2 text-xs">
                                        <span class="w-20 font-bold text-slate-500">Piutang:</span>
                                        <span class="bg-red-50 px-2 py-0.5 rounded text-red-700 font-mono">{{ getAccountCode(item.account_receivable_id) }}</span>
                                    </div>
                                    <div v-if="item.account_discount_id" class="flex items-center gap-2 text-xs">
                                        <span class="w-20 font-bold text-slate-500">Diskon:</span>
                                        <span class="bg-yellow-50 px-2 py-0.5 rounded text-yellow-700 font-mono">{{ getAccountCode(item.account_discount_id) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button @click="openModal(item)" class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 hover:bg-yellow-200 flex items-center justify-center transition-colors"><i class="fas fa-edit"></i></button>
                                    <button @click="deleteItem(item.id)" class="w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredList.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">Tidak ada data untuk kategori ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                list: [],
                accounts: [],
                filterCategory: 'MANDATORY_STUDENT',
                showModal: false,
                form: { 
                    id: null, name: '', code: '', category: 'MANDATORY_STUDENT', type: 'MONTHLY', default_amount: 0,
                    account_revenue_id: '', account_receivable_id: '', account_cash_id: '', account_discount_id: ''
                }
            }
        },
        computed: {
            filteredList() {
                if (this.filterCategory === 'ALL') return this.list;
                return this.list.filter(item => item.category === this.filterCategory);
            }
        },
        methods: {
            async fetchData() {
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_settings`);
                    const data = await res.json();
                    if (data.success) {
                        this.list = data.data.paymentTypes;
                    }
                    const resAcc = await fetch(`${window.BASE_URL}api/finance.php?action=get_accounts`);
                    const dataAcc = await resAcc.json();
                    if (dataAcc.success) {
                        this.accounts = dataAcc.data;
                    }
                } catch (e) {}
            },
            getAccountCode(id) {
                const acc = this.accounts.find(a => a.id == id);
                return acc ? `${acc.code} - ${acc.name}` : '-';
            },
            openModal(item = null) {
                if (item) {
                    this.form = { ...item };
                } else {
                    this.form = { 
                        id: null, name: '', code: '', category: this.filterCategory === 'ALL' ? 'MANDATORY_STUDENT' : this.filterCategory, 
                        type: 'MONTHLY', default_amount: 0,
                        account_revenue_id: '', account_receivable_id: '', account_cash_id: '', account_discount_id: ''
                    };
                    const cash = this.accounts.find(a => a.code === '111');
                    if (cash) this.form.account_cash_id = cash.id;
                }
                this.showModal = true;
            },
            async saveData() {
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=save_payment_type_full`, {
                        method: 'POST',
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showModal = false;
                        this.fetchData();
                    } else { alert(data.message); }
                } catch (e) {}
            },
            async deleteItem(id) {
                if(!confirm('Hapus jenis penerimaan ini?')) return;
                alert('Fitur hapus dinonaktifkan untuk demo.');
            }
        },
        mounted() {
            this.fetchData();
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
