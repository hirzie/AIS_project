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

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-bold text-slate-800">Jadwal Mengajar Guru</h2>
                </div>
                <div class="flex gap-2">
                    <select v-model="selectedTeacherId" class="bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm min-w-[250px]">
                        <option value="" disabled>-- Cari Nama Guru --</option>
                        <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                            {{ teacher.name }}
                        </option>
                    </select>
                </div>
            </div>
            
            <div v-if="selectedTeacherId" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl font-bold">
                        {{ teachers.find(t => t.id === selectedTeacherId)?.name.charAt(0) }}
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">{{ teachers.find(t => t.id === selectedTeacherId)?.name }}</h3>
                        <p class="text-slate-500 text-sm">NIP: {{ teachers.find(t => t.id === selectedTeacherId)?.employee_id || '-' }}</p>
                    </div>
                    <div class="ml-auto text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ getTeacherTotalHours(selectedTeacherId) }} JP</div>
                        <div class="text-xs text-slate-500 uppercase font-bold">Total Jam Mengajar</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                                <th class="p-2 w-24 border-r border-slate-100">Waktu</th>
                                <th v-for="day in days" :key="day" class="p-2 w-48 border-r border-slate-100">{{ day }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="slot in activeTimeslots" :key="slot.id" class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="p-2 border-r border-slate-100 bg-slate-50 text-center align-middle" :class="slot.isBreak ? 'h-8 bg-amber-50/50' : 'h-16'">
                                    <div class="font-bold text-slate-700">{{ slot.start }} - {{ slot.end }}</div>
                                </td>
                                <td v-for="day in days" :key="day + slot.id" class="p-1 border-r border-slate-100 align-top" :class="slot.isBreak ? 'h-8 bg-amber-50/30' : 'h-16'">
                                    <div v-if="getTeacherScheduleItem(day, slot.start)" 
                                        class="bg-green-50 border border-green-100 p-1.5 rounded h-full relative group hover:shadow-sm transition-shadow">
                                        <div class="font-bold text-green-800 text-[10px] mb-0.5 line-clamp-1">
                                            {{ getTeacherScheduleItem(day, slot.start).class_name }}
                                        </div>
                                        <div class="text-[9px] text-slate-500 flex items-center gap-1">
                                            <i class="fas fa-book text-[8px]"></i>
                                            <span class="truncate">{{ getTeacherScheduleItem(day, slot.start).subject_name }}</span>
                                        </div>
                                        <!-- Badge JP dihapus untuk kembali ke tampilan sebelum perubahan -->
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                <i class="fas fa-user-tie text-4xl text-slate-300 mb-3"></i>
                <p class="text-slate-500">Pilih guru untuk melihat jadwal mengajarnya.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="font-bold text-slate-800">Profil Unit BK</h3>
                    </div>
                    <div class="text-sm text-slate-600 mb-3">{{ bkProfile.profile?.description || 'Profil belum diisi' }}</div>
                    <div>
                        <div class="text-xs font-bold text-slate-500 uppercase mb-2">Tim BK</div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="t in bkProfile.team" :key="t.employee_id" class="px-2 py-1 bg-slate-50 border border-slate-200 rounded text-xs text-slate-600">
                                {{ t.name }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="font-bold text-slate-800">Media Psikoedukasi</h3>
                    </div>
                    <div v-if="bkArticles.length > 0" class="space-y-3">
                        <a v-for="a in bkArticles.slice(0,4)" :key="a.id || a.title" :href="a.url || '#'" target="_blank" class="block group">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-800 group-hover:text-emerald-700">{{ a.title }}</div>
                                    <div class="text-xs text-slate-500 line-clamp-2">{{ a.summary }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div v-else class="text-sm text-slate-500">Belum ada artikel.</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <h3 class="font-bold text-slate-800">Jadwal Bimbingan BK</h3>
                    </div>
                    <div v-if="bkSchedule.length > 0" class="space-y-2">
                        <div v-for="s in bkSchedule.slice(0,6)" :key="(s.id || '') + (s.date || '') + (s.start || '')" class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div>
                                <div class="text-sm font-bold text-slate-800">{{ s.title || 'Bimbingan' }}</div>
                                <div class="text-xs text-slate-500">{{ new Date(s.date).toLocaleDateString('id-ID') }} • {{ (s.start || '').slice(0,5) }}{{ s.end ? (' - ' + (s.end || '').slice(0,5)) : '' }}</div>
                            </div>
                            <div class="text-xs text-slate-500">{{ s.room || '-' }}</div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-slate-500">Belum ada jadwal.</div>
                </div>
            </div>
        </div>
    </main>
</div>

<script type="module">
    import { academicMixin } from '../../assets/js/modules/academic.js';
    import { adminMixin } from '../../assets/js/modules/admin.js';
    const { createApp } = Vue;
    const app = createApp({
        mixins: [academicMixin],
        data() {
            return {
                bkProfile: { profile: {}, team: [] },
                bkArticles: [],
                bkSchedule: []
            };
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
            async fetchBkProfile() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=get_bk_unit_profile');
                    const j = await r.json();
                    this.bkProfile = j.success ? (j.data || { profile:{}, team:[] }) : { profile:{}, team:[] };
                } catch(_) { this.bkProfile = { profile:{}, team:[] }; }
            },
            async fetchBkArticles() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=list_psychoedu');
                    const j = await r.json();
                    this.bkArticles = j.success ? (j.data || []) : [];
                } catch(_) { this.bkArticles = []; }
            },
            async fetchBkSchedule() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=list_bk_schedule');
                    const j = await r.json();
                    this.bkSchedule = j.success ? (j.data || []) : [];
                } catch(_) { this.bkSchedule = []; }
            }
        },
        async mounted() {
            await this.fetchBkProfile();
            await this.fetchBkArticles();
            await this.fetchBkSchedule();
        },
        computed: {
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            }
        }
    });
    app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
