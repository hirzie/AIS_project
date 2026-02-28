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
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-clipboard-check text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Pengaturan Checklist Keamanan</h1>
                <span class="text-xs text-slate-500 font-medium">Template dan Input Checklist</span>
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
                <h3 class="font-bold text-slate-800 mb-4">Buat/ubah Template</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-500 mb-1 block">Nama Template</label>
                            <input v-model="form.name" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Patroli Malam">
                        </div>
                        <div class="md:col-span-1">
                            <label class="text-xs text-slate-500 mb-1 block">Template</label>
                            <select v-model="selectedTemplateId" @change="loadSelectedTemplate" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">- Pilih -</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">Item Checklist</div>
                        <div class="p-3 space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                                <div class="md:col-span-7">
                                    <input v-model="newItem.label" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Nama item">
                                </div>
                                <div class="md:col-span-3">
                                    <select v-model="newItem.type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                                        <option value="BOOLEAN">Ya/Tidak + Keterangan</option>
                                        <option value="COUNT_NOTE">Jumlah + Keterangan</option>
                                        <option value="PACKAGE">Paket (jumlah + untuk siapa)</option>
                                        <option value="VEHICLE">Kendaraan (keluar/ada)</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <button @click="addItem" class="w-full bg-emerald-600 text-white px-3 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700"><i class="fas fa-plus"></i> Tambah</button>
                                </div>
                            </div>
                            <div v-for="(it,idx) in form.items" :key="idx" class="flex items-center justify-between border border-slate-200 rounded-lg p-3">
                                <div class="flex-1">
                                    <div class="font-bold text-slate-800 text-sm">{{ it.label }}</div>
                                    <div class="text-[11px] text-slate-500">{{ typeLabel(it.type) }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="moveUp(idx)" class="px-2 py-1 text-[11px] font-bold border border-slate-300 rounded">Up</button>
                                    <button @click="moveDown(idx)" class="px-2 py-1 text-[11px] font-bold border border-slate-300 rounded">Down</button>
                                    <button @click="removeItem(idx)" class="px-2 py-1 text-[11px] font-bold text-red-600">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="saveTemplate" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button @click="resetForm" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-200">Reset</button>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-slate-800 mb-4">Daftar Template</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 uppercase text-xs">
                            <th class="py-2">Nama</th>
                            <th class="py-2">Jumlah Item</th>
                            <th class="py-2">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in templates" :key="t.id" class="border-t border-slate-100 hover:bg-slate-50 cursor-pointer" @click="selectTemplate(t)">
                            <td class="py-2 font-medium text-slate-800">{{ t.name }}</td>
                            <td class="py-2 text-slate-600">{{ parseItems(t.items_json).length }}</td>
                            <td class="py-2 text-slate-600">{{ formatDate(t.created_at) }}</td>
                        </tr>
                        <tr v-if="templates.length === 0">
                            <td colspan="3" class="py-6 text-center text-slate-500">Belum ada template</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-end">
                    <button @click="openRunModal" :disabled="!form.id" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 disabled:opacity-50">
                        <i class="fas fa-play"></i> Mulai Checklist
                    </button>
                </div>
            </div>
        </div>
        <div v-if="showRunModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-4xl p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-800">{{ form.name }}</h3>
                    <div class="flex items-center gap-2">
                        <button @click="saveRun" class="px-3 py-1 text-[12px] font-bold bg-indigo-600 text-white rounded hover:bg-indigo-700"><i class="fas fa-save"></i> Simpan</button>
                        <button @click="closeRunModal" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Tutup</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi</label>
                        <input v-model="run.location" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Pos Jaga">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan</label>
                        <input v-model="run.notes" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="">
                    </div>
                </div>
                <div class="space-y-2">
                    <div v-for="(it,idx) in form.items" :key="'run-'+idx" class="border border-slate-200 rounded-lg p-3">
                        <div class="font-bold text-slate-800 text-sm mb-2">{{ it.label }}</div>
                        <div v-if="(it.type || 'BOOLEAN') === 'BOOLEAN'" class="space-y-2">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2"><input type="radio" :name="'yn-'+idx" :value="true" v-model="answers[idx].checked"> Ya</label>
                                <label class="flex items-center gap-2"><input type="radio" :name="'yn-'+idx" :value="false" v-model="answers[idx].checked"> Tidak</label>
                            </div>
                            <input v-model="answers[idx].note" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan">
                        </div>
                        <div v-else-if="it.type === 'COUNT_NOTE'" class="grid grid-cols-1 md:grid-cols-6 gap-2">
                            <div class="md:col-span-2">
                                <label class="flex items-center gap-2"><input type="checkbox" v-model="answers[idx].has"> Ada</label>
                            </div>
                            <div class="md:col-span-1">
                                <input v-model.number="answers[idx].count" type="number" min="0" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah">
                            </div>
                            <div class="md:col-span-3">
                                <input v-model="answers[idx].note" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan">
                            </div>
                        </div>
                        <div v-else-if="it.type === 'PACKAGE'" class="grid grid-cols-1 md:grid-cols-6 gap-2">
                            <div class="md:col-span-2">
                                <label class="flex items-center gap-2"><input type="checkbox" v-model="answers[idx].has"> Ada</label>
                            </div>
                            <div class="md:col-span-1">
                                <input v-model.number="answers[idx].count" type="number" min="0" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah">
                            </div>
                            <div class="md:col-span-3">
                                <input v-model="answers[idx].recipient" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Untuk siapa">
                            </div>
                            <div class="md:col-span-6">
                                <input v-model="answers[idx].note" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan">
                            </div>
                        </div>
                        <div v-else-if="it.type === 'VEHICLE'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <div class="text-[11px] font-bold text-slate-600 mb-1">Keluar</div>
                                <div class="border border-slate-300 rounded p-2 max-h-40 overflow-y-auto bg-white">
                                    <label v-for="v in vehicles" :key="v.id" class="flex items-center gap-2 text-sm py-1">
                                        <input type="checkbox" :value="v.id" v-model="answers[idx].out_ids"> {{ v.name }} ({{ v.license_plate }})
                                    </label>
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-slate-600 mb-1">Ada</div>
                                <div class="border border-slate-300 rounded p-2 max-h-40 overflow-y-auto bg-white">
                                    <label v-for="v in vehicles" :key="'p-'+v.id" class="flex items-center gap-2 text-sm py-1">
                                        <input type="checkbox" :value="v.id" v-model="answers[idx].present_ids"> {{ v.name }} ({{ v.license_plate }})
                                    </label>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <input v-model="answers[idx].note" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            form: { id: null, name: '', items: [] },
            newItem: { label: '', type: 'BOOLEAN' },
            templates: [],
            selectedTemplateId: '',
            showRunModal: false,
            run: { location: '', notes: '' },
            answers: [],
            vehicles: []
        }
    },
    methods: {
        resetForm() { this.form = { id: null, name: '', items: [] }; this.selectedTemplateId=''; this.newItem={label:'',type:'BOOLEAN'}; },
        parseItems(json) {
            try { const arr = JSON.parse(json || '[]'); return Array.isArray(arr) ? arr : []; }
            catch(_) { return []; }
        },
        formatDate(s) {
            if (!s) return '-';
            try { return new Date(s).toLocaleString('id-ID'); } catch (_) { return s; }
        },
        typeLabel(t) {
            const x = String(t || 'BOOLEAN').toUpperCase();
            if (x === 'BOOLEAN') return 'Ya/Tidak + Keterangan';
            if (x === 'COUNT_NOTE') return 'Jumlah + Keterangan';
            if (x === 'PACKAGE') return 'Paket';
            if (x === 'VEHICLE') return 'Kendaraan';
            return x;
        },
        addItem() {
            const lbl = String(this.newItem.label || '').trim();
            if (!lbl) return;
            this.form.items.push({ label: lbl, type: this.newItem.type || 'BOOLEAN', config: {} });
            this.newItem = { label: '', type: 'BOOLEAN' };
        },
        removeItem(i) { this.form.items.splice(i,1); },
        moveUp(i) { if (i>0) { const a=this.form.items[i-1]; this.form.items[i-1]=this.form.items[i]; this.form.items[i]=a; } },
        moveDown(i) { if (i<this.form.items.length-1) { const a=this.form.items[i+1]; this.form.items[i+1]=this.form.items[i]; this.form.items[i]=a; } },
        async fetchTemplates() {
            try {
                const r = await fetch(this.base() + 'api/security.php?action=list_checklist_templates');
                const j = await r.json();
                this.templates = j.success ? (j.data || []) : [];
            } catch (e) { console.error(e); }
        },
        base() {
            let b = window.BASE_URL || '/';
            if (b === '/' || !b) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                b = m ? `/${m[1]}/` : '/';
            }
            return b;
        },
        selectTemplate(t) {
            this.selectedTemplateId = t.id;
            this.loadSelectedTemplate();
        },
        loadSelectedTemplate() {
            const t = this.templates.find(x => String(x.id) === String(this.selectedTemplateId));
            if (!t) { this.form = { id: null, name: '', items: [] }; return; }
            const arr = this.parseItems(t.items_json);
            const normalized = arr.map(x => (typeof x === 'string') ? ({ label: x, type: 'BOOLEAN', config: {} }) : ({ label: x.label, type: x.type || 'BOOLEAN', config: x.config || {} }));
            this.form = { id: t.id, name: t.name, items: normalized };
        },
        async saveTemplate() {
            const items = Array.isArray(this.form.items) ? this.form.items.filter(it => String(it.label||'').trim()) : [];
            if (!this.form.name || items.length === 0) { alert('Nama dan item wajib'); return; }
            try {
                const r = await fetch(this.base() + 'api/security.php?action=save_checklist_template', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.form.id, name: this.form.name, items })
                });
                const j = await r.json();
                if (j.success) {
                    alert('Template tersimpan');
                    this.resetForm();
                    this.fetchTemplates();
                } else {
                    alert(j.message || 'Gagal menyimpan');
                }
            } catch (e) { console.error(e); alert('Gagal menyimpan'); }
        },
        async openRunModal() {
            if (!this.form || !this.form.id) return;
            this.run = { location: '', notes: '' };
            this.answers = this.form.items.map(it => {
                const t = String(it.type || 'BOOLEAN').toUpperCase();
                if (t === 'BOOLEAN') return { checked: false, note: '' };
                if (t === 'COUNT_NOTE') return { has: false, count: 0, note: '' };
                if (t === 'PACKAGE') return { has: false, count: 0, recipient: '', note: '' };
                if (t === 'VEHICLE') return { out_ids: [], present_ids: [], note: '' };
                return {};
            });
            await this.loadVehicles();
            this.showRunModal = true;
        },
        closeRunModal() { this.showRunModal = false; },
        async loadVehicles() {
            try {
                const r = await fetch(this.base() + 'api/inventory.php?action=get_vehicles');
                const j = await r.json();
                this.vehicles = j.success ? (j.data || []) : [];
            } catch(_) { this.vehicles = []; }
        },
        makeRequestId() {
            try {
                if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
                const ts = Date.now();
                const rnd = Math.random().toString(36).slice(2);
                const uid = String(window.USER_ID || '');
                return `sec-${ts}-${uid}-${rnd}`;
            } catch(_) { return `sec-${Date.now()}`; }
        },
        async saveRun() {
            try {
                const rStart = await fetch(this.base() + 'api/security.php?action=start_checklist_run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ template_id: this.form.id, location: this.run.location, notes: this.run.notes, request_id: this.makeRequestId() })
                });
                const js = await rStart.json();
                if (!js.success) { alert(js.message || 'Gagal memulai'); return; }
                const run_id = js.data && js.data.run_id ? js.data.run_id : null;
                if (!run_id) { alert('Run ID tidak valid'); return; }
                const answers = this.form.items.map((it, idx) => {
                    const t = String(it.type || 'BOOLEAN').toUpperCase();
                    const a = this.answers[idx] || {};
                    let value = null;
                    if (t === 'BOOLEAN') value = { checked: !!a.checked, note: String(a.note||'') };
                    else if (t === 'COUNT_NOTE') value = { has: !!a.has, count: Number(a.count||0), note: String(a.note||'') };
                    else if (t === 'PACKAGE') value = { has: !!a.has, count: Number(a.count||0), recipient: String(a.recipient||''), note: String(a.note||'') };
                    else if (t === 'VEHICLE') value = { out_ids: Array.isArray(a.out_ids)?a.out_ids:[], present_ids: Array.isArray(a.present_ids)?a.present_ids:[], note: String(a.note||'') };
                    return { label: it.label, type: it.type || 'BOOLEAN', value };
                });
                const rSave = await fetch(this.base() + 'api/security.php?action=save_checklist_result', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ run_id, answers, request_id: this.makeRequestId() })
                });
                const jr = await rSave.json();
                if (jr.success) {
                    alert('Checklist disimpan');
                    this.closeRunModal();
                } else {
                    alert(jr.message || 'Gagal menyimpan');
                }
            } catch(_) { alert('Terjadi kesalahan sistem'); }
        }
    },
    async mounted() {
        await this.fetchTemplates();
    }
}).mount('#app');
</script>
<?php require_once '../../includes/footer.php'; ?>
