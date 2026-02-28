<?php
require_once '../../includes/guard.php';
require_login_and_module('boarding');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kedisiplinan & Prestasi - SekolahOS</title>
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

    <?php require_once '../../includes/header_boarding.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Kedisiplinan & Prestasi</h2>
                    <p class="text-slate-500 text-sm">Pantau poin pelanggaran dan prestasi santri asrama</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showInputModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-bold text-sm shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                        <i class="fas fa-plus"></i> Catat Poin Baru
                    </button>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Ringkasan Poin Santri</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Nama Santri</th>
                            <th class="px-6 py-4">Kelas</th>
                            <th class="px-6 py-4 text-center">Poin Pelanggaran</th>
                            <th class="px-6 py-4 text-center">Poin Prestasi</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in summary" :key="s.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                        {{ s.name ? s.name.charAt(0) : '?' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-700">{{ s.name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ s.nis }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500">{{ s.class_name || '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span :class="{'text-red-600 font-bold': s.total_violations > 0, 'text-slate-300': !s.total_violations || s.total_violations == 0}" class="text-base">
                                    {{ s.total_violations || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="{'text-emerald-600 font-bold': s.total_achievements > 0, 'text-slate-300': !s.total_achievements || s.total_achievements == 0}" class="text-base">
                                    {{ s.total_achievements || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="viewDetail(s)" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">
                                    Lihat Riwayat
                                </button>
                            </td>
                        </tr>
                        <tr v-if="summary.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <i class="fas fa-users-slash text-4xl mb-3 opacity-20"></i>
                                <p>Belum ada data santri.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Recent Records -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Catatan Terbaru</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Nama Santri</th>
                            <th class="px-6 py-4">Kategori</th>
                            <th class="px-6 py-4">Poin</th>
                            <th class="px-6 py-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="r in records" :key="r.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-500 text-xs">{{ formatDate(r.record_date) }}</td>
                            <td class="px-6 py-4 font-bold text-slate-700">{{ r.student_name }}</td>
                            <td class="px-6 py-4">
                                <span :class="{'bg-red-50 text-red-700 border-red-100': r.type === 'VIOLATION', 'bg-emerald-50 text-emerald-700 border-emerald-100': r.type === 'ACHIEVEMENT'}" 
                                      class="px-2 py-1 rounded text-[10px] font-bold border uppercase">
                                    {{ r.category }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="{'text-red-600': r.type === 'VIOLATION', 'text-emerald-600': r.type === 'ACHIEVEMENT'}" class="font-bold">
                                    {{ r.type === 'VIOLATION' ? '-' : '+' }}{{ r.points }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-xs truncate max-w-xs">{{ r.description || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL INPUT POIN -->
        <div v-if="showInputModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">Catat Poin Kedisiplinan</h3>
                    <button @click="showInputModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form @submit.prevent="saveRecord" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Cari Santri</label>
                        <div class="relative">
                            <input type="text" v-model="studentSearch" @input="searchStudents" 
                                   placeholder="Ketik nama atau NIS..." 
                                   class="w-full border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                        <div v-if="searchResults.length > 0" class="absolute z-50 w-[calc(100%-3rem)] mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                            <div v-for="s in searchResults" :key="s.id" @click="selectStudent(s)" class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 flex items-center gap-3">
                                <div class="text-sm font-bold text-slate-700">{{ s.name }}</div>
                            </div>
                        </div>
                        <div v-if="selectedStudent" class="mt-2 p-2 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold flex justify-between items-center">
                            <span>Terpilih: {{ selectedStudent.name }}</span>
                            <button type="button" @click="selectedStudent = null" class="text-indigo-400"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Jenis Catatan</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="form.type = 'VIOLATION'" 
                                    :class="{'bg-red-600 text-white border-red-600': form.type === 'VIOLATION', 'bg-white text-slate-500 border-slate-200': form.type !== 'VIOLATION'}"
                                    class="py-2 border rounded-lg text-sm font-bold transition-all">Pelanggaran</button>
                            <button type="button" @click="form.type = 'ACHIEVEMENT'" 
                                    :class="{'bg-emerald-600 text-white border-emerald-600': form.type === 'ACHIEVEMENT', 'bg-white text-slate-500 border-slate-200': form.type !== 'ACHIEVEMENT'}"
                                    class="py-2 border rounded-lg text-sm font-bold transition-all">Prestasi</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori / Nama Poin</label>
                        <input v-model="form.category" type="text" placeholder="Contoh: Terlambat Berjamaah" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jumlah Poin</label>
                            <input v-model.number="form.points" type="number" min="1" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal</label>
                            <input v-model="form.record_date" type="date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan (Opsional)</label>
                        <textarea v-model="form.description" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button type="button" @click="showInputModal = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all" :disabled="saving">
                            <span v-if="saving"><i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...</span>
                            <span v-else>Simpan Catatan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue
    createApp({
        data() {
            return {
                currentGender: 'all',
                records: [],
                summary: [],
                showInputModal: false,
                saving: false,
                studentSearch: '',
                searchResults: [],
                selectedStudent: null,
                form: {
                    student_id: null,
                    type: 'VIOLATION',
                    category: '',
                    points: 5,
                    description: '',
                    record_date: new Date().toISOString().substr(0, 10)
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        },
        watch: {
            currentGender() { 
                this.fetchRecords(); 
                this.fetchSummary();
            }
        },
        methods: {
            async fetchRecords() {
                try {
                    const res = await fetch(`../../api/boarding_discipline.php?action=get_records&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) this.records = data.data;
                } catch (e) {}
            },
            async fetchSummary() {
                try {
                    const res = await fetch(`../../api/boarding_discipline.php?action=get_summary&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) this.summary = data.data;
                } catch (e) {}
            },
            async searchStudents() {
                if (this.studentSearch.length < 2) { this.searchResults = []; return; }
                try {
                    const res = await fetch(`../../api/boarding.php?action=get_students&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) {
                        const query = this.studentSearch.toLowerCase();
                        this.searchResults = data.data.filter(s => s.name.toLowerCase().includes(query)).slice(0, 5);
                    }
                } catch (e) {}
            },
            selectStudent(s) {
                this.selectedStudent = s;
                this.form.student_id = s.id;
                this.studentSearch = '';
                this.searchResults = [];
            },
            async saveRecord() {
                if (!this.form.student_id) { alert('Pilih santri terlebih dahulu'); return; }
                this.saving = true;
                try {
                    const res = await fetch('../../api/boarding_discipline.php?action=save_record', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showInputModal = false;
                        this.fetchRecords();
                        this.fetchSummary();
                        this.selectedStudent = null;
                        this.form.category = '';
                    }
                } catch (e) {} finally { this.saving = false; }
            },
            formatDate(d) {
                return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            },
            viewDetail(s) {
                // Placeholder for detail view
                alert('Detail for ' + s.name);
            }
        },
        mounted() {
            this.fetchRecords();
            this.fetchSummary();
        }
    }).mount('#app')
</script>
</body>
</html>
