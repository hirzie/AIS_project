<?php
require_once '../../includes/guard.php';
require_login_and_module('security');
require_once '../../includes/header.php';
require_once '../../config/database.php';
$__now = new DateTime();
$__days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$__months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$currentDate = $__days[$__now->format('l')] . ', ' . $__now->format('d') . ' ' . $__months[(int)$__now->format('n')] . ' ' . $__now->format('Y');
$__displayName = $_SESSION['username'] ?? 'Pengguna';
if (!empty($_SESSION['person_id'])) {
    try {
        $st = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
        $st->execute([$_SESSION['person_id']]);
        $nm = $st->fetchColumn();
        if ($nm) { $__displayName = $nm; }
    } catch (\Throwable $e) {}
}
?>
<script>
    window.USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
    window.USER_FULL_NAME = <?php echo json_encode($__displayName); ?>;
    window.__CURRENT_USER_ROLE__ = <?php echo json_encode(strtoupper($_SESSION['role'] ?? '')); ?>;
</script>
<div id="app" class="flex flex-col h-screen">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-shield-alt text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Keamanan</h1>
                <span class="text-xs text-slate-500 font-medium">Modul Add-on</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?php echo $currentDate; ?></span>
            <a href="<?php echo $baseUrl; ?>index.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </a>
        </div>
    </nav>

    <main class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50 relative">
        <div class="max-w-6xl mx-auto mb-3">
            <div class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-[12px] text-slate-700">
                <i class="fas fa-user mr-1 text-slate-500"></i> Hi <?php echo htmlspecialchars($__displayName); ?>
            </div>
        </div>
        <div class="max-w-6xl mx-auto grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4 justify-center justify-items-stretch relative z-10">
            <div class="group bg-white rounded-xl md:rounded-2xl p-3 md:p-4 lg:p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden w-full min-h-[160px] md:min-h-[220px] lg:min-h-[320px]">
                <div class="absolute top-0 right-0 w-12 h-12 md:w-16 md:h-16 lg:w-24 lg:h-24 bg-indigo-50 rounded-bl-[100px] -mr-3 -mt-3 md:-mr-4 md:-mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 bg-indigo-100 text-indigo-600 rounded-xl items-center justify-center text-lg md:text-xl lg:text-2xl mb-3 md:mb-4 lg:mb-6 mx-auto shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="text-sm md:text-base lg:text-xl font-bold text-slate-800 text-center">Pengaturan</h3>
                    <p class="text-[11px] md:text-sm text-slate-500 text-center">List pegawai, area penjagaan, checklist</p>
                    <div class="hidden md:grid mt-auto pt-3 lg:pt-4 border-t border-slate-100 grid-cols-1 gap-2">
                        <a href="employees.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-users w-5 text-center"></i> List Pegawai Security
                        </a>
                        <a href="areas.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-map-pin w-5 text-center"></i> Area Penjagaan
                        </a>
                        <a href="checklist_settings.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-clipboard-check w-5 text-center"></i> Pengaturan Checklist
                        </a>
                    </div>
                </div>
            </div>
            <a href="shifts.php" class="group bg-white rounded-xl md:rounded-2xl p-3 md:p-4 lg:p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden w-full min-h-[150px] md:min-h-[200px] lg:min-h-[300px]">
                <div class="absolute top-0 right-0 w-12 h-12 md:w-16 md:h-16 lg:w-24 lg:h-24 bg-blue-50 rounded-bl-[100px] -mr-3 -mt-3 md:-mr-4 md:-mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 bg-blue-100 text-blue-600 rounded-xl items-center justify-center text-lg md:text-xl lg:text-2xl mb-3 md:mb-4 lg:mb-6 mx-auto shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <i class="fas fa-business-time"></i>
                    </div>
                    <h3 class="text-sm md:text-base lg:text-xl font-bold text-slate-800 text-center">Pengaturan Shift</h3>
                    <p class="text-[11px] md:text-sm text-slate-500 text-center">Durasi jendela & template default</p>
                    <div class="hidden md:grid mt-auto pt-3 lg:pt-4 border-t border-slate-100 grid-cols-1 gap-2">
                        <span class="flex items-center text-xs font-medium text-slate-500"><i class="fas fa-clock w-5 text-center"></i> Atur jam & hari</span>
                    </div>
                </div>
            </a>
            <div class="group bg-white rounded-xl md:rounded-2xl p-3 md:p-4 lg:p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden w-full min-h-[150px] md:min-h-[200px] lg:min-h-[300px]">
                <div class="absolute top-0 right-0 w-12 h-12 md:w-16 md:h-16 lg:w-24 lg:h-24 bg-rose-50 rounded-bl-[100px] -mr-3 -mt-3 md:-mr-4 md:-mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 bg-rose-100 text-rose-600 rounded-xl items-center justify-center text-lg md:text-xl lg:text-2xl mb-3 md:mb-4 lg:mb-6 mx-auto shadow-sm group-hover:bg-rose-600 group-hover:text-white transition-colors">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="text-sm md:text-base lg:text-xl font-bold text-slate-800 text-center">Pelaporan</h3>
                    <p class="text-[11px] md:text-sm text-slate-500 text-center">Ringkasan aktivitas keamanan</p>
                    <div class="hidden md:grid mt-auto pt-3 lg:pt-4 border-t border-slate-100 grid-cols-1 gap-2">
                        <a href="reports.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-rose-600 transition-colors">
                            <i class="fas fa-list w-5 text-center"></i> Checklist Bulanan
                        </a>
                        <a href="activity_log.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-history w-5 text-center"></i> Log Aktivitas
                        </a>
                    </div>
                </div>
            </div>
            <a href="assets.php" class="group bg-white rounded-xl md:rounded-2xl p-3 md:p-4 lg:p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden w-full min-h-[150px] md:min-h-[200px] lg:min-h-[300px]">
                <div class="absolute top-0 right-0 w-12 h-12 md:w-16 md:h-16 lg:w-24 lg:h-24 bg-emerald-50 rounded-bl-[100px] -mr-3 -mt-3 md:-mr-4 md:-mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 bg-emerald-100 text-emerald-600 rounded-xl items-center justify-center text-lg md:text-xl lg:text-2xl mb-3 md:mb-4 lg:mb-6 mx-auto shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3 class="text-sm md:text-base lg:text-xl font-bold text-slate-800 text-center">Inventory</h3>
                    <p class="text-[11px] md:text-sm text-slate-500 text-center">Aset keamanan dan kendaraan</p>
                    <div class="hidden md:grid mt-auto pt-3 lg:pt-4 border-t border-slate-100 grid-cols-1 gap-2">
                        <span class="flex items-center text-xs font-medium text-slate-500"><i class="fas fa-car w-5 text-center"></i> Kendaraan</span>
                    </div>
                </div>
            </a>
        </div>
    </main>
