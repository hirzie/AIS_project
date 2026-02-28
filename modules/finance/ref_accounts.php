<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php?tab=settings" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center text-white shadow-lg">
            <i class="fas fa-book text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Kode Rekening</h1>
            <span class="text-xs text-slate-500 font-medium">Chart of Accounts</span>
        </div>
    </div>
</nav>

<main class="flex-1 overflow-hidden flex flex-col md:flex-row bg-slate-50 relative">
    
    <div class="w-full md:w-64 bg-white border-r border-slate-200 flex flex-col h-full shadow-lg z-10">
        <div class="p-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Kategori Akun</h3>
        </div>
        <div class="flex-1 overflow-y-auto p-2 space-y-1">
             <button @click="filterType = 'ALL'" :class="filterType === 'ALL' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors">
                 Semua Kategori
             </button>
             <button @click="filterType = 'ASSET'" :class="filterType === 'ASSET' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors">
                 HARTA (Assets)
             </button>
             <button @click="filterType = 'LIABILITY'" :class="filterType === 'LIABILITY' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors">
                 KEWAJIBAN (Liabilities)
             </button>
             <button @click="filterType = 'EQUITY'" :class="filterType === 'EQUITY' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors">
                 MODAL (Equity)
             </button>
             <button @click="filterType = 'REVENUE'" :class="filterType === 'REVENUE' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors">
                 PENDAPATAN (Revenue)
             </button>
             <button @click="filterType = 'EXPENSE'" :class="filterType === 'EXPENSE' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors">
                 BEBAN (Expenses)
             </button>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full bg-slate-50 overflow-hidden">
        <div class="p-6 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-800">Daftar Rekening</h2>
            <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 shadow-sm text-sm">
                <i class="fas fa-plus mr-2"></i> Tambah Rekening
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 w-24">Kode</th>
                            <th class="px-6 py-3">Nama Rekening</th>
                            <th class="px-6 py-3">Kategori</th>
                            <th class="px-6 py-3">Posisi Normal</th>
                            <th class="px-6 py-3 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="acc in filteredAccounts" :key="acc.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3 font-mono font-bold text-slate-700">{{ acc.code }}</td>
                            <td class="px-6 py-3 font-medium">{{ acc.name }}</td>
                            <td class="px-6 py-3">
                                <span :class="getTypeBadge(acc.type)" class="px-2 py-1 rounded-full text-xs font-bold">{{ acc.type }}</span>
                            </td>
                            <td class="px-6 py-3 text-slate-500 text-xs uppercase">{{ acc.balance_type }}</td>
                            <td class="px-6 py-3 text-center flex justify-center gap-2">
                                <button @click="openModal(acc)" class="text-blue-600 hover:text-blue-800 p-1"><i class="fas fa-edit"></i></button>
                                <button v-if="!acc.is_system" @click="deleteAccount(acc.id)" class="text-red-600 hover:text-red-800 p-1"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="filteredAccounts.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">Tidak ada data rekening.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-fade">
            <h3 class="text-lg font-bold mb-4 text-slate-800">{{ form.id ? 'Edit' : 'Tambah' }} Rekening</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Kode Rekening</label>
                    <input v-model="form.code" type="text" class="w-full border rounded px-3 py-2 text-sm font-mono" placeholder="e.g. 111">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Rekening</label>
                    <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2 text-sm" placeholder="e.g. Kas Besar">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Kategori</label>
                        <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm bg-white">
                            <option value="ASSET">HARTA (Asset)</option>
                            <option value="LIABILITY">KEWAJIBAN (Liability)</option>
                            <option value="EQUITY">MODAL (Equity)</option>
                            <option value="REVENUE">PENDAPATAN (Revenue)</option>
                            <option value="EXPENSE">BEBAN (Expense)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Saldo Normal</label>
                        <select v-model="form.balance_type" class="w-full border rounded px-3 py-2 text-sm bg-white">
                            <option value="DEBIT">DEBIT</option>
                            <option value="CREDIT">KREDIT</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="showModal = false" class="text-slate-500 font-bold text-sm px-4 py-2">Batal</button>
                <button @click="saveAccount" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 text-sm">Simpan</button>
            </div>
        </div>
    </div>

</main>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                accounts: [],
                filterType: 'ALL',
                showModal: false,
                form: { id: null, code: '', name: '', type: 'ASSET', balance_type: 'DEBIT' }
            }
        },
        computed: {
            filteredAccounts() {
                if (this.filterType === 'ALL') return this.accounts;
                return this.accounts.filter(a => a.type === this.filterType);
            }
        },
        methods: {
            async fetchAccounts() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_accounts');
                    const data = await res.json();
                    if (data.success) this.accounts = data.data;
                } catch (e) {}
            },
            getTypeBadge(type) {
                const map = {
                    'ASSET': 'bg-blue-100 text-blue-700',
                    'LIABILITY': 'bg-orange-100 text-orange-700',
                    'EQUITY': 'bg-purple-100 text-purple-700',
                    'REVENUE': 'bg-green-100 text-green-700',
                    'EXPENSE': 'bg-red-100 text-red-700'
                };
                return map[type] || 'bg-slate-100';
            },
            openModal(acc = null) {
                if (acc) {
                    this.form = { ...acc };
                } else {
                    this.form = { id: null, code: '', name: '', type: 'ASSET', balance_type: 'DEBIT' };
                }
                this.showModal = true;
            },
            async saveAccount() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=save_account', {
                        method: 'POST',
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showModal = false;
                        this.fetchAccounts();
                    } else { alert(data.message); }
                } catch (e) {}
            },
            async deleteAccount(id) {
                if(!confirm('Hapus rekening ini?')) return;
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=delete_account', {
                        method: 'POST',
                        body: JSON.stringify({ id: id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchAccounts();
                    } else { alert(data.message); }
                } catch (e) {
                    alert('Gagal menghapus akun');
                }
            }
        },
        mounted() {
            this.fetchAccounts();
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
</body>
</html>
