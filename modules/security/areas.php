<?php
require_once '../../includes/guard.php';
require_login_and_module('security');
require_once '../../includes/header.php';
?>
<div id="app" v-cloak class="flex flex-col h-screen">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-map-marked-alt text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Area Penjagaan</h1>
                <span class="text-xs text-slate-500 font-medium">Master area & titik kontrol</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>index.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </a>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Tambah Area</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500">Kode (opsional)</label>
                        <input v-model="form.code" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="SEC-001">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Nama Area</label>
                        <input v-model="form.name" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Gerbang Utama">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Gedung/Lokasi</label>
                        <input v-model="form.building" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Gedung A">
                    </div>
                    <div class="flex gap-2">
                        <button @click="saveArea" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button @click="resetForm" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-200">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Daftar Area</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 uppercase text-xs">
                            <th class="py-2">Kode</th>
                            <th class="py-2">Nama</th>
                            <th class="py-2">Gedung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in areas" :key="a.id" class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-2 text-slate-600">{{ a.code || '-' }}</td>
                            <td class="py-2 font-medium text-slate-800">{{ a.name }}</td>
                            <td class="py-2 text-slate-600">{{ a.building || '-' }}</td>
                        </tr>
                        <tr v-if="areas.length === 0">
                            <td colspan="3" class="py-6 text-center text-slate-500">Belum ada area</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            form: { id: null, code: '', name: '', building: '' },
            areas: []
        }
    },
    methods: {
        resetForm() {
            this.form = { id: null, code: '', name: '', building: '' };
        },
        async fetchAreas() {
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=list_areas');
                const j = await r.json();
                this.areas = j.success ? (j.data || []) : [];
            } catch (e) { console.error(e); }
        },
        async saveArea() {
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=save_area', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const j = await r.json();
                if (j.success) {
                    alert('Area tersimpan');
                    this.resetForm();
                    this.fetchAreas();
                } else {
                    alert(j.message || 'Gagal menyimpan');
                }
            } catch (e) { console.error(e); alert('Gagal menyimpan'); }
        }
    },
    async mounted() {
        await this.fetchAreas();
    }
}).mount('#app');
</script>
<?php require_once '../../includes/footer.php'; ?>
