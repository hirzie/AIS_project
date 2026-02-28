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
    <title>Daftar Santri - SekolahOS</title>
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
                    <h2 class="text-2xl font-bold text-slate-800">Daftar Santri Aktif</h2>
                    <p class="text-slate-500 text-sm">Menampilkan semua santri dengan status asrama aktif di sistem core</p>
                </div>
                <div class="flex gap-2">
                    <div class="relative">
                        <input type="text" v-model="searchQuery" placeholder="Cari santri..." class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Nama Santri</th>
                            <th class="px-6 py-4">Kelas</th>
                            <th class="px-6 py-4">Kamar</th>
                            <th class="px-6 py-4">Halaqoh</th>
                            <th class="px-6 py-4">Musyrif</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(student, index) in filteredStudents" :key="student.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-center text-slate-400 font-medium">{{ index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                        {{ student.name ? student.name.charAt(0) : '?' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-700">{{ student.name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ student.nis }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="student.class_name" class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-[10px] font-bold border border-blue-100 uppercase">
                                    {{ student.class_name }}
                                </span>
                                <span v-else class="text-slate-400 italic text-[10px]">Tanpa Kelas</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="student.room_name" class="flex items-center gap-2">
                                    <i class="fas fa-door-closed text-slate-400"></i>
                                    <span class="font-medium text-slate-700">{{ student.room_name }}</span>
                                </div>
                                <span v-else class="text-slate-300 italic text-[10px]">- Belum diatur -</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="student.halaqoh_db_name" class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-book-reader text-slate-400"></i>
                                        <span class="font-medium text-slate-700">{{ student.halaqoh_db_name }}</span>
                                    </div>
                                    <div v-if="student.halaqoh_ustadz_name" class="text-[10px] text-slate-400 italic pl-6">
                                        Ust. {{ student.halaqoh_ustadz_name }}
                                    </div>
                                </div>
                                <span v-else class="text-slate-300 italic text-[10px]">- Belum diatur -</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="student.musyrif_name" class="text-slate-700 font-medium">
                                    {{ student.musyrif_name }}
                                </div>
                                <span v-else class="text-slate-300 italic text-[10px]">- Belum diatur -</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusClass(student.boarding_status)" class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                                    {{ student.boarding_status || 'ACTIVE' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="openEditModal(student)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-indigo-100 hover:text-indigo-600 flex items-center justify-center transition-all">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredStudents.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-user-slash text-4xl mb-3 opacity-20"></i>
                                    <p v-if="searchQuery">Tidak ada santri yang cocok dengan pencarian.</p>
                                    <p v-else>Belum ada data santri dengan status asrama aktif.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL EDIT DETAILS -->
        <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Edit Data Asrama</h3>
                        <p class="text-xs text-slate-500">{{ editForm.name }} ({{ editForm.nis }})</p>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form @submit.prevent="saveDetails" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kamar Asrama</label>
                        <select v-model="editForm.room_name" @change="onRoomChange" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">-- Pilih Kamar --</option>
                            <option v-for="r in roomsList" :key="r.id" :value="r.name">
                                {{ r.name }} ({{ r.gender === 'L' ? 'Ikhwan' : 'Akhwat' }})
                            </option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kelompok Halaqoh / Tahfidz</label>
                        <select v-model="editForm.halaqoh_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option :value="null">-- Pilih Halaqoh --</option>
                            <option v-for="h in halaqohList" :key="h.id" :value="h.id">
                                {{ h.name }} ({{ h.gender === 'L' ? 'Ikhwan' : 'Akhwat' }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Musyrif (Kepala Asrama)</label>
                        <select v-model="editForm.musyrif_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option :value="null">-- Pilih Musyrif --</option>
                            <option v-for="m in musyrifList" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Status di Asrama</label>
                        <select v-model="editForm.boarding_status" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="ACTIVE">Aktif</option>
                            <option value="GRADUATED">Lulus</option>
                            <option value="OUT">Keluar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan Khusus</label>
                        <textarea v-model="editForm.remarks" rows="3" placeholder="Alergi makanan, riwayat penyakit, dll..." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button type="button" @click="showEditModal = false" class="flex-1 px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all" :disabled="saving">
                            <span v-if="saving"><i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
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
                students: [],
                musyrifList: [],
                roomsList: [],
                halaqohList: [],
                currentGender: 'all',
                searchQuery: '',
                loading: false,
                saving: false,
                showEditModal: false,
                editForm: {
                    student_id: null,
                    name: '',
                    nis: '',
                    room_name: '',
                    halaqoh_id: null,
                    musyrif_id: null,
                    boarding_status: 'ACTIVE',
                    remarks: ''
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredStudents() {
                if (!this.searchQuery) return this.students;
                const query = this.searchQuery.toLowerCase();
                return this.students.filter(s => 
                    s.name.toLowerCase().includes(query) || 
                    s.nis.toLowerCase().includes(query) ||
                    (s.class_name && s.class_name.toLowerCase().includes(query)) ||
                    (s.room_name && s.room_name.toLowerCase().includes(query)) ||
                    (s.halaqoh_name && s.halaqoh_name.toLowerCase().includes(query))
                );
            }
        },
        watch: {
            currentGender() {
                this.fetchStudents();
            }
        },
        methods: {
            async fetchStudents() {
                this.loading = true;
                try {
                    const baseUrl = window.BASE_URL || '../../';
                    const res = await fetch(`${baseUrl}api/boarding.php?action=get_students&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) {
                        this.students = data.data;
                    } else {
                        console.error('API Error:', data.error);
                        alert('Gagal mengambil data: ' + data.error);
                    }
                } catch (e) {
                    console.error('Failed to fetch students', e);
                    alert('Gagal menghubungi server. Pastikan koneksi internet stabil.');
                } finally {
                    this.loading = false;
                }
            },
            async fetchMusyrif() {
                try {
                    const baseUrl = window.BASE_URL || '../../';
                    const res = await fetch(`${baseUrl}api/boarding.php?action=get_musyrif`);
                    const data = await res.json();
                    if (data.success) {
                        this.musyrifList = data.data;
                    }
                } catch (e) {}
            },
            async fetchRooms() {
                try {
                    const baseUrl = window.BASE_URL || '../../';
                    const res = await fetch(`${baseUrl}api/boarding_rooms.php?action=get_rooms&gender=all`);
                    const data = await res.json();
                    if (data.success) {
                        this.roomsList = data.data;
                    }
                } catch (e) {}
            },
            async fetchHalaqoh() {
                try {
                    const baseUrl = window.BASE_URL || '../../';
                    const res = await fetch(`${baseUrl}api/boarding_halaqoh.php?action=get_halaqoh&gender=all`);
                    const data = await res.json();
                    if (data.success) {
                        this.halaqohList = data.data;
                    }
                } catch (e) {}
            },
            onRoomChange() {
                const selectedRoom = this.roomsList.find(r => r.name === this.editForm.room_name);
                if (selectedRoom && selectedRoom.musyrif_id) {
                    this.editForm.musyrif_id = selectedRoom.musyrif_id;
                }
            },
            getStatusClass(status) {
                switch(status) {
                    case 'ACTIVE': return 'bg-green-100 text-green-700 border border-green-200';
                    case 'GRADUATED': return 'bg-blue-100 text-blue-700 border border-blue-200';
                    case 'OUT': return 'bg-red-100 text-red-700 border border-red-200';
                    default: return 'bg-green-100 text-green-700 border border-green-200';
                }
            },
            openEditModal(student) {
                this.editForm = {
                    student_id: student.id,
                    name: student.name,
                    nis: student.nis,
                    room_name: student.room_name || '',
                    halaqoh_id: student.halaqoh_id,
                    musyrif_id: student.musyrif_id,
                    boarding_status: student.boarding_status || 'ACTIVE',
                    remarks: student.remarks || ''
                };
                this.showEditModal = true;
            },
            async saveDetails() {
                this.saving = true;
                try {
                    const baseUrl = window.BASE_URL || '../../';
                    const res = await fetch(`${baseUrl}api/boarding.php?action=save_details`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.editForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showEditModal = false;
                        this.fetchStudents();
                    } else {
                        alert(data.error);
                    }
                } catch (e) {
                    alert('Error saving details');
                } finally {
                    this.saving = false;
                }
            }
        },
        mounted() {
            this.fetchStudents();
            this.fetchMusyrif();
            this.fetchRooms();
            this.fetchHalaqoh();
        }
    }).mount('#app')
</script>
</body>
</html>
