<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../includes/header.php';
?>
<?php
$__userRole = strtoupper($_SESSION['role'] ?? '');
?>
<script>
    window.SKIP_GLOBAL_APP = true;
    window.USER_ROLE = <?php echo json_encode($__userRole); ?>;
</script>
<div id="app" v-cloak class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/executive/index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-200">
                <i class="fas fa-file text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Dokumen</h1>
                <span class="text-xs text-slate-500 font-medium">Executive View</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-file text-slate-600"></i> Daftar Dokumen
                    </h3>
                    <div class="flex items-center gap-2">
                        <select v-model="selectedModule" @change="fetchDocuments" class="px-2 py-1 rounded text-[10px] font-bold border border-slate-300 bg-white">
                            <option value="ALL">Semua Divisi</option>
                            <option v-for="m in modulesList" :key="m.code" :value="m.code">{{ m.name || m.label || m.code }}</option>
                        </select>
                        <button @click="fetchDocuments" class="px-2 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600 bg-white">Refresh</button>
                        <button @click="openLogSection" class="px-2 py-1 rounded text-[10px] font-bold text-slate-600 hover:text-indigo-600">Log Dokumen</button>
                        <button @click="openUpload" class="px-3 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i> Tambah Dokumen</button>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="documents.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="divide-y divide-slate-100">
                            <div v-for="d in documents" :key="d.id || d.doc_url" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ d.module_tag || '-' }}</span>
                                        <div>
                                            <div class="font-bold text-slate-800 text-sm">{{ d.doc_title || d.title || '(Dokumen)' }}</div>
                                            <div class="text-[11px] text-slate-500">{{ formatDocMeta(d) }}</div>
                                            <div v-if="d.description" class="text-[11px] text-slate-500">Keterangan: {{ d.description }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div v-if="pendingDeleteId !== d.id" class="flex gap-2 justify-end mt-1">
                                            <button @click="openViewer(d)" class="text-[10px] font-bold text-indigo-600">Lihat</button>
                                            <button v-if="!(d.approval_role || d.approval_date)" @click="openEdit(d)" class="text-[10px] font-bold text-amber-600 hover:text-amber-800">Edit</button>
                                            <button v-if="canManagerial() && !(d.approval_role || d.approval_date)" @click="openApproval(d)" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800">Sahkan</button>
                                            <button v-if="canManagerial() && (d.approval_role || d.approval_date)" @click="reapproveDocument(d)" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800">Sahkan Ulang</button>
                                            <button v-if="(userRole==='SUPERADMIN' || userRole==='ADMIN') && (d.approval_role || d.approval_date)" @click="openApproval(d)" class="text-[10px] font-bold text-amber-600 hover:text-amber-800">Edit Pengesahan</button>
                                            <button v-if="(userRole==='SUPERADMIN' || userRole==='ADMIN') && (d.approval_role || d.approval_date)" @click="unapproveDocument(d)" class="text-[10px] font-bold text-red-600 hover:text-red-800">Batalkan Pengesahan</button>
                                            <button v-if="canDelete() && !(d.approval_role || d.approval_date)" @click="askDelete(d)" class="text-[10px] font-bold text-red-600 hover:text-red-800">Hapus</button>
                                        </div>
                                        <div v-else class="flex gap-2 justify-end mt-1">
                                            <span class="text-[10px] text-slate-500 font-bold">Konfirmasi?</span>
                                            <button @click="performDeleteDocument(d.id)" class="px-2 py-1 text-[10px] font-bold bg-red-600 text-white rounded hover:bg-red-700">Ya</button>
                                            <button @click="cancelDelete" class="px-2 py-1 text-[10px] font-bold border border-slate-300 text-slate-600 rounded hover:bg-slate-100">Batal</button>
                                        </div>
                                        <div class="mt-2 text-right">
                                            <div class="text-[10px] text-slate-400">Upload: {{ formatDateTime(d.created_at) }}</div>
                                                <div class="text-[10px] text-slate-400">Pengesahan: {{ approvalDisplay(d) }}<span v-if="d.approval_date"> • {{ formatDateTime(d.approval_date) }}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-slate-400 py-6 text-sm">Belum ada dokumen.</div>
                </div>
            </div>
            <div v-if="showLogs" id="logsSection" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-slate-600"></i> Log Dokumen
                    </h3>
                    <div class="flex items-center gap-2">
                        <select v-model="logsActionFilter" class="px-2 py-1 rounded border border-slate-300 text-[12px]">
                            <option value="ALL">Semua Aksi</option>
                            <option value="CREATE">CREATE</option>
                            <option value="UPDATE">UPDATE</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                        <button @click="fetchLogs" class="px-2 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600 bg-white">Refresh</button>
                        <button @click="hideLogSection" class="px-2 py-1 rounded text-[10px] font-bold text-slate-600 hover:text-indigo-600">Hide Log</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Entity</th>
                                <th class="px-4 py-3">Judul</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada catatan aktivitas dokumen.</td>
                            </tr>
                            <tr v-for="row in filteredLogs" :key="(row.created_at || '') + (row.title || '')" class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2 text-slate-500 text-xs">{{ formatDateTime(row.created_at) }}</td>
                                <td class="px-4 py-2 text-slate-700 text-xs">{{ row.action }}</td>
                                <td class="px-4 py-2 text-slate-700 text-xs">
                                    <div class="font-mono">{{ row.entity_type || '-' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ row.entity_id || '-' }}</div>
                                </td>
                                <td class="px-4 py-2 text-slate-700 text-sm">{{ row.title || '-' }}</td>
                                <td class="px-4 py-2 text-slate-600 text-sm">{{ row.description || '-' }}</td>
                                <td class="px-4 py-2 text-slate-600 text-xs">{{ row.people_name || row.username || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-show="showApproval" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                        <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-stamp text-emerald-600"></i> Pengesahan Dokumen</div>
                        <button @click="closeApproval" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Pengesahan</label>
                            <input type="date" v-model="approvalForm.date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan</label>
                            <textarea v-model="approvalForm.note" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Catatan pengesahan"></textarea>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 flex justify-end gap-2">
                        <button @click="approveDocument" :disabled="savingApproval" class="px-3 py-1 text-[12px] font-bold rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                            <i v-if="savingApproval" class="fas fa-spinner fa-spin mr-1"></i> Sahkan
                        </button>
                        <button @click="closeApproval" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
                    </div>
                </div>
            </div>
            <div v-show="showUpload" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                        <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-upload text-indigo-600"></i> Upload Dokumen</div>
                        <button @click="closeUpload" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Divisi/Modul</label>
                                <select v-model="docForm.module_tag" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                    <option v-for="m in modulesList" :key="m.code" :value="m.code">{{ m.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Dokumen</label>
                                <select v-model="docForm.category" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                    <option value="ATURAN">Aturan</option>
                                    <option value="SOP">SOP</option>
                                    <option value="LAIN">Lain-lain</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Dokumen</label>
                                <input v-model="docForm.doc_title" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Nama dokumen">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Pengesahan / Perubahan</label>
                                <input v-model="docForm.approval_note" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Disahkan 2026-02-03">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Upload File (PDF)</label>
                                <input @change="onFileChange" type="file" accept=".pdf" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                <p class="text-[11px] text-slate-500 mt-1">Format disarankan PDF untuk preview.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan Dokumen</label>
                                <textarea v-model="docForm.description" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Dokumen ini ..."></textarea>
                            </div>
                        </div>
                        <div v-if="previewUrl" class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-600">Preview</div>
                            <div class="h-96">
                                <iframe :src="baseUrl + previewUrl" class="w-full h-full"></iframe>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 flex justify-end gap-2">
                        <button @click="uploadAndSave" :disabled="savingDoc" class="px-3 py-1 text-[12px] font-bold rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                            <i v-if="savingDoc" class="fas fa-spinner fa-spin mr-1"></i> Simpan
                        </button>
                        <button @click="closeUpload" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
                    </div>
                </div>
            </div>
            <div v-show="showViewer" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-4xl overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                        <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-file-pdf text-red-600"></i> {{ viewerTitle }}</div>
                        <button @click="closeViewer" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="h-[70vh]">
                        <iframe :src="baseUrl + viewerUrl" class="w-full h-full"></iframe>
                    </div>
                </div>
            </div>
            <div v-show="showEdit" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                        <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-edit text-indigo-600"></i> Edit Dokumen</div>
                        <button @click="closeEdit" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Divisi/Modul</label>
                                <select v-model="editForm.module_tag" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                    <option v-for="m in modulesList" :key="m.code" :value="m.code">{{ m.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Dokumen</label>
                                <select v-model="editForm.category" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                    <option value="ATURAN">Aturan</option>
                                    <option value="SOP">SOP</option>
                                    <option value="LAIN">Lain-lain</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Dokumen</label>
                                <input v-model="editForm.doc_title" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Nama dokumen">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Pengesahan / Perubahan</label>
                                <input v-model="editForm.approval_note" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Disahkan 2026-02-03">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Ganti File (PDF)</label>
                                <input @change="onEditFileChange" type="file" accept=".pdf" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                <p class="text-[11px] text-slate-500 mt-1">Biarkan kosong jika tidak mengganti file.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan Dokumen</label>
                                <textarea v-model="editForm.description" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Dokumen ini ..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 flex justify-end gap-2">
                        <button @click="updateAndSave" :disabled="savingEdit" class="px-3 py-1 text-[12px] font-bold rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                            <i v-if="savingEdit" class="fas fa-spinner fa-spin mr-1"></i> Simpan Perubahan
                        </button>
                        <button @click="closeEdit" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
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
        return { baseUrl: window.BASE_URL || '/', selectedModule: 'ALL', documents: [], showUpload: false, modulesList: [], previewUrl: '', savingDoc: false, docForm: { module_tag: '', category: 'ATURAN', doc_title: '', approval_note: '', description: '', doc_file: null, doc_url: '', doc_type: 'FILE' }, showViewer: false, viewerUrl: '', viewerTitle: '', showEdit: false, editForm: { id: null, module_tag: '', category: 'ATURAN', doc_title: '', approval_note: '', description: '', doc_url: '', doc_type: 'FILE', doc_file: null }, savingEdit: false, showLogs: false, logs: [], logsActionFilter: 'ALL', pendingDeleteId: null, userRole: (window.USER_ROLE || '').toUpperCase(), showApproval: false, approvalForm: { id: null, date: '', note: '' }, savingApproval: false, currentDoc: null }
    },
    methods: {
        canManagerial() {
            return String(this.userRole || '').toUpperCase() === 'MANAGERIAL';
        },
        canDelete() {
            const r = (this.userRole || '').toUpperCase();
            return r === 'SUPERADMIN' || r === 'ADMIN';
        },
        approvalDisplay(d) {
            const name = d.approval_people_name || d.approval_username || '';
            if (name) return name;
            return d.approval_role || '-';
        },
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        formatDateTime(dateStr) {
            const d = new Date(dateStr);
            const datePart = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const timePart = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return datePart + ' ' + timePart;
        },
        openLogSection() {
            this.showLogs = true;
            this.fetchLogs();
            this.$nextTick(() => {
                const el = document.getElementById('logsSection');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
        openApproval(d) {
            if (!this.canManagerial()) { alert('Akses pengesahan hanya untuk MANAGERIAL'); return; }
            this.currentDoc = d;
            const today = new Date().toISOString().slice(0,10);
            this.approvalForm = { id: d.id, date: today, note: '' };
            this.showApproval = true;
        },
        closeApproval() {
            this.showApproval = false;
            this.approvalForm = { id: null, date: '', note: '' };
            this.currentDoc = null;
        },
        async approveDocument() {
            if (!this.canManagerial()) { alert('Akses pengesahan hanya untuk MANAGERIAL'); return; }
            if (!this.approvalForm.id || !this.currentDoc) { alert('ID dokumen tidak valid'); return; }
            if (!this.approvalForm.date) { alert('Tanggal pengesahan wajib diisi'); return; }
            this.savingApproval = true;
            try {
                this.normalizeBaseUrl();
                const d = this.currentDoc;
                const tagsArr = String(d.tags || '').split(',').map(s=>s.trim()).filter(Boolean);
                const category = (tagsArr[0] || 'ATURAN').toUpperCase();
                const noteText = 'Disahkan ' + this.approvalForm.date + (this.approvalForm.note ? (' - ' + this.approvalForm.note) : '');
                const newTags = [category, noteText].join(',');
                const payloadUpdate = {
                    id: d.id,
                    module_tag: d.module_tag,
                    doc_title: d.doc_title,
                    doc_url: d.doc_url || '',
                    doc_type: d.doc_type || 'FILE',
                    tags: newTags,
                    description: d.description || ''
                };
                const resUp = await fetch(this.baseUrl + 'api/meetings.php?action=update_document', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payloadUpdate) });
                const dataUp = await resUp.json();
                if (!dataUp.success) { alert('Gagal menyimpan catatan pengesahan: ' + (dataUp.message || '')); this.savingApproval = false; return; }
                const resAp = await fetch(this.baseUrl + 'api/meetings.php?action=set_document_approval', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: d.id, approve: true }) });
                const dataAp = await resAp.json();
                if (dataAp && dataAp.success) {
                    this.closeApproval();
                    this.fetchDocuments();
                    alert('Dokumen disahkan');
                } else {
                    alert('Gagal mengesahkan: ' + (dataAp && dataAp.message ? dataAp.message : ''));
                }
            } catch(_) { alert('Terjadi kesalahan sistem'); }
            finally { this.savingApproval = false; }
        },
        async reapproveDocument(d) {
            if (!this.canManagerial()) { alert('Akses pengesahan hanya untuk MANAGERIAL'); return; }
            if (!d || !d.id) { alert('ID dokumen tidak valid'); return; }
            try {
                this.normalizeBaseUrl();
                const resAp = await fetch(this.baseUrl + 'api/meetings.php?action=set_document_approval', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: d.id, approve: true })
                });
                const dataAp = await resAp.json();
                if (dataAp && dataAp.success) {
                    this.fetchDocuments();
                    alert('Pengesahan diperbarui');
                } else {
                    alert('Gagal mengesahkan ulang: ' + (dataAp && dataAp.message ? dataAp.message : ''));
                }
            } catch(_) { alert('Terjadi kesalahan sistem'); }
        },
        async unapproveDocument(d) {
            if (!(this.userRole==='SUPERADMIN' || this.userRole==='ADMIN')) { alert('Akses pembatalan hanya untuk ADMIN/SUPERADMIN'); return; }
            if (!d || !d.id) { alert('ID dokumen tidak valid'); return; }
            if (!confirm('Batalkan pengesahan dokumen ini?')) return;
            try {
                this.normalizeBaseUrl();
                const resAp = await fetch(this.baseUrl + 'api/meetings.php?action=set_document_approval', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: d.id, approve: false })
                });
                const dataAp = await resAp.json();
                if (dataAp && dataAp.success) {
                    this.fetchDocuments();
                    alert('Pengesahan dibatalkan');
                } else {
                    alert('Gagal membatalkan pengesahan: ' + (dataAp && dataAp.message ? dataAp.message : ''));
                }
            } catch(_) { alert('Terjadi kesalahan sistem'); }
        },
        hideLogSection() { this.showLogs = false; },
        async fetchLogs() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_activity_logs.php?module=EXECUTIVE&category=DOCUMENT&limit=200');
                const data = await res.json();
                if (data.success) this.logs = data.data || [];
                else this.logs = [];
            } catch (_) { this.logs = []; }
        },
        askDelete(d) {
            if (!this.canDelete()) { alert('Anda tidak memiliki hak hapus'); return; }
            this.pendingDeleteId = d && d.id ? d.id : null;
        },
        cancelDelete() { this.pendingDeleteId = null; },
        async performDeleteDocument(id) {
            if (!id) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=delete_document', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    this.pendingDeleteId = null;
                    this.fetchDocuments();
                } else {
                    alert('Gagal menghapus: ' + (data.message || ''));
                }
            } catch (_) { alert('Terjadi kesalahan sistem'); }
        },
        formatDocMeta(d) {
            const tags = String(d.tags || '').split(',').map(s=>s.trim()).filter(Boolean);
            const cat = (tags[0] || '').toUpperCase();
            const appr = tags.length > 1 ? tags.slice(1).join(', ') : '';
            return [cat || 'FILE', appr].filter(Boolean).join(' • ');
        },
        async fetchModules() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_modules.php');
                const data = await res.json();
                if (Array.isArray(data)) {
                    this.modulesList = data;
                } else if (data.success) {
                    this.modulesList = Array.isArray(data.data) ? data.data : [];
                } else {
                    this.modulesList = [];
                }
                if (!this.docForm.module_tag && this.modulesList.length > 0) this.docForm.module_tag = this.modulesList[0].code;
            } catch(_) { this.modulesList = []; }
        },
        async fetchDocuments() {
            try {
                this.normalizeBaseUrl();
                const url = this.selectedModule === 'ALL' ? (this.baseUrl + 'api/meetings.php?action=list_documents&limit=50') : (this.baseUrl + 'api/meetings.php?action=list_documents&module=' + this.selectedModule + '&limit=50');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) this.documents = data.data || [];
                else this.documents = [];
            } catch (_) {}
        },
        formatDateTime(s) {
            if (!s) return '';
            try {
                const d = new Date(s);
                const pad = (n) => String(n).padStart(2,'0');
                return `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            } catch(_) { return s; }
        },
        openUpload() {
            this.showUpload = true;
            this.previewUrl = '';
            this.docForm = { module_tag: this.modulesList[0]?.code || '', category: 'ATURAN', doc_title: '', approval_note: '', description: '', doc_file: null, doc_url: '', doc_type: 'FILE' };
        },
        closeUpload() {
            this.showUpload = false;
            this.previewUrl = '';
        },
        onFileChange(e) {
            const f = e.target.files && e.target.files[0] ? e.target.files[0] : null;
            this.docForm.doc_file = f || null;
            this.previewUrl = '';
        },
        async uploadAndSave() {
            if (!this.docForm.doc_title) { alert('Nama dokumen wajib diisi'); return; }
            if (!this.docForm.module_tag) { alert('Divisi/Modul wajib dipilih'); return; }
            if (!this.docForm.doc_file) { alert('File PDF wajib diunggah'); return; }
            this.savingDoc = true;
            try {
                this.normalizeBaseUrl();
                const fd = new FormData();
                fd.append('file', this.docForm.doc_file);
                const upRes = await fetch(this.baseUrl + 'api/meetings.php?action=upload_document', { method: 'POST', body: fd });
                const upData = await upRes.json();
                if (!upData.success) { alert('Upload gagal: ' + upData.message); this.savingDoc = false; return; }
                this.docForm.doc_url = upData.data.url;
                this.previewUrl = upData.data.url;
                const tags = [this.docForm.category, (this.docForm.approval_note || '').trim()].filter(Boolean).join(',');
                const payload = {
                    meeting_id: null,
                    module_tag: this.docForm.module_tag,
                    doc_title: this.docForm.doc_title,
                    doc_url: this.docForm.doc_url,
                    doc_type: this.docForm.doc_type,
                    tags: tags,
                    description: this.docForm.description || ''
                };
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=add_document', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    this.showUpload = false;
                    this.fetchDocuments();
                    alert('Dokumen ditambahkan');
                } else {
                    alert('Gagal menyimpan: ' + data.message);
                }
            } catch(_) { alert('Terjadi kesalahan sistem'); }
            finally { this.savingDoc = false; }
        },
        openViewer(d) {
            const url = d.doc_url || d.url || '';
            if (!url) { window.alert('URL dokumen tidak tersedia'); return; }
            const isPdf = /\.pdf(\?|$)/i.test(url);
            if (!isPdf) { window.open(this.baseUrl + url, '_blank'); return; }
            this.viewerUrl = url;
            this.viewerTitle = d.doc_title || 'Dokumen';
            this.showViewer = true;
        },
        closeViewer() {
            this.showViewer = false;
            this.viewerUrl = '';
            this.viewerTitle = '';
        },
        openEdit(d) {
            if (d.approval_role || d.approval_date) { alert('Dokumen sudah disahkan, tidak bisa diedit'); return; }
            const tags = String(d.tags || '').split(',').map(s=>s.trim()).filter(Boolean);
            const category = ['ATURAN','SOP','LAIN'].includes(String(tags[0]||'').toUpperCase()) ? String(tags[0]).toUpperCase() : 'ATURAN';
            const approval_note = tags.length > 1 ? tags.slice(1).join(',') : '';
            this.editForm = {
                id: d.id,
                module_tag: d.module_tag || '',
                category,
                doc_title: d.doc_title || '',
                approval_note,
                description: d.description || '',
                doc_url: d.doc_url || d.url || '',
                doc_type: d.doc_type || 'FILE',
                doc_file: null
            };
            this.showEdit = true;
        },
        closeEdit() {
            this.showEdit = false;
            this.editForm = { id: null, module_tag: '', category: 'ATURAN', doc_title: '', approval_note: '', doc_url: '', doc_type: 'FILE', doc_file: null };
        },
        onEditFileChange(e) {
            const f = e.target.files && e.target.files[0] ? e.target.files[0] : null;
            this.editForm.doc_file = f || null;
        },
        async updateAndSave() {
            if (!this.editForm.id) { alert('ID dokumen tidak valid'); return; }
            if (!this.editForm.doc_title) { alert('Nama dokumen wajib diisi'); return; }
            if (!this.editForm.module_tag) { alert('Divisi/Modul wajib dipilih'); return; }
            this.savingEdit = true;
            try {
                this.normalizeBaseUrl();
                if (this.editForm.doc_file) {
                    const fd = new FormData();
                    fd.append('file', this.editForm.doc_file);
                    const upRes = await fetch(this.baseUrl + 'api/meetings.php?action=upload_document', { method: 'POST', body: fd });
                    const upData = await upRes.json();
                    if (!upData.success) { alert('Upload gagal: ' + upData.message); this.savingEdit = false; return; }
                    this.editForm.doc_url = upData.data.url;
                }
                const tags = [this.editForm.category, (this.editForm.approval_note || '').trim()].filter(Boolean).join(',');
                const payload = {
                    id: this.editForm.id,
                    module_tag: this.editForm.module_tag,
                    doc_title: this.editForm.doc_title,
                    doc_url: this.editForm.doc_url,
                    doc_type: this.editForm.doc_type,
                    tags: tags,
                    description: this.editForm.description || ''
                };
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=update_document', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    this.showEdit = false;
                    this.fetchDocuments();
                    alert('Dokumen diperbarui');
                } else {
                    alert('Gagal menyimpan: ' + data.message);
                }
            } catch(_) { alert('Terjadi kesalahan sistem'); }
            finally { this.savingEdit = false; }
        }
    },
    computed: {
        filteredLogs() {
            const a = (this.logsActionFilter || 'ALL').toUpperCase();
            if (a === 'ALL') return this.logs;
            return (this.logs || []).filter(l => String(l.action || '').toUpperCase() === a);
        }
    },
    mounted() { this.fetchModules(); this.fetchDocuments(); }
}).mount('#app');
</script>
