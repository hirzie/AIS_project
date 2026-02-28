<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../includes/header.php';
require_once '../../config/database.php';
// Resolve current user's full name
$__displayName = $_SESSION['username'] ?? 'Pengguna';
if (!empty($_SESSION['person_id'])) {
    $st = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
    $st->execute([$_SESSION['person_id']]);
    $nm = $st->fetchColumn();
    if ($nm) { $__displayName = $nm; }
}
?>
<style>
    [v-cloak] { display: none !important; }
</style>
<script>
    window.SKIP_GLOBAL_APP = true;
    window.USER_FULL_NAME = <?php echo json_encode($__displayName); ?>;
    window.USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div id="app" v-cloak class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Managerial View</h1>
                <span class="text-xs text-slate-500 font-medium">Executive Dashboard</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
            <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </button>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 h-full flex flex-col justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Menu Manajerial</p>
                        <div class="space-y-2">
                            <a :href="baseUrl + 'modules/executive/managerial_approval.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-stamp mr-2 text-indigo-600"></i> Approval Center</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                            <a :href="baseUrl + 'modules/executive/activity_log.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-clipboard-list mr-2 text-slate-600"></i> Aktivitas Divisi</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                            <a :href="baseUrl + 'modules/executive/finance_trend.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-chart-line mr-2 text-emerald-600"></i> Tren Keuangan</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                            <a :href="baseUrl + 'modules/executive/facility_summary.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-building mr-2 text-slate-500"></i> Summary Fasilitas</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                            <a :href="baseUrl + 'modules/executive/meetings.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-handshake mr-2 text-indigo-600"></i> Menu Rapat</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                            <a :href="baseUrl + 'modules/executive/documents.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-file mr-2 text-slate-600"></i> Menu Dokumen</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                            <a :href="baseUrl + 'modules/executive/announcements.php'" class="flex items-center justify-between px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-sm font-bold text-slate-700">
                                <span><i class="fas fa-bullhorn mr-2 text-orange-600"></i> Kelola Pengumuman</span>
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 card-hover h-full flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity transform group-hover:scale-110">
                        <i class="fas fa-user-graduate text-6xl text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Siswa Aktif</p>
                        <h3 class="text-3xl font-bold text-slate-800">{{ stats.total_students }}</h3>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded">
                        <i class="fas fa-arrow-up mr-1"></i> 5% dari tahun lalu
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 card-hover h-full flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity transform group-hover:scale-110">
                        <i class="fas fa-wallet text-6xl text-emerald-600"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Surplus/Defisit (Bln Ini)</p>
                        <h3 class="text-3xl font-bold text-slate-800 font-mono tracking-tight" :class="monthlyBalance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            {{ formatCurrency(monthlyBalance) }}
                        </h3>
                    </div>
                    <div class="mt-4 text-xs text-slate-500">
                        <span class="font-bold text-emerald-600">In: {{ formatShort(stats.finance_income) }}</span> • 
                        <span class="font-bold text-red-600">Out: {{ formatShort(stats.finance_expense) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 card-hover h-full flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity transform group-hover:scale-110">
                        <i class="fas fa-hard-hat text-6xl text-amber-600"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Proyek Pembangunan</p>
                        <h3 class="text-3xl font-bold text-slate-800">2 <span class="text-lg text-slate-400 font-normal">Aktif</span></h3>
                    </div>
                    <div class="mt-4 w-full">
                        <div class="flex justify-between text-[10px] font-bold text-slate-500 mb-1">
                            <span>Gedung Asrama C</span>
                            <span>75%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full" style="width: 75%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-span-12 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" id="activity-card">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-stream text-blue-600"></i> Aktivitas Terkini
                        </h3>
                        <div class="flex items-center gap-2">
                            <button @click="fetchData(); fetchRecentExpenses(); fetchApprovedApprovals()" class="text-xs font-bold text-blue-600 hover:text-blue-700"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="col-span-12 lg:col-span-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-bold text-slate-800">Pengingat & Fasilitas</h4>
                                </div>
                                <div class="flex gap-2 mb-3">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-700">Servis segera: {{ warningCounts.service }}</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red-100 text-red-700">Pajak lewat: {{ warningCounts.tax }}</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">Approval terlambat: {{ warningCounts.approval }}</span>
                                </div>
                                <div v-if="warningItems.length > 0" class="space-y-3">
                                    <div v-for="item in warningItems" :key="item.title + item.date" class="flex gap-4 items-start">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="getItemClass(item)">
                                            <i :class="getItemIcon(item)"></i>
                                        </div>
                                        <div class="flex-1 bg-white border border-slate-200 p-4 rounded-xl shadow-sm">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-bold text-slate-800 text-sm">{{ item.title }}</h4>
                                                    <p class="text-[11px] text-slate-500">{{ item.subtitle }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-[10px] text-slate-400">{{ formatDate(item.date) }}</p>
                                                    <span v-if="item.amount" class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">Rp {{ formatShort(item.amount) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 py-6 text-sm">Belum ada pengingat.</div>
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-bold text-slate-800">Log Aktivitas</h4>
                                    <div class="flex items-center gap-2">
                                        <button @click="selectedModule='ALL'" :class="selectedModule==='ALL' ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:text-slate-800'" class="px-2 py-0.5 rounded text-[10px] font-bold">ALL</button>
                                        <button @click="selectedModule='EXECUTIVE'" :class="selectedModule==='EXECUTIVE' ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:text-slate-800'" class="px-2 py-0.5 rounded text-[10px] font-bold">EXECUTIVE</button>
                                        <button @click="selectedModule='FINANCE'" :class="selectedModule==='FINANCE' ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:text-slate-800'" class="px-2 py-0.5 rounded text-[10px] font-bold">FINANCE</button>
                                        <button @click="selectedModule='ACADEMIC'" :class="selectedModule==='ACADEMIC' ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:text-slate-800'" class="px-2 py-0.5 rounded text-[10px] font-bold">ACADEMIC</button>
                                        <button @click="selectedModule='FOUNDATION'" :class="selectedModule==='FOUNDATION' ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:text-slate-800'" class="px-2 py-0.5 rounded text-[10px] font-bold">FOUNDATION</button>
                                        <a :href="baseUrl + 'modules/executive/activity_log.php' + (selectedModule==='ALL' ? '' : ('?module=' + selectedModule))" class="ml-2 text-[10px] font-bold text-indigo-600 hover:text-indigo-700">Lihat semua</a>
                                    </div>
                                </div>
                                <div>
                                    <div v-if="filteredActivityLogs.length > 0">
                                        <div v-for="l in filteredActivityLogs.slice(0,8)" :key="(l.created_at || '') + (l.title || '')" class="text-[10px] border-b border-slate-200 py-1 flex justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800 uppercase">{{ headerPrefix(l) }}</span>
                                                </div>
                                                <div class="truncate text-slate-800 mt-0.5">{{ l.title || l.description || '-' }}</div>
                                            </div>
                                            <div class="text-right shrink-0 w-40">
                                                <div class="text-slate-600 font-bold">{{ l.people_name || l.username || '-' }}</div>
                                                <div class="text-slate-400">{{ formatDateTime(l.created_at) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-[12px] text-slate-400">Belum ada log aktivitas.</div>
                                </div>
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <div class="flex flex-col h-full">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-bold text-slate-800">Diskusi Manajerial</h4>
                                    </div>
                                    <div id="discussion-list" class="flex-1 max-h-[420px] overflow-y-auto space-y-2">
                                        <div v-for="msg in chatMessages" :key="msg.id" class="flex" :class="isMine(msg) ? 'justify-end' : 'justify-start'">
                                            <div class="max-w-[75%]">
                                                <div class="text-[11px] text-slate-500 mb-0.5" :class="isMine(msg) ? 'text-right' : 'text-left'">
                                                    {{ msg.author || userFullName }} • {{ formatDate(msg.time) }}
                                                </div>
                                                <div class="text-sm leading-relaxed px-3 py-2 rounded-xl"
                                                     :class="isMine(msg) ? 'bg-indigo-50 text-slate-800' : 'bg-slate-50 text-slate-800'">
                                                    {{ msg.text }}
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="chatMessages.length === 0" class="py-6 text-center text-slate-400 text-sm">Belum ada diskusi.</div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="flex gap-2">
                                            <input v-model="chatInput" @keyup.enter="sendChat" placeholder="Tulis pesan..." class="flex-1 border border-slate-300 rounded px-3 py-2 text-sm">
                                            <button @click="sendChat" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm font-bold">Kirim</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sections moved to standalone pages have been removed from dashboard -->
            </div>
            
        </div>
    </main>
    <div v-if="showMeetingModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-800">Buat Rapat</h3>
                <button @click="showMeetingModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
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
                        <label class="block text-xs font-bold text-slate-500 mb-1">Divisi</label>
                        <select v-model="meetingForm.module_tag" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            <option value="EXECUTIVE">Executive</option>
                            <option value="BOARDING">Asrama</option>
                            <option value="FINANCE">Keuangan</option>
                            <option value="HR">Kepegawaian</option>
                            <option value="ACADEMIC">Akademik</option>
                            <option value="FOUNDATION">Yayasan</option>
                        </select>
                    </div>
                </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Rapat</label>
                    <input v-model="meetingForm.meeting_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="M-YYYYMMDD-XXXX">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Divisi terkait</label>
                    <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                        <div class="flex flex-wrap gap-1 mb-2">
                            <span v-for="mod in (meetingForm.modules_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="mod" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                {{ mod }}
                                <button @click="removeModule(mod)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                            </span>
                        </div>
                        <input v-model="moduleQuery" @input="updateModuleSuggestions" placeholder="Cari divisi (misal: board → BOARDING)" class="w-full outline-none">
                    </div>
                    <div v-if="moduleSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                        <div v-for="s in moduleSuggestions" :key="s" @click="addModule(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                    </div>
                </div>
            </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tags</label>
                    <input v-model="meetingForm.tags" placeholder="contoh: evaluasi, anggaran" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Peserta</label>
                    <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                        <div class="flex flex-wrap gap-1 mb-2">
                            <span v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold flex items-center gap-1">
                                {{ att }}
                                <button @click="removeAttendee(att)" class="text-emerald-700 hover:text-emerald-900"><i class="fas fa-times text-[10px]"></i></button>
                            </span>
                        </div>
                        <input v-model="attendeeQuery" @input="updateAttendeeSuggestions" placeholder="Cari nama (misal: fahm → Fahmi Hirzi)" class="w-full outline-none">
                    </div>
                    <div v-if="attendeeSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm max-h-40 overflow-y-auto">
                        <div v-for="s in attendeeSuggestions" :key="s.id || s" @click="addAttendee(s.name || s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                            {{ s.name || s }}
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Notulensi</label>
                    <textarea v-model="meetingForm.notes" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Keputusan</label>
                    <textarea v-model="meetingForm.decisions" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showMeetingModal=false" class="px-4 py-2 text-sm rounded border border-slate-300 text-slate-600">Batal</button>
                <button @click="saveMeeting" :disabled="savingMeeting" class="px-4 py-2 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                    <i class="fas fa-save mr-1" :class="{'fa-spin': savingMeeting}"></i> Simpan
                </button>
            </div>
        </div>
    </div>
    <div v-if="showDocModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-800">Tambah Dokumen</h3>
                <button @click="showDocModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Meeting ID</label>
                        <input v-model="docForm.meeting_id" disabled class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Divisi</label>
                        <input v-model="docForm.module_tag" disabled class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-slate-100">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Judul Dokumen</label>
                    <input v-model="docForm.doc_title" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                </div>
                <div v-if="docForm.doc_type === 'FILE'">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Pilih File (PDF/DOC/DOCX)</label>
                    <input type="file" @change="e => docForm.doc_file = e.target.files[0]" accept=".pdf,.doc,.docx" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">URL Dokumen</label>
                    <input v-model="docForm.doc_url" placeholder="https://..." class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tipe</label>
                    <select v-model="docForm.doc_type" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                        <option value="FILE">File</option>
                        <option value="LINK">Tautan</option>
                        <option value="IMAGE">Gambar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tags</label>
                    <input v-model="docForm.tags" placeholder="SOP, Asrama" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                </div>
                
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showDocModal=false" class="px-4 py-2 text-sm rounded border border-slate-300 text-slate-600">Batal</button>
                <button @click="saveDocument" :disabled="savingDoc" class="px-4 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                    <i class="fas fa-save mr-1" :class="{'fa-spin': savingDoc}"></i> Simpan
                </button>
            </div>
        </div>
    </div>
    <div v-if="showMeetingDetail" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-800">Detail Rapat</h3>
                <button @click="showMeetingDetail=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div v-if="meetingDetail" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[11px] text-slate-500 font-bold">Judul</div>
                        <div class="text-sm font-bold text-slate-800">{{ meetingDetail.title }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-slate-500 font-bold">Tanggal</div>
                        <div class="text-[12px] text-slate-600">{{ formatDate(meetingDetail.meeting_date) }}</div>
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
                    <div class="text-[11px] text-slate-500 font-bold">Keputusan</div>
                    <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-100 rounded p-3">{{ meetingDetail.decisions || '-' }}</div>
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <a :href="baseUrl + 'api/meetings.php?action=list_documents&meeting_id=' + (meetingDetail.id || 0)" target="_blank" class="px-3 py-1 text-[12px] font-bold bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Buka Dokumen
                    </a>
                    <button @click="showMeetingDetail=false" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">
                        Tutup
                    </button>
                </div>
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
            currentTime: '',
            currentDate: '',
            userFullName: window.USER_FULL_NAME || 'Pengguna',
            userId: window.USER_ID || null,
            stats: {
                total_students: 0,
                total_staff: 0,
                total_assets: 0,
                finance_income: 0,
                finance_expense: 0,
                fleet: []
            },
            vehicles: [],
            chartData: null,
            recentExpenses: [],
            meetings: [],
            documents: [],
            activityLogs: [],
            selectedModule: 'ALL',
            showMeetingModal: false,
            meetingForm: {
                meeting_number: '',
                title: '',
                meeting_date: new Date().toISOString().slice(0,10),
                module_tag: 'BOARDING',
                modules_text: '',
                tags: '',
                attendees_text: '',
                notes: '',
                decisions: ''
            },
            staffDirectory: [],
            staffLoaded: false,
            attendeeQuery: '',
            attendeeSuggestions: [],
            validModules: ['EXECUTIVE','BOARDING','FINANCE','HR','ACADEMIC','FOUNDATION'],
            moduleQuery: '',
            moduleSuggestions: [],
            savingMeeting: false,
            showDocModal: false,
            docForm: {
                meeting_id: null,
                module_tag: '',
                doc_title: '',
                doc_url: '',
                doc_type: 'FILE',
                tags: '',
                doc_file: null
            },
            savingDoc: false,
            showMeetingDetail: false,
            meetingDetail: null,
            approvedApprovals: [],
            timelineFilter: 'ALL',
            chatMessages: [],
            chatInput: '',
        }
    },
    computed: {
        monthlyBalance() {
            return Number(this.stats.finance_income) - Number(this.stats.finance_expense);
        },
        timelineItems() {
            const warnings = [];
            const now = new Date();
            (this.vehicles || []).forEach(v => {
                const ds = this.daysUntil(v.next_service);
                if (ds !== null && ds <= 14) {
                    warnings.push({
                        type: 'WARNING_SERVICE',
                        date: now.toISOString(),
                        title: 'Service ' + (v.name || ''),
                        subtitle: (ds <= 0 ? ('Lewat ' + Math.abs(ds) + ' hari') : (ds + ' hari lagi')),
                        amount: 0
                    });
                }
                const overdue = this.daysOverdue(v.tax_expiry_date);
                if (overdue > 0) {
                    warnings.push({
                        type: 'WARNING_TAX',
                        date: now.toISOString(),
                        title: 'Pajak ' + (v.name || ''),
                        subtitle: 'Lewat ' + overdue + ' hari',
                        amount: 0
                    });
                }
            });
            (this.stats.approvals || []).forEach(a => {
                const created = new Date(a.created_at);
                const diffMs = now.getTime() - created.getTime();
                const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                if (days >= 3) {
                    warnings.push({
                        type: 'WARNING_APPROVAL',
                        date: now.toISOString(),
                        title: (a.title || (a.module + ' #' + a.reference_no)),
                        subtitle: 'Pending ' + days + ' hari',
                        amount: Number(a.amount) || 0
                    });
                }
            });
            const approvalsPending = (this.stats.approvals || []).map(a => ({
                type: 'APPROVAL',
                date: a.created_at,
                title: a.title || (a.module + ' #' + a.reference_no),
                subtitle: 'Pending oleh: ' + (a.requester || '-'),
                amount: Number(a.amount) || 0
            }));
            const approvalsApproved = (this.approvedApprovals || []).map(a => ({
                type: 'APPROVAL',
                date: a.approved_at || a.created_at,
                title: (a.title || (a.module + ' #' + a.reference_no)) + ' (Disetujui)',
                subtitle: 'Disetujui oleh: ' + (a.approved_by || '-'),
                amount: Number(a.amount) || 0
            }));
            const meetings = (this.meetings || []).map(m => ({
                type: 'MEETING',
                date: m.meeting_date,
                title: m.title || 'Rapat',
                subtitle: (m.module_tag || '-') + (m.meeting_number ? (' • ' + m.meeting_number) : ''),
                amount: 0
            }));
            const expenses = (this.recentExpenses || []).map(e => ({
                type: 'EXPENSE',
                date: e.trans_date,
                title: e.description || 'Pengeluaran',
                subtitle: (e.category_name || 'Biaya') + (e.trans_number ? ' • ' + e.trans_number : ''),
                amount: Number(e.amount) || 0
            }));
            const activities = [...approvalsPending, ...approvalsApproved, ...meetings, ...expenses]
                .sort((a,b) => new Date(b.date) - new Date(a.date))
                .slice(0, 20);
            const warnSorted = warnings.sort((a,b) => a.type.localeCompare(b.type));
            const combined = [...warnSorted, ...activities].slice(0, 20);
            return combined;
        },
        timelineItemsFiltered() {
            if (this.timelineFilter === 'ALL') return this.timelineItems;
            if (this.timelineFilter === 'WARNING') {
                return this.timelineItems.filter(i => String(i.type).startsWith('WARNING'));
            }
            return this.timelineItems.filter(i => i.type === this.timelineFilter);
        },
        warningItems() {
            return this.timelineItems.filter(i => String(i.type).startsWith('WARNING'));
        },
        warningCounts() {
            const c = { service: 0, tax: 0, approval: 0 };
            this.warningItems.forEach(i => {
                if (i.type === 'WARNING_SERVICE') c.service++;
                else if (i.type === 'WARNING_TAX') c.tax++;
                else if (i.type === 'WARNING_APPROVAL') c.approval++;
            });
            return c;
        },
        otherLogs() {
            const finance = (this.recentExpenses || []).map(e => ({
                key: 'FIN-' + (e.id || e.trans_number || e.description),
                title: e.description || 'Transaksi Keuangan',
                subtitle: (e.category_name || 'Biaya') + (e.trans_number ? ' • ' + e.trans_number : ''),
                amount: Number(e.amount) || 0
            }));
            return [...finance].slice(0, 20);
        },
        filteredActivityLogs() {
            const logs = Array.isArray(this.activityLogs) ? this.activityLogs : [];
            if (this.selectedModule === 'ALL') return logs;
            const mod = String(this.selectedModule || '').toUpperCase();
            return logs.filter(l => String(l.module || '').toUpperCase() === mod);
        }
    },
    methods: {
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        async fetchApprovedApprovals() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/approval.php?action=get_list&status=APPROVED');
                const data = await res.json();
                if (data.success) this.approvedApprovals = data.data || [];
                else this.approvedApprovals = [];
            } catch (e) {
                this.approvedApprovals = [];
            }
        },
        loadChat() {
            try {
                const raw = localStorage.getItem('managerial_chat') || '[]';
                const arr = JSON.parse(raw);
                this.chatMessages = Array.isArray(arr) ? arr : [];
            } catch (_) {
                this.chatMessages = [];
            }
        },
        saveChat() {
            try {
                localStorage.setItem('managerial_chat', JSON.stringify(this.chatMessages));
            } catch (_) {}
        },
        sendChat() {
            const text = (this.chatInput || '').trim();
            if (!text) return;
            const msg = { id: Date.now(), author: this.userFullName, text, time: new Date().toISOString() };
            this.chatMessages.push(msg);
            this.chatInput = '';
            this.saveChat();
        },
        getItemClass(item) {
            if (item.type === 'APPROVAL') return 'bg-indigo-100 text-indigo-600';
            if (item.type === 'EXPENSE') return 'bg-amber-100 text-amber-600';
            if (item.type === 'MEETING') return 'bg-blue-100 text-blue-600';
            return 'bg-red-100 text-red-600';
        },
        getItemIcon(item) {
            if (item.type === 'APPROVAL') return 'fas fa-clipboard-check';
            if (item.type === 'EXPENSE') return 'fas fa-file-invoice-dollar';
            if (item.type === 'MEETING') return 'fas fa-handshake';
            return 'fas fa-exclamation-triangle';
        },
            async ensureStaffLoaded() {
                if (this.staffLoaded) return;
                try {
                    this.normalizeBaseUrl();
                    const res = await fetch(this.baseUrl + 'api/get_all_staff.php');
                    const data = await res.json();
                    this.staffDirectory = Array.isArray(data) ? data : [];
                    this.staffLoaded = true;
                } catch (e) {
                    this.staffDirectory = [];
                    this.staffLoaded = false;
                }
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
                if (!cur.map(s=>s.toLowerCase()).includes(String(name).toLowerCase())) {
                    cur.push(name);
                }
                this.meetingForm.attendees_text = cur.join(', ');
                this.attendeeQuery = '';
                this.attendeeSuggestions = [];
            },
            removeAttendee(name) {
                const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
                this.meetingForm.attendees_text = cur.filter(s => s.toLowerCase() !== String(name).toLowerCase()).join(', ');
            },
            updateModuleSuggestions() {
                const q = (this.moduleQuery || '').trim().toLowerCase();
                if (q.length < 1) { this.moduleSuggestions = []; return; }
                const selected = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase());
                this.moduleSuggestions = this.validModules
                    .filter(m => m.toLowerCase().includes(q))
                    .filter(m => !selected.includes(m))
                    .slice(0, 8);
            },
            addModule(tag) {
                const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
                if (!cur.includes(tag)) cur.push(tag);
                this.meetingForm.modules_text = cur.join(', ');
                this.moduleQuery = '';
                this.moduleSuggestions = [];
            },
            removeModule(tag) {
                const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
                this.meetingForm.modules_text = cur.filter(s => s !== tag).join(', ');
            },
        updateTime() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            this.currentDate = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        },
        async fetchData() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_executive_summary.php');
                const data = await res.json();
                if (data.success) {
                    this.stats = data.data;
                    this.chartData = data.data.chart;
                    this.renderChart();
                }
            } catch (e) {
                console.error('Failed to fetch dashboard data', e);
            }
        },
        async fetchRecentExpenses() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/finance.php?action=get_expenses');
                const data = await res.json();
                if (data && data.length >= 0) {
                    this.recentExpenses = data;
                } else if (data && data.success) {
                    this.recentExpenses = data.data || [];
                }
            } catch (e) {
                console.error('Failed to fetch expenses', e);
            }
        },
        async fetchMeetings() {
            try {
                this.normalizeBaseUrl();
                const url = this.selectedModule === 'ALL' 
                    ? (this.baseUrl + 'api/meetings.php?action=list&limit=12')
                    : (this.baseUrl + 'api/meetings.php?action=list&module=' + this.selectedModule + '&limit=12');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    this.meetings = data.data || [];
                } else {
                    this.meetings = [];
                }
            } catch (e) {
                console.error('Failed to fetch meetings', e);
            }
        },
        openMeeting(m) {
            let attendees = [];
            if (m.attendees) {
                if (typeof m.attendees === 'string') {
                    try { attendees = JSON.parse(m.attendees) || []; } catch (_) { attendees = []; }
                } else if (Array.isArray(m.attendees)) {
                    attendees = m.attendees;
                }
            }
            this.meetingDetail = {
                id: m.id,
                title: m.title,
                meeting_number: m.meeting_number,
                meeting_date: m.meeting_date,
                module_tag: m.module_tag,
                tags: m.tags || '',
                attendees: attendees,
                notes: m.notes || '',
                decisions: m.decisions || ''
            };
            this.showMeetingDetail = true;
        },
        openNewMeeting() {
            this.showMeetingModal = true;
            this.meetingForm = {
                meeting_number: ('M-' + new Date().toISOString().slice(0,10).replaceAll('-','') + '-' + Math.floor(Math.random()*9000+1000)),
                title: '',
                meeting_date: new Date().toISOString().slice(0,10),
                module_tag: this.selectedModule === 'ALL' ? 'BOARDING' : this.selectedModule,
                modules_text: '',
                tags: '',
                attendees_text: '',
                notes: '',
                decisions: ''
            };
            this.attendeeQuery = '';
            this.attendeeSuggestions = [];
            this.moduleQuery = '';
            this.moduleSuggestions = [];
            this.ensureStaffLoaded();
        },
        openStandaloneDoc() {
            this.showDocModal = true;
            this.docForm = {
                meeting_id: null,
                module_tag: (this.selectedModule === 'ALL' ? 'BOARDING' : this.selectedModule),
                doc_title: '',
                doc_url: '',
                doc_type: 'FILE',
                tags: '',
                doc_file: null
            };
        },
        async saveMeeting() {
            if (!this.meetingForm.title) { alert('Judul rapat wajib diisi'); return; }
            this.savingMeeting = true;
            try {
                this.normalizeBaseUrl();
                const payload = {
                    meeting_number: this.meetingForm.meeting_number,
                    title: this.meetingForm.title,
                    meeting_date: this.meetingForm.meeting_date,
                    module_tag: this.meetingForm.module_tag,
                    modules: (this.meetingForm.modules_text || '').split(',').map(s => s.trim().toUpperCase()).filter(Boolean).filter(m => this.validModules.includes(m)),
                    tags: this.meetingForm.tags,
                    attendees: (this.meetingForm.attendees_text || '').split(',').map(s => s.trim()).filter(Boolean),
                    notes: this.meetingForm.notes,
                    decisions: this.meetingForm.decisions
                };
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=save', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.showMeetingModal = false;
                    this.fetchMeetings();
                    this.fetchDocuments();
                    alert('Rapat berhasil disimpan');
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch(e) {
                alert('Terjadi kesalahan sistem');
            } finally {
                this.savingMeeting = false;
            }
        },
        async fetchDocuments() {
            try {
                this.normalizeBaseUrl();
                const url = this.selectedModule === 'ALL'
                    ? (this.baseUrl + 'api/meetings.php?action=list_documents&limit=20')
                    : (this.baseUrl + 'api/meetings.php?action=list_documents&module=' + this.selectedModule + '&limit=20');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) this.documents = data.data || [];
                else this.documents = [];
            } catch(e) { console.error('Failed to fetch documents', e); }
        },
        async fetchActivityLogs() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_activity_logs.php?limit=20');
                const data = await res.json();
                if (data.success) this.activityLogs = data.data || [];
                else this.activityLogs = [];
            } catch (e) {
                this.activityLogs = [];
            }
        },
        openDocModal(m) {
            this.showDocModal = true;
            this.docForm = {
                meeting_id: m.id,
                module_tag: m.module_tag,
                doc_title: '',
                doc_url: '',
                doc_type: 'FILE',
                tags: '',
                doc_file: null
            };
        },
        async saveDocument() {
            if (!this.docForm.doc_title) { alert('Judul dokumen wajib diisi'); return; }
            this.savingDoc = true;
            try {
                this.normalizeBaseUrl();
                if (this.docForm.doc_type === 'FILE' && this.docForm.doc_file) {
                    const fd = new FormData();
                    fd.append('file', this.docForm.doc_file);
                    const upRes = await fetch(this.baseUrl + 'api/meetings.php?action=upload_document', { method: 'POST', body: fd });
                    const upData = await upRes.json();
                    if (!upData.success) { alert('Upload gagal: ' + upData.message); this.savingDoc = false; return; }
                    this.docForm.doc_url = upData.data.url;
                }
                const payload = {
                    meeting_id: this.docForm.meeting_id,
                    module_tag: this.docForm.module_tag,
                    doc_title: this.docForm.doc_title,
                    doc_url: this.docForm.doc_url,
                    doc_type: this.docForm.doc_type,
                    tags: this.docForm.tags
                };
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=add_document', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) { this.showDocModal = false; alert('Dokumen ditambahkan'); }
                else { alert('Gagal: ' + data.message); }
            } catch(e) {
                alert('Terjadi kesalahan sistem');
            } finally { this.savingDoc = false; }
        },
        async fetchVehicles() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/inventory.php?action=get_vehicles');
                const data = await res.json();
                if (data.success) this.vehicles = data.data;
            } catch (e) {
                console.error('Failed to fetch vehicles', e);
            }
        },
        async fetchChatMessages() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/executive_discussions.php?action=list&limit=50');
                const data = await res.json();
                if (data.success) {
                    const rows = data.data || [];
                    this.chatMessages = rows.map(r => ({
                        id: r.id,
                        user_id: r.user_id,
                        text: r.message,
                        author: r.people_name || r.username || this.userFullName,
                        time: r.created_at
                    })).reverse();
                    this.$nextTick(() => { this.scrollChatToBottom(); });
                }
            } catch (e) {}
        },
        async sendChat() {
            const msg = String(this.chatInput || '').trim();
            if (!msg) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/executive_discussions.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    const r = data.data;
                    this.chatMessages.push({
                        id: r.id,
                        user_id: r.user_id,
                        text: r.message,
                        author: r.people_name || r.username || this.userFullName,
                        time: r.created_at
                    });
                    this.chatInput = '';
                    this.$nextTick(() => { this.scrollChatToBottom(); });
                }
            } catch (e) {}
        },
        isMine(msg) { return String(msg.user_id || '') === String(this.userId || ''); },
        scrollChatToBottom() {
            const el = document.getElementById('discussion-list');
            if (el) { el.scrollTop = el.scrollHeight; }
        },
        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
        },
        formatShort(val) {
            if (val >= 1000000000) return (val/1000000000).toFixed(1) + 'M';
            if (val >= 1000000) return (val/1000000).toFixed(1) + 'Jt';
            if (val >= 1000) return (val/1000).toFixed(0) + 'Rb';
            return val;
        },
        formatDate(dateStr) {
            const d = new Date(dateStr);
            const datePart = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            const hasTime = /T|\s\d{2}:\d{2}/.test(String(dateStr));
            const timePart = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return hasTime ? (datePart + ', ' + timePart) : datePart;
        },
        formatDateShort(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },
        formatDateTime(dateStr) {
            const d = new Date(dateStr);
            const datePart = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const timePart = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return datePart + ' ' + timePart;
        },
        headerPrefix(l) {
            const mod = String(l.module || '-').toUpperCase();
            let cat = String(l.category || '').toUpperCase();
            cat = cat.replace(/^FOUNDATION_/, '').replace(/^EXECUTIVE_/, '').replace(/^ACADEMIC_/, '');
            cat = cat.replace(/_/g, ' ').trim();
            return cat ? (mod + ' - ' + cat) : mod;
        },
        prefixForLog(l) {
            const cat = String(l.category || '').toUpperCase();
            const mod = String(l.module || '').toUpperCase();
            const act = String(l.action || '').toUpperCase();
            if (cat === 'APPROVAL') return 'Approval';
            if (mod === 'FINANCE') {
                const t = String(l.title || '').toUpperCase();
                if (act === 'CAPTURE_TRIAL_BALANCE' || /NERACA|TRIAL BALANCE|LAPORAN/.test(t)) return 'Posisi Neraca Dikirim';
                return 'Keuangan';
            }
            if (mod === 'ACADEMIC') return 'Akademik';
            if (mod === 'FOUNDATION') return 'Yayasan';
            if (mod) return mod.charAt(0) + mod.slice(1).toLowerCase();
            return 'Aktivitas';
        },
        async updateStatus(item, status) {
            if(!confirm(`Apakah Anda yakin ingin ${status === 'APPROVED' ? 'MENYETUJUI' : 'MENOLAK'} pengajuan ini?`)) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/approval.php?action=update_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: item.id, status: status })
                });
                const data = await res.json();
                if (data.success) {
                    this.stats.approvals = (this.stats.approvals || []).filter(i => i.id !== item.id);
                    alert(`Berhasil ${status === 'APPROVED' ? 'disetujui' : 'ditolak'}`);
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan sistem');
            }
        },
        renderChart() {
            if(!this.chartData) return;
            const ctx = document.getElementById('financeChart');
            if(ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.chartData.labels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: this.chartData.income,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 3
                            },
                            {
                                label: 'Pengeluaran',
                                data: this.chartData.expense,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true } },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { borderDash: [2, 4], color: '#f1f5f9' },
                                ticks: { font: { size: 10 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 } }
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                });
            }
        },
        daysUntil(dateStr) {
            if (!dateStr || dateStr === '-') return null;
            const target = new Date(dateStr);
            const today = new Date();
            const diffMs = target.getTime() - today.getTime();
            return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
        },
        daysOverdue(dateStr) {
            if (!dateStr || dateStr === '-') return 0;
            const d = new Date(dateStr);
            const today = new Date();
            const diffMs = today.getTime() - d.getTime();
            return diffMs > 0 ? Math.ceil(diffMs / (1000 * 60 * 60 * 24)) : 0;
        }
    },
    mounted() {
        this.updateTime();
        setInterval(this.updateTime, 60000);
        this.fetchData();
        this.fetchRecentExpenses();
        this.fetchDocuments();
        this.fetchActivityLogs();
        this.fetchVehicles();
        this.fetchMeetings();
        this.fetchApprovedApprovals();
        this.fetchChatMessages();
        setInterval(this.fetchChatMessages, 30000);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.showMeetingDetail) {
                this.showMeetingDetail = false;
            }
        });
    }
}).mount('#app');
</script>
