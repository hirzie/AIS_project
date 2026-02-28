<?php
require_once '../../includes/guard.php';
require_login_and_module('admin');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>
<style>
    [v-cloak] { display: none !important; }
    .column { min-height: 200px; }
    .drag-over { outline: 2px dashed #93c5fd; outline-offset: 2px; }
</style>
<div id="app" v-cloak class="flex flex-col h-screen">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/admin/version_log.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
                <i class="fas fa-chalkboard text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Whiteboard Pengembangan</h1>
                <span class="text-xs text-slate-500 font-medium">Manajerial • Tim Pengembangan</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openNewCard" class="px-3 py-1 rounded text-[12px] font-bold bg-blue-600 text-white hover:bg-blue-700">
                <i class="fas fa-plus mr-1"></i> Tambah Kartu
            </button>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800"><i class="fas fa-lightbulb text-yellow-500"></i> Rencana Pengembangan</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-yellow-100 text-yellow-700">{{ plannedCount }}</span>
                    </div>
                    <div class="p-4 space-y-3 column" @dragover.prevent="onDragOver('PLANNED')" @dragleave="onDragLeave" @drop="onDrop('PLANNED')" :class="dragTarget==='PLANNED' ? 'drag-over' : ''">
                        <div v-for="c in plannedCards" :key="c.id" class="border border-slate-200 rounded-lg bg-white p-3 hover:bg-slate-50 cursor-grab" draggable="true" @dragstart="onDragStart(c)">
                            <div class="flex items-start justify-between">
                                <div class="font-bold text-slate-800">{{ c.title }}</div>
                                <div class="flex items-center gap-2">
                                    <button @click="editCard(c)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-pencil"></i></button>
                                    <button @click="deleteCard(c)" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="text-[12px] text-slate-600 mt-1">{{ c.description || '-' }}</div>
                            <div class="text-[11px] text-slate-400 mt-1" v-if="c.meeting_id">Meeting: #{{ c.meeting_id }}</div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="t in (c.tags||[])" :key="t" class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-700 rounded">{{ t }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800"><i class="fas fa-flask text-indigo-500"></i> Testing Perlu Tinjauan</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">{{ testingCount }}</span>
                    </div>
                    <div class="p-4 space-y-3 column" @dragover.prevent="onDragOver('TESTING')" @dragleave="onDragLeave" @drop="onDrop('TESTING')" :class="dragTarget==='TESTING' ? 'drag-over' : ''">
                        <div v-for="c in testingCards" :key="c.id" class="border border-slate-200 rounded-lg bg-white p-3 hover:bg-slate-50 cursor-grab" draggable="true" @dragstart="onDragStart(c)">
                            <div class="flex items-start justify-between">
                                <div class="font-bold text-slate-800">{{ c.title }}</div>
                                <div class="flex items-center gap-2">
                                    <button @click="editCard(c)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-pencil"></i></button>
                                    <button @click="deleteCard(c)" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="text-[12px] text-slate-600 mt-1">{{ c.description || '-' }}</div>
                            <div class="text-[11px] text-slate-400 mt-1" v-if="c.meeting_id">Meeting: #{{ c.meeting_id }}</div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="t in (c.tags||[])" :key="t" class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-700 rounded">{{ t }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800"><i class="fas fa-rocket text-emerald-500"></i> Sudah Berjalan di Production</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">{{ productionCount }}</span>
                    </div>
                    <div class="p-4 space-y-3 column" @dragover.prevent="onDragOver('PRODUCTION')" @dragleave="onDragLeave" @drop="onDrop('PRODUCTION')" :class="dragTarget==='PRODUCTION' ? 'drag-over' : ''">
                        <div v-for="c in productionCards" :key="c.id" class="border border-slate-200 rounded-lg bg-white p-3 hover:bg-slate-50 cursor-grab" draggable="true" @dragstart="onDragStart(c)">
                            <div class="flex items-start justify-between">
                                <div class="font-bold text-slate-800">{{ c.title }}</div>
                                <div class="flex items-center gap-2">
                                    <button @click="editCard(c)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-pencil"></i></button>
                                    <button @click="deleteCard(c)" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="text-[12px] text-slate-600 mt-1">{{ c.description || '-' }}</div>
                            <div class="text-[11px] text-slate-400 mt-1" v-if="c.meeting_id">Meeting: #{{ c.meeting_id }}</div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="t in (c.tags||[])" :key="t" class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-700 rounded">{{ t }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">{{ editId ? 'Edit Kartu' : 'Tambah Kartu' }}</h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Judul</label>
                    <input v-model="form.title" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Deskripsi</label>
                    <textarea v-model="form.description" rows="4" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
                        <select v-model="form.status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                            <option value="PLANNED">Rencana</option>
                            <option value="TESTING">Testing/Tinjauan</option>
                            <option value="PRODUCTION">Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">No/ID Rapat (Opsional)</label>
                        <input v-model="form.meeting_id" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" placeholder="Contoh: M-20260209-1234 atau ID">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Tags (pisahkan dengan koma)</label>
                    <input v-model="form.tags_text" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" placeholder="opsional: security, finance, academic">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button @click="showModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="saveCard" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors disabled:opacity-50">
                    {{ saving ? 'Menyimpan...' : (editId ? 'Perbarui' : 'Simpan') }}
                </button>
            </div>
        </div>
    </div>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            baseUrl: window.BASE_URL || '/',
            board: { columns: ['PLANNED','TESTING','PRODUCTION'], cards: [] },
            dragTarget: null,
            dragCard: null,
            showModal: false,
            saving: false,
            editId: '',
            form: { title: '', description: '', status: 'PLANNED', meeting_id: '', tags_text: '' }
        };
    },
    computed: {
        plannedCards() { return (this.board.cards || []).filter(c => c.status === 'PLANNED'); },
        testingCards() { return (this.board.cards || []).filter(c => c.status === 'TESTING'); },
        productionCards() { return (this.board.cards || []).filter(c => c.status === 'PRODUCTION'); },
        plannedCount() { return this.plannedCards.length; },
        testingCount() { return this.testingCards.length; },
        productionCount() { return this.productionCards.length; }
    },
    methods: {
        normalizeBaseUrl() {
            let b = window.BASE_URL || '/';
            if (b === '/' || !b) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                b = m ? `/${m[1]}/` : '/';
            }
            this.baseUrl = b;
        },
        async fetchBoard() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/dev_whiteboard.php?action=get_board');
                const j = await res.json();
                if (j.success) this.board = j.data || { columns: ['PLANNED','TESTING','PRODUCTION'], cards: [] };
            } catch (e) {}
        },
        openNewCard() {
            this.editId = '';
            this.form = { title: '', description: '', status: 'PLANNED', meeting_id: '', tags_text: '' };
            this.showModal = true;
        },
        editCard(c) {
            this.editId = c.id;
            this.form = {
                title: c.title || '',
                description: c.description || '',
                status: c.status || 'PLANNED',
                meeting_id: c.meeting_id || '',
                tags_text: Array.isArray(c.tags) ? c.tags.join(', ') : ''
            };
            this.showModal = true;
        },
        async saveCard() {
            const t = this.form.title.trim();
            if (!t) { alert('Judul wajib'); return; }
            this.saving = true;
            try {
                const tags = (this.form.tags_text || '').split(',').map(s => s.trim()).filter(s => s);
                const res = await fetch(this.baseUrl + 'api/dev_whiteboard.php?action=save_card', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.editId || '',
                        title: t,
                        description: this.form.description,
                        status: this.form.status,
                        meeting_id: (this.form.meeting_id || '').trim(),
                        tags
                    })
                });
                const j = await res.json();
                if (j.success) {
                    this.showModal = false;
                    this.fetchBoard();
                } else {
                    alert(j.message || 'Gagal menyimpan kartu');
                }
            } catch (e) {
                alert('Gagal menyimpan kartu');
            } finally {
                this.saving = false;
            }
        },
        async deleteCard(c) {
            if (!confirm('Hapus kartu ini?')) return;
            try {
                const res = await fetch(this.baseUrl + 'api/dev_whiteboard.php?action=delete_card', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: c.id })
                });
                const j = await res.json();
                if (j.success) {
                    this.fetchBoard();
                } else {
                    alert(j.message || 'Gagal menghapus kartu');
                }
            } catch (e) {
                alert('Gagal menghapus kartu');
            }
        },
        onDragStart(c) {
            this.dragCard = c;
        },
        onDragOver(target) {
            this.dragTarget = target;
        },
        onDragLeave() {
            this.dragTarget = null;
        },
        async onDrop(target) {
            const card = this.dragCard;
            this.dragTarget = null;
            this.dragCard = null;
            if (!card || !target || card.status === target) return;
            try {
                const res = await fetch(this.baseUrl + 'api/dev_whiteboard.php?action=move_card', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: card.id, status: target })
                });
                const j = await res.json();
                if (j.success) {
                    this.fetchBoard();
                }
            } catch (e) {}
        }
    },
    mounted() {
        this.fetchBoard();
    }
}).mount('#app');
</script>

