<?php
require_once '../../config/database.php';
require_once '../../includes/header_personnel.php';
?>

<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/personnel/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Status Kepegawaian</h1>
            <span class="text-xs text-slate-500 font-medium">Manajemen Referensi Status Pegawai</span>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
    </div>
</nav>

<main class="flex-1 overflow-y-auto p-8 bg-slate-50" id="app">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div class="relative">
                <input type="text" v-model="searchQuery" placeholder="Cari status..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-64">
                <i class="fas fa-search absolute left-3 top-3 text-slate-400"></i>
            </div>
            <button @click="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> Tambah Status
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-semibold">Nama Status</th>
                        <th class="px-6 py-4 font-semibold">Keterangan</th>
                        <th class="px-6 py-4 font-semibold text-center">Status Aktif</th>
                        <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="4" class="py-8 text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl"></i></td>
                    </tr>
                    <tr v-else-if="filteredList.length === 0" class="text-center">
                        <td colspan="4" class="py-8 text-slate-400">Belum ada data status kepegawaian.</td>
                    </tr>
                    <tr v-for="item in filteredList" :key="item.id" class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="font-medium text-slate-800">{{ item.name }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ item.description || '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :class="item.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ item.is_active == 1 ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editItem(item)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center justify-center" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button @click="confirmDelete(item)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors flex items-center justify-center" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click.self="showModal = false">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all scale-100">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800">{{ isEdit ? 'Edit Status' : 'Tambah Status' }}</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        
        <form @submit.prevent="saveItem" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Status <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Contoh: Tetap, Kontrak">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Keterangan</label>
                <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Deskripsi singkat..."></textarea>
            </div>
            
            <div class="flex items-center gap-2">
                <input type="checkbox" id="isActive" v-model="form.is_active" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                <label for="isActive" class="text-sm text-slate-700">Status Aktif</label>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" @click="showModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                    <i v-if="saving" class="fas fa-circle-notch fa-spin"></i>
                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>
</div>

<div v-if="confirmModal.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden p-6 text-center">
        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-lg mb-2">{{ confirmModal.title }}</h3>
        <p class="text-slate-500 text-sm mb-6">{{ confirmModal.message }}</p>
        <div class="flex justify_center gap-3">
            <button @click="confirmModal.show = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium transition-colors">Batal</button>
            <button @click="executeConfirm" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                statusList: [],
                loading: false,
                saving: false,
                searchQuery: '',
                showModal: false,
                isEdit: false,
                form: { id: null, name: '', description: '', is_active: true },
                confirmModal: { show: false, title: '', message: '', onConfirm: null }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredList() {
                if (!this.searchQuery) return this.statusList;
                const query = this.searchQuery.toLowerCase();
                return this.statusList.filter(item => 
                    item.name.toLowerCase().includes(query) || 
                    (item.description && item.description.toLowerCase().includes(query))
                );
            }
        },
        methods: {
            async fetchData() {
                this.loading = true;
                try {
                    const baseUrl = window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/');
                    const response = await fetch(baseUrl + 'api/get_employment_statuses.php');
                    this.statusList = await response.json();
                } catch (error) {
                    console.error('Error fetching data:', error);
                } finally {
                    this.loading = false;
                }
            },
            openModal() {
                this.form = { id: null, name: '', description: '', is_active: true };
                this.isEdit = false;
                this.showModal = true;
            },
            editItem(item) {
                this.form = { 
                    id: item.id, 
                    name: item.name, 
                    description: item.description, 
                    is_active: item.is_active == 1 
                };
                this.isEdit = true;
                this.showModal = true;
            },
            async saveItem() {
                this.saving = true;
                try {
                    const baseUrl = window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/');
                    const response = await fetch(baseUrl + 'api/manage_employment_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: this.isEdit ? 'update' : 'create',
                            ...this.form,
                            is_active: this.form.is_active ? 1 : 0
                        })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showModal = false;
                        this.fetchData();
                    } else {
                        alert('Gagal: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error saving:', error);
                    alert('Terjadi kesalahan sistem');
                } finally {
                    this.saving = false;
                }
            },
            confirmDelete(item) {
                this.confirmModal = {
                    show: true,
                    title: 'Hapus Status?',
                    message: `Apakah Anda yakin ingin menghapus status "${item.name}"?`,
                    onConfirm: async () => {
                        try {
                            const baseUrl = window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/');
                            const response = await fetch(baseUrl + 'api/manage_employment_status.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'delete', id: item.id })
                            });
                            const result = await response.json();
                            if (result.success) {
                                this.fetchData();
                            } else {
                                alert(result.message);
                            }
                        } catch (error) {
                            console.error('Error deleting:', error);
                        }
                    }
                };
            },
            executeConfirm() {
                if (this.confirmModal.onConfirm) this.confirmModal.onConfirm();
                this.confirmModal.show = false;
            }
        },
        mounted() {
            this.fetchData();
        }
    }).mount('#app')
</script>
</body>
</html>
