<?php
require_once '../../config/database.php';
require_once '../../includes/header_inventory.php';
?>

<script>window.USE_GLOBAL_APP=false;</script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    try{document.querySelectorAll('[v-cloak]').forEach(function(n){n.removeAttribute('v-cloak');});}catch(_){}
});
</script>

<div id="movableApp" v-cloak class="flex flex-col h-screen overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
        <div class="flex items-center gap-4">
            <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/dashboard.php'; return false;" class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chair text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 leading-none">Aset Bergerak</h1>
                    <span class="text-xs text-slate-500 font-medium">Mebel, Elektronik, & Peralatan</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <button class="text-slate-500 hover:text-slate-700 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-barcode"></i> Scan Barcode
            </button>
            <div class="h-6 w-px bg-slate-300"></div>
            <span class="text-sm font-medium text-slate-500">{{ currentDate }}</span>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden p-4 bg-slate-100 flex flex-col">
        <div class="bg-white p-4 rounded-t-xl border-b border-slate-100 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" v-model="searchQuery" placeholder="Cari barang / kode..." class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
                </div>
                <select v-model="filterCategory" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 focus:outline-none">
                    <option value="">Semua Kategori</option>
                    <option v-for="c in categories" :value="c.name">{{ c.name }}</option>
                </select>
                <select v-model="filterCondition" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 focus:outline-none">
                    <option value="">Semua Kondisi</option>
                    <option value="GOOD">Baik</option>
                    <option value="DAMAGED_LIGHT">Rusak Ringan</option>
                    <option value="DAMAGED_HEAVY">Rusak Berat</option>
                </select>
                <select v-model="filterDivision" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 focus:outline-none">
                    <option value="">Semua Divisi</option>
                    <option v-for="d in divisions" :value="d.code">{{ d.name }}</option>
                </select>
                <button @click="fetchData" class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200">Terapkan</button>
            </div>
            <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm shadow-blue-200">
                <i class="fas fa-plus"></i> Input Barang
            </button>
        </div>

        <div class="bg-white flex-1 rounded-b-xl shadow-sm overflow-hidden flex flex-col">
            <div class="overflow-x-auto flex-1 custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 w-16">No</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Kode Barang</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Nama Barang</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Kategori</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Lokasi</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-center">Jumlah</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Kondisi</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in filteredItems" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-sm text-slate-500">{{ index + 1 }}</td>
                            <td class="p-4 text-sm font-mono font-bold text-slate-700 bg-slate-50 rounded w-32 text-center">{{ item.code }}</td>
                            <td class="p-4 text-sm font-medium text-slate-800">{{ item.name }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ item.category_name }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ item.location }}</td>
                            <td class="p-4 text-sm text-slate-600 text-center">{{ item.quantity }} Unit</td>
                            <td class="p-4 text-sm">
                                <span :class="getConditionClass(item.condition_status)" class="px-2 py-1 rounded text-xs font-bold">{{ formatCondition(item.condition_status) }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <button @click="openModal(item)" class="text-slate-400 hover:text-blue-600 mx-1"><i class="fas fa-edit"></i></button>
                                <button @click="deleteItem(item)" class="text-slate-400 hover:text-red-600 mx-1"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="filteredItems.length === 0">
                            <td colspan="8" class="p-8 text-center text-slate-500 italic">Data tidak ditemukan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">{{ form.id ? 'Edit Barang' : 'Tambah Barang Baru' }}</h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Kode Barang</label>
                        <input type="text" v-model="form.code" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Contoh: FUR-001">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Kategori</label>
                        <select v-model="form.category_id" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option v-for="c in categories" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Divisi</label>
                    <select v-model="form.division_code" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        <option value="">- Pilih Divisi -</option>
                        <option v-for="d in divisions" :value="d.code">{{ d.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Barang</label>
                    <input type="text" v-model="form.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Nama barang...">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi</label>
                        <input type="text" v-model="form.location" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Ruang Kelas, Gudang...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Jumlah</label>
                        <input type="number" v-model="form.quantity" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" min="1">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Kondisi</label>
                        <select v-model="form.condition_status" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="GOOD">Baik</option>
                            <option value="DAMAGED_LIGHT">Rusak Ringan</option>
                            <option value="DAMAGED_HEAVY">Rusak Berat</option>
                            <option value="LOST">Hilang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Beli</label>
                        <input type="date" v-model="form.purchase_date" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Harga Beli (Satuan)</label>
                    <input type="number" v-model="form.purchase_price" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="0">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="closeModal" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="saveItem" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200">Simpan</button>
            </div>
        </div>
    </div>
    
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                searchQuery: '',
                filterCategory: '',
                filterCondition: '',
                filterDivision: '',
                items: [],
                categories: [],
                divisions: [],
                showModal: false,
                form: {
                    id: null,
                    code: '',
                    name: '',
                    category_id: '',
                    location: '',
                    quantity: 1,
                    condition_status: 'GOOD',
                    purchase_date: '',
                    purchase_price: 0,
                    division_code: ''
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredItems() {
                let res = this.items;
                if (this.searchQuery) {
                    const lower = this.searchQuery.toLowerCase();
                    res = res.filter(item => 
                        item.name.toLowerCase().includes(lower) || 
                        item.code.toLowerCase().includes(lower)
                    );
                }
                if (this.filterCategory) {
                    res = res.filter(item => item.category_name === this.filterCategory);
                }
                if (this.filterCondition) {
                    res = res.filter(item => item.condition_status === this.filterCondition);
                }
                return res;
            }
        },
        methods: {
            async fetchCategories() {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_categories');
                const data = await res.json();
                if(data.success) this.categories = data.data;
            },
            async fetchDivisions() {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_divisions');
                const data = await res.json();
                if (data.success) this.divisions = data.data;
            },
            async fetchData() {
                const url = window.BASE_URL + 'api/inventory.php?action=get_movable' + (this.filterDivision ? ('&division=' + encodeURIComponent(this.filterDivision)) : '');
                const res = await fetch(url);
                const data = await res.json();
                if(data.success) this.items = data.data;
            },
            getConditionClass(condition) {
                if (condition === 'GOOD') return 'bg-emerald-100 text-emerald-700';
                if (condition === 'DAMAGED_LIGHT') return 'bg-yellow-100 text-yellow-700';
                if (condition === 'DAMAGED_HEAVY') return 'bg-red-100 text-red-700';
                return 'bg-slate-100 text-slate-700';
            },
            formatCondition(condition) {
                const map = {
                    'GOOD': 'Baik',
                    'DAMAGED_LIGHT': 'Rusak Ringan',
                    'DAMAGED_HEAVY': 'Rusak Berat',
                    'LOST': 'Hilang'
                };
                return map[condition] || condition;
            },
            openModal(item = null) {
                if (item) {
                    this.form = { ...item };
                } else {
                    this.form = {
                        id: null,
                        code: 'INV-' + Date.now().toString().slice(-6),
                        name: '',
                        category_id: this.categories.length ? this.categories[0].id : '',
                        location: '',
                        quantity: 1,
                        condition_status: 'GOOD',
                        purchase_date: new Date().toISOString().slice(0, 10),
                        purchase_price: 0,
                        division_code: this.filterDivision || ''
                    };
                }
                this.showModal = true;
            },
            closeModal() {
                this.showModal = false;
            },
            async saveItem() {
                if (!this.form.name || !this.form.code) return alert('Nama dan Kode wajib diisi!');
                
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_movable', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(data.message);
                        this.closeModal();
                        this.fetchData();
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan');
                }
            },
            async deleteItem(item) {
                if (!confirm(`Hapus barang ${item.name}?`)) return;
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_movable', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: item.id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchData();
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        },
        async mounted() {
            this.fetchCategories();
            await this.fetchDivisions();
            const params = new URLSearchParams(window.location.search || '');
            const div = params.get('division');
            if (div) this.filterDivision = div;
            await this.fetchData();
        }
    }).mount('#movableApp')
</script>
</body>
</html>
