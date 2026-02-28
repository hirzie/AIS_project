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
            <div class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-file-alt text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Laporan Keamanan</h1>
                <span class="text-xs text-slate-500 font-medium">Ringkasan aktivitas</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
            <a href="<?php echo $baseUrl; ?>index.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </a>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Pegawai</p>
                <h4 class="text-2xl font-bold text-slate-800">{{ stats.staff }}</h4>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Shift Aktif</p>
                <h4 class="text-2xl font-bold text-slate-800">{{ stats.shifts }}</h4>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Area</p>
                <h4 class="text-2xl font-bold text-slate-800">{{ stats.areas }}</h4>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Template Checklist</p>
                <h4 class="text-2xl font-bold text-slate-800">{{ stats.templates }}</h4>
            </div>
        </div>
        <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 mb-6">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Laporan Checklist Bulanan</h3>
                <div class="flex items-center gap-2">
                    <button @click="viewMode='list'" :class="viewMode==='list' ? 'bg-white shadow text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200">List</button>
                    <button @click="viewMode='calendar'" :class="viewMode==='calendar' ? 'bg-white shadow text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200">Kalender</button>
                    <input type="month" v-model="filters.month" @change="fetchChecklistMonth" class="border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                    <select v-model="filters.user_id" @change="fetchChecklistMonth" class="border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                        <option value="">Semua Pegawai (Security)</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.person_name || u.username }}</option>
                    </select>
                </div>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="border border-slate-200 rounded-xl p-4">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Total Run</div>
                        <div class="text-2xl font-bold text-slate-800">{{ checklistSummary.total }}</div>
                    </div>
                    <div class="md:col-span-2 border border-slate-200 rounded-xl p-4">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-2">Per Template</div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(c,name) in checklistSummary.byTemplate" :key="name" class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[12px] font-bold">{{ name }}: {{ c }}</span>
                            <span v-if="Object.keys(checklistSummary.byTemplate||{}).length===0" class="text-slate-500 text-sm">Tidak ada data</span>
                        </div>
                    </div>
                </div>
                <div v-if="viewMode==='list'">
                    <div v-for="grp in groupedRuns" :key="grp.date" class="mb-3">
                        <div class="text-xs font-bold text-slate-600 mb-1">{{ formatDate(grp.date) }}</div>
                        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                            <div v-for="r in grp.items" :key="r.id" class="px-4 py-3 bg-white hover:bg-slate-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm">{{ r.template_name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ r.location || '-' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] text-slate-500">{{ r.officer_name || r.username || ('#'+r.officer_user_id) }}</div>
                                        <button @click="viewRun(r)" class="text-[10px] font-bold text-blue-600 hover:underline">Lihat</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="(checklistRuns||[]).length===0" class="text-center text-slate-500 text-sm py-3">Tidak ada data bulan ini</div>
                </div>
                <div v-else>
                    <div class="grid grid-cols-7 gap-2 mb-2">
                        <div v-for="d in dowLabels" :key="d" class="text-[11px] font-bold text-slate-600 text-center">{{ d }}</div>
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="(d,idx) in calendarDays" :key="idx" class="border border-slate-200 rounded-xl min-h-[120px] p-2 bg-white">
                            <div v-if="d" class="flex flex-col h-full">
                                <div class="text-[11px] font-bold text-slate-800 mb-1">{{ d.day }}</div>
                                <div class="flex flex-wrap gap-1 mb-1">
                                    <span v-if="(d.runs||[]).length>0" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold">{{ d.runs.length }} run</span>
                                </div>
                                <div class="space-y-1 flex-1 overflow-auto">
                                    <div v-for="r in d.runs.slice(0,3)" :key="r.id" class="text-[11px] text-slate-700">
                                        • <span class="font-bold">{{ r.template_name }}</span>
                                        <span class="text-slate-500"> — {{ r.officer_name || r.username || ('#'+r.officer_user_id) }}</span>
                                        <button @click="viewRun(r)" class="text-blue-600 hover:underline ml-1">Lihat</button>
                                    </div>
                                    <div v-if="d.runs.length>3" class="text-[11px] text-slate-500">+{{ d.runs.length-3 }} lagi</div>
                                    <div v-if="(d.runs||[]).length===0" class="text-[11px] text-slate-400">Tidak ada</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showRunModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-800">Detail Checklist</h3>
                    <button @click="showRunModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div v-if="runDetail">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <div class="text-[11px] font-bold text-slate-600">Template</div>
                            <div class="text-sm">{{ runDetail.template_name }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-slate-600">Petugas</div>
                            <div class="text-sm">{{ runDetail.officer_name || runDetail.username || ('#'+runDetail.officer_user_id) }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-slate-600">Lokasi</div>
                            <div class="text-sm">{{ runDetail.location || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-slate-600">Waktu</div>
                            <div class="text-sm">{{ formatDate(runDetail.created_at) }}</div>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">Jawaban</div>
                        <div class="divide-y divide-slate-100">
                            <div v-for="a in runAnswers" :key="a.item_label" class="px-3 py-2">
                                <div class="text-sm font-bold text-slate-800">{{ a.item_label }}</div>
                                <div class="text-[12px] text-slate-600">{{ renderAnswer(a) }}</div>
                            </div>
                            <div v-if="(runAnswers||[]).length===0" class="px-3 py-2 text-sm text-slate-500">Tidak ada jawaban</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Shift Terbaru</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 uppercase text-xs">
                            <th class="py-2">Pegawai</th>
                            <th class="py-2">Shift</th>
                            <th class="py-2">Jam</th>
                            <th class="py-2">Efektif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in latestShifts" :key="s.id" class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-2 font-medium text-slate-800">{{ s.employee_name || ('#' + s.employee_id) }}</td>
                            <td class="py-2">{{ s.shift_name }}</td>
                            <td class="py-2">{{ s.start_time }} - {{ s.end_time }}</td>
                            <td class="py-2">{{ s.effective_date || '-' }}</td>
                        </tr>
                        <tr v-if="latestShifts.length === 0">
                            <td colspan="4" class="py-6 text-center text-slate-500">Tidak ada data</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Template Checklist</h3>
                <ul class="space-y-2 text-sm">
                    <li v-for="t in templates" :key="t.id" class="flex items-center justify-between border border-slate-100 rounded-lg p-3 hover:bg-slate-50">
                        <div>
                            <p class="font-bold text-slate-800">{{ t.name }}</p>
                            <p class="text-[11px] text-slate-500">Jumlah item: {{ parseItems(t.items_json).length }}</p>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-bold">{{ formatDate(t.created_at) }}</span>
                    </li>
                    <li v-if="templates.length === 0" class="text-slate-500">Belum ada template</li>
                </ul>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            currentDate: new Date().toLocaleDateString('id-ID'),
            stats: { staff: 0, shifts: 0, areas: 0, templates: 0 },
            latestShifts: [],
            templates: [],
            filters: { month: new Date().toISOString().slice(0,7), user_id: '' },
            users: [],
            checklistRuns: [],
            checklistSummary: { total: 0, byTemplate: {} },
            groupedRuns: [],
            viewMode: 'list',
            dowLabels: ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'],
            calendarDays: [],
            showRunModal: false,
            runDetail: null,
            runAnswers: [],
            vehicles: [],
            vehicleTextById: {},
            activeLogTab: 'MEETING',
            logsMeeting: [],
            logsApproval: [],
            logsZero: []
        };
    },
    methods: {
        parseItems(json) {
            try { const arr = JSON.parse(json || '[]'); return Array.isArray(arr) ? arr : []; }
            catch(_) { return []; }
        },
        formatDate(s) {
            if (!s) return '-';
            try { return new Date(s).toLocaleDateString('id-ID'); } catch (_) { return s; }
        },
        base() {
            let b = window.BASE_URL || '/';
            if (b === '/' || !b) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                b = m ? `/${m[1]}/` : '/';
            }
            return b;
        },
        async fetchStats() {
            try {
                const base = this.base();
                const r1 = await fetch(base + 'api/security.php?action=list_employees');
                const j1 = await r1.json();
                const employees = j1.success ? (j1.data || []) : [];
                this.stats.staff = employees.filter(e => (e.role || e.employee_type || '').toUpperCase() === 'SECURITY').length;

                const r2 = await fetch(base + 'api/security.php?action=list_shifts');
                const j2 = await r2.json();
                const shifts = j2.success ? (j2.data || []) : [];
                this.stats.shifts = shifts.length;
                this.latestShifts = shifts.slice(0, 10);

                const r3 = await fetch(base + 'api/security.php?action=list_areas');
                const j3 = await r3.json();
                const areas = j3.success ? (j3.data || []) : [];
                this.stats.areas = areas.length;

                const r4 = await fetch(base + 'api/security.php?action=list_checklist_templates');
                const j4 = await r4.json();
                const templates = j4.success ? (j4.data || []) : [];
                this.stats.templates = templates.length;
                this.templates = templates;
            } catch (e) { console.error(e); }
        },
        async fetchUsers() {
            try {
                const r = await fetch(this.base() + 'api/get_users.php');
                const j = await r.json();
                const arr = Array.isArray(j) ? j : (j.success ? (j.data || []) : []);
                const sec = arr.filter(u => String(u.division||'').toUpperCase() === 'SECURITY' || String(u.role||'').toUpperCase() === 'SECURITY');
                this.users = sec.length > 0 ? sec : arr.filter(u => ['ADMIN','SUPERADMIN'].includes(String(u.role||'').toUpperCase()));
            } catch(_) { this.users = []; }
        },
        async fetchVehicles() {
            try {
                const r = await fetch(this.base() + 'api/inventory.php?action=get_vehicles');
                const j = await r.json();
                this.vehicles = j.success ? (j.data || []) : [];
                const map = {};
                this.vehicles.forEach(v => { map[v.id] = (v.name || 'Kendaraan') + (v.license_plate ? (' • ' + v.license_plate) : ''); });
                this.vehicleTextById = map;
            } catch(_) { this.vehicles = []; this.vehicleTextById = {}; }
        },
        async fetchLogsMeeting() {
            try {
                const r = await fetch(this.base() + 'api/get_activity_logs.php?module=EXECUTIVE&category=MEETING&limit=200');
                const j = await r.json();
                this.logsMeeting = j.success ? (j.data || []) : [];
            } catch(_) { this.logsMeeting = []; }
        },
        async fetchLogsApproval() {
            try {
                const r = await fetch(this.base() + 'api/get_activity_logs.php?module=EXECUTIVE&category=APPROVAL&limit=200');
                const j = await r.json();
                this.logsApproval = j.success ? (j.data || []) : [];
            } catch(_) { this.logsApproval = []; }
        },
        async fetchLogsZero() {
            try {
                const r = await fetch(this.base() + 'api/get_activity_logs.php?module=BOARDING&category=ZERO_REPORT&limit=200');
                const j = await r.json();
                this.logsZero = j.success ? (j.data || []) : [];
            } catch(_) { this.logsZero = []; }
        },
        async refreshLogs() {
            await Promise.all([this.fetchLogsMeeting(), this.fetchLogsApproval(), this.fetchLogsZero()]);
        },
        async fetchChecklistMonth() {
            try {
                const u = String(this.filters.user_id || '');
                const qs = new URLSearchParams({ month: this.filters.month });
                if (u) qs.append('user_id', u);
                const r = await fetch(this.base() + 'api/security.php?action=list_checklist_runs_month&' + qs.toString());
                const j = await r.json();
                const rows = j.success ? (j.data || []) : [];
                this.checklistRuns = rows;
                this.checklistSummary.total = rows.length;
                const byTpl = {};
                rows.forEach(x => { const name = x.template_name || ('#'+x.template_id); byTpl[name] = (byTpl[name]||0)+1; });
                this.checklistSummary.byTemplate = byTpl;
                const map = {};
                rows.forEach(x => {
                    const d = (x.created_at || '').slice(0,10);
                    if (!map[d]) map[d] = [];
                    map[d].push(x);
                });
                this.groupedRuns = Object.keys(map).sort().map(date => ({ date, items: map[date] }));
                this.rebuildCalendar();
            } catch(_) {
                this.checklistRuns = [];
                this.checklistSummary = { total:0, byTemplate:{} };
                this.groupedRuns = [];
                this.calendarDays = [];
            }
        },
        rebuildCalendar() {
            try {
                const ym = String(this.filters.month || '');
                const [yStr, mStr] = ym.split('-');
                const y = parseInt(yStr, 10);
                const m = parseInt(mStr, 10) - 1; // 0-based
                const first = new Date(y, m, 1);
                const daysInMonth = new Date(y, m + 1, 0).getDate();
                const mondayFirst = (first.getDay() + 6) % 7; // 0=Mon ... 6=Sun
                const map = {};
                (this.checklistRuns || []).forEach(x => {
                    const d = (x.created_at || '').slice(0,10);
                    if (!map[d]) map[d] = [];
                    map[d].push(x);
                });
                const arr = [];
                for (let i = 0; i < mondayFirst; i++) arr.push(null);
                for (let day = 1; day <= daysInMonth; day++) {
                    const dStr = `${ym}-${String(day).padStart(2,'0')}`;
                    arr.push({ date: dStr, day, runs: map[dStr] || [] });
                }
                this.calendarDays = arr;
            } catch(_) { this.calendarDays = []; }
        },
        async viewRun(r) {
            this.runDetail = r;
            this.showRunModal = true;
            try {
                const q = new URLSearchParams({ run_id: r.id });
                const resp = await fetch(this.base() + 'api/security.php?action=get_checklist_answers&' + q.toString());
                const js = await resp.json();
                this.runAnswers = js.success ? (js.data || []) : [];
            } catch(_) { this.runAnswers = []; }
        },
        renderAnswer(a) {
            const t = String(a.item_type||'').toUpperCase();
            let val = {};
            try { val = JSON.parse(a.value_json || 'null') || {}; } catch(_) { val = {}; }
            if (t === 'BOOLEAN') {
                const choice = String(val.choice||'').toUpperCase();
                const yn = choice === 'YA' ? 'Ya' : (choice === 'TIDAK' ? 'Tidak' : (val.checked ? 'Ya' : 'Tidak'));
                const nt = (val.note||'').trim();
                return nt ? (yn + ' • ' + nt) : yn;
            } else if (t === 'COUNT_NOTE') {
                const cnt = Number(val.count||0);
                const pre = cnt > 0 ? 'Ada' : 'Tidak Ada';
                const nt = (val.note||'').trim();
                const mid = isNaN(cnt)?'':(' • Jumlah: '+cnt);
                return [pre, mid, nt?(' • '+nt):''].join('');
            } else if (t === 'PACKAGE') {
                const cnt = Number(val.count||0);
                const pre = cnt > 0 ? 'Ada' : 'Tidak Ada';
                const forw = (val.for||val.recipient||'').trim();
                const nt = (val.note||'').trim();
                const parts = [pre];
                if (!isNaN(cnt)) parts.push('Jumlah: '+cnt);
                if (forw) parts.push('Untuk: '+forw);
                if (nt) parts.push(nt);
                return parts.join(' • ');
            } else if (t === 'VEHICLE') {
                const outs = Array.isArray(val.out_ids) ? val.out_ids : [];
                const presArr = Array.isArray(val.present_ids) ? val.present_ids : [];
                const outNames = outs.map(id => this.vehicleTextById[id] || ('#'+id)).filter(x => !!x).join('; ');
                const pres = presArr.length;
                const nt = (val.note||'').trim();
                const parts = [];
                if (outs.length > 0) parts.push('Keluar: ' + outNames);
                else parts.push('Keluar: 0');
                parts.push('Ada: ' + pres);
                if (nt) parts.push(nt);
                return parts.join(' • ');
            }
            const txt = (typeof val === 'object' && val !== null) ? String((val.text ?? val.note ?? '')).trim() : (typeof val === 'string' ? val.trim() : '');
            return txt;
        }
    },
    async mounted() {
        await this.fetchStats();
        await this.fetchUsers();
        await this.fetchVehicles();
        await this.fetchChecklistMonth();
        await this.refreshLogs();
    }
}).mount('#app');
</script>
<?php require_once '../../includes/footer.php'; ?>
