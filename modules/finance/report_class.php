<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<style>
    .bg-blue-100 { background-color: #dbeafe !important; }
    .bg-green-100 { background-color: #dcfce7 !important; }
    .bg-purple-100 { background-color: #f3e8ff !important; }
    .text-blue-900 { color: #1e3a8a !important; }
    .text-green-900 { color: #14532d !important; }
    .text-purple-900 { color: #581c87 !important; }
    tr.bg-blue-100:hover { background-color: #bfdbfe !important; }
    tr.bg-green-100:hover { background-color: #bbf7d0 !important; }
    tr.bg-purple-100:hover { background-color: #e9d5ff !important; }
    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-left: 0 !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .badge-pegawai { background-color: #dbeafe; color: #1e3a8a; border: 1px solid #bfdbfe; }
    .badge-guru { background-color: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
    .badge-yayasan { background-color: #f3e8ff; color: #581c87; border: 1px solid #e9d5ff; }
</style>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-cyan-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-cyan-200">
                <i class="fas fa-chart-bar text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Kelas</h1>
                <span class="text-xs text-slate-500 font-medium">Rekap pembayaran siswa per kelas</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
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
                        <select v-model="filter.class_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700" :disabled="classes.length==0">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Jenis Tagihan</label>
                        <select v-model="filter.payment_type_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="ALL" class="font-bold text-indigo-600">Semua Pembayaran (Rekap Lengkap)</option>
                            <option v-for="p in paymentTypes" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" v-model="filter.asrama_only" class="w-4 h-4 rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <span>Tampilkan hanya Santri Asrama</span>
                    </label>
                    <button @click="fetchReport" :disabled="!canSearch" class="bg-cyan-600 text-white px-8 py-2 rounded-lg font-bold hover:bg-cyan-700 shadow-lg shadow-cyan-200 disabled:opacity-50 disabled:shadow-none transition-all">
                        <i class="fas fa-search mr-2"></i> Tampilkan Laporan
                    </button>
                </div>
            </div>

            <div v-if="reportData.length > 0" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Hasil Laporan</h3>
                    <button onclick="window.print()" class="text-slate-500 hover:text-slate-800"><i class="fas fa-print"></i> Cetak</button>
                </div>
                
                <div class="overflow-x-auto">
                    <table v-if="filter.payment_type_id !== 'ALL'" class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-500 font-bold uppercase text-xs whitespace-nowrap">
                            <tr>
                                <th class="px-6 py-3 w-10 text-center">No</th>
                                <th class="px-6 py-3">NIS</th>
                                <th class="px-6 py-3">Nama Siswa</th>
                                <th class="px-6 py-3 text-right">Total Tagihan</th>
                                <th class="px-6 py-3 text-right">Total Bayar</th>
                                <th class="px-6 py-3 text-right">Sisa / Tunggakan</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, index) in groupedData" :key="index" class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-center text-slate-400">{{ index + 1 }}</td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ row.nis }}</td>
                                <td class="px-6 py-4 font-bold text-slate-700">
                                    <div class="flex flex-col items-start gap-1">
                                        <span>{{ row.name }}</span>
                                        <span v-if="row.status_anak" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border" :class="getBadgeClass(row.status_anak)">
                                            {{ row.status_anak }}
                                        </span>
                                        <span v-if="isAsramaActive(row.asrama_status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border bg-green-100 text-green-700 border-green-200">
                                            Santri Asrama
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono">{{ formatMoney(row.total_bill) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-green-600">{{ formatMoney(row.total_paid) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-red-600 font-bold">{{ formatMoney(row.total_bill - row.total_paid) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="row.total_paid >= row.total_bill" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">LUNAS</span>
                                    <span v-else-if="row.total_paid > 0" class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">SEBAGIAN</span>
                                    <span v-else class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">BELUM BAYAR</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold text-slate-700 border-t border-slate-200">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right uppercase text-xs tracking-wider">Total Keseluruhan</td>
                                <td class="px-6 py-4 text-right">{{ formatMoney(grandTotal.bill) }}</td>
                                <td class="px-6 py-4 text-right text-green-600">{{ formatMoney(grandTotal.paid) }}</td>
                                <td class="px-6 py-4 text-right text-red-600">{{ formatMoney(grandTotal.bill - grandTotal.paid) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <table v-else class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-500 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3">Siswa</th>
                                <th class="px-4 py-3 w-[60%]">Rincian Pembayaran</th>
                                <th class="px-4 py-3 text-right">Total Tunggakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="(student, index) in groupedData" :key="index" class="hover:bg-slate-50">
                                <td class="px-4 py-4 text-center text-slate-400 align-top">{{ index + 1 }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-bold text-slate-800 text-base flex flex col items-start gap-1">
                                        <span>{{ student.name }}</span>
                                        <span v-if="student.status_anak" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border" :class="getBadgeClass(student.status_anak)">
                                            {{ student.status_anak }}
                                        </span>
                                        <span v-if="isAsramaActive(student.asrama_status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border bg-green-100 text-green-700 border-green-200">
                                            Santri Asrama
                                        </span>
                                    </div>
                                    <div class="font-mono text-xs text-slate-500">{{ student.nis }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-4">
                                        <div v-for="(bills, category) in groupBillsByCategory(student.items)" :key="category" class="border rounded-lg p-3 shadow-sm transition-colors" :class="getCardClass(student.status_anak)">
                                            <div class="flex justify-between items-center mb-2 border-b border-black/5 pb-1">
                                                <h4 class="font-bold text-xs uppercase tracking-wide" :class="student.status_anak ? 'text-slate-800' : 'text-indigo-700'">{{ category }}</h4>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" 
                                                      :class="bills.every(b => b.status === 'PAID') ? 'bg-green-100 text-green-600' : 'bg-white/50 text-slate-600 border border-slate-200'">
                                                    {{ bills.every(b => b.status === 'PAID') ? 'LUNAS' : 'BELUM LUNAS' }}
                                                </span>
                                            </div>
                                            <div v-if="bills[0].payment_period === 'MONTHLY'" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                                <div v-for="bill in bills" :key="bill.bill_name" 
                                                     class="text-center p-1.5 rounded border text-[10px]"
                                                     :class="bill.status === 'PAID' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'">
                                                    <div class="font-bold truncate" :title="bill.bill_name">{{ getMonthName(bill.bill_name) }}</div>
                                                    <div v-if="bill.status !== 'PAID'" class="mt-0.5 font-mono">{{ formatMoneyShort(bill.amount - bill.amount_paid) }}</div>
                                                </div>
                                            </div>
                                            <div v-else class="space-y-1">
                                                <div v-for="bill in bills" :key="bill.bill_name" class="flex justify-between text-xs">
                                                    <span class="text-slate-600">{{ bill.bill_name }}</span>
                                                    <div class="flex gap-2">
                                                        <span class="font-mono" :class="bill.status === 'PAID' ? 'text-green-600 line-through decoration-1 opacity-50' : 'text-slate-800'">
                                                            {{ formatMoney(bill.amount) }}
                                                        </span>
                                                        <span v-if="bill.status !== 'PAID'" class="font-mono text-red-600 font-bold">
                                                            (Kurang: {{ formatMoney(bill.amount - bill.amount_paid) }})
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right align-top">
                                    <div class="font-bold text-red-600 text-lg font-mono">{{ formatMoney(student.total_bill - student.total_paid) }}</div>
                                    <div class="text-xs text-slate-400">Total Tagihan: {{ formatMoney(student.total_bill) }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="searched" class="text-center py-12 text-slate-400">
                <i class="fas fa-search-minus text-4xl mb-3 opacity-20"></i>
                <p>Data tidak ditemukan.</p>
            </div>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                baseUrl: (window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/')),
                years: [],
                units: [],
                paymentTypes: [],
                classes: [],
                filter: {
                    academic_year_id: '',
                    unit_id: '',
                    class_id: '',
                    payment_type_id: '',
                    asrama_only: false
                },
                reportData: [],
                searched: false
            }
        },
        computed: {
            canSearch() {
                return this.filter.class_id && this.filter.payment_type_id;
            },
            groupedData() {
                const map = new Map();
                this.reportData.forEach(item => {
                    if (!map.has(item.identity_number)) {
                        map.set(item.identity_number, {
                            nis: item.identity_number,
                            name: item.student_name,
                            total_bill: 0,
                            total_paid: 0,
                            status_anak: item.status_anak,
                            asrama_status: item.asrama_status,
                            items: []
                        });
                    }
                    const student = map.get(item.identity_number);
                    if (!student.status_anak && item.status_anak) {
                         student.status_anak = item.status_anak;
                    }
                    if (!student.asrama_status && item.asrama_status) {
                         student.asrama_status = item.asrama_status;
                    }
                    student.total_bill += Number(item.amount);
                    student.total_paid += Number(item.amount_paid);
                    student.items.push(item);
                });
                let result = Array.from(map.values());
                if (this.filter.asrama_only) {
                    result = result.filter(s => this.isAsramaActive(s.asrama_status));
                }
                return result;
            },
            grandTotal() {
                return this.groupedData.reduce((acc, row) => {
                    acc.bill += row.total_bill;
                    acc.paid += row.total_paid;
                    return acc;
                }, { bill: 0, paid: 0 });
            }
        },
        methods: {
            async fetchInit() {
                try {
                    const res = await fetch(this.baseUrl + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.years = data.data.years;
                        this.units = data.data.units;
                        this.paymentTypes = data.data.paymentTypes;
                    }
                } catch (e) {}
            },
            async fetchClasses() {
                if (!this.filter.academic_year_id || !this.filter.unit_id) {
                    this.classes = [];
                    return;
                }
                try {
                    const res = await fetch(this.baseUrl + `api/finance.php?action=get_classes&academic_year_id=${this.filter.academic_year_id}&unit_id=${this.filter.unit_id}`);
                    const data = await res.json();
                    if (data.success) {
                        this.classes = data.data;
                    } else {
                        console.error("API Error:", data.message);
                    }
                } catch (e) {}
            },
            async fetchReport() {
                this.reportData = [];
                this.searched = true;
                try {
                    const params = new URLSearchParams({
                        action: this.filter.payment_type_id === 'ALL' ? 'get_class_report_all' : 'get_class_report_single',
                        academic_year_id: this.filter.academic_year_id,
                        unit_id: this.filter.unit_id,
                        class_id: this.filter.class_id,
                        payment_type_id: this.filter.payment_type_id,
                        asrama_only: this.filter.asrama_only ? '1' : '0'
                    });
                    const res = await fetch(this.baseUrl + `api/finance.php?${params.toString()}`);
                    const data = await res.json();
                    if (data.success) {
                        this.reportData = data.data;
                    }
                } catch (e) {}
            },
            groupBillsByCategory(items) {
                const groups = {};
                items.forEach(item => {
                    const cat = item.type_name || 'Lainnya';
                    if (!groups[cat]) groups[cat] = [];
                    groups[cat].push(item);
                });
                return groups;
            },
            getMonthName(billName) {
                const parts = billName.split(' - ');
                if (parts.length > 1) return parts[1];
                return billName;
            },
            getCardClass(status) {
                if (!status) return 'bg-white';
                const s = String(status).toLowerCase();
                if (s.includes('pegawai') || s.includes('staf') || s.includes('staff')) return 'bg-blue-100';
                if (s.includes('guru')) return 'bg-green-100';
                if (s.includes('yayasan')) return 'bg-purple-100';
                return 'bg-white';
            },
            isAsramaActive(val) {
                if (!val) return false;
                const v = String(val).toLowerCase();
                return (v === '1' || v === 'ya' || v === 'aktif' || v === 'y');
            },
            getBadgeClass(status) {
                const s = String(status || '').toLowerCase();
                if (s.includes('pegawai') || s.includes('staf') || s.includes('staff')) return 'badge-status badge-pegawai';
                if (s.includes('guru')) return 'badge-status badge-guru';
                if (s.includes('yayasan')) return 'badge-status badge-yayasan';
                return 'badge-status';
            },
            formatMoney(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
            },
            formatMoneyShort(num) {
                 if (num >= 1000000) return (num / 1000000).toFixed(1) + 'jt';
                 if (num >= 1000) return (num / 1000).toFixed(0) + 'k';
                 return num;
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
