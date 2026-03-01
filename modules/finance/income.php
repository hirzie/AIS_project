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
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">1. Jenis Penerimaan</label>
                    <select v-model="form.payment_type_id" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 outline-none transition-all">
                        <option value="">-- Semua Jenis Penerimaan --</option>
                        <option v-for="pt in paymentTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">2. Pilih Unit <span v-if="units.length === 0" class="text-red-500 text-[10px]">(Loading...)</span></label>
                    <select v-model="selectedUnitId" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 outline-none transition-all">
                        <option value="">-- Semua Unit ({{ units.length }}) --</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div class="relative">
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
                <div class="relative">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">4. Pilih Tagihan ({{ filteredUnpaidBills.length }})</label>
                    <select v-model="form.bill_id" @change="onBillSelect" :disabled="!selectedStudent" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-green-500 focus:ring-2 focus:ring-green-100 outline-none transition-all disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">-- Pilih Item Pembayaran --</option>
                        <option v-for="b in filteredUnpaidBills" :key="b.id" :value="b.id">
                            {{ b.bill_name }} (Sisa: Rp {{ formatNumber(b.amount - b.amount_paid) }})
                        </option>
                    </select>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">5a. Nominal Bayar (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 font-bold text-slate-400">Rp</span>
                            <input type="number" v-model="form.amount" class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-lg font-bold text-slate-800 focus:border-green-500 outline-none" placeholder="0">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="chkDiscount" v-model="showDiscount" class="w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-500">
                        <label for="chkDiscount" class="text-sm text-slate-600 font-medium select-none">Gunakan Diskon / Potongan</label>
                    </div>
                    <div v-if="showDiscount" class="animate-fade">
                        <label class="block text-xs font-bold text-yellow-600 mb-1 uppercase">5b. Nominal Diskon (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 font-bold text-yellow-500">Rp</span>
                            <input type="number" v-model="form.discount_amount" class="w-full pl-10 pr-4 py-2.5 border border-yellow-300 bg-yellow-50 rounded-lg text-lg font-bold text-yellow-800 focus:border-yellow-500 outline-none" placeholder="0">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">6. Masuk ke Akun (Debit)</label>
                    <select v-model="form.cash_account_id" class="w-full border border-slate-300 bg-blue-50 rounded-lg px-3 py-2.5 text-sm font-bold text-blue-800 focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Akun Kas/Bank --</option>
                        <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">{{ acc.code }} - {{ acc.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">7. Catatan (Opsional)</label>
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
                    payment_type_id: '', // New field
                    transaction_type: 'INCOME'
                },
                cart: []
            }
        },
        computed: {
            cashAccounts() {
                return this.accounts.filter(a => a.code.startsWith('11') && a.type === 'ASSET');
            },
            totalAmount() {
                return this.cart.reduce((sum, item) => sum + Number(item.amount), 0);
            },
            filteredUnpaidBills() {
                if (!this.form.payment_type_id) return this.unpaidBills;
                return this.unpaidBills.filter(b => b.payment_type_id == this.form.payment_type_id);
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
                // No need for detailed info yet, but good for badges
                try {
                    // Assuming get_student_detail is not in api/finance.php based on read file
                    // But maybe it's implicitly handled or I should check.
                    // Actually, finance.php doesn't have get_student_detail. 
                    // Let's remove this call to avoid 404 if it doesn't exist.
                    // Or keep it if it was working before.
                    // The previous code had it. Let's assume it might be handled or fail silently.
                    // But to be safe, let's use search result data which has some info.
                    // Wait, previous code had it. I should check if it exists in api/finance.php.
                    // It does NOT exist in the file I read.
                    // I will remove it to prevent errors.
                    // Instead, I'll rely on what search returns.
                    // But search returns limited data.
                    // Let's check `get_student_savings` which returns balance.
                    // Let's skip detail fetch for now as it wasn't in the provided api/finance.php
                } catch (e) {}
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
                        // Filter only unpaid or partial
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
                this.form.payment_type_id = bill.payment_type_id; // Auto select type
                
                // Auto-set Unit based on selected student/unit selection if empty
                if (!this.form.unit_id && this.selectedUnitId) {
                    this.form.unit_id = this.selectedUnitId;
                } else if (!this.form.unit_id && this.selectedStudent && this.selectedStudent.unit_id) {
                    this.form.unit_id = this.selectedStudent.unit_id;
                }
            },
            addToCart() {
                if (!this.form.bill_id || !this.form.amount || !this.form.cash_account_id || !this.form.unit_id) {
                    alert('Mohon isi semua data wajib (termasuk Unit).');
                    return;
                }
                const item = { ...this.form };
                this.cart.push(item);
                
                // Keep Unit & Type for easier subsequent entry, clear others
                const prevUnit = this.form.unit_id;
                const prevType = this.form.payment_type_id; // Keep payment type too? Or reset?
                // Maybe reset payment type if they want to pay different bill?
                // But usually one student pays multiple SPP (same type) or SPP + Gedung (diff type).
                // Let's keep unit, reset bill-specifics.
                
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
            async saveTransaction() {
                if (!confirm('Proses pembayaran ini?')) return;
                try {
                    // Group by Bill? Or just send one by one?
                    // API pay_bill supports batch items or single.
                    // Let's send all as one batch to api/finance.php?action=pay_bill
                    
                    const payload = {
                        date: this.globalDate,
                        items: this.cart.map(item => ({
                            bill_id: item.bill_id,
                            amount: item.amount,
                            discount_amount: item.discount_amount,
                            cash_account_id: item.cash_account_id,
                            description: item.description,
                            unit_id: item.unit_id
                        }))
                    };

                    const res = await fetch(this.baseUrl + 'api/finance.php?action=pay_bill', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        alert('Pembayaran berhasil diproses! Transaksi: ' + (data.data.trans_number || ''));
                        this.cart = [];
                        this.resetStudent();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (e) {
                    alert('ERROR: ' + e.message);
                }
            }
        },
        mounted() {
            this.fetchInit();
        }
    }).mount('#app')
</script>
</div> <!-- Close .flex container -->
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
