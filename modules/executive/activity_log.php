<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
$baseIncluded = require_once '../../includes/header.php';
?>
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/executive/index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="w-10 h-10 bg-slate-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-slate-200">
            <i class="fas fa-clipboard-list text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Aktivitas Unit/Divisi</h1>
            <span class="text-xs text-slate-500 font-medium">Ringkasan lintas divisi</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button data-div="SECURITY" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 bg-white text-indigo-600">Security</button>
        <button data-div="CLEANING" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Cleaning</button>
        <button data-div="FINANCE" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Finance</button>
        <button data-div="ACADEMIC" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Academic</button>
        <button data-div="FOUNDATION" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Foundation</button>
        <button data-div="COUNSELING" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">BK</button>
    </div>
</nav>
<style>
    body[data-division="COUNSELING"] .security-section { display: none !important; }
    body[data-division="SECURITY"] .bk-section { display: none !important; }
</style>
<main id="app" v-cloak class="flex-1 overflow-y-auto p-6 bg-slate-50">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-layer-group text-slate-600"></i> Ringkasan Aktivitas Divisi
                </h3>
                <div class="text-[11px] text-slate-500">Security & Aktivitas Divisi ditampilkan native tanpa frame</div>
            </div>
            <div class="p-4 security-section" v-if="activeDiv==='SECURITY'">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="md:col-span-2 border border-slate-200 rounded-xl p-4 bg-white shadow-sm" v-if="['MANAGERIAL','ADMIN','SUPERADMIN'].includes(userRole)">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-[12px] font-bold text-slate-800 flex items-center gap-2">
                                <i class="fab fa-whatsapp text-green-600"></i> Penerima WA per Divisi (Nama Pegawai)
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="loadRecipientsMap" class="text-[10px] font-bold text-blue-600 hover:text-blue-700">Muat</button>
                                <button @click="saveRecipientsMap" :disabled="savingRecipients" class="bg-indigo-600 text-white px-3 py-1 rounded text-[12px] font-bold">{{ savingRecipients ? 'Menyimpan...' : 'Simpan' }}</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Pilih Pegawai</label>
                                <div class="border border-slate-200 rounded-lg p-2 max-h-48 overflow-y-auto bg-white">
                                    <div v-for="e in employeesSecurity" :key="e.employee_id" class="flex items-center gap-2 py-1">
                                        <input type="checkbox" :value="e.employee_id" v-model="selectedSecurityRecipients" class="rounded">
                                        <span class="text-[12px] text-slate-700 font-bold">{{ e.name }}<span class="text-[11px] text-slate-500 font-normal" v-if="(e.mobile_phone||e.phone)">&nbsp;•&nbsp;{{ e.mobile_phone || e.phone }}</span></span>
                                    </div>
                                    <div v-if="(employeesSecurity||[]).length===0" class="text-[12px] text-slate-500">Tidak ada data</div>
                                </div>
                            </div>
                            <div></div>
                        </div>
                        <div class="mt-4 border-t border-slate-200 pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <div class="text-[12px] font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fab fa-whatsapp text-green-600"></i> Uji Broadcast WhatsApp (Manajerial)
                                </div>
                                <button @click="loadWaTargets" class="text-[10px] font-bold text-blue-600 hover:text-blue-700">Muat daftar</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Daftar Nomor (pisahkan koma/tiap baris)</label>
                                    <textarea v-model="waTargetsText" class="w-full border border-slate-300 rounded px-3 py-2 text-sm h-24"></textarea>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Pesan Uji</label>
                                    <textarea v-model="waBroadcastMessage" class="w-full border border-slate-300 rounded px-3 py-2 text-sm h-24" placeholder="Tulis pesan uji..."></textarea>
                                    <div class="mt-2 flex items-center gap-3">
                                        <label class="text-[11px] text-slate-600">
                                            <input type="checkbox" v-model="useLatestSecurity" class="mr-1"> Pakai laporan Security terbaru
                                        </label>
                                        <label class="text-[11px] text-slate-600">
                                            <input type="checkbox" v-model="useTestingSecurity" class="mr-1"> Prefix TESTING
                                        </label>
                                        <button @click="sendWaBroadcast" :disabled="sendingBroadcast" class="bg-green-600 text-white px-3 py-1 rounded text-[12px] font-bold">
                                            {{ sendingBroadcast ? 'Mengirim...' : 'Kirim Broadcast' }}
                                        </button>
                                    </div>
                                    <div v-if="broadcastResult" class="mt-2 text-[11px] text-slate-600">
                                        Terkirim: <span class="font-bold text-emerald-600">{{ broadcastResult.ok }}</span> •
                                        Gagal: <span class="font-bold text-red-600">{{ broadcastResult.fail }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Total Run</div>
                        <div class="text-2xl font-bold text-slate-800">{{ checklistSummary.total }}</div>
                    </div>
                </div>
                <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-2">Per Template</div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(c,name) in checklistSummary.byTemplate" :key="name" class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[12px] font-bold">{{ name }}: {{ c }}</span>
                            <span v-if="Object.keys(checklistSummary.byTemplate||{}).length===0" class="text-slate-500 text-sm">Tidak ada data</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 my-4" v-if="activeDiv==='SECURITY'">
                    <button @click="viewMode='list'" :class="viewMode==='list' ? 'bg-white shadow text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200">List</button>
                    <button @click="viewMode='calendar'" :class="viewMode==='calendar' ? 'bg-white shadow text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-3 py-1.5 rounded-md text-xs transition-all border border-slate-200">Kalender</button>
                    <input type="month" v-model="filters.month" @change="fetchChecklistMonth" class="border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                    <select v-model="filters.user_id" @change="fetchChecklistMonth" class="border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                        <option value="">Semua Pegawai (Security)</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.person_name || u.username }}</option>
                    </select>
                </div>
                <div v-if="activeDiv==='SECURITY' && viewMode==='list'">
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
                                        <button v-if="['MANAGERIAL','ADMIN','SUPERADMIN'].includes(userRole)" 
                                                @click="sendRunReport(r)" 
                                                :disabled="sendingRunId===r.id"
                                                class="text-[10px] font-bold text-green-700 hover:underline ml-2">
                                            {{ sendingRunId===r.id ? 'Mengirim...' : 'Kirim Laporan' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="(checklistRuns||[]).length===0" class="text-center text-slate-500 text-sm py-3">Tidak ada data bulan ini</div>
                </div>
                <div v-else-if="activeDiv==='SECURITY'">
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
                                        <button v-if="['MANAGERIAL','ADMIN','SUPERADMIN'].includes(userRole)" 
                                                @click="sendRunReport(r)" 
                                                :disabled="sendingRunId===r.id"
                                                class="text-green-700 hover:underline ml-1">
                                            {{ sendingRunId===r.id ? 'Mengirim...' : 'Kirim' }}
                                        </button>
                                    </div>
                                    <div v-if="d.runs.length>3" class="text-[11px] text-slate-500">+{{ d.runs.length-3 }} lagi</div>
                                    <div v-if="(d.runs||[]).length===0" class="text-[11px] text-slate-400">Tidak ada</div>
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
            </div>
            <div class="p-4 bk-section" v-else-if="activeDiv==='COUNSELING'">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="md:col-span-2 border border-slate-200 rounded-xl p-4 bg-white shadow-sm" v-if="['MANAGERIAL','ADMIN','SUPERADMIN'].includes(userRole)">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-[12px] font-bold text-slate-800 flex items-center gap-2">
                                <i class="fab fa-whatsapp text-green-600"></i> Penerima WA per Divisi (Nama Pegawai)
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="bkLoadRecipientsMap" class="text-[10px] font-bold text-blue-600 hover:text-blue-700">Muat</button>
                                <button @click="bkSaveRecipientsMap" :disabled="bkSavingEventRecipients" class="bg-indigo-600 text-white px-3 py-1 rounded text-[12px] font-bold">{{ bkSavingEventRecipients ? 'Menyimpan...' : 'Simpan' }}</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Pilih Pegawai</label>
                                <div class="border border-slate-200 rounded-lg p-2 max-h-48 overflow-y-auto bg-white">
                                    <div v-for="e in employeesBK" :key="e.employee_id" class="flex items-center gap-2 py-1">
                                        <input type="checkbox" :value="e.employee_id" v-model="bkSelectedRecipients" class="rounded">
                                        <span class="text-[12px] text-slate-700 font-bold">{{ e.name }}<span class="text-[11px] text-slate-500 font-normal" v-if="(e.mobile_phone||e.phone)">&nbsp;•&nbsp;{{ e.mobile_phone || e.phone }}</span></span>
                                    </div>
                                    <div v-if="(employeesBK||[]).length===0" class="text-[12px] text-slate-500">Tidak ada data</div>
                                </div>
                            </div>
                            <div></div>
                        </div>
                        <div class="mt-4 border-t border-slate-200 pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <div class="text-[12px] font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fab fa-whatsapp text-green-600"></i> Broadcast WhatsApp BK
                                </div>
                                <button @click="bkLoadWaTargets" class="text-[10px] font-bold text-blue-600 hover:text-blue-700">Muat daftar</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Daftar Nomor (pisahkan koma/tiap baris)</label>
                                    <textarea v-model="bkWaTargetsText" class="w-full border border-slate-300 rounded px-3 py-2 text-sm h-24"></textarea>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Pesan</label>
                                    <textarea v-model="bkWaBroadcastMessage" class="w-full border border-slate-300 rounded px-3 py-2 text-sm h-24" placeholder="Tulis pesan..."></textarea>
                                    <div class="mt-2 flex items-center gap-3">
                                        <label class="text-[11px] text-slate-600">
                                            <input type="checkbox" v-model="bkUseLatest" class="mr-1"> Pakai ticket BK terbaru
                                        </label>
                                        <label class="text-[11px] text-slate-600">
                                            <input type="checkbox" v-model="bkUseTesting" class="mr-1"> Prefix TESTING
                                        </label>
                                        <button @click="bkSendWaBroadcast" :disabled="bkSendingBroadcast" class="bg-green-600 text-white px-3 py-1 rounded text-[12px] font-bold">
                                            {{ bkSendingBroadcast ? 'Mengirim...' : 'Kirim Broadcast' }}
                                        </button>
                                    </div>
                                    <div v-if="bkBroadcastResult" class="mt-2 text-[11px] text-slate-600">
                                        Terkirim: <span class="font-bold text-emerald-600">{{ bkBroadcastResult.ok }}</span> •
                                        Gagal: <span class="font-bold text-red-600">{{ bkBroadcastResult.fail }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Total Ticket</div>
                        <div class="text-2xl font-bold text-slate-800">
                            {{ (bkStats.counts.OPEN||0) + (bkStats.counts.IN_PROGRESS||0) + (bkStats.counts.CLOSED||0) + (bkStats.counts.REOPEN||0) }}
                        </div>
                    </div>
                </div>
                <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm">
                    <div class="text-[10px] font-bold text-slate-400 uppercase mb-2">Per Status</div>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[12px] font-bold">OPEN: {{ bkStats.counts.OPEN }}</span>
                        <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[12px] font-bold">IN_PROGRESS: {{ bkStats.counts.IN_PROGRESS }}</span>
                        <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[12px] font-bold">CLOSED: {{ bkStats.counts.CLOSED }}</span>
                        <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[12px] font-bold">REOPEN: {{ bkStats.counts.REOPEN }}</span>
                    </div>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm">
                    <div class="px-4 py-3 bg-slate-50 text-[11px] font-bold text-slate-600">Ticket BK Terbaru</div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="t in bkTickets" :key="t.id" class="px-4 py-3 bg-white hover:bg-slate-50">
                            <div class="flex items-center justify-between">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 text-sm truncate">{{ t.title }}</div>
                                    <div class="text-[10px] text-slate-500 truncate">{{ t.student_name }} • NIS {{ t.nis }}</div>
                                </div>
                                <div class="text-right shrink-0 w-40">
                                    <div class="text-[10px] text-slate-400">{{ formatDate(t.created_at) }}</div>
                                    <div class="text-[10px] font-bold" :class="bkStatusClass(t.status)">{{ t.status }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="(bkTickets||[]).length===0" class="px-4 py-6 text-center text-slate-500 text-sm">Belum ada ticket.</div>
                    </div>
                </div>
                
                <div class="mt-4 border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm">
                    <div class="px-4 py-3 bg-slate-50 text-[11px] font-bold text-slate-600 flex justify-between items-center">
                        <span>Riwayat Notifikasi & Aktivitas</span>
                        <button @click="fetchBkActivityLogs" class="text-blue-600 hover:underline">Refresh</button>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div v-for="l in bkActivityLogs" :key="l.id" class="px-4 py-2 hover:bg-slate-50">
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-[10px] font-bold text-slate-500 uppercase bg-slate-100 px-1.5 py-0.5 rounded">{{ l.action }}</div>
                                <div class="text-[10px] text-slate-400">{{ formatDate(l.created_at) }}</div>
                            </div>
                            <div class="text-sm font-bold text-slate-800">{{ l.title }}</div>
                            <div class="text-[12px] text-slate-600">{{ l.description }}</div>
                            <div class="text-[10px] text-slate-400 mt-1" v-if="l.user_name">Oleh: {{ l.user_name }}</div>
                        </div>
                        <div v-if="(bkActivityLogs||[]).length===0" class="px-4 py-6 text-center text-slate-500 text-sm">Belum ada aktivitas.</div>
                    </div>
                </div>
            </div>
            <div class="p-6" v-else>
                <div class="text-center text-slate-500 text-sm">Divisi ini belum memiliki ringkasan. Pilih Security atau BK untuk melihat laporan.</div>
            </div>
        </div>
    </div>
</main>
<script>
const { createApp } = Vue;
document.addEventListener('DOMContentLoaded', () => {
    const app = createApp({
        data() {
            return {
                        activeDiv: 'COUNSELING',
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
                        userRole: String((window.USER_ROLE || '')).toUpperCase(),
                        waTargetsText: '',
                        waBroadcastMessage: '',
                        useLatestSecurity: false,
                        useTestingSecurity: false,
                        savingTargets: false,
                        sendingBroadcast: false,
                        broadcastResult: null
                        ,sendingRunId: null
                        ,employeesSecurity: []
                        ,selectedSecurityRecipients: []
                        ,savingRecipients: false
                        ,broadcastResultEmp: null
                        ,autoSending: false
                ,bkTickets: []
                ,bkStats: { counts: { OPEN: 0, IN_PROGRESS: 0, CLOSED: 0, REOPEN: 0 }, avg_completion_hours: 0, recent_closed_7d: 0 }
                ,bkWaTargetsText: ''
                ,bkWaBroadcastMessage: ''
                ,bkUseLatest: false
                        ,bkUseTesting: false
                ,bkSavingTargets: false
                ,bkSendingBroadcast: false
                ,bkBroadcastResult: null
                        ,bkSendingBroadcastEmp: false
                        ,bkBroadcastResultEmp: null
                        ,bkSelectedEvent: 'ESCALATION'
                        ,bkSelectedRecipients: []
                        ,bkSavingEventRecipients: false
                ,canEditHR: ['MANAGERIAL','ADMIN','SUPERADMIN'].includes(String((window.USER_ROLE || '')).toUpperCase())
                ,attrDivision: ''
                ,attrMobilePhone: ''
                ,savingAttrs: false
                ,divisionCtx: { name: 'COUNSELING', settings: {} }
                ,employeesBK: []
                ,bkActivityLogs: []
            };
        },
        methods: {
            base() {
                let b = window.BASE_URL || '/';
                if (b === '/' || !b) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    b = m ? `/${m[1]}/` : '/';
                }
                return b;
            },
            setDivision(div) {
                this.activeDiv = div;
                try { document.body.setAttribute('data-division', div); } catch(_) {}
                try { window.DIVISION = div; } catch(_) {}
                this.fetchDivisionSettings();
                if (div === 'SECURITY') {
                    this.fetchEmployeesSecurity();
                    this.fetchChecklistMonth();
                } else if (div === 'COUNSELING') {
                    this.fetchBkTickets();
                    this.fetchBkStats();
                    this.fetchEmployeesBK();
                    this.fetchBkActivityLogs();
                }
            },
            async fetchBkActivityLogs() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=list_activity_logs');
                    const j = await r.json();
                    this.bkActivityLogs = j.success ? (j.data || []) : [];
                } catch(_) { this.bkActivityLogs = []; }
            },
            async fetchDivisionSettings() {
                try {
                    const res = await fetch(this.base() + 'api/get_settings.php');
                    const all = await res.json();
                    const d = String(this.activeDiv||'').toUpperCase();
                    const s = {};
                    Object.keys(all || {}).forEach(k => {
                        const key = String(k).toLowerCase();
                        if (d === 'SECURITY') {
                            if (key.endsWith('_security') || key.startsWith('security_') || key === 'wa_managerial_targets') {
                                s[k] = all[k];
                            }
                        } else if (d === 'COUNSELING') {
                            if (key.endsWith('_counseling') || key.startsWith('counseling_') || key.startsWith('bk_')) {
                                s[k] = all[k];
                            }
                        } else if (d === 'CLEANING') {
                            if (key.startsWith('cleaning_') || key.endsWith('_cleaning')) {
                                s[k] = all[k];
                            }
                        } else if (d === 'FINANCE') {
                            if (key.startsWith('finance_') || key.endsWith('_finance')) {
                                s[k] = all[k];
                            }
                        } else if (d === 'ACADEMIC') {
                            if (key.startsWith('academic_') || key.endsWith('_academic')) {
                                s[k] = all[k];
                            }
                        } else if (d === 'FOUNDATION') {
                            if (key.startsWith('foundation_') || key.endsWith('_foundation')) {
                                s[k] = all[k];
                            }
                        }
                    });
                    this.divisionCtx = { name: d, settings: s };
                    try { window.DIVISION_SETTINGS = s; } catch(_) {}
                } catch(_) {
                    this.divisionCtx = { name: String(this.activeDiv||'').toUpperCase(), settings: {} };
                }
            },
            async bkLoadWaTargets() {
                try {
                    const ids = Array.isArray(this.bkSelectedRecipients) ? this.bkSelectedRecipients.map(x => Number(x)) : [];
                    const map = {};
                    (Array.isArray(this.employeesBK) ? this.employeesBK : []).forEach(e => {
                        const id = Number(e.employee_id);
                        const num = String(e.mobile_phone || e.phone || '').trim();
                        if (!isNaN(id) && num) map[id] = num;
                    });
                    const nums = ids.map(id => map[id]).filter(x => !!x);
                    this.bkWaTargetsText = nums.join('\n');
                } catch(_) {
                    this.bkWaTargetsText = '';
                }
            },
            async bkSaveWaTargets() {
                this.bkSavingTargets = true;
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=save_wa_targets', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ targets: this.bkWaTargetsText })
                    });
                    const j = await r.json();
                    if (!j.success) alert('Gagal menyimpan: ' + (j.message || ''));
                } catch(_) { alert('Terjadi kesalahan sistem'); }
                finally { this.bkSavingTargets = false; }
            },
            async bkSendWaBroadcast() {
                this.bkSendingBroadcast = true;
                this.bkBroadcastResult = null;
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=send_wa_broadcast', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: this.bkWaBroadcastMessage, use_latest: this.bkUseLatest, testing: this.bkUseTesting, targets: this.bkWaTargetsText })
                    });
                    const j = await r.json();
                    this.bkBroadcastResult = j.data || { ok: 0, fail: 0 };
                    if (!j.success) alert('Gagal mengirim');
                } catch(_) { alert('Terjadi kesalahan sistem'); }
                finally { this.bkSendingBroadcast = false; }
            },
            async bkSendWaBroadcastByEmployees() {
                this.bkSendingBroadcastEmp = true;
                this.bkBroadcastResultEmp = null;
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=send_wa_broadcast_employees', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ employee_ids: this.bkSelectedRecipients, message: this.bkWaBroadcastMessage, use_latest: this.bkUseLatest, testing: this.bkUseTesting })
                    });
                    const j = await r.json();
                    if (!j.success) {
                        alert('Gagal mengirim: ' + (j.message || ''));
                    }
                    this.bkBroadcastResultEmp = j.data || { ok: 0, fail: 0 };
                } catch(_) { alert('Terjadi kesalahan sistem'); }
                finally { this.bkSendingBroadcastEmp = false; }
            },
            bkStatusClass(s) {
                const u = String(s||'').toUpperCase();
                if (u === 'OPEN') return 'text-amber-600';
                if (u === 'IN_PROGRESS') return 'text-blue-600';
                if (u === 'CLOSED') return 'text-emerald-600';
                if (u === 'REOPEN') return 'text-purple-600';
                return 'text-slate-600';
            },
            async fetchBkTickets() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=list_tickets_recent&limit=20');
                    const j = await r.json();
                    this.bkTickets = j.success ? (j.data || []) : [];
                } catch(_) { this.bkTickets = []; }
            },
            async fetchBkStats() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=ticket_stats');
                    const j = await r.json();
                    this.bkStats = j.success ? (j.data || this.bkStats) : this.bkStats;
                } catch(_) {}
            },
            async fetchEmployeesBK() {
                try {
                    const r = await fetch(this.base() + 'api/get_employees.php?unit=all');
                    const j = await r.json();
                    const arr = Array.isArray(j) ? j : (j.success ? (j.data || []) : []);
                    const bk = arr.filter(e => {
                        const div = String(e.division||'').toUpperCase();
                        const typ = String(e.employee_type||'').toUpperCase();
                        const teams = Array.isArray(e.teams) ? e.teams.map(t => String(t).toUpperCase()) : [];
                        const isBkTeam = teams.includes('BK');
                        const isCounseling = div === 'COUNSELING' || typ === 'COUNSELING';
                        return isBkTeam || isCounseling;
                    });
                    this.employeesBK = bk;
                } catch(_) { this.employeesBK = []; }
            },
            async bkLoadRecipientsMap() {
                try {
                    const r = await fetch(this.base() + 'api/counseling.php?action=get_wa_recipients_map');
                    const j = await r.json();
                    const m = j && j.success ? (j.data || {}) : {};
                    let arr = [];
                    if (Array.isArray(m.COUNSELING)) arr = m.COUNSELING;
                    else if (m.COUNSELING && Array.isArray(m.COUNSELING[this.bkSelectedEvent])) arr = m.COUNSELING[this.bkSelectedEvent];
                    const ids = (arr || []).map(x => Number(String(x))).filter(n => !isNaN(n));
                    this.bkSelectedRecipients = ids;
                } catch(_) {
                    this.bkSelectedRecipients = [];
                }
            },
            async bkSaveRecipientsMap() {
                this.bkSavingEventRecipients = true;
                try {
                    const body = {
                        module: 'COUNSELING',
                        employee_ids: this.bkSelectedRecipients
                    };
                    const r = await fetch(this.base() + 'api/counseling.php?action=save_wa_recipients_map', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    });
                    const j = await r.json();
                    if (!j.success) alert('Gagal menyimpan penerima: ' + (j.message || ''));
                } catch(_) { alert('Terjadi kesalahan sistem'); }
                finally { this.bkSavingEventRecipients = false; }
            },
                async bkLoadCounselingTeam() {
                    try {
                        const r = await fetch(this.base() + 'api/counseling.php?action=get_counseling_team');
                        const j = await r.json();
                        const ids = (j && j.success && Array.isArray(j.data)) ? j.data.map(x => Number(x)) : [];
                        this.bkSelectedRecipients = ids;
                        if (ids.length === 0) {
                            alert('Tim BK belum disetel di HR (teams BK) atau konfigurasi kosong.');
                        }
                    } catch(_) {
                        alert('Gagal mengambil Tim BK');
                    }
                },
                async loadWaTargets() {
                    try {
                        const ids = Array.isArray(this.selectedSecurityRecipients) ? this.selectedSecurityRecipients.map(x => Number(x)) : [];
                        const map = {};
                        (Array.isArray(this.employeesSecurity) ? this.employeesSecurity : []).forEach(e => {
                            const id = Number(e.employee_id);
                            const num = String(e.mobile_phone || e.phone || '').trim();
                            if (!isNaN(id) && num) map[id] = num;
                        });
                        const nums = ids.map(id => map[id]).filter(x => !!x);
                        this.waTargetsText = nums.join('\n');
                    } catch(_) {
                        this.waTargetsText = '';
                    }
                },
                async loadRecipientsMap() {
                    try {
                        const r = await fetch(this.base() + 'api/security.php?action=get_wa_recipients_map');
                        const j = await r.json();
                        const m = j && j.success ? (j.data || {}) : {};
                        const arr = Array.isArray(m.SECURITY) ? m.SECURITY : [];
                        this.selectedSecurityRecipients = arr.map(x => Number(x));
                    } catch(_) { this.selectedSecurityRecipients = []; }
                },
                async saveWaTargets() {
                    this.savingTargets = true;
                    try {
                        const r = await fetch(this.base() + 'api/security.php?action=save_wa_targets', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ targets: this.waTargetsText })
                        });
                        const j = await r.json();
                        if (!j.success) alert('Gagal menyimpan: ' + (j.message || ''));
                    } catch(e) { alert('Terjadi kesalahan sistem'); }
                    finally { this.savingTargets = false; }
                },
                async saveRecipientsMap() {
                    this.savingRecipients = true;
                    try {
                        const r = await fetch(this.base() + 'api/security.php?action=save_wa_recipients_map', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ module: 'SECURITY', employee_ids: this.selectedSecurityRecipients })
                        });
                        const j = await r.json();
                        if (!j.success) alert('Gagal menyimpan penerima: ' + (j.message || ''));
                    } catch(_) { alert('Terjadi kesalahan sistem'); }
                    finally { this.savingRecipients = false; }
                },
                async saveEmployeeAttributes() {
                    if (!this.canEditHR) return;
                    const ids = Array.isArray(this.selectedSecurityRecipients) ? this.selectedSecurityRecipients.slice() : [];
                    if (ids.length === 0) return;
                    this.savingAttrs = true;
                    try {
                        const bodyBase = {
                            division: this.attrDivision,
                            mobile_phone: this.attrMobilePhone
                        };
                        const reqs = ids.map(id => {
                            const body = { action: 'update_custom_attributes', employee_id: id, ...bodyBase };
                            return fetch(this.base() + 'api/manage_employee.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(body)
                            }).then(r => r.json()).catch(() => ({ success: false }));
                        });
                        const res = await Promise.all(reqs);
                        const ok = res.filter(x => x && x.success).length;
                        const fail = res.length - ok;
                        alert('Selesai: OK ' + ok + ' • Gagal ' + fail);
                        await this.fetchEmployeesSecurity();
                    } catch(_) {
                        alert('Terjadi kesalahan sistem');
                    } finally {
                        this.savingAttrs = false;
                    }
                },
                async sendWaBroadcast() {
                    this.sendingBroadcast = true;
                    this.broadcastResult = null;
                    try {
                        const r = await fetch(this.base() + 'api/security.php?action=send_wa_broadcast', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ message: this.waBroadcastMessage, use_latest: this.useLatestSecurity, testing: this.useTestingSecurity, targets: this.waTargetsText })
                        });
                        const j = await r.json();
                        if (!j.success) {
                            alert('Gagal mengirim: ' + (j.message || ''));
                        }
                        this.broadcastResult = j.data || { ok: 0, fail: 0 };
                    } catch(e) { alert('Terjadi kesalahan sistem'); }
                    finally { this.sendingBroadcast = false; }
                },
                async sendWaBroadcastByEmployees() {
                    this.sendingBroadcast = true;
                    this.broadcastResultEmp = null;
                    try {
                        const r = await fetch(this.base() + 'api/security.php?action=send_wa_broadcast_employees', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ module: 'SECURITY', employee_ids: this.selectedSecurityRecipients, message: this.waBroadcastMessage, use_latest: this.useLatestSecurity, testing: this.useTestingSecurity })
                        });
                        const j = await r.json();
                        if (!j.success) {
                            alert('Gagal mengirim: ' + (j.message || ''));
                        }
                        this.broadcastResultEmp = j.data || { ok: 0, fail: 0 };
                    } catch(_) { alert('Terjadi kesalahan sistem'); }
                    finally { this.sendingBroadcast = false; }
                },
                async autoSendSecurity() {
                    this.autoSending = true;
                    try {
                        const r = await fetch(this.base() + 'api/security.php?action=auto_send_security', { method: 'POST' });
                        const j = await r.json();
                        if (!j.success) alert('Gagal proses otomatis: ' + (j.message || ''));
                    } catch(_) { alert('Terjadi kesalahan sistem'); }
                    finally { this.autoSending = false; }
                },
                async sendRunReport(r) {
                    if (!r || !r.id) return;
                    this.sendingRunId = r.id;
                    try {
                        const resp = await fetch(this.base() + 'api/security.php?action=send_wa_run_report', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ run_id: r.id })
                        });
                        const j = await resp.json();
                        if (j && j.success) {
                            alert('Laporan terkirim: OK ' + ((j.data && j.data.ok) || 0) + ' • Gagal ' + ((j.data && j.data.fail) || 0));
                        } else {
                            alert('Gagal mengirim: ' + (j && j.message ? j.message : 'Unknown error'));
                        }
                    } catch(_) {
                        alert('Terjadi kesalahan sistem');
                    } finally {
                        this.sendingRunId = null;
                    }
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
            async fetchEmployeesSecurity() {
                try {
                    const r = await fetch(this.base() + 'api/get_employees.php?unit=all');
                    const j = await r.json();
                    const arr = Array.isArray(j) ? j : (j.success ? (j.data || []) : []);
                    const sec = arr.filter(e => {
                        const div = String(e.division||'').toUpperCase();
                        const typ = String(e.employee_type||'').toUpperCase();
                        const pos = String(e.position||'').toUpperCase();
                        const isSecurity = div === 'SECURITY' || typ === 'SECURITY';
                        const isManagerial = div === 'MANAGERIAL' || typ === 'MANAGERIAL' || pos.includes('MANAJERIAL');
                        return isSecurity || isManagerial;
                    });
                    this.employeesSecurity = sec;
                    try {
                        if (this.activeDiv === 'SECURITY') {
                            const ids = sec.map(e => Number(e.employee_id)).filter(n => !isNaN(n));
                            const names = sec.map(e => String(e.name||'').trim()).filter(s => s !== '');
                            const url = this.base() + 'api/security.php?action=log_filter_selection';
                            const payload = JSON.stringify({ employee_ids: ids, names });
                            if (navigator && typeof navigator.sendBeacon === 'function') {
                                const blob = new Blob([payload], { type: 'application/json' });
                                navigator.sendBeacon(url, blob);
                            } else {
                                fetch(url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: payload,
                                    keepalive: true
                                }).catch(() => {});
                            }
                        }
                    } catch (_) {}
                } catch(_) { this.employeesSecurity = []; }
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
                    const parts = ym.split('-');
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1;
                    const first = new Date(y, m, 1);
                    const daysInMonth = new Date(y, m + 1, 0).getDate();
                    const mondayFirst = (first.getDay() + 6) % 7;
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
            formatDate(s) {
                if (!s) return '-';
                try { return new Date(s).toLocaleDateString('id-ID'); } catch (_) { return s; }
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
        watch: {
            bkSelectedEvent(newVal) {
                this.bkLoadRecipientsMap();
            },
            bkSelectedRecipients() {
                this.bkLoadWaTargets();
            },
            employeesBK() {
                this.bkLoadWaTargets();
            },
            selectedSecurityRecipients() {
                this.loadWaTargets();
            },
            employeesSecurity() {
                this.loadWaTargets();
            }
        },
        async mounted() {
            await this.fetchUsers();
            await this.fetchEmployeesSecurity();
            await this.fetchEmployeesBK();
            await this.fetchVehicles();
            await this.fetchChecklistMonth();
            await this.fetchBkTickets();
            await this.fetchBkStats();
            await this.fetchBkActivityLogs();
            await this.bkLoadRecipientsMap();
            await this.bkLoadWaTargets();
            await this.fetchDivisionSettings();
        }
    }).mount('#app');
    const divButtons = document.querySelectorAll('nav [data-div]');
    function updateNavClasses(selDiv) {
        divButtons.forEach(btn => {
            const on = btn.getAttribute('data-div') === selDiv;
            btn.className = 'px-3 py-1 rounded text-[10px] font-bold border border-slate-300 ' + (on ? 'bg-white text-indigo-600' : 'text-slate-600');
        });
    }
    divButtons.forEach(b => b.addEventListener('click', () => {
        const d = b.getAttribute('data-div');
        app.setDivision(d);
        updateNavClasses(d);
    }));
    app.setDivision('SECURITY');
    updateNavClasses('SECURITY');
});
</script>
