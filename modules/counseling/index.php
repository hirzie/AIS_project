<?php
require_once '../../includes/guard.php';
require_login_and_module('counseling');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BK & Kesiswaan - SekolahOS</title>
    <?php
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? 80;
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $isLocalEnv = (stripos($host, 'localhost') !== false || $host === '127.0.0.1');
    $isTest = (!$isLocalEnv) && (preg_match('#^/AIStest/#i', $scriptName) || stripos($serverName, 'test') !== false);
    $forceProd = (isset($_GET['prod']) && $_GET['prod'] === '1') || (isset($_GET['force_prod']) && $_GET['force_prod'] === '1') || (isset($_COOKIE['force_prod']) && $_COOKIE['force_prod'] === '1');
    if ($isLocalEnv) {
        if ($port == 8000) {
            $baseUrl = '/';
        } else {
            if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
                $baseUrl = '/' . $m[1] . '/';
            } else {
                $baseUrl = '/AIS/';
            }
        }
    } else {
        if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
            $baseUrl = '/' . $m[1] . '/';
        } else {
            $baseUrl = '/';
        }
    }
    $assetsJsDir = dirname(__DIR__, 2) . '/assets/js/';
    $vueFile = ($forceProd || (!$isLocalEnv && !$isTest)) ? 'vue.global.prod.js' : 'vue.global.js';
    $vueLocalExists = file_exists($assetsJsDir . $vueFile);
    $vueCdnProd = 'https://unpkg.com/vue@3/dist/vue.global.prod.js';
    $tailwindCssPath = dirname(__DIR__, 2) . '/assets/css/tailwind.css';
    $useTailwindCdn = ($isLocalEnv || $isTest) || !file_exists($tailwindCssPath);
    ?>
    <?php if ($useTailwindCdn): ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php else: ?>
        <link href="<?php echo $baseUrl; ?>assets/css/tailwind.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <?php if ($vueLocalExists): ?>
        <script src="<?php echo $baseUrl; ?>assets/js/<?php echo $vueFile; ?>"></script>
    <?php else: ?>
        <?php if (!$isLocalEnv && !$isTest): ?>
            <script src="<?php echo $vueCdnProd; ?>"></script>
        <?php else: ?>
            <script src="<?php echo $baseUrl; ?>assets/js/vue.global.js"></script>
        <?php endif; ?>
    <?php endif; ?>
    <script>window.SKIP_GLOBAL_APP = true;</script>
    <script>
        (function(){ 
            var ow = console.warn;
            console.warn = function(){ 
                try {
                    var msg = arguments[0] ? String(arguments[0]) : '';
                    if (msg.indexOf('cdn.tailwindcss.com should not be used in production') !== -1) return;
                    if (msg.indexOf('You are running a development build of Vue') !== -1) return;
                    if (msg.indexOf('Tags with side effect') !== -1) return;
                } catch(e){}
                return ow.apply(console, arguments);
            };
        })();
    </script>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
        window.ALLOWED_MODULES = <?php echo json_encode($_SESSION['allowed_modules'] ?? []); ?>;
        window.USER_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
    </script>
    <style>
        [v-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .animate-fade { animation: fade 0.3s ease-out; }
        @keyframes fade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
<?php require_once '../../includes/counseling_header.php'; ?>
<div id="app" v-cloak class="flex flex-col h-screen">

    <div v-if="debugOn" class="fixed top-3 right-3 z-[10000] bg-slate-900/80 text-white rounded px-3 py-2 text-[11px] shadow-lg">
        <div>open: {{ !!modalOpen }}</div>
        <div>view: {{ modalView || '-' }}</div>
        <div>sel: {{ ticketSelected ? ticketSelected.id : '-' }}</div>
        <div>target: {{ statusChangeTarget ? statusChangeTarget.id : '-' }}</div>
        <div>body: {{ documentBodyClass }}</div>
        <div class="mt-2 flex gap-2">
            <button @click="modalView='ticket'; modalOpen=true" class="px-2 py-1 bg-pink-600 rounded">Open Ticket</button>
            <button @click="modalView='status'; statusChangeTarget=tickets[0]||null; modalOpen=true" class="px-2 py-1 bg-amber-600 rounded">Open Status</button>
            <button @click="closeModal" class="px-2 py-1 bg-slate-600 rounded">Close</button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-8 flex items-center justify-center bg-slate-50 relative">
        <!-- Background Decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
            <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-pink-100/50 blur-3xl"></div>
            <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-purple-100/50 blur-3xl"></div>
        </div>

        <div class="max-w-7xl w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative z-10">
            
            <!-- CARD 0: PENCARIAN CEPAT (PRIMARY) -->
            <div class="lg:col-span-2 group bg-white rounded-3xl p-8 shadow-sm border border-slate-100 hover:shadow-xl transition-all duration-300 relative z-50 flex flex-col justify-center">
                <div class="absolute top-0 right-0 w-32 h-32 bg-pink-50 rounded-bl-[120px] -mr-4 -mt-4 transition-transform group-hover:scale-110 opacity-50"></div>
                <div class="relative z-10">
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Pencarian Data Siswa</h2>
                    <p class="text-sm text-slate-500 mb-6">Akses cepat ke profil akademik, jadwal, log perpustakaan, dan catatan BK.</p>
                    <div class="relative">
                    <input type="text" v-model="searchQuery" @input="searchStudents" @keyup.enter="doSearch" placeholder="Ketik Nama atau NIS siswa..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-lg focus:ring-4 focus:ring-pink-100 focus:border-pink-500 outline-none transition-all pr-16 shadow-inner">
                    <button @click="doSearch" class="absolute right-3 top-1/2 -translate-y-1/2 w-12 h-12 bg-pink-600 text-white rounded-xl flex items-center justify-center hover:bg-pink-700 transition-colors shadow-lg shadow-pink-200">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- Live Search Dropdown -->
                    <div v-if="searchResults.length > 0" class="absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden animate-fade z-[100]">
                        <div v-for="s in searchResults" :key="s.id" @click="goToProfile(s)" class="p-4 bg-white hover:bg-pink-50 cursor-pointer border-b border-slate-50 last:border-0 transition-colors flex items-center gap-4 relative">
                            <div class="w-12 h-12 bg-pink-100 text-pink-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-slate-800 text-base leading-tight">{{ s.name }}</div>
                                <div class="text-xs font-medium text-slate-500 mt-1 flex items-center gap-2">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-600 font-mono">{{ s.identity_number }}</span>
                                    <span class="text-slate-300">|</span>
                                    <span>{{ s.class_name || 'Tanpa Kelas' }}</span>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 text-sm"></i>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- CARD 1: DATA AKADEMIK -->
            <a href="student_profile.php?tab=academic" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Data Akademik</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Informasi kelas, wali kelas, dan riwayat kenaikan.</p>
                </div>
            </a>

            <!-- CARD 2: JADWAL PELAJARAN -->
            <a href="student_profile.php?tab=schedule" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-teal-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-teal-600 group-hover:text-white transition-colors">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Jadwal Pelajaran</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Cek posisi siswa saat ini berdasarkan jadwal kelas.</p>
                </div>
            </a>

            <!-- CARD 3: KUNJUNGAN PERPUSTAKAAN -->
            <a href="student_profile.php?tab=library" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Kunjungan Perpus</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Pantau aktivitas literasi dan buku yang dibaca.</p>
                </div>
            </a>

            <!-- CARD 4: BUKU KASUS -->
            <a href="student_profile.php?tab=cases" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-red-600 group-hover:text-white transition-colors">
                        <i class="fas fa-book-dead"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Buku Kasus</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Catatan pelanggaran dan poin kedisiplinan.</p>
                </div>
            </a>

            <!-- CARD 5: KONSELING -->
            <a href="student_profile.php?tab=counseling" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Konseling</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Agenda dan laporan bimbingan konseling.</p>
                </div>
            </a>

            <!-- CARD: Ticket BK -->
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden lg:col-span-2">
                <div class="absolute top-0 right-0 w-24 h-24 bg-pink-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 bg-pink-100 text-pink-600 rounded-xl flex items-center justify-center text-2xl shadow-sm">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 leading-tight">Ticket BK</h3>
                                <div class="text-[11px] text-slate-500 leading-relaxed">Tiket dari eskalasi kejadian siswa</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="setTicketFilter('OPEN')" :class="ticketFilter==='OPEN' ? 'bg-pink-600 text-white' : 'bg-slate-100 text-slate-700'" class="px-3 py-1 rounded text-[11px] font-bold">OPEN</button>
                            <button @click="setTicketFilter('IN_PROGRESS')" :class="ticketFilter==='IN_PROGRESS' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700'" class="px-3 py-1 rounded text-[11px] font-bold">IN PROGRESS</button>
                            <button @click="setTicketFilter('CLOSED')" :class="ticketFilter==='CLOSED' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'" class="px-3 py-1 rounded text-[11px] font-bold">CLOSED</button>
                            <button @click="setTicketFilter('REOPEN')" :class="ticketFilter==='REOPEN' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" class="px-3 py-1 rounded text-[11px] font-bold">REOPEN</button>
                        </div>
                    </div>
                    <div v-if="tickets.length === 0" class="text-xs text-slate-500">Belum ada ticket.</div>
                    <div v-else class="space-y-2">
                        <div v-for="t in tickets" :key="t.id" class="p-3 border border-slate-200 rounded-lg flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-slate-800 truncate">{{ t.student_name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ t.title }}</div>
                                <div class="mt-1">
                                    <span :class="statusClass(t.status)" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ t.status }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button v-if="t.status==='OPEN'" @click="startTicket(t)" class="px-3 py-1 rounded bg-amber-600 text-white text-[11px] font-bold">Mulai</button>
                                <button v-if="t.status==='IN_PROGRESS'" @click="closeTicket(t)" class="px-3 py-1 rounded bg-emerald-600 text-white text-[11px] font-bold">Selesai</button>
                                <button v-if="t.status==='CLOSED'" @click="reopenTicket(t)" class="px-3 py-1 rounded bg-indigo-600 text-white text-[11px] font-bold">Reopen</button>
                                <button @click="openTicket(t)" class="px-3 py-1 rounded bg-slate-800 text-white text-[11px] font-bold">Detail</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CARD 6: PRESTASI -->
            <a href="student_profile.php?tab=achievements" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Prestasi Siswa</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Rekap pencapaian lomba dan penghargaan.</p>
                </div>
            </a>
            
            <!-- CARD 7: INPUT PRESTASI -->
            <a href="achievements_manage.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Input Prestasi</h3>
                    <p class="text-[11px] text-slate-500 leading-relaxed">Tambah prestasi siswa melalui form terpisah.</p>
                </div>
            </a>

        </div>
    </main>

