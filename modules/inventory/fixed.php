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
                <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 leading-none">Aset Tidak Bergerak</h1>
                    <span class="text-xs text-slate-500 font-medium">Tanah, Bangunan, & Infrastruktur</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <button class="text-slate-500 hover:text-slate-700 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-file-export"></i> Export Laporan
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
                    <input type="text" v-model="searchQuery" placeholder="Cari aset..." class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 w-64">
                </div>
                <select v-model="filterType" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 focus:outline-none">
                    <option value="">Semua Jenis</option>
                    <option value="LAND">Tanah</option>
                    <option value="BUILDING">Bangunan</option>
                </select>
            </div>
            <button @click="openModal()" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm shadow-amber-200">
                <i class="fas fa-plus"></i> Tambah Aset Baru
            </button>
        </div>

        <div class="bg-white flex-1 rounded-b-xl shadow-sm overflow-hidden flex flex-col">
            <div class="overflow-x-auto flex-1 custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 w-16">No</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Nama Aset</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Jenis</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Lokasi</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Thn Beli</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Nilai Awal</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-center">Penyusutan</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Nilai Kini</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in filteredItems" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-sm text-slate-500">{{ index + 1 }}</td>
                            <td class="p-4 text-sm font-medium text-slate-800">{{ item.name }}</td>
                            <td class="p-4 text-sm text-slate-600">
                                <span :class="item.type === 'LAND' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-1 rounded text-xs font-bold">{{ item.type === 'LAND' ? 'Tanah' : 'Bangunan' }}</span>
                            </td>
                            <td class="p-4 text-sm text-slate-600">{{ item.location }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ item.purchase_year }}</td>
                            <td class="p-4 text-sm text-slate-600 text-right">{{ formatCurrency(item.purchase_price) }}</td>
                            <td class="p-4 text-sm text-red-500 text-center">-{{ item.depreciation_rate }}% / thn</td>
                            <td class="p-4 text-sm font-bold text-slate-800 text-right">{{ formatCurrency(calculateDepreciation(item.purchase_price, item.purchase_year, item.depreciation_rate)) }}</td>
                            <td class="p-4 text-center">
                                <button @click="openModal(item)" class="text-slate-400 hover:text-amber-600 mx-1"><i class="fas fa-edit"></i></button>
                                <button @click="deleteItem(item)" class="text-slate-400 hover:text-red-600 mx-1"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                         <tr v-if="filteredItems.length === 0">
                            <td colspan="9" class="p-8 text-center text-slate-500 italic">Data tidak ditemukan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">{{ form.id ? 'Edit Aset' : 'Tambah Aset Baru' }}</h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Aset</label>
                    <input type="text" v-model="form.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="Contoh: Gedung A">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Jenis Aset</label>
                        <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500">
                            <option value="LAND">Tanah</option>
                            <option value="BUILDING">Bangunan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi</label>
                        <input type="text" v-model="form.location" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="Lokasi...">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tahun Beli</label>
                        <input type="number" v-model="form.purchase_year" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="YYYY">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Harga Perolehan</label>
                        <input type="number" v-model="form.purchase_price" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="0">
                    </div>
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Penyusutan (%/thn)</label>
                        <input type="number" v-model="form.depreciation_rate" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Luas (m2)</label>
                        <input type="text" v-model="form.area_size" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="Contoh: 500 m2">
                    </div>
                </div>
                 <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Sertifikat / Dokumen</label>
                    <input type="text" v-model="form.certificate_number" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-amber-500" placeholder="Nomor...">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="closeModal" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="saveItem" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-lg shadow-amber-200">Simpan</button>
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
                filterType: '',
                items: [],
                showModal: false,
                form: {
                    id: null,
                    name: '',
                    type: 'BUILDING',
                    location: '',
                    purchase_year: new Date().getFullYear(),
                    purchase_price: 0,
                    depreciation_rate: 0,
                    area_size: '',
                    certificate_number: '',
                    status: 'ACTIVE'
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
                    res = res.filter(item => item.name.toLowerCase().includes(lower));
                }
                if (this.filterType) {
                    res = res.filter(item => item.type === this.filterType);
                }
                return res;
            }
        },
        methods: {
            async fetchData() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_fixed');
                    const data = await res.json();
                    if(data.success) this.items = data.data;
                } catch (e) { console.error(e); }
            },
            formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
            },
            calculateDepreciation(price, year, rate) {
                const age = new Date().getFullYear() - year;
                if (!rate || rate == 0) return price;
                const totalDep = price * (rate/100) * age;
                return Math.max(0, price - totalDep);
            },
            openModal(item = null) {
                if (item) {
                    this.form = { ...item };
                } else {
                    this.form = {
                        id: null,
                        name: '',
                        type: 'BUILDING',
                        location: '',
                        purchase_year: new Date().getFullYear(),
                        purchase_price: 0,
                        depreciation_rate: 0,
                        area_size: '',
                        certificate_number: '',
                        status: 'ACTIVE'
                    };
                }
                this.showModal = true;
            },
            closeModal() {
                this.showModal = false;
            },
            async saveItem() {
                if (!this.form.name) return alert('Nama Aset wajib diisi!');
                
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_fixed', {
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
                if (!confirm(`Hapus aset ${item.name}?`)) return;
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_fixed', {
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
        mounted() {
            this.fetchData();
        }
    }).mount('#app')
</script>
</body>
</html>
