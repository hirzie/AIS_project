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
            <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-business-time text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Pengaturan Shift Keamanan</h1>
                <span class="text-xs text-slate-500 font-medium">Set jadwal kerja petugas</span>
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
                <h3 class="font-bold text-slate-800 mb-4">Form Shift</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500">Pilih Pegawai</label>
                        <select v-model="form.employee_id" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            <option value="">-- pilih --</option>
                            <option v-for="s in securityStaff" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Nama Shift</label>
                        <input v-model="form.shift_name" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Contoh: Pagi">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500">Mulai</label>
                            <input type="time" v-model="form.start_time" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Selesai</label>
                            <input type="time" v-model="form.end_time" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Hari</label>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Senin"> Senin</label>
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Selasa"> Selasa</label>
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Rabu"> Rabu</label>
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Kamis"> Kamis</label>
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Jumat"> Jumat</label>
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Sabtu"> Sabtu</label>
                            <label class="text-xs"><input type="checkbox" v-model="form.days" value="Minggu"> Minggu</label>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500">Template Checklist Default</label>
                            <select v-model="form.default_template_id" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                <option :value="null">-- tidak ada --</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Durasi Jendela (menit)</label>
                            <input type="number" min="5" max="240" step="5" v-model.number="form.window_minutes" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="60">
                            <div class="text-[10px] text-slate-500 mt-1">Contoh: 30 untuk 30 menit; 120 untuk 2 jam</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="requireChecklist" type="checkbox" v-model="form.require_checklist" class="rounded">
                        <label for="requireChecklist" class="text-xs text-slate-600">Wajib Checklist untuk shift ini</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="autoSendWindow" type="checkbox" v-model="form.auto_send_on_window" class="rounded">
                        <label for="autoSendWindow" class="text-xs text-slate-600">Kirim otomatis saat jendela tertutup</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="sendIfEmpty" type="checkbox" v-model="form.send_if_empty" class="rounded">
                        <label for="sendIfEmpty" class="text-xs text-slate-600">Kirim pesan kosong jika tidak ada checklist</label>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Efektif Tanggal (opsional)</label>
                        <input type="date" v-model="form.effective_date" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex gap-2">
                        <button @click="saveShift" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-amber-600">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button @click="resetForm" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-200">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Daftar Shift</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 uppercase text-xs">
                            <th class="py-2">Pegawai</th>
                            <th class="py-2">Shift</th>
                            <th class="py-2">Jam</th>
                            <th class="py-2">Hari</th>
                            <th class="py-2 w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in shifts" :key="s.id" class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-2 font-medium text-slate-800">{{ s.employee_name || ('#' + s.employee_id) }}</td>
                            <td class="py-2">
                                <div class="flex flex-col">
                                    <div>{{ s.shift_name }}<span v-if="s.require_checklist == 1" class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 font-bold">Wajib</span></div>
                                    <div class="text-[11px] text-slate-500">
                                        <span v-if="s.window_minutes">Jendela: {{ s.window_minutes }} menit</span>
                                        <span v-if="s.default_template_id"> • Template: {{ templateName(s.default_template_id) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-2">{{ s.start_time }} - {{ s.end_time }}</td>
                            <td class="py-2">{{ parseDays(s.days_json).join(', ') }}</td>
                            <td class="py-2">
                                <button @click="editShift(s)" class="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700 mr-2"><i class="fas fa-edit"></i> Edit</button>
                                <button @click="deleteShift(s)" class="text-xs px-2 py-1 rounded bg-red-100 text-red-700"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="shifts.length === 0">
                            <td colspan="5" class="py-6 text-center text-slate-500">Belum ada data shift</td>
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
            staff: [],
            shifts: [],
            templates: [],
            form: {
                id: null,
                employee_id: '',
                shift_name: '',
                start_time: '',
                end_time: '',
                days: [],
                window_minutes: 60,
                default_template_id: null,
                require_checklist: true,
                auto_send_on_window: false,
                send_if_empty: false,
                effective_date: ''
            }
        };
    },
    computed: {
        securityStaff() {
            return (this.staff || []).filter(x => (x.role || x.employee_type || '').toUpperCase() === 'SECURITY');
        }
    },
    methods: {
        parseDays(json) {
            try { const arr = JSON.parse(json || '[]'); return Array.isArray(arr) ? arr : []; }
            catch(_) { return []; }
        },
        templateName(id) {
            const t = (this.templates || []).find(x => x.id === id);
            return t ? t.name : ('#' + id);
        },
        resetForm() {
            this.form = { id: null, employee_id: '', shift_name: '', start_time: '', end_time: '', days: [], window_minutes: 60, default_template_id: null, require_checklist: true, auto_send_on_window: false, send_if_empty: false, effective_date: '' };
        },
        async fetchEmployees() {
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=list_employees');
                const j = await r.json();
                this.staff = j.success ? (j.data || []) : [];
            } catch (e) { console.error(e); }
        },
        async fetchTemplates() {
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=list_checklist_templates');
                const j = await r.json();
                this.templates = j.success ? (j.data || []) : [];
            } catch (e) { console.error(e); }
        },
        async fetchShifts() {
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=list_shifts');
                const j = await r.json();
                this.shifts = j.success ? (j.data || []) : [];
            } catch (e) { console.error(e); }
        },
        async saveShift() {
            try {
                const payload = { ...this.form };
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=save_shift', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const j = await r.json();
                if (j.success) {
                    alert('Shift tersimpan');
                    this.resetForm();
                    this.fetchShifts();
                } else {
                    alert(j.message || 'Gagal menyimpan');
                }
            } catch (e) { console.error(e); alert('Gagal menyimpan'); }
        },
        editShift(s) {
            this.form = {
                id: s.id,
                employee_id: s.employee_id,
                shift_name: s.shift_name,
                start_time: s.start_time,
                end_time: s.end_time,
                days: this.parseDays(s.days_json),
                window_minutes: s.window_minutes || 60,
                default_template_id: s.default_template_id || null,
                require_checklist: (s.require_checklist == 1),
                auto_send_on_window: (s.auto_send_on_window == 1),
                send_if_empty: (s.send_if_empty == 1),
                effective_date: s.effective_date || ''
            };
        },
        async deleteShift(s) {
            if (!confirm('Hapus shift ini?')) return;
            try {
                const r = await fetch((window.BASE_URL || '/') + 'api/security.php?action=delete_shift', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: s.id })
                });
                const j = await r.json();
                if (j.success) {
                    this.fetchShifts();
                } else {
                    alert(j.message || 'Gagal menghapus');
                }
            } catch (e) { console.error(e); }
        }
    },
    async mounted() {
        await this.fetchEmployees();
        await this.fetchTemplates();
        await this.fetchShifts();
    }
}).mount('#app');
</script>
<?php require_once '../../includes/footer.php'; ?>
