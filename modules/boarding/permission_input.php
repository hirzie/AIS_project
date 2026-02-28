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
    <title>Input Perizinan - SekolahOS</title>
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
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Input Perizinan Santri</h2>
                    <p class="text-slate-500 text-sm">Catat izin keluar, pulang, atau sakit santri</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <form @submit.prevent="savePermission" class="p-8 space-y-6">
                    <!-- Cari Santri -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Santri</label>
                            <div class="relative">
                                <input type="text" v-model="studentSearch" @input="searchStudents" 
                                       placeholder="Ketik nama atau NIS..." 
                                       class="w-full border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                            
                            <!-- Dropdown Hasil Pencarian -->
                            <div v-if="searchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                <div v-for="s in searchResults" :key="s.id" 
                                     @click="selectStudent(s)"
                                     class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                        {{ s.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-700">{{ s.name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ s.nis }} - {{ s.class_name }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Santri Terpilih -->
                            <div v-if="selectedStudent" class="mt-3 p-3 bg-indigo-50 border border-indigo-100 rounded-lg flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold">
                                        {{ selectedStudent.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-indigo-900">{{ selectedStudent.name }}</div>
                                        <div class="text-xs text-indigo-600">{{ selectedStudent.nis }}</div>
                                    </div>
                                </div>
                                <button type="button" @click="selectedStudent = null" class="text-indigo-400 hover:text-indigo-600">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Jenis Izin</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" v-for="t in types" :key="t.value"
                                        @click="form.permission_type = t.value"
                                        :class="{'bg-indigo-600 text-white border-indigo-600 shadow-md': form.permission_type === t.value, 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50': form.permission_type !== t.value}"
                                        class="py-2 border rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2">
                                    <i :class="t.icon"></i> {{ t.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Mulai Izin</label>
                            <input type="datetime-local" v-model="form.start_date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Rencana Kembali</label>
                            <input type="datetime-local" v-model="form.end_date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Alasan / Keperluan</label>
                        <textarea v-model="form.reason" rows="3" placeholder="Sebutkan alasan izin secara detail..." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all" :disabled="saving">
                            <span v-if="saving"><i class="fas fa-spinner fa-spin mr-2"></i> Memproses...</span>
                            <span v-else><i class="fas fa-save mr-2"></i> Simpan Izin</span>
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
                studentSearch: '',
                searchResults: [],
                selectedStudent: null,
                saving: false,
                types: [
                    { label: 'Pulang', value: 'PULANG', icon: 'fas fa-home' },
                    { label: 'Keluar', value: 'KELUAR', icon: 'fas fa-walking' },
                    { label: 'Sakit', value: 'SAKIT', icon: 'fas fa-briefcase-medical' },
                    { label: 'Lainnya', value: 'LAINNYA', icon: 'fas fa-ellipsis-h' }
                ],
                form: {
                    student_id: null,
                    permission_type: 'PULANG',
                    reason: '',
                    start_date: '',
                    end_date: '',
                    status: 'APPROVED' // Langsung set disetujui untuk demo
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        },
        methods: {
            async searchStudents() {
                if (this.studentSearch.length < 2) {
                    this.searchResults = [];
                    return;
                }
                try {
                    const res = await fetch(`../../api/boarding.php?action=get_students&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) {
                        const query = this.studentSearch.toLowerCase();
                        this.searchResults = data.data.filter(s => 
                            s.name.toLowerCase().includes(query) || s.nis.includes(query)
                        ).slice(0, 5);
                    }
                } catch (e) {}
            },
            selectStudent(s) {
                this.selectedStudent = s;
                this.form.student_id = s.id;
                this.studentSearch = '';
                this.searchResults = [];
            },
            async savePermission() {
                if (!this.form.student_id) {
                    alert('Pilih santri terlebih dahulu');
                    return;
                }
                this.saving = true;
                try {
                    const res = await fetch('../../api/boarding_permissions.php?action=save_permission', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Izin berhasil dicatat');
                        window.location.href = 'permission_history.php';
                    }
                } catch (e) {
                    alert('Error saving permission');
                } finally {
                    this.saving = false;
                }
            }
        }
    }).mount('#app')
</script>
</body>
</html>
