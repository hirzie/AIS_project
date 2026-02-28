<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?><style>
        [v-cloak] { display: none !important; }
    </style>
</head>
<body>

<script>
    window.USE_GLOBAL_APP = false;
    window.SKIP_GLOBAL_APP = true;
</script>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative" v-cloak>
        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Manajemen Kelas</h2>
                </div>
                <div class="hidden md:flex gap-2">
                    <a href="students_audit.php" class="bg-white border border-slate-300 px-4 py-2 rounded-lg text-sm hover:bg-slate-50 font-bold text-slate-600"><i class="fas fa-clipboard-check mr-2"></i>Audit Siswa</a>
                    <button @click="showLevelModal = true" class="bg-white border border-slate-300 px-4 py-2 rounded-lg text-sm hover:bg-slate-50"><i class="fas fa-layer-group mr-2"></i>Atur Tingkatan</button>
                    <button @click="openCreateClassModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Buat Kelas</button>
                </div>
            </div>

            <!-- SKELETON LOADER (Default Visible) -->
            <div id="skeleton-loader" v-if="!isLoaded" class="space-y-8 animate-pulse mt-8">
                <!-- Level Skeleton 1 -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-8 w-1 bg-slate-300 rounded-full"></div>
                        <div class="h-8 w-48 bg-slate-200 rounded"></div>
                        <div class="h-6 w-20 bg-slate-200 rounded-full"></div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                        <div class="bg-white h-48 rounded-xl border border-slate-200"></div>
                        <div class="bg-white h-48 rounded-xl border border-slate-200"></div>
                        <div class="bg-white h-48 rounded-xl border border-slate-200"></div>
                    </div>
                </div>
                 <!-- Level Skeleton 2 -->
                 <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-8 w-1 bg-slate-300 rounded-full"></div>
                        <div class="h-8 w-32 bg-slate-200 rounded"></div>
                        <div class="h-6 w-20 bg-slate-200 rounded-full"></div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                        <div class="bg-white h-48 rounded-xl border border-slate-200"></div>
                        <div class="bg-white h-48 rounded-xl border border-slate-200"></div>
                    </div>
                </div>
            </div>

            <!-- VUE CONTENT (Hard Hidden Default) -->
            <div id="vue-content-wrapper" style="display: none;" :style="{ display: isLoaded ? 'block' : 'none' }">
                <div v-if="currentUnit !== 'all' && activeLevels.length > 0" class="space-y-8">
                    <div v-for="level in activeLevels.sort((a,b) => a.order_index - b.order_index)" :key="level.id">
                    <!-- Level Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-8 w-1 bg-blue-500 rounded-full"></div>
                        <h3 class="text-xl font-bold text-slate-700">{{ level.name }}</h3>
                        <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded-full font-bold">Level {{ level.order_index }}</span>
                    </div>

                    <!-- Classes Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                        <!-- Filter classes for this level and sort by sort_order then name -->
                        <div v-for="cls in activeClasses.filter(c => c.level_id === level.id).sort((a, b) => {
                            if (a.sort_order !== b.sort_order) return a.sort_order - b.sort_order;
                            return a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'});
                        })" :key="cls.id" class="bg-white p-3 md:p-5 rounded-xl shadow-sm border border-slate-200 hover:border-blue-300 transition-colors">
                            <div class="flex justify-between items-start mb-2 md:mb-4">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center font-bold text-sm md:text-lg"><i class="fas fa-chalkboard"></i></div>
                                <span class="bg-green-100 text-green-700 text-[10px] md:text-xs px-2 py-1 rounded font-bold uppercase">{{ currentUnit === 'all' ? 'SD' : currentUnit }}</span>
                            </div>
                            <h3 class="text-sm md:text-lg font-bold text-slate-800">{{ cls.name }}</h3>
                            <p class="text-xs md:text-sm text-slate-500 mb-2 md:mb-4"><i class="fas fa-user-tie mr-1"></i> Wali: {{ cls.homeroom || '-' }}</p>
                            
                            <!-- Quota Bar -->
                            <div class="mb-2 md:mb-4">
                                <div class="flex justify-between text-[10px] md:text-xs mb-1">
                                    <span class="text-slate-500">Kapasitas</span>
                                    <span class="font-bold" :class="(cls.student_count || 0) >= cls.capacity ? 'text-red-600' : 'text-blue-600'">
                                        {{ cls.student_count || 0 }} / {{ cls.capacity }} Siswa
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all duration-500" 
                                            :class="(cls.student_count || 0) >= cls.capacity ? 'bg-red-500' : 'bg-blue-500'"
                                            :style="`width: ${Math.min(((cls.student_count || 0) / (cls.capacity || 30)) * 100, 100)}%`"></div>
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-xs md:text-sm border-t border-slate-100 pt-2 md:pt-3">
                                <div class="flex gap-2">
                                    <button @click="openClassDetail(cls)" class="hidden md:block text-slate-600 font-medium hover:text-blue-600">Detail</button>
                                    <button @click="openEditClassModal(cls)" class="text-slate-400 hover:text-orange-500"><i class="fas fa-pencil-alt"></i></button>
                                    <button @click="deleteClass(cls)" class="text-slate-400 hover:text-red-500"><i class="fas fa-trash"></i></button>
                                </div>
                                <button @click="openClassDetail(cls)" class="text-blue-600 w-6 h-6 md:w-8 md:h-8 rounded-full hover:bg-blue-50 flex items-center justify-center text-xs md:text-base"><i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                        
                        <!-- Empty State for Level -->
                        <div v-if="activeClasses.filter(c => c.level_id === level.id).length === 0" class="col-span-full border-2 border-dashed border-slate-200 rounded-xl p-6 text-center text-slate-400 text-sm">
                            Belum ada kelas di tingkatan ini.
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="currentUnit === 'all'" class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-blue-200 mt-6">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-500">
                    <i class="fas fa-school text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700 mb-2">Pilih Unit Sekolah</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-6">Silakan pilih unit (TK, SD, SMP, SMA) pada menu bagian atas untuk mengelola data kelas.</p>
                <div class="flex gap-2 justify-center">
                    <button @click="currentUnit = 'TK'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">TK</button>
                    <button @click="currentUnit = 'SD'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SD</button>
                    <button @click="currentUnit = 'SMP'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SMP</button>
                    <button @click="currentUnit = 'SMA'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SMA</button>
                </div>
            </div>
            
            <div v-else class="text-center py-12">
                <i class="fas fa-layer-group text-4xl text-slate-300 mb-3"></i>
                <p class="text-slate-500">Belum ada data tingkatan (Level) untuk unit ini.</p>
            </div>
            
            </div> <!-- End of Vue Content Wrapper -->
        </div>

        <!-- MODALS -->
        <?php include '../../includes/modals/confirm_modal.php'; ?>

        <!-- MODAL: ATUR TINGKATAN (MANAGE LEVELS) -->
        <div v-if="showLevelModal" v-cloak style="display: none;" :style="{ display: showLevelModal ? 'flex' : 'none' }" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-xl font-bold text-slate-800">Atur Tingkatan (Levels)</h3>
                    <button @click="showLevelModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 text-blue-700 p-4 rounded-lg mb-6 text-sm flex items-start gap-3">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <div>
                            <p class="font-bold">Panduan Tingkatan:</p>
                            <ul class="list-disc ml-4 mt-1">
                                <li>Urutkan tingkatan berdasarkan jenjang (misal: 1 untuk Kelas 1 SD, 7 untuk Kelas 7 SMP).</li>
                                <li>Nama tingkatan akan menjadi grup untuk kelas-kelas di dalamnya.</li>
                            </ul>
                        </div>
                    </div>

                    <form @submit.prevent="saveLevel">
                        <div class="flex gap-4 mb-6">
                            <div class="w-24">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Level Order</label>
                                <input type="number" v-model="levelForm.order_index" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-center" placeholder="1" required>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Tingkatan</label>
                                <input type="text" v-model="levelForm.name" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Contoh: Kelas 1, Kelas X" required>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                                    <i class="fas fa-plus mr-2"></i>Tambah
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="border rounded-lg overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-20 text-center">Order</th>
                                    <th class="px-4 py-3">Nama Tingkatan</th>
                                    <th class="px-4 py-3 w-24 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="lvl in activeLevels" :key="lvl.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-center font-mono font-bold text-slate-600">{{ lvl.order_index }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ lvl.name }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="deleteLevel(lvl)" class="text-red-400 hover:text-red-600 p-1" title="Hapus Tingkatan">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="activeLevels.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-400 italic">Belum ada tingkatan. Silakan tambah di atas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button @click="showLevelModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Tutup</button>
                </div>
            </div>
        </div>

        <!-- MODAL: BUAT KELAS BARU -->
        <div v-if="showCreateClassModal" v-cloak style="display: none;" :style="{ display: showCreateClassModal ? 'flex' : 'none' }" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-xl font-bold text-slate-800">Buat Kelas Baru</h3>
                    <button @click="showCreateClassModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveNewClass">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tingkatan (Level)</label>
                            <select v-model="newClassForm.level_id" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                                <option value="" disabled>-- Pilih Tingkatan --</option>
                                <option v-for="lvl in activeLevels" :key="lvl.id" :value="lvl.id">{{ lvl.name }} (Level {{ lvl.order_index }})</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Kelas</label>
                            <input type="text" v-model="newClassForm.name" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Contoh: 1-A, X-MIPA-1" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Wali Kelas</label>
                            <select v-model="newClassForm.homeroom_teacher_id" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                <option value="">-- Tidak Ada --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kapasitas Siswa</label>
                            <input type="number" v-model="newClassForm.capacity" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="30">
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showCreateClassModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">Simpan Kelas</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: EDIT KELAS -->
        <div v-if="showEditClassModal" v-cloak style="display: none;" :style="{ display: showEditClassModal ? 'flex' : 'none' }" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
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

    </main>