</div>
<?php require_once '../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const centerHTML = `
    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-layer-group text-indigo-600"></i> Center Keamanan
            </h3>
            <div class="flex items-center gap-2">
                <button data-tab="MEETINGS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Rapat</button>
                <button data-tab="TASKS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Task</button>
                <button data-tab="PROC" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Pengajuan</button>
                <button data-tab="DOCS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Dokumen</button>
                <button data-tab="ZERO" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Zero Report</button>
            </div>
        </div>
        <div class="p-5 space-y-4">
            <div id="center-meetings" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-[12px] font-bold text-slate-600">Rapat Keamanan</div>
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
                    <div class="text-[12px] font-bold text-slate-600">Pengajuan Keamanan</div>
                    <a id="openApproval" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700">Buka Approval Center</a>
                </div>
                <div id="approvalsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                    <div id="approvalsItems" class="divide-y divide-slate-100"></div>
                </div>
                <div id="approvalsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada pengajuan.</div>
            </div>
            <div id="center-docs" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-[12px] font-bold text-slate-600">Dokumen Keamanan</div>
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
                    <div class="text-[12px] font-bold text-slate-700">Zero Report Keamanan</div>
                    <div class="text-[10px] text-slate-500" id="zero-period"></div>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="border border-slate-200 rounded-xl p-4 bg-white">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Harian (Checklist)</div>
                            <div id="zero-daily-summary" class="text-sm text-slate-800 mt-1"></div>
                            <div class="text-[11px] text-slate-500">Rule: jendela sesuai pengaturan shift</div>
                        </div>
                        <div class="border border-slate-200 rounded-xl p-4 bg-white">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Mingguan (Rapat)</div>
                            <div id="zero-weekly-summary" class="text-sm text-slate-800 mt-1"></div>
                            <div class="text-[11px] text-slate-500">Target: 1x rapat per minggu</div>
                        </div>
                        <div class="border border-slate-200 rounded-xl p-4 bg-white">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Bulanan</div>
                            <div id="zero-monthly-summary" class="text-sm text-slate-800 mt-1">Belum ditetapkan</div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">Detail Harian per Shift</div>
                        <div id="zero-daily-list" class="divide-y divide-slate-100"></div>
                        <div id="zero-daily-empty" class="text-center text-slate-400 text-sm py-3 hidden">Tidak ada shift aktif hari ini</div>
                    </div>
                    <!-- Quick panel akan ditambahkan dinamis di bawah ini -->
                </div>
            </div>
            <div id="center-placeholder" class="text-center py-10">
                <div class="inline-flex w-16 h-16 bg-slate-50 rounded-full items-center justify-center text-slate-300 mb-3">
                    <i class="fas fa-mouse-pointer text-2xl"></i>
                </div>
                <p class="text-sm text-slate-500 font-medium">Pilih tab di atas untuk memuat data.</p>
            </div>
        </div>
    </div>`;
    const main = document.querySelector('main .max-w-6xl.mx-auto');
    const container = document.createElement('div');
    container.innerHTML = centerHTML;
    const centerRoot = container.firstElementChild;
    const gridWrap = document.createElement('div');
    gridWrap.className = 'max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6';
    const leftCol = document.createElement('div');
    leftCol.className = 'lg:col-span-2';
    const centerHolder = document.createElement('div');
    centerHolder.className = 'w-full max-w-3xl mx-auto';
    centerHolder.appendChild(centerRoot);
    leftCol.appendChild(centerHolder);
    gridWrap.appendChild(leftCol);
    main.parentNode.appendChild(gridWrap);
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
                            <option value="SECURITY">SECURITY</option>
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
                            <option value="SECURITY">SECURITY</option>
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
    const quickHTML = `
    <div id="quickChecklistModal" class="fixed inset-0 z-[9999] hidden bg-black/40 backdrop-blur-sm flex items-end sm:items-center justify-center p-2 sm:p-4">
        <div class="bg-white w-full sm:max-w-3xl lg:max-w-6xl h-[90vh] sm:h-[80vh] rounded-none sm:rounded-2xl shadow-xl border border-slate-200 flex flex-col">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <div class="font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-clipboard-check text-emerald-600"></i> Checklist Keamanan</div>
                <button id="quickChecklistClose" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-3 overflow-y-auto flex-1">
                <div id="quickChecklistInfo" class="text-[11px] text-slate-500"></div>
                <div id="quickChecklistItems" class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4"></div>
                <div class="sticky bottom-0 bg-white pt-2 flex justify-end gap-2 mt-2">
                    <button id="quickChecklistSave" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold">Simpan Checklist</button>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', quickHTML);
    const quickModal = document.querySelector('#quickChecklistModal');
    document.querySelector('#quickChecklistClose').addEventListener('click', function() {
        quickModal.classList.add('hidden');
    });
    const quickSideHTML = `
    <div id="zero-quick-side" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-clipboard-check text-emerald-600"></i> Lapor Checklist
            </h3>
            <span id="zero-quick-badge" class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">Window Tidak Aktif</span>
        </div>
        <div class="p-5 space-y-3">
            <div id="zero-quick-panel">
                <div id="zero-quick-status" class="text-[11px] text-slate-500">Tidak ada window aktif untuk Anda saat ini.</div>
                <div id="zero-quick-form" class="space-y-3 hidden">
                    <div class="text-[11px] text-slate-600" id="zero-quick-window"></div>
                    <div id="zero-quick-template-row">
                        <label class="text-[11px] text-slate-500">Template</label>
                        <input id="zero-quick-template-name" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-slate-100 text-slate-700 hidden" readonly>
                        <select id="zero-quick-template" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white"></select>
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-500">Lokasi</label>
                        <input id="zero-quick-location" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Gerbang Utama">
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-500">Catatan</label>
                        <textarea id="zero-quick-notes" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                    </div>
                    <button id="zero-quick-start" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold w-full">Mulai Checklist</button>
                </div>
                <div id="zero-quick-edit" class="space-y-3 hidden">
                    <div class="text-[11px] text-slate-600">Sudah dilaporkan untuk window ini.</div>
                    <button id="zero-quick-edit-btn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold w-full">Edit Laporan</button>
                </div>
            </div>
        </div>
    </div>`;
    const sideContainer = document.createElement('div');
    sideContainer.innerHTML = quickSideHTML;
    const sideEl = sideContainer.firstElementChild;
    gridWrap.appendChild(sideEl);
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
        centerRoot.querySelector('#docModule').value = 'SECURITY';
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
        const module = centerRoot.querySelector('#docModule').value || 'SECURITY';
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
        const module = centerRoot.querySelector('#editModule').value || 'SECURITY';
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
    <div id="securityMeetingApp" v-cloak>
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
                            <input v-model="meetingForm.location" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Pos Jaga A">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 mb-1">Cari Peserta</label>
                            <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                <input v-model="attendeeQuery" @input="updateAttendeeSuggestions" @focus="ensureStaffLoaded" placeholder="Cari nama peserta..." class="w-full outline-none">
                            </div>
                            <div class="flex flex-wrap gap-1">
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
                <div class="flex justify-end gap-2 mt-2">
                    <button @click="closeDetailModal" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Tutup</button>
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
        ZERO: centerRoot.querySelector('#center-zero'),
        PLACEHOLDER: centerRoot.querySelector('#center-placeholder')
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
        else if (t === 'ZERO') fetchZeroOverview();
        else if (t === 'TASKS') loadTasks();
    }
    tabButtons.forEach(b => b.addEventListener('click', e => switchTab(b.getAttribute('data-tab'))));
    // switchTab('ZERO');
    if (typeof Vue === 'undefined') {
        console.error('Vue is not loaded');
        alert('Error: Sistem tidak dapat memuat library Vue.js. Silakan refresh halaman.');
    }
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
                meetingForm: { id: null, meeting_number: '', title: '', meeting_date: new Date().toISOString().slice(0,10), module_tag: 'SECURITY', modules_text: '', tags: '', attendees_text: '', notes: '', decisions: '', location: '' },
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
                this.meetingForm.module_tag = 'SECURITY';
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
                        modules: (this.meetingForm.modules_text || '').split(',').map(s => s.trim().toUpperCase()).filter(Boolean),
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
    }).mount('#securityMeetingApp');
    centerRoot.querySelector('#openCreateMeeting').addEventListener('click', function() {
        meetingApp.openNewMeeting();
    });
    async function fetchMeetings() {
        try {
            const r = await fetch(getBaseUrl() + 'api/meetings.php?action=list&module=SECURITY&limit=10');
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
            const r = await fetch(getBaseUrl() + 'api/meetings.php?action=recent_count&module=SECURITY&days=7');
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
            centerRoot.querySelector('#openDocList').href = getBaseUrl() + 'api/meetings.php?action=list_documents&module=SECURITY&limit=50';
            const r = await fetch(getBaseUrl() + 'api/meetings.php?action=list_documents&module=SECURITY&limit=10');
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
                            centerRoot.querySelector('#editModule').value = String(d.module_tag || 'SECURITY');
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
        try { arr = JSON.parse(localStorage.getItem('security_tasks') || '[]'); } catch(_) {}
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
        localStorage.setItem('security_tasks', JSON.stringify(arr));
        loadTasks();
    }
    centerRoot.querySelector('#addTaskBtn').addEventListener('click', () => {
        const input = centerRoot.querySelector('#newTaskInput');
        const t = String(input.value || '').trim();
        if (!t) return;
        let arr = [];
        try { arr = JSON.parse(localStorage.getItem('security_tasks') || '[]'); } catch(_) {}
        arr.push({ id: Date.now(), title: t, done: false });
        input.value = '';
        saveTasks(arr);
    });
    centerRoot.querySelector('#openApproval').href = getBaseUrl() + 'modules/executive/managerial_approval.php';
    // fetchMeetings();
    // fetchDocuments();
    async function fetchApprovals() {
        try {
            const r = await fetch(getBaseUrl() + 'api/approval.php?action=get_list&status=ALL');
            const j = await r.json();
            const items = centerRoot.querySelector('#approvalsItems');
            const box = centerRoot.querySelector('#approvalsList');
            const empty = centerRoot.querySelector('#approvalsEmpty');
            items.innerHTML = '';
            const rows = (j && j.success) ? (Array.isArray(j.data) ? j.data : []) : [];
            const filtered = rows.filter(a => String(a.module || '').toUpperCase() === 'SECURITY').slice(0,12);
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
    // fetchApprovals();
    // loadTasks();
    let currentUserId = (window.USER_ID ?? null);
    let checklistTemplates = [];
    let vehiclesList = [];
    let quickSelectedShift = null;
    let quickRunId = null;
    let serverNowHm = null;
    const namesEqual = (a, b) => {
        try { return String(a||'').trim().toUpperCase() === String(b||'').trim().toUpperCase(); }
        catch(_) { return false; }
    };
    function parseTimeToMinutes(t) {
        const p = String(t || '').split(':'); const h = parseInt(p[0]||'0',10); const m = parseInt(p[1]||'0',10);
        return h*60 + m;
    }
    function nowMinutes() {
        try {
            const s = String(serverNowHm || '').trim();
            if (s && s.includes(':')) {
                const p = s.split(':'); const h = parseInt(p[0]||'0',10); const m = parseInt(p[1]||'0',10);
                return h*60 + m;
            }
        } catch(_) {}
        const d = new Date(); return d.getHours()*60 + d.getMinutes();
    }
    async function fetchMyProfile() {
        try {
            const r = await fetch(getBaseUrl() + 'api/get_my_profile.php');
            const j = await r.json();
            if (j.success) {
                const uid = j.data && j.data.user_id ? j.data.user_id : null;
                if (uid) { currentUserId = uid; }
                const roleVal = (j.data && j.data.role) ? String(j.data.role).toUpperCase() : '';
                if (roleVal) { window.__CURRENT_USER_ROLE__ = roleVal; }
                const fullName = (j.data && (j.data.person_name || j.data.name)) ? (j.data.person_name || j.data.name) : (j.data && j.data.username ? j.data.username : null);
                if (fullName) { window.USER_FULL_NAME = fullName; }
            }
        } catch(_) { /* keep currentUserId from session */ }
    }
    async function fetchChecklistTemplates() {
        try {
            const r = await fetch(getBaseUrl() + 'api/security.php?action=list_checklist_templates');
            const j = await r.json();
            checklistTemplates = j.success ? (j.data || []) : [];
            const sel = document.querySelector('#zero-quick-template');
            sel.innerHTML = '';
            for (const t of checklistTemplates) {
                const opt = document.createElement('option');
                opt.value = t.id; opt.textContent = t.name;
                sel.appendChild(opt);
            }
        } catch(_) { checklistTemplates = []; }
    }
    async function fetchVehicles() {
        try {
            const r = await fetch(getBaseUrl() + 'api/inventory.php?action=get_vehicles');
            const j = await r.json();
            vehiclesList = j.success ? (j.data || []) : [];
        } catch(_) { vehiclesList = []; }
    }
    function updateQuickPanel(shifts) {
        const statusEl = document.querySelector('#zero-quick-status');
        const formEl = document.querySelector('#zero-quick-form');
        const winEl = document.querySelector('#zero-quick-window');
        const badgeEl = document.querySelector('#zero-quick-badge');
        const editEl = document.querySelector('#zero-quick-edit');
        const getCurrentRole = () => String(window.__CURRENT_USER_ROLE__ || window.USER_ROLE || '').toUpperCase();
        formEl.classList.add('hidden');
        editEl.classList.add('hidden');
        statusEl.textContent = 'Tidak ada window aktif untuk Anda saat ini.';
        quickSelectedShift = null;
        const now = nowMinutes();
        let activeFound = false;
        for (const s of shifts) {
            const ws = parseTimeToMinutes(s.window_start); const we = parseTimeToMinutes(s.window_end);
            const role = getCurrentRole();
            const hasOverride = role === 'ADMIN' || role === 'SUPERADMIN';
            const userMatch = hasOverride || !s.user_id || (currentUserId && String(s.user_id) === String(currentUserId)) || (role === 'SECURITY' && namesEqual(s.employee_name, window.USER_FULL_NAME));
            const active = (typeof s.is_active === 'boolean') ? s.is_active : (now >= ws && now <= we);
            if (userMatch && active) {
                quickSelectedShift = s;
                winEl.textContent = 'Window ' + (s.window_start || '') + '–' + (s.window_end || '') + ' • Selesai ' + (s.end_time || '');
                if (String(s.status || '') === 'REPORTED') {
                    statusEl.textContent = '';
                    editEl.classList.remove('hidden');
                    formEl.classList.add('hidden');
                } else {
                    formEl.classList.remove('hidden');
                    statusEl.textContent = '';
                    editEl.classList.add('hidden');
                }
                // Auto-select default template if present
                try {
                    const sel = document.querySelector('#zero-quick-template');
                    const nameEl = document.querySelector('#zero-quick-template-name');
                    const defId = s.default_template_id || null;
                    quickSelectedTemplateId = defId || null;
                    if (defId) sel.value = String(defId);
                    const tpl = checklistTemplates.find(x => String(x.id) === String(defId));
                    const tplName = tpl ? (tpl.name || '') : (sel.options[sel.selectedIndex]?.textContent || '');
                    const roleNow = getCurrentRole();
                    const isAdmin = roleNow === 'ADMIN' || roleNow === 'SUPERADMIN';
                    if (isAdmin) {
                        sel.classList.remove('hidden');
                        if (nameEl) { nameEl.classList.add('hidden'); nameEl.value = ''; }
                    } else {
                        if (nameEl) { nameEl.value = tplName || ''; nameEl.classList.remove('hidden'); }
                        sel.classList.add('hidden');
                    }
                } catch(_) {}
                document.querySelector('#zero-quick-location').value = '';
                document.querySelector('#zero-quick-notes').value = '';
                activeFound = true;
                break;
            }
        }
        if (badgeEl) {
            if (activeFound) {
                const reported = quickSelectedShift && String(quickSelectedShift.status || '') === 'REPORTED';
                badgeEl.textContent = reported ? 'Window Aktif • Sudah Lapor' : 'Window Aktif';
                badgeEl.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700';
            } else {
                badgeEl.textContent = 'Window Tidak Aktif';
                badgeEl.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700';
            }
        }
    }
    let quickSelectedTemplateId = null;
    let quickLocation = '';
    let quickNotes = '';
    document.querySelector('#zero-quick-start').addEventListener('click', async () => {
        if (!quickSelectedShift) { alert('Tidak ada window aktif untuk Anda'); return; }
        const sel = document.querySelector('#zero-quick-template');
        quickSelectedTemplateId = parseInt(sel.value || '0', 10) || (quickSelectedShift?.default_template_id || null);
        if (!Array.isArray(checklistTemplates) || checklistTemplates.length === 0) {
            await fetchChecklistTemplates();
        }
        if (!Array.isArray(vehiclesList) || vehiclesList.length === 0) {
            await fetchVehicles();
        }
        if (!quickSelectedTemplateId) {
            if (Array.isArray(checklistTemplates) && checklistTemplates.length > 0) {
                quickSelectedTemplateId = checklistTemplates[0].id;
            }
        }
        if (!quickSelectedTemplateId) { alert('Template tidak tersedia'); return; }
        quickLocation = String(document.querySelector('#zero-quick-location').value || '').trim();
        quickNotes = String(document.querySelector('#zero-quick-notes').value || '').trim();
        try {
            quickRunId = null;
            const tpl = checklistTemplates.find(x => x.id == quickSelectedTemplateId);
            const itemsBox = document.querySelector('#quickChecklistItems');
            itemsBox.innerHTML = '';
                itemsBox.className = 'grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4';
            let items = [];
            try { items = JSON.parse(tpl.items_json || '[]') || []; } catch(_) { items = []; }
            for (const it of items) {
                const type = String(it.type || 'BOOLEAN').toUpperCase();
                const label = String(it.label || 'Item');
                const wrap = document.createElement('div');
                wrap.className = 'border border-slate-200 rounded-lg p-4';
                if (type === 'BOOLEAN') {
                    wrap.innerHTML = `
                        <div class="text-sm font-bold text-slate-800">${label}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-3">
                            <label class="text-[12px]"><input type="radio" name="bool_${label}" value="YA"> Ya</label>
                            <label class="text-[12px]"><input type="radio" name="bool_${label}" value="TIDAK"> Tidak</label>
                        </div>
                        <textarea rows="4" data-type="BOOLEAN" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan"></textarea>
                    `;
                } else if (type === 'COUNT_NOTE') {
                    wrap.innerHTML = `
                        <div class="text-sm font-bold text-slate-800">${label}</div>
                        <input type="number" data-type="COUNT_NOTE" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah">
                        <textarea rows="4" data-type="COUNT_NOTE_NOTE" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan"></textarea>
                    `;
                } else if (type === 'PACKAGE') {
                    wrap.innerHTML = `
                        <div class="text-sm font-bold text-slate-800">${label}</div>
                        <input type="number" data-type="PACKAGE_COUNT" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah paket">
                        <input type="text" data-type="PACKAGE_FOR" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Untuk siapa">
                    `;
                } else if (type === 'VEHICLE') {
                    wrap.innerHTML = `
                        <div class="text-sm font-bold text-slate-800">${label}</div>
                        <div class="mt-2 space-y-2" data-type="VEHICLE_GROUP" data-label="${label}"></div>
                        <textarea rows="4" data-type="VEHICLE_NOTE" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan umum"></textarea>
                    `;
                    try {
                        const grp = wrap.querySelector('[data-type="VEHICLE_GROUP"][data-label="'+label+'"]');
                        grp.innerHTML = '';
                        if (Array.isArray(vehiclesList) && vehiclesList.length > 0) {
                            for (const v of vehiclesList) {
                                const row = document.createElement('div');
                                    row.className = 'flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2';
                                row.innerHTML = `
                                    <div class="text-[12px] font-medium">${(v.name || 'Kendaraan')} • ${(v.license_plate || '-')}</div>
                                    <div class="flex items-center gap-3">
                                        <label class="text-[12px]"><input type="radio" name="veh_${label}_${v.id}" value="ADA" checked> Ada</label>
                                        <label class="text-[12px]"><input type="radio" name="veh_${label}_${v.id}" value="KELUAR"> Keluar</label>
                                    </div>
                                `;
                                grp.appendChild(row);
                            }
                        } else {
                            grp.innerHTML = '<div class="text-[12px] text-slate-500">Tidak ada kendaraan terdata</div>';
                        }
                    } catch(_) {}
                } else {
                    wrap.innerHTML = `
                        <div class="text-sm font-bold text-slate-800">${label}</div>
                        <input type="text" data-type="TEXT" data-label="${label}" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Isi">
                    `;
                }
                itemsBox.appendChild(wrap);
            }
                document.querySelector('#quickChecklistInfo').textContent = (quickSelectedShift ? ('Selesai ' + (quickSelectedShift.end_time || '')) : '');
            quickModal.classList.remove('hidden');
        } catch(_) { alert('Gagal mulai checklist'); }
    });
    try {
        document.querySelector('#zero-quick-start').addEventListener('touchstart', async (e) => { e.preventDefault(); e.stopPropagation(); document.querySelector('#zero-quick-start').click(); }, { passive: false });
    } catch(_) {}
    function makeRequestId() {
        try {
            const base = 'secchk';
            const ts = Date.now();
            const rnd = Math.random().toString(36).slice(2);
            const uid = String(window.USER_ID || '');
            return `${base}-${ts}-${uid}-${rnd}`;
        } catch(_) {
            return `secchk-${Date.now()}`;
        }
    }
    async function findLatestRunInWindow(shift) {
        try {
            if (!shift) return null;
            const todayStr = new Date().toISOString().slice(0,10);
            const ym = todayStr.slice(0,7);
            const qsMine = new URLSearchParams({ month: ym });
            if (currentUserId) qsMine.set('user_id', String(currentUserId));
            const rMine = await fetch(getBaseUrl() + 'api/security.php?action=list_checklist_runs_month&' + qsMine.toString());
            const jMine = await rMine.json();
            let rows = jMine.success ? (jMine.data || []) : [];
            const startTime = shift.window_start || '00:00';
            const endTime = shift.window_end || '23:59';
            const startMin = parseTimeToMinutes(startTime);
            const endMin = parseTimeToMinutes(endTime);
            let todayRows = rows.filter(x => {
                const ca = String(x.created_at || '');
                const ca7 = String(x.created_at_plus7 || '');
                return (ca.slice(0,10) === todayStr) || (ca7.slice(0,10) === todayStr);
            });
            let inWindow = todayRows.filter(x => {
                const tRaw = String(x.created_at || '').slice(11,16);
                const tAdj = String(x.created_at_plus7 || '').slice(11,16);
                const t = tAdj || tRaw;
                const m = parseTimeToMinutes(t);
                return m >= startMin && m <= endMin;
            });
            if (inWindow.length === 0) {
                const qsAll = new URLSearchParams({ month: ym });
                const rAll = await fetch(getBaseUrl() + 'api/security.php?action=list_checklist_runs_month&' + qsAll.toString());
                const jAll = await rAll.json();
                rows = jAll.success ? (jAll.data || []) : [];
                todayRows = rows.filter(x => {
                    const ca = String(x.created_at || '');
                    const ca7 = String(x.created_at_plus7 || '');
                    return (ca.slice(0,10) === todayStr) || (ca7.slice(0,10) === todayStr);
                });
                inWindow = todayRows.filter(x => {
                    const tRaw = String(x.created_at || '').slice(11,16);
                    const tAdj = String(x.created_at_plus7 || '').slice(11,16);
                    const t = tAdj || tRaw;
                    const m = parseTimeToMinutes(t);
                    return m >= startMin && m <= endMin;
                });
            }
            if (inWindow.length === 0) return null;
            inWindow.sort((a,b) => String(a.created_at).localeCompare(String(b.created_at)));
            return inWindow[inWindow.length - 1];
        } catch(_) { return null; }
    }
    document.querySelector('#zero-quick-edit-btn').addEventListener('click', async () => {
        if (!quickSelectedShift) return;
        let run = null;
        try {
            const lrid = quickSelectedShift.latest_run_id || null;
            if (lrid) {
                run = {
                    id: lrid,
                    template_id: quickSelectedShift.latest_template_id || quickSelectedShift.default_template_id || null,
                    location: quickSelectedShift.latest_location || '',
                    notes: quickSelectedShift.latest_notes || '',
                    officer_user_id: quickSelectedShift.latest_officer_user_id || null
                };
            }
        } catch(_) {}
        if (!run) {
            run = await findLatestRunInWindow(quickSelectedShift);
        }
        if (!run) { alert('Tidak menemukan laporan untuk diedit'); return; }
        const role = String(window.__CURRENT_USER_ROLE__ || window.USER_ROLE || '').toUpperCase();
        const hasOverride = role === 'ADMIN' || role === 'SUPERADMIN';
        if (!hasOverride && run.officer_user_id && String(run.officer_user_id) !== String(currentUserId)) {
            alert('Sudah dilaporkan oleh petugas lain');
            return;
        }
        quickRunId = run.id || null;
        quickSelectedTemplateId = run.template_id || null;
        try {
            if (!Array.isArray(checklistTemplates) || checklistTemplates.length === 0) {
                await fetchChecklistTemplates();
            }
            if (!Array.isArray(vehiclesList) || vehiclesList.length === 0) {
                await fetchVehicles();
            }
            const tpl = checklistTemplates.find(x => String(x.id) === String(quickSelectedTemplateId));
            if (!tpl) { alert('Template tidak tersedia'); return; }
            try {
                const sel = document.querySelector('#zero-quick-template');
                const nameEl = document.querySelector('#zero-quick-template-name');
                if (sel) sel.value = String(quickSelectedTemplateId);
                const tplName = tpl ? (tpl.name || '') : '';
                const roleNow = String(window.__CURRENT_USER_ROLE__ || window.USER_ROLE || '').toUpperCase();
                const isAdmin = roleNow === 'ADMIN' || roleNow === 'SUPERADMIN';
                if (isAdmin) {
                    if (sel) sel.classList.remove('hidden');
                    if (nameEl) { nameEl.classList.add('hidden'); nameEl.value = ''; }
                } else {
                    if (nameEl) { nameEl.value = tplName; nameEl.classList.remove('hidden'); }
                    if (sel) sel.classList.add('hidden');
                }
            } catch(_) {}
            const itemsBox = document.querySelector('#quickChecklistItems');
            itemsBox.innerHTML = '';
            itemsBox.className = 'grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4';
            let items = [];
            try { items = JSON.parse(tpl.items_json || '[]') || []; } catch(_) { items = []; }
            for (const it of items) {
                const type = String(it.type || 'BOOLEAN').toUpperCase();
                const label = String(it.label || 'Item');
                const wrap = document.createElement('div');
                wrap.className = 'border border-slate-200 rounded-lg p-4';
                if (type === 'BOOLEAN') {
                    wrap.innerHTML = '<div class="text-sm font-bold text-slate-800">'+label+'</div><div class="mt-1 flex flex-wrap items-center gap-3"><label class="text-[12px]"><input type="radio" name="bool_'+label+'" value="YA"> Ya</label><label class="text-[12px]"><input type="radio" name="bool_'+label+'" value="TIDAK"> Tidak</label></div><textarea rows="4" data-type="BOOLEAN" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan"></textarea>';
                } else if (type === 'COUNT_NOTE') {
                    wrap.innerHTML = '<div class="text-sm font-bold text-slate-800">'+label+'</div><input type="number" data-type="COUNT_NOTE" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah"><textarea rows="4" data-type="COUNT_NOTE_NOTE" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan"></textarea>';
                } else if (type === 'PACKAGE') {
                    wrap.innerHTML = '<div class="text-sm font-bold text-slate-800">'+label+'</div><input type="number" data-type="PACKAGE_COUNT" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jumlah paket"><input type="text" data-type="PACKAGE_FOR" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Untuk siapa">';
                } else if (type === 'VEHICLE') {
                    wrap.innerHTML = '<div class="text-sm font-bold text-slate-800">'+label+'</div><div class="mt-2 space-y-2" data-type="VEHICLE_GROUP" data-label="'+label+'"></div><textarea rows="4" data-type="VEHICLE_NOTE" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Keterangan umum"></textarea>';
                    try {
                        const grp = wrap.querySelector('[data-type="VEHICLE_GROUP"][data-label="'+label+'"]');
                        grp.innerHTML = '';
                        if (Array.isArray(vehiclesList) && vehiclesList.length > 0) {
                            for (const v of vehiclesList) {
                                const row = document.createElement('div');
                                row.className = 'flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2';
                                row.innerHTML = '<div class="text-[12px] font-medium">'+(v.name || 'Kendaraan')+' • '+(v.license_plate || '-')+'</div><div class="flex items-center gap-3"><label class="text-[12px]"><input type="radio" name="veh_'+label+'_'+v.id+'" value="ADA" checked> Ada</label><label class="text-[12px]"><input type="radio" name="veh_'+label+'_'+v.id+'" value="KELUAR"> Keluar</label></div>';
                                grp.appendChild(row);
                            }
                        } else {
                            grp.innerHTML = '<div class="text-[12px] text-slate-500">Tidak ada kendaraan terdata</div>';
                        }
                    } catch(_) {}
                } else {
                    wrap.innerHTML = '<div class="text-sm font-bold text-slate-800">'+label+'</div><input type="text" data-type="TEXT" data-label="'+label+'" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Isi">';
                }
                itemsBox.appendChild(wrap);
            }
            document.querySelector('#quickChecklistInfo').textContent = (quickSelectedShift ? ('Selesai ' + (quickSelectedShift.end_time || '')) : '');
            try {
                const locEl = document.querySelector('#zero-quick-location');
                const noteEl = document.querySelector('#zero-quick-notes');
                if (locEl) locEl.value = String(run.location || '');
                if (noteEl) noteEl.value = String(run.notes || '');
                quickLocation = String(run.location || '');
                quickNotes = String(run.notes || '');
            } catch(_) {}
            quickModal.classList.remove('hidden');
            try {
                const q = new URLSearchParams({ run_id: run.id });
                const resp = await fetch(getBaseUrl() + 'api/security.php?action=get_checklist_answers&' + q.toString());
                const js = await resp.json();
                const answers = js.success ? (js.data || []) : [];
                for (const a of answers) {
                    const label = String(a.item_label || '');
                    const type = String(a.item_type || '').toUpperCase();
                    let val = {};
                    try { val = JSON.parse(a.value_json || 'null') || {}; } catch(_) { val = {}; }
                    if (type === 'BOOLEAN') {
                        const ch = String(val.choice || '').toUpperCase();
                        const radios = document.querySelectorAll('input[name="bool_'+label+'"]');
                        radios.forEach(r => { r.checked = (r.value === ch); });
                        const noteEl = document.querySelector('#quickChecklistItems [data-type="BOOLEAN"][data-label="'+label+'"]');
                        if (noteEl) noteEl.value = val.note || '';
                    } else if (type === 'COUNT_NOTE') {
                        const cEl = document.querySelector('#quickChecklistItems [data-type="COUNT_NOTE"][data-label="'+label+'"]');
                        const nEl = document.querySelector('#quickChecklistItems [data-type="COUNT_NOTE_NOTE"][data-label="'+label+'"]');
                        if (cEl) cEl.value = (typeof val.count === 'number' ? val.count : parseInt(val.count||'0',10));
                        if (nEl) nEl.value = val.note || '';
                    } else if (type === 'PACKAGE') {
                        const cEl = document.querySelector('#quickChecklistItems [data-type="PACKAGE_COUNT"][data-label="'+label+'"]');
                        const fEl = document.querySelector('#quickChecklistItems [data-type="PACKAGE_FOR"][data-label="'+label+'"]');
                        if (cEl) cEl.value = (typeof val.count === 'number' ? val.count : parseInt(val.count||'0',10));
                        if (fEl) fEl.value = val.for || '';
                    } else if (type === 'VEHICLE') {
                        const present = Array.isArray(val.present_ids) ? val.present_ids : [];
                        const outs = Array.isArray(val.out_ids) ? val.out_ids : [];
                        if (Array.isArray(vehiclesList)) {
                            for (const v of vehiclesList) {
                                const name = 'veh_'+label+'_'+v.id;
                                const radios = document.querySelectorAll('input[name="'+name+'"]');
                                const isOut = outs.includes(v.id);
                                radios.forEach(r => { r.checked = (isOut ? r.value === 'KELUAR' : r.value === 'ADA'); });
                            }
                        }
                        const noteEl = document.querySelector('#quickChecklistItems [data-type="VEHICLE_NOTE"][data-label="'+label+'"]');
                        if (noteEl) noteEl.value = val.note || '';
                    } else {
                        const tEl = document.querySelector('#quickChecklistItems [data-type="TEXT"][data-label="'+label+'"]');
                        const textVal = (typeof val.text === 'string') ? val.text : (typeof val === 'string' ? val : '');
                        if (tEl) tEl.value = textVal || '';
                        // Fallback: if template expects COUNT_NOTE or PACKAGE but answer stored as TEXT (legacy bug)
                        const cEl = document.querySelector('#quickChecklistItems [data-type=\"COUNT_NOTE\"][data-label=\"'+label+'\"]');
                        const nEl = document.querySelector('#quickChecklistItems [data-type=\"COUNT_NOTE_NOTE\"][data-label=\"'+label+'\"]');
                        if (textVal && !tEl && (cEl || nEl)) {
                            if (nEl) nEl.value = textVal;
                        }
                        const pCnt = document.querySelector('#quickChecklistItems [data-type=\"PACKAGE_COUNT\"][data-label=\"'+label+'\"]');
                        const pFor = document.querySelector('#quickChecklistItems [data-type=\"PACKAGE_FOR\"][data-label=\"'+label+'\"]');
                        if (textVal && !tEl && (pCnt || pFor)) {
                            if (pFor) pFor.value = textVal;
                        }
                    }
                }
            } catch(_) {}
        } catch(_) { alert('Terjadi kesalahan saat memuat laporan'); }
    });
    document.querySelector('#quickChecklistSave').addEventListener('click', async () => {
        const itemsEl = document.querySelectorAll('#quickChecklistItems [data-label]');
        const answersMap = {};
        itemsEl.forEach(el => {
            const label = el.getAttribute('data-label');
            const type = el.getAttribute('data-type');
            if (type === 'BOOLEAN') {
                const radios = document.querySelectorAll('input[name="bool_'+label+'"]');
                let choice = '';
                radios.forEach(r => { if (r.checked) choice = r.value; });
                answersMap[label] = { label, type: 'BOOLEAN', value: { choice, note: el.value || '' } };
            } else if (type === 'COUNT_NOTE') {
                const countEl = document.querySelector('#quickChecklistItems [data-type="COUNT_NOTE"][data-label="'+label+'"]');
                const noteEl = document.querySelector('#quickChecklistItems [data-type="COUNT_NOTE_NOTE"][data-label="'+label+'"]');
                answersMap[label] = { label, type: 'COUNT_NOTE', value: { count: parseInt(countEl.value||'0',10), note: noteEl.value||'' } };
            } else if (type === 'COUNT_NOTE_NOTE') {
                const cur = answersMap[label];
                if (cur && cur.type === 'COUNT_NOTE') {
                    cur.value.note = el.value || '';
                    answersMap[label] = cur;
                } else {
                    answersMap[label] = { label, type: 'COUNT_NOTE', value: { count: 0, note: el.value || '' } };
                }
            } else if (type === 'PACKAGE_COUNT') {
                const countEl = document.querySelector('#quickChecklistItems [data-type="PACKAGE_COUNT"][data-label="'+label+'"]');
                const forEl = document.querySelector('#quickChecklistItems [data-type="PACKAGE_FOR"][data-label="'+label+'"]');
                answersMap[label] = { label, type: 'PACKAGE', value: { count: parseInt(countEl.value||'0',10), for: forEl.value||'' } };
            } else if (type === 'PACKAGE_FOR') {
                const cur = answersMap[label];
                if (cur && cur.type === 'PACKAGE') {
                    cur.value.for = el.value || '';
                    answersMap[label] = cur;
                } else {
                    answersMap[label] = { label, type: 'PACKAGE', value: { count: 0, for: el.value || '' } };
                }
            } else if (type === 'VEHICLE_GROUP') {
                const present_ids = []; const out_ids = [];
                try {
                    if (Array.isArray(vehiclesList)) {
                        for (const v of vehiclesList) {
                            const name = 'veh_'+label+'_'+v.id;
                            const radios = document.querySelectorAll('input[name="'+name+'"]');
                            let status = 'ADA';
                            radios.forEach(r => { if (r.checked) status = r.value; });
                            if (status === 'KELUAR') out_ids.push(v.id);
                            else present_ids.push(v.id);
                        }
                    }
                } catch(_) {}
                let note = '';
                try {
                    const noteEl = document.querySelector('#quickChecklistItems [data-type="VEHICLE_NOTE"][data-label="'+label+'"]');
                    note = noteEl ? (noteEl.value || '') : '';
                } catch(_) {}
                answersMap[label] = { label, type: 'VEHICLE', value: { present_ids, out_ids, note } };
            } else if (type === 'VEHICLE_NOTE') {
                const cur = answersMap[label];
                if (cur && cur.type === 'VEHICLE') {
                    cur.value.note = el.value || '';
                    answersMap[label] = cur;
                }
            } else {
                const textVal = el.value || '';
                if (textVal && answersMap[label] && answersMap[label].type === 'COUNT_NOTE') {
                    answersMap[label].value.note = textVal;
                } else if (textVal && answersMap[label] && answersMap[label].type === 'PACKAGE') {
                    answersMap[label].value.for = textVal;
                } else {
                    answersMap[label] = { label, type: 'TEXT', value: { text: textVal } };
                }
            }
        });
        const answers = Object.values(answersMap);
        try {
            const r = await fetch(getBaseUrl() + 'api/security.php?action=save_checklist_result', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ run_id: quickRunId || 0, template_id: quickSelectedTemplateId, location: quickLocation, notes: quickNotes, answers, request_id: makeRequestId() })
            });
            const j = await r.json();
            if (j.success) {
                alert('Checklist disimpan');
                try {
                    if (quickSelectedShift && quickSelectedShift.shift_id) {
                        const badge = document.querySelector('#zero-daily-list span[data-shift-id="'+quickSelectedShift.shift_id+'"]');
                        if (badge) {
                            badge.textContent = 'LAPOR';
                            badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700';
                        }
                    }
                } catch(_) {}
                quickModal.classList.add('hidden');
                quickRunId = null;
                quickSelectedTemplateId = null;
                quickLocation = '';
                quickNotes = '';
                quickSelectedShift = null;
                fetchZeroOverview();
            } else {
                alert('Gagal menyimpan');
            }
        } catch(_) { alert('Gagal menyimpan'); }
    });
    try {
        const btn = document.querySelector('#quickChecklistSave');
        if (btn) btn.addEventListener('touchstart', (e) => { e.preventDefault(); e.stopPropagation(); btn.click(); }, { passive: false });
    } catch(_) {}
    let zeroInitialized = false;
    async function fetchZeroOverview() {
        if (!zeroInitialized) {
            try { await fetchMyProfile(); } catch(_) {}
            try { await fetchChecklistTemplates(); } catch(_) {}
            try { await fetchVehicles(); } catch(_) {}
            zeroInitialized = true;
        }
        try {
            const r = await fetch(getBaseUrl() + 'api/security.php?action=zero_report_overview');
            const j = await r.json();
            const d = j.success ? (j.data || {}) : {};
            const daily = d.daily || {};
            serverNowHm = String(daily.server_now || '').trim() || null;
            const weekly = d.weekly || {};
            centerRoot.querySelector('#zero-period').textContent = 'Hari ini: ' + (daily.today || '');
            const dailySummary = `${daily.reported || 0} lapor • ${daily.not_reported || 0} belum lapor (total ${daily.total_shifts || 0})`;
            centerRoot.querySelector('#zero-daily-summary').textContent = dailySummary;
            const weeklySummary = (weekly.compliance ? 'Terpenuhi' : 'Belum') + ` • Rapat: ${weekly.meetings_done || 0}/` + (weekly.required_meetings || 1) + ` (${weekly.period_start || ''}–${weekly.period_end || ''})`;
            centerRoot.querySelector('#zero-weekly-summary').textContent = weeklySummary;
            const monthly = d.monthly || {};
            const monthlySummary = `Checklist: ${monthly.checklists_done || 0}/${monthly.required_checklists || 0} • Compliance ${monthly.compliance_rate_percent || 0}%` + (monthly.month ? ` (${monthly.month})` : '') + (monthly.window_start ? ` • Window Bulanan ${monthly.window_start}–${monthly.window_end}` : '');
            centerRoot.querySelector('#zero-monthly-summary').textContent = monthlySummary;
            const list = centerRoot.querySelector('#zero-daily-list');
            const empty = centerRoot.querySelector('#zero-daily-empty');
            list.innerHTML = '';
            const arr = Array.isArray(daily.shifts) ? daily.shifts : [];
            const role = String(window.__CURRENT_USER_ROLE__ || window.USER_ROLE || '').toUpperCase();
            const isAdmin = role === 'ADMIN' || role === 'SUPERADMIN';
            const filtered = arr.filter(s => {
                const hasOverride = isAdmin;
                const userMatch = hasOverride || !s.user_id || (currentUserId && String(s.user_id) === String(currentUserId)) || (role === 'SECURITY' && namesEqual(s.employee_name, window.USER_FULL_NAME));
                return isAdmin ? true : userMatch;
            });
            if (filtered.length > 0) {
                for (const s of filtered) {
                    const el = document.createElement('div');
                    const ok = (s.status === 'REPORTED');
                    el.className = 'px-4 py-3 bg-white hover:bg-slate-50';
                    el.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">${s.employee_name || '-'}</div>
                                <div class="text-[10px] text-slate-500">Window ${s.window_start || ''}–${s.window_end || ''} • Selesai ${s.end_time || ''}</div>
                            </div>
                            <div class="text-right">
                                <span data-shift-id="${s.shift_id}" class="px-2 py-0.5 rounded text-[10px] font-bold ${ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}">
                                    ${ok ? 'LAPOR' : 'BELUM LAPOR'}
                                </span>
                            </div>
                        </div>
                    `;
                    const ws = parseTimeToMinutes(s.window_start); const we = parseTimeToMinutes(s.window_end);
                    const now = nowMinutes();
                    const role = String(window.__CURRENT_USER_ROLE__ || window.USER_ROLE || '').toUpperCase();
                    const hasOverride = role === 'ADMIN' || role === 'SUPERADMIN';
                    const userMatch = hasOverride || !s.user_id || (currentUserId && String(s.user_id) === String(currentUserId)) || (role === 'SECURITY' && namesEqual(s.employee_name, window.USER_FULL_NAME));
                    if (!ok && userMatch && now >= ws && now <= we) {
                        const btn = document.createElement('button');
                        btn.className = 'mt-2 text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700';
                        btn.innerHTML = '<i class="fas fa-clipboard-check"></i> Mulai Checklist';
                        btn.addEventListener('click', () => {
                            quickSelectedShift = s;
                            const statusEl = document.querySelector('#zero-quick-status');
                            const formEl = document.querySelector('#zero-quick-form');
                            const winEl = document.querySelector('#zero-quick-window');
                            winEl.textContent = 'Window ' + (s.window_start || '') + '–' + (s.window_end || '') + ' • Selesai ' + (s.end_time || '');
                            formEl.classList.remove('hidden');
                            statusEl.textContent = '';
                        });
                        try {
                            btn.addEventListener('touchstart', (e) => { e.preventDefault(); e.stopPropagation(); btn.click(); }, { passive: false });
                        } catch(_) {}
                        el.appendChild(btn);
                    }
                    list.appendChild(el);
                }
                empty.classList.add('hidden');
            } else {
                empty.classList.remove('hidden');
            }
            updateQuickPanel(filtered);
        } catch(_) {}
    }
    (async function initZeroHelpers() {
        // Trigger auto-send check
        try { fetch(getBaseUrl() + 'api/security.php?action=auto_send_security', {method: 'POST'}).catch(()=>{}); } catch(_) {}
        
        try { await fetchMyProfile(); } catch(_) {}
        try { await fetchChecklistTemplates(); } catch(_) {}
        try { await fetchVehicles(); } catch(_) {}
        try { await fetchZeroOverview(); } catch(_) {}
    })();
});
</script>
