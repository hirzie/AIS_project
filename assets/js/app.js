import { TreeItem } from './components/TreeItem.js';
import { utilsMixin } from './modules/utils.js';
import { employeeMixin } from './modules/employee.js';
import { academicMixin } from './modules/academic.js?v=5';
import { kioskMixin } from './modules/kiosk.js';
import { adminMixin } from './modules/admin.js?v=2.2'; 

const { createApp } = Vue;

const app = createApp({
    mixins: [utilsMixin, employeeMixin, academicMixin, kioskMixin, adminMixin],
    data() {
        return {
            isMounted: false, // Controls Skeleton vs Content visibility
            currentPage: 'dash',
            activeSettingTab: 'general',
            currentPreset: 'all',
            currentUnit: (window.MANUAL_FETCH_ONLY ? 'all' : (localStorage.getItem('currentUnit') || 'all')), 
            // availableUnits: [], // Removed duplicate definition
            showControls: false, // Default hidden for production feel
            isSidebarOpen: false,
            modules: {
                core: true,       // Dashboard
                academic: true,   // Akademik
                finance: true,    // Keuangan
                hr: true,         // Kepegawaian Basic
                library: true,    // Perpustakaan
                executive: true,  // Executive View (Custom)
                foundation: true, // Portal Yayasan
                boarding: true,   // Asrama (Add-on)
                workspace: true,  // Workspace (Guru & Kepala)
                pos: true,        // POS (Add-on)
                payroll: true,    // HR & Payroll (Add-on)
                counseling: true, // BK & Kesiswaan (Add-on)
                people: true      // Data Induk (Hidden/System)
            },
            myProfile: (window.INITIAL_PROFILE || {}),
            isProfileEditing: false,
            profileForm: { phone: '', email: '', address: '' },
            usernameForm: { value: '' },
            passwordForm: { current: '', new1: '', new2: '' },
            profileLoading: false,
            waTestMessage: '',
            allowedUnits: Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS.map(u => String(u).toLowerCase()) : [],
            notificationCounts: {
                total: 0,
                student_incidents: 0,
                counseling_tickets: 0,
                facility_tickets: 0,
                vehicle_lending: 0,
                resource_lending: 0
            },
            showNotifications: false,
            incidentQuery: '',
            incidentSuggestions: [],
            incidentStudent: null,
            incidentSelected: [],
            incidentForm: { category: 'VIOLATION', title: '', description: '', severity: 'LOW' },
            incidentSubmitting: false,
            showIncidentModal: false,
            manualFetchOnly: (typeof window.MANUAL_FETCH_ONLY !== 'undefined' ? window.MANUAL_FETCH_ONLY : false),
            // PORTAL MODULES DATA
            portalModules: [
                {
                    title: 'Pelaksanaan & Evaluasi KBM',
                    module: 'academic',
                    gradient: 'from-emerald-400 to-teal-500',
                    items: [
                        { label: 'Jadwal Pelajaran', icon: 'fas fa-calendar-alt', action: 'modules/academic/schedule.php' },
                        { label: 'Jadwal Guru Mengajar', icon: 'fas fa-chalkboard-teacher', action: 'modules/academic/teacher_schedule.php' },
                        { label: 'Presensi Harian', icon: 'fas fa-user-check' },
                        { label: 'Jurnal Mengajar', icon: 'fas fa-book-open' },
                        { label: 'Input Nilai', icon: 'fas fa-marker' },
                        { label: 'e-Raport', icon: 'fas fa-print' },
                    ]
                },
                {
                    title: 'Perencanaan Akademik',
                    module: 'academic',
                    gradient: 'from-blue-400 to-indigo-500',
                    items: [
                        { label: 'Tahun Ajaran', icon: 'fas fa-calendar-check', action: 'modules/academic/years.php' },
                        { label: 'Pengaturan & Referensi', icon: 'fas fa-sliders-h', action: 'modules/academic/references.php' },
                        { label: 'Data Kelas', icon: 'fas fa-chalkboard', action: 'modules/academic/classes.php' },
                        { label: 'List Mapel', icon: 'fas fa-list-ul', action: 'modules/academic/subjects.php' },
                        { label: 'Jadwal Guru', icon: 'fas fa-chalkboard-teacher', action: 'modules/academic/teacher_schedule.php' },
                        { label: 'Jam Pelajaran', icon: 'fas fa-clock', action: 'modules/academic/time_slots.php' },
                        { label: 'Kalender Akademik', icon: 'far fa-calendar', action: 'modules/academic/calendar.php' },
                    ]
                },
                {
                    title: 'Bimbingan & Konseling',
                    module: 'counseling',
                    gradient: 'from-pink-500 to-rose-500',
                    items: [
                        { label: 'Buku Kasus', icon: 'fas fa-book-dead' },
                        { label: 'Poin Pelanggaran', icon: 'fas fa-gavel' },
                        { label: 'Jadwal Konseling', icon: 'fas fa-calendar-check' },
                        { label: 'Home Visit', icon: 'fas fa-home' },
                        { label: 'Peta Kerawanan', icon: 'fas fa-map-marked-alt' },
                        { label: 'Prestasi & Lomba', icon: 'fas fa-medal' },
                        { label: 'Minat Bakat', icon: 'fas fa-fingerprint' },
                        { label: 'Karir & Alumni', icon: 'fas fa-user-graduate' },
                        { label: 'Lapor Bullying', icon: 'fas fa-user-shield' },
                    ]
                },
                {
                    title: 'Keuangan',
                    module: 'finance',
                    gradient: 'from-orange-400 to-amber-500',
                    items: [
                        { label: 'Setup Keuangan', icon: 'fas fa-cogs' },
                        { label: 'Status Pembayaran', icon: 'fas fa-wallet' },
                        { label: 'Dashboard FLIP', icon: 'fas fa-exchange-alt' },
                        { label: 'Pembayaran Orang Tua', icon: 'fas fa-hand-holding-usd' },
                        { label: 'Riwayat Penerimaan', icon: 'fas fa-history' },
                    ]
                },
                {
                    title: 'Inventory & Asset',
                    module: 'inventory',
                    gradient: 'from-emerald-500 to-green-600',
                    items: [
                        { label: 'Aset Tetap', icon: 'fas fa-building', action: 'modules/inventory/dashboard.php' },
                        { label: 'Aset Bergerak', icon: 'fas fa-chair', action: 'modules/inventory/dashboard.php' },
                        { label: 'Kendaraan', icon: 'fas fa-car', action: 'modules/inventory/dashboard.php' },
                    ]
                },
                {
                    title: 'Kepegawaian',
                    module: 'hr',
                    gradient: 'from-violet-500 to-purple-600',
                    items: [
                        { label: 'Presensi Geolokasi', icon: 'fas fa-map-marker-alt' },
                        { label: 'Daftar Pegawai', icon: 'fas fa-users' },
                        { label: 'Perizinan Staf', icon: 'fas fa-envelope-open-text' },
                    ]
                },
                {
                    title: 'Admin',
                    module: 'core',
                    gradient: 'from-cyan-500 to-blue-500',
                    items: [
                        { label: 'List Akun', icon: 'fas fa-users-cog', action: 'users' },
                        { label: 'Bagan Lembaga', icon: 'fas fa-sitemap' },
                        { label: 'Master Lembar Data', icon: 'fas fa-database' },
                    ]
                }
            ],
            menuStructure: {
                "MENU UTAMA": [
                    { id: 'executive-view', label: 'Managerial View', icon: 'fas fa-chart-line', required: 'executive', url: 'modules/executive/index.php', tag: 'custom' },
                    { id: 'foundation-portal', label: 'Portal Yayasan', icon: 'fas fa-building', required: 'foundation', url: 'modules/foundation/index.php', tag: 'custom' },
                    { id: 'workspace-portal', label: 'Workspace', icon: 'fas fa-chalkboard-teacher', required: 'workspace', url: 'modules/workspace/index.php', tag: 'custom' },
                    { id: 'dash', label: 'Dashboard Utama', icon: 'fas fa-th-large', required: 'core', url: 'index.php', tag: 'core' },
                    { id: 'academic-portal', label: 'Akademik', icon: 'fas fa-graduation-cap', required: 'academic', url: 'modules/academic/index.php', tag: 'core' },
                    { id: 'finance-portal', label: 'Keuangan Sekolah', icon: 'fas fa-wallet', required: 'finance', url: 'modules/finance/dashboard.php', tag: 'core' },
                    { id: 'hr-portal', label: 'Kepegawaian Basic', icon: 'fas fa-users', required: 'hr', url: 'modules/personnel/dashboard.php', tag: 'core' },
                    { id: 'library-portal', label: 'Perpustakaan', icon: 'fas fa-book', required: 'library', url: 'modules/library/index.php', tag: 'core' },
                    { id: 'inventory-portal', label: 'Inventory & Aset', icon: 'fas fa-boxes', required: 'inventory', url: 'modules/inventory/dashboard.php', tag: 'add-on' },
                    { id: 'kiosk-portal', label: 'Info Kiosk Display', icon: 'fas fa-tv', required: 'kiosk', url: 'modules/kiosk/settings.php', tag: 'add-on' },
                    { id: 'boarding-portal', label: 'Asrama', icon: 'fas fa-bed', required: 'boarding', url: 'modules/boarding/index.php', tag: 'add-on' },
                    { id: 'pos-portal', label: 'POS (Kantin/Toko)', icon: 'fas fa-cash-register', required: 'pos', url: 'modules/pos/dashboard.php', tag: 'add-on' },
                    { id: 'hr-payroll', label: 'HR & Payroll', icon: 'fas fa-money-check-alt', required: 'hr', tag: 'add-on' },
                    { id: 'counseling-portal', label: 'BK & Kesiswaan', icon: 'fas fa-user-friends', required: 'counseling', url: 'modules/counseling/index.php', tag: 'add-on' },
                    { id: 'cleaning-portal', label: 'Kebersihan', icon: 'fas fa-broom', required: 'cleaning', url: 'modules/cleaning/index.php', tag: 'add-on' },
                    { id: 'security-portal', label: 'Keamanan', icon: 'fas fa-shield-alt', required: 'security', url: 'modules/security/index.php', tag: 'add-on' }
                ],
                "PENGATURAN": [
                    { id: 'profile', label: 'Pengaturan Profile', icon: 'fas fa-id-card', required: 'core', url: 'index.php?page=profile', tag: 'system' },
                    { id: 'settings', label: 'Pengaturan Sekolah', icon: 'fas fa-school', required: 'core', url: 'index.php?page=settings', tag: 'system' },
                    { id: 'users', label: 'Manajemen User', icon: 'fas fa-users-cog', required: 'core', url: 'index.php?page=users', tag: 'system' },
                    { id: 'backup', label: 'Backup & Restore', icon: 'fas fa-history', required: 'core', url: 'modules/admin/backup.php', tag: 'admin' }
                ]
            }
        };
    },
    computed: {
        currentDate() {
            return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        },
        currentPresetName() {
            if (this.currentPreset === 'basic') return 'Basic (Core Only)';
            if (this.currentPreset === 'partial') return 'Partial (Akademik + Perpus)';
            if (this.currentPreset === 'all') return 'All Rounder (Enterprise)';
            return 'Custom Configuration';
        },
        activeMenuGroups() {
            // Force re-evaluation by referencing modules directly
            const activeModules = this.modules;
            const allowed = (window.ALLOWED_MODULES || {});
            const result = {};
            for (const [groupName, items] of Object.entries(this.menuStructure)) {
                const role = (window.USER_ROLE || '').toUpperCase();
                let activeItems = [];
                if (groupName === 'PENGATURAN' && !['ADMIN','SUPERADMIN'].includes(role)) {
                    activeItems = items.filter(item => item.id === 'profile' && activeModules[item.required] === true);
                } else {
                    activeItems = items.filter(item => {
                        const key = item.required;
                        return activeModules[key] === true || allowed[key] === true;
                    });
                }
                
                if (activeItems.length > 0) {
                    result[groupName] = activeItems;
                }
            }
            return result;
        },
        activePortalModules() {
            return this.portalModules.filter(section => this.modules[section.module]);
        }
    },
    watch: {
        currentUnit(newVal) {
            localStorage.setItem('currentUnit', newVal);
            
            this.scheduleData = {}; 
            this.selectedClassId = '';
            
            // In manual mode, do not fetch if unit is 'all' or empty
            if (this.manualFetchOnly && (!newVal || newVal === 'all')) return;

            this.fetchAcademicData(newVal);
            this.fetchEmployees(newVal);
            // this.fetchStudents(newVal); 
            this.fetchOrgStructure(newVal); 
            this.fetchStaffList(); 
            // this.loadNotifications(); // DISABLED TEMPORARILY
        },
        currentPage(newVal) {
            if (newVal === 'profile') {
                this.fetchMyProfile();
            }
            if (newVal === 'settings') {
                this.fetchSettings();
            }
            if (newVal === 'users') {
                this.fetchUsers();
            }
        }
    },
    methods: {
        getBaseUrl() {
            const fromHeader = window.BASE_URL;
            if (fromHeader && typeof fromHeader === 'string' && fromHeader.length > 0) {
                return fromHeader;
            }
            const p = window.location.pathname || '';
            const m = p.match(/^\/(AIS|AIStest)\//i);
            return m ? `/${m[1]}/` : '/';
        },
        async sendWaTest() {
            try {
                const base = this.getBaseUrl();
                const r = await fetch(base + 'api/security.php?action=send_wa_test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: this.waTestMessage || 'Test WA dari AIS', target: this.schoolSettings.wa_security_target || '' })
                });
                const j = await r.json();
                if (j.success) { alert('Pesan WA terkirim'); }
                else { alert('Gagal mengirim WA: ' + (j.message || '')); }
            } catch (e) {
                alert('Terjadi kesalahan saat mengirim WA');
            }
        },
        toggleNotifications() {
            this.showNotifications = !this.showNotifications;
        },
        async loadNotifications() {
            try {
                const base = this.getBaseUrl();
                const res = await fetch(base + 'api/notifications.php?action=get_counts');
                const ct = res.headers.get('content-type') || '';
                if (ct.includes('application/json')) {
                    const data = await res.json();
                    if (data && data.success && data.data) {
                        this.notificationCounts = data.data;
                    }
                }
            } catch (_) {}
        },
        async loadDashboardAgenda() {
            if (window.INITIAL_AGENDA) {
                this.renderAgendaHTML(window.INITIAL_AGENDA);
                return;
            }
            try {
                const now = new Date();
                const y = now.getFullYear();
                const m = now.getMonth() + 1;
                const start = `${y}-${String(m).padStart(2, '0')}-01`;
                const end = new Date(y, m, 0).toISOString().slice(0,10);
                const base = this.getBaseUrl();
                const res = await fetch(base + `api/manage_agenda.php?action=get_agenda&start=${start}&end=${end}&unit=${encodeURIComponent(this.currentUnit)}`);
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    const root = document.getElementById('agendaList');
                    if (root) root.innerHTML = `<div class="text-slate-400 italic text-sm">Gagal memuat agenda</div>`;
                    return;
                }
                const data = await res.json();
                const items = (data && data.success) ? (data.data || []) : [];
                this.renderAgendaHTML(items);
            } catch (_) {
                const root = document.getElementById('agendaList');
                if (root) root.innerHTML = `<div class="text-slate-400 italic text-sm">Terjadi kesalahan</div>`;
            }
        },
        renderAgendaHTML(items) {
            const root = document.getElementById('agendaList');
            if (!root) return;
            if (!items || items.length === 0) {
                root.innerHTML = `<div class="text-slate-400 italic text-sm">Tidak ada agenda bulan ini.</div>`;
                return;
            }
            const fmt = (d) => {
                try { return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }); } catch(_) { return d; }
            };
            root.innerHTML = items.slice(0, 8).map(ev => {
                const date = fmt(ev.start_date);
                const t = (ev.title || '').toString();
                const typ = (ev.type || 'EVENT');
                const color = ev.color || (typ === 'ACADEMIC' ? '#1e40af' : (typ === 'HOLIDAY' ? '#dc2626' : '#0ea5e9'));
                return `<div class="flex items-center gap-3 p-2 rounded-lg border border-slate-100">
                    <div class="w-10 text-center">
                        <div class="text-[10px] font-bold text-slate-500 uppercase">${date.split(' ')[1] || ''}</div>
                        <div class="text-[13px] font-bold text-slate-800">${date.split(' ')[0] || ''}</div>
                    </div>
                    <div class="flex-1">
                        <div class="text-[12px] font-bold" style="color:${color}">${t}</div>
                        <div class="text-[11px] text-slate-500">${(ev.location || '').toString()}</div>
                    </div>
                </div>`;
            }).join('');
        },
        async loadAnnouncements() {
            try {
                const base = this.getBaseUrl();
                const res = await fetch(base + 'api/manage_agenda.php?action=get_announcements');
                const ct = res.headers.get('content-type') || '';
                const root = document.getElementById('annBox');
                if (!root) return;
                if (!ct.includes('application/json')) {
                    const txt = await res.text();
                    root.innerHTML = `<div class="text-slate-400 italic py-4">Gagal memuat pengumuman</div>`;
                    return;
                }
                const data = await res.json();
                const items = (data && data.success) ? (data.data || []) : [];
                if (!items || items.length === 0) {
                    root.innerHTML = `<div class="text-center text-slate-400 italic py-4">Belum ada pengumuman.</div>`;
                    return;
                }
                const fmt = (d) => {
                    try { return new Date(d).toLocaleString('id-ID', { year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' }); } catch(_) { return d; }
                };
                root.innerHTML = items.slice(0, 5).map(a => {
                    return `<div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                        <div class="flex items-center justify-between">
                            <div class="text-[12px] font-bold text-slate-700">${(a.title || '').toString()}</div>
                            <div class="text-[10px] text-slate-400">${fmt(a.created_at || '')}</div>
                        </div>
                        <div class="text-[12px] text-slate-700 mt-1">${(a.content || '').toString()}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Oleh: ${(a.created_by || '').toString()}</div>
                    </div>`;
                }).join('');
            } catch (_) {
                const root = document.getElementById('annBox');
                if (root) root.innerHTML = `<div class="text-slate-400 italic py-4">Gagal memuat pengumuman</div>`;
            }
        },
        async loadMandatoryClasses() {
            try {
                const base = this.getBaseUrl();
                const root = document.getElementById('mandatoryClassesBox');
                if (!root) return;
                // Try fetch from LMS API if available
                let items = null;
                try {
                    const res = await fetch(base + 'api/lms.php?action=mandatory_classes');
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        const data = await res.json();
                        items = (data && data.success) ? (data.data || null) : null;
                    }
                } catch (_) {}
                // Fallback dummy data
                if (!items || !Array.isArray(items) || items.length === 0) {
                    items = [
                        { subject: 'Aqidah Dasar', teacher: 'Ust. Galih Suharsa, Lc' },
                        { subject: 'Fiqih Dasar', teacher: 'Ust. Lanlan Tuhfatulanfas, Lc., MA' },
                        { subject: 'Tahsin', teacher: 'Ust. Ruman Al Wafi, Lc., MA' },
                        { subject: 'Bahasa Arab', teacher: 'Ust. Feri' },
                    ];
                }
                const total = items.length;
                const completed = Math.max(0, Math.min(total, Math.round(total * 0.6)));
                const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
                const meter = `
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="text-[11px] font-bold text-slate-600">Keterpenuhan Kelas Wajib</div>
                            <div class="text-[11px] font-bold text-indigo-700">${percent}%</div>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded">
                            <div class="h-2 bg-indigo-600 rounded" style="width:${percent}%"></div>
                        </div>
                        <div class="text-[10px] text-slate-500 mt-1">Terpenuhi ${completed}/${total}</div>
                    </div>
                `;
                const list = items.slice(0, 8).map(it => {
                    const sub = (it.subject || '').toString();
                    const t = (it.teacher || '').toString();
                    const s = sub || 'CLASS';
                    let h = 0;
                    for (let i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) % 1000; }
                    const pct = 30 + (h % 61);
                    const totalSess = 6;
                    const doneSess = Math.max(0, Math.min(totalSess, Math.round((pct / 100) * totalSess)));
                    return `<div class="flex items-center gap-3 p-2 rounded-lg border border-indigo-200 bg-indigo-50">
                        <div class="w-9 h-9 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center"><i class="fas fa-book-open"></i></div>
                        <div class="flex-1">
                            <div class="text-[12px] font-bold text-indigo-700">${sub}</div>
                            <div class="text-[11px] text-slate-600">Pengampu: ${t}</div>
                            <div class="mt-1">
                                <div class="h-1.5 w-full bg-slate-200 rounded">
                                    <div class="h-1.5 bg-indigo-600 rounded" style="width:${pct}%"></div>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">Progress ${pct}% • ${doneSess}/${totalSess} sesi</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-pink-100 text-pink-700">WAJIB</span>
                    </div>`;
                }).join('');
                root.innerHTML = meter + list;
            } catch (_) {
                const root = document.getElementById('mandatoryClassesBox');
                if (root) root.innerHTML = `<div class="text-center text-slate-400 italic py-4">Gagal memuat daftar kelas</div>`;
            }
        },
        async loadDashboardTasks() {
            try {
                const base = this.getBaseUrl();
                const root = document.getElementById('tasksContainer');
                if (!root) return;
                const items = [];
                try {
                    const res = await fetch(base + 'api/security.php?action=zero_report_overview');
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        const data = await res.json();
                        const shifts = (data && data.success) ? (data.data?.daily?.shifts || []) : [];
                        const uid = (window.USER_ID != null) ? Number(window.USER_ID) : null;
                        const pid = (window.PERSON_ID != null) ? Number(window.PERSON_ID) : null;
                        const myTasks = shifts.filter(s => {
                            const assignedUser = (s.user_id != null) ? Number(s.user_id) : null;
                            const assignedPerson = (s.person_id != null) ? Number(s.person_id) : null;
                            return s.status === 'NOT_REPORTED' && (
                                (uid != null && assignedUser === uid) ||
                                (pid != null && assignedPerson === pid)
                            );
                        });
                        for (const t of myTasks.slice(0, 8)) {
                            const end = (t.window_end || '').toString();
                            const who = (t.employee_name || '').toString();
                            const tmpl = t.default_template_id ? `• Template #${t.default_template_id}` : '';
                            items.push(
                                `<div class="flex items-center gap-3 p-2 rounded-lg border border-emerald-200 bg-emerald-50">
                                    <div class="w-9 h-9 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fas fa-clipboard-check"></i></div>
                                    <div class="flex-1">
                                        <div class="text-[12px] font-bold text-emerald-700">Checklist Shift sebelum ${end}</div>
                                        <div class="text-[11px] text-slate-600">${who ? ('Petugas: ' + who + ' ') : ''}${tmpl}</div>
                                    </div>
                                    <a href="${base}modules/security/index.php" class="text-[11px] font-bold text-emerald-700 hover:underline">Buka</a>
                                </div>`
                            );
                        }
                    }
                } catch (_) {}
                try {
                    const now = new Date();
                    const year = now.getFullYear();
                    const r = await fetch(base + 'api/attendance.php?action=missing_class_recaps_by_month&year=' + year + '&unit=all');
                    const ct2 = r.headers.get('content-type') || '';
                    if (ct2.includes('application/json')) {
                        const j = await r.json();
                        const rows = (j && j.success) ? (j.data || []) : [];
                        const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        const tasks = rows.filter(d => (parseInt(d.missing,10)||0) > 0);
                        for (const d of tasks.slice(0, 8)) {
                            const mIdx = Math.max(1, Math.min(12, parseInt(d.month,10)||1)) - 1;
                            const label = monthNames[mIdx] + ' ' + (d.year || year);
                            const count = parseInt(d.missing,10) || 0;
                            items.push(
                                `<div class="flex items-center gap-3 p-2 rounded-lg border border-amber-200 bg-amber-50">
                                    <div class="w-9 h-9 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center"><i class="fas fa-list-alt"></i></div>
                                    <div class="flex-1">
                                        <div class="text-[12px] font-bold text-amber-700">Lengkapi rekap presensi ${label}</div>
                                        <div class="text-[11px] text-slate-600">${count} kelas belum rekap</div>
                                    </div>
                                    <a href="${base}modules/academic/attendance_summary.php" class="text-[11px] font-bold text-amber-700 hover:underline">Buka</a>
                                </div>`
                            );
                        }
                    }
                } catch (_) {}
                if (items.length === 0) {
                    root.innerHTML = `<div class="text-center text-slate-400 italic py-4">Belum ada tugas ditampilkan.</div>`;
                    return;
                }
                root.innerHTML = items.slice(0, 8).join('');
            } catch (_) {
                const root = document.getElementById('tasksContainer');
                if (root) root.innerHTML = `<div class="text-center text-slate-400 italic py-4">Gagal memuat tugas</div>`;
            }
        },
        switchUnit() {
            if (this.currentUnit === 'tk') { this.modules.boarding = false; this.modules.exams = false; }
            else if (this.currentUnit === 'sma') { this.setPreset('all'); }
            else { this.setPreset('all'); }
        },
        navigate(pageId) {
            // Close sidebar on mobile when navigating
            this.isSidebarOpen = false;
            if (pageId === 'profile') {
                this.currentPage = 'profile';
                return;
            }
            if (pageId === 'settings') {
                this.currentPage = 'settings';
                return;
            }
            if (pageId === 'users') {
                this.currentPage = 'users';
                return;
            }
            if (pageId === 'finance-portal') {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                window.location.href = baseUrl + 'modules/finance/dashboard.php';
                return;
            }
            if (pageId && (pageId.includes('.php') || pageId.includes('?'))) {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                window.location.href = baseUrl + pageId;
                return;
            }

            // Check if pageId is an object or string
            let targetId = pageId;
            
            // Find item in menuStructure
            let item = null;
            for (const group in this.menuStructure) {
                const found = this.menuStructure[group].find(i => i.id === targetId);
                if (found) { item = found; break; }
            }

            if (item && item.url) {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                window.location.href = baseUrl + item.url;
                return;
            }
            
            this.currentPage = targetId;
        },
        toggleSidebar() { this.isSidebarOpen = !this.isSidebarOpen; },
        closeSidebar() { this.isSidebarOpen = false; },
        async searchIncidentStudents() {
            try {
                const q = (this.incidentQuery || '').trim();
                if (q.length < 2) { this.incidentSuggestions = []; return; }
                const base = this.getBaseUrl();
                const r = await fetch(base + 'api/counseling.php?action=search_students&q=' + encodeURIComponent(q));
                const j = await r.json();
                // Guard against stale responses: if current query changed, ignore this result
                const current = (this.incidentQuery || '').trim();
                if (current.length < 2 || current !== q) { return; }
                const list = (j && j.success) ? (j.data || []) : [];
                const selectedIds = new Set(this.incidentSelected.map(s => s.id));
                this.incidentSuggestions = list.filter(s => !selectedIds.has(s.id));
            } catch (_) { this.incidentSuggestions = []; }
        },
        pickIncidentStudent(s) {
            if (!s || !s.id) return;
            const exists = this.incidentSelected.find(x => x.id === s.id);
            if (!exists) {
                this.incidentSelected.push(s);
            }
            this.incidentSuggestions = (this.incidentSuggestions || []).filter(x => x.id !== s.id);
            this.incidentQuery = '';
            this.incidentSuggestions = [];
            this.$nextTick(() => {
                try {
                    if (this.$refs && this.$refs.incidentInput && typeof this.$refs.incidentInput.focus === 'function') {
                        this.$refs.incidentInput.focus();
                    }
                } catch (_) {}
            });
        },
        removeIncidentStudent(s) {
            this.incidentSelected = this.incidentSelected.filter(x => x.id !== s.id);
            const q = (this.incidentQuery || '').trim();
            if (q.length >= 2) {
                this.searchIncidentStudents();
            }
        },
        openIncidentModal() {
            this.showIncidentModal = true;
        },
        closeIncidentModal() {
            this.showIncidentModal = false;
        },
        generateRequestId() {
            const t = Date.now().toString(36);
            const r = Math.random().toString(36).slice(2, 8);
            return 'INC-' + t + '-' + r;
        },
        async submitIncidentReport() {
            try {
                const count = this.incidentSelected.length || (this.incidentStudent && this.incidentStudent.id ? 1 : 0);
                if (count < 1) { alert('Pilih setidaknya satu siswa'); return; }
                const title = (this.incidentForm.title || '').trim();
                const category = (this.incidentForm.category || '').trim();
                if (!title || !category) { alert('Kategori dan judul wajib diisi'); return; }
                this.incidentSubmitting = true;
                const base = this.getBaseUrl();
                let j = null;
                if (this.incidentSelected.length > 1) {
                    const payload = {
                        student_ids: this.incidentSelected.map(s => s.id),
                        category,
                        title,
                        description: (this.incidentForm.description || '').trim(),
                        severity: (this.incidentForm.severity || 'LOW'),
                        base_request_id: this.generateRequestId()
                    };
                    const r = await fetch(base + 'api/student_incidents.php?action=submit_batch', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    j = await r.json();
                } else {
                    const targetId = this.incidentSelected[0]?.id || this.incidentStudent.id;
                    const payload = {
                        student_id: targetId,
                        category,
                        title,
                        description: (this.incidentForm.description || '').trim(),
                        severity: (this.incidentForm.severity || 'LOW'),
                        request_id: this.generateRequestId()
                    };
                    const r = await fetch(base + 'api/student_incidents.php?action=submit', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    j = await r.json();
                }
                if (j && j.success) {
                    alert('Laporan terkirim');
                    this.incidentStudent = null;
                    this.incidentSelected = [];
                    this.incidentQuery = '';
                    this.incidentForm = { category: 'VIOLATION', title: '', description: '', severity: 'LOW' };
                    this.showIncidentModal = false;
                } else {
                    alert('Gagal: ' + ((j && (j.error || j.message)) || ''));
                }
            } catch (e) {
                alert('Terjadi kesalahan sistem');
            } finally {
                this.incidentSubmitting = false;
            }
        },
        async fetchMyProfile() {
            if (window.INITIAL_PROFILE && Object.keys(window.INITIAL_PROFILE).length > 0) {
                this.myProfile = window.INITIAL_PROFILE;
                this.usernameForm.value = this.myProfile.username || '';
                // Optional: If you want to refresh data anyway, remove 'return'
                return;
            }
            try {
                const res = await fetch(this.getBaseUrl() + 'api/get_my_profile.php');
                const data = await res.json();
                if (data && data.success) {
                    this.myProfile = data.data || {};
                    this.usernameForm.value = this.myProfile.username || '';
                } else {
                    console.error('Profile fetch failed:', data.message);
                }
            } catch (e) {
                console.error('Profile fetch error:', e);
            }
        },
        setPreset(type) {
            this.currentPreset = type;
            if (type === 'basic') {
                // Core Only
                this.modules = { 
                    core: true, academic: true, finance: true, hr: true, library: true,
                    executive: false, foundation: false,
                    principal: false,
                    boarding: false, pos: false, payroll: false, counseling: false, inventory: false,
                    people: true 
                };
            } else if (type === 'partial') {
                // Core + Custom (Executive)
                this.modules = { 
                    core: true, academic: true, finance: true, hr: true, library: true,
                    executive: true, foundation: false, principal: false,
                    boarding: false, pos: false, payroll: false, counseling: false, inventory: false,
                    people: true
                };
            } else if (type === 'all') {
                // All Modules (Enterprise)
                this.modules = {
                    core: true, academic: true, finance: true, hr: true, library: true,
                    executive: true, foundation: true, principal: true,
                    boarding: true, pos: true, payroll: true, counseling: true, kiosk: true, inventory: true,
                    people: true
                };
            }
            // IMPORTANT: Force Vue reactivity update
            this.modules = {...this.modules};
        },
        async fetchGlobalUnits() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_units.php');
                const units = await response.json();
                const role = (window.USER_ROLE || '').toUpperCase();
                if (this.allowedUnits.length > 0 && !['SUPERADMIN','ADMIN'].includes(role)) {
                    const norm = (v) => String(v || '').toLowerCase();
                    this.allUnits = units.filter(u => {
                        const code = norm(u.code);
                        const rc = norm(u.receipt_code);
                        return this.allowedUnits.includes(code) || this.allowedUnits.includes(rc);
                    });
                    const allowedCodes = this.allUnits.map(u => norm(u.code || u.receipt_code));
                    const curr = norm(this.currentUnit);
                    if (!allowedCodes.includes(curr)) {
                        const next = allowedCodes[0] || 'all';
                        this.currentUnit = next;
                        localStorage.setItem('currentUnit', this.currentUnit);
                    }
                } else {
                    this.allUnits = units;
                }
            } catch (error) {
                console.error('Error fetching global units:', error);
            }
        },
        async changePassword() {
            const role = (window.USER_ROLE || '').toUpperCase();
            if (!this.passwordForm.new1 || !this.passwordForm.new2) { alert('Lengkapi password baru dan konfirmasi'); return; }
            if (this.passwordForm.new1 !== this.passwordForm.new2) { alert('Konfirmasi password tidak cocok'); return; }
            const proceedWithForce = async () => {
                this.profileLoading = true;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/manage_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'update',
                            id: this.myProfile.user_id,
                            username: this.myProfile.username,
                            password: this.passwordForm.new1,
                            role: this.myProfile.role
                        })
                    });
                    const result = await res.json();
                    if (result.success) {
                        alert('Password berhasil diubah');
                        this.passwordForm = { current: '', new1: '', new2: '' };
                    } else {
                        alert('Gagal: ' + (result.message || ''));
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem');
                } finally {
                    this.profileLoading = false;
                }
            };
            // Admin/Superadmin boleh tanpa password lama dengan konfirmasi
            if (['ADMIN','SUPERADMIN'].includes(role) && !this.passwordForm.current) {
                this.confirmAction(
                    'Ganti Password tanpa verifikasi?',
                    'Anda akan mengganti password tanpa memasukkan password saat ini. Pastikan tindakan ini disetujui.',
                    proceedWithForce
                );
                return;
            }
            // Default: butuh password saat ini
            if (!this.passwordForm.current) { alert('Password saat ini wajib diisi'); return; }
            this.confirmAction(
                'Ganti Password?',
                'Anda akan mengganti password akun Anda. Pastikan password baru aman dan mudah diingat.',
                async () => {
                    this.profileLoading = true;
                    try {
                        const res = await fetch(this.getBaseUrl() + 'api/change_password.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ current_password: this.passwordForm.current, new_password: this.passwordForm.new1 })
                        });
                        const result = await res.json();
                        if (result.success) {
                            alert('Password berhasil diubah');
                            this.passwordForm = { current: '', new1: '', new2: '' };
                        } else {
                            alert('Gagal: ' + (result.message || ''));
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan sistem');
                    } finally {
                        this.profileLoading = false;
                    }
                }
            );
        },
        toggleEditProfile() {
            // Get phone from person_phone OR custom_attributes.mobile_phone
            let phone = this.myProfile.person_phone || '';
            if (!phone && this.myProfile.custom_attributes) {
                try {
                    const ca = typeof this.myProfile.custom_attributes === 'string' 
                        ? JSON.parse(this.myProfile.custom_attributes) 
                        : this.myProfile.custom_attributes;
                    if (ca && ca.mobile_phone) {
                        phone = ca.mobile_phone;
                    }
                } catch (e) {}
            }
            
            this.profileForm.phone = phone;
            this.profileForm.email = this.myProfile.person_email || this.myProfile.user_email || '';
            this.profileForm.address = this.myProfile.person_address || '';
            this.isProfileEditing = true;
        },
        cancelEditProfile() {
            this.isProfileEditing = false;
        },
        async saveProfileData() {
            this.profileLoading = true;
            try {
                const res = await fetch(this.getBaseUrl() + 'api/update_my_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.profileForm)
                });
                const result = await res.json();
                if (result.success) {
                    alert('Profil berhasil diperbarui');
                    this.isProfileEditing = false;
                    this.fetchMyProfile();
                } else {
                    alert('Gagal: ' + (result.message || ''));
                }
            } catch (e) {
                alert('Terjadi kesalahan sistem');
            } finally {
                this.profileLoading = false;
            }
        },
        async changeUsername() {
            const newUsername = (this.usernameForm.value || '').trim();
            if (!newUsername) { alert('Username tidak boleh kosong'); return; }
            this.confirmAction(
                'Ubah Username?',
                'Mengubah username dapat memengaruhi proses login. Pastikan Anda mengingat username baru ini.',
                async () => {
                    this.profileLoading = true;
                    try {
                        const res = await fetch(this.getBaseUrl() + 'api/manage_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update',
                                id: this.myProfile.user_id,
                                username: newUsername,
                                role: this.myProfile.role,
                                password: '' // tidak mengubah password
                            })
                        });
                        const result = await res.json();
                        if (result.success) {
                            alert('Username berhasil diubah');
                            this.fetchMyProfile();
                        } else {
                            alert('Gagal: ' + (result.message || ''));
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan sistem');
                    } finally {
                        this.profileLoading = false;
                    }
                }
            );
        }
    },
    mounted() {
        // Reveal content after mount
        this.$nextTick(() => {
            this.isMounted = true;
        });

        // Auto-detect Page
        const path = window.location.pathname;
        const urlParams = new URLSearchParams(window.location.search);
        const pageParam = urlParams.get('page');

        if (window.MANUAL_FETCH_ONLY) this.manualFetchOnly = true;

        if (path.includes('modules/academic/index.php')) {
            this.currentPage = pageParam || 'academic-portal';
        } else if (pageParam) {
            this.currentPage = pageParam;
        } else if (path.includes('modules/academic/years.php')) {
            // Auto-trigger sync if on academic_year page
            if (this.syncYears) this.syncYears();
        }

        if (!this.manualFetchOnly) this.setPreset('all');
        if (window.ALLOWED_MODULES) {
            const keys = Object.keys(this.modules);
            const allowed = window.ALLOWED_MODULES;
            const next = {};
            for (const k of keys) {
                next[k] = k === 'core' ? true : !!allowed[k];
            }
            this.modules = next;
        }

        // Optimized Loading Strategy
        const loadHeavyData = () => {
            if (this.manualFetchOnly) return; // Respect manual fetch flag
            this.fetchAcademicData(this.currentUnit); 
            this.fetchEmployees(this.currentUnit);
            // this.fetchStudents(this.currentUnit); // Disabled for performance
            this.fetchOrgStructure(this.currentUnit);
            this.fetchGlobalUnits();
        };

        if (window.DASHBOARD_LAZY_LOAD) {
            // Dashboard: Defer heavy data to prioritize UI & Widgets
            // Removed delay to speed up loading perception
            setTimeout(loadHeavyData, 10);
        } else {
            // Modules: Load immediately
            loadHeavyData();
        }

        // Always fetch settings to ensure logo is loaded
        if (this.fetchSettings) this.fetchSettings();
        // this.loadNotifications(); // DISABLED TEMPORARILY
        if (this.currentPage === 'profile') this.fetchMyProfile();
        // Dashboard widgets
        if ((path.endsWith('/index.php') || path.split('/').pop() === 'index.php') && !this.manualFetchOnly) {
            this.loadDashboardAgenda();
            this.loadAnnouncements();
            this.loadDashboardTasks();
            this.loadMandatoryClasses();
        }
    }
});

