<?php
require_once '../../includes/guard.php';
require_login_and_module(); // Allow login first, check permissions manually below
ais_init_session();

// Manual Access Check: Allow ACADEMIC, ADMIN, SUPERADMIN, or WORKSPACE (Teacher/Principal)
$allowed = $_SESSION['allowed_modules'] ?? [];
$hasAccess = false;

// Check standard roles
if (!empty($allowed['academic']) || !empty($allowed['core'])) {
    $hasAccess = true;
} 
// Check workspace access (Teachers, Principals)
elseif (!empty($allowed['workspace'])) {
    $hasAccess = true;
}

if (!$hasAccess) {
    header("Location: " . __ais_redirect_prefix() . "index.php?noaccess=academic_class_detail");
    exit;
}
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Auto-detect Homeroom Class for Walikelas
$homeroomClassId = null;
$personId = $_SESSION['person_id'] ?? 0;
if ($personId) {
    // We need to be careful with units if possible, but usually a teacher has one homeroom class
    // If multiple, we pick the first one.
    $stmtHR = $pdo->prepare("SELECT id FROM acad_classes WHERE homeroom_teacher_id = ? LIMIT 1");
    $stmtHR->execute([$personId]);
    $homeroomClassId = $stmtHR->fetchColumn();
}

// SERVER-SIDE PRE-FETCHING (HYBRID RENDERING)
// Ambil data detail kelas & siswa langsung di PHP agar tidak perlu fetch API lagi (mengurangi LCP)
$initialData = [
    'class' => null,
    'students' => []
];

$classId = $_GET['id'] ?? $homeroomClassId;

