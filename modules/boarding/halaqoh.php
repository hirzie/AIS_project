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
    <title>Manajemen Halaqoh - SekolahOS</title>
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
                    <h2 class="text-2xl font-bold text-slate-800">Manajemen Halaqoh</h2>
                    <p class="text-slate-500 text-sm">Kelola kelompok tahfidz dan ustadz pembimbing</p>
                </div>
                <div class="flex gap-2">
                    <button @click="openAddModal" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-bold text-sm shadow-lg shadow-indigo-100 transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Halaqoh
                    </button>
                </div>
            </div>

            <!-- Halaqoh Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="h in filteredHalaqoh" :key="h.id" 
                     class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-all relative overflow-hidden group">
                    
                    <div class="absolute top-0 right-0 w-16 h-16 opacity-10 -mr-4 -mt-4 transition-transform group-hover:scale-110"
                         :class="{'text-blue-600': h.gender === 'L', 'text-pink-600': h.gender === 'P'}">
                        <i class="fas" :class="{'fa-male text-6xl': h.gender === 'L', 'fa-female text-6xl': h.gender === 'P'}"></i>
                    </div>

                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shadow-sm"
                             :class="{'bg-blue-100 text-blue-600': h.gender === 'L', 'bg-pink-100 text-pink-600': h.gender === 'P'}">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditModal(h)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-indigo-100 hover:text-indigo-600 flex items-center justify-center transition-all">
                                <i class="fas fa-pencil-alt text-xs"></i>
                            </button>
                            <button @click="deleteHalaqoh(h)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-800 mb-1">{{ h.name }}</h3>
                    <div class="flex flex-col gap-1 mb-4">
                        <span :class="{'bg-blue-50 text-blue-700 border-blue-100': h.gender === 'L', 'bg-pink-50 text-pink-700 border-pink-100': h.gender === 'P'}" 
                              class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase w-fit">
                            {{ h.gender === 'L' ? 'Ikhwan' : 'Akhwat' }}
                        </span>
                        <div v-if="h.ustadz_name" class="text-xs text-slate-500 italic">
                            Pembimbing: {{ h.ustadz_name }}
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredHalaqoh.length === 0" class="col-span-full py-20 text-center bg-white rounded-2xl border border-dashed border-slate-300">
                    <i class="fas fa-book-reader text-5xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400">Belum ada data halaqoh untuk kategori ini.</p>
                </div>
            </div>
        </div>

        <!-- MODAL ADD/EDIT HALAQOH -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">{{ form.id ? 'Edit' : 'Tambah' }} Halaqoh</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form @submit.prevent="saveHalaqoh" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Kelompok</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Kelompok Abu Bakar" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori (Gender)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="form.gender = 'L'" 
                                    :class="{'bg-blue-600 text-white border-blue-600': form.gender === 'L', 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50': form.gender !== 'L'}"
                                    class="flex items-center justify-center gap-2 py-2 border rounded-lg text-sm font-bold transition-all">
                                <i class="fas fa-male"></i> Ikhwan
                            </button>
                            <button type="button" @click="form.gender = 'P'" 
                                    :class="{'bg-pink-600 text-white border-pink-600': form.gender === 'P', 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50': form.gender !== 'P'}"
                                    class="flex items-center justify-center gap-2 py-2 border rounded-lg text-sm font-bold transition-all">
                                <i class="fas fa-female"></i> Akhwat
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ustadz Pembimbing</label>
                        <select v-model="form.ustadz_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option :value="null">-- Pilih Ustadz --</option>
                            <option v-for="u in ustadzList" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all" :disabled="saving">
                            <span v-if="saving"><i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...</span>
                            <span v-else>Simpan Halaqoh</span>
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
                halaqohList: [],
                ustadzList: [],
                currentGender: 'all',
                loading: false,
                saving: false,
                showModal: false,
                form: {
                    id: null,
                    name: '',
                    gender: 'L',
                    ustadz_id: null
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredHalaqoh() {
                if (this.currentGender === 'all') return this.halaqohList;
                return this.halaqohList.filter(h => h.gender === this.currentGender);
            }
        },
        watch: {
            currentGender() {
                this.fetchHalaqoh();
            }
        },
        methods: {
            async fetchHalaqoh() {
                this.loading = true;
                try {
                    const res = await fetch(`../../api/boarding_halaqoh.php?action=get_halaqoh&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) {
                        this.halaqohList = data.data;
                    }
                } catch (e) {
                    console.error('Failed to fetch halaqoh', e);
                } finally {
                    this.loading = false;
                }
            },
            async fetchUstadz() {
                try {
                    // Reusing get_musyrif as it likely returns teachers/staff
                    const res = await fetch('../../api/boarding.php?action=get_musyrif');
                    const data = await res.json();
                    if (data.success) {
                        this.ustadzList = data.data;
                    }
                } catch (e) {}
            },
            openAddModal() {
                this.form = { id: null, name: '', gender: 'L', ustadz_id: null };
                this.showModal = true;
            },
            openEditModal(halaqoh) {
                this.form = { ...halaqoh };
                this.showModal = true;
            },
            async saveHalaqoh() {
                this.saving = true;
                try {
                    const res = await fetch('../../api/boarding_halaqoh.php?action=save_halaqoh', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showModal = false;
                        this.fetchHalaqoh();
                    } else {
                        alert(data.error);
                    }
                } catch (e) {
                    alert('Error saving halaqoh');
                } finally {
                    this.saving = false;
                }
            },
            async deleteHalaqoh(halaqoh) {
                if (!confirm(`Hapus halaqoh ${halaqoh.name}?`)) return;
                try {
                    const res = await fetch('../../api/boarding_halaqoh.php?action=delete_halaqoh', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: halaqoh.id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchHalaqoh();
                    }
                } catch (e) {
                    alert('Error deleting halaqoh');
                }
            }
        },
        mounted() {
            this.fetchHalaqoh();
            this.fetchUstadz();
        }
    }).mount('#app')
</script>
</body>
</html>
