<?php
require_once '../../includes/guard.php';
ais_init_session();
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Prestasi Siswa - BK & Kesiswaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<div id="app" class="flex flex-col h-screen">
    <?php require_once '../../includes/counseling_header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-3">Cari Siswa</h3>
                <div class="relative">
                    <input v-model="searchQuery" @input="searchStudents" placeholder="Nama atau NIS..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm pr-12">
                    <button @click="searchStudents" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-pink-600 text-white rounded-lg flex items-center justify-center">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </div>
                <div class="mt-3 bg-white border border-slate-200 rounded-xl overflow-hidden" v-if="searchResults.length">
                    <div v-for="s in searchResults" :key="s.id" class="px-4 py-3 hover:bg-slate-50 cursor-pointer flex items-center justify-between" @click="selectStudent(s)">
                        <div>
                            <div class="font-bold text-slate-800 text-sm">{{ s.name }}</div>
                            <div class="text-[11px] text-slate-500">NIS: {{ s.identity_number }} • {{ s.class_name || '-' }}</div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-300"></i>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Input Prestasi Siswa</h3>
                    <div v-if="selectedStudent" class="text-right">
                        <div class="text-xs text-slate-400">Siswa</div>
                        <div class="font-bold text-slate-800">{{ selectedStudent.name }}</div>
                        <div class="text-[11px] text-slate-500">NIS: {{ selectedStudent.identity_number }}</div>
                    </div>
                </div>
                <div v-if="!selectedStudent" class="text-slate-500 text-sm">Pilih siswa terlebih dahulu dari panel kiri.</div>
                <div v-else class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input v-model="newAch.title" type="text" placeholder="Judul Prestasi" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <select v-model="newAch.category" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                            <option value="">Kategori</option>
                            <option>Akademik</option>
                            <option>Olahraga</option>
                            <option>Seni</option>
                            <option>Riset</option>
                            <option>Ibadah</option>
                            <option>Akhlaq</option>
                            <option>Tahfidz</option>
                            <option>Hadits</option>
                            <option>Lainnya</option>
                        </select>
                        <select v-model="newAch.level" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                            <option value="">Tingkat</option>
                            <option>Sekolah</option>
                            <option>Kecamatan</option>
                            <option>Kabupaten</option>
                            <option>Provinsi</option>
                            <option>Nasional</option>
                        </select>
                        <input v-model="newAch.rank" type="text" placeholder="Peringkat (mis. Juara 1)" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <input v-model="newAch.organizer" type="text" placeholder="Penyelenggara" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <input v-model="newAch.date" type="date" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <input v-model.number="newAch.points" type="number" min="0" placeholder="Poin" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <button @click="createAchievement" class="bg-pink-600 hover:bg-pink-700 text-white rounded-lg px-4 py-2 text-sm font-bold">Simpan Prestasi</button>
                    </div>
                    <div class="space-y-3">
                        <div class="text-sm font-bold text-slate-700">Daftar Prestasi</div>
                        <div v-for="a in achievements" :key="a.id" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-lg">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-slate-800">{{ a.title }}</div>
                                <div class="text-[11px] text-slate-500">Kategori {{ a.category }} • Tingkat {{ a.level }} • {{ a.rank }}</div>
                                <div class="text-[10px] text-slate-400 uppercase font-bold">{{ formatDate(a.date) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-bold text-slate-300 uppercase">Penyelenggara</div>
                                <div class="text-xs text-slate-600">{{ a.organizer }}</div>
                                <div v-if="a.points" class="mt-1 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">{{ a.points }} Poin</div>
                            </div>
                        </div>
                        <div v-if="achievements.length === 0" class="py-8 text-center text-slate-400 text-sm">Belum ada prestasi.</div>
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
            searchQuery: '',
            searchResults: [],
            selectedStudent: null,
            achievements: [],
            newAch: { title: '', category: '', level: '', rank: '', organizer: '', date: '', points: null }
        }
    },
    methods: {
        async searchStudents() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            const res = await fetch(`../../api/counseling.php?action=search_students&q=${encodeURIComponent(this.searchQuery)}`);
            const data = await res.json();
            if (data.success) this.searchResults = data.data;
        },
        async selectStudent(s) {
            this.selectedStudent = s;
            const res = await fetch(`../../api/counseling.php?action=list_achievements&student_id=${s.id}`);
            const data = await res.json();
            if (data.success) this.achievements = data.data || [];
        },
        async createAchievement() {
            if (!this.selectedStudent || !this.selectedStudent.id || !this.newAch.title) return;
            const payload = { ...this.newAch, student_id: this.selectedStudent.id };
            const res = await fetch(`../../api/counseling.php?action=add_achievement`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success && data.data) {
                this.achievements.unshift(data.data);
                this.newAch = { title: '', category: '', level: '', rank: '', organizer: '', date: '', points: null };
            }
        },
        formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }
    }
}).mount('#app')
</script>
</body>
</html>
