<?php
// modules/finance/report_savings.php
require_once '../../includes/guard.php';
require_login_and_module('finance');
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <!-- TOP NAVIGATION -->
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Tabungan</h1>
                <span class="text-xs text-slate-500 font-medium">Ringkasan Saldo per Unit, Kelas & Siswa</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <button @click="printReport" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center gap-2">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-hidden flex">
        
        <!-- LEFT PANEL: SIDEBAR NAVIGATION -->
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col z-10 shadow-sm">
            <div class="p-4 border-b border-slate-100 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Jenis Laporan</label>
                    <div class="flex p-1 bg-slate-100 rounded-lg">
                        <button v-for="v in views" :key="v.id" @click="setView(v.id)"
                            :class="currentView === v.id ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all uppercase">
                            {{ v.label }}
                        </button>
                    </div>
                </div>
                
                <div v-if="currentView === 'class'">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Filter Unit</label>
                    <select v-model="filterUnitId" @change="fetchListData" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="">Semua Unit</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cari {{ currentViewLabel }}</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-3 text-slate-400"></i>
                        <input type="text" v-model="searchQuery" @input="debouncedSearch" :placeholder="'Cari ' + currentViewLabel + '...'" 
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                <div v-if="isLoading" class="p-4 text-center text-slate-400 text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Memuat...
                </div>
                
                <button v-for="item in listData" :key="item.id" @click="selectItem(item)" 
                    :class="selectedItem && selectedItem.id === item.id ? 'bg-indigo-50 border-indigo-200 ring-1 ring-indigo-300' : 'hover:bg-slate-50 border-transparent'"
                    class="w-full text-left p-3 rounded-lg border transition-all group relative">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-bold text-slate-700 text-sm line-clamp-1 group-hover:text-indigo-600">{{ item.name }}</span>
                        <span v-if="currentView === 'student'" class="text-[10px] font-mono bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ item.identity_number }}</span>
                    </div>
                    <div class="text-[10px] text-slate-500 flex justify-between uppercase font-medium">
                        <span>{{ item.unit_name || item.level_name || (currentView === 'unit' ? 'UNIT' : 'GLOBAL') }}</span>
                        <span class="text-indigo-600 font-bold">Rp {{ formatNumber(item.total_balance || item.balance) }}</span>
                    </div>
                </button>

                <div v-if="!isLoading && listData.length === 0" class="p-8 text-center text-slate-400 text-sm italic">
                    Data tidak ditemukan.
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: REPORT DETAILS -->
        <div class="flex-1 bg-slate-50 overflow-y-auto p-8">
            <div v-if="!selectedItem" class="h-full flex flex-col items-center justify-center text-slate-400">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                    <i class="fas fa-chart-pie text-3xl text-slate-200"></i>
                </div>
                <p class="text-lg font-medium">Pilih data di sebelah kiri untuk melihat detail laporan</p>
            </div>

            <div v-else class="max-w-4xl mx-auto">
                <!-- HEADER DETAIL -->
                <div class="mb-8">
                    <h2 class="text-3xl font-extrabold text-slate-800">{{ selectedItem.name }}</h2>
                    <p class="text-slate-500 font-medium">Laporan Ringkasan Tabungan • {{ currentViewLabel }}</p>
                </div>

                <!-- SUMMARY CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-indigo-50 rounded-bl-[50px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10">
                            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Saldo Terkumpul</div>
                            <div class="text-3xl font-black text-indigo-600">Rp {{ formatNumber(selectedItem.total_balance || selectedItem.balance) }}</div>
                        </div>
                    </div>
                    
                    <div v-if="currentView !== 'student'" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-slate-50 rounded-bl-[50px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10">
                            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Jumlah Siswa</div>
                            <div class="text-3xl font-black text-slate-700">{{ selectedItem.student_count }} <span class="text-sm font-normal text-slate-400">Siswa</span></div>
                        </div>
                    </div>
                    
                    <div v-else class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-slate-50 rounded-bl-[50px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10">
                            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Informasi Siswa</div>
                            <div class="text-lg font-bold text-slate-700 mt-1">{{ selectedItem.class_name || '-' }}</div>
                            <div class="text-sm text-slate-500">{{ selectedItem.unit_name || '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- TABLE SECTION (FOR UNIT/CLASS) -->
                <div v-if="currentView !== 'student'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700">Daftar Siswa & Saldo</h3>
                        <div v-if="isLoadingDetail" class="text-xs text-slate-400">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Memuat detail...
                        </div>
                    </div>
                    
                    <div class="max-h-[500px] overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-3 font-bold bg-slate-50">Nama Siswa</th>
                                    <th class="px-6 py-3 font-bold bg-slate-50">NIS</th>
                                    <th v-if="currentView === 'unit'" class="px-6 py-3 font-bold bg-slate-50">Kelas</th>
                                    <th class="px-6 py-3 font-bold bg-slate-50 text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="s in filteredStudents" :key="s.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-700">{{ s.name }}</td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ s.identity_number }}</td>
                                    <td v-if="currentView === 'unit'" class="px-6 py-4 text-slate-600">{{ s.class_name }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-indigo-600">Rp {{ formatNumber(s.balance) }}</td>
                                </tr>
                                <tr v-if="!isLoadingDetail && filteredStudents.length === 0">
                                    <td :colspan="currentView === 'unit' ? 4 : 3" class="px-6 py-8 text-center text-slate-400 italic">
                                        Tidak ada data siswa.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- STUDENT HISTORY (FOR STUDENT VIEW) -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-slate-700">Riwayat Transaksi</h3>
                        <div v-if="isLoadingDetail" class="text-xs text-slate-400">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Memuat...
                        </div>
                    </div>
                    
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 font-bold">Waktu</th>
                                <th class="px-6 py-3 font-bold">Jenis</th>
                                <th class="px-6 py-3 font-bold">Keterangan</th>
                                <th class="px-6 py-3 font-bold text-right">Nominal</th>
                                <th class="px-6 py-3 font-bold text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="t in studentHistory" :key="t.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 text-slate-500 whitespace-nowrap">
                                    <div class="font-bold text-slate-700">{{ formatDate(t.trans_date) }}</div>
                                    <div class="text-[10px]">{{ formatTime(t.trans_date) }}</div>
                                </td>
                                <td class="px-6 py-3">
                                    <span :class="t.transaction_type === 'DEPOSIT' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded text-[10px] font-bold">
                                        {{ t.transaction_type === 'DEPOSIT' ? 'SETORAN' : 'PENARIKAN' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ t.description || '-' }}</td>
                                <td class="px-6 py-3 text-right font-mono font-bold" :class="t.transaction_type === 'DEPOSIT' ? 'text-green-600' : 'text-red-600'">
                                    {{ formatNumber(t.amount) }}
                                </td>
                                <td class="px-6 py-3 text-right font-mono text-slate-700">
                                    {{ formatNumber(t.balance_after) }}
                                </td>
                            </tr>
                            <tr v-if="!isLoadingDetail && studentHistory.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">
                                    Belum ada riwayat transaksi.
                                </td>
                            </tr>
                        </tbody>
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
            currentView: 'unit', // unit, class, student
            views: [
                { id: 'unit', label: 'Unit' },
                { id: 'class', label: 'Kelas' },
                { id: 'student', label: 'Siswa' }
            ],
            searchQuery: '',
            filterUnitId: '',
            units: [],
            listData: [],
            selectedItem: null,
            allStudents: [], // Untuk detail unit/kelas
            studentHistory: [], // Untuk detail siswa
            isLoading: false,
            isLoadingDetail: false,
            searchTimeout: null
        }
    },
    computed: {
        currentViewLabel() {
            return this.views.find(v => v.id === this.currentView).label;
        },
        filteredStudents() {
            if (this.currentView === 'student') return [];
            return this.allStudents;
        }
    },
    methods: {
        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num || 0)
        },
        formatDate(dateStr) {
            if (!dateStr) return '-'
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        formatTime(dateStr) {
            if (!dateStr) return '-'
            return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        },
        async fetchUnits() {
            try {
                const res = await fetch(`../../api/finance.php?action=get_settings`);
                const data = await res.json();
                if (data.success) {
                    this.units = data.data.units.filter(u => u.code !== 'YAYASAN');
                }
            } catch (e) {}
        },
        setView(v) {
            this.currentView = v;
            this.selectedItem = null;
            this.searchQuery = '';
            this.filterUnitId = ''; // Reset filter
            this.listData = []; // Clear previous data
            this.fetchListData();
        },
        debouncedSearch() {
            clearTimeout(this.searchTimeout)
            this.searchTimeout = setTimeout(() => {
                this.fetchListData()
            }, 300)
        },
        async fetchListData() {
            // If student view and no search query, don't fetch anything (or clear list)
            if (this.currentView === 'student' && !this.searchQuery) {
                this.listData = [];
                return;
            }

            this.isLoading = true;
            try {
                let params = `view=${this.currentView}&q=${this.searchQuery}`;
                if (this.currentView === 'class' && this.filterUnitId) {
                    params += `&unit_id=${this.filterUnitId}`;
                }
                
                const res = await fetch(`../../api/finance.php?action=get_savings_report&${params}`);
                const data = await res.json();
                if (data.success) {
                    this.listData = data.data;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },
        async selectItem(item) {
            this.selectedItem = item;
            if (this.currentView === 'student') {
                this.fetchStudentHistory();
            } else {
                this.fetchDetailStudents();
            }
        },
        async fetchStudentHistory() {
            this.isLoadingDetail = true;
            this.studentHistory = [];
            try {
                const res = await fetch(`../../api/finance.php?action=get_student_savings&student_id=${this.selectedItem.id}`);
                const data = await res.json();
                if (data.success) {
                    this.studentHistory = data.data.history;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoadingDetail = false;
            }
        },
        async fetchDetailStudents() {
            this.isLoadingDetail = true;
            this.allStudents = [];
            try {
                let params = `view=student&q=`;
                if (this.currentView === 'unit') params += `&unit_id=${this.selectedItem.id}`;
                if (this.currentView === 'class') params += `&class_id=${this.selectedItem.id}`;
                
                const res = await fetch(`../../api/finance.php?action=get_savings_report&${params}`);
                const data = await res.json();
                if (data.success) {
                    this.allStudents = data.data;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoadingDetail = false;
            }
        },
        printReport() {
            window.print();
        }
    },
    mounted() {
        this.fetchUnits();
        this.fetchListData();
    }
}).mount('#app')
</script>

<?php require_once '../../includes/footer_finance.php'; ?>