if ($classId) {
    // 1. Get Class Detail
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            l.name as level_name, 
            p.name as homeroom,
            u.name as unit_name,
            u.code as unit_code
        FROM acad_classes c 
        LEFT JOIN acad_class_levels l ON c.level_id = l.id 
        LEFT JOIN core_units u ON l.unit_id = u.id
        LEFT JOIN core_people p ON c.homeroom_teacher_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$classId]);
    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($classData) {
        $initialData['class'] = $classData;
        
        // 2. Get Students
        $stmtS = $pdo->prepare("
            SELECT 
                p.id,
                COALESCE(p.identity_number, '') as identity_number,
                COALESCE(p.name, '') as name,
                COALESCE(p.gender, '') as gender,
                COALESCE(sd.nisn, '') as nisn,
                COALESCE(sd.birth_place, '') as birth_place,
                sd.birth_date,
                COALESCE(sd.school_origin, '') as school_origin,
                sc.status
            FROM acad_student_classes sc
            JOIN core_people p ON sc.student_id = p.id
            LEFT JOIN acad_student_details sd ON p.id = sd.student_id
            WHERE sc.class_id = ? AND sc.status = 'ACTIVE' AND p.type = 'STUDENT'
            ORDER BY p.name ASC
        ");
        $stmtS->execute([$classId]);
        $initialData['students'] = $stmtS->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<script>
    window.USE_GLOBAL_APP = false;
    window.SKIP_GLOBAL_APP = true;
    window.HOMEROOM_CLASS_ID = <?= json_encode($homeroomClassId) ?>;
    window.INITIAL_DETAIL = <?= json_encode($initialData) ?>;
</script>

<style>
    [v-cloak] { display: none !important; }
    /* Extra safeguard for specific elements */
    .v-cloak-hidden { display: none !important; }
</style>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto">
            
            <div class="mb-4">
                <a href="classes.php" class="inline-flex items-center text-slate-500 hover:text-blue-600 transition-colors font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Kelas
                </a>
            </div>

            <!-- HEADER KELAS (PHP RENDERED - LCP OPTIMIZED) -->
            <?php if ($initialData['class']): $cls = $initialData['class']; ?>
            <div class="flex items-center gap-3 md:gap-4 mb-4 md:mb-6">
                <div class="w-10 h-10 md:w-16 md:h-16 bg-blue-600 rounded-lg md:rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200 flex-shrink-0">
                    <i class="fas fa-chalkboard-teacher text-lg md:text-3xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-3">
                        <h2 class="text-lg md:text-2xl font-bold text-slate-800 truncate">Detail Kelas <?= htmlspecialchars($cls['name']) ?></h2>
                        <span class="self-start md:self-auto bg-slate-100 text-slate-600 px-2 py-0.5 md:py-1 rounded text-[10px] md:text-xs font-bold uppercase whitespace-nowrap"><?= htmlspecialchars($cls['level_name']) ?></span>
                    </div>
                    <p class="text-xs md:text-base text-slate-500 truncate"><i class="fas fa-user-tie mr-1"></i> Wali Kelas: <?= htmlspecialchars($cls['homeroom'] ?? 'Belum ditentukan') ?></p>
                </div>
                <div class="ml-auto flex gap-2">
                    <!-- Edit Button Trigger (Vue Controlled) -->
                    <button @click="openEditClassModal" class="bg-white border border-slate-200 text-slate-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-50 shadow-sm">
                        <i class="fas fa-edit md:mr-1"></i> <span class="hidden md:inline">Edit</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- CONTENT: VUE (Tabs & Table) -->
            <div id="vue-content" v-if="selectedClass" v-cloak>
                <!-- Tabs -->
                <div class="flex border-b border-slate-200 mb-6">
                    <button @click="activeTab = 'students'" :class="activeTab === 'students' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-6 py-3 text-sm font-bold border-b-2 transition-colors">Daftar Siswa</button>
                    <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-6 py-3 text-sm font-bold border-b-2 transition-colors">Jadwal Pelajaran</button>
                    <button @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-6 py-3 text-sm font-bold border-b-2 transition-colors">Absensi</button>
                </div>

                

                <!-- TAB: SISWA -->
                <div v-if="activeTab === 'students'">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" v-model="searchQuery" placeholder="Cari siswa..." class="pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm w-64 focus:outline-none focus:border-blue-500">
                            </div>
                            <span class="text-sm text-slate-500 font-medium bg-slate-100 px-3 py-2 rounded-lg">{{ filteredClassMembers ? filteredClassMembers.length : 0 }} Siswa</span>
                        </div>
                        <div class="hidden md:flex gap-2">
                            <button @click="openImportModal(selectedClass.id)" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-file-import mr-2"></i>Import CSV</button>
                            <button @click="exportStudents" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-emerald-700"><i class="fas fa-file-export mr-2"></i>Export Excel</button>
                            <button @click="openAddStudentModal(selectedClass)" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700"><i class="fas fa-plus mr-2"></i>Tambah Siswa</button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs md:text-sm">
                                <tr>
                                    <th class="px-3 md:px-6 py-3 w-10 md:w-16 text-center">No</th>
                                    <th class="hidden md:table-cell px-6 py-3">NIS</th>
                                    <th class="hidden md:table-cell px-6 py-3">NISN</th>
                                    <th class="px-3 md:px-6 py-3">Nama Siswa</th>
                                    <th class="hidden md:table-cell px-6 py-3">L/P</th>
                                    <th class="hidden md:table-cell px-6 py-3">Tempat, Tgl Lahir</th>
                                    <th class="hidden md:table-cell px-6 py-3">Asal Sekolah</th>
                                    <th class="px-3 md:px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(student, index) in filteredClassMembers" :key="student.id" class="hover:bg-slate-50">
                                    <td class="px-3 md:px-6 py-3 md:py-4 text-center text-slate-500 text-xs md:text-sm">{{ index + 1 }}</td>
                                    <td class="hidden md:table-cell px-6 py-4 font-mono text-slate-600">{{ student.identity_number }}</td>
                                    <td class="hidden md:table-cell px-6 py-4 font-mono text-slate-600">{{ student.nisn || '-' }}</td>
                                    <td class="px-3 md:px-6 py-3 md:py-4">
                                        <div class="font-bold text-slate-800 text-sm">{{ student.name }}</div>
                                        <!-- Mobile View: Stacked Info -->
                                        <div class="block md:hidden text-[11px] text-slate-500 mt-0.5">
                                            <span class="font-mono text-slate-600">{{ student.identity_number }}</span>
                                            <span class="mx-1">•</span>
                                            <span :class="student.gender === 'L' ? 'text-blue-600' : 'text-pink-600'" class="font-bold">{{ student.gender }}</span>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-6 py-4">{{ student.gender }}</td>
                                    <td class="hidden md:table-cell px-6 py-4 text-slate-600">
                                        <div class="text-xs">
                                            {{ student.birth_place || '-' }}, <br>
                                            {{ student.birth_date ? new Date(student.birth_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-6 py-4 text-slate-600 text-xs">{{ student.school_origin || '-' }}</td>
                                    <td class="px-3 md:px-6 py-3 md:py-4 text-right">
                                        <div class="flex items-center justify-end gap-1 md:gap-2">
                                            <button @click="openStudentModal(student)" class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-blue-100 hover:text-blue-600 transition-colors flex items-center justify-center" title="Lihat Detail"><i class="fas fa-eye text-xs md:text-sm"></i></button>
                                            <button @click="openStudentModal(student)" class="hidden md:flex w-7 h-7 md:w-8 md:h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-orange-100 hover:text-orange-600 transition-colors items-center justify-center" title="Edit Data"><i class="fas fa-pencil-alt text-xs md:text-sm"></i></button>
                                            <button @click="removeStudentFromClass(student)" class="hidden md:flex w-7 h-7 md:w-8 md:h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-100 hover:text-red-600 transition-colors items-center justify-center" title="Keluarkan dari Kelas"><i class="fas fa-times text-xs md:text-sm"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!filteredClassMembers || filteredClassMembers.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                        <i class="fas fa-users text-3xl mb-3 text-slate-300"></i>
                                        <p>Tidak ada data siswa ditemukan.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB: JADWAL -->
                <div v-if="activeTab === 'schedule'">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-32 border-r border-slate-200">Waktu</th>
                                    <th v-for="day in days" :key="day" class="px-4 py-3 text-center border-r border-slate-200 min-w-[140px]">
                                        {{ day }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="slot in activeTimeslots" :key="slot.id">
                                    <td class="px-4 py-2 font-mono text-xs text-slate-500 border-r border-slate-200 bg-slate-50/50">
                                        <div class="font-bold">{{ slot.start }} - {{ slot.end }}</div>
                                        <div class="text-[10px]">{{ slot.name }}</div>
                                    </td>
                                    
                                    <!-- Jika Istirahat -->
                                    <td v-if="slot.isBreak" :colspan="days.length" class="bg-amber-50 text-center py-2 text-amber-600 font-bold text-xs tracking-widest uppercase">
                                        ISTIRAHAT
                                    </td>

                                    <!-- Jika Jam Pelajaran -->
                                    <template v-else>
                                        <td v-for="day in days" :key="day + slot.id" class="p-1 border-r border-slate-100 align-top h-16">
                                            <div v-if="getScheduleItem(day, slot.start)" 
                                                class="bg-blue-50 border border-blue-100 p-2 rounded h-full">
                                                <div class="font-bold text-blue-800 text-xs mb-1 line-clamp-2">
                                                    {{ getScheduleItem(day, slot.start).subject_name }}
                                                </div>
                                                <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                                    <i class="fas fa-user-tie text-[9px]"></i>
                                                    <span class="truncate">{{ getScheduleItem(day, slot.start).teacher_name }}</span>
                                                </div>
                                            </div>
                                            <div v-else class="h-full flex items-center justify-center text-slate-300 text-[10px]">
                                                -
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Empty State if No Timeslots -->
                        <div v-if="activeTimeslots.length === 0" class="p-8 text-center text-slate-400">
                            <i class="fas fa-clock text-4xl mb-3 text-slate-300"></i>
                            <p>Belum ada slot waktu pelajaran yang diatur untuk unit ini.</p>
                        </div>
                    </div>
                </div>

                 <!-- TAB: ABSENSI -->
                 <div v-if="activeTab === 'attendance'">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700">Rekap Presensi Bulanan</h3>
                            <a :href="'attendance_batch.php?class_id=' + selectedClass.id" class="text-xs font-bold text-blue-600 hover:text-blue-700">
                                <i class="fas fa-external-link-alt mr-1"></i> Kelola Presensi
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="px-6 py-3">Periode</th>
                                        <th class="px-6 py-3 text-center">Hari Aktif</th>
                                        <th class="px-6 py-3 text-center">Total Siswa</th>
                                        <th class="px-6 py-3 text-center">Izin (I)</th>
                                        <th class="px-6 py-3 text-center">Sakit (S)</th>
                                        <th class="px-6 py-3 text-center">Alfa (A)</th>
                                        <th class="px-6 py-3 text-center">Cuti (C)</th>
                                        <th class="px-6 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="item in classAttendanceSummary" :key="item.month + '-' + item.year" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-slate-700">
                                            {{ monthNames[item.month-1] }} {{ item.year }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded font-bold">{{ item.active_days }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center text-slate-500">{{ item.total_students }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-amber-600 font-bold">{{ item.total_izin }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-blue-600 font-bold">{{ item.total_sakit }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-red-600 font-bold">{{ item.total_alfa }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-purple-600 font-bold">{{ item.total_cuti }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a :href="'attendance_batch.php?class_id=' + selectedClass.id + '&month=' + item.month + '&year=' + item.year" 
                                               class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="!classAttendanceSummary || classAttendanceSummary.length === 0">
                                        <td colspan="8" class="px-6 py-12 text-center text-slate-400 italic">
                                            <i class="fas fa-clipboard-check text-3xl mb-3 text-slate-200 block"></i>
                                            Belum ada rekap presensi untuk kelas ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Vue Content -->

            <!-- MODALS (Moved inside v-cloak to prevent early render) -->
            <div v-cloak>
                <?php include '../../includes/modals/student_modal.php'; ?>
                <?php include '../../includes/modals/import_student_modal.php'; ?>
                <?php include '../../includes/modals/confirm_modal.php'; ?>

                <!-- MODAL: EDIT KELAS (Inline) -->
                <div v-if="showEditClassModal" style="display: none;" :style="{ display: showEditClassModal ? 'flex' : 'none' }" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
                    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-fade">
                        <h3 class="text-lg font-bold mb-4">Edit Kelas</h3>
                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Nama Kelas</label>
                                <input v-model="editClassData.name" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Tingkat (Level)</label>
                                <select v-model="editClassData.level_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">
                                    <option v-for="lvl in activeLevels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Wali Kelas</label>
                                <select v-model="editClassData.homeroom_teacher_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">
                                    <option value="">-- Pilih Wali Kelas --</option>
                                    <option v-for="p in staffList" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Kapasitas</label>
                                <input v-model="editClassData.capacity" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Urutan (Sort Order)</label>
                                <input v-model="editClassData.sort_order" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="0">
                                <p class="text-xs text-slate-400 mt-1">Angka lebih kecil tampil lebih dulu.</p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button @click="showEditClassModal = false" class="text-slate-500 hover:text-slate-700 font-medium text-sm">Batal</button>
                            <button @click="updateClass" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 font-bold">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
    </main>
</div>

<script>
// INLINE MIXINS TO AVOID MODULE LOADING ISSUES
const studentMixin = {
    data() {
        return {
            students: [],
            showStudentModal: false,
            studentForm: {
                id: null,
                // Core
                nis: '', name: '', gender: 'L', class_id: '', status: 'ACTIVE',
                // Details
                nisn: '', nik: '', prev_exam_number: '', pin: '', nickname: '', admission_year: '',
                school_origin: '', diploma_number: '', diploma_date: '',
                birth_place: '', birth_date: '', special_needs: '', health_history: '',
                weight: '', height: '', blood_type: '', postal_code: '', distance_to_school: '',
                mobile_phone: '', daily_language: '', ethnicity: '', religion: '', citizenship: '',
                child_order: '', siblings_total: '', child_status: '', siblings_biological: '', siblings_step: '',
                father_name: '', father_status: '', father_birth_place: '', father_birth_date: '',
                father_email: '', father_pin: '', father_education: '', father_job: '', father_income: '',
                mother_name: '', mother_status: '', mother_birth_place: '', mother_birth_date: '',
                mother_email: '', mother_pin: '', mother_education: '', mother_job: '', mother_income: '',
                guardian_name: '', guardian_address: '', guardian_phone: '', guardian_mobile_1: '',
                guardian_mobile_2: '', guardian_mobile_3: '', hobbies: '', remarks: '',
                // Custom
                custom_values: {}
            },
            activeTabStudent: 'data_diri', // data_diri, orang_tua, wali, lainnya, custom
            customFieldsDef: [], // Definitions from DB
            
            // Import Modal
            showImportModal: false,
            importFile: null,
            importTargetClassId: ''
        };
    },
    mounted() {
        // Fix for mobile modal auto-closing issue
        document.addEventListener('touchend', (e) => {
            if (this.showImportModal) {
                const modalContent = document.querySelector('.bg-white.rounded-xl.shadow-xl.w-full.max-w-md');
                if (modalContent && !modalContent.contains(e.target) && e.target.closest('.fixed.inset-0')) {
                } else {
                    // e.stopPropagation(); 
                }
            }
        });
    },
    methods: {
        openAddStudentModal(cls = null) {
            console.log("openAddStudentModal called", cls);
            try {
                this.studentForm = {
                    id: null,
                    name: '',
                    nickname: '',
                    nis: '',
                    nisn: '',
                    nik: '',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    religion: '',
                    daily_language: '',
                    class_id: cls ? cls.id : (this.selectedClassId || ''),
                    status: 'ACTIVE',
                    address: '',
                    father_name: '', father_pin: '', father_birth_date: '', father_education: '', father_job: '', father_income: '',
                    mother_name: '', mother_pin: '', mother_birth_date: '', mother_education: '', mother_job: '', mother_income: '',
                    guardian_name: '', guardian_address: '', guardian_phone: '', guardian_mobile_1: '',
                    guardian_mobile_2: '', guardian_mobile_3: '', hobbies: '', remarks: '',
                    custom_values: {}
                };
                
                if (typeof this.fetchCustomFields === 'function') {
                    this.fetchCustomFields().then(() => {
                        if (Array.isArray(this.customFieldsDef)) {
                            this.customFieldsDef.forEach(f => {
                                if (this.studentForm.custom_values[f.field_key] === undefined) {
                                    this.studentForm.custom_values[f.field_key] = '';
                                }
                            });
                        }
                    });
                }

                this.activeTabStudent = 'data_diri';
                this.showStudentModal = true;
            } catch (e) {
                console.error("Error opening Add Student Modal:", e);
                alert("Terjadi kesalahan saat membuka form siswa.");
            }
        },
        async fetchStudents(unit) {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + `api/get_students.php?unit=${unit}`);
                this.students = await response.json();
            } catch (error) {
                console.error("Gagal mengambil data siswa:", error);
            }
        },
        async fetchCustomFields() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_references.php?action=get_fields&entity_type=STUDENT');
                const data = await response.json();
                if (data.success) {
                    this.customFieldsDef = Array.isArray(data.data) ? data.data : [];
                }
            } catch (error) {
                console.error("Gagal mengambil custom fields:", error);
            }
        },
        openStudentModal(student = null) {
            console.log("openStudentModal called", student);
            try {
                this.showStudentModal = true;
                this.activeTabStudent = 'data_diri'; // Reset tab
                if (typeof this.fetchCustomFields === 'function') {
                    this.fetchCustomFields().then(() => {
                        if (Array.isArray(this.customFieldsDef)) {
                            this.customFieldsDef.forEach(f => {
                                if (this.studentForm.custom_values[f.field_key] === undefined) {
                                    this.studentForm.custom_values[f.field_key] = '';
                                }
                            });
                        }
                    });
                }

                if (student) {
                    const studentId = student.id;
                    this.studentForm.id = studentId;
                    
                    this.studentForm.name = student.name || '';
                    this.studentForm.nis = student.nis || student.identity_number || '';
                    this.studentForm.gender = student.gender || 'L';
                    this.studentForm.status = student.status || 'ACTIVE';
                    this.studentForm.custom_values = {};
                    
                    const keys = [
                        'nisn', 'nik', 'prev_exam_number', 'pin', 'nickname', 'admission_year',
                        'school_origin', 'diploma_number', 'diploma_date',
                        'birth_place', 'birth_date', 'special_needs', 'health_history',
                        'weight', 'height', 'blood_type', 'postal_code', 'distance_to_school',
                        'mobile_phone', 'daily_language', 'ethnicity', 'religion', 'citizenship',
                        'child_order', 'siblings_total', 'child_status', 'siblings_biological', 'siblings_step',
                        'father_name', 'father_status', 'father_birth_place', 'father_birth_date',
                        'father_email', 'father_pin', 'father_education', 'father_job', 'father_income',
                        'mother_name', 'mother_status', 'mother_birth_place', 'mother_birth_date',
                        'mother_email', 'mother_pin', 'mother_education', 'mother_job', 'mother_income',
                        'guardian_name', 'guardian_address', 'guardian_phone', 'guardian_mobile_1',
                        'guardian_mobile_2', 'guardian_mobile_3', 'hobbies', 'remarks'
                    ];
                    keys.forEach(k => { this.studentForm[k] = ''; });

                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    fetch(baseUrl + `api/get_student_detail.php?id=${studentId}`)
                        .then(res => res.json())
                        .then(data => {
                            for (const key in this.studentForm) {
                                if (data.hasOwnProperty(key) && data[key] !== null && data[key] !== 'null') {
                                    this.studentForm[key] = data[key];
                                }
                            }
                            if (!data.class_id && student.class_id) {
                                this.studentForm.class_id = student.class_id;
                            }
                            const cv = data.custom_values;
                            this.studentForm.custom_values = (Array.isArray(cv) && cv.length === 0) ? {} : (cv || {});
                        })
                        .catch(e => {
                            console.error("Gagal ambil detail siswa", e);
                            alert("Gagal mengambil data detail siswa.");
                        });

                } else {
                    this.openAddStudentModal();
                }
            } catch (e) {
                console.error("Error in openStudentModal:", e);
            }
        },
        async saveStudent() {
            if (this.currentPosition === 'wali') {
                 alert('Anda hanya memiliki akses lihat (Read-Only).');
                 return;
            }
            if (this.getUnitId && !this.getUnitId(this.currentUnit)) {
                alert('Silakan pilih unit spesifik (TK/SD/SMP/SMA) terlebih dahulu.');
                return;
            }
            try {
                const payload = {
                    ...this.studentForm,
                    unit_id: (this.getUnitId ? this.getUnitId(this.currentUnit) : 1), 
                    action: this.studentForm.id ? 'update' : 'create'
                };
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_student.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.showStudentModal = false;
                    
                    if (this.selectedClassId && typeof this.loadClassDetailDirectly === 'function') {
                        this.loadClassDetailDirectly(this.selectedClassId);
                    } else if (this.fetchStudents) {
                        this.fetchStudents(this.currentUnit);
                    }
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error("Error saving student:", e); }
        },
        async deleteStudent(student) {
            if (!confirm(`Hapus siswa ${student.name}?`)) return;
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_student.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: student.id, action: 'delete' })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    if (this.fetchStudents) this.fetchStudents(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        openImportModal(classId = '') {
            let target = classId;
            if (!target && this.selectedClass) target = this.selectedClass.id;
            if (!target) target = this.selectedClassId;

            this.importTargetClassId = target;
            this.showImportModal = true;
            this.importFile = null;
        },
        handleFileUpload(event) {
            this.importFile = event.target.files[0];
        },
        async uploadStudentCsv() {
            if (!this.importFile) { alert('Pilih file terlebih dahulu!'); return; }
            
            let unitId = (this.getUnitId ? this.getUnitId(this.currentUnit) : null);
            if ((!unitId || unitId === 'all') && this.selectedClass && this.selectedClass.unit_id) {
                unitId = this.selectedClass.unit_id;
            }

            if (!unitId && !this.importTargetClassId) {
                alert('Unit tidak valid. Pastikan Anda memilih unit spesifik.'); 
                return; 
            }

            try {
                const worksheet = await this.readExcelFile(this.importFile);
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });
                
                if (!jsonData || jsonData.length === 0) {
                    alert("File kosong atau format tidak terbaca.");
                    return;
                }

                const normalizedData = jsonData.map(row => {
                    const newRow = {};
                    for (const key in row) {
                        newRow[key.trim()] = row[key]; 
                    }
                    return newRow;
                });

                const payload = {
                    data: normalizedData,
                    unit_id: unitId,
                    class_id: this.importTargetClassId
                };

                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const cleanBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                
                const response = await fetch(cleanBaseUrl + 'api/import_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (jsonError) {
                    console.error("Server Raw Response:", text);
                    throw new Error("Server Error: Respon tidak valid (bukan JSON). Cek console untuk detail.");
                }
                
                if (result.success) {
                    alert(result.message);
                    if (result.errors && result.errors.length > 0) {
                        alert('Peringatan (Data Dilewati):\n' + result.errors.slice(0, 10).join('\n') + (result.errors.length > 10 ? '\n...dan lainnya' : ''));
                    }
                    this.showImportModal = false;
                    
                    if (this.importTargetClassId && this.selectedClass) {
                        if (this.openClassDetail) this.openClassDetail(this.selectedClass);
                        else if (this.loadClassDetailDirectly) this.loadClassDetailDirectly(this.selectedClass.id);
                        else location.reload(); 
                    } else {
                        if (this.fetchStudents) this.fetchStudents(this.currentUnit);
                    }
                } else {
                    alert('Gagal: ' + (result.error || 'Unknown error'));
                }
            } catch (e) { 
                console.error(e); 
                alert('Terjadi kesalahan: ' + e.message); 
            }
        },
        readExcelFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        resolve(worksheet);
                    } catch (err) {
                        reject(err);
                    }
                };
                reader.onerror = reject;
                reader.readAsArrayBuffer(file);
            });
        },
        async removeStudentFromClass(student) {
            if (!student) return;
            if (!confirm(`Keluarkan ${student.name} dari kelas ini?`)) return;
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const classId = this.selectedClass ? this.selectedClass.id : (this.selectedClassId || '');
                
                if (!classId) {
                    alert("ID Kelas tidak ditemukan.");
                    return;
                }

                const response = await fetch(baseUrl + 'api/manage_student.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ 
                        student_id: student.id,
                        class_id: classId, 
                        action: 'remove_from_class' 
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    if (this.selectedClassId && typeof this.loadClassDetailDirectly === 'function') {
                        this.loadClassDetailDirectly(this.selectedClassId);
                    } else if (this.selectedClass && typeof this.openClassDetail === 'function') {
                        this.openClassDetail(this.selectedClass);
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error("Error removing student:", e); }
        }
    }
};

