<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';

// --- REFAC: SERVER-SIDE DATA PREFETCHING ---
$prefetched = [
    'unit' => null,
    'classes' => [],
    'timeSlots' => [],
    'schedule' => [],
    'class_id' => null,
    'years' => [],
    'subjects' => []
];

try {
    // 1. Resolve Unit
    $unit = $_GET['unit'] ?? $_COOKIE['ais_selected_unit'] ?? null;
    
    // Fallback: Get first allowed unit
    if (!$unit && !empty($_SESSION['allowed_units'])) {
        $unit = $_SESSION['allowed_units'][0];
    }
    
    // 2. Fetch Academic Years (Global)
    $stmt = $pdo->query("SELECT * FROM acad_years ORDER BY start_date DESC");
    $prefetched['years'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($unit && $unit !== 'all') {
        $prefetched['unit'] = $unit;
        
        // Get Unit ID
        $stmtUnit = $pdo->prepare("SELECT id FROM core_units WHERE name = ? OR code = ? LIMIT 1");
        $stmtUnit->execute([$unit, $unit]);
        $unitId = $stmtUnit->fetchColumn();
        
        if ($unitId) {
            // 2.5 Fetch Schedule Categories
            $stmtCat = $pdo->prepare("SELECT * FROM acad_schedule_categories WHERE unit_id = ? ORDER BY id ASC");
            $stmtCat->execute([$unitId]);
            $prefetched['scheduleCategories'] = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

            // Determine Active Category
            $activeCategoryId = null;
            if (!empty($prefetched['scheduleCategories'])) {
                foreach ($prefetched['scheduleCategories'] as $cat) {
                    if ($cat['is_active']) {
                        $activeCategoryId = $cat['id'];
                        break;
                    }
                }
                if (!$activeCategoryId) {
                    $activeCategoryId = $prefetched['scheduleCategories'][0]['id'];
                }
            }
            $prefetched['activeCategoryId'] = $activeCategoryId;

            // 3. Fetch Classes
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, l.name as level_name, c.level_id 
                FROM acad_classes c 
                LEFT JOIN acad_class_levels l ON c.level_id = l.id 
                WHERE c.unit_id = ? 
                ORDER BY l.order_index ASC, c.name ASC
            ");
            $stmt->execute([$unitId]);
            $prefetched['classes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Fetch Subjects
            $stmt = $pdo->prepare("SELECT * FROM acad_subjects WHERE unit_id = ? ORDER BY name ASC");
            $stmt->execute([$unitId]);
            $prefetched['subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Fetch Time Slots (Formatted)
            $sqlSlots = "SELECT * FROM acad_time_slots WHERE unit_id = ?";
            $paramsSlots = [$unitId];
            if ($activeCategoryId) {
                $sqlSlots .= " AND category_id = ?";
                $paramsSlots[] = $activeCategoryId;
            } else {
                 $sqlSlots .= " AND category_id IS NULL";
            }
            $sqlSlots .= " ORDER BY start_time ASC";
            
            $stmt = $pdo->prepare($sqlSlots);
            $stmt->execute($paramsSlots);
            $rawSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $seen = [];
            $cleanSlots = [];
            foreach ($rawSlots as $slot) {
                $key = $slot['start_time'] . '|' . $slot['end_time'] . '|' . $slot['is_break'];
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $cleanSlots[] = $slot;
            }
            
            $order = 0;
            foreach ($cleanSlots as $slot) {
                $isBreak = (bool)$slot['is_break'];
                if (!$isBreak) $order++;
                
                // Calculate duration in minutes
                $start = new DateTime($slot['start_time']);
                $end = new DateTime($slot['end_time']);
                $diff = $start->diff($end);
                $minutes = ($diff->h * 60) + $diff->i;

                $prefetched['timeSlots'][] = [
                    'id' => $slot['id'],
                    'name' => $slot['name'],
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                    'duration' => $minutes,
                    'isBreak' => $isBreak,
                    'order' => $isBreak ? null : $order
                ];
            }
            
            // 6. Check Selected Class
            $classId = $_GET['class_id'] ?? $_COOKIE['ais_selected_class_id'] ?? null;
            if ($classId) {
                // Verify class belongs to unit
                $validClass = false;
                foreach ($prefetched['classes'] as $c) {
                    if ($c['id'] == $classId) { $validClass = true; break; }
                }
                
                if ($validClass) {
                    $prefetched['class_id'] = $classId;
                    // Fetch Schedule
                    // Note: Joining with acad_subjects and core_people to match get_schedule.php output structure
                    $sqlSchedule = "
                        SELECT 
                            s.id,
                            s.day_name,
                            s.start_time,
                            s.end_time,
                            s.subject_id,
                            s.teacher_id,
                            sub.name as subject_name,
                            sub.code as subject_code,
                            sub.category as subject_category,
                            p.name as teacher_name
                        FROM acad_schedules s 
                        JOIN acad_subjects sub ON s.subject_id = sub.id 
                        LEFT JOIN core_people p ON s.teacher_id = p.id 
                        WHERE s.class_id = ?
                    ";
                    $paramsSchedule = [$classId];
                    
                    if ($activeCategoryId) {
                        $sqlSchedule .= " AND s.category_id = ?";
                        $paramsSchedule[] = $activeCategoryId;
                    }
                    
                    $stmt = $pdo->prepare($sqlSchedule);
                    $stmt->execute($paramsSchedule);
                    $rawSchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Group by Day and Start Time (HH:mm)
                    foreach ($rawSchedule as $sch) {
                        $timeKey = date('H:i', strtotime($sch['start_time']));
                        $prefetched['schedule'][$sch['day_name']][$timeKey] = $sch;
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    // Silent fail, allow client-side fetch to retry
    // error_log($e->getMessage());
}
?>

<script>
    window.USE_GLOBAL_APP = false;
    window.SKIP_GLOBAL_APP = true;
    window.PREFETCHED_SCHEDULE = <?php echo json_encode($prefetched); ?>;
</script>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto">
            
            <!-- HEADER & CONTROLS -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-600"></i> Jadwal Pelajaran
                    </h2>
                    <p class="text-slate-500 text-sm">Kelola jadwal pelajaran mingguan per kelas.</p>
                </div>
                
                <div class="flex flex-wrap gap-2 w-full md:w-auto">
                    <!-- Unit Selector (if multiple) -->
                    <div v-if="availableUnits.length > 1" class="relative">
                        <select v-model="currentUnit" class="bg-white border border-slate-300 rounded-lg pl-3 pr-8 py-2 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer">
                            <option value="all" disabled>Pilih Unit</option>
                            <option v-for="u in availableUnits" :key="u.id" :value="u.unit_level">{{ u.name }}</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>

                    <!-- Schedule Category Selector -->
                    <div v-if="scheduleCategories.length > 0" class="relative">
                        <select v-model="activeCategoryId" class="bg-white border border-slate-300 rounded-lg pl-3 pr-8 py-2 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer">
                            <option v-for="cat in scheduleCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }} <span v-if="getYearName(cat.academic_year_id)">({{ getYearName(cat.academic_year_id) }})</span> <span v-if="cat.is_active" class="text-xs"> (Aktif)</span>
                            </option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>

                    <!-- Class Selector -->
                    <div class="relative flex-1 md:flex-none">
                        <select v-model="selectedClassId" :disabled="!currentUnit || currentUnit === 'all'" class="w-full md:w-64 bg-white border border-slate-300 rounded-lg pl-3 pr-8 py-2 text-sm focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="" disabled>-- Pilih Kelas --</option>
                            <option v-for="cls in activeClasses" :key="cls.id" :value="cls.id">
                                {{ cls.name }} <span v-if="cls.level_name">({{ cls.level_name }})</span>
                            </option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button @click="openConstraintModal" class="bg-amber-100 text-amber-700 px-3 py-2 rounded-lg text-xs font-bold hover:bg-amber-200 transition-colors" title="Batasan Jadwal">
                            <i class="fas fa-ban"></i><span class="hidden sm:inline ml-2">Constraint</span>
                        </button>
                        <button @click="openShuffleWizard" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-purple-700 transition-colors shadow-sm shadow-purple-200">
                            <i class="fas fa-magic"></i><span class="hidden sm:inline ml-2">Smart Shuffle</span>
                        </button>
                        <button class="bg-white border border-slate-300 text-slate-600 px-3 py-2 rounded-lg text-xs font-bold hover:bg-slate-50 transition-colors">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- SKELETON LOADER (Refactoring Standard: UX) -->
            <div v-if="loading && !selectedClassId" class="animate-pulse space-y-4">
                 <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
                 <div class="grid grid-cols-6 gap-4">
                     <div class="h-96 bg-slate-200 rounded-xl col-span-1"></div>
                     <div class="h-96 bg-slate-200 rounded-xl col-span-5"></div>
                 </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-else-if="currentUnit === 'all' || !selectedClassId" class="text-center py-16 bg-white rounded-2xl border border-dashed border-slate-300">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <i class="fas fa-chalkboard text-4xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700 mb-2">Pilih Kelas Terlebih Dahulu</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-6 text-sm">Silakan pilih unit dan kelas pada menu di atas untuk mulai mengelola jadwal pelajaran.</p>
            </div>

            <!-- SCHEDULE GRID -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                            <th class="p-2 w-24 border-r border-slate-100">Waktu</th>
                            <th v-for="day in days" :key="day" class="p-2 w-48 border-r border-slate-100">{{ day }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="slot in activeTimeslots" :key="slot.id" class="border-b border-slate-100 hover:bg-slate-50">
                            <!-- Kolom Waktu -->
                            <td class="p-2 border-r border-slate-100 bg-slate-50 text-center align-middle" :class="slot.isBreak ? 'h-8 bg-amber-50/50' : 'h-16'">
                                <div class="font-bold text-slate-700">{{ slot.start }} - {{ slot.end }}</div>
                                <div v-if="slot.isBreak" class="text-[9px] text-amber-600 font-bold uppercase">Istirahat</div>
                                <div v-else class="text-[9px] text-slate-400">Jam Ke-{{ slot.order }}</div>
                            </td>

                            <!-- Kolom Hari -->
                            <td v-for="day in days" :key="day + slot.id" class="p-1 border-r border-slate-100 align-top" :class="slot.isBreak ? 'h-8 bg-amber-50/30' : 'h-16'">
                                <!-- Jika Jam Istirahat -->
                                <div v-if="slot.isBreak" class="h-full flex items-center justify-center">
                                    <i class="fas fa-mug-hot text-amber-300 text-xs"></i>
                                </div>
                                
                                <!-- Jika Jam Pelajaran & Ada Jadwal -->
                                <div v-else-if="getScheduleItem(day, slot.start)" 
                                    @click="openScheduleModal(day, slot, getScheduleItem(day, slot.start))"
                                    :class="getScheduleItem(day, slot.start).subject_category === 'CUSTOM' ? 'bg-emerald-50 border-emerald-100 hover:bg-emerald-100' : 'bg-blue-50 border-blue-100 hover:bg-blue-100'"
                                    class="border p-1.5 rounded h-full relative group hover:shadow-sm transition-shadow cursor-pointer">
                                    <div :class="getScheduleItem(day, slot.start).subject_category === 'CUSTOM' ? 'text-emerald-800' : 'text-blue-800'" class="font-bold text-[10px] mb-0.5 line-clamp-1">
                                        {{ getScheduleItem(day, slot.start).subject_name }}
                                    </div>
                                    <div v-if="getScheduleItem(day, slot.start).subject_category !== 'CUSTOM'" class="text-[9px] text-slate-500 flex items-center gap-1">
                                        <i class="fas fa-user-tie text-[8px]"></i>
                                        <span class="truncate">{{ getScheduleItem(day, slot.start).teacher_name }}</span>
                                    </div>
                                </div>
                                
                                <!-- Jika Kosong -->
                                <div v-else class="h-full flex items-center justify-center group">
                                    <button @click="openScheduleModal(day, slot)" class="text-slate-200 group-hover:text-blue-400 text-xs transition-colors w-full h-full">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
        </div>

        <!-- MODAL: ISI JADWAL (ADD SCHEDULE) -->
        <div v-if="showScheduleModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="font-bold text-slate-800">{{ scheduleForm.day }} - {{ scheduleForm.slot_name }}</h3>
                        <p class="text-xs text-slate-500">{{ scheduleForm.start_time }} - {{ scheduleForm.end_time }}</p>
                    </div>
                    <button @click="showScheduleModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-4">
                    <form @submit.prevent="saveSchedule">
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran</label>
                            <select v-model="scheduleForm.subject_id" @change="onSubjectChange" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                                <option value="" disabled>-- Pilih Mapel --</option>
                                <option v-for="sub in classSubjects" :key="sub.subject_id" :value="sub.subject_id">
                                    {{ sub.subject_name }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Guru Pengajar</label>
                            <select v-model="scheduleForm.teacher_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" :required="!isCustomSubject">
                                <option value="">-- {{ isCustomSubject ? 'Tanpa Guru (Opsional)' : 'Pilih Guru' }} --</option>
                                <!-- Hanya Tampilkan Guru Mapel -->
                                <option v-if="assignedTeacher" :value="assignedTeacher.id" class="font-bold bg-blue-50 text-blue-800">
                                    {{ assignedTeacher.name }} (Guru Pengampu)
                                </option>
                            </select>
                            <p v-if="assignedTeacher" class="text-[10px] text-blue-600 mt-1">
                                <i class="fas fa-info-circle"></i> Guru otomatis dipilih berdasarkan pengaturan Guru Mapel.
                            </p>
                            <p v-else-if="!isCustomSubject" class="text-[10px] text-red-500 mt-1 font-bold">
                                <i class="fas fa-exclamation-circle"></i> Belum ada guru untuk mapel ini. Silakan atur di menu "Guru Mapel".
                            </p>
                            <p v-else class="text-[10px] text-amber-600 mt-1">
                                <i class="fas fa-info-circle"></i> Mapel Custom tidak mewajibkan guru.
                            </p>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <button type="button" v-if="scheduleForm.id" @click="deleteSchedule" class="text-red-500 hover:text-red-700 text-xs font-bold">
                                <i class="fas fa-trash mr-1"></i> Hapus
                            </button>
                            <div v-else></div>
                            <div class="flex gap-2">
                                <button type="button" @click="showScheduleModal = false" class="px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-600 hover:bg-slate-50">Batal</button>
                                <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 font-bold">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: CONSTRAINT MANAGEMENT -->
        <div v-if="showConstraintModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Batasan Jadwal (Constraints)</h3>
                        <p class="text-sm text-slate-500">Atur waktu berhalangan untuk Guru atau Mapel</p>
                    </div>
                    <button @click="showConstraintModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Form Input -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6">
                        <h4 class="font-bold text-slate-700 mb-3 text-sm uppercase">Tambah Batasan Baru</h4>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Tipe Entitas</label>
                                <select v-model="constraintForm.type" @change="fetchConstraints" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="TEACHER">Guru / Pengajar</option>
                                    <option value="SUBJECT">Mata Pelajaran</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Pilih Entitas</label>
                                <select v-model="constraintForm.entity_id" @change="fetchConstraints" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Pilih --</option>
                                    <option v-for="item in constraintEntityList" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Hari</label>
                                <select v-model="constraintForm.day" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                    <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Jam Mulai</label>
                                <input type="time" v-model="constraintForm.start_time" :disabled="constraintForm.is_whole_day" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Jam Selesai</label>
                                <input type="time" v-model="constraintForm.end_time" :disabled="constraintForm.is_whole_day" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="constraintForm.is_whole_day" class="rounded text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-slate-700">Seharian Penuh</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-600 mb-1">Alasan (Opsional)</label>
                                <input type="text" v-model="constraintForm.reason" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Contoh: Rapat Dinas, MGMP, dll">
                            </div>
                            <button @click="saveConstraint" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 mb-[1px]">
                                <i class="fas fa-plus mr-1"></i> Tambah
                            </button>
                        </div>
                    </div>

                    <!-- List Constraints -->
                    <div>
                        <h4 class="font-bold text-slate-700 mb-3 text-sm uppercase">Daftar Batasan Aktif</h4>
                        <div v-if="constraintList.length === 0" class="text-center py-8 text-slate-400 italic bg-slate-50 rounded-lg border border-dashed border-slate-300">
                            Belum ada batasan untuk entitas ini.
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="c in constraintList" :key="c.id" class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg shadow-sm">
                                <div>
                                    <div class="font-bold text-slate-800 text-sm">
                                        <span v-if="c.entity_name" class="block text-xs text-indigo-600 mb-1 font-extrabold uppercase tracking-wider">
                                            {{ c.entity_name }} <span class="text-slate-400 font-normal">({{ c.type }})</span>
                                        </span>
                                        <span class="text-blue-600 mr-2">[{{ c.day_name }}]</span>
                                        <span v-if="c.is_whole_day">Seharian Penuh</span>
                                        <span v-else>{{ c.start_time }} - {{ c.end_time }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500">{{ c.reason || 'Tidak ada alasan' }}</div>
                                </div>
                                <button @click="deleteConstraint(c.id)" class="text-slate-400 hover:text-red-600 p-2"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: SMART SHUFFLE WIZARD -->
        <div v-if="showShuffleWizard" v-cloak class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white">
                    <h3 class="text-2xl font-bold flex items-center gap-2">
                        <i class="fas fa-magic"></i> Smart Shuffle
                    </h3>
                    <p class="text-purple-100 text-sm mt-1">Generate jadwal otomatis berbasis AI heuristic.</p>
                </div>

                <!-- Body -->
                <div class="p-6 flex-1 overflow-y-auto">
                    
                    <!-- STEP 1: SCOPE SELECTION -->
                    <div v-if="shuffleStep === 1">
                        <h4 class="font-bold text-slate-800 mb-4">1. Pilih Lingkup Generate</h4>
                        
                        <div class="space-y-3 mb-6">
                            <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition-colors" :class="shuffleScope === 'single_class' ? 'border-purple-500 bg-purple-50' : 'border-slate-200'">
                                <input type="radio" v-model="shuffleScope" value="single_class" class="mt-1 text-purple-600 focus:ring-purple-500">
                                <div>
                                    <span class="block font-bold text-slate-800">Hanya Kelas Ini</span>
                                    <span class="text-xs text-slate-500">Generate jadwal hanya untuk <b>{{ selectedClass ? selectedClass.name : 'Kelas Terpilih' }}</b>. Jadwal kelas lain tidak akan berubah.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition-colors" :class="shuffleScope === 'selected_units' ? 'border-purple-500 bg-purple-50' : 'border-slate-200'">
                                <input type="radio" v-model="shuffleScope" value="selected_units" class="mt-1 text-purple-600 focus:ring-purple-500">
                                <div>
                                    <span class="block font-bold text-slate-800">Satu Unit Sekolah (Massal)</span>
                                    <span class="text-xs text-slate-500">Generate ulang jadwal untuk <b>SEMUA KELAS</b> dalam unit yang dipilih. <span class="text-red-500 font-bold">PERINGATAN: Jadwal lama akan dihapus!</span></span>
                                </div>
                            </label>
                        </div>

                        <!-- Unit Selection if Scope is Units -->
                        <div v-if="shuffleScope === 'selected_units'" class="mb-6 animate-fade">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Unit:</label>
                            <div class="flex gap-2 flex-wrap">
                                <label v-for="u in availableUnits" :key="u.id" class="cursor-pointer">
                                    <input type="checkbox" :value="u.id" v-model="shuffleSelectedUnits" class="peer sr-only">
                                    <div class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm font-bold text-slate-600 peer-checked:bg-purple-600 peer-checked:text-white peer-checked:border-purple-600 transition-all">
                                        {{ u.name }}
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button @click="shuffleStep = 2" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-purple-700 shadow-lg shadow-purple-200">
                                Lanjut <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: CONFIRMATION -->
                    <div v-if="shuffleStep === 2">
                        <h4 class="font-bold text-slate-800 mb-4">2. Konfirmasi Tindakan</h4>
                        
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-amber-600 text-xl mt-1"></i>
                                <div>
                                    <h5 class="font-bold text-amber-800 text-sm">Peringatan Penting!</h5>
                                    <p class="text-xs text-amber-700 mt-1">
                                        Proses ini akan <b>MENGHAPUS SEMUA JADWAL</b> yang ada pada lingkup yang Anda pilih, lalu membuat jadwal baru secara acak berdasarkan ketersediaan guru dan batasan (constraints).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Ketik "SAYA MENGERTI" untuk melanjutkan:</label>
                            <input type="text" v-model="shuffleConfirmText" @input="shuffleConfirmText = $event.target.value.toUpperCase()" class="w-full border border-slate-300 rounded-lg px-4 py-2 font-bold text-center uppercase focus:border-purple-500 focus:ring-purple-500" placeholder="...">
                        </div>

                        <div class="flex justify-between">
                            <button @click="shuffleStep = 1" class="text-slate-500 hover:text-slate-800 font-bold text-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </button>
                            <button @click="runSmartShuffle" :disabled="shuffleConfirmText !== 'SAYA MENGERTI'" :class="{'opacity-50 cursor-not-allowed bg-slate-400': shuffleConfirmText !== 'SAYA MENGERTI', 'bg-purple-600 hover:bg-purple-700 shadow-lg shadow-purple-200': shuffleConfirmText === 'SAYA MENGERTI'}" class="text-white px-6 py-2 rounded-lg font-bold transition-all">
                                <i class="fas fa-play mr-2"></i> Mulai Generate
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: PROCESSING / RESULT -->
                    <div v-if="shuffleStep === 3">
                        <div v-if="shuffleLoading" class="text-center py-12">
                            <div class="inline-block animate-spin rounded-full h-16 w-16 border-4 border-purple-200 border-t-purple-600 mb-4"></div>
                            <h4 class="text-xl font-bold text-slate-800">Sedang Memproses...</h4>
                            <p class="text-slate-500 text-sm">Mohon tunggu, AI sedang menyusun jadwal terbaik.</p>
                        </div>

                        <div v-else>
                            <div v-if="shuffleResult.success" class="text-center py-6">
                                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Berhasil!</h4>
                                <p class="text-slate-600 text-sm mb-6">Jadwal berhasil dibuat ulang.</p>
                                
                                <div class="bg-slate-50 rounded-lg p-3 text-left max-h-40 overflow-y-auto border border-slate-200 mb-6">
                                    <p class="text-xs font-bold text-slate-500 mb-2 uppercase">Log Proses:</p>
                                    <ul class="text-xs text-slate-600 font-mono space-y-1">
                                        <li v-for="(log, i) in shuffleResult.logs" :key="i">{{ log }}</li>
                                    </ul>
                                </div>

                                <button @click="closeShuffleWizard" class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-slate-900 w-full">
                                    Selesai
                                </button>
                            </div>

                            <div v-else class="text-center py-6">
                                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                                    <i class="fas fa-times"></i>
                                </div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Gagal!</h4>
                                <p class="text-red-600 text-sm mb-6">{{ shuffleResult.error }}</p>
                                
                                <button @click="shuffleStep = 1" class="bg-white border border-slate-300 text-slate-600 px-6 py-2 rounded-lg font-bold hover:bg-slate-50">
                                    Coba Lagi
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<script type="module">
    import { academicMixin } from '../../assets/js/modules/academic.js';
    import { adminMixin } from '../../assets/js/modules/admin.js';
    const { createApp } = Vue;
    const app = createApp({
        mixins: [academicMixin],
        data() {
            return {
                loading: false,
                activeCategoryId: null
            }
        },
        created() {
            // Initialize activeCategoryId from unitData if available (e.g. from prefetch)
            if (this.unitData && this.unitData.activeCategoryId) {
                this.activeCategoryId = this.unitData.activeCategoryId;
            }
        },
        computed: {
            scheduleCategories() {
                return this.unitData.scheduleCategories || [];
            },
            currentSubject() {
                if (!this.scheduleForm.subject_id || !this.classSubjects) return null;
                return this.classSubjects.find(s => s.subject_id == this.scheduleForm.subject_id);
            },
            isCustomSubject() {
                const sub = this.currentSubject;
                return sub && (sub.category === 'CUSTOM' || sub.category === 'SPECIAL');
            },
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            }
        },
        watch: {
            activeCategoryId(newVal, oldVal) {
                if (newVal && newVal !== oldVal) {
                    if (this.unitData.activeCategoryId == newVal) return;
                    this.fetchAcademicData(this.currentUnit, newVal);
                    if (this.selectedClassId) {
                         this.fetchSchedule(this.selectedClassId, newVal);
                    }
                }
            },
            'unitData': {
                handler(newData) {
                    if (newData && newData.activeCategoryId) {
                        if (this.activeCategoryId !== newData.activeCategoryId) {
                             this.activeCategoryId = newData.activeCategoryId;
                        }
                    }
                },
                deep: true
            }
        },
        methods: {
            getYearName(yearId) {
                if (!yearId || !this.unitData.years) return '';
                const year = this.unitData.years.find(y => y.id == yearId);
                return year ? year.name : '';
            }
        }
    });
    app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
