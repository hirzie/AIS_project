export const academicMixin = {
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
            activeTabSubject: 'ALL', // NEW: Subject Tab State
            
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
        filteredSubjects() {
            if (this.activeTabSubject === 'CORE') {
                return this.activeSubjects.filter(s => s.category === 'CORE' || !s.category);
            } else if (this.activeTabSubject === 'MULOK') {
                return this.activeSubjects.filter(s => s.category === 'MULOK');
            } else if (this.activeTabSubject === 'EKSTRA') {
                return this.activeSubjects.filter(s => s.category === 'EKSTRA');
            } else if (this.activeTabSubject === 'CUSTOM') {
                return this.activeSubjects.filter(s => s.category === 'CUSTOM' || s.category === 'SPECIAL');
            }
            return this.activeSubjects;
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
             
             // Initial Fetch after setting unit
             this.fetchAcademicData(this.currentUnit);
             this.fetchStaffList();
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
                
                // Cek tipe subject
                const subject = this.activeSubjects.find(s => s.id == this.scheduleForm.subject_id);
                const isCustom = subject && (subject.category === 'CUSTOM' || subject.category === 'SPECIAL');

                if (!isCustom && (!assignment || !assignment.teacher_id)) {
                    alert('Belum ada Guru Mapel terpasang untuk kelas ini. Atur di menu "Guru Mapel" terlebih dahulu.');
                    return;
                }
                
                // Jika custom dan tidak ada assignment, default ke 1
                const sessionLen = assignment ? Number(assignment.session_length || 1) : 1;
                // BUG FIX: Jika custom dan tidak ada assignment, jangan batasi weeklyCount (biarkan unlimited atau 99)
                const weeklyCount = assignment ? Number(assignment.weekly_count || 1) : (isCustom ? 99 : 1); 
                const maxSlots = weeklyCount * sessionLen;

                // Hitung slot yang sudah terpakai untuk subject ini di minggu berjalan
                await this.fetchSchedule(this.selectedClassId); // Pastikan data jadwal terbaru
                const usedSlots = this.countScheduledSlotsForSubject(this.scheduleForm.subject_id);
                
                // BUG FIX: Hanya cek maxSlots jika BUKAN custom (atau jika custom mau dibatasi, set assignment dulu)
                if (!isCustom && usedSlots >= maxSlots) {
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
                
                // Debugging: Cek response
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`Server Error (${response.status}): ${text.substring(0, 100)}...`);
                }

                const result = await response.json();
                if (!result.success) { successAll = false; lastError = result.error || 'Gagal menyimpan sebagian jadwal'; break; }
            }

            if (successAll) {
                this.showScheduleModal = false;
                await this.fetchSchedule(this.selectedClassId);
            } else {
                alert(lastError);
            }
        } catch(e) { 
            console.error(e); 
            alert("Terjadi kesalahan sistem: " + e.message);
        }
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
                this.subjectForm = { 
                    ...sub,
                    default_weekly_count: sub.default_weekly_count || 1,
                    default_session_length: sub.default_session_length || 2
                };
            } else {
                this.subjectForm = { 
                    id: null, 
                    code: '', 
                    name: '', 
                    category: 'CORE',
                    default_weekly_count: 1,
                    default_session_length: 2
                };
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
                    
                    // Show Warnings if any (Non-blocking)
                    if (result.warnings && result.warnings.length > 0) {
                        const warningMsg = "Update Berhasil, namun ada peringatan:\n\n- " + result.warnings.join("\n- ");
                        alert(warningMsg);
                    } else {
                        // Optional: Show success toast only if needed
                    }
                } else { 
                    alert(result.error); 
                }
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
        // openAddStudentModal(cls) { ... } -> Uses studentMixin
        // openStudentModal(student) { ... } -> Uses studentMixin
        // saveStudent() { ... } -> Uses studentMixin
        // removeStudentFromClass(student) { ... } -> Uses studentMixin
        
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
