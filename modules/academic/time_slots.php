<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<script>
    window.USE_GLOBAL_APP = false;
    window.SKIP_GLOBAL_APP = true;
</script>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-hidden relative flex">
        <!-- SIDEBAR KATEGORI -->
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col z-20">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Kategori Jadwal</h3>
                <button @click="openCategoryModal()" class="text-blue-600 hover:text-blue-800 text-sm font-bold bg-blue-50 px-2 py-1 rounded">
                    <i class="fas fa-plus"></i> Baru
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                <div v-for="cat in sortedCategories" :key="cat.id" 
                     @click="selectCategory(cat)"
                     class="p-3 rounded-lg border cursor-pointer transition-all relative group"
                     :class="activeCategoryId === cat.id ? 'bg-blue-50 border-blue-200 shadow-sm' : 'bg-white border-slate-100 hover:border-blue-100 hover:bg-slate-50'">
                    
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-bold text-slate-800" :class="activeCategoryId === cat.id ? 'text-blue-800' : ''">
                            {{ cat.name }}
                            <span v-if="getYearName(cat.academic_year_id)" class="text-[10px] font-normal text-slate-500 bg-slate-100 px-1 rounded ml-1">
                                {{ getYearName(cat.academic_year_id) }}
                            </span>
                        </span>
                        <span v-if="cat.is_active" class="bg-green-100 text-green-700 text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Aktif</span>
                    </div>
                    <p class="text-xs text-slate-500 line-clamp-2 mb-2">{{ cat.description || 'Tidak ada deskripsi' }}</p>
                    
                    <div class="flex gap-2 text-xs border-t border-slate-200/50 pt-2 mt-1" v-if="activeCategoryId === cat.id">
                        <button @click.stop="openCategoryModal(cat)" class="text-slate-500 hover:text-blue-600 flex items-center gap-1">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button @click.stop="openDuplicateCategoryModal(cat)" class="text-slate-500 hover:text-indigo-600 flex items-center gap-1">
                            <i class="fas fa-copy"></i> Duplikat
                        </button>
                        <button v-if="!cat.is_active" @click.stop="setCategoryActive(cat)" class="text-slate-500 hover:text-green-600 flex items-center gap-1">
                            <i class="fas fa-check-circle"></i> Set Aktif
                        </button>
                        <button @click.stop="deleteCategory(cat)" class="text-slate-500 hover:text-red-600 ml-auto flex items-center gap-1">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div v-if="sortedCategories.length === 0" class="text-center p-8 text-slate-400 text-sm">
                    Belum ada kategori jadwal.
                    <button @click="openCategoryModal()" class="text-blue-600 font-bold hover:underline mt-2 block mx-auto">Buat Kategori</button>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
            <div class="max-w-5xl mx-auto">
                <div v-if="activeCategoryId">
                    <div class="flex items-center gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                                <i class="fas fa-clock"></i>
                                <span>Pengaturan Jam Pelajaran</span>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                                {{ activeCategory ? activeCategory.name : 'Memuat...' }}
                                <span v-if="activeCategory && activeCategory.is_active" class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold">Aktif Digunakan</span>
                            </h2>
                        </div>
                        <div class="flex-1 text-right">
                            <button @click="generateTimeslots" class="bg-white border border-slate-300 text-slate-600 px-4 py-2 rounded-lg text-sm hover:bg-slate-50 mr-2 shadow-sm transition-all">
                                <i class="fas fa-magic mr-2 text-indigo-500"></i>Generate Default
                            </button>
                            <button @click="openTimeSlotModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 shadow-md transition-all shadow-blue-200">
                                <i class="fas fa-plus mr-2"></i>Tambah Slot
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 w-20 text-center">Urutan</th>
                                    <th class="px-6 py-3">Waktu Mulai</th>
                                    <th class="px-6 py-3">Waktu Selesai</th>
                                    <th class="px-6 py-3 text-center">Tipe</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="slot in activeTimeslots" :key="slot.id" class="hover:bg-slate-50 transition-colors" :class="slot.isBreak ? 'bg-amber-50/30' : ''">
                                    <td class="px-6 py-4 text-center font-bold text-slate-500">{{ slot.name }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-700">{{ slot.start }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-700">{{ slot.end }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="slot.isBreak" class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs font-bold uppercase border border-amber-200">Istirahat</span>
                                        <span v-else class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-bold uppercase border border-blue-100">Pelajaran</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="openTimeSlotModal(slot)" class="text-blue-600 hover:text-blue-800 mr-3 transition-colors"><i class="fas fa-edit"></i></button>
                                        <button @click="deleteTimeSlot(slot)" class="text-slate-400 hover:text-red-600 transition-colors"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr v-if="activeTimeslots.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                        <i class="fas fa-clock text-4xl mb-3 text-slate-300"></i>
                                        <p>Belum ada slot waktu di kategori ini.</p>
                                        <button @click="generateTimeslots" class="text-blue-600 font-bold hover:underline mt-2">Generate Default</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div v-else class="flex flex-col items-center justify-center h-full text-slate-400 py-20">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </div>
                    <p class="text-lg font-medium">Pilih kategori jadwal di sebelah kiri</p>
                    <p class="text-sm">atau buat kategori baru untuk memulai</p>
                </div>
            </div>
        </div>

        <!-- MODAL: CATEGORY -->
        <div v-if="showCategoryModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">{{ categoryForm.id ? 'Edit' : 'Buat' }} Kategori Jadwal</h3>
                    <button @click="showCategoryModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveCategory">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tahun Akademik</label>
                            <select v-model="categoryForm.academic_year_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" required>
                                <option :value="null" disabled>Pilih Tahun Akademik</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                    {{ year.name }} <span v-if="year.status === 'ACTIVE' || year.status === 'Aktif'">(Aktif)</span>
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Kategori</label>
                            <input type="text" v-model="categoryForm.name" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Contoh: Reguler, Ramadhan, Ujian" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Deskripsi (Opsional)</label>
                            <textarea v-model="categoryForm.description" class="w-full border border-slate-300 rounded-lg px-3 py-2 h-24 resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Keterangan tambahan..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showCategoryModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 font-medium">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-md shadow-blue-200">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: DUPLICATE CATEGORY -->
        <div v-if="showDuplicateCategoryModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">Duplikat Kategori</h3>
                    <button @click="showDuplicateCategoryModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6">
                    <div class="mb-4 p-3 bg-blue-50 text-blue-700 rounded-lg text-sm flex gap-3 items-start">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <div>
                            <p class="font-bold">Duplikasi dari: {{ duplicateCategoryForm.source_name }}</p>
                            <p class="opacity-80">Semua slot waktu akan disalin ke kategori baru.</p>
                        </div>
                    </div>
                    <form @submit.prevent="saveDuplicateCategory">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tahun Akademik Tujuan</label>
                            <select v-model="duplicateCategoryForm.academic_year_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" required>
                                <option :value="null" disabled>Pilih Tahun Akademik</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                    {{ year.name }} <span v-if="year.status === 'ACTIVE' || year.status === 'Aktif'">(Aktif)</span>
                                </option>
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Kategori Baru</label>
                            <input type="text" v-model="duplicateCategoryForm.new_name" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Contoh: Reguler (Copy)" required>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showDuplicateCategoryModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 font-medium">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold shadow-md shadow-indigo-200">Duplikat Sekarang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: MANAGE TIMESLOT -->
        <div v-if="showTimeSlotModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden transform transition-all">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-xl font-bold text-slate-800">{{ timeSlotForm.id ? 'Edit' : 'Tambah' }} Slot Waktu</h3>
                    <button @click="showTimeSlotModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveTimeSlot">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Slot / Urutan</label>
                            <input type="text" v-model="timeSlotForm.name" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Contoh: Jam Ke-1" required>
                        </div>
                        <div class="flex gap-4 mb-4">
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Mulai</label>
                                <input type="time" v-model="timeSlotForm.start_time" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Selesai</label>
                                <input type="time" v-model="timeSlotForm.end_time" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                            </div>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Slot</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="timeSlotForm.is_break" :value="false" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm">Jam Pelajaran</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="timeSlotForm.is_break" :value="true" class="text-amber-600 focus:ring-amber-500">
                                    <span class="text-sm">Istirahat</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showTimeSlotModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script type="module">
    import { academicMixin } from '../../assets/js/modules/academic.js';
    import { adminMixin } from '../../assets/js/modules/admin.js';
    const { createApp } = Vue;
    const app = createApp({
        mixins: [academicMixin],
        data() {
            return {
                // Category State
                activeCategoryId: null,
                showCategoryModal: false,
                categoryForm: { id: null, name: '', description: '', academic_year_id: null },
                
                // Duplicate Category State
                showDuplicateCategoryModal: false,
                duplicateCategoryForm: { source_id: null, source_name: '', new_name: '', academic_year_id: null }
            };
        },
        computed: {
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            },
            sortedCategories() {
                if (!this.unitData || !this.unitData.scheduleCategories) return [];
                return this.unitData.scheduleCategories;
            },
            academicYears() {
                return this.unitData.years || [];
            },
            activeYearId() {
                const active = this.academicYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif');
                return active ? active.id : (this.academicYears[0] ? this.academicYears[0].id : null);
            },
            activeCategory() {
                if (!this.activeCategoryId) return null;
                return this.sortedCategories.find(c => c.id == this.activeCategoryId);
            },
            // OVERRIDE: Filter timeslots by activeCategoryId
            activeTimeslots() {
                if (!this.unitData || !this.unitData.timeSlots || !this.activeCategoryId) return [];
                
                return this.unitData.timeSlots
                    .filter(slot => slot.category_id == this.activeCategoryId)
                    .sort((a, b) => {
                        const ta = parseInt(a.start.replace(':', ''));
                        const tb = parseInt(b.start.replace(':', ''));
                        return ta - tb;
                    });
            }
        },
        watch: {
            'unitData.scheduleCategories': {
                handler(newVal) {
                    // Auto-select first category if none selected
                    if (newVal && newVal.length > 0 && !this.activeCategoryId) {
                        // Prefer active one, otherwise first
                        const active = newVal.find(c => c.is_active);
                        this.activeCategoryId = active ? active.id : newVal[0].id;
                    }
                },
                deep: true
            }
        },
        methods: {
            getYearName(yearId) {
                if (!yearId || !this.unitData.years) return '';
                const year = this.unitData.years.find(y => y.id == yearId);
                return year ? year.name : '';
            },
            // CATEGORY METHODS
            selectCategory(cat) {
                this.activeCategoryId = cat.id;
            },
            openCategoryModal(cat = null) {
                if (cat) {
                    this.categoryForm = { 
                        id: cat.id, 
                        name: cat.name, 
                        description: cat.description || '',
                        academic_year_id: cat.academic_year_id || this.activeYearId 
                    };
                } else {
                    this.categoryForm = { 
                        id: null, 
                        name: '', 
                        description: '',
                        academic_year_id: this.activeYearId 
                    };
                }
                this.showCategoryModal = true;
            },
            async saveCategory() {
                if (!this.getUnitId(this.currentUnit)) return;
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const payload = {
                        ...this.categoryForm,
                        unit_id: this.getUnitId(this.currentUnit),
                        action: this.categoryForm.id ? 'update_category' : 'create_category'
                    };
                    const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.showCategoryModal = false;
                        this.fetchAcademicData(this.currentUnit);
                        // If created new, it might not be auto-selected unless we handle it, 
                        // but fetchAcademicData refresh will trigger watcher if activeCategoryId was null.
                    } else {
                        alert(result.error);
                    }
                } catch(e) { console.error(e); }
            },
            openDuplicateCategoryModal(cat) {
                this.duplicateCategoryForm = {
                    source_id: cat.id,
                    source_name: cat.name,
                    new_name: cat.name + ' (Copy)',
                    academic_year_id: this.activeYearId
                };
                this.showDuplicateCategoryModal = true;
            },
            async saveDuplicateCategory() {
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            action: 'duplicate_category',
                            ...this.duplicateCategoryForm
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.showDuplicateCategoryModal = false;
                        this.fetchAcademicData(this.currentUnit);
                        alert('Kategori berhasil diduplikasi!');
                    } else {
                        alert(result.error);
                    }
                } catch(e) { console.error(e); }
            },
            async deleteCategory(cat) {
                if (!confirm(`Hapus kategori "${cat.name}"? Semua slot waktu di dalamnya akan ikut terhapus!`)) return;
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const payload = { id: cat.id, action: 'delete_category' };
                    const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        if (this.activeCategoryId === cat.id) this.activeCategoryId = null;
                        this.fetchAcademicData(this.currentUnit);
                    } else {
                        alert(result.error);
                    }
                } catch(e) { console.error(e); }
            },
            async setCategoryActive(cat) {
                if (!confirm(`Set kategori "${cat.name}" sebagai AKTIF? Ini akan mengubah jadwal pelajaran yang berlaku saat ini.`)) return;
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const payload = { id: cat.id, action: 'set_active_category' };
                    const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.fetchAcademicData(this.currentUnit);
                        alert('Kategori aktif berhasil diubah.');
                    } else {
                        alert(result.error);
                    }
                } catch(e) { console.error(e); }
            },

            // OVERRIDE TIMESLOT METHODS
            async saveTimeSlot() {
                if (!this.getUnitId(this.currentUnit)) {
                    alert('Silakan pilih unit spesifik (TK/SD/SMA) terlebih dahulu!');
                    return;
                }
                if (!this.activeCategoryId) {
                    alert('Pilih kategori jadwal terlebih dahulu!');
                    return;
                }
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const payload = {
                        ...this.timeSlotForm,
                        unit_id: this.getUnitId(this.currentUnit),
                        category_id: this.activeCategoryId, // ADDED
                        action: this.timeSlotForm.id ? 'update' : 'create'
                    };
                    const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        // alert(result.message); // Optional
                        this.showTimeSlotModal = false;
                        // Refresh data (passing activeCategoryId to avoid prefetch skip issue?)
                        // Actually prefetch logic I added earlier was: if categoryId passed, skip prefetch.
                        // Here we just want to reload everything.
                        this.fetchAcademicData(this.currentUnit);
                    } else {
                        alert(result.error);
                    }
                } catch(e) { console.error(e); }
            },
            async generateTimeslots() {
                if (!this.getUnitId(this.currentUnit)) {
                    alert('Pilih unit terlebih dahulu!');
                    return;
                }
                if (!this.activeCategoryId) {
                    alert('Pilih kategori jadwal terlebih dahulu!');
                    return;
                }
                if (!confirm('Generate otomatis akan menghapus slot waktu yang ada di kategori ini dan menggantinya dengan default. Lanjutkan?')) return;
                
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const response = await fetch(baseUrl + 'api/generate_timeslots.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ 
                            unit_id: this.getUnitId(this.currentUnit),
                            category_id: this.activeCategoryId // ADDED to generate_timeslots.php support?
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        this.fetchAcademicData(this.currentUnit);
                    } else {
                        alert(result.error);
                    }
                } catch(e) { console.error(e); }
            }
        }
    });
    app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
