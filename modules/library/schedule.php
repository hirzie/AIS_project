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
    <title>Jadwal Kunjungan Perpustakaan - SekolahOS</title>
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
    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Jadwal Kunjungan Mingguan</h2>
                    <p class="text-slate-500 text-sm">Atur jadwal kunjungan kelas ke perpustakaan</p>
                </div>
                <div class="flex gap-2">
                    <button @click="openScheduleForm()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all">
                        <i class="fas fa-plus mr-2"></i> Atur Jadwal
                    </button>
                </div>
            </div>

            <!-- Inline Form for Schedule -->
            <div v-if="showScheduleForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-slate-800">{{ scheduleForm.id ? 'Edit' : 'Tambah' }} Jadwal</h3>
                    <button @click="showScheduleForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form @submit.prevent="saveSchedule" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Hari</label>
                        <select v-model="scheduleForm.day_name" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                            <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pilih Kelas</label>
                        <select v-model="scheduleForm.class_id" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Mulai</label>
                        <input v-model="scheduleForm.start_time" type="time" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Selesai</label>
                        <input v-model="scheduleForm.end_time" type="time" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                    </div>
                    <div class="md:col-span-5 flex justify-end gap-2 mt-2">
                        <button v-if="scheduleForm.id" type="button" @click="deleteSchedule(scheduleForm)" class="mr-auto px-6 py-2 border border-red-100 text-red-500 rounded-xl text-sm font-bold hover:bg-red-50">Hapus</button>
                        <button type="button" @click="showScheduleForm = false" class="px-6 py-2 border border-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                        <button type="submit" class="px-8 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm">Simpan Jadwal</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                            <th v-for="day in days" :key="day" class="p-4 w-48 border-r border-slate-100 text-center">{{ day }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td v-for="day in days" :key="day" class="p-2 border-r border-slate-100 align-top h-96 bg-slate-50/20">
                                <div v-for="s in getDaySchedules(day)" :key="s.id" 
                                        class="bg-white border border-emerald-100 p-3 rounded-xl shadow-sm mb-2 hover:shadow-md transition-all group relative">
                                    <div class="font-bold text-emerald-700 text-sm mb-1 leading-tight">{{ s.class_name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ formatTime(s.start_time) }} - {{ formatTime(s.end_time) }}</div>
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                                        <button @click="openScheduleForm(s)" class="text-indigo-400 hover:text-indigo-600"><i class="fas fa-pen text-[10px]"></i></button>
                                        <button @click="deleteSchedule(s)" class="text-red-300 hover:text-red-500"><i class="fas fa-trash text-[10px]"></i></button>
                                    </div>
                                </div>
                                <button @click="openScheduleForm({day_name: day})" class="w-full py-2 border-2 border-dashed border-slate-200 rounded-xl text-slate-300 hover:text-emerald-500 hover:border-emerald-200 transition-all">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </td>
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
                days: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                classes: [],
                schedules: [],
                showScheduleForm: false,
                scheduleForm: { id: null, class_id: '', day_name: 'Senin', start_time: '08:00', end_time: '09:00' }
            }
        },
        methods: {
            async fetchClasses() {
                const res = await fetch('../../api/library.php?action=get_classes');
                const data = await res.json();
                if (data.success) this.classes = data.data;
            },
            async fetchSchedules() {
                const res = await fetch('../../api/library.php?action=get_schedules');
                const data = await res.json();
                if (data.success) this.schedules = data.data;
            },
            getDaySchedules(day) {
                return this.schedules.filter(s => s.day_name === day);
            },
            openScheduleForm(s = null) {
                if (s && s.id) this.scheduleForm = { ...s };
                else if (s && s.day_name) this.scheduleForm = { id: null, class_id: '', day_name: s.day_name, start_time: '08:00', end_time: '09:00' };
                else this.scheduleForm = { id: null, class_id: '', day_name: 'Senin', start_time: '08:00', end_time: '09:00' };
                this.showScheduleForm = true;
                this.$nextTick(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
            },
            async saveSchedule() {
                const res = await fetch('../../api/library.php?action=save_schedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.scheduleForm)
                });
                if ((await res.json()).success) {
                    this.showScheduleForm = false;
                    this.fetchSchedules();
                }
            },
            async deleteSchedule(s) {
                if (!confirm('Hapus jadwal ini?')) return;
                const res = await fetch('../../api/library.php?action=delete_schedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: s.id })
                });
                if ((await res.json()).success) {
                    this.showScheduleForm = false;
                    this.fetchSchedules();
                }
            },
            formatTime(t) { return t ? t.substring(0, 5) : ''; }
        },
        mounted() {
            this.fetchClasses();
            this.fetchSchedules();
        }
    }).mount('#app')
</script>
</body>
</html>
