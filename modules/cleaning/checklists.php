<?php
require_once '../../includes/guard.php';
require_login_and_module('cleaning');
require_once '../../includes/header.php';
?>
<div id="app" v-cloak class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-clipboard-check text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Daftar Checklist</h1>
                <span class="text-xs text-slate-500 font-medium">Input ceklist kebersihan</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>index.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </a>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Template</label>
                        <select v-model="selectedTemplateId" @change="onTemplateChange" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">- Pilih -</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Ruangan</label>
                        <select v-model="selectedRoomId" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">- Pilih -</option>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.building ? (r.building + ' • ') : '' }}{{ r.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Tanggal</label>
                        <input v-model="dateStr" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Keterangan</label>
                        <input v-model="notes" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="">
                    </div>
                    <div class="flex gap-2">
                        <button @click="startRun" :disabled="!selectedTemplateId || !selectedRoomId" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 disabled:opacity-50">
                            <i class="fas fa-play"></i> Mulai
                        </button>
                        <button @click="saveRun" :disabled="!runId" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 disabled:opacity-50">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800">Item Checklist</h3>
                    <div class="text-xs text-slate-500">{{ templates.find(x => String(x.id)===String(selectedTemplateId))?.name || '-' }}</div>
                </div>
                <div id="itemsBox" class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="(it,idx) in items" :key="'it-'+idx" class="border border-slate-200 rounded-lg p-4">
                        <div class="text-sm font-bold text-slate-800">{{ it.label }}</div>
                        <div v-if="typeOf(it)==='BOOLEAN'" class="mt-1 flex flex-wrap items-center gap-3">
                            <label class="text-[12px]"><input type="radio" :name="'bool_'+idx" value="YA" v-model="answers[idx].choice"> Ya</label>
                            <label class="text-[12px]"><input type="radio" :name="'bool_'+idx" value="TIDAK" v-model="answers[idx].choice"> Tidak</label>
                            <textarea rows="3" v-model="answers[idx].note" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan"></textarea>
                        </div>
                        <div v-else-if="typeOf(it)==='COUNT_NOTE'" class="mt-2">
                            <input type="number" v-model.number="answers[idx].count" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah">
                            <textarea rows="3" v-model="answers[idx].note" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan"></textarea>
                        </div>
                        <div v-else class="mt-2">
                            <input type="text" v-model="answers[idx].text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Isi">
                        </div>
                    </div>
                    <div v-if="items.length===0" class="text-xs text-slate-500">Belum ada item</div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once '../../includes/footer.php'; ?>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            templates: [],
            rooms: [],
            selectedTemplateId: '',
            selectedRoomId: '',
            items: [],
            answers: [],
            notes: '',
            dateStr: new Date().toISOString().slice(0,10),
            runId: null
        };
    },
    methods: {
        base() {
            let b = window.BASE_URL || '/';
            if (!b || b === '/') {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                b = m ? `/${m[1]}/` : '/';
            }
            return b;
        },
        async fetchTemplates() {
            try {
                const r = await fetch(this.base() + 'api/cleaning.php?action=list_checklist_templates');
                const j = await r.json();
                this.templates = j.success ? (j.data || []) : [];
            } catch (_) { this.templates = []; }
        },
        async fetchRooms() {
            try {
                const r = await fetch(this.base() + 'api/cleaning.php?action=list_rooms');
                const j = await r.json();
                this.rooms = j.success ? (j.data || []) : [];
            } catch (_) { this.rooms = []; }
        },
        parseItems(json) {
            try { const arr = JSON.parse(json || '[]'); return Array.isArray(arr) ? arr : []; }
            catch (_) { return []; }
        },
        typeOf(it) {
            return String((it && it.type) ? it.type : 'BOOLEAN').toUpperCase();
        },
        onTemplateChange() {
            const t = this.templates.find(x => String(x.id) === String(this.selectedTemplateId));
            let arr = [];
            try {
                arr = this.parseItems(t ? t.items_json : '[]').map(x => (typeof x === 'string') ? ({ label: x, type: 'BOOLEAN' }) : ({ label: x.label, type: x.type || 'BOOLEAN' }));
            } catch(_) { arr = []; }
            this.items = arr;
            this.answers = arr.map(it => {
                const tp = this.typeOf(it);
                if (tp === 'BOOLEAN') return { choice: 'YA', note: '' };
                if (tp === 'COUNT_NOTE') return { count: 0, note: '' };
                return { text: '' };
            });
            this.runId = null;
        },
        makeRequestId() {
            try {
                if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
                const ts = Date.now();
                const rnd = Math.random().toString(36).slice(2);
                const uid = String(window.USER_ID || '');
                return `clean-${ts}-${uid}-${rnd}`;
            } catch(_) { return `clean-${Date.now()}`; }
        },
        async startRun() {
            try {
                const r = await fetch(this.base() + 'api/cleaning.php?action=start_checklist_run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ template_id: this.selectedTemplateId, room_id: this.selectedRoomId, date: this.dateStr, notes: this.notes, request_id: this.makeRequestId() })
                });
                const j = await r.json();
                if (!j.success) { alert(j.message || 'Gagal memulai'); return; }
                this.runId = j.data && j.data.run_id ? j.data.run_id : null;
                if (!this.runId) { alert('Run ID tidak valid'); }
            } catch(_) { alert('Gagal memulai'); }
        },
        async saveRun() {
            if (!this.runId) { alert('Mulai dahulu'); return; }
            try {
                const answers = this.items.map((it, idx) => {
                    const tp = this.typeOf(it);
                    const a = this.answers[idx] || {};
                    if (tp === 'BOOLEAN') return { label: it.label, type: 'BOOLEAN', value: { choice: String(a.choice||'YA'), note: String(a.note||'') } };
                    if (tp === 'COUNT_NOTE') return { label: it.label, type: 'COUNT_NOTE', value: { count: Number(a.count||0), note: String(a.note||'') } };
                    return { label: it.label, type: 'TEXT', value: { text: String(a.text||'') } };
                });
                const r = await fetch(this.base() + 'api/cleaning.php?action=save_checklist_result', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ run_id: this.runId, template_id: this.selectedTemplateId, room_id: this.selectedRoomId, date: this.dateStr, notes: this.notes, answers, request_id: this.makeRequestId() })
                });
                const j = await r.json();
                if (j.success) {
                    alert('Checklist disimpan');
                } else {
                    alert(j.message || 'Gagal menyimpan');
                }
            } catch(_) { alert('Gagal menyimpan'); }
        }
    },
    async mounted() {
        await Promise.all([this.fetchTemplates(), this.fetchRooms()]);
    }
}).mount('#app');
</script>