<!-- TICKET MODAL -->
<teleport to="body">
<div v-show="modalOpen && modalView==='ticket'" :style="(modalOpen && modalView==='ticket') ? { display: 'flex' } : { display: 'none' }" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade flex flex-col max-h-[80vh]">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-pink-50">
            <h3 class="font-bold text-sm text-slate-800">Detail Ticket BK</h3>
            <button @click="closeModal" class="text-slate-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3 overflow-y-auto">
            <div class="flex items-center justify-between">
                <div class="font-bold text-slate-800 text-sm">{{ ticketSelected ? ticketSelected.student_name : '' }}</div>
                <span v-if="ticketSelected" :class="statusClass(ticketSelected.status)" class="px-2 py-0.5 rounded text-[10px] font-bold">{{ ticketSelected.status }}</span>
            </div>
            <div class="text-xs text-slate-500">{{ ticketSelected ? ticketSelected.title : '' }}</div>
            <div class="text-[11px] text-slate-500">NIS: {{ ticketSelected ? ticketSelected.nis : '' }}</div>
            <div class="mt-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Penanganan</label>
                <div class="space-y-2">
                    <div v-for="n in ticketNotes" :key="n.id" class="p-2 rounded border border-slate-200 text-[12px]">
                        <div class="font-bold text-slate-800">{{ n.user_name || 'Petugas BK' }}</div>
                        <div class="text-slate-700">{{ n.note }}</div>
                        <div class="text-[10px] text-slate-400">{{ new Date(n.created_at).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) }}</div>
                    </div>
                    <div v-if="ticketNotes.length===0" class="text-[12px] text-slate-500">Belum ada catatan.</div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <input v-model="noteInput" type="text" class="flex-1 border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Tambahkan catatan...">
                    <button @click="submitNote" class="px-3 py-2 rounded bg-pink-600 text-white text-[12px] font-bold">Tambah</button>
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
            <button @click="closeModal" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Tutup</button>
            <button v-if="ticketSelected && ticketSelected.status==='OPEN'" @click="startTicket(ticketSelected)" class="ml-2 px-3 py-1 text-[12px] font-bold bg-amber-600 text-white rounded">Mulai</button>
            <button v-if="ticketSelected && ticketSelected.status==='IN_PROGRESS'" @click="closeTicket(ticketSelected)" class="ml-2 px-3 py-1 text-[12px] font-bold bg-emerald-600 text-white rounded">Selesai</button>
            <button v-if="ticketSelected && ticketSelected.status==='CLOSED'" @click="reopenTicket(ticketSelected)" class="ml-2 px-3 py-1 text-[12px] font-bold bg-indigo-600 text-white rounded">Reopen</button>
        </div>
    </div>
