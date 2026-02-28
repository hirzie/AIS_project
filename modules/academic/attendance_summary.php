<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<div id="app-summary" class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Rekap Presensi Bulanan</h2>
                    <p class="text-slate-500 text-sm">Monitoring rekapitulasi kehadiran siswa per unit dan kelas.</p>
                </div>
                <div class="flex gap-3">
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                        <i class="fas fa-building text-slate-400 mr-2"></i>
                        <select v-model="currentUnit" class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 outline-none min-w-[140px]">
                            <option value="all">Semua Unit</option>
                            <option v-for="unit in availableUnits" :key="unit.id" :value="unit.unit_level || unit.code">
                                {{ unit.name }} ({{ unit.prefix }})
                            </option>
                        </select>
                    </div>
                    <button @click="printTable" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all flex items-center gap-2">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <a href="attendance_batch.php" class="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                        <i class="fas fa-plus"></i> Input Presensi
                    </a>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16 text-center">No</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Unit</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kelas</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Periode</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">H. Aktif</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Siswa</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">I / S / A / C</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="loading" class="animate-pulse">
                                <td colspan="8" class="py-20 text-center">
                                    <i class="fas fa-spinner fa-spin text-3xl text-blue-200"></i>
                                </td>
                            </tr>
                            <tr v-else-if="summaries.length === 0">
                                <td colspan="8" class="py-20 text-center text-slate-400 italic">
                                    Tidak ada data presensi yang ditemukan untuk kriteria ini.
                                </td>
                            </tr>
                            <tr v-for="(item, idx) in summaries" :key="item.class_id + '-' + item.month + '-' + item.year" class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4 text-sm text-slate-500 text-center font-mono">{{ idx + 1 }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded uppercase">{{ item.unit_code }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-700">{{ item.class_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ monthNames[item.month-1] }} {{ item.year }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-blue-600">{{ item.active_days }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm text-slate-600">{{ item.total_students }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <span class="w-8 py-1 bg-amber-50 text-amber-600 text-xs font-bold rounded border border-amber-100" title="Izin">{{ item.total_izin }}</span>
                                        <span class="w-8 py-1 bg-blue-50 text-blue-600 text-xs font-bold rounded border border-blue-100" title="Sakit">{{ item.total_sakit }}</span>
                                        <span class="w-8 py-1 bg-red-50 text-red-600 text-xs font-bold rounded border border-red-100" title="Alfa">{{ item.total_alfa }}</span>
                                        <span class="w-8 py-1 bg-purple-50 text-purple-600 text-xs font-bold rounded border border-purple-100" title="Cuti">{{ item.total_cuti }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a :href="'attendance_batch.php?class_id=' + item.class_id + '&month=' + item.month + '&year=' + item.year" 
                                           class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-all" title="Edit">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>
                                        <button @click="printItem(item)" 
                                                class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-all" title="Cetak">
                                            <i class="fas fa-print text-xs"></i>
                                        </button>
                                        <button @click="deleteSummary(item)" 
                                                class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-red-600 hover:text-white flex items-center justify-center transition-all" title="Hapus">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                loading: true,
                currentUnit: 'all',
                availableUnits: [],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                summaries: []
            }
        },
        computed: {
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            }
        },
        watch: {
            currentUnit() {
                this.fetchSummaries();
            }
        },
        async mounted() {
            try {
                await this.fetchUnits();
                await this.fetchSummaries();
            } catch (e) {
                console.error('Initialization error:', e);
                this.loading = false;
            }
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
            async fetchUnits() {
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/get_units.php');
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const all = await res.json();
                    const allowed = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                    const role = String(window.USER_ROLE || '').toUpperCase();
                    if (allowed.length > 0 && !['SUPERADMIN','ADMIN'].includes(role)) {
                        const allowUp = allowed.map(u => String(u).toUpperCase());
                        this.availableUnits = (all || []).filter(u => {
                            const codeUp = String(u.code || u.unit_level || '').toUpperCase();
                            const rcUp = String(u.prefix || u.receipt_code || '').toUpperCase();
                            return allowUp.includes(codeUp) || allowUp.includes(rcUp);
                        });
                    } else {
                        this.availableUnits = all || [];
                    }
                } catch (e) {
                    console.error('Gagal mengambil data unit');
                }
            },
            async fetchSummaries() {
                this.loading = true;
                try {
                    const res = await fetch(this.getBaseUrl() + `api/attendance.php?action=get_attendance_summary&unit=${this.currentUnit}`);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (data && data.success) {
                        this.summaries = data.data;
                    } else {
                        throw new Error((data && data.message) ? data.message : 'API gagal');
                    }
                } catch (e) {
                    console.error('Gagal mengambil data rekap:', e && e.message ? e.message : e);
                } finally {
                    this.loading = false;
                }
            },
            printItem(item) {
                window.location.href = `attendance_batch.php?class_id=${item.class_id}&month=${item.month}&year=${item.year}`;
            },
            printTable() {
                window.print();
            },
            async deleteSummary(item) {
                if (!confirm(`Hapus rekap presensi ${item.class_name} ${this.monthNames[item.month-1]} ${item.year}?`)) return;
                this.loading = true;
                try {
                    const res = await fetch(this.getBaseUrl() + `api/attendance.php?action=delete_attendance_month&class_id=${item.class_id}&month=${item.month}&year=${item.year}`);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (data && data.success) {
                        this.summaries = this.summaries.filter(s => !(s.class_id == item.class_id && s.month == item.month && s.year == item.year));
                    } else {
                        alert((data && data.message) ? data.message : 'Gagal menghapus data');
                    }
                } catch (e) {
                    alert('Gagal menghubungi server');
                } finally {
                    this.loading = false;
                }
            }
        }
    }).mount('#app-summary')
</script>

<?php require_once '../../includes/footer.php'; ?>
