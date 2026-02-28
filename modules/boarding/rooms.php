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
    <title>Manajemen Kamar - SekolahOS</title>
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
                    <h2 class="text-2xl font-bold text-slate-800">Manajemen Kamar</h2>
                    <p class="text-slate-500 text-sm">Kelola daftar kamar, kapasitas, dan peruntukan (Ikhwan/Akhwat)</p>
                </div>
                <div class="flex gap-2">
                    <button @click="openAddModal" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-bold text-sm shadow-lg shadow-indigo-100 transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Kamar
                    </button>
                </div>
            </div>

            <!-- Rooms Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="room in filteredRooms" :key="room.id" 
                     class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-all relative overflow-hidden group">
                    
                    <div class="absolute top-0 right-0 w-16 h-16 opacity-10 -mr-4 -mt-4 transition-transform group-hover:scale-110"
                         :class="[room.gender === 'L' ? 'text-blue-600' : 'text-pink-600']">
                        <i class="fas" :class="[room.gender === 'L' ? 'fa-male text-6xl' : 'fa-female text-6xl']"></i>
                    </div>

                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shadow-sm"
                             :class="[room.gender === 'L' ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600']">
                            <i class="fas fa-door-closed"></i>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditModal(room)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-indigo-100 hover:text-indigo-600 flex items-center justify-center transition-all">
                                <i class="fas fa-pencil-alt text-xs"></i>
                            </button>
                            <button @click="deleteRoom(room)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-800 mb-1">{{ room.name }}</h3>
                    <div class="flex flex-col gap-1 mb-4">
                        <span :class="[room.gender === 'L' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-pink-50 text-pink-700 border-pink-100']" 
                              class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase w-fit">
                            {{ room.gender === 'L' ? 'Ikhwan' : 'Akhwat' }}
                        </span>
                        <div v-if="room.musyrif_name" class="text-xs text-slate-500 italic">
                            Musyrif: {{ room.musyrif_name }}
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-end text-sm">
                            <span class="text-slate-500">Kapasitas</span>
                            <span class="font-bold text-slate-700">{{ room.occupants_count }} / {{ room.capacity }}</span>
                        </div>
                        <!-- Progress Bar -->
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full transition-all duration-500"
                                 :class="[getProgressClass(room)]"
                                 :style="{ width: getProgressWidth(room) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredRooms.length === 0" class="col-span-full py-20 text-center bg-white rounded-2xl border border-dashed border-slate-300">
                    <i class="fas fa-door-open text-5xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400">Belum ada data kamar untuk kategori ini.</p>
                </div>
            </div>
        </div>

        <!-- MODAL ADD/EDIT ROOM -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">{{ form.id ? 'Edit' : 'Tambah' }} Kamar</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form @submit.prevent="saveRoom" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Kamar</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Abu Bakar 01" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Peruntukan (Gender)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="form.gender = 'L'" 
                                    :class="[form.gender === 'L' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50']"
                                    class="flex items-center justify-center gap-2 py-2 border rounded-lg text-sm font-bold transition-all">
                                <i class="fas fa-male"></i> Ikhwan
                            </button>
                            <button type="button" @click="form.gender = 'P'" 
                                    :class="[form.gender === 'P' ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50']"
                                    class="flex items-center justify-center gap-2 py-2 border rounded-lg text-sm font-bold transition-all">
                                <i class="fas fa-female"></i> Akhwat
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kapasitas (Orang)</label>
                        <input v-model.number="form.capacity" type="number" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Musyrif Kamar</label>
                        <select v-model="form.musyrif_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option :value="null">-- Pilih Musyrif --</option>
                            <option v-for="m in musyrifList" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all" :disabled="saving">
                            <span v-if="saving"><i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...</span>
                            <span v-else>Simpan Kamar</span>
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
                rooms: [],
                musyrifList: [],
                currentGender: 'all',
                loading: false,
                saving: false,
                showModal: false,
                form: {
                    id: null,
                    name: '',
                    gender: 'L',
                    capacity: 4,
                    musyrif_id: null
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredRooms() {
                if (this.currentGender === 'all') return this.rooms;
                return this.rooms.filter(r => r.gender === this.currentGender);
            }
        },
        watch: {
            currentGender() {
                this.fetchRooms();
            }
        },
        methods: {
            async fetchRooms() {
                this.loading = true;
                try {
                    const res = await fetch(`../../api/boarding_rooms.php?action=get_rooms&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) {
                        this.rooms = data.data;
                    }
                } catch (e) {
                    console.error('Failed to fetch rooms', e);
                } finally {
                    this.loading = false;
                }
            },
            async fetchMusyrif() {
                try {
                    const res = await fetch('../../api/boarding.php?action=get_musyrif');
                    const data = await res.json();
                    if (data.success) {
                        this.musyrifList = data.data;
                    }
                } catch (e) {}
            },
            getProgressWidth(room) {
                if (!room.capacity) return 0;
                const percent = (room.occupants_count / room.capacity) * 100;
                return Math.min(percent, 100);
            },
            getProgressClass(room) {
                if (!room.capacity) return 'bg-slate-300';
                const percent = (room.occupants_count / room.capacity) * 100;
                if (percent >= 100) return 'bg-red-500';
                if (percent >= 80) return 'bg-orange-500';
                return 'bg-emerald-500';
            },
            openAddModal() {
                this.form = { id: null, name: '', gender: 'L', capacity: 4, musyrif_id: null };
                this.showModal = true;
            },
            openEditModal(room) {
                this.form = { ...room };
                this.showModal = true;
            },
            async saveRoom() {
                this.saving = true;
                try {
                    const res = await fetch('../../api/boarding_rooms.php?action=save_room', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showModal = false;
                        this.fetchRooms();
                    } else {
                        alert(data.error);
                    }
                } catch (e) {
                    alert('Error saving room');
                } finally {
                    this.saving = false;
                }
            },
            async deleteRoom(room) {
                if (!confirm(`Hapus kamar ${room.name}?`)) return;
                try {
                    const res = await fetch('../../api/boarding_rooms.php?action=delete_room', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: room.id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchRooms();
                    }
                } catch (e) {
                    alert('Error deleting room');
                }
            }
        },
        mounted() {
            this.fetchRooms();
            this.fetchMusyrif();
        }
    }).mount('#app')
</script>
</body>
</html>
