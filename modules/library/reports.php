<?php
require_once '../../includes/guard.php';
require_login_and_module('library');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Sirkulasi - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
    <style>
        [v-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .animate-fade { animation: fade 0.3s ease-out; }
        @keyframes fade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
<div id="app" v-cloak class="flex flex-col h-screen">

    <?php require_once '../../includes/library_header.php'; ?>

    <main class="flex-1 p-6 overflow-y-auto">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Sirkulasi & Laporan</h2>
                    <p class="text-slate-500 text-sm">Peminjaman, pengembalian, dan denda pustaka.</p>
                </div>
                <div class="flex bg-slate-100 rounded-lg p-1 border border-slate-200">
                    <button @click="tab = 'loans'" :class="tab === 'loans' ? 'bg-white shadow text-red-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Sirkulasi</button>
                    <button @click="tab = 'fines'" :class="tab === 'fines' ? 'bg-white shadow text-red-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Denda</button>
                    <button @click="tab = 'stats'" :class="tab === 'stats' ? 'bg-white shadow text-red-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Statistik</button>
                </div>
            </div>
            <div v-if="tab === 'loans'" class="animate-fade">
                <!-- Inline Form for New Loan -->
                <div v-if="showLoanForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Transaksi Peminjaman Baru</h3>
                        <button @click="showLoanForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cari Anggota</label>
                            <div class="relative">
                                <input type="text" v-model="memberSearch" @input="searchMembers" placeholder="Nama / Kode..." 
                                       class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <div v-if="memberResults.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                    <div v-for="m in memberResults" :key="m.id" @click="selectMember(m)" class="p-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-50 last:border-0">
                                        <div class="text-xs font-bold text-slate-700">{{ m.name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ m.member_code }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="selectedMember" class="mt-2 p-2 bg-emerald-50 rounded-lg border border-emerald-100 flex justify-between items-center">
                                <div class="text-[10px] font-bold text-emerald-700">{{ selectedMember.name }}</div>
                                <button @click="selectedMember = null" class="text-slate-300 hover:text-red-500"><i class="fas fa-times-circle"></i></button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cari Buku</label>
                            <div class="relative">
                                <input type="text" v-model="bookSearch" @input="searchBooks" placeholder="Judul / Barcode..." 
                                       class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <div v-if="bookResults.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                    <div v-for="b in bookResults" :key="b.id" @click="selectBook(b)" class="p-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-50 last:border-0">
                                        <div class="text-xs font-bold text-slate-700">{{ b.title }}</div>
                                        <div class="text-[10px] text-slate-400">Tersedia: {{ b.available_stock }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="selectedBook" class="mt-2 p-2 bg-blue-50 rounded-lg border border-blue-100 flex justify-between items-center">
                                <div class="text-[10px] font-bold text-blue-700 truncate w-40">{{ selectedBook.title }}</div>
                                <button @click="selectedBook = null" class="text-slate-300 hover:text-red-500"><i class="fas fa-times-circle"></i></button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pinjam</label>
                                <input type="date" v-model="loanForm.loan_date" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tempo</label>
                                <input type="date" v-model="loanForm.due_date" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm outline-none font-bold text-red-600">
                            </div>
                        </div>

                        <div class="flex items-end gap-2">
                            <button @click="showLoanForm = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                            <button @click="saveLoan" :disabled="!selectedMember || !selectedBook" 
                                    class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-100 hover:bg-emerald-700 disabled:bg-slate-200 disabled:shadow-none transition-all">
                                Pinjamkan
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end items-center mb-6">
                    <button @click="showLoanForm = true" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all">
                        <i class="fas fa-plus mr-2"></i> Pinjam Buku
                    </button>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Buku</th>
                                <th class="px-6 py-4">Peminjam</th>
                                <th class="px-6 py-4">Tgl Pinjam</th>
                                <th class="px-6 py-4">Jatuh Tempo</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="l in loans" :key="l.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700">{{ l.book_title }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ l.book_barcode }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-600">{{ l.member_name }}</td>
                                <td class="px-6 py-4 text-xs">{{ formatDate(l.loan_date) }}</td>
                                <td class="px-6 py-4 text-xs" :class="isOverdue(l) ? 'text-red-600 font-bold' : ''">
                                    {{ formatDate(l.due_date) }}
                                    <div v-if="isOverdue(l) && l.status === 'BORROWED'" class="text-[10px] uppercase">Terlambat!</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold"
                                          :class="getStatusClass(l.status)">
                                        {{ l.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button v-if="l.status === 'BORROWED'" @click="returnBook(l)" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white px-3 py-1 rounded-lg text-[10px] font-bold border border-emerald-100 transition-all">
                                        Kembalikan
                                    </button>
                                    <span v-else class="text-slate-400 text-[10px] italic">Selesai</span>
                                </td>
                            </tr>
                            <tr v-if="loans.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada riwayat peminjaman.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: FINES (DENDA) -->
            <div v-if="tab === 'fines'" class="animate-fade">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Peminjam</th>
                                <th class="px-6 py-4">Buku</th>
                                <th class="px-6 py-4">Tgl Kembali</th>
                                <th class="px-6 py-4">Denda</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="f in fines" :key="f.id">
                                <td class="px-6 py-4 font-bold text-slate-700">{{ f.member_name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ f.book_title }}</td>
                                <td class="px-6 py-4 text-xs">{{ f.return_date ? formatDate(f.return_date) : '-' }}</td>
                                <td class="px-6 py-4 font-bold text-red-600">Rp {{ formatNumber(f.fine_amount) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700">TERTUNGGAK</span>
                                </td>
                            </tr>
                            <tr v-if="fines.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Tidak ada denda yang tercatat.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: STATS -->
            <div v-if="tab === 'stats'" class="animate-fade">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Peminjaman</div>
                        <div class="text-3xl font-black text-slate-800">{{ loans.length }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Buku Terpinjam</div>
                        <div class="text-3xl font-black text-emerald-600">{{ loans.filter(l => l.status === 'BORROWED').length }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Denda</div>
                        <div class="text-3xl font-black text-red-600">Rp {{ formatNumber(totalFines) }}</div>
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
                tab: 'loans',
                loans: [],
                fines: [],
                showLoanForm: false,
                memberSearch: '',
                memberResults: [],
                selectedMember: null,
                bookSearch: '',
                bookResults: [],
                selectedBook: null,
                loanForm: {
                    loan_date: new Date().toISOString().substr(0, 10),
                    due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().substr(0, 10) // +7 days
                }
            }
        },
        computed: {
            totalFines() {
                return this.fines.reduce((sum, f) => sum + parseFloat(f.fine_amount), 0);
            }
        },
        methods: {
            async fetchData() {
                const resLoans = await fetch('../../api/library.php?action=get_loans');
                const dataLoans = await resLoans.json();
                if (dataLoans.success) this.loans = dataLoans.data;

                const resFines = await fetch('../../api/library.php?action=get_fines');
                const dataFines = await resFines.json();
                if (dataFines.success) this.fines = dataFines.data;
            },
            async searchMembers() {
                if (this.memberSearch.length < 2) { this.memberResults = []; return; }
                const res = await fetch(`../../api/library.php?action=get_members`);
                const data = await res.json();
                if (data.success) {
                    this.memberResults = data.data.filter(m => 
                        m.name.toLowerCase().includes(this.memberSearch.toLowerCase()) || 
                        m.member_code.toLowerCase().includes(this.memberSearch.toLowerCase())
                    );
                }
            },
            selectMember(m) { this.selectedMember = m; this.memberResults = []; this.memberSearch = ''; },
            async searchBooks() {
                if (this.bookSearch.length < 2) { this.bookResults = []; return; }
                const res = await fetch(`../../api/library.php?action=get_books`);
                const data = await res.json();
                if (data.success) {
                    this.bookResults = data.data.filter(b => 
                        b.title.toLowerCase().includes(this.bookSearch.toLowerCase()) || 
                        b.barcode.toLowerCase().includes(this.bookSearch.toLowerCase())
                    ).filter(b => b.available_stock > 0);
                }
            },
            selectBook(b) { this.selectedBook = b; this.bookResults = []; this.bookSearch = ''; },
            async saveLoan() {
                const res = await fetch('../../api/library.php?action=save_loan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        member_id: this.selectedMember.id,
                        book_id: this.selectedBook.id,
                        loan_date: this.loanForm.loan_date,
                        due_date: this.loanForm.due_date
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showLoanForm = false;
                    this.selectedMember = null;
                    this.selectedBook = null;
                    this.fetchData();
                } else {
                    alert(data.error);
                }
            },
            async returnBook(loan) {
                const returnDate = new Date().toISOString().substr(0, 10);
                const dueDate = new Date(loan.due_date);
                const today = new Date();
                let fine = 0;
                
                if (today > dueDate) {
                    const diffTime = Math.abs(today - dueDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    fine = diffDays * 1000; // 1000 per day
                }

                if (!confirm(`Kembalikan buku ini?${fine > 0 ? '\nDenda keterlambatan: Rp ' + fine : ''}`)) return;

                const res = await fetch('../../api/library.php?action=return_book', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: loan.id, return_date: returnDate, fine_amount: fine })
                });
                if ((await res.json()).success) this.fetchData();
            },
            isOverdue(l) { return new Date() > new Date(l.due_date); },
            getStatusClass(status) {
                switch(status) {
                    case 'BORROWED': return 'bg-blue-100 text-blue-700';
                    case 'RETURNED': return 'bg-emerald-100 text-emerald-700';
                    case 'OVERDUE': return 'bg-red-100 text-red-700';
                    default: return 'bg-slate-100 text-slate-500';
                }
            },
            formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); },
            formatNumber(n) { return parseFloat(n).toLocaleString('id-ID'); }
        },
        mounted() {
            this.fetchData();
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'stats') this.tab = 'stats';
            if (urlParams.get('action') === 'fines') this.tab = 'fines';
        }
    }).mount('#app')
</script>
</body>
</html>
