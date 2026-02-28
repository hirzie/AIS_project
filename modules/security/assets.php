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
            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-boxes text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Aset Keamanan</h1>
                <span class="text-xs text-slate-500 font-medium">Kendaraan, Inventaris Divisi, Ringkasan</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="hidden md:flex items-center gap-1">
                <button @click="activeTab='VEHICLES'" :class="activeTab==='VEHICLES'?'bg-purple-600 text-white':'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-bold">Kendaraan</button>
                <button @click="activeTab='INVENTORY'" :class="activeTab==='INVENTORY'?'bg-purple-600 text-white':'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-bold">Inventaris Divisi</button>
                <button @click="activeTab='SUMMARY'" :class="activeTab==='SUMMARY'?'bg-purple-600 text-white':'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-bold">Ringkasan</button>
            </div>
            <a href="<?php echo $baseUrl; ?>index.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </a>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <button @click="activeTab='VEHICLES'" :class="activeTab==='VEHICLES'?'bg-purple-600 text-white':'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-bold">Kendaraan</button>
                    <button @click="activeTab='INVENTORY'" :class="activeTab==='INVENTORY'?'bg-purple-600 text-white':'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-bold">Inventaris Divisi</button>
                    <button @click="activeTab='SUMMARY'" :class="activeTab==='SUMMARY'?'bg-purple-600 text-white':'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-bold">Ringkasan</button>
                </div>
                <div v-if="activeTab==='VEHICLES'" class="flex items-center gap-2 ml-auto">
                    <input v-model="q" type="text" placeholder="Cari kendaraan..." class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm w-64">
                    <button @click="fetchVehicles" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-purple-700">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
                <div v-if="activeTab==='INVENTORY'" class="flex items-center gap-2 ml-auto">
                    <select v-model="selectedDivision" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm w-56">
                        <option value="">Semua Divisi</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.code">{{ d.name }}</option>
                    </select>
                    <button @click="fetchInventoryItems" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-purple-700">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </div>
            <div v-if="activeTab==='VEHICLES'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 mb-4">Daftar Kendaraan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="v in vehicles" :key="v.id" class="border border-slate-200 rounded-lg p-4 hover:shadow-sm transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                        <i class="fas fa-car"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ v.name }}</p>
                                        <p class="text-xs text-slate-500">{{ v.license_plate }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded" :class="v.status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">{{ v.status || 'UNKNOWN' }}</span>
                            </div>
                            <div class="mt-3">
                                <p class="text-[11px] text-slate-500 mb-1">Servis terakhir: <span class="font-medium text-slate-700">{{ v.last_service || '-' }}</span></p>
                                <p class="text-[11px] text-slate-500 mb-2">Servis berikutnya: <span class="font-medium text-slate-700">{{ v.next_service || '-' }}</span></p>
                                <div class="h-2 bg-slate-200 rounded">
                                    <div class="h-2 bg-purple-600 rounded" :style="{ width: (v.service_health || 0) + '%' }"></div>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Kesehatan servis: <span class="font-medium text-slate-700">{{ v.service_health || 0 }}%</span></p>
                            </div>
                        </div>
                    </div>
                    <div v-if="vehicles.length === 0" class="text-center text-slate-500 py-10">Tidak ada data kendaraan</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 mb-4">Ringkasan</h3>
                    <ul class="space-y-2 text-sm text-slate-700">
                        <li class="flex justify-between"><span>Total kendaraan</span><span class="font-bold">{{ vehicles.length }}</span></li>
                        <li class="flex justify-between"><span>Jadwal servis bulan ini</span><span class="font-bold">{{ upcomingServiceCount }}</span></li>
                    </ul>
                </div>
            </div>
            <div v-if="activeTab==='INVENTORY'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Inventaris Divisi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-if="inventoryItems.length">
                    <div v-for="item in inventoryItems" :key="item.id" class="border border-slate-200 rounded-lg p-4 hover:shadow-sm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 text-purple-600 flex items-center justify-center rounded">
                                <i class="fas fa-box"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ item.name }}</p>
                                <p class="text-xs text-slate-500">{{ item.category_name }} • {{ item.location || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-slate-500 py-10">Tidak ada data inventaris</div>
            </div>
            <div v-if="activeTab==='SUMMARY'" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Ringkasan</h3>
                <ul class="space-y-2 text-sm text-slate-700">
                    <li class="flex justify-between"><span>Total kendaraan</span><span class="font-bold">{{ vehicles.length }}</span></li>
                    <li class="flex justify-between"><span>Jadwal servis bulan ini</span><span class="font-bold">{{ upcomingServiceCount }}</span></li>
                    <li class="flex justify-between"><span>Total inventaris</span><span class="font-bold">{{ inventoryItems.length }}</span></li>
                </ul>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return { q: '', vehicles: [], activeTab: 'VEHICLES', divisions: [], selectedDivision: '', inventoryItems: [] };
    },
    computed: {
        upcomingServiceCount() {
            const today = new Date().toISOString().slice(0, 7);
            return (this.vehicles || []).filter(v => (v.next_service || '').startsWith(today)).length;
        }
    },
    methods: {
        async fetchVehicles() {
            try {
                const base = (window.BASE_URL || '/');
                const url = base + 'api/inventory.php?action=get_vehicles' + (this.q ? ('&q=' + encodeURIComponent(this.q)) : '');
                const r = await fetch(url);
                const j = await r.json();
                this.vehicles = j.success ? (j.data || []) : [];
            } catch (e) { console.error(e); }
        },
        async fetchDivisions() {
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/inventory.php?action=get_divisions');
                const j = await r.json();
                this.divisions = j.success ? (j.data || []) : [];
                const def = (this.divisions || []).find(d => String(d.code||'').toUpperCase() === 'SECURITY');
                if (def) this.selectedDivision = def.code;
            } catch (e) { this.divisions = []; }
        },
        async fetchInventoryItems() {
            try {
                let url = (window.BASE_URL || '/') + 'api/inventory.php?action=get_movable';
                if (this.selectedDivision) url += '&division=' + encodeURIComponent(this.selectedDivision);
                const r = await fetch(url);
                const j = await r.json();
                this.inventoryItems = j.success ? (j.data || []) : [];
            } catch (e) { this.inventoryItems = []; }
        }
    },
    async mounted() {
        await this.fetchVehicles();
        await this.fetchDivisions();
        await this.fetchInventoryItems();
    }
}).mount('#app');
</script>
<?php require_once '../../includes/footer.php'; ?>
