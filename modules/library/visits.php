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
    <title>Kunjungan Perpustakaan - SekolahOS</title>
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

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- VIEW: VISIT LIST -->
            <div v-if="view === 'list'" class="animate-fade">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-800">Riwayat Kunjungan Kelas</h2>
                    <div class="flex gap-2">
                        <a href="schedule.php" class="bg-white text-slate-600 border border-slate-200 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all">
                            <i class="fas fa-calendar-alt mr-2"></i> Jadwal Mingguan
                        </a>
                        <button @click="openVisitForm()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all">
                            <i class="fas fa-plus mr-2"></i> Kunjungan Baru
                        </button>
                    </div>
                </div>

                <!-- Inline Form for Visit -->
                <div v-if="showVisitForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">{{ visitForm.id ? 'Edit' : 'Buat' }} Kunjungan Baru</h3>
                        <button @click="showVisitForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="saveVisit" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pilih Kelas</label>
                            <select v-model="visitForm.class_id" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                                <option value="">-- Pilih Kelas --</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tanggal</label>
                            <input v-model="visitForm.visit_date" type="date" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jam Mulai</label>
                            <input v-model="visitForm.start_time" type="time" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jam Selesai</label>
                            <input v-model="visitForm.end_time" type="time" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Keterangan</label>
                            <input v-model="visitForm.remarks" type="text" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Opsional...">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="button" @click="showVisitForm = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                            <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Simpan</button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Tanggal & Waktu</th>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4">Keterangan</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="v in visits" :key="v.id" class="hover:bg-slate-50 transition-colors cursor-pointer" @click="selectVisit(v)">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700">{{ formatDate(v.visit_date) }}</div>
                                    <div v-if="v.start_time" class="text-[10px] text-slate-400 font-mono">
                                        <i class="far fa-clock mr-1"></i> {{ formatTime(v.start_time) }} - {{ formatTime(v.end_time) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-[10px] font-bold border border-blue-100 uppercase">
                                        {{ v.class_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 italic text-xs">{{ v.remarks || '-' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <button @click.stop="openVisitForm(v)" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs mr-3">Edit</button>
                                    <button class="text-emerald-600 hover:text-emerald-800 font-bold text-xs">Pilih <i class="fas fa-chevron-right ml-1"></i></button>
                                </td>
                            </tr>
                            <tr v-if="visits.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data kunjungan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: LOG BACA (DETAILS) -->
            <div v-if="view === 'details' && selectedVisit" class="animate-fade">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- LEFT: INPUT AREA -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-emerald-600"></i> Input Log Baca
                                </h3>
                                <div class="flex bg-slate-100 rounded-lg p-1">
                                    <button @click="inputMode = 'barcode'" :class="inputMode === 'barcode' ? 'bg-white shadow-sm text-emerald-600' : 'text-slate-400'" class="px-2 py-1 text-[10px] font-bold rounded-md transition-all">BARCODE</button>
                                    <button @click="inputMode = 'manual'" :class="inputMode === 'manual' ? 'bg-white shadow-sm text-emerald-600' : 'text-slate-400'" class="px-2 py-1 text-[10px] font-bold rounded-md transition-all">MANUAL</button>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Student Selection -->
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Siswa</label>
                                    <!-- Barcode Mode -->
                                    <div v-if="inputMode === 'barcode'" class="relative">
                                        <input type="text" ref="studentInput" v-model="scan.student_barcode" @keyup.enter="scanStudent" placeholder="Scan NIS siswa..." 
                                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                                        <i v-if="scan.student_id" class="fas fa-check-circle absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                                    </div>
                                    <!-- Manual Mode -->
                                    <div v-else class="relative">
                                        <input type="text" v-model="manualSearch.student" @input="searchStudents" placeholder="Cari nama/NIS..." 
                                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                        <div v-if="searchResults.students.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                            <div v-for="s in searchResults.students" :key="s.id" @click="selectManualStudent(s)" class="p-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-50 last:border-0">
                                                <div class="text-xs font-bold text-slate-700">{{ s.name }}</div>
                                                <div class="text-[10px] text-slate-400">{{ s.identity_number }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Selected Student Info -->
                                    <div v-if="scan.student_name" class="mt-2 p-3 bg-emerald-50 rounded-xl border border-emerald-100 flex justify-between items-center">
                                        <div>
                                            <div class="text-xs font-bold text-emerald-700">{{ scan.student_name }}</div>
                                            <div class="text-[10px] text-emerald-500">{{ scan.student_nis }}</div>
                                        </div>
                                        <button @click="clearStudent" class="text-slate-300 hover:text-red-500"><i class="fas fa-times-circle"></i></button>
                                    </div>
                                </div>

                                <!-- Book Selection -->
                                <div :class="{'opacity-50 pointer-events-none': !scan.student_id}">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Buku yang Dibaca</label>
                                    <!-- Barcode Mode -->
                                    <div v-if="inputMode === 'barcode'" class="relative">
                                        <input type="text" ref="bookInput" v-model="scan.book_barcode" @keyup.enter="scanBook" placeholder="Scan barcode buku..." 
                                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                                        <i v-if="scan.book_id" class="fas fa-check-circle absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                                    </div>
                                    <!-- Manual Mode -->
                                    <div v-else class="relative">
                                        <input type="text" v-model="manualSearch.book" @input="searchBooks" placeholder="Cari judul/barcode..." 
                                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                        <div v-if="searchResults.books.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                            <div v-for="b in searchResults.books" :key="b.id" @click="selectManualBook(b)" class="p-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-50 last:border-0">
                                                <div class="text-xs font-bold text-slate-700">{{ b.title }}</div>
                                                <div class="text-[10px] text-slate-400">{{ b.author }} | {{ b.barcode }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Selected Book Info -->
                                    <div v-if="scan.book_title" class="mt-2 p-3 bg-blue-50 rounded-xl border border-blue-100 flex justify-between items-center">
                                        <div>
                                            <div class="text-xs font-bold text-blue-700">{{ scan.book_title }}</div>
                                            <div class="text-[10px] text-blue-500">{{ scan.book_author }}</div>
                                        </div>
                                        <button @click="clearBook" class="text-slate-300 hover:text-red-500"><i class="fas fa-times-circle"></i></button>
                                    </div>
                                </div>

                                <button @click="submitLog" :disabled="!scan.student_id || !scan.book_id" 
                                        class="w-full py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-100 hover:bg-emerald-700 disabled:bg-slate-200 disabled:shadow-none transition-all">
                                    Simpan Log Baca
                                </button>
                                <button @click="resetScan" class="w-full text-xs font-bold text-slate-400 hover:text-slate-600 py-2">Reset Form</button>
                            </div>
                        </div>

                        <!-- Info Kunjungan -->
                        <div class="bg-slate-800 rounded-2xl p-6 text-white shadow-xl">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Info Kunjungan</div>
                            <div class="text-xl font-bold mb-2">{{ selectedVisit.class_name }}</div>
                            <div class="flex items-center gap-2 text-xs text-slate-400 mb-4">
                                <i class="fas fa-calendar"></i> {{ formatDate(selectedVisit.visit_date) }}
                                <i v-if="selectedVisit.start_time" class="far fa-clock ml-2"></i> {{ formatTime(selectedVisit.start_time) }} - {{ formatTime(selectedVisit.end_time) }}
                            </div>
                            <div class="p-3 bg-slate-700/50 rounded-xl border border-slate-600/50">
                                <div class="text-[10px] text-slate-400 mb-1">Total Bacaan</div>
                                <div class="text-2xl font-bold">{{ readingLogs.length }} <span class="text-xs font-normal text-slate-500 italic">buku</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: LOG TABLE -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden h-full flex flex-col">
                            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                                <h3 class="font-bold text-slate-800">Daftar Siswa & Buku Dibaca</h3>
                                <div class="text-xs text-slate-400">Paling baru di atas</div>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] sticky top-0 z-10">
                                        <tr>
                                            <th class="px-6 py-4">Waktu</th>
                                            <th class="px-6 py-4">Siswa</th>
                                            <th class="px-6 py-4">Buku yang Dibaca</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="log in readingLogs" :key="log.id" class="animate-fade">
                                            <td class="px-6 py-4 font-mono text-[10px] text-slate-400">{{ formatDateTime(log.read_at) }}</td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-slate-700">{{ log.student_name }}</div>
                                                <div class="text-[10px] text-slate-400">{{ log.nis }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-book text-emerald-500 text-xs"></i>
                                                    <span class="font-medium text-slate-600">{{ log.book_title }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="readingLogs.length === 0">
                                            <td colspan="3" class="px-6 py-24 text-center text-slate-300 italic">Belum ada log bacaan untuk kunjungan ini.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
                view: 'list',
                inputMode: 'barcode',
                days: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                visits: [],
                classes: [],
                schedules: [],
                readingLogs: [],
                selectedVisit: null,
                showVisitForm: false,
                visitForm: { id: null, class_id: '', visit_date: new Date().toISOString().substr(0, 10), start_time: '08:00', end_time: '09:00', remarks: '' },
                scan: { student_barcode: '', student_id: null, student_name: '', student_nis: '', book_barcode: '', book_id: null, book_title: '', book_author: '' },
                manualSearch: { student: '', book: '' },
                searchResults: { students: [], books: [] }
            }
        },
        methods: {
            async fetchVisits() {
                const res = await fetch('../../api/library.php?action=get_visits');
                const data = await res.json();
                if (data.success) this.visits = data.data;
            },
            async fetchClasses() {
                const res = await fetch('../../api/library.php?action=get_classes');
                const data = await res.json();
                if (data.success) this.classes = data.data;
            },
            async fetchLogs() {
                if (!this.selectedVisit) return;
                const res = await fetch(`../../api/library.php?action=get_reading_logs&visit_id=${this.selectedVisit.id}`);
                const data = await res.json();
                if (data.success) this.readingLogs = data.data;
            },
            selectVisit(visit) {
                this.selectedVisit = visit;
                this.view = 'details';
                this.fetchLogs();
                this.$nextTick(() => { if(this.inputMode === 'barcode') this.$refs.studentInput?.focus(); });
            },
            openVisitForm(v = null) {
                if (v) this.visitForm = { ...v };
                else this.visitForm = { id: null, class_id: '', visit_date: new Date().toISOString().substr(0, 10), start_time: '08:00', end_time: '09:00', remarks: '' };
                this.showVisitForm = true;
                this.$nextTick(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
            },
            async saveVisit() {
                const res = await fetch('../../api/library.php?action=save_visit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.visitForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.showVisitForm = false;
                    this.fetchVisits();
                }
            },
            // MANUAL SEARCH
            async searchStudents() {
                if (this.manualSearch.student.length < 2) { this.searchResults.students = []; return; }
                const res = await fetch(`../../api/library.php?action=search_students&q=${this.manualSearch.student}&class_id=${this.selectedVisit.class_id}`);
                const data = await res.json();
                if (data.success) this.searchResults.students = data.data;
            },
            selectManualStudent(s) {
                this.scan.student_id = s.id;
                this.scan.student_name = s.name;
                this.scan.student_nis = s.identity_number;
                this.manualSearch.student = '';
                this.searchResults.students = [];
            },
            async searchBooks() {
                if (this.manualSearch.book.length < 2) { this.searchResults.books = []; return; }
                const res = await fetch(`../../api/library.php?action=search_books&q=${this.manualSearch.book}`);
                const data = await res.json();
                if (data.success) this.searchResults.books = data.data;
            },
            selectManualBook(b) {
                this.scan.book_id = b.id;
                this.scan.book_title = b.title;
                this.scan.book_author = b.author;
                this.manualSearch.book = '';
                this.searchResults.books = [];
            },
            // BARCODE SCAN
            async scanStudent() {
                if (!this.scan.student_barcode) return;
                const res = await fetch(`../../api/library.php?action=get_student_by_barcode&barcode=${this.scan.student_barcode}&class_id=${this.selectedVisit.class_id}`);
                const data = await res.json();
                if (data.success) {
                    this.scan.student_id = data.data.id;
                    this.scan.student_name = data.data.name;
                    this.scan.student_nis = data.data.identity_number;
                    this.$nextTick(() => this.$refs.bookInput?.focus());
                } else {
                    alert('Siswa tidak ditemukan!');
                    this.scan.student_barcode = '';
                }
            },
            async scanBook() {
                if (!this.scan.book_barcode) return;
                const res = await fetch(`../../api/library.php?action=get_book_by_barcode&barcode=${this.scan.book_barcode}`);
                const data = await res.json();
                if (data.success) {
                    this.scan.book_id = data.data.id;
                    this.scan.book_title = data.data.title;
                    this.scan.book_author = data.data.author;
                    this.submitLog();
                } else {
                    alert('Buku tidak ditemukan!');
                    this.scan.book_barcode = '';
                }
            },
            async submitLog() {
                if (!this.scan.student_id || !this.scan.book_id) return;
                const res = await fetch('../../api/library.php?action=save_reading_log', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ visit_id: this.selectedVisit.id, student_id: this.scan.student_id, book_id: this.scan.book_id })
                });
                if ((await res.json()).success) {
                    this.fetchLogs();
                    this.resetScan();
                }
            },
            clearStudent() { this.scan.student_id = null; this.scan.student_name = ''; this.scan.student_nis = ''; },
            clearBook() { this.scan.book_id = null; this.scan.book_title = ''; this.scan.book_author = ''; },
            resetScan() {
                this.scan = { student_barcode: '', student_id: null, student_name: '', student_nis: '', book_barcode: '', book_id: null, book_title: '', book_author: '' };
                this.manualSearch = { student: '', book: '' };
                this.$nextTick(() => { if(this.inputMode === 'barcode') this.$refs.studentInput?.focus(); });
            },
            formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); },
            formatTime(t) { return t ? t.substring(0, 5) : ''; },
            formatDateTime(dt) { const date = new Date(dt); return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0'); }
        },
        mounted() {
            this.fetchVisits();
            this.fetchClasses();
        }
    }).mount('#app')
</script>
</body>
</html>
