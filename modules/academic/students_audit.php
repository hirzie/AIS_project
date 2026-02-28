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

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative" v-cloak>
        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Data Siswa • Audit Cepat</h2>
                    <p class="text-sm text-slate-500">Rekap statistik untuk audit penempatan kelas dan status siswa.</p>
                </div>
                <div class="flex gap-2">
                    <a href="classes.php" class="bg-white border border-slate-300 px-4 py-2 rounded-lg text-sm hover:bg-slate-50 font-bold text-slate-600"><i class="fas fa-layer-group mr-2"></i>Data Kelas</a>
                </div>
            </div>

            <!-- Empty State / Select Unit -->
            <div v-if="currentUnit === 'all'" class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-blue-200 mt-6">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-500">
                    <i class="fas fa-search text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700 mb-2">Pilih Unit Sekolah</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-6">Silakan pilih unit (TK, SD, SMP, SMA) pada menu bagian atas untuk menampilkan data audit siswa.</p>
                <div class="flex gap-2 justify-center">
                    <button @click="currentUnit = 'TK'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">TK</button>
                    <button @click="currentUnit = 'SD'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SD</button>
                    <button @click="currentUnit = 'SMP'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SMP</button>
                    <button @click="currentUnit = 'SMA'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SMA</button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-else-if="isLoading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                <p class="mt-2 text-slate-500 font-bold">Memuat data audit...</p>
            </div>

            <div v-else>
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Row 1 -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase">Siswa Aktif</div>
                        <div class="text-3xl font-extrabold text-blue-700 mt-2">{{ stats.totals.students_active }}</div>
                        <div class="text-xs text-slate-400 mt-1">Status ACTIVE</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase">Di Kelas (Aktif)</div>
                        <div class="text-3xl font-extrabold text-emerald-700 mt-2">{{ stats.totals.students_in_class_active }}</div>
                        <div class="text-xs text-slate-400 mt-1">Memiliki penempatan kelas aktif</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase">Tidak di Kelas (Aktif)</div>
                        <div class="text-3xl font-extrabold text-red-600 mt-2">{{ stats.totals.students_not_in_class_active }}</div>
                        <div class="text-xs text-slate-400 mt-1">Aktif tanpa penempatan kelas</div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase">Siswa Nonaktif</div>
                        <div class="text-3xl font-extrabold text-slate-700 mt-2">{{ stats.totals.students_inactive }}</div>
                        <div class="text-xs text-slate-400 mt-1">INACTIVE/ARCHIVED</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase">Laki-laki (Aktif)</div>
                        <div class="text-3xl font-extrabold text-indigo-700 mt-2">{{ stats.totals.male_active }}</div>
                        <div class="text-xs text-slate-400 mt-1">Gender: L</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase">Perempuan (Aktif)</div>
                        <div class="text-3xl font-extrabold text-pink-700 mt-2">{{ stats.totals.female_active }}</div>
                        <div class="text-xs text-slate-400 mt-1">Gender: P</div>
                    </div>
                </div>

                <!-- Audit Flags Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div>
                            <div class="text-sm font-bold text-slate-800">Audit Flags</div>
                            <div class="text-xs text-slate-500">Indikasi masalah data yang perlu ditindaklanjuti</div>
                        </div>
                        <button @click="loadIntegritySamples" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-blue-50 hover:text-blue-700 transition-colors shadow-sm">
                            <i class="fas fa-search mr-2"></i>Lihat Sampel
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                        <div class="rounded-xl border border-orange-100 bg-orange-50/50 p-4">
                            <div class="text-xs font-bold text-orange-600 uppercase mb-1">Duplikasi NIS</div>
                            <div class="text-3xl font-extrabold text-orange-700">{{ stats.flags.duplicate_nis }}</div>
                            <div class="text-xs text-orange-500 mt-1">Identity Number ganda</div>
                        </div>
                        <div class="rounded-xl border border-red-100 bg-red-50/50 p-4">
                            <div class="text-xs font-bold text-red-600 uppercase mb-1">Multi Kelas Aktif</div>
                            <div class="text-3xl font-extrabold text-red-700">{{ stats.flags.multi_active_classes }}</div>
                            <div class="text-xs text-red-500 mt-1">Satu siswa di >1 kelas aktif</div>
                        </div>
                        <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-4">
                            <div class="text-xs font-bold text-amber-600 uppercase mb-1">Tanpa NISN (Aktif)</div>
                            <div class="text-3xl font-extrabold text-amber-700">{{ stats.flags.missing_nisn_active }}</div>
                            <div class="text-xs text-amber-500 mt-1">Siswa aktif tanpa NISN</div>
                        </div>
                    </div>
                </div>

                <!-- Integrity Samples -->
                <div v-if="integritySamples" class="mb-8 bg-white rounded-xl shadow-lg border border-slate-200 ring-4 ring-slate-100 animate-fade">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div class="text-sm font-bold text-slate-800"><i class="fas fa-bug text-red-500 mr-2"></i>Sampel Temuan</div>
                        <button @click="integritySamples=null" class="text-slate-400 hover:text-slate-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-200"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-6">
                        <!-- Duplicates -->
                        <div>
                            <div class="text-xs font-bold text-slate-500 uppercase mb-3 border-b border-slate-100 pb-2">NIS Duplikat</div>
                            <ul class="space-y-2">
                                <li v-for="d in integritySamples.duplicates_identity || []" :key="d.identity_number" class="flex justify-between items-center text-sm p-2 rounded hover:bg-slate-50">
                                    <span class="font-mono text-slate-600">{{ d.identity_number }}</span>
                                    <span class="font-bold text-white bg-orange-500 px-2 py-0.5 rounded text-xs">{{ d.cnt }}x</span>
                                </li>
                                <li v-if="!integritySamples.duplicates_identity || integritySamples.duplicates_identity.length===0" class="text-slate-400 italic text-sm p-2">Tidak ada sample.</li>
                            </ul>
                        </div>
                        <!-- Limbo -->
                        <div>
                            <div class="text-xs font-bold text-slate-500 uppercase mb-3 border-b border-slate-100 pb-2">Siswa Limbo (Tanpa Kelas)</div>
                            <ul class="space-y-2">
                                <li v-for="s in integritySamples.limbo_students || []" :key="s.id" class="flex justify-between items-center text-sm p-2 rounded hover:bg-slate-50">
                                    <span class="text-slate-700 font-medium">{{ s.name || '-' }}</span>
                                    <span class="text-xs text-slate-400 font-mono">#{{ s.id }}</span>
                                </li>
                                <li v-if="!integritySamples.limbo_students || integritySamples.limbo_students.length===0" class="text-slate-400 italic text-sm p-2">Tidak ada sample.</li>
                            </ul>
                        </div>
                        <!-- Missing NISN -->
                        <div>
                            <div class="text-xs font-bold text-slate-500 uppercase mb-3 border-b border-slate-100 pb-2">Aktif Tanpa NISN</div>
                            <ul class="space-y-2">
                                <li v-for="m in integritySamples.missing_nisn_active || []" :key="m.id" class="flex justify-between items-center text-sm p-2 rounded hover:bg-slate-50">
                                    <div class="truncate max-w-[150px] font-medium text-slate-700" :title="m.name">{{ m.name || ('#'+m.id) }}</div>
                                    <div class="text-xs text-slate-500">{{ m.class_name || '-' }}</div>
                                </li>
                                <li v-if="!integritySamples.missing_nisn_active || integritySamples.missing_nisn_active.length===0" class="text-slate-400 italic text-sm p-2">Tidak ada sample.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Class List -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <div class="text-sm font-bold text-slate-800">Daftar Kelas Cepat</div>
                        <div class="text-xs text-slate-500">Jumlah siswa aktif per kelas</div>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <a v-for="k in classCounts" :key="k.class_id" :href="`class_detail.php?id=${k.class_id}`" class="group block rounded-xl border border-slate-200 hover:border-blue-400 hover:shadow-md bg-white p-4 transition-all">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ k.level_name }}</div>
                                <div class="flex items-center justify-between">
                                    <div class="font-bold text-slate-800 text-lg group-hover:text-blue-600">{{ k.class_name }}</div>
                                    <div class="text-sm font-bold bg-emerald-100 text-emerald-700 px-2 py-1 rounded-lg">{{ k.students_active }} Siswa</div>
                                </div>
                            </a>
                        </div>
                        <div v-if="classCounts.length===0" class="text-center py-8 text-slate-400 italic border-2 border-dashed border-slate-100 rounded-xl">
                            Tidak ada data kelas untuk unit ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script type="module">
    window.SKIP_GLOBAL_APP = true;
    import { academicMixin } from '../../assets/js/modules/academic.js?v=5';
    const { createApp } = Vue;
    
    const app = createApp({
        mixins: [academicMixin],
        data() {
            return {
                manualFetchOnly: true,
                currentUnit: 'all',
                isLoading: false,
                stats: {
                    totals: { students_active: 0, students_in_class_active: 0, students_not_in_class_active: 0, students_inactive: 0, male_active: 0, female_active: 0 },
                    flags: { duplicate_nis: 0, multi_active_classes: 0, missing_nisn_active: 0 }
                },
                integritySamples: null,
                classCounts: []
            };
        },
        watch: {
            currentUnit(newVal) {
                if (newVal && newVal !== 'all') {
                    this.refreshData(newVal);
                }
            }
        },
        async mounted() {
            await this.fetchAllUnits();
            // const unit = this.currentUnit || 'SD';
            // this.refreshData(unit);
        },
        methods: {
            base() {
                let b = window.BASE_URL || '';
                if (!b) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//);
                    b = m ? `/${m[1]}/` : '/';
                }
                return b;
            },
            async refreshData(unit) {
                this.isLoading = true;
                await Promise.all([
                    this.fetchAuditStats(unit),
                    this.fetchClassCounts(unit)
                ]);
                this.isLoading = false;
            },
            async fetchAuditStats(unit) {
                try {
                    const res = await fetch(this.base() + `api/manage_student.php?action=audit_stats&unit=${encodeURIComponent(unit)}&_t=${Date.now()}`);
                    const ct = res.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) return;
                    const j = await res.json();
                    if (j.success) {
                        this.stats = j.data;
                    }
                } catch (e) { console.error('Stats error:', e); }
            },
            async fetchClassCounts(unit) {
                try {
                    const res = await fetch(this.base() + `api/manage_student.php?action=audit_class_counts&unit=${encodeURIComponent(unit)}&_t=${Date.now()}`);
                    const ct = res.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) return;
                    const j = await res.json();
                    if (j.success) this.classCounts = j.data || [];
                } catch (e) { console.error('Class counts error:', e); }
            },
            async loadIntegritySamples() {
                try {
                    const unit = this.currentUnit || 'ALL';
                    const res = await fetch(this.base() + `api/manage_student.php?action=audit_integrity&unit=${encodeURIComponent(unit)}&_t=${Date.now()}`);
                    const ct = res.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) return;
                    const j = await res.json();
                    if (j.success) {
                        this.integritySamples = j.data.samples;
                    }
                } catch (e) { console.error('Integrity error:', e); }
            }
        }
    });
    app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>