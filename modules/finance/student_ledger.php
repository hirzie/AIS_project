<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<style>
    .bg-blue-100 { background-color: #dbeafe !important; }
    .bg-green-100 { background-color: #dcfce7 !important; }
    .bg-purple-100 { background-color: #f3e8ff !important; }
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
</style>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-book-reader text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Buku Besar Siswa</h1>
                <span class="text-xs text-slate-500 font-medium">Riwayat Tagihan & Pembayaran Lintas Tahun</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden flex flex-col p-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex gap-4 items-center z-10">
            <div class="relative flex-1 max-w-xl">
                <input type="text" v-model="searchQuery" @input="searchStudents" placeholder="Cari Nama Siswa atau NIS..." class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                <i class="fas fa-search absolute left-3 top-3.5 text-slate-400"></i>
                
                <div v-if="searchResults.length > 0" class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-xl border border-slate-100 mt-2 z-50 max-h-80 overflow-y-auto">
                    <div v-for="s in searchResults" :key="s.id" @click="selectStudent(s)" class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-slate-50 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold">
                            {{ s.name.substring(0,1) }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-700">{{ s.name }}</div>
                            <div class="text-xs text-slate-500">{{ s.identity_number }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-if="selectedStudent" class="flex items-center gap-4 bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 animate-fade">
                <div class="w-10 h-10 rounded-full bg-white border-2 border-indigo-200 overflow-hidden">
                     <img :src="'https://ui-avatars.com/api/?name=' + selectedStudent.name + '&background=random'" class="w-full h-full object-cover">
                </div>
                <div>
                    <div class="font-bold text-indigo-900">{{ selectedStudent.name }}</div>
                    <div class="text-xs text-indigo-600">{{ selectedStudent.identity_number }}</div>
                </div>
                <button @click="resetStudent" class="ml-2 text-indigo-400 hover:text-red-500"><i class="fas fa-times-circle"></i></button>
            </div>
        </div>

        <div v-if="selectedStudent" class="flex-1 overflow-y-auto space-y-8 pr-2">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Total Tagihan</div>
                    <div class="text-2xl font-bold text-slate-800">Rp {{ formatNumber(summary.total_bill) }}</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Total Terbayar</div>
                    <div class="text-2xl font-bold text-green-600">Rp {{ formatNumber(summary.total_paid) }}</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Sisa Tunggakan</div>
                    <div class="text-2xl font-bold text-red-600">Rp {{ formatNumber(summary.total_arrears) }}</div>
                </div>
            </div>

            <div v-for="(yearData, yearName) in groupedBills" :key="yearName" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-calendar text-indigo-500"></i>
                        Tahun Ajaran {{ yearName || 'Lainnya' }}
                    </h3>
                    <div class="flex gap-4 text-sm">
                        <span class="text-slate-500">Tagihan: <span class="font-bold text-slate-700">Rp {{ formatNumber(yearData.total) }}</span></span>
                        <span class="text-slate-500">Sisa: <span class="font-bold text-red-600">Rp {{ formatNumber(yearData.arrears) }}</span></span>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="(bills, category) in groupBillsByCategory(yearData.items)" :key="category" class="border rounded-lg p-4 shadow-sm transition-colors bg-white border-slate-100 hover:border-indigo-100 hover:shadow-md">
                            <div class="flex justify-between items-center mb-3 border-b border-slate-50 pb-2">
                                <h4 class="font-bold text-sm uppercase tracking-wide text-indigo-700">{{ category }}</h4>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" 
                                      :class="bills.every(b => b.status === 'PAID') ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500 border border-slate-200'">
                                    {{ bills.every(b => b.status === 'PAID') ? 'LUNAS' : 'BELUM LUNAS' }}
                                </span>
                            </div>
                            
                            <div v-if="bills[0].payment_type === 'MONTHLY'" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                <div v-for="bill in bills" :key="bill.id" 
                                     class="text-center p-2 rounded border text-xs transition-all"
                                     :class="bill.status === 'PAID' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100'">
                                    <div class="font-bold truncate mb-1" :title="bill.bill_name">{{ getMonthName(bill.bill_name) }}</div>
                                    <div class="font-mono text-[10px] opacity-75">
                                        {{ bill.status === 'PAID' ? 'LUNAS' : formatMoneyShort(bill.amount - bill.amount_paid) }}
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="space-y-2">
                                <div v-for="bill in bills" :key="bill.id" class="flex justify-between items-center text-sm p-2 hover:bg-slate-50 rounded">
                                    <span class="text-slate-700 font-medium">{{ bill.bill_name }}</span>
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono" :class="bill.status === 'PAID' ? 'text-green-600 line-through decoration-1 opacity-50' : 'text-slate-800'">
                                            {{ formatNumber(bill.amount) }}
                                        </span>
                                        <span v-if="bill.status !== 'PAID'" class="font-mono text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded text-xs">
                                            Kurang: {{ formatNumber(bill.amount - bill.amount_paid) }}
                                        </span>
                                        <span v-else class="text-green-600 text-xs font-bold bg-green-50 px-2 py-0.5 rounded">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="Object.keys(groupedBills).length === 0" class="text-center py-12 text-slate-400 italic">
                Tidak ada data tagihan untuk siswa ini.
            </div>
        </div>
        
        <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-300">
            <i class="fas fa-search text-6xl mb-4 opacity-50"></i>
            <p class="font-bold text-lg">Cari siswa untuk melihat buku besar.</p>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                searchQuery: '',
                searchResults: [],
                selectedStudent: null,
                bills: [],
            }
        },
        computed: {
            groupedBills() {
                const groups = {};
                this.bills.forEach(bill => {
                    const year = bill.academic_year_name || 'UNDEFINED';
                    if (!groups[year]) {
                        groups[year] = { items: [], total: 0, paid: 0, arrears: 0 };
                    }
                    groups[year].items.push(bill);
                    groups[year].total += Number(bill.amount);
                    groups[year].paid += Number(bill.amount_paid);
                    groups[year].arrears += (Number(bill.amount) - Number(bill.amount_paid));
                });
                return groups;
            },
            summary() {
                let tBill = 0, tPaid = 0;
                this.bills.forEach(b => {
                    tBill += Number(b.amount);
                    tPaid += Number(b.amount_paid);
                });
                return {
                    total_bill: tBill,
                    total_paid: tPaid,
                    total_arrears: tBill - tPaid
                }
            }
        },
        methods: {
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            },
            formatMoneyShort(num) {
                 if (num >= 1000000) return (num / 1000000).toFixed(1) + 'jt';
                 if (num >= 1000) return (num / 1000).toFixed(0) + 'k';
                 return num;
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
            async searchStudents() {
                if (!this.searchQuery || this.searchQuery.length < 3) {
                    this.searchResults = [];
                    return;
                }
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=search_students&q=${encodeURIComponent(this.searchQuery)}`);
                    const data = await res.json();
                    this.searchResults = data.data;
                } catch (e) {}
            },
            async selectStudent(s) {
                this.selectedStudent = s;
                this.searchQuery = '';
                this.searchResults = [];
                this.fetchBills(s.id);
            },
            resetStudent() {
                this.selectedStudent = null;
                this.bills = [];
            },
            async fetchBills(studentId) {
                try {
                    const res = await fetch(`${window.BASE_URL}api/finance.php?action=get_student_bills&student_id=${studentId}`);
                    const data = await res.json();
                    if (data.success) {
                        this.bills = data.data.bills;
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