</div>

</div>

<script>
    // Inlined academicMixin to avoid module loading issues on hosting
    const academicMixin = {
        data() {
            return {
                // DATA DUMMY (AKAN DIGANTI FETCH API)
                academicYears: [
                    { id: 1, name: '2025/2026', status: 'Aktif', start: '15 Juli 2025' },
                    { id: 2, name: '2024/2025', status: 'Arsip', start: '15 Juli 2024' }
                ],
                // Container Data Dinamis dari API
                unitData: {
                    subjects: [],
                    timeSlots: [],
                    classes: [],
                    levels: [],
                    years: []
                },
                // Schedule State
                selectedClassId: '',
                scheduleData: {}, 
                days: ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'],
                // Class Detail State
                selectedClass: null,
                classMembers: [],
                classAttendanceSummary: [],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                activeTab: 'students', 
                searchQuery: '', // NEW: Search Query
                
                // Modal Student (Managed by studentMixin)
                // showStudentModal, activeTabStudent, studentForm removed to avoid conflict
                
                // Modal Edit Class
                showEditClassModal: false,
                editClassData: { id: null, name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
                // Modal Year
                showModalYear: false,
                yearForm: { id: null, name: '', start_date: '', end_date: '', semester_active: 'GANJIL', status: 'PLANNED' },
                
                // Modal Levels
                showLevelModal: false,
                levelForm: { id: null, name: '', order_index: '' },
                
                // Modal Create Class
                showCreateClassModal: false,
                newClassForm: { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
                
                // Modal Time Slots
                showTimeSlotModal: false,
                timeSlotForm: { id: null, name: '', start_time: '', end_time: '', is_break: false },
                
                // Modal Schedule
                showScheduleModal: false,
                scheduleForm: { id: null, day: '', slot_name: '', start_time: '', end_time: '', time_slot_id: '', subject_id: '', teacher_id: '' },

                // Manage Subject
                showSubjectModal: false,
                subjectForm: { id: null, code: '', name: '', category: 'CORE' },

                // Subject Teachers
                classSubjects: [],
                selectedSubjectId: '', // NEW
                subjectAssignments: [], // NEW
                showSubjectTeachersListModal: false, // NEW: List of classes for a subject
                showAssignTeacherModal: false,
                assignTeacherForm: { class_id: null, subject_id: null, subject_name: '', code: '', teacher_id: '', weekly_count: 1, session_length: 2 }, // Added count/length

                // Teacher Schedule State
                selectedTeacherId: '',
                teacherScheduleData: {},
                staffList: [],
                allUnits: [],
                currentUnit: '',
                currentPage: '',

                // Smart Shuffle State
                showShuffleWizard: false,
                shuffleStep: 1,
                shuffleScope: 'selected_units',
                shuffleSelectedUnits: [], // New array for multi-unit
                shuffleConfirmText: '',
                shuffleLoading: false,
                shuffleResult: { success: false, error: '', logs: [] },

                // Constraint Management State
                showConstraintModal: false,
                constraintForm: { type: 'TEACHER', entity_id: '', unit_id: '', day: 'SENIN', start_time: '', end_time: '', is_whole_day: false, reason: '' },
                constraintList: [],
                
                // Academic Year Sync State
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
                        // Use active category if available (component or unitData)
                        const catId = this.activeCategoryId || (this.unitData && this.unitData.activeCategoryId) || null;
                        this.fetchSchedule(newVal, catId);
                    }
                    // OLD FLOW REMOVED
                }
            },
            selectedSubjectId(newVal) { // NEW FLOW
                // Subject-teachers page removed
            },
            selectedTeacherId(newVal) {
                console.log('Watcher: selectedTeacherId changed to', newVal);
                // Always fetch schedule regardless of current page
                if (newVal) {
                    this.fetchTeacherSchedule(newVal);
                }
            },
            currentPage(val) {
                // Subject-teachers page removed
                if (val === 'teacher-schedule') {
                     this.fetchStaffList(); 
                     // If teacher already selected (e.g. from cache or default), fetch schedule immediately
                     if (this.selectedTeacherId) this.fetchTeacherSchedule(this.selectedTeacherId);
                }
            },
            currentUnit(newVal) {
                // Skip fetch if manual fetch mode is enabled (handled explicitly by component)
                if (this.manualFetchOnly) return;
                
                if (newVal && newVal !== 'all') {
                    this.fetchAcademicData(newVal);
                    // Also refresh staff list based on unit
                    this.fetchStaffList();
                }
            }
        },
        async mounted() {
            // Detect Page
            const path = window.location.pathname;
            if (path.includes('schedule.php')) this.currentPage = 'schedule';
            else if (path.includes('teacher_schedule.php')) this.currentPage = 'teacher-schedule';
            else if (path.includes('class_detail.php')) this.currentPage = 'class-detail';

            // Initialize
            this.fetchAllUnits(); // Non-blocking
            
            if (this.manualFetchOnly) {
                if (!this.currentUnit) this.currentUnit = 'all';
                return;
            }
            
            // Handle Class Detail Page Logic specifically
            if (this.currentPage === 'class-detail') {
                const urlParams = new URLSearchParams(window.location.search);
                const classIdParam = urlParams.get('id');
                if (classIdParam) {
                    this.loadClassDetailDirectly(classIdParam);
                }
            } 
            // Auto-select Unit if not set (for other pages)
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
                 // If already set (e.g. hardcoded), ensure we fetch
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

                    // Fetch basic class info including unit
                    const res = await fetch(baseUrl + `api/get_class_detail.php?id=${classId}`);
                    let classInfo = null;
                    if (res.ok) {
                        classInfo = await res.json();
                    } else {
                        // Fallback: Try relative path resolution if server base differs
                        try {
                            const relRes = await fetch(`../../api/get_class_detail.php?id=${classId}`);
                            if (relRes.ok) {
                                classInfo = await relRes.json();
                            }
                        } catch (_) {}
                    }
                    
                    if (!classInfo || !classInfo.id) {
                        // Soft fallback: still load attendance summary and members by classId
                        this.selectedClass = { id: Number(classId), name: `Kelas #${classId}`, level_name: '-' };
                        this.selectedClassId = Number(classId);
                        await this.fetchClassAttendanceSummary(classId);
                        // Try members even if class info missing
                        try {
                            const memResFallback = await fetch(baseUrl + `api/get_class_members.php?class_id=${classId}`);
                            if (memResFallback.ok) this.classMembers = await memResFallback.json();
                        } catch (_) {}
                        // Exit early without error alert
                        return;
                    }
                    
                    // Set Selected Class
                    this.selectedClass = classInfo;
                    this.selectedClassId = classInfo.id; // Also set ID for consistency
                    
                    // Set Current Unit based on class info
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

                    // Fetch Members
                    const memRes = await fetch(baseUrl + `api/get_class_members.php?class_id=${classId}`);
                    this.classMembers = await memRes.json();

                    // Fetch Schedule
                    this.fetchSchedule(classId);

                    // Fetch Attendance Summary
                    this.fetchClassAttendanceSummary(classId);

                    // Force set active tab to students and ensure re-render
                    this.activeTab = 'students';
                    
                } catch (e) {
                    console.error("Failed to load class detail:", e);
                    // Avoid blocking alert on hosting; attempt minimal fallback
                    try {
                        await this.fetchClassAttendanceSummary(classId);
                    } catch (_) {}
                }
            },
            async fetchStaffList() {
                try {
                     // FALLBACK
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
                    // Fallback if API missing: extract from classes
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
                    
                    // --- PREFETCH CHECK ---
                    // Skip prefetch if specific category requested, as prefetch is usually just default
                    if (!categoryId && window.PREFETCHED_SCHEDULE && window.PREFETCHED_SCHEDULE.unit === unit && !window.PREFETCHED_SCHEDULE.consumed) {
                        console.log("Using PREFETCHED_SCHEDULE for unit:", unit);
                        const pre = window.PREFETCHED_SCHEDULE;
                        
                        this.unitData = {
                            classes: pre.classes || [],
                            timeSlots: pre.timeSlots || [],
                            subjects: pre.subjects || [],
                            years: pre.years || [],
                            levels: [], // Levels not prefetched currently, harmless for schedule view
                            scheduleCategories: pre.scheduleCategories || [],
                            activeCategoryId: pre.activeCategoryId || null
                        };
                        
                        // Mark as consumed to allow future refreshes
                        window.PREFETCHED_SCHEDULE.consumed = true;
                        
                        // Auto-select Class from Prefetch if available
                        if (pre.class_id) {
                             this.selectedClassId = pre.class_id;
                             // Schedule data is also prefetched and will be handled by fetchSchedule
                        } else if (this.unitData.classes.length > 0) {
                            this.selectedClassId = this.unitData.classes[0].id;
                        }
                        
                        return; // Skip AJAX
                    }
                    // ----------------------

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
                    
                    // Check URL for class_id (for detail page)
                    const urlParams = new URLSearchParams(window.location.search);
                    const classIdParam = urlParams.get('id');
                    if (classIdParam && data.classes) {
                        const targetClass = data.classes.find(c => c.id == classIdParam);
                        if (targetClass) {
                             this.openClassDetail(targetClass);
                        }
                    }
                    
                    // Auto-select logic
                    else if (data.classes && data.classes.length > 0 && !window.location.pathname.includes('class_detail.php')) {
                        // Try to restore from localStorage
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
                    this.yearForm = { ...year }; // Clone
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
                this.fetchStaffList(); // Ensure staff loaded
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
                // Use custom confirm if available
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
                // Redirect if not on detail page
                if (!window.location.pathname.includes('class_detail.php')) {
                     window.location.href = `class_detail.php?id=${cls.id}`;
                     return;
                }

                this.selectedClass = cls;
                this.currentPage = 'class-detail';
                this.classMembers = []; // Reset dulu
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
                        this.levelForm = { id: null, name: '', order_index: '' }; // Reset
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
                // --- PREFETCH CHECK ---
                if (window.PREFETCHED_SCHEDULE && window.PREFETCHED_SCHEDULE.class_id == classId && window.PREFETCHED_SCHEDULE.schedule) {
                    // If categoryId is provided, ensure it matches the prefetched category
                    const prefetchedCatId = window.PREFETCHED_SCHEDULE.activeCategoryId;
                    if (!categoryId || categoryId == prefetchedCatId) {
                        console.log("Using PREFETCHED_SCHEDULE for class:", classId);
                        this.scheduleData = window.PREFETCHED_SCHEDULE.schedule;
                        // Clear to prevent stale data usage
                        window.PREFETCHED_SCHEDULE.schedule = null;
                        return;
                    }
                }
                // ----------------------

                try {
                    // Add timestamp to prevent caching
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
                    // Add timestamp to prevent caching
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
                    
                    // Ambil parameter assignment untuk Subject & Class (weekly_count dan session_length)
                    const assignment = await this.getAssignmentForSubject(this.scheduleForm.subject_id, activeYear.id, this.selectedClassId);
                    if (!assignment || !assignment.teacher_id) {
                        alert('Belum ada Guru Mapel terpasang untuk kelas ini. Atur di menu "Guru Mapel" terlebih dahulu.');
                        return;
                    }
                    const sessionLen = assignment ? Number(assignment.session_length || 1) : 1;
                    const weeklyCount = assignment ? Number(assignment.weekly_count || 1) : 1;
                    const maxSlots = weeklyCount * sessionLen;

                    // Hitung slot yang sudah terpakai untuk subject ini di minggu berjalan
                    await this.fetchSchedule(this.selectedClassId); // Pastikan data jadwal terbaru
                    const usedSlots = this.countScheduledSlotsForSubject(this.scheduleForm.subject_id);
                    if (usedSlots >= maxSlots) {
                        alert(`Pembatasan JP tercapai: ${weeklyCount} JP/minggu dengan durasi ${sessionLen} JP per sesi. Tidak dapat menambah jadwal lagi untuk mapel ini.`);
                        return;
                    }

                    // Jika session_length > 1, pastikan slot yang dipilih memiliki slot berurutan yang cukup dan kosong
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
            // Helpers untuk pembatasan JP
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
                // Jika sukses, refresh jadwal
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
            // Constraints
            openConstraintModal() {
                this.fetchStaffList(); // Ensure staff
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
                        // Reset form partially
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
            openAssignTeacherModal(sub) { // OLD FLOW
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
            openAssignTeacherModalFromSubject(assign) { // NEW FLOW
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
                        class_id: this.assignTeacherForm.class_id, // Use form data
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
                
                // 1. Exact Match
                let unit = this.allUnits.find(u => 
                    String(u.name || '').toUpperCase() === search || 
                    String(u.code || '').toUpperCase() === search || 
                    String(u.prefix || '').toUpperCase() === search
                );
                if (unit) return unit.id;

                // 2. Alias Mapping (MTS -> SMP, MA -> SMA)
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
            // --- STUDENT MANAGEMENT (MOVED TO student.js) ---
            exportStudents() {
                if (!this.selectedClassId) return;
                // Open in new tab to trigger download
                window.open(`api/export_class_students.php?class_id=${this.selectedClassId}`, '_blank');
            },
            openImportStudentModal(cls) {
                // Placeholder for now or if user has modal logic
                alert("Fitur Import sedang dalam pengembangan.");
            },
            // --- ACADEMIC YEAR LOGIC ---
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
                    // Fallback
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

    try {
        const app = createApp({
            mixins: [academicMixin],
            data() {
                return {
                    manualFetchOnly: false, 
                    currentUnit: 'all',
                    isLoaded: false,
                    showLevelModal: false,
                    showCreateClassModal: false,
                    showEditClassModal: false,
                    levelForm: { name: '', order_index: 1 },
                    newClassForm: { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
                    editClassData: { id: '', name: '', level_id: '', homeroom_teacher_id: '', capacity: 30, sort_order: 0 }
                };
            },
            created() {
                this.isLoaded = true;
            },
            mounted() {
                if (this.fetchStaffList) this.fetchStaffList();
                // Manual hide skeleton if Vue reactivity is slow
                const skel = document.getElementById('skeleton-loader');
                if(skel) skel.style.display = 'none';
                
                const content = document.getElementById('vue-content-wrapper');
                if(content) content.style.display = 'block';
            },
            computed: {
                currentDate() {
                    const d = new Date();
                    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
                },
                teachers() { return this.staffList || []; }
            },
            methods: {
                openCreateClassModal() {
                    this.newClassForm = { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 };
                    this.showCreateClassModal = true;
                },
                openEditClassModal(cls) {
                    this.editClassData = { ...cls };
                    this.showEditClassModal = true;
                },
                // Re-implement CRUD methods here to ensure they work without adminMixin
                async saveLevel() {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'create_level');
                        formData.append('unit_level', this.currentUnit === 'all' ? 'SD' : this.currentUnit); 
                        formData.append('name', this.levelForm.name);
                        formData.append('order_index', this.levelForm.order_index);
                        const res = await axios.post('../../api/academic.php', formData);
                        if (res.data.success) {
                            this.showLevelModal = false;
                            this.fetchAcademicData(this.currentUnit);
                            Swal.fire('Berhasil', 'Tingkatan berhasil ditambahkan', 'success');
                        }
                    } catch (e) { Swal.fire('Error', 'Gagal menyimpan', 'error'); }
                },
                async deleteLevel(lvl) {
                    const result = await Swal.fire({ title: 'Hapus?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!' });
                    if (result.isConfirmed) {
                        try {
                            await axios.post('../../api/academic.php', { action: 'delete_level', id: lvl.id }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
                            this.fetchAcademicData(this.currentUnit);
                            Swal.fire('Terhapus!', '', 'success');
                        } catch (e) { Swal.fire('Gagal!', '', 'error'); }
                    }
                },
                async saveNewClass() {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'create_class');
                        formData.append('name', this.newClassForm.name);
                        formData.append('level_id', this.newClassForm.level_id);
                        formData.append('homeroom_teacher_id', this.newClassForm.homeroom_teacher_id || '');
                        formData.append('capacity', this.newClassForm.capacity);
                        const res = await axios.post('../../api/academic.php', formData);
                        if (res.data.success) {
                            this.showCreateClassModal = false;
                            this.fetchAcademicData(this.currentUnit);
                            Swal.fire('Berhasil', 'Kelas dibuat', 'success');
                        }
                    } catch (e) { Swal.fire('Error', 'Gagal', 'error'); }
                },
                async updateClass() {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'update_class');
                        formData.append('id', this.editClassData.id);
                        formData.append('name', this.editClassData.name);
                        formData.append('level_id', this.editClassData.level_id);
                        formData.append('homeroom_teacher_id', this.editClassData.homeroom_teacher_id || '');
                        formData.append('capacity', this.editClassData.capacity);
                        formData.append('sort_order', this.editClassData.sort_order);
                        const res = await axios.post('../../api/academic.php', formData);
                        if (res.data.success) {
                            this.showEditClassModal = false;
                            this.fetchAcademicData(this.currentUnit);
                            Swal.fire('Berhasil', 'Diperbarui', 'success');
                        }
                    } catch (e) { Swal.fire('Error', 'Gagal', 'error'); }
                },
                async deleteClass(cls) {
                    const result = await Swal.fire({ title: 'Hapus Kelas?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya' });
                    if (result.isConfirmed) {
                        try {
                            await axios.post('../../api/academic.php', { action: 'delete_class', id: cls.id }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
                            this.fetchAcademicData(this.currentUnit);
                            Swal.fire('Terhapus!', '', 'success');
                        } catch (e) { Swal.fire('Gagal!', '', 'error'); }
                    }
                },
                openClassDetail(cls) { window.location.href = `class_detail.php?id=${cls.id}`; }
            }
        });
        app.mount('#app');
    } catch (err) {
        console.error("Vue Mount Error:", err);
        document.getElementById('skeleton-loader').innerHTML = '<div class="p-8 text-center text-red-500 font-bold">Gagal memuat aplikasi. Silakan refresh halaman.</div>';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>
