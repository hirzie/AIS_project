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
    <title>Keanggotaan - SekolahOS</title>
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
        <div class="max-w-6xl mx-auto">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Manajemen Keanggotaan</h2>
                    <p class="text-slate-500 text-sm">Kelola data anggota perpustakaan dan kartu anggota.</p>
                </div>
                <button @click="showRegisterForm = true" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all">
                    <i class="fas fa-user-plus mr-2"></i> Tambah Anggota
                </button>
            </div>

            <!-- Inline Form for Member Registration -->
            <div v-if="showRegisterForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Registrasi Anggota Baru</h3>
                    <button @click="showRegisterForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cari Orang (Siswa/Staff)</label>
                        <div class="relative">
                            <input type="text" v-model="searchQuery" @input="searchPeople" placeholder="Ketik nama atau NIS..." 
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                            <div v-if="searchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                <div v-for="p in searchResults" :key="p.id" @click="selectPerson(p)" class="p-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-50 last:border-0">
                                    <div class="text-xs font-bold text-slate-700">{{ p.name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ p.identity_number }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kode Anggota (Barcode)</label>
                        <input type="text" v-model="memberCode" placeholder="LIB-XXXX" 
                               class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none font-mono">
                    </div>

                    <div class="md:col-span-1 flex items-end gap-2">
                        <button @click="showRegisterForm = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                        <button @click="registerMember" :disabled="!selectedPerson || !memberCode" 
                                class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-100 hover:bg-emerald-700 disabled:bg-slate-200 disabled:shadow-none transition-all">
                            Daftarkan
                        </button>
                    </div>
                </div>
                
                <div v-if="selectedPerson" class="mt-4 p-4 bg-emerald-50 rounded-xl border border-emerald-100 inline-block min-w-[300px]">
                    <div class="text-[10px] font-bold text-emerald-500 uppercase mb-1">Terpilih:</div>
                    <div class="font-bold text-emerald-800">{{ selectedPerson.name }}</div>
                    <div class="text-xs text-emerald-600">{{ selectedPerson.identity_number }}</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="px-6 py-4">Kode Anggota</th>
                            <th class="px-6 py-4">Nama Lengkap</th>
                            <th class="px-6 py-4">Tipe</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Terdaftar</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in members" :key="m.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-emerald-600">{{ m.member_code }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ m.name }}</div>
                                <div class="text-[10px] text-slate-400">{{ m.nis }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-[10px] font-bold border" 
                                      :class="m.type === 'STUDENT' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100'">
                                    {{ m.type }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-[10px] font-bold"
                                      :class="m.status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ m.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ formatDate(m.created_at) }}</td>
                            <td class="px-6 py-4 text-center">
                                <button class="text-slate-400 hover:text-indigo-600 transition-colors mr-3" title="Cetak Kartu">
                                    <i class="fas fa-id-card"></i>
                                </button>
                                <button class="text-slate-400 hover:text-red-600 transition-colors" title="Nonaktifkan">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="members.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada anggota terdaftar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue
    createApp({
        data() {
            return {
                members: [],
                showRegisterForm: false,
                searchQuery: '',
                searchResults: [],
                selectedPerson: null,
                memberCode: ''
            }
        },
        methods: {
            async fetchMembers() {
                const res = await fetch('../../api/library.php?action=get_members');
                const data = await res.json();
                if (data.success) this.members = data.data;
            },
            async searchPeople() {
                if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                const res = await fetch(`../../api/library.php?action=search_people_not_members&q=${this.searchQuery}`);
                const data = await res.json();
                if (data.success) this.searchResults = data.data;
            },
            selectPerson(p) {
                this.selectedPerson = p;
                this.memberCode = p.identity_number; // Default to NIS
                this.searchResults = [];
                this.searchQuery = p.name;
            },
            async registerMember() {
                const res = await fetch('../../api/library.php?action=register_member', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ person_id: this.selectedPerson.id, member_code: this.memberCode })
                });
                if ((await res.json()).success) {
                    this.showRegisterForm = false;
                    this.selectedPerson = null;
                    this.memberCode = '';
                    this.searchQuery = '';
                    this.fetchMembers();
                }
            },
            formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }
        },
        mounted() {
            this.fetchMembers();
        }
    }).mount('#app')
</script>
</body>
</html>
