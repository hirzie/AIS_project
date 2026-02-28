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
    <title>Jadwal Kegiatan Asrama - SekolahOS</title>
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

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Jadwal Kegiatan Asrama</h2>
                    <p class="text-slate-500 text-sm">Kelola agenda harian dan slot waktu kegiatan santri</p>
                </div>
                <div class="flex gap-2">
                    <button @click="viewMode = 'agenda'" :class="viewMode === 'agenda' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition-all border border-slate-200">
                        <i class="fas fa-calendar-alt mr-2"></i> Agenda Mingguan
                    </button>
                    <button @click="viewMode = 'slots'" :class="viewMode === 'slots' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition-all border border-slate-200">
                        <i class="fas fa-clock mr-2"></i> Atur Jam
                    </button>
                </div>
            </div>

            <!-- VIEW: AGENDA MINGGUAN -->
            <div v-if="viewMode === 'agenda'" class="animate-fade">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                                <th class="p-4 w-32 border-r border-slate-100 text-center">Waktu</th>
                                <th v-for="day in days" :key="day" class="p-4 w-48 border-r border-slate-100 text-center">{{ day }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="slot in slots" :key="slot.id" class="border-b border-slate-100 hover:bg-slate-50/50">
                                <td class="p-4 border-r border-slate-100 bg-slate-50/30 text-center align-middle" :class="slot.is_break ? 'h-12' : 'h-24'">
                                    <div class="font-bold text-slate-700 text-sm">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</div>
                                    <div class="text-[10px] text-slate-400 mt-1 font-medium uppercase tracking-wider">{{ slot.name }}</div>
                                </td>
                                <td v-for="day in days" :key="day + slot.id" class="p-2 border-r border-slate-100 align-top relative group" :class="slot.is_break ? 'bg-amber-50/20' : ''">
                                    <!-- Activity Item -->
                                    <div v-if="getAgendaItem(day, slot.id)" 
                                         @click="openAgendaModal(day, slot, getAgendaItem(day, slot.id))"
                                         class="bg-white border border-indigo-100 p-3 rounded-xl shadow-sm hover:shadow-md transition-all cursor-pointer group/item">
                                        <div class="font-bold text-indigo-700 text-sm mb-1 leading-tight">{{ getAgendaItem(day, slot.id).activity_name }}</div>
                                        <div class="text-[10px] text-slate-500 line-clamp-2 leading-relaxed">{{ getAgendaItem(day, slot.id).description || '-' }}</div>
                                        <button class="absolute top-2 right-2 opacity-0 group-hover/item:opacity-100 text-slate-300 hover:text-red-500 transition-opacity">
                                            <i class="fas fa-pen text-[10px]"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Empty Slot -->
                                    <div v-else class="h-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openAgendaModal(day, slot)" class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-400 hover:bg-indigo-600 hover:text-white transition-all">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: SLOTS MANAGEMENT -->
            <div v-if="viewMode === 'slots'" class="animate-fade max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-700">Daftar Slot Waktu</h3>
                    <div class="flex gap-2">
                        <button @click="generateSlots" class="bg-amber-50 text-amber-600 border border-amber-200 px-4 py-2 rounded-lg text-sm font-bold hover:bg-amber-600 hover:text-white transition-all">
                            <i class="fas fa-magic mr-2"></i> Generate Default (16:00+)
                        </button>
                        <button @click="openSlotModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">
                            <i class="fas fa-plus mr-2"></i> Tambah Jam
                        </button>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-4">Nama Slot</th>
                                <th class="px-6 py-4">Waktu</th>
                                <th class="px-6 py-4 text-center">Tipe</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="slot in slots" :key="slot.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-700">{{ slot.name }}</td>
                                <td class="px-6 py-4 font-mono text-slate-500">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="slot.is_break" class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[10px] font-bold uppercase">Istirahat</span>
                                    <span v-else class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-[10px] font-bold uppercase">Kegiatan</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openSlotModal(slot)" class="text-indigo-600 hover:text-indigo-900 mr-3 font-bold text-xs">Edit</button>
                                    <button @click="deleteSlot(slot)" class="text-red-400 hover:text-red-600 font-bold text-xs">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="slots.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">Belum ada slot waktu diatur.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: ADD/EDIT SLOT -->
        <div v-if="showSlotModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">{{ slotForm.id ? 'Edit' : 'Tambah' }} Slot Jam</h3>
                    <button @click="showSlotModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form @submit.prevent="saveSlot" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Slot</label>
                        <input v-model="slotForm.name" type="text" placeholder="Contoh: Halaqoh Maghrib" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mulai</label>
                            <input v-model="slotForm.start_time" type="time" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Selesai</label>
                            <input v-model="slotForm.end_time" type="time" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="slotForm.is_break" class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-slate-700">Ini adalah jam istirahat/makan</span>
                        </label>
                    </div>
                    <div class="pt-4 flex gap-2">
                        <button type="button" @click="showSlotModal = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ADD/EDIT AGENDA -->
        <div v-if="showAgendaModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">{{ agendaForm.id ? 'Edit' : 'Tambah' }} Agenda</h3>
                        <p class="text-xs text-slate-500">{{ agendaForm.day_name }} | {{ agendaForm.slot_time }}</p>
                    </div>
                    <button @click="showAgendaModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form @submit.prevent="saveAgenda" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Kegiatan</label>
                        <input v-model="agendaForm.activity_name" type="text" placeholder="Contoh: Setoran Hafalan" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan (Opsional)</label>
                        <textarea v-model="agendaForm.description" rows="3" placeholder="Detail kegiatan..." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>
                    <div class="pt-4 flex gap-2">
                        <button v-if="agendaForm.id" type="button" @click="deleteAgenda" class="px-4 py-2 border border-red-200 text-red-500 rounded-lg text-sm font-bold hover:bg-red-50 transition-colors"><i class="fas fa-trash"></i></button>
                        <button type="button" @click="showAgendaModal = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Simpan Agenda</button>
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
                viewMode: 'agenda',
                currentGender: 'all',
                days: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                slots: [],
                agenda: [],
                showSlotModal: false,
                showAgendaModal: false,
                slotForm: { id: null, name: '', start_time: '', end_time: '', is_break: false },
                agendaForm: { id: null, slot_id: null, day_name: '', activity_name: '', description: '', slot_time: '' }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        },
        methods: {
            async fetchSlots() {
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=get_slots');
                    const data = await res.json();
                    if (data.success) this.slots = data.data;
                } catch (e) {}
            },
            async fetchAgenda() {
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=get_agenda');
                    const data = await res.json();
                    if (data.success) this.agenda = data.data;
                } catch (e) {}
            },
            getAgendaItem(day, slotId) {
                return this.agenda.find(a => a.day_name === day && a.slot_id === slotId);
            },
            formatTime(t) {
                return t.substring(0, 5);
            },
            openSlotModal(slot = null) {
                if (slot) {
                    this.slotForm = { ...slot, is_break: !!slot.is_break };
                } else {
                    this.slotForm = { id: null, name: '', start_time: '16:00', end_time: '17:00', is_break: false };
                }
                this.showSlotModal = true;
            },
            async saveSlot() {
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=save_slot', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.slotForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showSlotModal = false;
                        this.fetchSlots();
                    }
                } catch (e) {}
            },
            async deleteSlot(slot) {
                if (!confirm(`Hapus slot jam ${slot.name}? Semua agenda pada jam ini juga akan terhapus.`)) return;
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=delete_slot', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: slot.id })
                    });
                    if ((await res.json()).success) this.fetchSlots();
                } catch (e) {}
            },
            openAgendaModal(day, slot, agendaItem = null) {
                if (agendaItem) {
                    this.agendaForm = { ...agendaItem, slot_time: `${this.formatTime(slot.start_time)} - ${this.formatTime(slot.end_time)}` };
                } else {
                    this.agendaForm = { 
                        id: null, slot_id: slot.id, day_name: day, 
                        activity_name: '', description: '',
                        slot_time: `${this.formatTime(slot.start_time)} - ${this.formatTime(slot.end_time)}` 
                    };
                }
                this.showAgendaModal = true;
            },
            async saveAgenda() {
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=save_agenda', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.agendaForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showAgendaModal = false;
                        this.fetchAgenda();
                    }
                } catch (e) {}
            },
            async deleteAgenda() {
                if (!confirm('Hapus agenda ini?')) return;
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=delete_agenda', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: this.agendaForm.id })
                    });
                    if ((await res.json()).success) {
                        this.showAgendaModal = false;
                        this.fetchAgenda();
                    }
                } catch (e) {}
            },
            async generateSlots() {
                if (!confirm('Generate slot jam default? Semua data jam saat ini akan diganti.')) return;
                try {
                    const res = await fetch('../../api/boarding_schedule.php?action=generate_slots');
                    if ((await res.json()).success) this.fetchSlots();
                } catch (e) {}
            }
        },
        mounted() {
            this.fetchSlots();
            this.fetchAgenda();
        }
    }).mount('#app')
</script>
</body>
</html>
