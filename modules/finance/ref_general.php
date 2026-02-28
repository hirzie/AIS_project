<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" class="flex flex-col h-screen bg-slate-50">

    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-200">
                <i class="fas fa-cogs text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Pengaturan Umum</h1>
                <span class="text-xs text-slate-500 font-medium">Tahun Buku & Parameter Sistem</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden flex flex-col md:flex-row p-6 gap-6">
        
        <div class="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-slate-800">Daftar Tahun Buku</h3>
                    <p class="text-xs text-slate-500">Otomatisasi Periode Akuntansi</p>
                </div>
                <span class="bg-slate-100 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 cursor-help" title="Dikelola otomatis oleh sistem">
                    <i class="fas fa-robot mr-1"></i> Auto-Managed
                </span>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Tahun</th>
                            <th class="px-6 py-3">Periode</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="y in fiscalYears" :key="y.id" class="hover:bg-slate-50" :class="{'bg-green-50/50': y.is_active}">
                            <td class="px-6 py-4 font-bold text-slate-700">
                                {{ y.name }}
                                <span v-if="y.is_active" class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 text-[10px] rounded-full border border-green-200">AKTIF</span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                {{ formatDate(y.start_date) }} - {{ formatDate(y.end_date) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold" :class="y.status === 'OPEN' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'">
                                    {{ y.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button v-if="!y.is_active && y.status === 'OPEN'" @click="activateYear(y.id)" class="text-blue-600 hover:text-blue-800 text-xs font-bold border border-blue-200 px-2 py-1 rounded hover:bg-blue-50 transition-colors">
                                    Aktifkan
                                </button>
                                <span v-else class="text-slate-300">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="w-full md:w-1/3 flex flex-col gap-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col flex-1">
                <div class="p-5 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-800">Awalan Kuitansi (Prefix)</h3>
                    <p class="text-xs text-slate-500">Format: [KODE UNIT] + [2 DIGIT TAHUN]</p>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div v-for="unit in units" :key="unit.id" class="flex items-center gap-3 p-3 border border-slate-100 rounded-lg hover:border-slate-300 transition-colors bg-white">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-xs">
                            {{ unit.code }}
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-bold text-slate-700">{{ unit.name }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="text" v-model="unit.receipt_code" class="w-16 border border-slate-300 rounded px-2 py-1 text-center font-mono font-bold text-sm uppercase focus:border-blue-500 outline-none" maxlength="3">
                                <span class="text-xs text-slate-400"><i class="fas fa-arrow-right mx-1"></i> Preview: </span>
                                <span class="font-mono font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded text-xs">
                                    {{ unit.receipt_code }}{{ activeYearSuffix }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50">
                    <button @click="saveSettings" class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all">
                        Simpan Perubahan
                    </button>
                </div>
            </div>

            <div class="bg-yellow-50 rounded-xl p-5 border border-yellow-100">
                <h4 class="font-bold text-yellow-800 text-sm mb-2"><i class="fas fa-lightbulb mr-1"></i> Tips Kode Unit</h4>
                <p class="text-xs text-yellow-700 leading-relaxed mb-2">
                    Jika kode unit SMP dan SMA sama-sama "SM", ubah salah satu menjadi kode unik lain.
                </p>
                <ul class="text-xs text-yellow-700 list-disc list-inside">
                    <li>SMP → <strong>MP</strong> atau <strong>SMP</strong></li>
                    <li>SMA → <strong>MA</strong> atau <strong>SMA</strong></li>
                </ul>
            </div>

            <div class="bg-red-50 rounded-xl p-5 border border-red-100 mt-auto">
                <h4 class="font-bold text-red-800 text-sm mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> Dangerous Zone</h4>
                <p class="text-xs text-red-700 leading-relaxed mb-3">
                    Fitur ini digunakan untuk pengembangan/debug. Menghapus semua data transaksi, tagihan, dan jurnal.
                </p>
                <button @click="resetData" class="w-full bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-700 shadow-lg shadow-red-200 transition-all text-xs">
                    <i class="fas fa-trash-alt mr-2"></i> Reset Data Keuangan
                </button>
            </div>

        </div>

    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                fiscalYears: [],
                units: [],
            }
        },
        computed: {
            activeYear() {
                return this.fiscalYears.find(y => y.is_active);
            },
            activeYearSuffix() {
                if (!this.activeYear) return 'XX';
                return this.activeYear.name.slice(-2);
            }
        },
        methods: {
            formatDate(dateStr) {
                return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            async fetchInit() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.fiscalYears = data.data.fiscalYears;
                        this.units = data.data.units;
                    }
                } catch (e) {}
            },
            async activateYear(id) {
                if (!confirm('Aktifkan tahun buku ini?')) return;
                this.saveSettings(id);
            },
            async saveSettings(activeYearId = null) {
                try {
                    const payload = {
                        units: this.units,
                        active_year_id: activeYearId || (this.activeYear ? this.activeYear.id : null)
                    };
                    
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=save_fiscal_settings', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Pengaturan berhasil disimpan');
                        this.fetchInit();
                    } else {
                        alert(data.message);
                    }
                } catch (e) {}
            },
            async resetData() {
                if (!confirm('PERINGATAN KERAS!\n\nSemua data transaksi, tagihan siswa, jurnal, dan tarif akan DIHAPUS PERMANEN.\n\nApakah Anda yakin ingin melanjutkan reset data untuk debug?')) return;
                
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=reset_finance_data', { method: 'POST' });
                    const data = await res.json();
                    alert(data.message);
                } catch (e) {
                    alert('Gagal reset data.');
                }
            }
        },
        mounted() {
            this.fetchInit();
        }
    }).mount('#app')
</script>
</body>
</html>