const adminMixin = {
    data() {
        return {
            schoolSettings: Object.assign({
                name: '',
                npsn: '',
                address: '',
                phone: '',
                email: '',
                logo_url: '',
                footer_text: '',
                wa_api_url: '',
                wa_api_token: '',
                wa_security_target: '',
                google_gemini_api_key: ''
            }, (window.INITIAL_SETTINGS || {})),
            userList: [],
            userForm: {
                id: null,
                username: '',
                password: '',
                role: 'ADMIN',
                status: 'ACTIVE',
                email: '',
                access_modules: [],
                people_id: null
            },
            staffLookup: [],
            showUserModal: false,
            isUserEdit: false,
            adminLoading: false,
            unitList: [],
            unitForm: { id: null, name: '', unit_level: 'SD', prefix: '', headmaster: '', address: '' },
            showUnitModal: false,
            isUnitEdit: false,
            confirmModal: {
                show: false,
                title: '',
                message: '',
                onConfirm: null
            }
        };
    },
    methods: {
        confirmAction(title, message, callback) {
            this.confirmModal = {
                show: true,
                title: title,
                message: message,
                onConfirm: callback
            };
        },
        executeConfirm() {
            if (this.confirmModal.onConfirm) {
                this.confirmModal.onConfirm();
            }
            this.confirmModal.show = false;
        },
        async fetchSettings() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_settings.php');
                const data = await response.json();
                if (data) {
                    this.schoolSettings = { ...this.schoolSettings, ...data };
                }
                this.fetchUnits(); 
            } catch (error) {
                console.error('Error fetching settings:', error);
            }
        },
        async saveSettings() {
            this.confirmAction('Simpan Pengaturan?', 'Apakah Anda yakin ingin menyimpan perubahan pengaturan sekolah?', async () => {
                this.adminLoading = true;
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const formData = new FormData();
                    for (const key in this.schoolSettings) {
                        if (key !== 'logo_file') {
                            formData.append(key, this.schoolSettings[key] || '');
                        }
                    }
                    if (this.schoolSettings.logo_file) {
                        formData.append('logo_file', this.schoolSettings.logo_file);
                    }

                    const response = await fetch(baseUrl + 'api/save_settings.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Pengaturan berhasil disimpan!');
                        if (result.logo_url) {
                            this.schoolSettings.logo_url = result.logo_url;
                            if (this.$root) this.$root.schoolLogo = result.logo_url;
                        }
                    } else {
                        alert('Gagal menyimpan: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error saving settings:', error);
                    alert('Terjadi kesalahan sistem.');
                } finally {
                    this.adminLoading = false;
                }
            });
        },
        async testWaNotification() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const msg = (this.waTestMessage || '').trim();
                if (!msg) {
                    alert('Isi pesan uji WA terlebih dahulu');
                    return;
                }
                const target = (this.schoolSettings.wa_security_target || '').trim();
                const res = await fetch(baseUrl + 'api/security.php?action=send_wa_test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg, target })
                });
                const j = await res.json();
                alert(j.success ? 'Uji kirim WA berhasil' : ('Uji kirim WA gagal: ' + (j.message || '')));
            } catch (e) {
                alert('Terjadi kesalahan sistem saat mengirim WA');
                console.error(e);
            }
        },
        handleLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.schoolSettings.logo_file = file;
                this.schoolSettings.logo_url = URL.createObjectURL(file);
            }
        },
        async fetchUnits() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_units.php');
                this.unitList = await response.json();
            } catch (error) {
                console.error('Error fetching units:', error);
            }
        },
        openAddUnitModal() {
            this.unitForm = { id: null, name: '', unit_level: 'SD', prefix: '', headmaster: '', address: '' };
            this.isUnitEdit = false;
            this.showUnitModal = true;
        },
        editUnit(unit) {
            this.unitForm = { ...unit };
            this.isUnitEdit = true;
            this.showUnitModal = true;
        },
        async saveUnit() {
            if (!this.unitForm.name) return alert('Nama unit wajib diisi');
            
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_unit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: this.isUnitEdit ? 'update' : 'create',
                        ...this.unitForm
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Data unit berhasil disimpan!');
                    this.showUnitModal = false;
                    this.fetchUnits();
                } else {
                    alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving unit:', error);
            }
        },
        async deleteUnit(id) {
            this.confirmAction('Hapus Unit?', 'Apakah Anda yakin ingin menghapus unit ini? Data yang terkait mungkin akan error.', async () => {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const response = await fetch(baseUrl + 'api/manage_unit.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    });
                    if ((await response.json()).success) this.fetchUnits();
                } catch (e) { console.error(e); }
            });
        },
        async fetchUsers() {
            this.adminLoading = true;
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_users.php');
                const data = await response.json();
                this.userList = (data || []).map(u => {
                    const modules = Array.isArray(u.access_modules) ? u.access_modules : [];
                    return {
                        ...u,
                        email: u.email || '',
                        status: (u.status || 'ACTIVE').toUpperCase(),
                        access_modules: modules
                    };
                });
            } catch (error) {
                console.error('Error fetching users:', error);
            } finally {
                this.adminLoading = false;
            }
        },
        openAddUserModal() {
            this.userForm = { id: null, username: '', password: '', role: 'STAFF', status: 'ACTIVE', email: '', access_modules: [], people_id: null };
            this.isUserEdit = false;
            this.showUserModal = true;
            this.fetchStaffLookup();
        },
        editUser(user) {
            const role = (user.role || 'ADMIN').toUpperCase();
            const mods = (user.access_modules || []);
            this.userForm = { 
                ...user, 
                password: '', 
                role: role, 
                status: (user.status || 'ACTIVE'), 
                email: (user.email || ''), 
                access_modules: (['ADMIN','SUPERADMIN'].includes(role) ? [] : mods),
                people_id: (user.people_id || null) 
            };
            this.isUserEdit = true;
            this.showUserModal = true;
            this.fetchStaffLookup();
        },
        async fetchStaffLookup() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const res = await fetch(baseUrl + 'api/get_all_staff.php');
                if (!res.ok) { this.staffLookup = []; return; }
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    this.staffLookup = [];
                    return;
                }
                this.staffLookup = await res.json();
            } catch (e) { console.error(e); }
        },
        async saveUser() {
            if (!this.userForm.username) return alert('Username wajib diisi');
            if (!this.isUserEdit && !this.userForm.password) return alert('Password wajib diisi');

            this.adminLoading = true;
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: this.isUserEdit ? 'update' : 'create',
                        id: this.userForm.id,
                        username: this.userForm.username,
                        password: this.userForm.password,
                        role: this.userForm.role,
                        status: this.userForm.status,
                        email: this.userForm.email,
                        access_modules: this.userForm.access_modules,
                        people_id: this.userForm.people_id
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Data user berhasil disimpan!');
                    this.showUserModal = false;
                    this.fetchUsers();
                } else {
                    alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving user:', error);
                alert('Terjadi kesalahan sistem.');
            } finally {
                this.adminLoading = false;
            }
        },
        async deleteUser(id) {
            this.confirmAction('Hapus User?', 'Apakah Anda yakin ingin menghapus user ini?', async () => {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const response = await fetch(baseUrl + 'api/manage_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.fetchUsers();
                    } else {
                        alert('Gagal menghapus: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error deleting user:', error);
                }
            });
        }
    },
    mounted() {
        if (this.currentPage === 'settings') this.fetchSettings();
        if (this.currentPage === 'users') this.fetchUsers();
        if (!this.schoolSettings.name && this.currentPage !== 'settings') {
             this.fetchSettings();
        }
    },
    watch: {
        currentPage(newVal) {
            if (newVal === 'settings') this.fetchSettings();
            if (newVal === 'users') this.fetchUsers();
        }
    }
};

