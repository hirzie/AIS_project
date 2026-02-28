<?php
require_once '../../config/database.php';
require_once '../../includes/header_inventory.php';
?>

<div class="flex flex-col h-screen overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
        <div class="flex items-center gap-4">
            <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/dashboard.php'; return false;" class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-800 text-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-cogs text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 leading-none">Pengaturan Inventory</h1>
                    <span class="text-xs text-slate-500 font-medium">Konfigurasi Master Data</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500">{{ currentDate }}</span>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden p-6 bg-slate-50 flex gap-6">
        <div class="w-64 flex flex-col gap-2">
            <button @click="activeTab = 'categories'" :class="activeTab === 'categories' ? 'bg-white text-emerald-600 shadow-md border-emerald-500' : 'text-slate-500 hover:bg-white/50'" class="text-left px-4 py-3 rounded-lg text-sm font-medium transition-all border-l-4 border-transparent flex items-center gap-3">
                <i class="fas fa-tags w-5"></i> Kategori Aset
            </button>
            <button @click="activeTab = 'locations'" :class="activeTab === 'locations' ? 'bg-white text-emerald-600 shadow-md border-emerald-500' : 'text-slate-500 hover:bg-white/50'" class="text-left px-4 py-3 rounded-lg text-sm font-medium transition-all border-l-4 border-transparent flex items-center gap-3">
                <i class="fas fa-map-marker-alt w-5"></i> Master Lokasi
            </button>
            <button @click="activeTab = 'depreciation'" :class="activeTab === 'depreciation' ? 'bg-white text-emerald-600 shadow-md border-emerald-500' : 'text-slate-500 hover:bg-white/50'" class="text-left px-4 py-3 rounded-lg text-sm font-medium transition-all border-l-4 border-transparent flex items-center gap-3">
                <i class="fas fa-calculator w-5"></i> Aturan Penyusutan
            </button>
            <button @click="activeTab = 'divisions'" :class="activeTab === 'divisions' ? 'bg-white text-emerald-600 shadow-md border-emerald-500' : 'text-slate-500 hover:bg-white/50'" class="text-left px-4 py-3 rounded-lg text-sm font-medium transition-all border-l-4 border-transparent flex items-center gap-3">
                <i class="fas fa-layer-group w-5"></i> Divisi
            </button>
            <button @click="activeTab = 'import'" :class="activeTab === 'import' ? 'bg-white text-emerald-600 shadow-md border-emerald-500' : 'text-slate-500 hover:bg-white/50'" class="text-left px-4 py-3 rounded-lg text-sm font-medium transition-all border-l-4 border-transparent flex items-center gap-3">
                <i class="fas fa-file-import w-5"></i> Import Aset
            </button>
        </div>

        <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 overflow-y-auto">
            <div v-if="activeTab === 'categories'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-slate-800">Kategori Aset</h2>
                    <button @click="openCatModal()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm hover:bg-emerald-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Kategori
                    </button>
                </div>
                <div class="space-y-3">
                    <div v-for="cat in categories" :key="cat.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm font-bold text-xs">{{ cat.code }}</div>
                            <div>
                                <h3 class="font-bold text-slate-700">{{ cat.name }}</h3>
                                <p class="text-xs text-slate-500">{{ cat.description }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openCatModal(cat)" class="text-slate-400 hover:text-blue-600 px-2"><i class="fas fa-edit"></i></button>
                            <button @click="deleteCategory(cat)" class="text-slate-400 hover:text-red-600 px-2"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'locations'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-slate-800">Master Lokasi</h2>
                    <button @click="openLocModal()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm hover:bg-emerald-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Lokasi
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="loc in locations" :key="loc.id" class="p-4 bg-slate-50 rounded-xl border border-slate-100 relative group">
                        <h3 class="font-bold text-slate-700">{{ loc.name }}</h3>
                        <p class="text-xs text-slate-500">{{ loc.type }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ loc.description }}</p>
                        <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                             <button @click="openLocModal(loc)" class="text-slate-300 hover:text-blue-500"><i class="fas fa-edit"></i></button>
                             <button @click="deleteLocation(loc)" class="text-slate-300 hover:text-red-500"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>

             <div v-if="activeTab === 'depreciation'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-slate-800">Aturan Penyusutan</h2>
                </div>
                <div class="p-8 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <i class="fas fa-hard-hat text-4xl mb-4"></i>
                    <p>Fitur dalam pengembangan</p>
                </div>
            </div>
            <div v-if="activeTab === 'divisions'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-slate-800">Divisi</h2>
                    <button @click="openDivModal()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm hover:bg-emerald-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Divisi
                    </button>
                </div>
                <div class="space-y-3">
                    <div v-for="d in divisions" :key="d.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm font-bold text-xs">{{ d.code }}</div>
                            <div>
                                <h3 class="font-bold text-slate-700">{{ d.name }}</h3>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openDivModal(d)" class="text-slate-400 hover:text-blue-600 px-2"><i class="fas fa-edit"></i></button>
                            <button @click="deleteDivision(d)" class="text-slate-400 hover:text-red-600 px-2"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="activeTab === 'import'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-slate-800">Import Aset Bergerak</h2>
                </div>
                <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-sm text-slate-600 mb-3">Unggah file CSV dengan kolom: name, code, category_code, location, quantity, condition_status, division_code</p>
                    <input type="file" @change="onFileChange" accept=".csv" class="border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                    <div class="mt-3">
                        <button @click="uploadImport" :disabled="!importFile || uploading" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm hover:bg-emerald-700 disabled:opacity-50">
                            {{ uploading ? 'Mengunggah...' : 'Unggah & Import' }}
                        </button>
                    </div>
                    <div v-if="importResult" class="mt-3 text-sm text-slate-700">Import: {{ importResult.count }} baris</div>
                </div>
            </div>
        </div>
    </main>

    <div v-if="showCatModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">{{ formCat.id ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                <button @click="showCatModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Kategori</label>
                    <input type="text" v-model="formCat.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="showCatModal = false" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="saveCategory" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-200">Simpan</button>
            </div>
        </div>
    </div>

    <div v-if="showLocModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">{{ formLoc.id ? 'Edit Lokasi' : 'Tambah Lokasi' }}</h3>
                <button @click="showLocModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lokasi</label>
                    <input type="text" v-model="formLoc.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="showLocModal = false" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="saveLocation" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-200">Simpan</button>
            </div>
        </div>
    </div>
    <div v-if="showDivModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">{{ formDiv.id ? 'Edit Divisi' : 'Tambah Divisi' }}</h3>
                <button @click="showDivModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Kode Divisi</label>
                    <input type="text" v-model="formDiv.code" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Divisi</label>
                    <input type="text" v-model="formDiv.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="showDivModal = false" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="saveDivision" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-200">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                activeTab: 'categories',
                categories: [],
                locations: [],
                divisions: [],
                showCatModal: false,
                showLocModal: false,
                showDivModal: false,
                formCat: { id: null, name: '', description: '' },
                formLoc: { id: null, name: '', type: '', description: '' }
                , formDiv: { id: null, code: '', name: '' }
                , importFile: null, uploading: false, importResult: null
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        },
        methods: {
            async fetchCategories() {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_categories');
                const data = await res.json();
                if (data.success) this.categories = data.data;
            },
            async fetchLocations() {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_locations');
                const data = await res.json();
                if (data.success) this.locations = data.data;
            },
            async fetchDivisions() {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_divisions');
                const data = await res.json();
                if (data.success) this.divisions = data.data;
            },
            openCatModal(cat = null) {
                this.formCat = cat ? { ...cat } : { id: null, name: '', description: '' };
                this.showCatModal = true;
            },
            openLocModal(loc = null) {
                this.formLoc = loc ? { ...loc } : { id: null, name: '', type: '', description: '' };
                this.showLocModal = true;
            },
            openDivModal(d = null) {
                this.formDiv = d ? { ...d } : { id: null, code: '', name: '' };
                this.showDivModal = true;
            },
            async saveCategory() {
                if (!this.formCat.name) return alert('Nama kategori wajib diisi!');
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_category', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formCat)
                });
                const data = await res.json();
                if (data.success) {
                    this.showCatModal = false;
                    this.fetchCategories();
                } else {
                    alert(data.message);
                }
            },
            async deleteCategory(cat) {
                if (!confirm(`Hapus kategori ${cat.name}?`)) return;
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_category', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: cat.id })
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchCategories();
                } else {
                    alert(data.message);
                }
            },
            async saveLocation() {
                if (!this.formLoc.name) return alert('Nama lokasi wajib diisi!');
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formLoc)
                });
                const data = await res.json();
                if (data.success) {
                    this.showLocModal = false;
                    this.fetchLocations();
                } else {
                    alert(data.message);
                }
            },
            async deleteLocation(loc) {
                if (!confirm(`Hapus lokasi ${loc.name}?`)) return;
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: loc.id })
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchLocations();
                } else {
                    alert(data.message);
                }
            },
            async saveDivision() {
                if (!this.formDiv.code || !this.formDiv.name) return alert('Kode dan nama wajib diisi!');
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_division', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formDiv)
                });
                const data = await res.json();
                if (data.success) {
                    this.showDivModal = false;
                    this.fetchDivisions();
                } else {
                    alert(data.message);
                }
            },
            async deleteDivision(d) {
                if (!confirm(`Hapus divisi ${d.name}?`)) return;
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_division', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: d.id })
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchDivisions();
                } else {
                    alert(data.message);
                }
            },
            onFileChange(e) {
                this.importFile = e.target.files && e.target.files[0] ? e.target.files[0] : null;
            },
            async uploadImport() {
                if (!this.importFile) return;
                this.uploading = true;
                try {
                    const fd = new FormData();
                    fd.append('file', this.importFile);
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=import_movable', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.importResult = data.data;
                        alert('Import berhasil');
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    alert('Gagal import');
                } finally {
                    this.uploading = false;
                }
            }
        },
        mounted() {
            this.fetchCategories();
            this.fetchLocations();
            this.fetchDivisions();
        }
    }).mount('#app')
</script>
</body>
</html>
