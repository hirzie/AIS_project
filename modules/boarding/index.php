<?php
require_once '../../includes/guard.php';
require_login_and_module('boarding');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Asrama - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
    <style>
        [v-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .animate-fade { animation: fade 0.3s ease-out; }
        @keyframes fade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div id="app" v-cloak class="flex flex-col h-screen">

    <?php require_once '../../includes/header_boarding.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-8 flex flex-col items-center bg-slate-50 relative">
        <!-- Background Decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
            <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-indigo-100/50 blur-3xl"></div>
            <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-purple-100/50 blur-3xl"></div>
        </div>

        <div class="max-w-6xl w-full space-y-6 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="students.php" class="block">
                            <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Data Santri</h3>
                            <p class="text-sm text-slate-500 mb-4">Kelola data penghuni asrama, halaqoh, dan musyrif.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="students.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                                <i class="fas fa-list w-5 text-center"></i> Daftar Santri
                            </a>
                            <a href="rooms.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                                <i class="fas fa-door-open w-5 text-center"></i> Manajemen Kamar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="permission_history.php" class="block">
                            <div class="w-14 h-14 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-yellow-600 group-hover:text-white transition-colors">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Perizinan</h3>
                            <p class="text-sm text-slate-500 mb-4">Lihat daftar izin santri, status keluar-masuk, dan riwayat.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="permission_input.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-yellow-600 transition-colors">
                                <i class="fas fa-pen w-5 text-center"></i> Input Izin Baru
                            </a>
                            <a href="permission_history.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-yellow-600 transition-colors">
                                <i class="fas fa-list w-5 text-center"></i> Daftar & Riwayat Izin
                            </a>
                        </div>
                    </div>
                </div>
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="discipline.php" class="block">
                            <div class="w-14 h-14 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-red-600 group-hover:text-white transition-colors">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Kedisiplinan</h3>
                            <p class="text-sm text-slate-500 mb-4">Pencatatan pelanggaran, poin kedisiplinan, dan prestasi.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="discipline.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-red-600 transition-colors">
                                <i class="fas fa-exclamation-triangle w-5 text-center"></i> Pelanggaran & Prestasi
                            </a>
                            <a href="discipline.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-red-600 transition-colors">
                                <i class="fas fa-star w-5 text-center"></i> Ranking Poin
                            </a>
                        </div>
                    </div>
                </div>
                <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <a href="schedule.php" class="block">
                            <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Jadwal Kegiatan</h3>
                            <p class="text-sm text-slate-500 mb-4">Agenda harian santri, halaqoh, laundry, dan menu makan.</p>
                        </a>
                        <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                            <a href="schedule.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-clock w-5 text-center"></i> Agenda Harian
                            </a>
                            <a href="halaqoh.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-book-reader w-5 text-center"></i> Halaqoh
                            </a>
                            <a href="laundry.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-tshirt w-5 text-center"></i> Laundry
                            </a>
                            <a href="dining.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-utensils w-5 text-center"></i> Menu Makan
                            </a>
                            <a href="#" @click.prevent="activeTab='ZERO'" class="flex items-center text-xs font-bold text-red-600 hover:text-red-700 transition-colors">
                                <i class="fas fa-bullhorn w-5 text-center"></i> Zero Report
                            </a>
                            <a href="../procurement/submit.php?module=BOARDING&label=Asrama" class="flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700 transition-colors mt-1 pt-1 border-t border-slate-50">
                                <i class="fas fa-shopping-cart w-5 text-center"></i> Pengajuan / Procurement
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-layer-group text-indigo-600"></i> Center Asrama
                            </h3>
                            <div class="flex items-center gap-2">
                                <button @click="activeTab='MEETINGS'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='MEETINGS' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'">Rapat</button>
                                <button @click="activeTab='TASKS'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='TASKS' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700'">Task</button>
                                <button @click="activeTab='PROC'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='PROC' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'">Pengajuan</button>
                                <button @click="activeTab='DOCS'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='DOCS' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Dokumen</button>
                                    <button @click="activeTab='ZERO'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='ZERO' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700'">Zero Report</button>
                            </div>
                        </div>
                        <div class="p-5">
                            <div v-show="activeTab==='MEETINGS'">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[12px] font-bold text-slate-600">Rapat Asrama</div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700" v-if="recentMeetingCount > 0">{{ recentMeetingCount }} baru</span>
                                </div>
                                <div v-if="meetings.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="m in meetings" :key="m.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ m.title }}</div>
                                                    <div class="text-[10px] font-mono text-slate-500">{{ m.meeting_number }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-[10px] text-slate-400">{{ formatDate(m.meeting_date) }}</div>
                                                    <div class="flex gap-2 justify-end mt-1">
                                                        <a :href="baseUrl + 'api/meetings.php?action=list_documents&meeting_id=' + m.id" target="_blank" class="text-[10px] font-bold text-indigo-600 hover:underline">Dokumen</a>
                                                        <button @click="openMeeting(m)" class="text-[10px] font-bold text-slate-600 hover:text-indigo-600">Detail</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2" v-if="m.notes">{{ m.notes }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada rapat.</div>
                            </div>
                            <div v-show="activeTab==='TASKS'">
                                <div class="flex items-center gap-2 mb-3">
                                    <input v-model="newTaskTitle" @keyup.enter="addTask" placeholder="Tambahkan tugas..." class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-500">
                                    <button @click="addTask" class="px-3 py-2 rounded bg-amber-600 text-white text-[12px] font-bold">Tambah</button>
                                </div>
                                <div v-if="tasks.length > 0" class="space-y-2">
                                    <div v-for="t in tasks" :key="t.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-slate-50">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" v-model="t.done" @change="saveTasks">
                                            <span :class="t.done ? 'line-through text-slate-400' : 'text-slate-700'">{{ t.title }}</span>
                                        </label>
                                        <button @click="deleteTask(t.id)" class="text-[12px] font-bold text-red-600 hover:text-red-700">Hapus</button>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada tugas.</div>
                            </div>
                            <div v-show="activeTab==='PROC'">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[12px] font-bold text-slate-600">Pengajuan Asrama</div>
                                    <a :href="baseUrl + 'modules/executive/managerial_approval.php'" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700">Buka Approval Center</a>
                                </div>
                                <div v-if="approvals.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="a in approvals" :key="a.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ a.title || (a.module + ' #' + a.reference_no) }}</div>
                                                    <div class="text-[10px] text-slate-500">{{ a.requester || '-' }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-[10px] font-bold" :class="a.status==='APPROVED' ? 'text-emerald-600' : a.status==='REJECTED' ? 'text-red-600' : 'text-amber-600'">{{ a.status }}</div>
                                                    <div class="text-[10px] text-slate-400">{{ formatDate(a.created_at) }}</div>
                                                </div>
                                            </div>
                                            <div class="text-[12px] text-slate-600 mt-2" v-if="Number(a.amount) > 0">{{ formatCurrency(a.amount) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada pengajuan.</div>
                            </div>
                            <div v-show="activeTab==='DOCS'">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[12px] font-bold text-slate-600">Dokumen Asrama</div>
                                    <a :href="baseUrl + 'api/meetings.php?action=list_documents&module=BOARDING&limit=50'" target="_blank" class="text-[11px] font-bold text-blue-600 hover:text-blue-700">Buka Daftar</a>
                                </div>
                                <div v-if="documents.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="d in documents" :key="d.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ d.doc_title }}</div>
                                                    <div class="text-[10px] text-slate-500">{{ d.doc_type || 'FILE' }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <a v-if="d.doc_url" :href="d.doc_url" target="_blank" class="text-[10px] font-bold text-blue-600 hover:underline">Buka</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada dokumen.</div>
                            </div>
                            <div v-show="activeTab==='ZERO'">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <div class="text-[12px] font-bold text-slate-700">Jendela Lapor</div>
                                        <div class="text-[10px] text-slate-500">21:00–22:00</div>
                                        <div v-if="!zeroStatus.reported" class="text-[10px] mt-1" :class="zeroAlertClass">{{ zeroAlertText }}</div>
                                        <div v-else class="text-[10px] mt-1 text-emerald-600">Sudah lapor: {{ (zeroStatus.status || '').toUpperCase() }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="openIncident" class="px-3 py-1 rounded bg-red-600 text-white text-[11px] font-bold">Ada Kejadian</button>
                                        <button @click="reportSafe" class="px-3 py-1 rounded bg-emerald-600 text-white text-[11px] font-bold">Lengkap & Aman</button>
                                    </div>
                                </div>
                                <div v-if="showIncident" class="rounded-xl border border-slate-200 p-3 bg-slate-50">
                                    <div class="flex items-center gap-2 mb-2">
                                        <input v-model="incidentQuery" class="flex-1 border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Cari siswa...">
                                        <button @click="submitIncident" class="px-3 py-2 rounded bg-red-600 text-white text-[12px] font-bold">Kirim</button>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2 max-h-56 overflow-y-auto">
                                        <div v-for="s in filteredStudents" :key="s.id" class="flex items-center justify-between p-2 bg-white border border-slate-200 rounded">
                                            <div class="text-sm">{{ s.name }}</div>
                                            <select v-model="incidentMap[s.id]" class="text-xs border border-slate-300 rounded px-2 py-1">
                                                <option value="">-</option>
                                                <option value="SAKIT">Sakit</option>
                                                <option value="IZIN">Izin</option>
                                                <option value="ALFA">Alfa</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white overflow-hidden">
                                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fas fa-calendar-week text-amber-600"></i> Laporan Mingguan</div>
                                        <div class="text-[10px] text-slate-500">Jumat siang / Ahad pagi</div>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <button @click="reportWeeklySafe" class="px-3 py-2 rounded bg-emerald-600 text-white text-[12px] font-bold">Fasilitas Layak Huni</button>
                                            <span class="text-[11px] text-slate-500">Klik jika kamar aman</span>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50">
                                            <div class="text-[12px] font-bold text-slate-700 mb-2">Lapor Kerusakan Aset</div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <input v-model="weeklyAssetName" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Nama aset (misal: Lemari B3)">
                                                <input v-model="weeklyPhotoUrl" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="URL foto bukti (opsional)">
                                            </div>
                                            <textarea v-model="weeklyDescription" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" rows="2" placeholder="Deskripsi singkat"></textarea>
                                            <div class="mt-2">
                                                <button @click="submitWeeklyIncident" class="px-3 py-2 rounded bg-red-600 text-white text-[12px] font-bold">Kirim Kerusakan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white overflow-hidden">
                                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fas fa-calendar-alt text-indigo-600"></i> Laporan Bulanan</div>
                                        <div class="text-[10px] text-slate-500">Tanggal 25–28</div>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <button @click="reportMonthlySafe" class="px-3 py-2 rounded bg-emerald-600 text-white text-[12px] font-bold">Stok Logistik Aman</button>
                                            <span class="text-[11px] text-slate-500">Klik jika stok cukup</span>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50">
                                            <div class="text-[12px] font-bold text-slate-700 mb-2">Pengajuan Restock</div>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                <label class="text-[12px] flex items-center gap-2"><input type="checkbox" v-model="monthlySelected.galon"> Galon</label>
                                                <label class="text-[12px] flex items-center gap-2"><input type="checkbox" v-model="monthlySelected.pembersih"> Sabun pembersih</label>
                                                <label class="text-[12px] flex items-center gap-2"><input type="checkbox" v-model="monthlySelected.alat_bersih"> Alat kebersihan</label>
                                                <label class="text-[12px] flex items-center gap-2"><input type="checkbox" v-model="monthlySelected.p3k"> P3K</label>
                                                <label class="text-[12px] flex items-center gap-2"><input type="checkbox" v-model="monthlySelected.lainnya"> Lainnya</label>
                                            </div>
                                            <input v-model="monthlyNotes" class="mt-2 w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Catatan tambahan (opsional)">
                                            <div class="mt-2">
                                                <button @click="submitMonthlyRestock" class="px-3 py-2 rounded bg-indigo-600 text-white text-[12px] font-bold">Ajukan Restock</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="isManager" class="mt-4 rounded-xl border border-slate-200 p-3 bg-white">
                                    <div class="text-sm font-bold text-slate-700 mb-2">Rekap Hari Ini</div>
                                    <div v-if="todayReports.length > 0" class="space-y-2">
                                        <div v-for="r in todayReports" :key="r.id" class="flex items-center justify-between p-2 border border-slate-200 rounded">
                                            <div class="text-sm">{{ r.name || r.username }}</div>
                                            <div class="text-xs">{{ (r.status || '').toUpperCase() }} ({{ r.incident_count }})</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-slate-500">Belum ada rekap.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <aside class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden h-full flex flex-col">
                        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-comments text-indigo-600"></i> Chat Asrama
                            </h3>
                        </div>
                        <div class="p-5 flex-1 overflow-y-auto">
                            <div v-if="chatMessages.length > 0" class="space-y-3">
                                <div v-for="(c, idx) in chatMessages" :key="c.id" class="flex items-start justify-between">
                                    <div class="max-w-[80%]">
                                        <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-200 rounded px-3 py-2">{{ c.text }}</div>
                                        <div class="text-[10px] text-slate-400 mt-1">{{ formatChatTime(c.ts) }}</div>
                                    </div>
                                    <button @click="deleteChatMessage(idx)" class="text-[10px] font-bold text-red-600">Hapus</button>
                                </div>
                            </div>
                            <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada pesan.</div>
                        </div>
                        <div class="p-4 border-t border-slate-100 bg-white">
                            <div class="flex items-center gap-2">
                                <input v-model="chatInput" @keyup.enter="sendChat" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" placeholder="Tulis pesan...">
                                <button @click="sendChat" class="px-3 py-2 rounded bg-indigo-600 text-white text-[12px] font-bold">Kirim</button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- MODAL DETAIL RAPAT (dipindahkan ke dalam #app) -->
    <div v-if="showMeetingModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" id="modal-root">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-800">Detail Rapat</h3>
                <button @click="showMeetingModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
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
                    <button @click="showMeetingModal=false" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                currentGender: 'all',
                baseUrl: window.BASE_URL || '/',
                zeroStatus: { reported: false, status: '', incident_count: 0 },
                zeroWindowStart: '21:00',
                zeroWindowEnd: '22:00',
                zeroAlertText: '',
                zeroAlertClass: 'text-slate-500',
                showIncident: false,
                incidentQuery: '',
                incidentMap: {},
                students: [],
                todayReports: [],
                isManager: ['SUPERADMIN','ADMIN','MANAGERIAL'].includes((window.USER_ROLE || '').toUpperCase()),
                meetings: [],
                recentMeetingCount: 0,
                showMeetingModal: false,
                meetingDetail: null,
                activeTab: 'MEETINGS',
                approvals: [],
                documents: [],
                tasks: [],
                newTaskTitle: '',
                chatMessages: [],
                chatInput: '',
                weeklyAssetName: '',
                weeklyPhotoUrl: '',
                weeklyDescription: '',
                monthlySelected: { galon: false, pembersih: false, alat_bersih: false, p3k: false, lainnya: false },
                monthlyNotes: ''
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        },
        methods: {
            normalizeBaseUrl() {
                if (this.baseUrl === '/' || !this.baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    this.baseUrl = m ? `/${m[1]}/` : '/';
                }
            },
            async fetchMeetings() {
                try {
                    this.normalizeBaseUrl();
                    const res = await fetch(this.baseUrl + 'api/meetings.php?action=list&module=BOARDING&limit=12');
                    const data = await res.json();
                    if (data.success) this.meetings = data.data || [];
                } catch(e) { console.error(e); }
            },
            async fetchApprovals() {
                try {
                    this.normalizeBaseUrl();
                    const res = await fetch(this.baseUrl + 'api/approval.php?action=get_list&status=ALL');
                    const data = await res.json();
                    if (data.success) {
                        const rows = Array.isArray(data.data) ? data.data : [];
                        this.approvals = rows.filter(a => String(a.module || '').toUpperCase() === 'BOARDING').slice(0, 12);
                    }
                } catch(e) {}
            },
            genRequestId() {
                try {
                    if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
                    const ts = Date.now();
                    const rnd = Math.random().toString(36).slice(2);
                    const uid = String(window.USER_ID || '');
                    return `zero-${ts}-${uid}-${rnd}`;
                } catch(_) { return `zero-${Date.now()}`; }
            },
            async fetchDocuments() {
                try {
                    this.normalizeBaseUrl();
                    const res = await fetch(this.baseUrl + 'api/meetings.php?action=list_documents&module=BOARDING&limit=30');
                    const data = await res.json();
                    if (data.success) this.documents = data.data || [];
                } catch(e) {}
            },
            async fetchRecentCount() {
                try {
                    this.normalizeBaseUrl();
                    const res = await fetch(this.baseUrl + 'api/meetings.php?action=recent_count&module=BOARDING&days=14');
                    const data = await res.json();
                    if (data.success) this.recentMeetingCount = data.data.count || 0;
                } catch(e) { console.error(e); }
            },
            loadTasks() {
                try {
                    const raw = localStorage.getItem('boarding_tasks') || '[]';
                    const arr = JSON.parse(raw);
                    this.tasks = Array.isArray(arr) ? arr : [];
                } catch(_) { this.tasks = []; }
            },
            saveTasks() {
                localStorage.setItem('boarding_tasks', JSON.stringify(this.tasks));
            },
            addTask() {
                const t = String(this.newTaskTitle || '').trim();
                if (!t) return;
                this.tasks.unshift({ id: Date.now(), title: t, done: false });
                this.newTaskTitle = '';
                this.saveTasks();
            },
            deleteTask(id) {
                this.tasks = this.tasks.filter(x => x.id !== id);
                this.saveTasks();
            },
            formatDate(dateStr) {
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            },
            formatCurrency(val) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
            },
            filteredStudents() {
                const q = String(this.incidentQuery || '').toLowerCase();
                if (!q) return this.students;
                return this.students.filter(s => String(s.name || '').toLowerCase().includes(q));
            },
                updateZeroAlerts() {
                    if (this.zeroStatus.reported) { this.zeroAlertText = ''; this.zeroAlertClass = 'text-slate-500'; return; }
                    const now = new Date();
                    const start = new Date(); const end = new Date();
                    const [sh, sm] = this.zeroWindowStart.split(':'); const [eh, em] = this.zeroWindowEnd.split(':');
                    start.setHours(parseInt(sh), parseInt(sm), 0, 0);
                    end.setHours(parseInt(eh), parseInt(em), 0, 0);
                    if (now < start) { this.zeroAlertText = 'Belum waktu lapor'; this.zeroAlertClass = 'text-slate-500'; return; }
                    if (now >= start && now <= end) {
                        const mins = Math.floor((now - start) / 60000);
                        if (mins >= 30) { this.zeroAlertText = 'Peringatan: belum lapor'; this.zeroAlertClass = 'text-amber-600'; }
                        else { this.zeroAlertText = 'Form aktif: harap lapor'; this.zeroAlertClass = 'text-indigo-600'; }
                        return;
                    }
                    if (now > end) { this.zeroAlertText = 'Lewat deadline: belum lapor'; this.zeroAlertClass = 'text-red-600'; }
                },
                async fetchZeroStatus() {
                    this.normalizeBaseUrl();
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=status');
                    const j = await r.json();
                    if (j.success) { this.zeroStatus = j.data; this.updateZeroAlerts(); }
                },
                openIncident() { this.showIncident = true; },
                async reportSafe() {
                    this.normalizeBaseUrl();
                    const now = new Date();
                    const [eh, em] = this.zeroWindowEnd.split(':');
                    const end = new Date(); end.setHours(parseInt(eh), parseInt(em), 0, 0);
                    if (now > end) { alert('Lewat deadline lapor'); return; }
                    const body = { period: 'daily', status: 'safe', incident_count: 0, details: null, request_id: this.genRequestId() };
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const j = await r.json();
                    if (j.success) { this.fetchZeroStatus(); this.showIncident = false; }
                },
                async submitIncident() {
                    this.normalizeBaseUrl();
                    const entries = Object.entries(this.incidentMap).filter(([id, st]) => !!st).map(([id, st]) => ({ student_id: Number(id), status: st }));
                    if (entries.length === 0) { alert('Pilih minimal 1 siswa'); return; }
                    const body = { period: 'daily', status: 'incident', incident_count: entries.length, details: entries, request_id: this.genRequestId() };
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const j = await r.json();
                    if (j.success) { this.fetchZeroStatus(); this.showIncident = false; this.incidentMap = {}; this.incidentQuery = ''; }
                },
                async reportWeeklySafe() {
                    this.normalizeBaseUrl();
                    const body = { period: 'weekly', status: 'safe', incident_count: 0, details: null, request_id: this.genRequestId() };
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const j = await r.json();
                    if (j.success) { alert('Laporan Mingguan: Aman'); }
                },
                async submitWeeklyIncident() {
                    this.normalizeBaseUrl();
                    const asset = String(this.weeklyAssetName || '').trim();
                    const desc = String(this.weeklyDescription || '').trim();
                    if (!asset) { alert('Isi nama aset'); return; }
                    const details = { asset, photo_url: this.weeklyPhotoUrl || null, description: desc };
                    const body = { period: 'weekly', status: 'incident', incident_count: 1, details, request_id: this.genRequestId() };
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const j = await r.json();
                    if (j.success) { this.weeklyAssetName = ''; this.weeklyPhotoUrl = ''; this.weeklyDescription = ''; alert('Kerusakan tercatat & tugas dibuat'); }
                },
                async reportMonthlySafe() {
                    this.normalizeBaseUrl();
                    const body = { period: 'monthly', status: 'safe', incident_count: 0, details: null, request_id: this.genRequestId() };
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const j = await r.json();
                    if (j.success) { alert('Laporan Bulanan: Aman'); }
                },
                async submitMonthlyRestock() {
                    this.normalizeBaseUrl();
                    const items = Object.keys(this.monthlySelected).filter(k => this.monthlySelected[k]);
                    if (items.length === 0) { alert('Pilih minimal satu item'); return; }
                    const details = { items, notes: this.monthlyNotes || '' };
                    const body = { period: 'monthly', status: 'incident', incident_count: items.length, details, request_id: this.genRequestId() };
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const j = await r.json();
                    if (j.success) { this.monthlySelected = { galon: false, pembersih: false, alat_bersih: false, p3k: false, lainnya: false }; this.monthlyNotes = ''; alert('Restock diajukan'); }
                },
                async fetchStudents() {
                    this.normalizeBaseUrl();
                    const r = await fetch(this.baseUrl + 'api/boarding.php?action=get_students&gender=all');
                    const j = await r.json();
                    this.students = j.success ? (j.data || []) : [];
                },
                async fetchTodayReports() {
                    this.normalizeBaseUrl();
                    const r = await fetch(this.baseUrl + 'api/zero_report.php?action=list_today');
                    const j = await r.json();
                    this.todayReports = j.success ? (j.data || []) : [];
                },
            loadChatMessages() {
                try {
                    const raw = localStorage.getItem('boarding_chat_messages') || '[]';
                    const arr = JSON.parse(raw);
                    this.chatMessages = Array.isArray(arr) ? arr : [];
                } catch(_) { this.chatMessages = []; }
            },
            saveChatMessages() {
                localStorage.setItem('boarding_chat_messages', JSON.stringify(this.chatMessages));
            },
            sendChat() {
                const t = String(this.chatInput || '').trim();
                if (!t) return;
                this.chatMessages.push({ id: Date.now(), text: t, ts: Date.now() });
                this.chatInput = '';
                this.saveChatMessages();
            },
            deleteChatMessage(idx) {
                this.chatMessages.splice(idx, 1);
                this.saveChatMessages();
            },
            formatChatTime(ts) {
                const d = new Date(ts);
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            },
            openMeeting(m) {
                try {
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
                } catch (_) {
                    this.meetingDetail = { ...m, attendees: [] };
                }
                this.showMeetingModal = true;
            },
        },
        mounted() {
            this.fetchMeetings();
            this.fetchRecentCount();
            this.fetchApprovals();
            this.fetchDocuments();
            this.fetchZeroStatus();
            this.fetchStudents();
            if (this.isManager) this.fetchTodayReports();
            this.loadTasks();
            this.loadChatMessages();
            setInterval(this.updateZeroAlerts, 10000);
        }
    }).mount('#app')
</script>

</body>
</html>
