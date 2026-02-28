<?php
// modules/finance/savings.php
require_once '../../includes/guard.php';
require_login_and_module('finance');
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<!-- Content wrapper inside #app -->
<div class="flex flex-col h-full">
    <!-- TOP NAVIGATION -->
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
                <i class="fas fa-piggy-bank text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Tabungan Siswa</h1>
                <span class="text-xs text-slate-500 font-medium">Kelola Simpanan & Penarikan</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>modules/finance/import_savings.php" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-200 transition-colors flex items-center gap-2">
                <i class="fas fa-file-excel"></i> Import Saldo
            </a>
            <div class="h-8 w-px bg-slate-200"></div>
            <div class="text-right">
                <div class="text-xs text-slate-500">Petugas</div>
                <div class="text-sm font-bold text-slate-700"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-hidden flex">
        
        <!-- LEFT PANEL: SEARCH -->
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col z-10 shadow-sm">
            <!-- Toggle Mode -->
            <div class="flex p-2 border-b border-slate-100">
                <button @click="mode = 'single'" :class="mode === 'single' ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs rounded-lg transition-colors">
                    <i class="fas fa-user mr-1"></i> Per Siswa
                </button>
                <button @click="mode = 'batch'" :class="mode === 'batch' ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs rounded-lg transition-colors">
                    <i class="fas fa-users mr-1"></i> Input Massal (Scan)
                </button>
            </div>

            <!-- Single Mode Search -->
            <div v-if="mode === 'single'" class="p-4 border-b border-slate-100">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cari Siswa</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400"></i>
                    <input type="text" v-model="searchQuery" @input="debouncedSearch" placeholder="Nama atau NIS..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
            </div>

            <!-- Batch Mode Selectors -->
            <div v-else-if="mode === 'batch'" class="p-4 border-b border-slate-100 space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pilih Unit</label>
                    <select v-model="batchUnitId" @change="fetchClasses" :disabled="isBatchLocked" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed">
                        <option value="">-- Pilih Unit --</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pilih Kelas</label>
                    <select v-model="batchClassId" :disabled="!batchUnitId || isBatchLocked" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed">
                        <option value="">-- Pilih Kelas --</option>
                        <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                
                <!-- Scan Input in Locked Batch Mode -->
                <div v-if="isBatchLocked" class="mt-4 pt-4 border-t border-slate-200 animate-fade">
                    <label class="block text-xs font-bold text-blue-500 uppercase mb-2">SCAN BARCODE / NIS</label>
                    <div class="relative">
                        <i class="fas fa-barcode absolute left-3 top-3 text-slate-400"></i>
                        <input type="text" ref="batchScanInput" v-model="batchScanQuery" @keyup.enter="handleBatchScan" 
                            placeholder="Scan kartu siswa..." 
                            class="w-full pl-10 pr-4 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <p class="text-[10px] text-blue-400 mt-1 italic">* Scan untuk langsung mengisi nominal</p>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2">
                    <button v-if="!isBatchLocked" @click="lockBatch" :disabled="!batchClassId" class="w-full bg-blue-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-blue-700 disabled:opacity-50 transition-all shadow-sm">
                        <i class="fas fa-lock mr-1"></i> MULAI INPUT / LOCK
                    </button>
                    
                    <div v-else class="space-y-2 animate-fade">
                         <div class="bg-blue-50 p-2 rounded border border-blue-100 text-xs text-blue-700 mb-2 flex justify-between items-center">
                            <span><i class="fas fa-lock text-blue-500 mr-1"></i> Kelas Terkunci</span>
                            <button @click="unlockBatch" class="text-blue-500 hover:text-blue-700 font-bold underline">Ubah</button>
                         </div>
                    
                         <div class="text-xs text-slate-500 flex justify-between mb-1">
                            <span>Total Setoran</span>
                            <span class="font-bold text-blue-600">Rp {{ formatNumber(batchTotal) }}</span>
                         </div>
                         <button @click="submitBatch" :disabled="isSubmitting || batchTotal <= 0" class="w-full bg-green-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-green-700 disabled:opacity-50 shadow-sm">
                            <i class="fas fa-save mr-1"></i> SIMPAN BATCH
                         </button>
                         <button @click="clearDraft" class="w-full text-slate-400 hover:text-red-500 text-xs font-bold py-1">
                            <i class="fas fa-trash mr-1"></i> Reset Draft
                         </button>
                    </div>
                </div>
            </div>
            
            <!-- Scan Mode Panel (REMOVED) -->
            
            <!-- List (Single Mode) -->
            <div v-if="mode === 'single'" class="flex-1 overflow-y-auto p-2 space-y-1">
                <div v-if="isLoadingSearch" class="p-4 text-center text-slate-400 text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Mencari...
                </div>
                
                <div v-else-if="students.length === 0 && searchQuery" class="p-4 text-center text-slate-400 text-sm">
                    Tidak ditemukan siswa.
                </div>
                
                <button v-for="s in students" :key="s.id" @click="selectStudent(s)" 
                    :class="selectedStudent && selectedStudent.id === s.id ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-300' : 'hover:bg-slate-50 border-transparent'"
                    class="w-full text-left p-3 rounded-lg border transition-all group relative">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-bold text-slate-700 text-sm line-clamp-1 group-hover:text-blue-600">{{ s.name }}</span>
                        <span class="text-[10px] font-mono bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ s.identity_number }}</span>
                    </div>
                    <div class="text-xs text-slate-500 truncate">
                        {{ s.class_name || 'Belum Masuk Kelas' }}
                    </div>
                </button>
            </div>
        </div>

        <!-- RIGHT PANEL: DETAILS -->
        <div class="flex-1 bg-slate-50 overflow-hidden flex flex-col relative">
            
            <!-- SCAN MODE UI (REMOVED) -->
            
            <!-- BATCH MODE UI -->
            <div v-if="mode === 'batch'" class="flex-1 flex flex-col overflow-hidden">
                <div v-if="!isBatchLocked" class="flex-1 flex flex-col items-center justify-center text-slate-400">
                    <i class="fas fa-users text-4xl mb-4 text-slate-300"></i>
                    <p v-if="!batchClassId">Pilih Unit dan Kelas untuk memulai input massal</p>
                    <p v-else class="animate-pulse">Klik tombol <b>MULAI INPUT / LOCK</b> di panel kiri</p>
                </div>
                <div v-else class="flex-1 flex flex-col overflow-hidden bg-white m-4 rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-slate-700">Input Tabungan Massal</h3>
                        <div class="text-xs text-slate-500">
                            {{ batchStudents.length }} Siswa
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 font-bold bg-slate-50">Siswa</th>
                                    <th class="px-4 py-3 font-bold bg-slate-50 text-right">Saldo Saat Ini</th>
                                    <th class="px-4 py-3 font-bold bg-slate-50 w-48">Input Setoran (Rp)</th>
                                    <th class="px-4 py-3 font-bold bg-slate-50">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="batchStudents.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-400 italic">
                                        <i class="fas fa-user-slash text-2xl mb-2"></i>
                                        <p>Tidak ada siswa aktif di kelas ini.</p>
                                    </td>
                                </tr>
                                <tr v-for="s in batchStudents" :key="s.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-700">{{ s.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ s.identity_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-600">
                                        {{ formatNumber(s.balance) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                            :id="'batch-input-' + s.id"
                                            v-model.number="s.input_amount" min="0" 
                                            class="w-full px-3 py-1.5 border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-bold text-right" 
                                            placeholder="0"
                                            @keydown.enter.prevent="$refs.batchScanInput.focus()">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" v-model="s.note" 
                                            class="w-full px-3 py-1.5 border border-slate-200 rounded text-xs focus:outline-none focus:border-blue-500" 
                                            placeholder="Opsional">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
                        <div class="text-xs text-slate-500 italic">
                            * Data tersimpan otomatis sebagai draft (lokal)
                        </div>
                        <div class="text-lg font-bold text-slate-700">
                            Total: <span class="text-blue-600">Rp {{ formatNumber(batchTotal) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SINGLE MODE UI (Existing) -->
            <div v-else-if="!selectedStudent" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                    <i class="fas fa-user-graduate text-3xl text-slate-300"></i>
                </div>
                <p class="text-lg font-medium">Pilih siswa untuk melihat data tabungan</p>
            </div>

            <!-- CONTENT -->
            <div v-else class="flex-1 overflow-y-auto p-6">
                <!-- HEADER -->
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">{{ selectedStudent.name }}</h2>
                        <p class="text-slate-500 flex items-center gap-2 mt-1">
                            <i class="fas fa-id-card"></i> {{ selectedStudent.identity_number }}
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <i class="fas fa-users"></i> {{ selectedStudent.class_name || '-' }}
                        </p>
                    </div>
                    
                    <!-- BALANCE CARD -->
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-5 rounded-2xl shadow-lg shadow-blue-200 min-w-[280px]">
                        <div class="text-blue-100 text-sm font-medium mb-1">Saldo Tabungan</div>
                        <div class="text-3xl font-bold tracking-tight mb-4">Rp {{ formatNumber(currentBalance) }}</div>
                        <div class="flex gap-2">
                            <button @click="openModal('DEPOSIT')" class="flex-1 bg-white/20 hover:bg-white/30 py-1.5 rounded-lg text-xs font-bold transition-colors backdrop-blur-sm border border-white/10">
                                <i class="fas fa-plus mr-1"></i> SETOR
                            </button>
                            <button @click="openModal('WITHDRAW')" class="flex-1 bg-white/20 hover:bg-white/30 py-1.5 rounded-lg text-xs font-bold transition-colors backdrop-blur-sm border border-white/10">
                                <i class="fas fa-minus mr-1"></i> TARIK
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HISTORY -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-slate-700">Riwayat Transaksi</h3>
                        <button @click="fetchSavingsData" class="text-xs text-blue-600 hover:underline">
                            <i class="fas fa-sync-alt mr-1"></i> Refresh
                        </button>
                    </div>
                    
                    <div v-if="isLoadingHistory" class="p-8 text-center text-slate-400">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p>Memuat riwayat...</p>
                    </div>

                    <table v-else class="w-full text-sm text-left">
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
                            <tr v-for="t in history" :key="t.id" class="hover:bg-slate-50 transition-colors">
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
                                    {{ t.transaction_type === 'DEPOSIT' ? '+' : '-' }} {{ formatNumber(t.amount) }}
                                </td>
                                <td class="px-6 py-3 text-right font-mono text-slate-700">
                                    {{ formatNumber(t.balance_after) }}
                                </td>
                            </tr>
                            <tr v-if="history.length === 0">
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

    <!-- TRANSACTION MODAL -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center" :class="modalType === 'DEPOSIT' ? 'bg-green-50' : 'bg-red-50'">
                <h3 class="font-bold text-lg" :class="modalType === 'DEPOSIT' ? 'text-green-700' : 'text-red-700'">
                    <i :class="modalType === 'DEPOSIT' ? 'fas fa-plus-circle' : 'fas fa-minus-circle'" class="mr-2"></i>
                    {{ modalType === 'DEPOSIT' ? 'Setor Tabungan' : 'Tarik Tabungan' }}
                </h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nominal (Rp)</label>
                    <input type="number" v-model="form.amount" class="w-full text-2xl font-bold text-slate-700 border-b-2 border-slate-200 focus:border-blue-500 focus:outline-none py-2 bg-transparent" placeholder="0" min="1000">
                    <p class="text-xs text-slate-400 mt-1">Saldo saat ini: Rp {{ formatNumber(currentBalance) }}</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Keterangan</label>
                    <textarea v-model="form.description" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="2" placeholder="Contoh: Setoran harian..."></textarea>
                </div>

                <button @click="submitTransaction" :disabled="isSubmitting || !form.amount || form.amount <= 0" 
                    :class="modalType === 'DEPOSIT' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
                    class="w-full text-white py-3 rounded-lg font-bold shadow-lg shadow-slate-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                    <span v-else>{{ modalType === 'DEPOSIT' ? 'SIMPAN SETORAN' : 'PROSES PENARIKAN' }}</span>
                </button>
            </div>
        </div>
    </div>
    </div>
    <!-- End of #app -->
</div> 

<script>
const { createApp } = Vue
createApp({
    data() {
        return {
            mode: 'single', // single, batch
            searchQuery: '',
            students: [],
            selectedStudent: null,
            isLoadingSearch: false,
            isLoadingHistory: false,
            history: [],
            currentBalance: 0,
            
            // Batch Mode Data
            units: [],
            classes: [],
            batchUnitId: '',
            batchClassId: '',
            batchStudents: [],
            isBatchLocked: false,
            batchScanQuery: '',
            
            // Scan Mode Data (Legacy Removed)
            scanQuery: '',
            scannedStudent: null,
            scanAmount: '',
            stagingList: [],
            
            showModal: false,
            modalType: 'DEPOSIT', // DEPOSIT / WITHDRAW
            form: {
                amount: '',
                description: ''
            },
            isSubmitting: false,
            searchTimeout: null
        }
    },
    computed: {
        batchTotal() {
            return this.batchStudents.reduce((sum, s) => sum + (Number(s.input_amount) || 0), 0)
        },
        stagingTotal() {
            return this.stagingList.reduce((sum, s) => sum + (Number(s.amount) || 0), 0)
        }
    },
    watch: {
        batchStudents: {
            handler(newVal) {
                if (this.batchClassId && newVal.length > 0) {
                    // Save draft to local storage
                    const draft = newVal.map(s => ({ id: s.id, input_amount: s.input_amount, note: s.note }))
                    localStorage.setItem('savings_draft_' + this.batchClassId, JSON.stringify(draft))
                }
            },
            deep: true
        }
    },
    methods: {
        // ... (existing methods: formatNumber, formatDate, etc) ...
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
        
        // --- BATCH METHODS ---
        async fetchUnits() {
            try {
                const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings')
                const data = await res.json()
                if (data.success) {
                    this.units = data.data.units
                }
            } catch (e) {}
        },
        async fetchClasses() {
            this.classes = []
            this.batchClassId = ''
            this.isBatchLocked = false // Unlock if unit changes
            if (!this.batchUnitId) return
            try {
                const res = await fetch(window.BASE_URL + `api/finance.php?action=get_classes&unit_id=${this.batchUnitId}`)
                const data = await res.json()
                if (data.success) {
                    this.classes = data.data
                } else {
                     // Fallback/Hack: try fetching all classes
                     const res2 = await fetch(window.BASE_URL + `api/academic.php?action=get_classes&unit_id=${this.batchUnitId}`)
                     const data2 = await res2.json()
                     this.classes = data2
                }
            } catch (e) {}
        },
        lockBatch() {
            if (!this.batchClassId) return
            this.isBatchLocked = true
            this.fetchClassStudents()
        },
        unlockBatch() {
            this.isBatchLocked = false
            this.batchStudents = []
            this.batchScanQuery = ''
        },
        async fetchClassStudents() {
            if (!this.batchClassId) return
            this.batchStudents = []
            try {
                const res = await fetch(window.BASE_URL + `api/finance.php?action=get_class_savings_list&class_id=${this.batchClassId}`)
                const data = await res.json()
                if (data.success) {
                    // Load draft if exists
                    const draftKey = 'savings_draft_' + this.batchClassId
                    const draft = JSON.parse(localStorage.getItem(draftKey) || '[]')
                    
                    this.batchStudents = data.data.map(s => {
                        const saved = draft.find(d => d.id === s.id)
                        return {
                            ...s,
                            input_amount: saved ? saved.input_amount : '',
                            note: saved ? saved.note : ''
                        }
                    })
                    
                    // Auto focus scan input if locked
                    if (this.isBatchLocked) {
                        this.$nextTick(() => {
                            this.$refs.batchScanInput?.focus()
                        })
                    }
                }
            } catch (e) {
                alert('Gagal mengambil data siswa')
                this.isBatchLocked = false
            }
        },
        handleBatchScan() {
            if (!this.batchScanQuery) return
            
            // Cari siswa di list yang sudah ada (local search)
            // Support NIS (priority) atau Nama
            const query = this.batchScanQuery.toLowerCase()
            const student = this.batchStudents.find(s => 
                s.identity_number === this.batchScanQuery || 
                s.name.toLowerCase().includes(query)
            )
            
            if (student) {
                this.batchScanQuery = '' // Clear input
                
                // Pindah fokus ke input nominal siswa tersebut
                this.$nextTick(() => {
                    const el = document.getElementById('batch-input-' + student.id)
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
                        el.focus()
                        el.select() // Select text biar gampang replace
                    }
                })
            } else {
                alert(`Siswa dengan NIS/Nama "${this.batchScanQuery}" tidak ditemukan di kelas ini.`)
                this.batchScanQuery = ''
                this.$refs.batchScanInput.focus()
            }
        },
        async submitBatch() {
            const items = this.batchStudents
                .filter(s => s.input_amount && s.input_amount > 0)
                .map(s => ({
                    student_id: s.id,
                    amount: s.input_amount,
                    description: s.note
                }))
            
            if (items.length === 0) return alert('Belum ada nominal yang diisi')
            
            if (!confirm(`Simpan ${items.length} transaksi tabungan dengan total Rp ${this.formatNumber(this.batchTotal)}?`)) return
            
            this.isSubmitting = true
            try {
                const payload = {
                    items: items,
                    type: 'DEPOSIT', // Default batch is deposit
                    request_id: `BATCH-${this.batchClassId}-${Date.now()}`
                }
                
                const res = await fetch(window.BASE_URL + 'api/finance.php?action=save_savings_batch', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                })
                const data = await res.json()
                
                if (data.success) {
                    alert('Batch berhasil disimpan!')
                    this.clearDraft()
                    this.fetchClassStudents() // Refresh balances
                } else {
                    alert(data.message || 'Gagal menyimpan batch')
                }
            } catch (e) {
                alert('Error: ' + e.message)
            } finally {
                this.isSubmitting = false
            }
        },
        clearDraft() {
            if (this.batchClassId) {
                localStorage.removeItem('savings_draft_' + this.batchClassId)
                this.batchStudents.forEach(s => {
                    s.input_amount = ''
                    s.note = ''
                })
            }
        },

        // --- EXISTING SINGLE METHODS ---
        debouncedSearch() {
            clearTimeout(this.searchTimeout)
            this.searchTimeout = setTimeout(() => {
                this.fetchStudents()
            }, 300)
        },
        async fetchStudents() {
            if (!this.searchQuery || this.searchQuery.length < 2) {
                this.students = []
                return
            }
            this.isLoadingSearch = true
            try {
                const res = await fetch(window.BASE_URL + `api/finance.php?action=search_students&q=${this.searchQuery}`)
                const data = await res.json()
                if (data.success) {
                    this.students = data.data
                }
            } catch (e) {
                console.error(e)
            } finally {
                this.isLoadingSearch = false
            }
        },
        async selectStudent(student) {
            this.selectedStudent = student
            this.fetchSavingsData()
        },
        async fetchSavingsData() {
            if (!this.selectedStudent) return
            this.isLoadingHistory = true
            try {
                const res = await fetch(window.BASE_URL + `api/finance.php?action=get_student_savings&student_id=${this.selectedStudent.id}`)
                const data = await res.json()
                if (data.success) {
                    this.history = data.data.history
                    this.currentBalance = Number(data.data.balance)
                }
            } catch (e) {
                console.error(e)
            } finally {
                this.isLoadingHistory = false
            }
        },
        openModal(type) {
            this.modalType = type
            this.form.amount = ''
            this.form.description = ''
            this.showModal = true
        },
        async submitTransaction() {
            if (!this.form.amount || this.form.amount <= 0) return alert('Nominal harus diisi!')
            
            if (this.modalType === 'WITHDRAW' && Number(this.form.amount) > this.currentBalance) {
                return alert('Saldo tidak mencukupi!')
            }

            if (!confirm(`Yakin ingin ${this.modalType === 'DEPOSIT' ? 'menyetor' : 'menarik'} Rp ${this.formatNumber(this.form.amount)}?`)) return

            this.isSubmitting = true
            try {
                const payload = {
                    student_id: this.selectedStudent.id,
                    type: this.modalType,
                    amount: Number(this.form.amount),
                    description: this.form.description,
                    request_id: `SAV-${this.selectedStudent.id}-${Date.now()}`
                }
                
                const res = await fetch(window.BASE_URL + 'api/finance.php?action=save_savings', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                })
                const data = await res.json()
                
                if (data.success) {
                    this.showModal = false
                    await this.fetchSavingsData() // Tunggu refresh data selesai dulu
                    alert('Transaksi berhasil!')
                } else {
                    alert(data.message || 'Gagal memproses transaksi')
                }
            } catch (e) {
                alert('Terjadi kesalahan: ' + e.message)
            } finally {
                this.isSubmitting = false
            }
        }
    },
    mounted() {
        this.fetchUnits() // Load units for batch mode
    }
}).mount('#app')
</script>
</body>
</html>