const academicMixin = {
    data() {
        return {
            academicYears: [
                { id: 1, name: '2025/2026', status: 'Aktif', start: '15 Juli 2025' },
                { id: 2, name: '2024/2025', status: 'Arsip', start: '15 Juli 2024' }
            ],
            unitData: {
                subjects: [],
                timeSlots: [],
                classes: [],
                levels: [],
                years: []
            },
            selectedClassId: '',
            scheduleData: {}, 
            days: ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'],
            selectedClass: null,
            classMembers: [],
            classAttendanceSummary: [],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            activeTab: 'students', 
            searchQuery: '', 
            showEditClassModal: false,
            editClassData: { id: null, name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
            showModalYear: false,
            yearForm: { id: null, name: '', start_date: '', end_date: '', semester_active: 'GANJIL', status: 'PLANNED' },
            showLevelModal: false,
            levelForm: { id: null, name: '', order_index: '' },
            showCreateClassModal: false,
            newClassForm: { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
            showTimeSlotModal: false,
            timeSlotForm: { id: null, name: '', start_time: '', end_time: '', is_break: false },
            showScheduleModal: false,
            scheduleForm: { id: null, day: '', slot_name: '', start_time: '', end_time: '', time_slot_id: '', subject_id: '', teacher_id: '' },
            showSubjectModal: false,
            subjectForm: { id: null, code: '', name: '', category: 'CORE' },
            classSubjects: [],
            selectedSubjectId: '', 
            subjectAssignments: [], 
            showSubjectTeachersListModal: false, 
            showAssignTeacherModal: false,
            assignTeacherForm: { class_id: null, subject_id: null, subject_name: '', code: '', teacher_id: '', weekly_count: 1, session_length: 2 }, 
            selectedTeacherId: '',
            teacherScheduleData: {},
            staffList: [],
            allUnits: [],
            currentUnit: '',
            currentPage: '',
            showShuffleWizard: false,
            shuffleStep: 1,
            shuffleScope: 'selected_units',
            shuffleSelectedUnits: [], 
            shuffleConfirmText: '',
            shuffleLoading: false,
            shuffleResult: { success: false, error: '', logs: [] },
            showConstraintModal: false,
            constraintForm: { type: 'TEACHER', entity_id: '', unit_id: '', day: 'SENIN', start_time: '', end_time: '', is_whole_day: false, reason: '' },
            constraintList: [],
            years: [],
            yearLoading: false,
        };
    },
    computed: {
        currentDate() {
            try {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            } catch (_) {
                return '';
            }
        },
        filteredClassMembers() {
            if (!this.searchQuery) return this.classMembers;
            const lower = this.searchQuery.toLowerCase();
            return this.classMembers.filter(s => 
                (s.name && s.name.toLowerCase().includes(lower)) ||
                (s.identity_number && s.identity_number.toLowerCase().includes(lower))
            );
        },
        availableUnits() {
            return this.allUnits || [];
        },
        teachers() {
            return this.staffList;
        },
        constraintEntityList() {
            if (this.constraintForm.type === 'TEACHER') return this.teachers;
            if (this.constraintForm.type === 'SUBJECT') return this.activeSubjects;
            return [];
        },
        activeSubjects() { return this.unitData.subjects || []; },
        activeTimeslots() { return this.unitData.timeSlots || []; },
        activeClasses() { return this.unitData.classes || []; },
        activeLevels() { return this.unitData.levels || []; },
        activeYears() { return this.unitData.years || []; }
        ,
        assignedTeacher() {
            try {
                if (!this.scheduleForm || !this.scheduleForm.subject_id) return null;
                const match = this.classSubjects.find(s => s.subject_id == this.scheduleForm.subject_id);
                if (match && match.teacher_id) {
                    return { id: match.teacher_id, name: match.teacher_name };
                }
                return null;
            } catch (_) { return null; }
        }
    },
    watch: {
        selectedClassId(newVal) {
            if (newVal) {
                localStorage.setItem('ais_selected_class_id', newVal);
                if (this.currentPage === 'schedule') {
                    const catId = this.activeCategoryId || (this.unitData && this.unitData.activeCategoryId) || null;
                    this.fetchSchedule(newVal, catId);
                }
            }
        },
        selectedSubjectId(newVal) { 
        },
        selectedTeacherId(newVal) {
            console.log('Watcher: selectedTeacherId changed to', newVal);
            if (newVal) {
                this.fetchTeacherSchedule(newVal);
            }
        },
        currentPage(val) {
            if (val === 'teacher-schedule') {
                 this.fetchStaffList(); 
                 if (this.selectedTeacherId) this.fetchTeacherSchedule(this.selectedTeacherId);
            }
        },
        currentUnit(newVal) {
            if (this.manualFetchOnly) return;
            
            if (newVal && newVal !== 'all') {
                this.fetchAcademicData(newVal);
                this.fetchStaffList();
            }
        }
    },
    async mounted() {
        const path = window.location.pathname;
        if (path.includes('schedule.php')) this.currentPage = 'schedule';
        else if (path.includes('teacher_schedule.php')) this.currentPage = 'teacher-schedule';
        else if (path.includes('class_detail.php')) this.currentPage = 'class-detail';

        this.fetchAllUnits(); 
        
        if (this.manualFetchOnly) {
            if (!this.currentUnit) this.currentUnit = 'all';
            return;
        }
        
        if (this.currentPage === 'class-detail') {
            const urlParams = new URLSearchParams(window.location.search);
            const classIdParam = urlParams.get('id');
            if (classIdParam) {
                this.loadClassDetailDirectly(classIdParam);
            }
        } 
        else if (!this.currentUnit) {
             const savedUnit = localStorage.getItem('ais_selected_unit');
             const allowedRaw = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
             const allowed = allowedRaw.map(s => String(s).toUpperCase());
             let candidate = savedUnit || '';
             if (candidate) {
                 const candUp = String(candidate).toUpperCase();
                 const ok = allowed.length === 0 || allowed.includes(candUp);
                 this.currentUnit = ok ? candidate : '';
             }
             if (!this.currentUnit) {
                 if (Array.isArray(this.allUnits) && this.allUnits.length > 0) {
                     const first = this.allUnits[0];
                     this.currentUnit = first.code || first.unit_level || first.name || 'SD';
                 } else if (allowedRaw.length > 0) {
                     this.currentUnit = allowedRaw[0];
                 } else {
                     this.currentUnit = 'SD';
                 }
             }
        } else {
             this.fetchAcademicData(this.currentUnit);
             this.fetchStaffList();
        }
    },
    methods: {
        async loadClassDetailDirectly(classId) {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }

                const res = await fetch(baseUrl + `api/get_class_detail.php?id=${classId}`);
                let classInfo = null;
                if (res.ok) {
                    classInfo = await res.json();
                } else {
                    try {
                        const relRes = await fetch(`../../api/get_class_detail.php?id=${classId}`);
                        if (relRes.ok) {
                            classInfo = await relRes.json();
                        }
                    } catch (_) {}
                }
                
                if (!classInfo || !classInfo.id) {
                    this.selectedClass = { id: Number(classId), name: `Kelas #${classId}`, level_name: '-' };
                    this.selectedClassId = Number(classId);
                    await this.fetchClassAttendanceSummary(classId);
                    try {
                        const memResFallback = await fetch(baseUrl + `api/get_class_members.php?class_id=${classId}`);
                        if (memResFallback.ok) this.classMembers = await memResFallback.json();
                    } catch (_) {}
                    return;
                }
                
                this.selectedClass = classInfo;
                this.selectedClassId = classInfo.id; 
                
                if (classInfo.unit_code) {
                    if (this.currentUnit !== classInfo.unit_code) {
                        this.currentUnit = classInfo.unit_code;
                    } else {
                        this.fetchAcademicData(this.currentUnit);
                    }
                } else if (classInfo.unit_name) {
                     if (this.currentUnit !== classInfo.unit_name) {
                        this.currentUnit = classInfo.unit_name;
                     } else {
                        this.fetchAcademicData(this.currentUnit);
                     }
                }

                const memRes = await fetch(baseUrl + `api/get_class_members.php?class_id=${classId}`);
                this.classMembers = await memRes.json();

                this.fetchSchedule(classId);

                this.fetchClassAttendanceSummary(classId);

                this.activeTab = 'students';
                
            } catch (e) {
                console.error("Failed to load class detail:", e);
                try {
                    await this.fetchClassAttendanceSummary(classId);
                } catch (_) {}
            }
        },
        async fetchStaffList() {
            try {
                 let baseUrl = window.BASE_URL || '/';
                 if (baseUrl === '/' || !baseUrl) {
                     const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                     baseUrl = m ? `/${m[1]}/` : '/';
                 }

                 const unitId = this.getUnitId(this.currentUnit);
                 let url = baseUrl + 'api/get_staff_list.php?role=TEACHER';
                 if (unitId) {
                     url += `&unit_id=${unitId}`;
                 }
                 
                const res = await fetch(url);
                let data;
                const ct = String(res.headers.get('content-type') || '');
                if (ct.includes('application/json')) {
                    data = await res.json();
                } else {
                    const txt = await res.text();
                    try { data = JSON.parse(txt); }
                    catch(_) { console.warn('Non-JSON response from get_staff_list:', txt.slice(0, 160)); data = []; }
                }
                this.staffList = Array.isArray(data) ? data : [];
            } catch(e) {
                 console.error("Gagal ambil data guru:", e);
            }
        },
        async fetchAllUnits() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const res = await fetch(baseUrl + 'api/get_units.php');
                this.allUnits = await res.json();
                const allowedRaw = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                const allowed = allowedRaw.map(s => String(s).toUpperCase());
                if (Array.isArray(this.allUnits) && allowed.length > 0) {
                    this.allUnits = this.allUnits.filter(u => {
                        const code = String(u.code || u.unit_level || '').toUpperCase();
                        const prefix = String(u.prefix || '').toUpperCase();
                        return allowed.includes(code) || (prefix && allowed.includes(prefix));
                    });
                }
            } catch(e) {
                console.warn("Using fallback units");
                this.allUnits = [
                    {id: 1, name: 'TK', code: 'TK'}, 
                    {id: 2, name: 'SD', code: 'SD'}, 
                    {id: 3, name: 'SMP', code: 'SMP'}, 
                    {id: 4, name: 'SMA', code: 'SMA'}
                ]; 
            }
        },
        async fetchAcademicData(unit, categoryId = null) {
            try {
                if (!unit || unit === 'all') return;
                
                if (!categoryId && window.PREFETCHED_SCHEDULE && window.PREFETCHED_SCHEDULE.unit === unit && !window.PREFETCHED_SCHEDULE.consumed) {
                    console.log("Using PREFETCHED_SCHEDULE for unit:", unit);
                    const pre = window.PREFETCHED_SCHEDULE;
                    
                    this.unitData = {
                        classes: pre.classes || [],
                        timeSlots: pre.timeSlots || [],
                        subjects: pre.subjects || [],
                        years: pre.years || [],
                        levels: [], 
                        scheduleCategories: pre.scheduleCategories || [],
                        activeCategoryId: pre.activeCategoryId || null
                    };
                    
                    window.PREFETCHED_SCHEDULE.consumed = true;
                    
                    if (pre.class_id) {
                         this.selectedClassId = pre.class_id;
                    } else if (this.unitData.classes.length > 0) {
                        this.selectedClassId = this.unitData.classes[0].id;
                    }
                    
                    return; 
                }

                console.log("Fetching data for unit:", unit);
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + `api/get_academic_data.php?unit=${unit}${categoryId ? '&category_id=' + categoryId : ''}`);
                let data;
                const ct = String(response.headers.get('content-type') || '');
                if (ct.includes('application/json')) {
                    data = await response.json();
                } else {
                    const txt = await response.text();
                    throw new Error('Invalid JSON response: ' + txt.slice(0, 80));
                }
                console.log("Data received:", data);
                this.unitData = data;
                
                const urlParams = new URLSearchParams(window.location.search);
                const classIdParam = urlParams.get('id');
                if (classIdParam && data.classes) {
                    const targetClass = data.classes.find(c => c.id == classIdParam);
                    if (targetClass) {
                         this.openClassDetail(targetClass);
                    }
                }
                
                else if (data.classes && data.classes.length > 0 && !window.location.pathname.includes('class_detail.php')) {
                    const savedClassId = localStorage.getItem('ais_selected_class_id');
                    if (savedClassId && data.classes.find(c => c.id == savedClassId)) {
                        this.selectedClassId = savedClassId;
                    } else {
                        this.selectedClassId = data.classes[0].id;
                    }
                }
            } catch (error) {
                console.error("Gagal mengambil data akademik:", error);
            }
        },
        openModalYear(year = null) {
            if (year) {
                this.yearForm = { ...year }; 
            } else {
                this.yearForm = { id: null, name: '', start_date: '', end_date: '', semester_active: 'GANJIL', status: 'ACTIVE' };
            }
            this.showModalYear = true;
        },
        async saveYear() {
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = { ...this.yearForm, action: 'save' };
                const response = await fetch(baseUrl + 'api/manage_year.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Tahun Ajaran Berhasil Disimpan!');
                    this.showModalYear = false;
                    this.fetchAcademicData(this.currentUnit);
                } else { alert(result.error); }
            } catch (e) { console.error(e); }
        },
        openEditClassModal(cls) {
            this.fetchStaffList(); 
            this.editClassData = {
                id: cls.id,
                name: cls.name,
                level_id: cls.level_id,
                homeroom_teacher_id: cls.homeroom_teacher_id,
                capacity: cls.capacity || 30,
                sort_order: cls.sort_order || 0
            };
            this.showEditClassModal = true;
        },
        async updateClass() {
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = { ...this.editClassData, action: 'update', class_id: this.editClassData.id };
                const response = await fetch(baseUrl + 'api/manage_class.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Kelas Berhasil Diupdate!');
                    this.showEditClassModal = false;
                    this.fetchAcademicData(this.currentUnit);
                } else { alert(result.error); }
            } catch (e) { console.error(e); }
        },
        async deleteClass(cls) {
            if (this.confirmAction) {
                this.confirmAction(
                    'Hapus Kelas?',
                    `Apakah Anda yakin ingin menghapus kelas <b>${cls.name}</b>?<br>Tindakan ini tidak dapat dibatalkan.`,
                    async () => {
                        await this.executeDeleteClass(cls);
                    }
                );
            } else {
                if (!confirm(`Hapus kelas ${cls.name}?`)) return;
                this.executeDeleteClass(cls);
            }
        },
        async executeDeleteClass(cls) {
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = { class_id: cls.id, action: 'delete' };
                const response = await fetch(baseUrl + 'api/manage_class.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Kelas Berhasil Dihapus!');
                    this.fetchAcademicData(this.currentUnit);
                } else { 
                    alert(result.error); 
                }
            } catch (e) { 
                console.error(e);
                alert("Terjadi kesalahan sistem.");
            }
        },
        async openClassDetail(cls) {
            if (!window.location.pathname.includes('class_detail.php')) {
                 window.location.href = `class_detail.php?id=${cls.id}`;
                 return;
            }

            this.selectedClass = cls;
            this.currentPage = 'class-detail';
            this.classMembers = []; 
            try {
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + `api/get_class_members.php?class_id=${cls.id}`);
                this.classMembers = await response.json();
            } catch (error) {
                console.error("Gagal ambil anggota kelas:", error);
            }
        },
        async saveLevel() {
            if (!this.getUnitId(this.currentUnit)) {
                alert('Silakan pilih unit spesifik (TK/SD/SMP/SMA) terlebih dahulu untuk menambah tingkatan.');
                return;
            }
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = {
                    ...this.levelForm,
                    unit_id: this.getUnitId(this.currentUnit)
                };
                const response = await fetch(baseUrl + 'api/manage_level.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.levelForm = { id: null, name: '', order_index: '' }; 
                    this.fetchAcademicData(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        async deleteLevel(lvl) {
            if(!confirm('Hapus tingkatan ini?')) return;
            try {
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_level.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: lvl.id })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.fetchAcademicData(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        openTimeSlotModal(slot = null) {
            if (slot) {
                this.timeSlotForm = { 
                    id: slot.id, 
                    name: slot.name, 
                    start_time: slot.start, 
                    end_time: slot.end, 
                    is_break: slot.isBreak 
                };
            } else {
                this.timeSlotForm = { id: null, name: '', start_time: '', end_time: '', is_break: false };
            }
            this.showTimeSlotModal = true;
        },
        async saveTimeSlot() {
            if (!this.getUnitId(this.currentUnit)) {
                alert('Silakan pilih unit spesifik (TK/SD/SMA) terlebih dahulu!');
                return;
            }
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = {
                    ...this.timeSlotForm,
                    unit_id: this.getUnitId(this.currentUnit),
                    action: this.timeSlotForm.id ? 'update' : 'create'
                };
                const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.showTimeSlotModal = false;
                    this.fetchAcademicData(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        async generateTimeslots() {
            if (!this.getUnitId(this.currentUnit)) {
                alert('Pilih unit terlebih dahulu!');
                return;
            }
            if (!confirm('Generate otomatis akan menghapus pengaturan jam pelajaran yang ada untuk unit ini dan menggantinya dengan default. Lanjutkan?')) return;
            
            try {
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/generate_timeslots.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ unit_id: this.getUnitId(this.currentUnit) })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.fetchAcademicData(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        async deleteTimeSlot(slot) {
            if(!confirm('Hapus slot waktu ini?')) return;
            try {
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_timeslot.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: slot.id, action: 'delete' })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.fetchAcademicData(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        async fetchSchedule(classId, categoryId = null) {
            if (window.PREFETCHED_SCHEDULE && window.PREFETCHED_SCHEDULE.class_id == classId && window.PREFETCHED_SCHEDULE.schedule) {
                const prefetchedCatId = window.PREFETCHED_SCHEDULE.activeCategoryId;
                if (!categoryId || categoryId == prefetchedCatId) {
                    console.log("Using PREFETCHED_SCHEDULE for class:", classId);
                    this.scheduleData = window.PREFETCHED_SCHEDULE.schedule;
                    window.PREFETCHED_SCHEDULE.schedule = null;
                    return;
                }
            }

            try {
                const baseUrl = window.BASE_URL || '/';
                const url = baseUrl + `api/get_schedule.php?class_id=${classId}&_t=${new Date().getTime()}` + (categoryId ? `&category_id=${categoryId}` : '');
                const response = await fetch(url);
                this.scheduleData = await response.json();
            } catch (error) {
                console.error("Gagal mengambil jadwal:", error);
            }
        },
        async fetchClassAttendanceSummary(classId) {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const res = await fetch(baseUrl + `api/attendance.php?action=get_class_attendance_summary&class_id=${classId}`);
                const data = await res.json();
                if (data.success) {
                    this.classAttendanceSummary = data.data;
                }
            } catch (e) {
                console.error("Gagal mengambil rekap absensi:", e);
            }
        },
        async fetchTeacherSchedule(teacherId) {
            try {
                console.log('Fetching schedule for teacher:', teacherId);
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + `api/get_teacher_schedule.php?teacher_id=${teacherId}&_t=${new Date().getTime()}`);
                const data = await response.json();
                console.log('Teacher schedule received:', data);
                this.teacherScheduleData = data;
            } catch (error) {
                console.error("Gagal mengambil jadwal guru:", error);
            }
        },
        getScheduleItem(day, startTime) {
            if (this.scheduleData[day] && this.scheduleData[day][startTime]) {
                return this.scheduleData[day][startTime];
            }
            return null;
        },
        getTeacherScheduleItem(day, startTime) {
            if (this.teacherScheduleData[day] && this.teacherScheduleData[day][startTime]) {
                return this.teacherScheduleData[day][startTime];
            }
            return null;
        },
        getTeacherSessionLength(day, startTime) {
            try {
                const startIndex = this.activeTimeslots.findIndex(ts => ts.start === startTime);
                if (startIndex === -1) return 1;
                const first = this.getTeacherScheduleItem(day, startTime);
                if (!first) return 0;
                let length = 0;
                for (let k = 0; ; k++) {
                    const ts = this.activeTimeslots[startIndex + k];
                    if (!ts || ts.isBreak) break;
                    const item = this.getTeacherScheduleItem(day, ts.start);
                    if (!item) break;
                    if (item.subject_id === first.subject_id && item.class_id === first.class_id) {
                        length++;
                    } else break;
                }
                return length;
            } catch (_) { return 1; }
        },
        isFirstSessionSlot(day, startTime) {
            try {
                const idx = this.activeTimeslots.findIndex(ts => ts.start === startTime);
                if (idx <= 0) return true;
                const prevTs = this.activeTimeslots[idx - 1];
                if (!prevTs || prevTs.isBreak) return true;
                const curr = this.getTeacherScheduleItem(day, startTime);
                const prev = this.getTeacherScheduleItem(day, prevTs.start);
                if (!curr) return false;
                if (!prev) return true;
                return !(prev.subject_id === curr.subject_id && prev.class_id === curr.class_id);
            } catch (_) { return true; }
        },
        openScheduleModal(day, slot, existingItem = null) {
            console.log('Opening Schedule Modal for Class:', this.selectedClassId);
            this.fetchClassSubjects(); 
            
            if (existingItem) {
                this.scheduleForm = {
                    id: existingItem.id,
                    day: day,
                    slot_name: slot.name,
                    start_time: slot.start,
                    end_time: slot.end,
                    time_slot_id: slot.id,
                    subject_id: existingItem.subject_id,
                    teacher_id: existingItem.teacher_id
                };
            } else {
                this.scheduleForm = {
                    id: null,
                    day: day,
                    slot_name: slot.name,
                    start_time: slot.start,
                    end_time: slot.end,
                    time_slot_id: slot.id,
                    subject_id: '',
                    teacher_id: ''
                };
            }
            this.showScheduleModal = true;
        },
        async saveSchedule() {
            try {
                const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                if (!activeYear) { alert('Harap set Tahun Ajaran Aktif terlebih dahulu!'); return; }
                
                const assignment = await this.getAssignmentForSubject(this.scheduleForm.subject_id, activeYear.id, this.selectedClassId);
                if (!assignment || !assignment.teacher_id) {
                    alert('Belum ada Guru Mapel terpasang untuk kelas ini. Atur di menu "Guru Mapel" terlebih dahulu.');
                    return;
                }
                const sessionLen = assignment ? Number(assignment.session_length || 1) : 1;
                const weeklyCount = assignment ? Number(assignment.weekly_count || 1) : 1;
                const maxSlots = weeklyCount * sessionLen;

                await this.fetchSchedule(this.selectedClassId); 
                const usedSlots = this.countScheduledSlotsForSubject(this.scheduleForm.subject_id);
                if (usedSlots >= maxSlots) {
                    alert(`Pembatasan JP tercapai: ${weeklyCount} JP/minggu dengan durasi ${sessionLen} JP per sesi. Tidak dapat menambah jadwal lagi untuk mapel ini.`);
                    return;
                }

                const startIndex = this.activeTimeslots.findIndex(ts => ts.id == this.scheduleForm.time_slot_id);
                if (startIndex === -1) { alert('Slot waktu tidak ditemukan.'); return; }
                for (let k = 0; k < sessionLen; k++) {
                    const ts = this.activeTimeslots[startIndex + k];
                    if (!ts || ts.isBreak) { alert('Slot berurutan tidak cukup untuk durasi sesi yang ditentukan.'); return; }
                    const occupied = !!this.getScheduleItem(this.scheduleForm.day, ts.start);
                    if (occupied) { alert('Sebagian slot sudah terisi. Pilih waktu lain atau kosongkan terlebih dahulu.'); return; }
                }

                const baseUrl = window.BASE_URL || '/';
                let successAll = true, lastError = '';
                for (let k = 0; k < sessionLen; k++) {
                    const ts = this.activeTimeslots[startIndex + k];
                    const payload = {
                        action: 'create',
                        class_id: this.selectedClassId,
                        subject_id: this.scheduleForm.subject_id,
                        teacher_id: this.scheduleForm.teacher_id,
                        day: this.scheduleForm.day,
                        time_slot_id: ts.id,
                        academic_year_id: activeYear.id
                    };
                    const response = await fetch(baseUrl + 'api/manage_schedule.php', { method: 'POST', body: JSON.stringify(payload) });
                    const result = await response.json();
                    if (!result.success) { successAll = false; lastError = result.error || 'Gagal menyimpan sebagian jadwal'; break; }
                }

                if (successAll) {
                    this.showScheduleModal = false;
                    await this.fetchSchedule(this.selectedClassId);
                } else {
                    alert(lastError);
                }
            } catch(e) { console.error(e); }
        },
        async shuffleSchedule() {
            if (!this.selectedClassId) {
                alert('Pilih kelas terlebih dahulu!');
                return;
            }
            if (!confirm('Apakah Anda yakin ingin men-shuffle jadwal? Jadwal yang sudah ada akan dihapus dan diganti dengan jadwal acak baru.')) return;
            
            try {
                const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                if (!activeYear) { alert('Harap set Tahun Ajaran Aktif terlebih dahulu!'); return; }
                
                const payload = {
                    class_id: this.selectedClassId,
                    academic_year_id: activeYear.id
                };

                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/generate_schedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    this.fetchSchedule(this.selectedClassId);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        async deleteSchedule() {
            if(!confirm('Hapus jadwal ini?')) return;
            try {
                const payload = {
                    action: 'delete',
                    schedule_id: this.scheduleForm.id
                };
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_schedule.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    this.showScheduleModal = false;
                    this.fetchSchedule(this.selectedClassId);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        countScheduledSlotsForSubject(subjectId) {
            try {
                let count = 0;
                const days = this.days || ['SENIN','SELASA','RABU','KAMIS','JUMAT'];
                for (const day of days) {
                    const map = this.scheduleData[day] || {};
                    for (const timeKey in map) {
                        const item = map[timeKey];
                        if (item && item.subject_id == subjectId) count++;
                    }
                }
                return count;
            } catch (_) { return 0; }
        },
        async getAssignmentForSubject(subjectId, academicYearId, classId) {
            try {
                const baseUrl = window.BASE_URL || '/';
                const res = await fetch(baseUrl + `api/get_subject_assignments.php?subject_id=${subjectId}&academic_year_id=${academicYearId}`);
                const list = await res.json();
                if (Array.isArray(list)) {
                    return list.find(a => a.class_id == classId) || null;
                }
                return null;
            } catch (e) {
                console.error('Gagal ambil assignment mapel:', e);
                return null;
            }
        },
        openShuffleWizard() {
            if (!this.selectedClassId && Array.isArray(this.activeClasses) && this.activeClasses.length > 0) {
                this.selectedClassId = this.activeClasses[0].id;
            }
            if (!this.allUnits || this.allUnits.length === 0) {
                this.fetchAllUnits();
            }
            this.shuffleStep = 1;
            this.shuffleScope = 'single_class';
            this.shuffleSelectedUnits = [];
            const currentUnitId = this.getUnitId(this.currentUnit);
            if (currentUnitId && !this.shuffleSelectedUnits.includes(currentUnitId)) this.shuffleSelectedUnits.push(currentUnitId);
            this.shuffleConfirmText = '';
            this.shuffleResult = { success: false, error: '', logs: [] };
            this.showShuffleWizard = true;
        },
        closeShuffleWizard() {
            this.showShuffleWizard = false;
            if (this.shuffleResult.success) {
                this.fetchSchedule(this.selectedClassId);
            }
        },
        async runSmartShuffle() {
            this.shuffleLoading = true;
            this.shuffleStep = 3;
            
            try {
                let activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                if (!activeYear) {
                    await this.syncYears();
                    activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                    if (!activeYear) throw new Error("Tahun ajaran tidak aktif.");
                }
                if (this.shuffleScope === 'single_class') {
                    if (!this.selectedClassId) {
                        if (Array.isArray(this.activeClasses) && this.activeClasses.length > 0) {
                            this.selectedClassId = this.activeClasses[0].id;
                        }
                    }
                    if (!this.selectedClassId) throw new Error("Pilih kelas terlebih dahulu.");
                } else {
                    if (!this.shuffleSelectedUnits || this.shuffleSelectedUnits.length === 0) {
                        const currentUnitId = this.getUnitId(this.currentUnit);
                        if (currentUnitId) this.shuffleSelectedUnits = [currentUnitId];
                    }
                    if (!this.shuffleSelectedUnits || this.shuffleSelectedUnits.length === 0) throw new Error("Pilih unit terlebih dahulu.");
                }

                const payload = {
                    class_id: this.selectedClassId,
                    target_unit_ids: this.shuffleSelectedUnits,
                    academic_year_id: activeYear.id,
                    scope: this.shuffleScope
                };

                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/generate_schedule_global.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                this.shuffleResult = result;
                
            } catch (e) {
                this.shuffleResult = { success: false, error: e.message, logs: [] };
            } finally {
                this.shuffleLoading = false;
            }
        },
        openConstraintModal() {
            this.fetchStaffList(); 
            this.constraintForm = { type: 'TEACHER', entity_id: '', unit_id: '', day: 'SENIN', start_time: '', end_time: '', is_whole_day: false, reason: '' };
            this.fetchConstraints();
            this.showConstraintModal = true;
        },
        async fetchConstraints() {
            try {
                const baseUrl = window.BASE_URL || '/';
                const url = baseUrl + `api/manage_constraints.php?action=list&type=${this.constraintForm.type}&entity_id=${this.constraintForm.entity_id}`;
                const res = await fetch(url);
                this.constraintList = await res.json();
            } catch(e) { console.error(e); }
        },
        async saveConstraint() {
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = { ...this.constraintForm, action: 'create' };
                const res = await fetch(baseUrl + 'api/manage_constraints.php', { method: 'POST', body: JSON.stringify(payload) });
                const json = await res.json();
                if(json.success) {
                    this.fetchConstraints();
                    this.constraintForm.day = 'SENIN';
                    this.constraintForm.start_time = '';
                    this.constraintForm.end_time = '';
                    this.constraintForm.reason = '';
                } else alert(json.error);
            } catch(e) { console.error(e); }
        },
        async deleteConstraint(id) {
            if(!confirm('Hapus constraint ini?')) return;
            try {
                const baseUrl = window.BASE_URL || '/';
                const res = await fetch(baseUrl + 'api/manage_constraints.php', { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });
                const json = await res.json();
                if(json.success) this.fetchConstraints();
            } catch(e) { console.error(e); }
        },
        openSubjectModal(sub = null) {
            if (sub) {
                this.subjectForm = { ...sub };
            } else {
                this.subjectForm = { id: null, code: '', name: '', category: 'CORE' };
            }
            this.showSubjectModal = true;
        },
        async saveSubject() {
            if (!this.getUnitId(this.currentUnit)) { alert('Pilih unit dulu!'); return; }
            try {
                const baseUrl = window.BASE_URL || '/';
                const payload = { ...this.subjectForm, unit_id: this.getUnitId(this.currentUnit), action: this.subjectForm.id ? 'update' : 'create' };
                const response = await fetch(baseUrl + 'api/manage_subject.php', { method: 'POST', body: JSON.stringify(payload) });
                const result = await response.json();
                if (result.success) {
                    this.showSubjectModal = false;
                    this.fetchAcademicData(this.currentUnit);
                } else { alert(result.error); }
            } catch(e) { console.error(e); }
        },
        async deleteSubject(sub) {
            if(!confirm('Hapus mapel ini?')) return;
            try {
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_subject.php', { method: 'POST', body: JSON.stringify({ id: sub.id, action: 'delete' }) });
                const result = await response.json();
                if (result.success) this.fetchAcademicData(this.currentUnit);
                else alert(result.error);
            } catch(e) { console.error(e); }
        },
        async fetchClassSubjects(classId = null) {
            const targetClassId = classId || this.selectedClassId;
            console.log('Fetching subjects for Class ID:', targetClassId);

            if (!targetClassId) {
                console.warn('No Class ID selected for fetching subjects');
                return;
            }
            try {
                const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                if (!activeYear) {
                    console.error('No Active Year found');
                    return;
                }

                const baseUrl = window.BASE_URL || '/';
                const url = baseUrl + `api/get_class_subjects.php?class_id=${targetClassId}&academic_year_id=${activeYear.id}`;
                const response = await fetch(url);
                const data = await response.json();

                if (Array.isArray(data)) {
                    this.classSubjects = data;
                } else {
                    this.classSubjects = [];
                }
            } catch(e) { console.error('Error fetching subjects:', e); this.classSubjects = []; }
        },
        async fetchSubjectAssignments() {
            if (!this.selectedSubjectId) return;
            try {
                const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                if (!activeYear) return;

                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + `api/get_subject_assignments.php?subject_id=${this.selectedSubjectId}&academic_year_id=${activeYear.id}`);
                this.subjectAssignments = await response.json();
            } catch(e) { console.error(e); }
        },
        openSubjectTeachersListModal(sub) {
            this.selectedSubjectId = sub.id;
            this.showSubjectTeachersListModal = true;
            this.fetchSubjectAssignments();
        },
        openAssignTeacherModal(sub) { 
            this.fetchStaffList();
            this.assignTeacherForm = {
                class_id: this.selectedClassId,
                subject_id: sub.subject_id,
                subject_name: sub.subject_name,
                code: sub.code,
                teacher_id: sub.teacher_id || ''
            };
            this.showAssignTeacherModal = true;
        },
        openAssignTeacherModalFromSubject(assign) { 
            const sub = this.activeSubjects.find(s => s.id == this.selectedSubjectId);
            this.assignTeacherForm = {
                class_id: assign.class_id,
                subject_id: this.selectedSubjectId,
                subject_name: sub ? sub.name : 'Unknown',
                code: sub ? sub.code : '',
                teacher_id: assign.teacher_id || '',
                weekly_count: assign.weekly_count || 1,
                session_length: assign.session_length || 2
            };
            this.showAssignTeacherModal = true;
        },
        async saveAssignTeacher() {
            try {
                const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                const payload = {
                    class_id: this.assignTeacherForm.class_id, 
                    academic_year_id: activeYear.id,
                    subject_id: this.assignTeacherForm.subject_id,
                    teacher_id: this.assignTeacherForm.teacher_id,
                    weekly_count: this.assignTeacherForm.weekly_count,
                    session_length: this.assignTeacherForm.session_length
                };
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/save_subject_teacher.php', { method: 'POST', body: JSON.stringify(payload) });
                const result = await response.json();
                if (result.success) {
                    this.showAssignTeacherModal = false;
                    if (this.selectedSubjectId) this.fetchSubjectAssignments();
                    if (this.selectedClassId) this.fetchClassSubjects();
                } else { alert(result.error); }
            } catch(e) { console.error(e); }
        },
        getAssignedTeacherForSelectedSubject() {
            if (!this.scheduleForm.subject_id || !Array.isArray(this.classSubjects)) return null;
            const match = this.classSubjects.find(s => s.subject_id == this.scheduleForm.subject_id);
            if (match && match.teacher_id) {
                return { id: match.teacher_id, name: match.teacher_name };
            }
            return null;
        },
        async onSubjectChange() {
            if (!Array.isArray(this.classSubjects)) return;
            const match = this.classSubjects.find(s => s.subject_id == this.scheduleForm.subject_id);
            if (match && match.teacher_id) {
                this.scheduleForm.teacher_id = match.teacher_id;
            } else {
                this.scheduleForm.teacher_id = '';
            }
        },
        openCreateClassModal() {
            this.fetchStaffList();
            this.newClassForm = { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 };
            this.showCreateClassModal = true;
        },
        async saveNewClass() {
            try {
                const activeYear = this.activeYears.find(y => y.status === 'ACTIVE' || y.status === 'Aktif') || (this.activeYears.length > 0 ? this.activeYears[0] : null);
                
                if (!activeYear) {
                    alert('Tidak ada tahun ajaran aktif! Harap buat/aktifkan tahun ajaran terlebih dahulu.');
                    return;
                }
                
                const payload = {
                    ...this.newClassForm,
                    action: 'create',
                    academic_year_id: activeYear.id
                };
                
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_class.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.showCreateClassModal = false;
                    this.fetchAcademicData(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        },
        getUnitId(unitName) {
            if (!this.allUnits) return null;
            const search = String(unitName).toUpperCase();
            
            let unit = this.allUnits.find(u => 
                String(u.name || '').toUpperCase() === search || 
                String(u.code || '').toUpperCase() === search || 
                String(u.prefix || '').toUpperCase() === search
            );
            if (unit) return unit.id;

            if (search === 'MTS') {
                unit = this.allUnits.find(u => String(u.code || '').toUpperCase() === 'SMP');
            } else if (search === 'MA') {
                unit = this.allUnits.find(u => String(u.code || '').toUpperCase() === 'SMA');
            }

            return unit ? unit.id : null;
        },
        getTeacherTotalHours(teacherId) {
            if (!this.teacherScheduleData) return 0;
            let total = 0;
            for (const day in this.teacherScheduleData) {
                total += Object.keys(this.teacherScheduleData[day]).length;
            }
            return total;
        },
        exportStudents() {
            if (!this.selectedClassId) return;
            window.open(`api/export_class_students.php?class_id=${this.selectedClassId}`, '_blank');
        },
        openImportStudentModal(cls) {
            alert("Fitur Import sedang dalam pengembangan.");
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const date = new Date(dateStr);
                return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
            } catch (e) {
                return dateStr;
            }
        },
        async syncYears() {
            this.yearLoading = true;
            try {
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/ensure_active_year.php');
                const result = await response.json();
                if (result.success) {
                    this.years = Array.isArray(result.data) ? result.data : [];
                } else {
                    console.error('Sync failed:', result.error);
                }
            } catch (e) {
                console.error('Connection error:', e);
                if (this.years.length === 0) {
                     this.years = [
                        { id: 1, name: '2025/2026', status: 'ACTIVE', semester_active: 'GANJIL', start_date: '2025-07-15', end_date: '2025-12-20' }
                     ];
                }
            } finally {
                this.yearLoading = false;
            }
        }
    }
};

const { createApp } = Vue;

const app = createApp({
    mixins: [studentMixin, academicMixin, adminMixin],
    data() {
        return {
            manualFetchOnly: true, 
            currentUnit: 'all',
            activeTab: 'students',
            showEditClassModal: false,
            isDetailLoading: true 
        };
    },
    created() {
        if (window.INITIAL_DETAIL && window.INITIAL_DETAIL.class) {
            const urlParams = new URLSearchParams(window.location.search);
            let id = urlParams.get('id');

            if (!id || String(window.INITIAL_DETAIL.class.id) === String(id)) {
                console.log("🚀 Hybrid Rendering: Hydrating data...");
                this.selectedClass = window.INITIAL_DETAIL.class;
                this.selectedClassId = this.selectedClass.id;
                
                if (this.selectedClass.unit_code) {
                    this.currentUnit = this.selectedClass.unit_code;
                }

                this.classMembers = window.INITIAL_DETAIL.students || [];
                this.isDetailLoading = false;
                
                // Trigger background fetches for other tabs
                this.fetchSchedule(this.selectedClass.id);
                this.fetchClassAttendanceSummary(this.selectedClass.id);
                
                console.log("✅ Vue Hydrated with " + this.classMembers.length + " students");
            }
        }
    },
    computed: {
        filteredClassMembers() {
            if (!this.classMembers || !Array.isArray(this.classMembers)) return [];
            if (!this.searchQuery) return this.classMembers;
            
            const lower = (this.searchQuery || '').toLowerCase();
            return this.classMembers.filter(s => {
                if (!s) return false;
                const name = (s.name || '').toLowerCase();
                const nis = (s.identity_number || '').toLowerCase();
                return name.includes(lower) || nis.includes(lower);
            });
        }
    },
    async mounted() {
        setTimeout(() => {
            this.fetchStaffList(); 
            if (this.currentUnit) {
                this.fetchAcademicData(this.currentUnit).catch(err => console.warn("Background fetch warning:", err));
            }
        }, 100);

        if (this.selectedClass) return;

        const urlParams = new URLSearchParams(window.location.search);
        let id = urlParams.get('id');

        if (id) {
            try {
                await this.loadClassDetailDirectly(id);
            } catch (e) {
                console.error("Error loading class detail:", e);
            } finally {
                this.isDetailLoading = false;
            }
        } else {
            this.isDetailLoading = false;
        }
    }
});

app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
