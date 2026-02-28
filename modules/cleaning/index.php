<?php
require_once '../../includes/guard.php';
require_login_and_module('cleaning');
require_once '../../includes/header.php';
$__now = new DateTime();
$__days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$__months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$currentDate = $__days[$__now->format('l')] . ', ' . $__now->format('d') . ' ' . $__months[(int)$__now->format('n')] . ' ' . $__now->format('Y');
?>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-broom text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Unit Kebersihan</h1>
                <span class="text-xs text-slate-500 font-medium">dashboard kebersihan terpadu</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?php echo $currentDate; ?></span>
            <button onclick="window.location.href='../../index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Kembali ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </button>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="rooms.php" class="block">
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Pengaturan</h3>
                        <p class="text-sm text-slate-500 mb-4">Kelola ruangan, checklist, dan data pegawai.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="rooms.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-building w-5 text-center"></i> List Ruangan/Bangunan
                        </a>
                        <a href="checklist_settings.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-tasks w-5 text-center"></i> Pengaturan Checklist
                        </a>
                        <a href="staff_list.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-users w-5 text-center"></i> Data Pegawai Kebersihan
                        </a>
                    </div>
                </div>
            </div>
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="assignments.php" class="block">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Penempatan & Checklist</h3>
                        <p class="text-sm text-slate-500 mb-4">Tentukan penugasan dan input checklist harian.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="assignments.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-user-check w-5 text-center"></i> Penempatan Petugas
                        </a>
                        <a href="checklists.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-clipboard-check w-5 text-center"></i> Daftar Checklist
                        </a>
                    </div>
                </div>
            </div>
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="reports.php" class="block">
                        <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Laporan & Statistik</h3>
                        <p class="text-sm text-slate-500 mb-4">Laporan per ruangan dan per pegawai.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="reports.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-purple-600 transition-colors">
                            <i class="fas fa-chart-bar w-5 text-center"></i> Lihat Laporan & Grafik
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const centerHTML = `
    <div class="max-w-6xl mx-auto mt-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-layer-group text-indigo-600"></i> Center Kebersihan
                </h3>
                <div class="flex items-center gap-2">
                    <button data-tab="MEETINGS" class="text-[11px] font-bold px-3 py-1 rounded bg-indigo-600 text-white">Rapat</button>
                    <button data-tab="TASKS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Task</button>
                    <button data-tab="PROC" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Pengajuan</button>
                    <button data-tab="DOCS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Dokumen</button>
                    <button data-tab="ZERO" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Zero Report</button>
                </div>
            </div>
            <div class="p-5 space-y-4">
                <div id="center-meetings">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Rapat Kebersihan</div>
                        <div class="flex items-center gap-2">
                            <span id="recentBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700"></span>
                            <button id="openCreateMeeting" class="px-2 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i> Buat Rapat</button>
                        </div>
                    </div>
                    <div id="meetingsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="meetingsItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="meetingsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada rapat.</div>
                </div>
                <div id="center-tasks" class="hidden">
                    <div class="flex items-center gap-2 mb-3">
                        <input id="newTaskInput" placeholder="Tambahkan tugas..." class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-500">
                        <button id="addTaskBtn" class="px-3 py-2 rounded bg-amber-600 text-white text-[12px] font-bold">Tambah</button>
                    </div>
                    <div id="tasksList" class="space-y-2"></div>
                    <div id="tasksEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada tugas.</div>
                </div>
                <div id="center-proc" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Pengajuan Kebersihan</div>
                        <a id="openApproval" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700">Buka Approval Center</a>
                    </div>
                    <div id="approvalsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="approvalsItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="approvalsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada pengajuan.</div>
                </div>
                <div id="center-docs" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Dokumen Kebersihan</div>
                        <div class="flex items-center gap-2">
                            <a id="openDocList" target="_blank" class="text-[11px] font-bold text-blue-600 hover:text-blue-700">Buka Daftar</a>
                            <button id="openDocUpload" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i> Tambah Dokumen</button>
                        </div>
                    </div>
                    <div id="docsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="docsItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="docsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada dokumen.</div>
                </div>
                <div id="center-zero" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-700">Jendela Lapor</div>
                        <div class="text-[10px] text-slate-500">21:00–22:00</div>
                    </div>
                    <div class="text-center text-slate-500 text-sm py-3">Fitur Zero Report Kebersihan akan ditambahkan.</div>
                </div>
            </div>
        </div>
    </div>`;
    const main = document.querySelector('main .max-w-6xl.mx-auto');
    const container = document.createElement('div');
    container.innerHTML = centerHTML;
    const centerRoot = container.firstElementChild;
    main.parentNode.appendChild(centerRoot);
    const viewerHTML = `
    <div id="docViewerModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-4xl overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <div id="docViewerTitle" class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-file-pdf text-red-600"></i> Dokumen</div>
                <button id="docViewerClose" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="h-[70vh]">
                <iframe id="docViewerFrame" class="w-full h-full"></iframe>
            </div>
        </div>
    </div>`;
    centerRoot.insertAdjacentHTML('beforeend', viewerHTML);
    const viewerModal = centerRoot.querySelector('#docViewerModal');
    const viewerFrame = centerRoot.querySelector('#docViewerFrame');
    const viewerTitle = centerRoot.querySelector('#docViewerTitle');
    centerRoot.querySelector('#docViewerClose').addEventListener('click', function() {
        viewerModal.classList.add('hidden');
        viewerFrame.src = '';
        viewerTitle.textContent = 'Dokumen';
    });
    const uploadHTML = `
    <div id="docUploadModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-upload text-indigo-600"></i> Upload Dokumen</div>
                <button id="docUploadClose" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Divisi/Modul</label>
                        <select id="docModule" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                            <option value="CLEANING">CLEANING</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Dokumen</label>
                        <select id="docCategory" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                            <option value="ATURAN">Aturan</option>
                            <option value="SOP">SOP</option>
                            <option value="LAIN">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nama Dokumen</label>
                        <input id="docTitle" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Nama dokumen">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Pengesahan / Perubahan</label>
                        <input id="docApprovalNote" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Disahkan 2026-02-03">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Upload File (PDF)</label>
                        <input id="docFile" type="file" accept=".pdf" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                        <p class="text-[11px] text-slate-500 mt-1">Format disarankan PDF untuk preview.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan Dokumen</label>
                        <textarea id="docDescription" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Dokumen ini ..."></textarea>
                    </div>
                </div>
                <div id="docPreviewBox" class="hidden rounded-xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-600">Preview</div>
                    <div class="h-96">
                        <iframe id="docPreviewFrame" class="w-full h-full"></iframe>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 flex justify-end gap-2">
                <button id="docUploadSave" class="px-3 py-1 text-[12px] font-bold rounded bg-indigo-600 text-white hover:bg-indigo-700">Simpan</button>
                <button id="docUploadCancel" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
            </div>
        </div>
    </div>`;
    const editHTML = `
    <div id="docEditModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-edit text-indigo-600"></i> Edit Dokumen</div>
                <button id="docEditClose" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Divisi/Modul</label>
                        <select id="editModule" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                            <option value="CLEANING">CLEANING</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Dokumen</label>
                        <select id="editCategory" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                            <option value="ATURAN">Aturan</option>
                            <option value="SOP">SOP</option>
                            <option value="LAIN">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nama Dokumen</label>
                        <input id="editTitle" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Nama dokumen">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Pengesahan / Perubahan</label>
                        <input id="editApprovalNote" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Disahkan 2026-02-03">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Ganti File (PDF)</label>
                        <input id="editFile" type="file" accept=".pdf" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                        <p class="text-[11px] text-slate-500 mt-1">Biarkan kosong jika tidak mengganti file.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan Dokumen</label>
                        <textarea id="editDescription" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Dokumen ini ..."></textarea>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 flex justify-end gap-2">
                <button id="docEditSave" class="px-3 py-1 text-[12px] font-bold rounded bg-indigo-600 text-white hover:bg-indigo-700">Simpan Perubahan</button>
                <button id="docEditCancel" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
            </div>
        </div>
    </div>`;
    centerRoot.insertAdjacentHTML('beforeend', uploadHTML + editHTML);
    const uploadModal = centerRoot.querySelector('#docUploadModal');
    const editModal = centerRoot.querySelector('#docEditModal');
    const docFileInput = centerRoot.querySelector('#docFile');
    const docPreviewBox = centerRoot.querySelector('#docPreviewBox');
    const docPreviewFrame = centerRoot.querySelector('#docPreviewFrame');
    let editingDocId = null;
    centerRoot.querySelector('#docUploadClose').addEventListener('click', () => { uploadModal.classList.add('hidden'); });
    centerRoot.querySelector('#docUploadCancel').addEventListener('click', () => { uploadModal.classList.add('hidden'); });
    centerRoot.querySelector('#docEditClose').addEventListener('click', () => { editModal.classList.add('hidden'); editingDocId = null; });
    centerRoot.querySelector('#docEditCancel').addEventListener('click', () => { editModal.classList.add('hidden'); editingDocId = null; });
    centerRoot.querySelector('#openDocUpload').addEventListener('click', () => {
        centerRoot.querySelector('#docModule').value = 'CLEANING';
        centerRoot.querySelector('#docCategory').value = 'ATURAN';
        centerRoot.querySelector('#docTitle').value = '';
        centerRoot.querySelector('#docApprovalNote').value = '';
        centerRoot.querySelector('#docDescription').value = '';
        docFileInput.value = '';
        docPreviewBox.classList.add('hidden');
        docPreviewFrame.src = '';
        uploadModal.classList.remove('hidden');
    });
    docFileInput.addEventListener('change', () => { docPreviewBox.classList.add('hidden'); docPreviewFrame.src = ''; });
    centerRoot.querySelector('#docUploadSave').addEventListener('click', async () => {
        const title = String(centerRoot.querySelector('#docTitle').value || '').trim();
        const module = centerRoot.querySelector('#docModule').value || 'CLEANING';
        const category = centerRoot.querySelector('#docCategory').value || 'ATURAN';
        const approvalNote = String(centerRoot.querySelector('#docApprovalNote').value || '').trim();
        const description = String(centerRoot.querySelector('#docDescription').value || '').trim();
        const file = docFileInput.files && docFileInput.files[0] ? docFileInput.files[0] : null;
        if (!title) { alert('Nama dokumen wajib diisi'); return; }
        if (!file) { alert('File PDF wajib diunggah'); return; }
        try {
            const fd = new FormData();
            fd.append('file', file);
            const upRes = await fetch(getBaseUrl() + 'api/meetings.php?action=upload_document', { method: 'POST', body: fd });
            const upData = await upRes.json();
            if (!upData.success) { alert('Upload gagal: ' + (upData.message || '')); return; }
            const url = upData.data && upData.data.url ? upData.data.url : '';
            const tags = [category, approvalNote].filter(Boolean).join(',');
            const payload = { meeting_id: null, module_tag: module, doc_title: title, doc_url: url, doc_type: 'FILE', tags, description };
            const res = await fetch(getBaseUrl() + 'api/meetings.php?action=add_document', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) {
                uploadModal.classList.add('hidden');
                fetchDocuments();
                alert('Dokumen ditambahkan');
            } else {
                alert('Gagal menyimpan: ' + (data.message || ''));
            }
        } catch(_) { alert('Terjadi kesalahan sistem'); }
    });
    centerRoot.querySelector('#docEditSave').addEventListener('click', async () => {
        if (!editingDocId) { alert('ID dokumen tidak valid'); return; }
        const title = String(centerRoot.querySelector('#editTitle').value || '').trim();
        const module = centerRoot.querySelector('#editModule').value || 'CLEANING';
        const category = centerRoot.querySelector('#editCategory').value || 'ATURAN';
        const approvalNote = String(centerRoot.querySelector('#editApprovalNote').value || '').trim();
        const description = String(centerRoot.querySelector('#editDescription').value || '').trim();
        const file = centerRoot.querySelector('#editFile').files && centerRoot.querySelector('#editFile').files[0] ? centerRoot.querySelector('#editFile').files[0] : null;
        if (!title) { alert('Nama dokumen wajib diisi'); return; }
        let docUrl = '';
        try {
            if (file) {
                const fd = new FormData();
                fd.append('file', file);
                const upRes = await fetch(getBaseUrl() + 'api/meetings.php?action=upload_document', { method: 'POST', body: fd });
                const upData = await upRes.json();
                if (!upData.success) { alert('Upload gagal: ' + (upData.message || '')); return; }
                docUrl = upData.data && upData.data.url ? upData.data.url : '';
            }
            const tags = [category, approvalNote].filter(Boolean).join(',');
            const payload = { id: editingDocId, module_tag: module, doc_title: title, doc_url: docUrl, doc_type: 'FILE', tags, description };
            const res = await fetch(getBaseUrl() + 'api/meetings.php?action=update_document', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) {
                editModal.classList.add('hidden');
                editingDocId = null;
                fetchDocuments();
                alert('Dokumen diperbarui');
            } else {
                alert('Gagal menyimpan: ' + (data.message || ''));
            }
        } catch(_) { alert('Terjadi kesalahan sistem'); }
    });
    const meetingHTML = `
    <div id="cleaningMeetingApp">
        <div v-if="showCreateModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-800">{{ meetingForm.id ? 'Edit Rapat' : 'Buat Rapat' }}</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Judul</label>
                            <input v-model="meetingForm.title" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label>
                                <input type="date" v-model="meetingForm.meeting_date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Rapat</label>
                                <input v-model="meetingForm.meeting_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="M-YYYYMMDD-XXXX">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Divisi</label>
                                <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span v-if="meetingForm.module_tag" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">{{ meetingForm.module_tag }}</span>
                                    </div>
                                    <input v-model="moduleQueryPrimary" @input="updateModuleSuggestionsPrimary" placeholder="Cari divisi" class="w-full outline-none">
                                </div>
                                <div v-if="moduleSuggestionsPrimary.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                    <div v-for="s in moduleSuggestionsPrimary" :key="s" @click="setPrimaryModuleTag(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Divisi terkait</label>
                                <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <span v-for="mod in (meetingForm.modules_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="mod" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                            {{ mod }}
                                            <button @click="removeRelatedModule(mod)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                                        </span>
                                    </div>
                                    <input v-model="moduleQueryRelated" @input="updateModuleSuggestionsRelated" placeholder="Cari divisi" class="w-full outline-none">
                                </div>
                                <div v-if="moduleSuggestionsRelated.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                    <div v-for="s in moduleSuggestionsRelated" :key="s" @click="addRelatedModule(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi Rapat</label>
                            <input v-model="meetingForm.location" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Ruang Rapat A">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 mb-1">Cari Peserta</label>
                            <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                <input v-model="attendeeQuery" @input="updateAttendeeSuggestions" @focus="ensureStaffLoaded" placeholder="Cari nama peserta..." class="w-full outline-none">
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                    {{ att }}
                                    <button @click="removeAttendee(att)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                                </span>
                            </div>
                            <div v-if="attendeeSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                <div v-for="s in attendeeSuggestions" :key="s.id || s.name" @click="addAttendee(s.name)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                                    {{ s.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Notulensi</label>
                            <textarea v-model="meetingForm.notes" rows="10" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Keputusan</label>
                            <textarea v-model="meetingForm.decisions" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-bold text-slate-500">Catatan Peserta</label>
                            <button @click="saveEditorPoints" :disabled="!meetingForm.id || savingEditorPoints" class="px-2 py-1 text-[11px] font-bold bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50">
                                <i class="fas fa-save mr-1" :class="{'fa-spin': savingEditorPoints}"></i> Simpan Catatan
                            </button>
                        </div>
                        <div class="border border-slate-200 rounded p-3 bg-white max-h-80 overflow-y-auto space-y-2">
                            <div v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="space-y-1">
                                <div class="text-[11px] font-bold text-slate-700">{{ att }}</div>
                                <textarea v-model="editorPoints[att]" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-[12px]" placeholder="Catatan peserta"></textarea>
                            </div>
                            <div v-if="!(meetingForm.attendees_text || '').trim()" class="text-[12px] text-slate-400">Peserta belum ditentukan.</div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="closeModal" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Batal</button>
                    <button @click="saveMeeting" :disabled="savingMeeting" class="px-3 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                        <i class="fas fa-save mr-1" :class="{'fa-spin': savingMeeting}"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
        <div v-if="showDetailModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-800">Detail Rapat</h3>
                    <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div v-if="meetingDetail" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[11px] text-slate-500 font-bold">Judul</div>
                                <div class="text-sm font-bold text-slate-800">{{ meetingDetail.title }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] text-slate-500 font-bold">Tanggal</div>
                                <div class="text-[12px] text-slate-600">{{ formatDateFull(meetingDetail.meeting_date) }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[11px] text-slate-500 font-bold">Nomor Rapat</div>
                                <div class="text-[12px] font-mono text-slate-700">{{ meetingDetail.meeting_number }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] text-slate-500 font-bold">Divisi</div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ meetingDetail.module_tag }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[11px] text-slate-500 font-bold">Lokasi</div>
                                <div class="text-[12px] text-slate-700">{{ meetingDetail.location || '-' }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500 font-bold">Peserta</div>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <span v-for="p in (meetingDetail.attendees || [])" :key="p" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">{{ p }}</span>
                                <div v-if="!meetingDetail.attendees || meetingDetail.attendees.length === 0" class="text-[12px] text-slate-400 italic">Tidak tercatat</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500 font-bold">Notulensi</div>
                            <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-100 rounded p-3">{{ meetingDetail.notes || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500 font-bold">Kesimpulan</div>
                            <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-100 rounded p-3">{{ meetingDetail.decisions || '-' }}</div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">Catatan per Peserta (KPI Keaktifan)</div>
                        <div class="space-y-2 border border-slate-200 rounded p-3 bg-white">
                            <div v-for="p in (meetingDetail.attendees || [])" :key="p" class="flex items-start gap-2">
                                <div class="text-[11px] font-bold text-slate-700 w-40 shrink-0">{{ p }}</div>
                                <textarea v-model="notesPerParticipant[p]" rows="2" class="flex-1 border border-slate-300 rounded px-3 py-2 text-[12px]" placeholder="Catatan keaktifan peserta"></textarea>
                            </div>
                            <div v-if="!meetingDetail.attendees || meetingDetail.attendees.length === 0" class="text-[12px] text-slate-400">Peserta belum ditentukan.</div>
                            <div class="text-right mt-2">
                                <button @click="saveParticipantNotes" :disabled="savingNotes" class="px-3 py-1 text-[12px] font-bold bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50">
                                    <i class="fas fa-save mr-1" :class="{'fa-spin': savingNotes}"></i> Simpan Catatan
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-2">
                        <button @click="closeDetailModal" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    centerRoot.insertAdjacentHTML('beforeend', meetingHTML);
    function getBaseUrl() {
        let baseUrl = window.BASE_URL || '/';
        if (baseUrl === '/' || !baseUrl) {
            const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
            baseUrl = m ? `/${m[1]}/` : '/';
        }
        return baseUrl;
    }
    function formatDateTime(s) {
        if (!s) return '';
        try {
            const d = new Date(s);
            const pad = (n) => String(n).padStart(2,'0');
            return `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        } catch(_) { return s || ''; }
    }
    function formatDocMeta(d) {
        const tags = String(d.tags || '').split(',').map(s=>s.trim()).filter(Boolean);
        const cat = (tags[0] || '').toUpperCase();
        const appr = tags.length > 1 ? tags.slice(1).join(', ') : '';
        return [cat || (d.doc_type || 'FILE'), appr].filter(Boolean).join(' • ');
    }
    function approvalDisplay(d) {
        const name = d.approval_people_name || d.approval_username || '';
        return name ? name : (d.approval_role || '-');
    }
    const tabButtons = centerRoot.querySelectorAll('button[data-tab]');
    const views = {
        MEETINGS: centerRoot.querySelector('#center-meetings'),
        TASKS: centerRoot.querySelector('#center-tasks'),
        PROC: centerRoot.querySelector('#center-proc'),
        DOCS: centerRoot.querySelector('#center-docs'),
        ZERO: centerRoot.querySelector('#center-zero')
    };
    function switchTab(t) {
        Object.entries(views).forEach(([k, el]) => {
            if (el) el.classList.toggle('hidden', k !== t);
        });
        tabButtons.forEach(b => {
            const active = b.getAttribute('data-tab') === t;
            b.className = 'text-[11px] font-bold px-3 py-1 rounded ' + (active ? (
                t==='MEETINGS' ? 'bg-indigo-600 text-white' :
                t==='TASKS' ? 'bg-amber-600 text-white' :
                t==='PROC' ? 'bg-emerald-600 text-white' :
                t==='DOCS' ? 'bg-blue-600 text-white' :
                'bg-red-600 text-white'
            ) : 'bg-slate-100 text-slate-700');
        });
        if (t === 'MEETINGS') fetchMeetings();
        else if (t === 'DOCS') fetchDocuments();
        else if (t === 'PROC') fetchApprovals();
    }
    tabButtons.forEach(b => b.addEventListener('click', e => switchTab(b.getAttribute('data-tab'))));
    switchTab('MEETINGS');
    const { createApp } = Vue;
    const meetingApp = createApp({
        data() {
            return {
                showCreateModal: false,
                showDetailModal: false,
                meetingDetail: null,
                notesPerParticipant: {},
                savingMeeting: false,
                modulesList: [],
                moduleQueryPrimary: '',
                moduleSuggestionsPrimary: [],
                moduleQueryRelated: '',
                moduleSuggestionsRelated: [],
                staffDirectory: [],
                staffLoaded: false,
                attendeeQuery: '',
                attendeeSuggestions: [],
                meetingForm: { id: null, meeting_number: '', title: '', meeting_date: new Date().toISOString().slice(0,10), module_tag: 'CLEANING', modules_text: '', tags: '', attendees_text: '', notes: '', decisions: '', location: '' },
                editorPoints: {},
                savingEditorPoints: false,
                savingNotes: false
            }
        },
        methods: {
            formatDateFull(s) {
                try {
                    const d = new Date(s);
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                } catch(_) { return s || ''; }
            },
            async saveParticipantNotes() {
                if (!this.meetingDetail || !this.meetingDetail.id) return;
                this.savingNotes = true;
                try {
                    const notesPayload = Object.entries(this.notesPerParticipant || {}).map(([participant, note]) => ({ participant, note }));
                    const res = await fetch(getBaseUrl() + 'api/meetings.php?action=save_participant_notes', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ meeting_id: this.meetingDetail.id, notes: notesPayload })
                    });
                    const json = await res.json();
                    if (!json.success) {
                        alert('Gagal menyimpan catatan peserta: ' + (json.message || ''));
                    }
                } catch(_) {
                    alert('Terjadi kesalahan sistem');
                } finally {
                    this.savingNotes = false;
                }
            },
            initializeParticipantNotes() {
                this.notesPerParticipant = {};
                (this.meetingDetail?.attendees || []).forEach(n => { this.notesPerParticipant[n] = this.notesPerParticipant[n] || ''; });
                this.savingNotes = false;
            },
            async fetchParticipantNotes() {
                try {
                    if (!this.meetingDetail || !this.meetingDetail.id) return;
                    const res = await fetch(getBaseUrl() + 'api/meetings.php?action=list_participant_notes&meeting_id=' + this.meetingDetail.id);
                    const json = await res.json();
                    if (json.success && Array.isArray(json.data)) {
                        json.data.forEach(r => { const key = r.participant || ''; if (key) this.notesPerParticipant[key] = r.note || ''; });
                    }
                } catch(_) {}
            },
            normalizeBaseUrl() { return getBaseUrl(); },
            async fetchModules() {
                try {
                    const res = await fetch(getBaseUrl() + 'api/get_modules.php');
                    const data = await res.json();
                    if (Array.isArray(data)) this.modulesList = data;
                    else if (data.success) this.modulesList = Array.isArray(data.data) ? data.data : [];
                    else this.modulesList = [];
                } catch(_) { this.modulesList = []; }
            },
            async ensureStaffLoaded() {
                if (this.staffLoaded) return;
                try {
                    const res = await fetch(getBaseUrl() + 'api/get_all_staff.php');
                    const data = await res.json();
                    this.staffDirectory = Array.isArray(data) ? data : [];
                    this.staffLoaded = true;
                } catch(_) { this.staffDirectory = []; this.staffLoaded = false; }
            },
            updateAttendeeSuggestions() {
                const q = (this.attendeeQuery || '').trim().toLowerCase();
                if (q.length < 2 || !Array.isArray(this.staffDirectory)) { this.attendeeSuggestions = []; return; }
                const selected = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim().toLowerCase());
                this.attendeeSuggestions = this.staffDirectory
                    .filter(s => (s.name || '').toLowerCase().includes(q))
                    .filter(s => !selected.includes((s.name || '').toLowerCase()))
                    .slice(0, 8);
            },
            addAttendee(name) {
                const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
                if (!cur.map(s=>s.toLowerCase()).includes(String(name).toLowerCase())) cur.push(name);
                this.meetingForm.attendees_text = cur.join(', ');
                this.attendeeQuery = '';
                this.attendeeSuggestions = [];
                this.editorPoints[name] = this.editorPoints[name] || '';
            },
            removeAttendee(name) {
                const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
                this.meetingForm.attendees_text = cur.filter(s => s.toLowerCase() !== String(name).toLowerCase()).join(', ');
                delete this.editorPoints[name];
            },
            updateModuleSuggestionsPrimary() {
                const q = (this.moduleQueryPrimary || '').trim().toLowerCase();
                if (q.length < 1 || !Array.isArray(this.modulesList)) { this.moduleSuggestionsPrimary = []; return; }
                this.moduleSuggestionsPrimary = this.modulesList.map(m=>m.code).filter(code => code.toLowerCase().includes(q)).slice(0, 8);
            },
            updateModuleSuggestionsRelated() {
                const q = (this.moduleQueryRelated || '').trim().toLowerCase();
                if (q.length < 1 || !Array.isArray(this.modulesList)) { this.moduleSuggestionsRelated = []; return; }
                const selected = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase());
                this.moduleSuggestionsRelated = this.modulesList.map(m=>m.code).filter(code => code.toLowerCase().includes(q)).filter(code => !selected.includes(code)).slice(0, 8);
            },
            addRelatedModule(tag) {
                const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
                if (!cur.includes(tag)) cur.push(tag);
                this.meetingForm.modules_text = cur.join(', ');
                this.moduleQueryRelated = '';
                this.moduleSuggestionsRelated = [];
            },
            removeRelatedModule(tag) {
                const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
                this.meetingForm.modules_text = cur.filter(s => s !== tag).join(', ');
            },
            setPrimaryModuleTag(tag) {
                this.meetingForm.module_tag = tag;
                this.moduleQueryPrimary = '';
                this.moduleSuggestionsPrimary = [];
            },
            openNewMeeting() {
                const d = new Date().toISOString().slice(0,10);
                this.meetingForm.meeting_date = d;
                this.meetingForm.meeting_number = ('M-' + d.replaceAll('-','') + '-' + Math.floor(Math.random()*9000+1000));
                this.meetingForm.title = '';
                this.meetingForm.module_tag = 'CLEANING';
                this.meetingForm.modules_text = '';
                this.meetingForm.tags = '';
                this.meetingForm.location = '';
                this.meetingForm.attendees_text = '';
                this.meetingForm.notes = '';
                this.meetingForm.decisions = '';
                this.meetingForm.id = null;
                this.attendeeQuery = '';
                this.attendeeSuggestions = [];
                this.fetchModules();
                this.ensureStaffLoaded();
                this.editorPoints = {};
                this.savingEditorPoints = false;
                this.showCreateModal = true;
            },
            async openEditMeeting(m) {
                this.meetingForm = {
                    id: m.id,
                    meeting_number: m.meeting_number,
                    title: m.title,
                    meeting_date: m.meeting_date,
                    module_tag: m.module_tag,
                    modules_text: Array.isArray(m.modules) ? m.modules.join(', ') : (m.modules || ''),
                    tags: m.tags,
                    location: m.location,
                    attendees_text: Array.isArray(m.attendees) ? m.attendees.join(', ') : (m.attendees || ''),
                    notes: m.notes,
                    decisions: m.decisions
                };
                this.editorPoints = {};
                this.attendeeQuery = '';
                this.attendeeSuggestions = [];
                this.fetchModules();
                this.ensureStaffLoaded();
                
                // Fetch participant notes for edit
                try {
                    const res = await fetch(getBaseUrl() + 'api/meetings.php?action=list_participant_notes&meeting_id=' + m.id);
                    const json = await res.json();
                    if (json.success && Array.isArray(json.data)) {
                        json.data.forEach(n => {
                            if (n.participant) this.editorPoints[n.participant] = n.note;
                        });
                    }
                } catch(_) {}
                
                this.showCreateModal = true;
            },
            async openMeetingDetail(m) {
                let attendees = [];
                if (m.attendees) {
                    if (Array.isArray(m.attendees)) {
                        attendees = m.attendees;
                    } else {
                        try { attendees = JSON.parse(m.attendees) || []; }
                        catch(_) { attendees = String(m.attendees || '').split(',').map(s=>s.trim()).filter(Boolean); }
                    }
                }
                this.meetingDetail = {
                    id: m.id,
                    title: m.title,
                    meeting_number: m.meeting_number,
                    meeting_date: m.meeting_date,
                    module_tag: m.module_tag,
                    tags: m.tags || '',
                    location: m.location || '',
                    attendees: attendees,
                    notes: m.notes || '',
                    decisions: m.decisions || ''
                };
                this.showDetailModal = true;
                this.initializeParticipantNotes();
                this.fetchParticipantNotes();
            },
            closeModal() { this.showCreateModal = false; },
            closeDetailModal() { this.showDetailModal = false; },
            async saveMeeting() {
                if (!this.meetingForm.title) { alert('Judul rapat wajib diisi'); return; }
                this.savingMeeting = true;
                try {
                    const payload = {
                        id: null,
                        meeting_number: this.meetingForm.meeting_number,
                        title: this.meetingForm.title,
                        meeting_date: this.meetingForm.meeting_date,
                        module_tag: this.meetingForm.module_tag,
                        modules: (this.meetingForm.modules_text || '').split(',').map(s => s.trim().toUpperCase()).filter(Boolean).filter(m => this.modulesList.map(x=>x.code).includes(m)),
                        tags: this.meetingForm.tags,
                        location: this.meetingForm.location || '',
                        attendees: (this.meetingForm.attendees_text || '').split(',').map(s => s.trim()).filter(Boolean),
                        notes: this.meetingForm.notes,
                        decisions: this.meetingForm.decisions
                    };
                    const r = await fetch(getBaseUrl() + 'api/meetings.php?action=save', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                    const j = await r.json();
                    if (j && j.success) {
                        const newId = j.data && j.data.id ? j.data.id : null;
                        this.meetingForm.id = newId;
                        await this.saveEditorPoints();
                        this.closeModal();
                        fetchMeetings();
                        alert('Rapat berhasil disimpan');
                    } else {
                        alert('Gagal: ' + (j && j.message ? j.message : ''));
                    }
                } catch(_) { alert('Terjadi kesalahan sistem'); }
                finally { this.savingMeeting = false; }
            },
            async saveEditorPoints() {
                if (!this.meetingForm || !this.meetingForm.id) return;
                this.savingEditorPoints = true;
                try {
                    const notesPayload = Object.entries(this.editorPoints || {})
                        .map(([participant, note]) => ({ participant, note }))
                        .filter(x => String(x.participant || '').trim() !== '');
                    const res = await fetch(getBaseUrl() + 'api/meetings.php?action=save_participant_notes', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ meeting_id: this.meetingForm.id, notes: notesPayload })
                    });
                    const data = await res.json();
                    if (!data.success) { alert('Gagal menyimpan catatan peserta: ' + (data.message || '')); }
                } catch (_) { alert('Terjadi kesalahan sistem'); }
                finally { this.savingEditorPoints = false; }
            }
        }
    }).mount('#cleaningMeetingApp');
    centerRoot.querySelector('#openCreateMeeting').addEventListener('click', function() {
        meetingApp.openNewMeeting();
    });
    async function fetchMeetings() {
        try {
            const r = await fetch(getBaseUrl() + 'api/meetings.php?action=list&module=CLEANING&limit=10');
            const j = await r.json();
            const items = centerRoot.querySelector('#meetingsItems');
            const box = centerRoot.querySelector('#meetingsList');
            const empty = centerRoot.querySelector('#meetingsEmpty');
            items.innerHTML = '';
            const rows = (j && j.success) ? (j.data || []) : [];
            if (rows.length > 0) {
                for (const m of rows) {
                    const el = document.createElement('div');
                    el.className = 'px-4 py-3 bg-white hover:bg-slate-50 transition-colors';
                    el.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">${m.title || '-'}</div>
                                <div class="text-[10px] font-mono text-slate-500">${m.meeting_number || ''}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400">${m.meeting_date || ''}</div>
                                <div class="flex gap-2 justify-end mt-1">
                                    <button class="text-[10px] font-bold text-slate-600 hover:text-indigo-600" data-action="detail">Detail</button>
                                    <button class="text-[10px] font-bold text-amber-600 hover:text-amber-800" data-action="edit">Edit</button>
                                </div>
                            </div>
                        </div>
                    `;
                    el.querySelector('button[data-action="detail"]').addEventListener('click', () => meetingApp.openMeetingDetail(m));
                    el.querySelector('button[data-action="edit"]').addEventListener('click', () => meetingApp.openEditMeeting(m));
                    items.appendChild(el);
                }
                box.classList.remove('hidden');
                empty.classList.add('hidden');
            } else {
                box.classList.add('hidden');
                empty.classList.remove('hidden');
            }
        } catch(_) {}
        try {
            const r = await fetch(getBaseUrl() + 'api/meetings.php?action=recent_count&module=CLEANING&days=7');
            const j = await r.json();
            const badge = centerRoot.querySelector('#recentBadge');
            const c = (j && j.success) ? (j.data?.count || 0) : 0;
            if (c > 0) {
                badge.textContent = c + ' baru';
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        } catch(_) {}
    }
    async function fetchDocuments() {
        try {
            centerRoot.querySelector('#openDocList').href = getBaseUrl() + 'api/meetings.php?action=list_documents&module=CLEANING&limit=50';
            const r = await fetch(getBaseUrl() + 'api/meetings.php?action=list_documents&module=CLEANING&limit=10');
            const j = await r.json();
            const items = centerRoot.querySelector('#docsItems');
            const box = centerRoot.querySelector('#docsList');
            const empty = centerRoot.querySelector('#docsEmpty');
            items.innerHTML = '';
            const rows = (j && j.success) ? (j.data || []) : [];
            if (rows.length > 0) {
                for (const d of rows) {
                    const el = document.createElement('div');
                    el.className = 'px-4 py-3 bg-white hover:bg-slate-50 transition-colors';
                    const approved = !!(d.approval_role || d.approval_date);
                    el.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">${d.doc_title || '-'}</div>
                                <div class="text-[11px] text-slate-500">${formatDocMeta(d)}</div>
                                ${d.description ? ('<div class="text-[11px] text-slate-500">Keterangan: ' + d.description + '</div>') : ''}
                            </div>
                            <div class="text-right">
                                <div class="flex gap-2 justify-end">
                                    <button class="text-[10px] font-bold text-blue-600 hover:underline" data-action="view">Lihat</button>
                                    ${approved ? '' : '<button class="text-[10px] font-bold text-amber-600 hover:text-amber-800" data-action="edit">Edit</button>'}
                                    ${approved ? '' : '<button class="text-[10px] font-bold text-red-600 hover:text-red-800" data-action="delete">Hapus</button>'}
                                </div>
                                <div class="mt-2 text-right">
                                    <div class="text-[10px] text-slate-400">Upload: ${formatDateTime(d.created_at)}</div>
                                    <div class="text-[10px] text-slate-400">Pengesahan: ${approvalDisplay(d)}${d.approval_date ? (' • ' + formatDateTime(d.approval_date)) : ''}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    const vb = el.querySelector('button[data-action="view"]');
                    if (vb) {
                        vb.addEventListener('click', function() {
                            const url = d.doc_url || '';
                            if (!url) { alert('Dokumen ini belum memiliki file untuk dilihat'); return; }
                            const isPdf = /\.pdf(\?|$)/i.test(url);
                            if (!isPdf) { window.open(getBaseUrl() + url, '_blank'); return; }
                            viewerTitle.textContent = d.doc_title || 'Dokumen';
                            viewerFrame.src = getBaseUrl() + url;
                            viewerModal.classList.remove('hidden');
                        });
                    }
                    const eb = el.querySelector('button[data-action="edit"]');
                    if (eb) {
                        eb.addEventListener('click', function() {
                            editingDocId = d.id;
                            centerRoot.querySelector('#editModule').value = String(d.module_tag || 'CLEANING');
                            const tags = String(d.tags || '').split(',').map(s=>s.trim()).filter(Boolean);
                            const category = ['ATURAN','SOP','LAIN'].includes(String(tags[0]||'').toUpperCase()) ? String(tags[0]).toUpperCase() : 'ATURAN';
                            const approval_note = tags.length > 1 ? tags.slice(1).join(',') : '';
                            centerRoot.querySelector('#editCategory').value = category;
                            centerRoot.querySelector('#editTitle').value = d.doc_title || '';
                            centerRoot.querySelector('#editApprovalNote').value = approval_note;
                            centerRoot.querySelector('#editDescription').value = d.description || '';
                            centerRoot.querySelector('#editFile').value = '';
                            editModal.classList.remove('hidden');
                        });
                    }
                    const db = el.querySelector('button[data-action="delete"]');
                    if (db) {
                        db.addEventListener('click', async function() {
                            if (!confirm('Hapus dokumen ini?')) return;
                            try {
                                const res = await fetch(getBaseUrl() + 'api/meetings.php?action=delete_document', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ id: d.id })
                                });
                                const data = await res.json();
                                if (data.success) {
                                    fetchDocuments();
                                } else {
                                    alert('Gagal menghapus: ' + (data.message || ''));
                                }
                            } catch(_) { alert('Terjadi kesalahan sistem'); }
                        });
                    }
                    items.appendChild(el);
                }
                box.classList.remove('hidden');
                empty.classList.add('hidden');
            } else {
                box.classList.add('hidden');
                empty.classList.remove('hidden');
            }
        } catch(_) {}
    }
    function loadTasks() {
        let arr = [];
        try { arr = JSON.parse(localStorage.getItem('cleaning_tasks') || '[]'); } catch(_) {}
        const list = centerRoot.querySelector('#tasksList');
        const empty = centerRoot.querySelector('#tasksEmpty');
        list.innerHTML = '';
        if (Array.isArray(arr) && arr.length > 0) {
            for (const t of arr) {
                const el = document.createElement('div');
                el.className = 'flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-slate-50';
                el.innerHTML = `
                    <label class="flex items-center gap-2">
                        <input type="checkbox" ${t.done ? 'checked' : ''}>
                        <span class="${t.done ? 'line-through text-slate-400' : 'text-slate-700'}">${t.title}</span>
                    </label>
                    <button class="text-[12px] font-bold text-red-600 hover:text-red-700">Hapus</button>
                `;
                const cb = el.querySelector('input[type="checkbox"]');
                const del = el.querySelector('button');
                cb.addEventListener('change', () => { t.done = cb.checked; saveTasks(arr); });
                del.addEventListener('click', () => { const idx = arr.findIndex(x => x.id === t.id); if (idx >= 0) { arr.splice(idx,1); saveTasks(arr); } });
                list.appendChild(el);
            }
            empty.classList.add('hidden');
        } else {
            empty.classList.remove('hidden');
        }
    }
    function saveTasks(arr) {
        localStorage.setItem('cleaning_tasks', JSON.stringify(arr));
        loadTasks();
    }
    centerRoot.querySelector('#addTaskBtn').addEventListener('click', () => {
        const input = centerRoot.querySelector('#newTaskInput');
        const t = String(input.value || '').trim();
        if (!t) return;
        let arr = [];
        try { arr = JSON.parse(localStorage.getItem('cleaning_tasks') || '[]'); } catch(_) {}
        arr.push({ id: Date.now(), title: t, done: false });
        input.value = '';
        saveTasks(arr);
    });
    centerRoot.querySelector('#openApproval').href = getBaseUrl() + 'modules/executive/managerial_approval.php';
    fetchMeetings();
    fetchDocuments();
    async function fetchApprovals() {
        try {
            const r = await fetch(getBaseUrl() + 'api/approval.php?action=get_list&status=ALL');
            const j = await r.json();
            const items = centerRoot.querySelector('#approvalsItems');
            const box = centerRoot.querySelector('#approvalsList');
            const empty = centerRoot.querySelector('#approvalsEmpty');
            items.innerHTML = '';
            const rows = (j && j.success) ? (Array.isArray(j.data) ? j.data : []) : [];
            const filtered = rows.filter(a => String(a.module || '').toUpperCase() === 'CLEANING').slice(0,12);
            if (filtered.length > 0) {
                for (const a of filtered) {
                    const el = document.createElement('div');
                    el.className = 'px-4 py-3 bg-white hover:bg-slate-50 transition-colors';
                    el.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">${a.title || (a.module + ' #' + a.reference_no)}</div>
                                <div class="text-[10px] text-slate-500">${a.requester || '-'}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-bold ${a.status==='APPROVED' ? 'text-emerald-600' : a.status==='REJECTED' ? 'text-red-600' : 'text-amber-600'}">${a.status}</div>
                                <div class="text-[10px] text-slate-400">${a.created_at || ''}</div>
                            </div>
                        </div>
                        ${Number(a.amount||0) > 0 ? '<div class="text-[12px] text-slate-600 mt-2">'+new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(a.amount || 0)+'</div>' : ''}
                    `;
                    items.appendChild(el);
                }
                box.classList.remove('hidden');
                empty.classList.add('hidden');
            } else {
                box.classList.add('hidden');
                empty.classList.remove('hidden');
            }
        } catch(_) {}
    }
    fetchApprovals();
    loadTasks();
});
</script>
