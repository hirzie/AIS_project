<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php?tab=settings" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-red-200">
            <i class="fas fa-file-invoice-dollar text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Kategori Pengeluaran</h1>
            <span class="text-xs text-slate-500 font-medium">Pengaturan Pos Biaya & Akuntansi</span>
        </div>
    </div>
</nav>

<main class="flex-1 overflow-hidden flex flex-col md:flex-row bg-slate-50 relative">
    
    <div class="w-full md:w-64 bg-white border-r border-slate-200 flex flex-col h-full shadow-lg z-10">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider mb-4">Cari Kategori</h3>
            <div class="relative">
                <input type="text" v-model="searchQuery" placeholder="Nama Kategori..." class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
                <i class="fas fa-search absolute right-3 top-2.5 text-slate-400"></i>
            </div>
        </div>
        
        <div class="p-6 bg-red-50 m-4 rounded-xl border border-red-100">
            <h4 class="font-bold text-red-800 text-sm mb-2"><i class="fas fa-info-circle mr-1"></i> Informasi</h4>
            <p class="text-xs text-red-600 leading-relaxed">
                Setiap jenis pengeluaran harus memiliki pasangan akun: <br>
                1. <strong>Rekening Kas</strong> (Kredit) - Sumber dana.<br>
                2. <strong>Rekening Beban</strong> (Debit) - Alokasi biaya.
            </p>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full bg-slate-50 overflow-hidden">
        <div class="p-6 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-800">Daftar Pos Pengeluaran</h2>
            <div class="flex gap-2">
                <button @click="fetchData" class="text-slate-500 hover:text-red-600 px-3 py-2 text-sm font-bold"><i class="fas fa-sync mr-1"></i> Refresh</button>
                <button @click="openModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 shadow-sm text-sm">
                    <i class="fas fa-plus mr-2"></i> Tambah Kategori
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-800 text-white font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Nama</th>
                            <th class="px-6 py-4">Kode Rekening</th>
                            <th class="px-6 py-4">Keterangan</th>
                            <th class="px-6 py-4 text-center w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in filteredList" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-center text-slate-500">{{ index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ item.name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ item.code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-12 font-bold text-slate-500">Kas:</span>
                                        <span v-if="item.account_cash_id" class="font-mono font-bold text-slate-700">
                                            {{ getAccountCode(item.account_cash_id) }}
                                        </span>
                                        <span v-else class="text-red-400 italic">Belum diatur</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-12 font-bold text-slate-500">Beban:</span>
                                        <span v-if="item.account_id" class="font-mono font-bold text-slate-700">
                                            {{ getAccountCode(item.account_id) }}
                                        </span>
                                        <span v-else class="text-red-400 italic">Belum diatur</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500 italic truncate max-w-xs">
                                {{ item.description || '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button @click="openModal(item)" class="text-yellow-500 hover:text-yellow-600"><i class="fas fa-edit"></i></button>
                                    <button @click="deleteItem(item.id)" class="text-red-500 hover:text-red-600"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-40 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg overflow-hidden animate-fade">
            <div class="bg-white p-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">Ubah Jenis Pengeluaran</h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-3 items-center gap-4">
                    <label class="text-sm font-bold text-slate-600">Departemen</label>
                    <div class="col-span-2">
                        <select v-model="form.department_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:border-red-500 outline-none">
                            <option value="">-- Pilih Departemen --</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 items-center gap-4">
                    <label class="text-sm font-bold text-slate-600">Nama <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input v-model="form.name" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:border-red-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 items-center gap-4">
                    <label class="text-sm font-bold text-slate-600">Kode Unik</label>
                    <div class="col-span-2">
                        <input v-model="form.code" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm font-mono uppercase focus:border-red-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 items-center gap-4">
                    <label class="text-sm font-bold text-slate-600">Rek Kas <span class="text-red-500">*</span></label>
                    <div class="col-span-2 flex gap-2">
                        <input type="text" :value="getAccountName(form.account_cash_id)" readonly class="flex-1 bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-600 cursor-not-allowed">
                        <button @click="openAccountSelector('cash')" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded text-slate-600 font-bold text-xs border border-slate-300">(..)</button>
                    </div>
                </div>

                <div class="grid grid-cols-3 items-center gap-4">
                    <label class="text-sm font-bold text-slate-600">Rek Beban <span class="text-red-500">*</span></label>
                    <div class="col-span-2 flex gap-2">
                        <input type="text" :value="getAccountName(form.account_id)" readonly class="flex-1 bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-600 cursor-not-allowed">
                        <button @click="openAccountSelector('expense')" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded text-slate-600 font-bold text-xs border border-slate-300">(..)</button>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <label class="text-sm font-bold text-slate-600 mt-2">Keterangan</label>
                    <div class="col-span-2">
                        <textarea v-model="form.description" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:border-red-500 outline-none resize-none"></textarea>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-center gap-2">
                <button @click="saveData" class="bg-blue-600 text-white px-8 py-2 rounded font-bold hover:bg-blue-700 text-sm shadow-lg shadow-blue-200">Simpan</button>
                <button @click="showModal = false" class="bg-red-600 text-white px-8 py-2 rounded font-bold hover:bg-red-700 text-sm shadow-lg shadow-red-200">Tutup</button>
            </div>
        </div>
    </div>

    <div v-if="accountSelector.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md flex flex-col max-h-[80vh]">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Pilih Rekening {{ accountSelector.type === 'cash' ? 'Kas' : 'Beban' }}</h3>
                <button @click="accountSelector.show = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 border-b border-slate-100 bg-slate-50">
                <input v-model="accountSearch" type="text" placeholder="Cari kode atau nama akun..." class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex-1 overflow-y-auto p-2">
                <div v-for="acc in filteredAccounts" :key="acc.id" @click="selectAccount(acc.id)" 
                     class="p-3 hover:bg-blue-50 cursor-pointer rounded border-b border-slate-50 flex justify-between items-center group">
                    <div>
                        <div class="font-bold text-slate-700 text-sm">{{ acc.code }}</div>
                        <div class="text-xs text-slate-500">{{ acc.name }}</div>
                    </div>
                    <i class="fas fa-check text-blue-600 opacity-0 group-hover:opacity-100"></i>
                </div>
                <div v-if="filteredAccounts.length === 0" class="p-4 text-center text-slate-400 italic text-sm">
                    Tidak ada akun ditemukan.
                </div>
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
                units: [],
                searchQuery: '',
                showModal: false,
                accountSearch: '',
                accountSelector: { show: false, type: null },
                form: { id: null, name: '', code: '', account_id: '', account_cash_id: '', department_id: '', description: '' }
            }
        },
        computed: {
            filteredList() {
                if (!this.searchQuery) return this.list;
                const q = this.searchQuery.toLowerCase();
                return this.list.filter(item => 
                    item.name.toLowerCase().includes(q) || 
                    item.code.toLowerCase().includes(q)
                );
            },
            filteredAccounts() {
                let list = this.accounts;
                
                if (this.accountSelector.type === 'cash') {
                    list = list.filter(a => ['ASSET', 'EQUITY'].includes(a.type));
                } else if (this.accountSelector.type === 'expense') {
                    list = list.filter(a => a.type === 'EXPENSE');
                }

                if (!this.accountSearch) return list;
                const q = this.accountSearch.toLowerCase();
                return list.filter(a => a.name.toLowerCase().includes(q) || a.code.toLowerCase().includes(q));
            }
        },
        methods: {
            async fetchData() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.list = data.data.expenseCategories;
                        this.units = data.data.units || [];
                    }
                    const resAcc = await fetch(window.BASE_URL + 'api/finance.php?action=get_accounts');
                    const dataAcc = await resAcc.json();
                    if (dataAcc.success) {
                        this.accounts = dataAcc.data;
                    }
                } catch (e) {}
            },
            getAccountCode(id) {
                const acc = this.accounts.find(a => a.id == id);
                return acc ? acc.code + ' ' + acc.name : '-';
            },
            getAccountName(id) {
                const acc = this.accounts.find(a => a.id == id);
                return acc ? acc.code + ' - ' + acc.name : '';
            },
            openModal(item = null) {
                if (item) {
                    this.form = { ...item };
                } else {
                    this.form = { id: null, name: '', code: '', account_id: '', account_cash_id: '', department_id: '', description: '' };
                }
                this.showModal = true;
            },
            openAccountSelector(type) {
                this.accountSelector = { show: true, type: type };
                this.accountSearch = '';
            },
            selectAccount(id) {
                if (this.accountSelector.type === 'cash') {
                    this.form.account_cash_id = id;
                } else {
                    this.form.account_id = id;
                }
                this.accountSelector.show = false;
            },
            async saveData() {
                if (!this.form.name) return alert('Nama wajib diisi');
                if (!this.form.account_cash_id) return alert('Rekening Kas wajib dipilih');
                if (!this.form.account_id) return alert('Rekening Beban wajib dipilih');

                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=save_expense_category', {
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
                if(!confirm('Hapus kategori ini?')) return;
                alert('Fitur hapus dinonaktifkan.');
            }
        },
        mounted() {
            this.fetchData();
        }
    }).mount('#app')
</script>
</body>
</html>
