<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-green-200">
                <i class="fas fa-hand-holding-usd text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Transaksi Penerimaan</h1>
                <span class="text-xs text-slate-500 font-medium">Pembayaran Tagihan & Pemasukan</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
             <div class="flex items-center gap-2 bg-slate-100 rounded-lg px-3 py-1.5 border border-slate-200">
                <i class="fas fa-calendar-alt text-slate-400"></i>
                <input type="date" v-model="globalDate" class="bg-transparent text-sm font-bold text-slate-700 focus:outline-none">
             </div>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden flex flex-col md:flex-row">
        <div class="w-full md:w-[450px] bg-white border-r border-slate-200 flex flex-col h-full shadow-xl z-10 overflow-y-auto">
            <div class="p-6 space-y-5">
                <div class="bg-green-50 p-4 rounded-xl border border-green-100">
                    <h3 class="font-bold text-green-800 text-sm mb-1"><i class="fas fa-edit mr-1"></i> Input Penerimaan</h3>
                    <p class="text-xs text-green-600">Cari siswa dan pilih tagihan yang akan dibayar.</p>
                </div>
                
                <!-- Step 1: Kategori -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">1. Kategori Penerimaan</label>
                    <select v-model="selectedCategory" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 outline-none transition-all">
                        <option value="">-- Semua Kategori --</option>
                        <option v-for="(label, code) in categoryMap" :key="code" :value="code">{{ label }}</option>
                    </select>
                </div>

                <!-- Step 2: Pilih Unit -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">2. Pilih Unit <span v-if="units.length === 0" class="text-red-500 text-[10px]">(Loading...)</span></label>
                    <select v-model="selectedUnitId" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 outline-none transition-all">
                        <option value="">-- Semua Unit ({{ units.length }}) --</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <!-- Step 3: Cari Siswa (Hidden for OTHER) -->
                <div v-if="!selectedCategory.includes('OTHER')" class="relative">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">3. Cari Siswa</label>
                    <div class="relative">
                        <input type="text" v-model="studentSearch" @input="searchStudents" placeholder="Ketik Nama atau NIS..." class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none transition-all">
                        <i class="fas fa-search absolute left-3 top-3 text-slate-400"></i>
                        <button v-if="studentSearch" @click="resetStudent" class="absolute right-3 top-2.5 text-slate-300 hover:text-red-500"><i class="fas fa-times"></i></button>
                    </div>
                    <div v-if="students.length > 0 && !selectedStudent" class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-xl border border-slate-100 mt-2 z-50 max-h-60 overflow-y-auto">
                        <div v-for="s in students" :key="s.id" @click="selectStudent(s)" class="p-3 hover:bg-green-50 cursor-pointer border-b border-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold">
                                {{ s.name.substring(0,1) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-700 text-sm">{{ s.name }}</div>
                                <div class="text-xs text-slate-400">{{ s.identity_number }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="selectedStudent" class="mt-2 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white border-2 border-green-200 overflow-hidden">
                            <img :src="'https://ui-avatars.com/api/?name=' + selectedStudent.name + '&background=random'" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-green-800 text-sm">{{ selectedStudent.name }}</div>
                            <div class="text-xs text-green-600">{{ selectedStudent.identity_number }}</div>
                        </div>
                        <button @click="resetStudent" class="text-green-400 hover:text-green-600"><i class="fas fa-times-circle"></i></button>
                    </div>
                </div>
                
                <!-- Step 4: Pilih Tagihan (MANDATORY) -->
                <div v-if="!selectedCategory.includes('OTHER') && !selectedCategory.includes('VOLUNTARY')" class="relative">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">4. Pilih Tagihan ({{ filteredUnpaidBills.length }})</label>
                    <select v-model="form.bill_id" @change="onBillSelect" :disabled="!selectedStudent" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none transition-all disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">-- Pilih Item Pembayaran --</option>
                        <option v-for="b in filteredUnpaidBills" :key="b.id" :value="b.id">
                            {{ b.bill_name }} (Sisa: Rp {{ formatNumber(b.amount - b.amount_paid) }})
                        </option>
                    </select>
                </div>
                
                <!-- Step 3/4: OTHER Flows -->
                <div v-if="selectedCategory.includes('OTHER')" class="space-y-4">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">3. Jenis Penerimaan</label>
                        <select v-model="form.payment_type_id" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 outline-none transition-all">
                            <option value="">-- Pilih Jenis --</option>
                            <option v-for="pt in filteredPaymentTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                        </select>
                    </div>
                    <div class="relative">
                         <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">4. Nama Penyetor / Sumber Dana</label>
                         <input type="text" v-model="form.student_name" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 focus:border-green-500 outline-none" placeholder="Contoh: Hamba Allah, Dinas Pendidikan, dll">
                    </div>
                </div>

                <!-- Step 4: VOLUNTARY Flows -->
                <div v-if="selectedCategory.includes('VOLUNTARY')" class="relative">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">4. Jenis Penerimaan</label>
                    <select v-model="form.payment_type_id" :disabled="!selectedStudent" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none transition-all disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">-- Pilih Jenis --</option>
                        <option v-for="pt in filteredPaymentTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                    </select>
                </div>
                
                <!-- Step 5: Nominal & Diskon -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">{{ selectedCategory.includes('OTHER') ? '5' : '5a' }}. Nominal Bayar (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 font-bold text-slate-400">Rp</span>
                            <input type="number" v-model="form.amount" class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-lg font-bold text-slate-800 focus:border-green-500 outline-none" placeholder="0">
                        </div>
                    </div>
                    <div v-if="!selectedCategory.includes('OTHER')" class="flex items-center gap-2">
                        <input type="checkbox" id="chkDiscount" v-model="showDiscount" class="w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-500">
                        <label for="chkDiscount" class="text-sm text-slate-600 font-medium select-none">Gunakan Diskon / Potongan</label>
                    </div>
                    <div v-if="showDiscount && !selectedCategory.includes('OTHER')" class="animate-fade">
                        <label class="block text-xs font-bold text-yellow-600 mb-1 uppercase">5b. Nominal Diskon (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 font-bold text-yellow-500">Rp</span>
                            <input type="number" v-model="form.discount_amount" class="w-full pl-10 pr-4 py-2.5 border border-yellow-300 bg-yellow-50 rounded-lg text-lg font-bold text-yellow-800 focus:border-yellow-500 outline-none" placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Step 6: Akun Kas -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">{{ selectedCategory.includes('OTHER') ? '6' : '6' }}. Masuk ke Akun (Debit)</label>
                    <select v-model="form.cash_account_id" class="w-full border border-slate-300 bg-blue-50 rounded-lg px-3 py-2.5 text-sm font-bold text-blue-800 focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Akun Kas/Bank --</option>
                        <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">{{ acc.code }} - {{ acc.name }}</option>
                    </select>
                </div>

                <!-- Step 7: Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">{{ selectedCategory.includes('OTHER') ? '7' : '7' }}. Catatan (Opsional)</label>
                    <textarea v-model="form.description" rows="2" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none transition-all" placeholder="Keterangan tambahan..."></textarea>
                </div>
                <button @click="addToCart" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-slate-900 transition-all flex items-center justify-center gap-2 shadow-lg">
                    <i class="fas fa-plus-circle"></i> Tambah ke Daftar
                </button>
            </div>
        </div>

        <div class="flex-1 flex flex-col bg-slate-50 relative overflow-hidden">
            <div class="flex-1 overflow-y-auto p-6">
                <div v-if="selectedStudent" class="bg-white rounded-xl shadow-sm border border-slate-200 mb-4 p-4 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-slate-100 overflow-hidden border border-slate-200">
                        <img :src="'https://ui-avatars.com/api/?name=' + selectedStudent.name + '&background=random'" class="w-full h-full object-cover" alt="Avatar">
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-slate-800 text-base">{{ selectedStudent.name }}</div>
                        <div class="text-xs text-slate-500">NIS: {{ selectedStudent.identity_number || '-' }}</div>
                        <div class="text-xs text-slate-500">Kelas: {{ (selectedStudentDetail && selectedStudentDetail.class_name) ? selectedStudentDetail.class_name : '-' }}</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span v-if="showKaryawanBadge()" :class="'px-2 py-1 rounded-full text-[10px] font-bold ' + getStatusAnakClass()">{{ getStatusAnakText() }}</span>
                            <span v-if="showAsramaBadge()" :class="'px-2 py-1 rounded-full text-[10px] font-bold ' + getAsramaClass()">Santri Asrama</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden min-h-[400px] flex flex-col">
                    <div class="bg-slate-100 px-6 py-3 border-b border-slate-200 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700">Daftar Penerimaan (Draft)</h3>
                        <span class="text-xs font-bold bg-slate-200 text-slate-600 px-2 py-1 rounded">{{ cart.length }} Item</span>
                    </div>
                    <div class="flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center">#</th>
                                    <th class="px-4 py-3">Siswa</th>
                                    <th class="px-4 py-3">Unit/Tipe</th>
                                    <th class="px-4 py-3">Pembayaran</th>
                                    <th class="px-4 py-3">Akun Masuk</th>
                                    <th class="px-4 py-3 text-right">Jumlah</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in cart" :key="index" class="hover:bg-slate-50 group">
                                    <td class="px-4 py-3 text-center text-slate-400">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-700">{{ item.student_name }}</div>
                                        <div class="text-xs text-slate-500">{{ item.student_nis }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs font-bold text-slate-700">{{ getUnitName(item.unit_id) }}</div>
                                        <div class="text-[10px] text-slate-400 uppercase">{{ item.transaction_type }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-700 font-medium">{{ item.bill_name }}</div>
                                        <div class="text-xs text-slate-500 italic">{{ item.description || '-' }}</div>
                                        <div v-if="item.discount_amount > 0" class="text-xs text-yellow-600 font-bold mt-1">
                                            Diskon: Rp {{ formatNumber(item.discount_amount) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                                        {{ getAccountCode(item.cash_account_id) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600">
                                        Rp {{ formatNumber(item.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="removeFromCart(index)" class="text-slate-300 hover:text-red-600 transition-colors"><i class="fas fa-times-circle text-lg"></i></button>
                                    </td>
                                </tr>
                                <tr v-if="cart.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">
                                        <div class="flex flex col items-center">
                                            <i class="fas fa-file-invoice text-4xl mb-3 opacity-20"></i>
                                            <p>Belum ada item ditambahkan.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end items-center gap-4">
                        <span class="text-slate-500 font-bold uppercase text-xs tracking-wider">Total Penerimaan</span>
                        <span class="text-2xl font-bold text-slate-800">Rp {{ formatNumber(totalAmount) }}</span>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex justify-between items-center z-20">
                <button @click="cart = []" :disabled="cart.length === 0" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 disabled:opacity-50 transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i> Bersihkan
                </button>
                <button @click="saveTransaction" :disabled="cart.length === 0" class="bg-green-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-green-700 shadow-lg shadow-green-200 disabled:opacity-50 disabled:shadow-none transition-all active:scale-[0.98]">
                    <i class="fas fa-save mr-2"></i> Proses Pembayaran
                </button>
            </div>
        </div>
    </main>

    <!-- Confirm Modal -->
    <div v-if="showConfirmModal" class="fixed inset-0 bg-black/20 z-50 flex items-center justify-center p-4 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100 border border-slate-100">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center shrink-0">
                        <i class="fas fa-question text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Konfirmasi Pembayaran</h3>
                        <p class="text-xs text-slate-500">Mohon periksa kembali data sebelum memproses.</p>
                    </div>
                </div>
                
                <div class="bg-slate-50 rounded-lg p-3 mb-6 border border-slate-100 max-h-40 overflow-y-auto">
                    <div v-for="(item, idx) in cart" :key="idx" class="flex justify-between items-start mb-2 last:mb-0 border-b last:border-0 border-slate-100 pb-2 last:pb-0">
                        <div>
                            <div class="text-xs font-bold text-slate-700">{{ item.bill_name }}</div>
                            <div class="text-[10px] text-slate-500">{{ item.student_name }}</div>
                        </div>
                        <div class="text-xs font-bold text-slate-700">Rp {{ formatNumber(item.amount) }}</div>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-6 px-1">
                    <span class="text-sm font-bold text-slate-500">Total Nominal</span>
                    <span class="text-xl font-bold text-green-600">Rp {{ formatNumber(totalAmount) }}</span>
                </div>

                <div class="flex gap-3">
                    <button @click="showConfirmModal = false" :disabled="isProcessing" class="flex-1 px-4 py-3 border border-slate-300 rounded-xl text-slate-600 font-bold hover:bg-slate-50 transition-colors disabled:opacity-50">
                        Batal
                    </button>
                    <button @click="processTransaction" :disabled="isProcessing" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition-colors flex items-center justify-center gap-2 shadow-lg shadow-green-200 disabled:opacity-50 disabled:shadow-none">
                        <span v-if="isProcessing" class="animate-spin"><i class="fas fa-spinner"></i></span>
                        <span>{{ isProcessing ? 'Memproses...' : 'Ya, Proses' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-100">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                    <i class="fas fa-check text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Pembayaran Berhasil!</h3>
                <p class="text-sm text-slate-600 mb-6">Transaksi berhasil disimpan.<br>No. Transaksi: <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded">{{ lastTransNumber }}</span></p>
                <button @click="closeSuccessModal" class="w-full px-4 py-3 bg-slate-800 text-white rounded-xl font-bold hover:bg-slate-900 transition-colors shadow-lg">
                    Selesai & Input Baru
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                globalDate: new Date().toISOString().split('T')[0],
                baseUrl: (window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/')),
                accounts: [],
                paymentTypes: [],
                units: [],
                selectedUnitId: '',
                studentSearch: '',
                students: [],
                selectedStudent: null,
                selectedStudentDetail: null,
                selectedCategory: 'MANDATORY_STUDENT', // Default to Iuran Wajib
                categoryMap: {
                    'MANDATORY_STUDENT': 'Iuran Wajib Siswa',
                    'VOLUNTARY_STUDENT': 'Iuran Sukarela Siswa',
                    'MANDATORY_PROSPECT': 'Iuran Wajib Calon Siswa',
                    'VOLUNTARY_PROSPECT': 'Iuran Sukarela Calon Siswa',
                    'OTHER': 'Penerimaan Lainnya'
                },
                unpaidBills: [],
                showDiscount: false,
                form: {
                    bill_id: '',
                    amount: '',
                    discount_amount: 0,
                    cash_account_id: '',
                    description: '',
                    bill_name: '',
                    student_name: '',
                    student_nis: '',
                    unit_id: '',
                    payment_type_id: '',
                    transaction_type: 'INCOME'
                },
                cart: [],
                showConfirmModal: false,
                showSuccessModal: false,
                isProcessing: false,
                lastTransNumber: ''
            }
        },
        computed: {
            filteredPaymentTypes() {
                if (!this.selectedCategory) return [];
                return this.paymentTypes.filter(pt => pt.category === this.selectedCategory);
            },
            cashAccounts() {
                return this.accounts.filter(a => a.code.startsWith('11') && a.type === 'ASSET');
            },
            totalAmount() {
                return this.cart.reduce((sum, item) => sum + Number(item.amount), 0);
            },
            filteredUnpaidBills() {
                if (!this.selectedCategory) return this.unpaidBills;
                return this.unpaidBills.filter(b => b.category === this.selectedCategory);
            }
        },
        methods: {
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            },
            getUnitName(id) {
                if (!id) return '-';
                const u = this.units.find(x => x.id == id);
                return u ? u.name : '-';
            },
            showAsramaBadge() {
                const d = this.selectedStudentDetail;
                const v = d && d.custom_values ? d.custom_values.asrama_status : null;
                if (!v) return false;
                const val = String(v).toLowerCase();
                return (val === '1' || val === 'ya' || val === 'aktif' || val === 'y');
            },
            getAsramaClass() {
                const d = this.selectedStudentDetail;
                const v = d && d.custom_values ? d.custom_values.asrama_status : null;
                if (!v) return 'bg-slate-100 text-slate-700 border border-slate-200';
                const val = String(v).toLowerCase();
                if (val === '1' || val === 'ya' || val === 'aktif' || val === 'y') return 'bg-green-100 text-green-700 border border-green-200';
                return 'bg-slate-100 text-slate-700 border border-slate-200';
            },
            showKaryawanBadge() {
                const d = this.selectedStudentDetail;
                const v = d && d.custom_values ? d.custom_values.statusanak : null;
                if (!v) return false;
                const s = String(v).toLowerCase();
                return s.includes('staf') || s.includes('staff') || s.includes('guru') || s.includes('yayasan') || s.includes('pegawai');
            },
            getStatusAnakText() {
                const d = this.selectedStudentDetail;
                const v = d && d.custom_values ? d.custom_values.statusanak : null;
                return v ? String(v) : '';
            },
            getStatusAnakClass() {
                const d = this.selectedStudentDetail;
                const v = d && d.custom_values ? String(d.custom_values.statusanak).toLowerCase() : '';
                if (!v) return 'bg-slate-100 text-slate-700 border border-slate-200';
                if (v.includes('staf') || v.includes('staff') || v.includes('pegawai')) return 'bg-amber-100 text-amber-700 border border-amber-200';
                if (v.includes('guru')) return 'bg-green-100 text-green-700 border border-green-200';
                if (v.includes('yayasan')) return 'bg-purple-100 text-purple-700 border border-purple-200';
                return 'bg-slate-100 text-slate-700 border border-slate-200';
            },
            async fetchInit() {
                try {
                    const resAcc = await fetch(this.baseUrl + 'api/finance.php?action=get_accounts');
                    const dataAcc = await resAcc.json();
                    if (dataAcc.success) this.accounts = dataAcc.data;
                    const res = await fetch(this.baseUrl + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.units = data.data.units || [];
                        this.paymentTypes = data.data.paymentTypes || [];
                    }
                } catch (e) {}
            },
            async searchStudents() {
                if (!this.studentSearch || this.studentSearch.length < 3) {
                    this.students = [];
                    return;
                }
                try {
                    const res = await fetch(this.baseUrl + `api/finance.php?action=search_students&q=${encodeURIComponent(this.studentSearch)}${this.selectedUnitId ? '&unit_id=' + this.selectedUnitId : ''}`);
                    const data = await res.json();
                    this.students = data.data;
                } catch (e) {}
            },
            async selectStudent(s) {
                this.selectedStudent = s;
                this.studentSearch = '';
                this.students = [];
                this.fetchUnpaidBills(s.id);
            },
            resetStudent() {
                this.selectedStudent = null;
                this.selectedStudentDetail = null;
                this.unpaidBills = [];
            },
            async fetchUnpaidBills(studentId) {
                try {
                    const res = await fetch(this.baseUrl + `api/finance.php?action=get_student_bills&student_id=${studentId}`);
                    const data = await res.json();
                    if (data.success) {
                        this.unpaidBills = (data.data.bills || []).filter(b => b.status !== 'PAID');
                    }
                } catch (e) {}
            },
            onBillSelect() {
                const bill = this.unpaidBills.find(b => b.id == this.form.bill_id);
                if (!bill) return;
                this.form.bill_name = bill.bill_name;
                this.form.amount = String(Math.max(0, bill.amount - bill.amount_paid));
                this.form.student_name = this.selectedStudent?.name || '';
                this.form.student_nis = this.selectedStudent?.identity_number || '';
                this.form.payment_type_id = bill.payment_type_id; 
                
                if (!this.form.unit_id) {
                     if (this.selectedUnitId) {
                         this.form.unit_id = this.selectedUnitId;
                     } else if (this.selectedStudent && this.selectedStudent.unit_id) {
                         this.form.unit_id = this.selectedStudent.unit_id;
                     }
                }
            },
            addToCart() {
                if (!this.form.cash_account_id || !this.form.amount) {
                    alert('Mohon isi Nominal dan Akun Kas.');
                    return;
                }

                if (!this.form.unit_id && !this.selectedCategory.includes('OTHER') && !this.selectedCategory.includes('VOLUNTARY')) {
                     alert('Mohon pilih unit.');
                     return;
                }
                
                if (!this.selectedCategory.includes('OTHER') && !this.selectedCategory.includes('VOLUNTARY')) {
                     if (!this.form.bill_id) {
                         alert('Mohon pilih tagihan siswa.');
                         return;
                     }
                } else {
                    if (!this.form.payment_type_id) {
                        alert('Mohon pilih Jenis Penerimaan.');
                        return;
                    }
                    if (this.selectedCategory.includes('OTHER') && !this.form.student_name) {
                        alert('Mohon isi Nama Penyetor / Sumber Dana.');
                        return;
                    }
                    const pt = this.filteredPaymentTypes.find(p => p.id === this.form.payment_type_id);
                    this.form.bill_name = pt ? pt.name : (this.selectedCategory.includes('VOLUNTARY') ? 'Iuran Sukarela' : 'Penerimaan Lainnya');
                    
                    if (this.selectedCategory.includes('VOLUNTARY')) {
                         this.form.student_name = this.selectedStudent ? this.selectedStudent.name : '';
                         this.form.student_nis = this.selectedStudent ? this.selectedStudent.identity_number : '';
                         
                         if (!this.form.unit_id && this.selectedStudent && this.selectedStudent.unit_id) {
                             this.form.unit_id = this.selectedStudent.unit_id;
                         } else if (!this.form.unit_id && this.selectedUnitId) {
                             this.form.unit_id = this.selectedUnitId;
                         }
                    }
                    
                    this.form.transaction_type = this.selectedCategory.includes('VOLUNTARY') ? 'INCOME_VOLUNTARY' : 'INCOME_OTHER';
                }

                const item = { ...this.form };
                this.cart.push(item);
                
                const prevUnit = this.form.unit_id;
                
                this.form = { 
                    bill_id: '', amount: '', discount_amount: 0, cash_account_id: '', 
                    description: '', bill_name: '', student_name: '', student_nis: '',
                    unit_id: prevUnit, payment_type_id: '', transaction_type: 'INCOME'
                };
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
            },
            getAccountCode(id) {
                const acc = this.accounts.find(a => a.id == id);
                return acc ? `${acc.code} - ${acc.name}` : '-';
            },
            saveTransaction() {
                if (this.cart.length === 0) {
                    alert('Tidak ada item yang akan diproses.');
                    return;
                }
                this.showConfirmModal = true;
            },
            async processTransaction() {
                this.isProcessing = true;
                try {
                    const payload = {
                        date: this.globalDate,
                        items: this.cart.map(item => ({
                            bill_id: item.bill_id,
                            amount: item.amount,
                            discount_amount: item.discount_amount,
                            cash_account_id: item.cash_account_id,
                            description: item.description,
                            unit_id: item.unit_id,
                            payment_type_id: item.payment_type_id
                        }))
                    };

                    const res = await fetch(this.baseUrl + 'api/finance.php?action=pay_bill', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        this.lastTransNumber = data.data.trans_number || '-';
                        this.showConfirmModal = false;
                        this.showSuccessModal = true;
                        
                        // Callback Logic: Maintain state for consecutive entries
                        if (payload.items.length > 0) {
                            const last = payload.items[payload.items.length-1];
                            if (!this.selectedUnitId && last.unit_id) {
                                this.selectedUnitId = last.unit_id;
                            }
                        }
                    } else {
                        alert(data.message || 'Gagal memproses pembayaran');
                        this.showConfirmModal = false;
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem: ' + e.message);
                    this.showConfirmModal = false;
                } finally {
                    this.isProcessing = false;
                }
            },
            closeSuccessModal() {
                this.showSuccessModal = false;
                this.cart = [];
                this.resetStudent();
            }
        },
        mounted() {
            this.fetchInit();
        }
    }).mount('#app')
</script>
