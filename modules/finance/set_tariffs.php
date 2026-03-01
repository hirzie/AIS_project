<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-purple-200">
                <i class="fas fa-tags text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Setting Tarif Tagihan</h1>
                <span class="text-xs text-slate-500 font-medium">Atur nominal tagihan per kelas</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Tahun Ajaran</label>
                        <select v-model="filter.academic_year_id" @change="fetchClasses" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Unit Sekolah</label>
                        <select v-model="filter.unit_id" @change="fetchClasses" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700">
                            <option value="">-- Pilih Unit --</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Kelas</label>
                        <select v-model="filter.class_id" @change="fetchTariffs" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700" :disabled="classes.length==0">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Jenis Tagihan</label>
                        <select v-model="filter.payment_type_id" @change="fetchTariffs" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700">
                            <option value="">-- Pilih Jenis Tagihan --</option>
                            <option v-for="p in paymentTypes.filter(pt => pt.category && pt.category.includes('MANDATORY'))" :key="p.id" :value="p.id">{{ p.name }} ({{ p.type }})</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="displayedClasses.length > 0 && filter.payment_type_id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Setting Tarif Kelas</h3>
                    <button @click="saveAll" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-purple-700 text-sm shadow-lg shadow-purple-200 transition-all">
                        <i class="fas fa-save mr-2"></i> Simpan Tarif
                    </button>
                </div>
                
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 w-10 text-center">#</th>
                            <th class="px-6 py-3">Kelas</th>
                            <th class="px-6 py-3 text-right">Nominal Tagihan (Rp)</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(cls, index) in displayedClasses" :key="cls.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-center text-slate-400">{{ index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ cls.name }}</div>
                                <div class="text-xs text-slate-500">{{ cls.level_name }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <input type="number" v-model="cls.tariff_amount" class="w-48 text-right border border-slate-300 rounded px-3 py-2 font-bold text-slate-800 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 outline-none" placeholder="0">
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="generateBills(cls)" class="text-xs font-bold bg-blue-100 text-blue-600 px-3 py-1.5 rounded hover:bg-blue-600 hover:text-white transition-colors" title="Generate Tagihan untuk Kelas ini">
                                    <i class="fas fa-sync-alt mr-1"></i> Generate
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else-if="filter.unit_id && !filter.class_id" class="text-center py-12 text-slate-400">
                <i class="fas fa-hand-pointer text-4xl mb-3 opacity-20"></i>
                <p>Silakan pilih kelas terlebih dahulu.</p>
            </div>

            <div v-if="showPeriodModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-xl shadow-xl w-96">
                    <h3 class="font-bold text-lg mb-4 text-slate-800">Pilih Periode Tagihan</h3>
                    <p class="text-sm text-slate-500 mb-4">Untuk Kelas: <span class="font-bold text-slate-700">{{ selectedClass ? selectedClass.name : '' }}</span></p>
                    
                    <div class="space-y-3">
                        <button @click="confirmGenerate('SEMESTER_1')" class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:border-purple-500 hover:bg-purple-50 flex justify-between items-center transition-all group">
                            <div>
                                <div class="font-bold text-slate-700 group-hover:text-purple-700">Semester 1 (Ganjil)</div>
                                <div class="text-xs text-slate-500">Juli - Desember</div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-purple-500"></i>
                        </button>
                        <button @click="confirmGenerate('SEMESTER_2')" class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:border-purple-500 hover:bg-purple-50 flex justify-between items-center transition-all group">
                            <div>
                                <div class="font-bold text-slate-700 group-hover:text-purple-700">Semester 2 (Genap)</div>
                                <div class="text-xs text-slate-500">Januari - Juni</div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-purple-500"></i>
                        </button>
                        <button @click="confirmGenerate('FULL')" class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:border-purple-500 hover:bg-purple-50 flex justify-between items-center transition-all group">
                            <div>
                                <div class="font-bold text-slate-700 group-hover:text-purple-700">Satu Tahun Penuh</div>
                                <div class="text-xs text-slate-500">Juli - Juni (12 Bulan)</div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-purple-500"></i>
                        </button>
                        <button @click="confirmGenerate('ONCE')" class="w-full text-left px-4 py-3 rounded-lg border border-slate-200 hover:border-purple-500 hover:bg-purple-50 flex justify-between items-center transition-all group">
                            <div>
                                <div class="font-bold text-slate-700 group-hover:text-purple-700">Sekali Bayar (Langsung)</div>
                                <div class="text-xs text-slate-500">Tanpa Cicilan / Non-Bulanan</div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-purple-500"></i>
                        </button>
                    </div>
                    <button @click="showPeriodModal = false" class="mt-6 w-full text-center text-slate-400 hover:text-slate-600 text-sm font-bold">Batal</button>
                </div>
            </div>

            <div v-if="showResultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-xl w-3/4 max-h-[80vh] flex flex-col">
                    <div class="p-6 border-b border-slate-200 flex justify-between items-center bg-slate-50 rounded-t-xl">
                        <div>
                            <h3 class="font-bold text-lg text-slate-800">Hasil Generate Tagihan</h3>
                            <p class="text-sm text-slate-500">Kelas: <span class="font-bold">{{ selectedClass ? selectedClass.name : '' }}</span></p>
                        </div>
                        <button @click="showResultModal = false" class="text-slate-400 hover:text-slate-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-100 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3">No</th>
                                    <th class="px-4 py-3">Nama Siswa</th>
                                    <th class="px-4 py-3">Tagihan</th>
                                    <th class="px-4 py-3 text-right">Nominal</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(bill, idx) in generatedResults" :key="idx" class="hover:bg-slate-50">
                                    <td class="px-4 py-2 text-slate-400">{{ idx + 1 }}</td>
                                    <td class="px-4 py-2 font-bold text-slate-700">{{ bill.student_name }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ bill.bill_name }}</td>
                                    <td class="px-4 py-2 text-right font-mono font-bold">{{ new Intl.NumberFormat('id-ID').format(bill.amount) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="px-2 py-1 rounded text-xs font-bold" :class="bill.status === 'PAID' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'">
                                            {{ bill.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="generatedResults.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">Belum ada tagihan yang terbentuk.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-6 border-t border-slate-200 bg-slate-50 rounded-b-xl text-right">
                        <button @click="showResultModal = false" class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-slate-900">
                            Tutup
                        </button>
                    </div>
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
                years: [],
                units: [],
                paymentTypes: [],
                classes: [],
                
                filter: {
                    academic_year_id: '',
                    unit_id: '',
                    class_id: '',
                    payment_type_id: ''
                },
                
                showPeriodModal: false,
                selectedClass: null,
                
                showResultModal: false,
                generatedResults: []
            }
        },
        computed: {
            displayedClasses() {
                if (this.filter.class_id) {
                    return this.classes.filter(c => c.id == this.filter.class_id);
                }
                return [];
            }
        },
        methods: {
            async fetchInit() {
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_settings`);
                    const data = await res.json();
                    if (data.success) {
                        this.years = data.data.years;
                        this.units = data.data.units;
                        this.paymentTypes = data.data.paymentTypes;
                        const activeYear = this.years.find(y => y.is_active) || this.years[0];
                        if (activeYear) this.filter.academic_year_id = activeYear.id;
                    }
                } catch (e) {
                    console.error("Fetch Init Error:", e);
                }
            },
            async fetchClasses() {
                if (!this.filter.academic_year_id || !this.filter.unit_id) {
                    this.classes = [];
                    return;
                }
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_classes&academic_year_id=${this.filter.academic_year_id}&unit_id=${this.filter.unit_id}`);
                    const data = await res.json();
                    if (data.success) {
                        this.classes = data.data.map(c => ({...c, tariff_amount: 0}));
                        this.fetchTariffs();
                    } else {
                        console.error("API Error:", data.message);
                    }
                } catch (e) {
                    console.error("Fetch Classes Error:", e);
                }
            },
            async fetchTariffs() {
                if (!this.filter.payment_type_id || this.displayedClasses.length === 0) return;
                const pType = this.paymentTypes.find(p => p.id == this.filter.payment_type_id);
                const defaultAmount = pType ? pType.default_amount : 0;
                for (let cls of this.displayedClasses) {
                    try {
                        const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_tariffs&class_id=${cls.id}`);
                        const data = await res.json();
                        const tariff = data.data.find(t => t.payment_type_id == this.filter.payment_type_id);
                        cls.tariff_amount = tariff ? tariff.amount : defaultAmount;
                    } catch (e) {}
                }
            },
            async saveAll() {
                if (!confirm('Simpan perubahan tarif?')) return;
                for (let cls of this.displayedClasses) {
                    const payload = {
                        class_id: cls.id,
                        payment_type_id: this.filter.payment_type_id,
                        academic_year_id: this.filter.academic_year_id,
                        amount: cls.tariff_amount
                    };
                    try {
                        await fetch(`${window.BASE_URL}api/finance.php?action=save_tariff`, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        });
                    } catch (e) {}
                }
                alert(`Berhasil menyimpan tarif untuk kelas ini.`);
            },
            generateBills(cls) {
                this.selectedClass = cls;
                this.showPeriodModal = true;
            },
            async confirmGenerate(periodMode) {
                this.showPeriodModal = false;
                const cls = this.selectedClass;
                if (!confirm(`Yakin generate tagihan ${periodMode} untuk kelas ${cls.name}?`)) return;
                try {
                    const savePayload = {
                        class_id: cls.id,
                        payment_type_id: this.filter.payment_type_id,
                        academic_year_id: this.filter.academic_year_id,
                        amount: cls.tariff_amount
                    };
                    await fetch(`${window.BASE_URL}api/finance.php?action=save_tariff`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(savePayload)
                    });
                } catch (e) {
                    console.error("Auto-save failed", e);
                }

                const payload = {
                    academic_year_id: this.filter.academic_year_id,
                    payment_type_id: this.filter.payment_type_id,
                    class_id: cls.id,
                    period_mode: periodMode
                };

                try {
                    console.log('Sending payload:', payload);
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=generate_bills`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    console.log('Response:', data);
                    
                    if (data.success) {
                        this.generatedResults = data.data || [];
                        if (this.generatedResults.length === 0) {
                            alert('Proses selesai, namun tidak ada tagihan baru yang dibuat. Kemungkinan tagihan untuk periode ini sudah ada sebelumnya.');
                        } else {
                            this.showResultModal = true;
                        }
                    } else {
                        alert(data.message || 'Gagal generate');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan saat generate: ' + e.message);
                }
            }
        },
        mounted() {
            this.fetchInit();
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