</teleport>

<teleport to="body">
<div v-show="modalOpen && modalView==='status'" :style="(modalOpen && modalView==='status') ? { display: 'flex' } : { display: 'none' }" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade flex flex-col">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-amber-50">
            <h3 class="font-bold text-sm text-slate-800">Ubah Status Ticket</h3>
            <button @click="closeModal" class="text-slate-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <div class="text-xs text-slate-500">Ticket: {{ statusChangeTarget ? statusChangeTarget.title : '' }}</div>
            <div class="text-[11px] text-slate-500">Status baru: {{ statusChangeNextStatus }}</div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
                <textarea v-model="statusChangeNote" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Tambahkan catatan penanganan (opsional)"></textarea>
            </div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
            <button @click="closeModal" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
            <button @click="submitStatusChange" class="ml-2 px-3 py-1 text-[12px] font-bold bg-amber-600 text-white rounded">Simpan</button>
        </div>
    </div>
</div>
</teleport>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                searchQuery: '',
                searchResults: [],
                ticketsRecent: [],
                tickets: [],
                ticketFilter: 'OPEN',
                modalOpen: false,
                modalView: '',
                ticketSelected: null,
                ticketNotes: [],
                noteInput: '',
                statusChangeModal: false,
                statusChangeTarget: null,
                statusChangeNextStatus: '',
                statusChangeNote: '',
                debugOn: false,
                userRole: String((window.USER_ROLE || '')).toUpperCase()
            }
        },
        methods: {
            baseUrl() {
                let b = window.BASE_URL || '';
                if (!b) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    b = m ? `/${m[1]}/` : '/';
                }
                return b;
            },
            updateBodyOverflow() {
                const anyOpen = !!this.modalOpen;
                try { document.body.classList.toggle('overflow-hidden', anyOpen); } catch(_) {}
            },
            logState(tag) {
                try {
                    console.log('[BK]', tag, {
                        open: !!this.modalOpen,
                        view: this.modalView || '',
                        sel: this.ticketSelected ? this.ticketSelected.id : null,
                        target: this.statusChangeTarget ? this.statusChangeTarget.id : null,
                        body: document.body.className || ''
                    });
                } catch(_) {}
            },
            async searchStudents() {
                if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                const res = await fetch(`${this.baseUrl()}api/counseling.php?action=search_students&q=${encodeURIComponent(this.searchQuery)}`);
                const data = await res.json();
                if (data.success) this.searchResults = data.data;
            },
            doSearch() {
                if (this.searchQuery.trim()) {
                    window.location.href = 'student_profile.php?q=' + encodeURIComponent(this.searchQuery);
                }
            },
            goToProfile(s) {
                window.location.href = 'student_profile.php?q=' + encodeURIComponent(s.identity_number);
            },
            statusClass(s) {
                const st = String(s || '').toUpperCase();
                if (st === 'OPEN') return 'bg-pink-100 text-pink-700';
                if (st === 'IN_PROGRESS') return 'bg-amber-100 text-amber-700';
                if (st === 'REOPEN') return 'bg-indigo-100 text-indigo-700';
                return 'bg-emerald-100 text-emerald-700';
            },
            setTicketFilter(s) {
                this.ticketFilter = s;
                this.fetchTickets();
            },
            async fetchTickets() {
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=list_tickets&status=${encodeURIComponent(this.ticketFilter)}`);
                    const data = await res.json();
                    if (data.success) {
                        this.tickets = data.data || [];
                        this.ticketsRecent = this.tickets.slice(0, 10);
                    } else {
                        this.tickets = [];
                        this.ticketsRecent = [];
                    }
                } catch (e) {
                    this.tickets = [];
                    this.ticketsRecent = [];
                }
            },
            async updateTicketStatus(id, status, note) {
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=update_ticket_status`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, status, note })
                    });
                    const data = await res.json();
                    if (data && data.success) {
                        if (this.ticketSelected && this.ticketSelected.id === id) {
                            this.ticketSelected = Object.assign({}, this.ticketSelected, { status });
                        }
                        if (this.statusChangeTarget && this.statusChangeTarget.id === id) {
                            this.statusChangeTarget = Object.assign({}, this.statusChangeTarget, { status });
                        }
                        this.fetchTickets();
                    } else {
                        alert('Gagal update status');
                    }
                } catch (_) { alert('Gagal update status'); }
            },
            openStatusChange(t, nextStatus) {
                this.statusChangeTarget = t;
                this.statusChangeNextStatus = nextStatus;
                this.statusChangeNote = '';
                this.modalView = 'status';
                this.modalOpen = true;
                this.logState('openStatusChange');
            },
            submitStatusChange() {
                if (!this.statusChangeTarget) return;
                const id = this.statusChangeTarget.id;
                const st = this.statusChangeNextStatus;
                const note = (this.statusChangeNote || '').trim();
                this.updateTicketStatus(id, st, note);
                this.statusChangeTarget = null;
                this.statusChangeNextStatus = '';
                this.statusChangeNote = '';
                this.modalOpen = false;
                this.modalView = '';
                this.logState('submitStatusChange');
            },
            startTicket(t) { this.openStatusChange(t, 'IN_PROGRESS'); },
            closeTicket(t) { this.openStatusChange(t, 'CLOSED'); },
            reopenTicket(t) { this.openStatusChange(t, 'REOPEN'); },
            async openTicket(t) {
                this.ticketSelected = t;
                this.ticketNotes = [];
                this.modalView = 'ticket';
                this.modalOpen = true;
                this.logState('openTicket');
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=list_ticket_notes&ticket_id=${encodeURIComponent(t.id)}`);
                    const data = await res.json();
                    if (data.success) this.ticketNotes = data.data || [];
                } catch (_) { this.ticketNotes = []; }
            },
            closeModal() {
                this.modalOpen = false;
                this.modalView = '';
                this.ticketSelected = null;
                this.ticketNotes = [];
                this.noteInput = '';
                this.statusChangeTarget = null;
                this.statusChangeNextStatus = '';
                this.statusChangeNote = '';
                this.logState('closeModal');
            },
            async submitNote() {
                if (!this.ticketSelected || !this.ticketSelected.id) return;
                const note = (this.noteInput || '').trim();
                if (!note) return;
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=add_ticket_note`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ticket_id: this.ticketSelected.id, note })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.ticketNotes = data.data || [];
                        this.noteInput = '';
                    } else {
                        alert('Gagal menambah catatan');
                    }
                } catch (_) { alert('Gagal menambah catatan'); }
            }
        },
        computed: {
            documentBodyClass() {
                try { return document.body.className || ''; } catch(_) { return ''; }
            }
        },
        watch: {
            modalOpen() { this.updateBodyOverflow(); }
        },
        mounted() {
            this.fetchTickets();
            this.updateBodyOverflow();
            try {
                const sp = new URLSearchParams(window.location.search || '');
                this.debugOn = sp.get('debug') === '1';
            } catch(_) {}
            try {
                if (this.debugOn) console.log('[BK] debug on');
            } catch(_) {}
            }
    }).mount('#app')
</script>
</body>
</html>
