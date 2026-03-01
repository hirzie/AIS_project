<?php
require_once '../../includes/guard.php';
require_login_and_module('finance');
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" v-cloak class="flex flex-col h-screen">
    <!-- TOP NAV -->
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php?tab=reports" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-teal-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-teal-200">
                <i class="fas fa-chart-pie text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Kustom</h1>
                <span class="text-xs text-slate-500 font-medium">Custom Financial Reports</span>
            </div>
        </div>
    </nav>

    <div class="flex-1 overflow-hidden flex bg-slate-50">
        
        <!-- SIDEBAR: REPORT TYPES -->
        <div class="w-64 bg-white border-r border-slate-200 flex flex-col flex-none">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-sm font-bold text-slate-700">Jenis Laporan</h2>
                <button @click="showCreateTypeModal = true; newReportTypeName = ''" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-1 rounded">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                <div v-for="rt in reportTypes" :key="rt.id" 
                     @click="selectReportType(rt)"
                     class="group flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition-colors"
                     :class="currentReportType && currentReportType.id === rt.id ? 'bg-teal-50 text-teal-700' : 'hover:bg-slate-50 text-slate-600'">
                    
                    <div v-if="editingReportType && editingReportType.id === rt.id" class="flex-1 mr-2">
                        <input v-model="editingReportType.name" @keyup.enter="saveReportType" @blur="saveReportType" class="w-full text-xs border rounded px-1 py-0.5" autofocus>
                    </div>
                    <span v-else class="text-sm font-medium truncate">{{ rt.name }}</span>

                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity" v-if="!editingReportType || editingReportType.id !== rt.id">
                        <button @click.stop="editReportType(rt)" class="text-slate-400 hover:text-blue-500"><i class="fas fa-pen text-xs"></i></button>
                        <button @click.stop="deleteReportType(rt.id)" class="text-slate-400 hover:text-red-500"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
            <div class="max-w-5xl mx-auto space-y-6">
                <div v-if="!currentReportType" class="text-center py-20 text-slate-400">
                    <i class="fas fa-chart-pie text-4xl mb-4"></i>
                    <p>Pilih Jenis Laporan di sebelah kiri.</p>
                </div>

                <div v-else>
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-800">{{ currentReportType.name }}</h2>
                        <button @click="openManageGroups" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 transition-colors">
                            <i class="fas fa-cog mr-2"></i> Atur Grup
                        </button>
                    </div>

                    <!-- Filter Bar -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-4 items-end mb-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Mulai</label>
                            <input type="date" v-model="filter.startDate" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Akhir</label>
                            <input type="date" v-model="filter.endDate" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Unit</label>
                            <select v-model="filter.unitId" class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-teal-500 min-w-[150px] h-[38px]">
                                <option value="">Semua Unit</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        <button @click="fetchReport" class="bg-teal-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-teal-700 text-sm shadow-lg shadow-teal-200 h-[38px]">
                            <i class="fas fa-search mr-2"></i> Tampilkan
                        </button>
                        <div class="flex gap-2 ml-auto">
                            <button onclick="window.print()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg font-bold hover:bg-slate-200 text-sm h-[38px]">
                                <i class="fas fa-print mr-2"></i> Cetak
                            </button>
                        </div>
                    </div>

                    <!-- Report Content -->
                    <div v-if="reportData" class="space-y-6">
                        
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                                <p class="text-xs font-bold text-slate-500 uppercase mb-1">Total Surplus/Defisit</p>
                                <h3 class="text-3xl font-bold" :class="reportData.grand_total_surplus >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatMoney(reportData.grand_total_surplus) }}
                                </h3>
                            </div>
                        </div>

                        <!-- Groups -->
                        <div v-for="group in reportData.groups" :key="group.group_id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-slate-800">{{ group.group_name }}</h3>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-500 block">Surplus/Defisit Grup</span>
                                    <span class="text-lg font-bold" :class="group.surplus >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatMoney(group.surplus) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-0">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-xs text-slate-500 font-bold uppercase border-b border-slate-100">
                                        <tr>
                                            <th class="px-6 py-3 text-left">Keterangan Item</th>
                                            <th class="px-6 py-3 text-center w-32">Tipe</th>
                                            <th class="px-6 py-3 text-right w-48">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(item, idx) in group.items" :key="idx" class="hover:bg-slate-50">
                                            <td class="px-6 py-3 text-slate-700">{{ item.name }}</td>
                                            <td class="px-6 py-3 text-center">
                                                <span v-if="item.type.includes('INCOME') || item.type === 'PAYMENT_TYPE'" class="text-[10px] font-bold px-2 py-1 rounded bg-green-50 text-green-600">Pemasukan</span>
                                                <span v-else class="text-[10px] font-bold px-2 py-1 rounded bg-red-50 text-red-600">Pengeluaran</span>
                                            </td>
                                            <td class="px-6 py-3 text-right font-mono" :class="item.type.includes('EXPENSE') ? 'text-red-600' : 'text-green-600'">
                                                {{ item.type.includes('EXPENSE') ? '-' : '+' }} {{ formatMoney(item.amount) }}
                                            </td>
                                        </tr>
                                        <tr v-if="group.items.length === 0">
                                            <td colspan="3" class="px-6 py-8 text-center text-slate-400 italic">Tidak ada item dalam grup ini.</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-slate-50 border-t border-slate-100">
                                        <tr>
                                            <td colspan="2" class="px-6 py-3 text-right font-bold text-slate-600">Total Pemasukan</td>
                                            <td class="px-6 py-3 text-right font-bold text-green-600">{{ formatMoney(group.income) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="px-6 py-3 text-right font-bold text-slate-600">Total Pengeluaran</td>
                                            <td class="px-6 py-3 text-right font-bold text-red-600">{{ formatMoney(group.expense) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                    
                    <div v-else-if="!loading" class="text-center py-12 text-slate-500">
                        <p>Silakan klik "Tampilkan" untuk melihat laporan.</p>
                    </div>
                    
                    <div v-if="loading" class="text-center py-12">
                        <i class="fas fa-spinner fa-spin text-3xl text-teal-600 mb-4"></i>
                        <p class="text-slate-500">Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CREATE REPORT TYPE MODAL -->
    <div v-if="showCreateTypeModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h3 class="font-bold text-lg mb-4">Buat Jenis Laporan Baru</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Laporan</label>
                <input v-model="newReportTypeName" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Contoh: Laporan Asrama / Laporan Makan" @keyup.enter="createReportType" autofocus>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="showCreateTypeModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium">Batal</button>
                <button @click="createReportType" class="px-4 py-2 bg-teal-600 text-white hover:bg-teal-700 rounded-lg text-sm font-medium">Simpan</button>
            </div>
        </div>
    </div>

    <!-- MANAGE GROUPS MODAL -->
    <div v-if="showManageModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-xl">
                <h2 class="text-lg font-bold text-slate-800">
                    Pengaturan Grup: {{ currentReportType ? currentReportType.name : '' }}
                </h2>
                <button @click="showManageModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-hidden flex">
                <!-- Sidebar: List of Groups -->
                <div class="w-1/3 border-r border-slate-100 overflow-y-auto p-4 bg-slate-50">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-700 text-sm">Daftar Grup</h3>
                        <button @click="createGroup" class="text-xs bg-slate-800 text-white px-2 py-1 rounded hover:bg-slate-900">
                            <i class="fas fa-plus"></i> Baru
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="g in groups" :key="g.id" 
                             @click="selectGroup(g)"
                             class="p-3 rounded-lg cursor-pointer transition-colors border"
                             :class="selectedGroup && selectedGroup.id === g.id ? 'bg-white border-teal-500 shadow-sm ring-1 ring-teal-500' : 'bg-white border-slate-200 hover:border-teal-300'">
                            <div class="font-bold text-sm text-slate-800">{{ g.name }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ g.description || 'No description' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Main: Edit Group & Items -->
                <div class="w-2/3 overflow-y-auto p-6">
                    <div v-if="selectedGroup">
                        <!-- Edit Group Form -->
                        <div class="mb-8 border-b border-slate-100 pb-6">
                            <h3 class="font-bold text-slate-800 mb-4">Edit Grup</h3>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Grup</label>
                                    <input v-model="selectedGroup.name" class="w-full border rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Deskripsi</label>
                                    <input v-model="selectedGroup.description" class="w-full border rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Urutan</label>
                                    <input type="number" v-model="selectedGroup.sort_order" class="w-24 border rounded px-3 py-2 text-sm">
                                </div>
                                <div class="flex justify-between mt-2">
                                    <button @click="deleteGroup(selectedGroup.id)" class="text-red-600 text-xs font-bold hover:underline">Hapus Grup</button>
                                    <button @click="saveGroup" class="bg-teal-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-teal-700">Simpan Perubahan</button>
                                </div>
                            </div>
                        </div>

                            <!-- Manage Items Split View -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- COLUMN 1: INCOME -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="font-bold text-slate-800 text-sm">Sumber Pemasukan</h3>
                                        <button @click="showAddIncome = !showAddIncome" class="text-xs bg-teal-50 text-teal-600 px-2 py-1 rounded hover:bg-teal-100 font-bold">
                                            <i class="fas fa-plus mr-1"></i> Tambah
                                        </button>
                                    </div>

                                    <!-- Add Income Form -->
                                    <div v-if="showAddIncome" class="bg-teal-50 p-3 rounded-lg mb-4 border border-teal-100">
                                        <div class="flex gap-2 mb-2 text-xs">
                                            <button @click="newItem.type = 'PAYMENT_TYPE'; newItem.id = ''" 
                                                    class="flex-1 py-1 rounded transition-colors font-bold"
                                                    :class="newItem.type === 'PAYMENT_TYPE' ? 'bg-teal-600 text-white' : 'bg-white text-slate-500 hover:bg-teal-100'">
                                                Tagihan Siswa
                                            </button>
                                            <button @click="newItem.type = 'INCOME_CATEGORY'; newItem.id = ''" 
                                                    class="flex-1 py-1 rounded transition-colors font-bold"
                                                    :class="newItem.type === 'INCOME_CATEGORY' ? 'bg-teal-600 text-white' : 'bg-white text-slate-500 hover:bg-teal-100'">
                                                Lainnya
                                            </button>
                                        </div>

                                        <div v-if="newItem.type === 'PAYMENT_TYPE'">
                                            <select v-model="newItem.id" class="w-full border rounded px-2 py-1.5 text-xs mb-2">
                                                <option value="">Pilih Jenis Pembayaran...</option>
                                                <!-- Group by Category -->
                                                <optgroup v-for="(group, catName) in paymentTypesGrouped" :key="catName" :label="catName">
                                                    <option v-for="opt in group" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <div v-else>
                                            <select v-model="newItem.id" class="w-full border rounded px-2 py-1.5 text-xs mb-2">
                                                <option value="">Pilih Kategori Pemasukan...</option>
                                                <option v-for="opt in incomeCategories" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                            </select>
                                        </div>

                                        <button @click="addItem" :disabled="!newItem.id" class="w-full bg-teal-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-teal-700 disabled:opacity-50">
                                            Tambahkan
                                        </button>
                                    </div>

                                    <!-- Income List -->
                                    <div class="space-y-2">
                                        <div v-for="item in incomeItems" :key="item.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-white shadow-sm">
                                            <div>
                                                <div class="font-bold text-xs text-slate-800">{{ item.item_name }}</div>
                                                <div class="text-[10px] text-slate-500">
                                                    {{ item.item_type === 'PAYMENT_TYPE' ? 'Tagihan Siswa' : 'Pemasukan Lain' }}
                                                </div>
                                            </div>
                                            <button @click="deleteItem(item.id)" class="text-slate-300 hover:text-red-500 transition-colors">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                        <div v-if="incomeItems.length === 0" class="text-xs text-slate-400 italic text-center py-4 border border-dashed border-slate-200 rounded-lg">
                                            Belum ada pemasukan
                                        </div>
                                    </div>
                                </div>

                                <!-- COLUMN 2: EXPENSE -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="font-bold text-slate-800 text-sm">Pos Pengeluaran</h3>
                                        <button @click="showAddExpense = !showAddExpense" class="text-xs bg-red-50 text-red-600 px-2 py-1 rounded hover:bg-red-100 font-bold">
                                            <i class="fas fa-plus mr-1"></i> Tambah
                                        </button>
                                    </div>

                                    <!-- Add Expense Form -->
                                    <div v-if="showAddExpense" class="bg-red-50 p-3 rounded-lg mb-4 border border-red-100">
                                        <select v-model="newItem.expenseId" class="w-full border rounded px-2 py-1.5 text-xs mb-2">
                                            <option value="">Pilih Kategori Pengeluaran...</option>
                                            <option v-for="opt in expenseCategories" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                        </select>
                                        <button @click="addExpenseItem" :disabled="!newItem.expenseId" class="w-full bg-red-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-red-700 disabled:opacity-50">
                                            Tambahkan
                                        </button>
                                    </div>

                                    <!-- Expense List -->
                                    <div class="space-y-2">
                                        <div v-for="item in expenseItems" :key="item.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-white shadow-sm">
                                            <div>
                                                <div class="font-bold text-xs text-slate-800">{{ item.item_name }}</div>
                                                <div class="text-[10px] text-slate-500">Kategori Beban</div>
                                            </div>
                                            <button @click="deleteItem(item.id)" class="text-slate-300 hover:text-red-500 transition-colors">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                        <div v-if="expenseItems.length === 0" class="text-xs text-slate-400 italic text-center py-4 border border-dashed border-slate-200 rounded-lg">
                                            Belum ada pengeluaran
                                        </div>
                                    </div>
                                </div>

                            </div>
                    </div>
                    <div v-else class="h-full flex items-center justify-center text-slate-400 italic">
                        Pilih grup di sebelah kiri untuk mengedit.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                loading: false,
                reportData: null,
                units: [],
                filter: {
                    startDate: new Date().toISOString().slice(0, 8) + '01', 
                    endDate: new Date().toISOString().slice(0, 10),
                    unitId: ''
                },
                // Manage Modal
                showManageModal: false,
                showAddIncome: false,
                showAddExpense: false,
                
                // Report Types
                reportTypes: [],
                currentReportType: null,
                editingReportType: null,
                showCreateTypeModal: false,
                newReportTypeName: '',
                
                groups: [],
                selectedGroup: null,
                paymentTypes: [],
                incomeCategories: [],
                expenseCategories: [],
                newItem: {
                    type: 'PAYMENT_TYPE',
                    id: '',
                    expenseId: ''
                }
            }
        },
        computed: {
            incomeItems() {
                if (!this.selectedGroup || !this.selectedGroup.items) return [];
                return this.selectedGroup.items.filter(i => i.item_type === 'PAYMENT_TYPE' || i.item_type === 'INCOME_CATEGORY');
            },
            expenseItems() {
                if (!this.selectedGroup || !this.selectedGroup.items) return [];
                return this.selectedGroup.items.filter(i => i.item_type === 'EXPENSE_CATEGORY');
            },
            paymentTypesGrouped() {
                const groups = {};
                this.paymentTypes.forEach(pt => {
                    const cat = pt.category || 'Lainnya';
                    if (!groups[cat]) groups[cat] = [];
                    groups[cat].push(pt);
                });
                return groups;
            }
        },
        methods: {
            getBaseUrl() {
                return window.BASE_URL;
            },
            formatMoney(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency', currency: 'IDR', minimumFractionDigits: 0
                }).format(amount);
            },
            async fetchInit() {
                try {
                    // Fetch Settings
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.units = data.data.units;
                        this.paymentTypes = data.data.paymentTypes;
                        this.incomeCategories = data.data.incomeCategories;
                        this.expenseCategories = data.data.expenseCategories;
                    }
                    
                    // Fetch Report Types
                    const resRT = await fetch(this.getBaseUrl() + 'api/finance.php?action=get_report_types');
                    const dataRT = await resRT.json();
                    if (dataRT.success) {
                        this.reportTypes = dataRT.data;
                        
                        // If we have types and none selected, select first
                        if (this.reportTypes.length > 0 && !this.currentReportType) {
                            this.selectReportType(this.reportTypes[0]);
                        }
                    }
                } catch (e) { console.error(e); }
            },
            
            selectReportType(rt) {
                this.currentReportType = rt;
                this.reportData = null; // Reset report view
                this.fetchReport(); // Auto fetch? Or wait for button? Let's auto fetch.
            },
            
            async createReportType() {
                if (!this.newReportTypeName) return alert('Nama laporan harus diisi');
                const name = this.newReportTypeName;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=save_report_type', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            name: name,
                            sort_order: this.reportTypes.length + 1
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showCreateTypeModal = false;
                        await this.fetchInit(); // Reload types
                        // Select the new one (last one)
                        if (this.reportTypes.length > 0) {
                            this.selectReportType(this.reportTypes[this.reportTypes.length - 1]);
                        }
                    }
                } catch (e) { console.error(e); }
            },
            
            editReportType(rt) {
                this.editingReportType = JSON.parse(JSON.stringify(rt));
            },
            
            async saveReportType() {
                if (!this.editingReportType) return;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=save_report_type', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.editingReportType)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.editingReportType = null;
                        // Refresh list but keep selection
                        const currentId = this.currentReportType.id;
                        await this.fetchInit();
                        this.currentReportType = this.reportTypes.find(rt => rt.id === currentId) || this.reportTypes[0];
                    }
                } catch (e) { console.error(e); }
            },
            
            async deleteReportType(id) {
                if (!confirm('Hapus jenis laporan ini? Semua grup di dalamnya akan terhapus.')) return;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=delete_report_type', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchInit();
                    }
                } catch (e) { console.error(e); }
            },

            async fetchReport() {
                if (!this.currentReportType) return;
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        action: 'get_operational_report_data',
                        start_date: this.filter.startDate,
                        end_date: this.filter.endDate,
                        unit_id: this.filter.unitId,
                        report_type_id: this.currentReportType.id
                    });
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?' + params.toString());
                    const data = await res.json();
                    if (data.success) {
                        this.reportData = data.data;
                    }
                } catch (e) { console.error(e); }
                this.loading = false;
            },
            async fetchGroups() {
                if (!this.currentReportType) return;
                try {
                    const params = new URLSearchParams({
                        action: 'get_operational_report_groups',
                        report_type_id: this.currentReportType.id
                    });
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?' + params.toString());
                    const data = await res.json();
                    if (data.success) {
                        this.groups = data.data;
                        // Refresh selected group if exists
                        if (this.selectedGroup) {
                            const updated = this.groups.find(g => g.id === this.selectedGroup.id);
                            if (updated) this.selectedGroup = updated;
                        }
                    }
                } catch (e) { console.error(e); }
            },
            openManageGroups() {
                this.fetchGroups();
                this.showManageModal = true;
                this.selectedGroup = null;
            },
            selectGroup(group) {
                // Deep copy to avoid mutating list directly before save
                this.selectedGroup = JSON.parse(JSON.stringify(group));
            },
            createGroup() {
                if (!this.currentReportType) return alert('Pilih jenis laporan terlebih dahulu');
                const newGroup = {
                    id: null,
                    name: 'Grup Baru',
                    description: '',
                    sort_order: this.groups.length + 1,
                    report_type_id: this.currentReportType.id, 
                    items: []
                };
                
                // Add to list visually so user sees it
                this.groups.push(newGroup);
                this.selectGroup(newGroup);
            },
            async saveGroup() {
                if (!this.selectedGroup.name) return alert('Nama grup harus diisi');
                // Ensure report_type_id is set
                if (!this.selectedGroup.report_type_id && this.currentReportType) {
                    this.selectedGroup.report_type_id = this.currentReportType.id;
                }
                
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=save_operational_report_group', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.selectedGroup)
                    });
                    const data = await res.json();
                    if (data.success) {
                        const savedId = (data.data && data.data.id) ? data.data.id : this.selectedGroup.id;
                        await this.fetchGroups();
                        
                        // Refresh selected group with fresh data from server
                        const fresh = this.groups.find(g => g.id == savedId);
                        if (fresh) {
                            // Keep current selection items if exists to prevent flicker, or just reload
                            // But better to just update reference
                            this.selectGroup(fresh);
                        }
                        
                        alert('Grup berhasil disimpan');
                    }
                } catch (e) { console.error(e); }
            },
            async deleteGroup(id) {
                if (!id) {
                    // Remove unsaved group from list
                    this.groups = this.groups.filter(g => g.id !== null);
                    this.selectedGroup = null;
                    return;
                }
                if (!confirm('Hapus grup ini?')) return;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=delete_operational_report_group', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.selectedGroup = null;
                        this.fetchGroups();
                    }
                } catch (e) { console.error(e); }
            },
            async addItem() {
                if (!this.selectedGroup.id) return alert('Simpan grup terlebih dahulu sebelum menambah item.');
                // Validation
                if (this.newItem.type === 'PAYMENT_TYPE' && !this.newItem.id) return alert('Pilih jenis pembayaran');
                if (this.newItem.type === 'INCOME_CATEGORY' && !this.newItem.id) return alert('Pilih kategori pemasukan');
                
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=save_operational_group_item', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            group_id: this.selectedGroup.id,
                            item_type: this.newItem.type,
                            item_id: this.newItem.id
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.newItem.id = ''; // Reset selection
                        
                        // REFRESH GROUPS AND UPDATE SELECTED GROUP
                        await this.fetchGroups();
                        const fresh = this.groups.find(g => g.id == this.selectedGroup.id);
                        if (fresh) this.selectGroup(fresh);

                    } else {
                        alert(data.message);
                    }
                } catch (e) { console.error(e); }
            },
            async addExpenseItem() {
                if (!this.selectedGroup.id) return alert('Simpan grup terlebih dahulu sebelum menambah item.');
                if (!this.newItem.expenseId) return alert('Pilih kategori pengeluaran');
                
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=save_operational_group_item', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            group_id: this.selectedGroup.id,
                            item_type: 'EXPENSE_CATEGORY',
                            item_id: this.newItem.expenseId
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.newItem.expenseId = ''; // Reset selection
                        
                        // REFRESH GROUPS AND UPDATE SELECTED GROUP
                        await this.fetchGroups();
                        const fresh = this.groups.find(g => g.id == this.selectedGroup.id);
                        if (fresh) this.selectGroup(fresh);

                    } else {
                        alert(data.message);
                    }
                } catch (e) { console.error(e); }
            },
            async deleteItem(id) {
                if (!confirm('Hapus item ini?')) return;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/finance.php?action=delete_operational_group_item', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        // REFRESH GROUPS AND UPDATE SELECTED GROUP
                        await this.fetchGroups();
                        const fresh = this.groups.find(g => g.id == this.selectedGroup.id);
                        if (fresh) this.selectGroup(fresh);
                    }
                } catch (e) { console.error(e); }
            }
        },
        mounted() {
            this.fetchInit();
            // Don't call fetchReport directly here, selectReportType will call it
        }
    }).mount('#app')
</script>
</body>
</html>
