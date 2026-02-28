<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-purple-200">
            <i class="fas fa-file-invoice text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Log Laporan Yayasan</h1>
            <span class="text-xs text-slate-500 font-medium">Riwayat kirim posisi neraca ke yayasan</span>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
    </div>
    </nav>

<main id="app" class="flex-1 overflow-y-auto p-8 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Riwayat Log Keuangan</h3>
                <div class="flex items-center gap-2">
                    <button @click="fetchLogs" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">Entity</th>
                            <th class="px-4 py-3">Judul</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in logs" :key="(row.created_at || '') + (row.title || '')" class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-2 text-slate-500 text-xs">{{ formatDate(row.created_at) }}</td>
                            <td class="px-4 py-2 text-slate-700 text-xs">{{ row.category || '-' }}</td>
                            <td class="px-4 py-2 text-slate-700 text-xs">{{ row.action || '-' }}</td>
                            <td class="px-4 py-2 text-slate-700 text-xs">
                                <div class="font-mono">{{ row.entity_type || '-' }}</div>
                                <div class="text-[10px] text-slate-500">{{ row.entity_id || '-' }}</div>
                            </td>
                            <td class="px-4 py-2 text-slate-700 text-sm">{{ row.title || '-' }}</td>
                            <td class="px-4 py-2 text-slate-600 text-sm">{{ row.description || '-' }}</td>
                            <td class="px-4 py-2 text-slate-600 text-xs">{{ row.people_name || row.username || '-' }}</td>
                        </tr>
                        <tr v-if="logs.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Belum ada log laporan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const { createApp } = Vue;
createApp({
    data() {
        return { logs: [] }
    },
    computed: {
        currentDate() {
            return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    },
    mounted() {
        this.fetchLogs();
    },
    methods: {
        getBaseUrl() {
            let baseUrl = window.BASE_URL || '/';
            if (baseUrl === '/' || !baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                baseUrl = m ? `/${m[1]}/` : '/';
            }
            return baseUrl;
        },
        async fetchLogs() {
            try {
                const res = await fetch(this.getBaseUrl() + 'api/get_activity_logs.php?module=FINANCE&category=FOUNDATION_REPORT&limit=100');
                const data = await res.json();
                if (data.success) this.logs = data.data || [];
                else this.logs = [];
            } catch (e) {
                this.logs = [];
            }
        },
        formatDate(dt) {
            if (!dt) return '-';
            const d = new Date(dt);
            const opts = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
            return d.toLocaleDateString('id-ID', opts);
        }
    }
}).mount('#app');
</script>
</body>
</html>