(() => {
    const mountGlobal = () => {
        try {
            if (window.__GLOBAL_APP_MOUNTED) return true;
            if (window.SKIP_GLOBAL_APP) return false;
            const el = document.querySelector('#app');
            if (!el) return false;
            app.component('tree-item', TreeItem);
            app.mount('#app');
            window.__GLOBAL_APP_MOUNTED = true;
            return true;
        } catch (e) {
            console.error('Global mount error:', e);
            return false;
        }
    };

    mountGlobal();
})();

(() => {
    const showError = (title, detail) => {
        try {
            const ov = document.getElementById('js-error-overlay');
            const t = document.getElementById('js-error-title');
            const d = document.getElementById('js-error-details');
            if (ov && t && d) {
                t.textContent = title || 'Error';
                d.textContent = (detail || '').toString();
                ov.classList.remove('hidden');
            }
        } catch (_) {}
    };
    let __error_once = false;
    window.addEventListener('error', (e) => {
        if (__error_once) return;
        __error_once = true;
        const msg = (e && e.message) ? e.message : 'Unknown error';
        const loc = (e && e.filename) ? (e.filename + ':' + (e.lineno || '') + ':' + (e.colno || '')) : '';
        showError('Aplikasi crash', msg + (loc ? ('\n' + loc) : ''));
    });
    window.addEventListener('unhandledrejection', (e) => {
        if (__error_once) return;
        __error_once = true;
        const reason = e && e.reason ? e.reason : 'Unhandled rejection';
        showError('Aplikasi crash', String(reason));
    });
})();
