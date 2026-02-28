import { studentMixin } from './student.js?v=3';
import { utilsMixin } from './utils.js?v=2';

export const workspaceLogics = {
    mixins: [studentMixin, utilsMixin],
    data() {
        return {
            baseUrl: (window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/')),
            currentUnit: (function() {
                var stored = localStorage.getItem('workspaceUnit');
                var allowed = window.ALLOWED_UNITS || [];
                
                // Case 1: allowed is array (e.g. ['sma'])
                if (Array.isArray(allowed) && allowed.length > 0) {
                    if (stored && allowed.includes(stored)) return stored;
                    return allowed[0];
                }
                
                // Case 2: allowed is object (e.g. {'sma': true})
                if (typeof allowed === 'object' && allowed !== null) {
                    var keys = Object.keys(allowed);
                    if (keys.length > 0) {
                         if (stored && allowed[stored]) return stored;
                         return keys[0];
                    }
                }
                
                return stored || 'tk'; // Default fallback
            })(),
            workspaceTab: 'DASHBOARD',
            moduleCode: 'WORKSPACE',
            showWaliClassSelector: false,
            // Logic to conditionally show selector if not locked
            // If user has 'ACADEMIC' or 'SUPERADMIN' role, they can switch classes
            canSwitchClass: (function() {
                var r = (window.USER_ROLE || '').toUpperCase();
                return ['SUPERADMIN','ADMIN','ACADEMIC','KEPALA','PRINCIPAL','WAKASEK'].some(role => r.includes(role));
            })(),
            availableUnits: [], // List of all units for header
            accessibleUnits: [], // List of units user can access
            workspaceDailyDate: new Date().toISOString().split('T')[0],
            workspaceMonth: new Date().getMonth() + 1,
            workspaceYear: new Date().getFullYear(),
            // Lock System
            lockStatus: { is_locked: false, locks: [], overrides: [] },
            unitLocks: [],
            showClassUnlockModal: false,
            selectedLockClass: null,
            unlockReason: '',
            unlockDuration: 24,
            unlockType: 'ALL',
            loading: true, // For blank page fix
            unitStats: {
                studentCount: 0,
                attendancePct: 0,
                bkActive: 0,
                inventoryIssues: 0,
                classes: []
            },
            currentTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
            isSaturday: new Date().getDay() === 6,
            attendanceSubmitted: false,
            monthlyRecapSubmitted: false,
            monthlyRecapModal: false,
            monthlyRecaps: [], // Added for monthly recap list
            // If not found in localStorage, use default based on role
            // But we prefer 'kepala' as default view for everyone who has access
            currentPosition: (function(){ 
                var role = (window.USER_ROLE || '').toUpperCase();
                var isFixed = window.IS_WORKSPACE_FIXED;

                // 1. Superadmin/Admin: Allow switching, respect localStorage
                if (role === 'SUPERADMIN' || role === 'ADMIN') {
                     var s = localStorage.getItem('workspacePosition'); 
                     if (s && ['kepala','wakasek','wali','guru'].includes(s)) return s;
                     return 'kepala';
                }

                // 2. Principal (Kepala Sekolah): FORCE 'kepala'
                // User requirement: "langsung arahkan ke kepala sekolah"
                if (isFixed) {
                    return 'kepala';
                }

                // 3. Regular Teacher (Walikelas): Default to 'guru' or 'wali'
                // For now, default to 'guru' as requested for "tampilan guru"
                var s = localStorage.getItem('workspacePosition');
                if (s === 'wali') return 'wali';
                return 'guru'; 
            })(),
            userRole: (window.USER_ROLE || ''),
            // Teacher Data
            teacherTab: 'DASHBOARD',
            teacherStats: { totalJP: 0, totalSubjects: 0 },
            teacherSchedule: [],
            teacherSubjects: [],
            teacherInventory: [],
            teacherTasks: [],
            classesOptions: [],
            selectedClassId: '',
            className: '',
            classSlug: '',
            homeroomClassId: null,
            dailyDate: new Date().toISOString().substring(0,10),
            monthlyMonth: (new Date()).getMonth() + 1,
            monthlyYear: (new Date()).getFullYear(),
            monthlySource: '-',
            monthlyCalendar: [],
            monthlyStudentRows: [],
            topAbsentees: [],
            classStudents: [],
            attendancePercent: 0,
            absentStudents: [],
            classCondition: 'Kuning',
            behaviorPointsAvg: 78,
            pendingIssues: [],
            resolveModal: false,
            resolveIncident: null,
            resolveNote: '',
            escalateModal: false,
            escalateIncident: null,
            escalateNote: '',
            bkQueue: [],
            inventoryAssets: [
                { id: 'INV-CHAIR', name: 'Kursi Siswa', count: 36, condition: 'Baik', note: 'Lengkap' },
                { id: 'INV-TABLE', name: 'Meja Siswa', count: 36, condition: 'Baik', note: 'Lengkap' },
                { id: 'INV-BOARD', name: 'Papan Tulis', count: 1, condition: 'Rusak Ringan', note: 'Ada noda spidol permanen' },
                { id: 'INV-AC', name: 'AC', count: 1, condition: 'Baik', note: 'Baru diservis' },
                { id: 'INV-CLOCK', name: 'Jam Dinding', count: 1, condition: 'Mati', note: 'Perlu ganti baterai' },
                { id: 'INV-CLEAN', name: 'Alat Kebersihan', count: 1, condition: 'Baik', note: 'Sapu, Pel, Pengki' }
            ],
            inventoryModal: false,
            inventorySelected: null,
            inventoryReportCount: 1,
            inventoryReportDesc: '',
            inventoryReportPhotoName: '',
            maintenanceTickets: [
                { id: 'MT-900', item: 'Papan Tulis', count: 1, status: 'In Progress', date: new Date(Date.now()-86400000).toISOString() }
            ],
            tasksDaily: [
                { id: 'TD-1', text: 'Verifikasi Absensi', done: false },
                { id: 'TD-2', text: 'Jurnal Kelas', done: false }
            ],
            tasksWeekly: [
                { id: 'TW-1', text: 'Zero Report Sarpras', done: false },
                { id: 'TW-2', text: 'Evaluasi Kedisiplinan', done: false }
            ],
            tasksMonthly: [
                { id: 'TM-1', text: 'Rekap Presensi', done: false },
                { id: 'TM-2', text: 'Rapat Orang Tua', done: false }
            ],
            bkLogs: [
                { id: 'BK-1', title: 'Konseling ringan', student: 'Dimas Putra', status: 'Closed', date: new Date(Date.now()-7200000).toISOString() }
            ],
            libraryNotifications: [
                { id: 'LIB-1', student: 'Citra Lestari', book: 'Fisika Dasar', status: 'Telat', due: new Date(Date.now()+86400000).toISOString() }
            ],
            teacherNotes: [
                { id: 'TN-1', text: 'Kondisi kelas perlu penertiban saat jam terakhir', ts: Date.now()-5400000 }
            ],
            teacherNoteInput: '',
            showAbsentModal: false,
            dailyVerifyModal: false,
            studentPortfolioModal: false,
            portfolioStudent: { academic_avg: 86, academic_top_subject: 'Matematika', library_loans: 2, library_late: 1, bk_points: 5, bk_active_cases: 0 },
            attendanceSummary: { present: 0, permit: 0, absent: 0 },
            classAttendanceSummary: [],
            activities: { PRESENSI: [], RAPAT: [], KEGIATAN: [] },
            studentCount: 0,
            classCount: 0,
            teacherCount: 0,
            activeActivityTab: 'PRESENSI',
            academicSlots: [],
            boardingGender: 'all',
            boardingCount: 0,
            permissionsActive: [],
            achievementsRecent: [],
            violationsRecent: [],
            roomsTop: [],
            halaqohTop: [],
            musyrifCount: 0,
            musyrifList: [],
            boardingStudents: [],
            activeTab: 'MEETINGS',
            meetings: [],
            approvals: [],
            documents: [],
            tasks: [],
            chatMessages: [],
            chatInput: '',
            recentMeetingCount: 0,
            zeroTodayReports: [],
            zeroCounts: { safe: 0, incident: 0, pending: 0 },
            topResponder: { name: null, time: null },
            counselingSummary: { achievements_recent: 0, cases_recent: 0, counseling_sessions_recent: 0 },
            weeklyMonthlyZero: { weekly: { safe: 0, incident: 0 }, monthly: { safe: 0, incident: 0 } },
            zeroUrgencyText: '',
            zeroUrgencyClass: '',
            classScheduleMap: {},
            bkSchedule: [],
            bkCases: [],
            homeroomTab: 'DASH',
            bkTab: 'KONSELING',
            bkProfile: { profile: {}, team: [] },
            bkArticles: [],
            studentSearch: '',
            // Attendance Batch Validation Data
            attendanceValidationModal: false,
            attendanceBatchStudents: [],
            attendanceBatchActiveDays: 22,
            attendanceBatchMonth: null,
            attendanceBatchYear: null,
            attendanceBatchLoading: false,
            attendanceBatchSaving: false,
            // Lock System (Consolidated)
            // lockStatus: { is_locked: false, locks: [], overrides: [] }, // Already defined above
            // unitLocks: [], // Already defined above
            // showUnlockModal: false, // Already defined above
            unlockForm: { class_id: '', lock_type: 'ALL', hours: 24, reason: '' }, // Unused but kept for safety
            unlockTargetClass: null,
            facilityCheckModal: false,
            facilityCheck: { status: 'GOOD', notes: '' },
            // UI Display Data
            positionName: 'Kepala Sekolah',
            // Dynamic User Roles per Unit
            availableUnits: [],
            accessibleUnits: [],
            userRoles: {
                kepala: false,
                wakasek: false,
                wali: false,
                guru: false
            },
            displaySettings: {
                tickerActive: true,
                tickerText: '',
                urgentActive: false,
                urgentText: '',
                imageOverride: '',
                imageActive: false,
                isUploadingImage: false,
                imageHistory: [],
                pdfActive: false,
                pdfUrl: '',
                pdfPage: 1,
                aiActive: false
            },
            currentPDF: null,
            uploadingPDF: false,
            // pdfDoc removed to avoid Proxy issues
            pdfLoading: false,
            pdfError: null,
            aiPrompt: '',
            aiResult: null,
            aiLoading: false
        };
    },
    created() {
        this.pdfDoc = null;
    },
    watch: {
        'displaySettings.pdfUrl': {
            handler(n) {
                if (n && this.displaySettings.pdfActive) {
                    this.loadPdfDocument(n);
                }
            },
            immediate: true
        },
        'displaySettings.pdfPage': {
            handler(n) {
                if (this.pdfDoc && this.displaySettings.pdfActive) {
                    this.renderPdfThumbnail(n);
                }
            }
        },
        'displaySettings.pdfActive': {
            handler(n) {
                if (n && this.displaySettings.pdfUrl) {
                    this.loadPdfDocument(this.displaySettings.pdfUrl);
                }
            }
        },
        homeroomTab(n) {
            if (n === 'DISPLAY' && this.classSlug) {
                this.fetchDisplaySettings();
            }
        },
        currentUnit: {
            handler(n) {
                if (n) {
                    this.fetchUserRoles();
                    localStorage.setItem('workspaceUnit', n);
                    
                    // Don't set this.unitName manually, let computed property handle it
                    // this.unitName = n.toUpperCase(); 
                    
                    if (n === 'asrama') {
                        // Asrama Logic
                        this.attendanceSummary = { present: 0, permit: 0, absent: 0 };
                        this.studentCount = 0;
                        this.classCount = 0;
                        this.teacherCount = 0;
                        this.boardingCount = 0;
                        this.activeTab = 'ZERO';
                        
                        this.fetchBoardingCount();
                        this.fetchAsramaStats();
                        this.fetchMeetings();
                        this.fetchApprovals();
                        this.fetchDocuments();
                        this.loadTasks();
                        this.loadChatMessages();
                        this.fetchZeroTodayReports();
                        this.fetchZeroSummary();
                    } else {
                        // School Unit Logic
                        this.boardingCount = 0;
                        this.clearAsramaStats();
                        this.meetings = [];
                        this.approvals = [];
                        this.documents = [];
                        this.tasks = [];
                        this.chatMessages = [];
                        this.chatInput = '';
                        this.activeTab = 'MEETINGS';
                        this.recentMeetingCount = 0;
                        this.zeroTodayReports = [];
                        this.zeroCounts = { safe: 0, incident: 0, pending: 0 };
                        this.topResponder = { name: null, time: null };

                        if (this.currentPosition === 'kepala') {
                            this.fetchUnitStats();
                            this.fetchUnitLocks();
                        } else {
                            this.fetchAttendance();
                            this.fetchStats();
                            this.fetchAcademicAgenda();
                            this.fetchCounselingSummary();
                            
                            if (this.currentPosition === 'guru') {
                                this.fetchTeacherData();
                            }
                        }
                    }
                    
                    this.refreshActivities();
                    if (this.showWaliClassSelector) {
                        this.fetchClassesForUnit();
                    }
                }
            },
            immediate: true
        },
        currentPosition(n) {
            localStorage.setItem('workspacePosition', n);
            this.updatePositionName();
            
            // Set Position Name for display
            if (n === 'kepala') this.positionName = 'Kepala Sekolah';
            else if (n === 'wakasek') this.positionName = 'Wakil Kepala';
            else if (n === 'wali') this.positionName = 'Wali Kelas';
            else if (n === 'guru') this.positionName = 'Guru Mapel';
            else this.positionName = 'Staff';

            if (n === 'kepala') {
                this.fetchUnitStats();
                this.fetchUnitLocks();
            } else if (this.currentUnit !== 'asrama') {
                this.fetchStats();
                this.refreshActivities();
            }
            
            if (n === 'wali') {
                this.checkLockStatus();
                this.fetchHomeroomClass();
                if (this.canSwitchClass) {
                    this.fetchClassesForUnit();
                }
                this.fetchAcademicAgenda();
                this.fetchBkProfile();
                this.fetchBkArticles();
                this.generateAcademicMonths();
            } else if (n === 'guru') {
                this.fetchTeacherData();
            }
        },
        dailyDate() {
            if (this.homeroomClassId) {
                this.fetchDailyAttendance();
            }
        },
        homeroomClassId(val) {
            if (val && this.currentPosition === 'wali') {
                this.checkLockStatus();
            }
        },
        monthlyMonth() {
            this.fetchMonthlyData();
        },
        monthlyYear() {
            this.fetchMonthlyData();
        },
        workspaceTab(val) {
            if (val === 'TUGAS' && this.currentPosition === 'kepala') {
                this.fetchUnitLocks();
            } else if (val === 'BK' && this.currentPosition === 'kepala') {
                this.fetchBkCases();
            }
        }
    },
    mounted() {
        // 1. Initialize Base Data
        this.fetchAvailableUnits();
        this.fetchAccessibleUnits();
        
        // 2. Determine Best Unit & Roles
        this.fetchBestUnit().then(async () => {
            await this.fetchUserRoles();
            
            // Enforce Position based on Role
            if (window.IS_WORKSPACE_FIXED) {
                this.currentPosition = 'kepala';
            } else {
                // Try to restore from localStorage if valid
                const saved = localStorage.getItem('workspacePosition');
                if (saved && this.userRoles && this.userRoles[saved]) {
                    this.currentPosition = saved;
                } else {
                    // Default Priority: Kepala > Wakasek > Wali > Guru
                    if (this.userRoles.kepala) this.currentPosition = 'kepala';
                    else if (this.userRoles.wakasek) this.currentPosition = 'wakasek';
                    else if (this.userRoles.wali) this.currentPosition = 'wali';
                    else this.currentPosition = 'guru';
                }
            }

            // Force update positionName
            this.updatePositionName();

            // Auto-select unit if fixed
            if (window.IS_WORKSPACE_FIXED && window.WORKSPACE_FIXED_UNIT) {
                this.currentUnit = window.WORKSPACE_FIXED_UNIT;
            }

            // 3. Hydrate from Server-side Pre-fetch
            const initData = window.INITIAL_DATA || {};
            if (this.currentPosition === 'kepala' && initData.unitStats) {
                const us = initData.unitStats;
                this.unitStats.studentCount = us.studentCount || 0;
                this.unitStats.classCount = us.classCount || 0;
            }
            if (initData.homeroomClass) {
                const hc = initData.homeroomClass;
                this.homeroomClassId = hc.id;
                this.className = hc.name;
                if (hc.unit_code && !window.IS_WORKSPACE_FIXED) {
                    this.currentUnit = hc.unit_code.toLowerCase();
                }
                this.selectedClassId = hc.id;
            }

            // 4. Initial Data Fetch (Deferred)
            setTimeout(() => {
                if (this.currentUnit === 'asrama') {
                    this.activeTab = 'ZERO';
                    this.fetchAsramaStats();
                } else {
                    if (this.currentPosition === 'kepala') {
                        this.fetchUnitStats();
                    } else if (this.currentPosition === 'guru') {
                        this.fetchTeacherData();
                    } else {
                        this.fetchAttendance();
                        this.fetchStats();
                        this.fetchAcademicAgenda();
                        this.fetchCounselingSummary();
                        if (this.currentPosition === 'wali') {
                            if (this.canSwitchClass) this.fetchClassesForUnit();
                            
                            if (!this.homeroomClassId) this.fetchHomeroomClass();
                            else this.fetchClassAttendanceSummary();
                        }
                    }
                }
                
                // Ensure selector is populated for all views if user has access
                if (this.canSwitchClass && this.currentUnit !== 'asrama' && this.classesOptions.length === 0) {
                    this.fetchClassesForUnit();
                }
                
                this.refreshActivities();
                
                // Hide loading screen
                const loadingScreen = document.getElementById('loading-screen');
                if (loadingScreen) loadingScreen.style.display = 'none';
                this.loading = false;

                // Auto-scroll timeline on mobile
                if (this.currentPosition === 'guru') {
                    setTimeout(() => this.scrollToCurrentTime(), 500);
                }
            }, 100);
        });

        // Auto-refresh on window focus
        window.addEventListener('focus', this.handleWindowFocus);
        
        // Clock
        setInterval(() => {
            this.currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }, 1000);
        
        this.showClassUnlockModal = false;
    },
    computed: {
        filteredClassStudents() {
            if (!this.classStudents) return [];
            if (!this.studentSearch) return this.classStudents;
            const q = String(this.studentSearch || '').toLowerCase();
            return this.classStudents.filter(s => 
                (s.name && s.name.toLowerCase().includes(q)) || 
                (s.nis && s.nis.includes(q))
            );
        },
        teacherTodaySchedule() {
            if (!this.teacherSchedule || this.teacherSchedule.length === 0) return [];
            
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const todayIndex = new Date().getDay();
            const todayName = days[todayIndex].toUpperCase();
            
            return this.teacherSchedule.filter(s => (s.day || '').toUpperCase() === todayName);
        },
        teacherScheduleSorted() {
            // Sort by Day then Time
            if (!this.teacherSchedule) return [];
            const dayOrder = { 'SENIN':1, 'SELASA':2, 'RABU':3, 'KAMIS':4, 'JUMAT':5, 'SABTU':6, 'MINGGU':7 };
            
            return [...this.teacherSchedule].sort((a,b) => {
                const da = dayOrder[(a.day||'').toUpperCase()] || 99;
                const db = dayOrder[(b.day||'').toUpperCase()] || 99;
                if (da !== db) return da - db;
                return (a.time || '').localeCompare(b.time || '');
            });
        },
        allowedPositions() {
            return this.userRoles || { kepala: false, wakasek: false, wali: false, guru: false };
        },
        unitName() {
            return (this.currentUnit || '').toUpperCase();
        },
        isRegularTeacher() {
            const r = String(this.userRole || '').toUpperCase();
            return !['SUPERADMIN', 'ADMIN'].includes(r);
        }
    },
    methods: {
        async loadPdfDocument(url) {
            if (!url || typeof window.pdfjsLib === 'undefined') {
                console.warn('PDF.js library not found or URL empty');
                this.pdfError = 'Library PDF.js tidak ditemukan';
                return;
            }
            this.pdfLoading = true;
            this.pdfError = null;
            try {
                // Use global pdfjsLib via window
                const finalUrl = url.startsWith('http') ? url : this.baseUrl + url;
                console.log('Thumbnail: Loading PDF from:', finalUrl);
                
                const loadingTask = window.pdfjsLib.getDocument(finalUrl);
                const pdf = await loadingTask.promise;
                
                // Use markRaw if available to prevent Proxy wrapping
                if (typeof Vue !== 'undefined' && Vue.markRaw) {
                    this.pdfDoc = Vue.markRaw(pdf);
                } else {
                    this.pdfDoc = pdf; // Fallback
                }
                
                await this.renderPdfThumbnail(this.displaySettings.pdfPage || 1);
            } catch (err) {
                console.error('Error loading PDF for thumbnail:', err);
                this.pdfError = 'Gagal memuat PDF: ' + err.message;
            } finally {
                this.pdfLoading = false;
            }
        },

        async renderPdfThumbnail(num) {
            if (!this.pdfDoc) return;
            
            try {
                const page = await this.pdfDoc.getPage(num);
                
                // Wait for DOM update
                await this.$nextTick();
                
                const canvas = this.$refs.pdfThumbnailCanvas;
                if (!canvas) return;

                const context = canvas.getContext('2d');
                
                // Fit to container logic
                const container = canvas.parentElement;
                const containerWidth = container ? container.clientWidth : 300;
                const containerHeight = container ? container.clientHeight : 200;
                
                const viewport = page.getViewport({ scale: 1.0 });
                const scaleX = containerWidth / viewport.width;
                const scaleY = containerHeight / viewport.height;
                const scale = Math.min(scaleX, scaleY) * 0.95; // 95% fit
                
                const scaledViewport = page.getViewport({ scale });

                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: scaledViewport
                };
                
                await page.render(renderContext).promise;
            } catch (err) {
                console.error('Render error:', err);
            }
        },

        async uploadOverrideImage() {
            if (!this.classSlug) return;
            const input = this.$refs.overrideImageInput;
            if (!input || !input.files || input.files.length === 0) {
                alert('Pilih file gambar terlebih dahulu');
                return;
            }

            this.displaySettings.isUploadingImage = true;
            const formData = new FormData();
            formData.append('image', input.files[0]);

            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?action=upload_image&slug=${this.classSlug}`, {
                    method: 'POST',
                    body: formData
                });
                const j = await res.json();
                
                if (j.success) {
                    this.displaySettings.imageOverride = j.url;
                    this.displaySettings.imageActive = true;
                    alert('Gambar berhasil diupload dan diaktifkan!');
                    // Clear input
                    input.value = ''; 
                    this.fetchImageHistory(); // Refresh list
                } else {
                    alert('Gagal upload: ' + j.message);
                }
            } catch (e) {
                console.error('Upload error', e);
                alert('Terjadi kesalahan saat upload gambar');
            } finally {
                this.displaySettings.isUploadingImage = false;
            }
        },
        async fetchImageHistory() {
            if (!this.classSlug) return;
            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?action=get_images&slug=${this.classSlug}`);
                const j = await res.json();
                if (j.success) {
                    this.displaySettings.imageHistory = j.data;
                }
            } catch (e) {
                console.error('Failed to fetch image history', e);
            }
        },
        async setActiveImage(id) {
            if (!this.classSlug || !id) return;
            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?action=set_active_image&slug=${this.classSlug}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const j = await res.json();
                if (j.success) {
                    // Refresh settings and history
                    this.fetchDisplaySettings(); 
                } else {
                    alert('Gagal: ' + j.message);
                }
            } catch (e) {
                alert('Error activating image');
            }
        },
        async deleteImage(id) {
            if (!this.classSlug || !id) return;
            if (!confirm('Hapus gambar ini?')) return;
            
            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?action=delete_image&slug=${this.classSlug}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const j = await res.json();
                if (j.success) {
                    this.fetchImageHistory();
                    // If the deleted image was active, refresh settings to reflect no active image
                    // Check if deleted image was the active one is hard locally, just refresh
                    this.fetchDisplaySettings();
                } else {
                    alert('Gagal hapus: ' + j.message);
                }
            } catch (e) {
                alert('Error deleting image');
            }
        },
        async fetchDisplaySettings() {
            if (!this.classSlug) return;
            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?action=get&slug=${this.classSlug}`);
                const j = await res.json();
                if (j.success && j.data) {
                    this.displaySettings.tickerText = j.data.tickerText || '';
                    this.displaySettings.tickerActive = j.data.tickerActive;
                    this.displaySettings.urgentText = j.data.urgentText || '';
                    this.displaySettings.urgentActive = j.data.urgentActive;
                    this.displaySettings.imageOverride = j.data.imageOverride || '';
                    this.displaySettings.imageActive = j.data.imageActive;
                    
                    // PDF Settings
                    this.displaySettings.pdfActive = j.data.pdfActive;
                    this.displaySettings.pdfUrl = j.data.pdfUrl || '';
                    this.displaySettings.pdfPage = j.data.pdfPage || 1;
                    this.displaySettings.pdfId = j.data.pdfId;
                    
                    // AI Settings
                    this.displaySettings.aiActive = j.data.aiActive || false;
                    
                    // Laser Settings
                    this.displaySettings.laserActive = j.data.laserActive || false;
                    this.displaySettings.laserX = j.data.laserX || 0;
                    this.displaySettings.laserY = j.data.laserY || 0;
                    
                    if (this.displaySettings.pdfActive && this.displaySettings.pdfUrl) {
                        this.currentPDF = {
                            id: j.data.pdfId,
                            filename: j.data.pdfUrl.split('/').pop(),
                            page: j.data.pdfPage
                        };
                    } else {
                        this.currentPDF = null;
                    }
                    
                    this.fetchImageHistory(); // Also fetch history
                }
            } catch (e) {
                console.error('Failed to fetch display settings', e);
            }
        },
        async saveDisplaySettings(type) {
            if (!this.classSlug) return;
            
            let text = '';
            let isActive = false;
            
            if (type === 'ticker') {
                text = this.displaySettings.tickerText;
                isActive = this.displaySettings.tickerActive;
                if (isActive && !text) {
                    alert('Mohon isi teks ticker terlebih dahulu!');
                    // Revert switch visually but wait for vue to update
                    this.displaySettings.tickerActive = false;
                    return;
                }
            } else if (type === 'urgent') {
                text = this.displaySettings.urgentText;
                isActive = this.displaySettings.urgentActive;
                
                // If trying to activate but no text
                if (isActive && !text) {
                    alert('Mohon isi pesan penting terlebih dahulu!');
                    this.displaySettings.urgentActive = false;
                    return;
                }
                
                // If just updating text (isActive=false), warn if empty? No, maybe they want to clear it.
            } else if (type === 'image_override') {
                text = this.displaySettings.imageOverride; // Send the current image URL
                isActive = this.displaySettings.imageActive;
            } else if (type === 'pdf_active') {
                type = 'pdf_override';
                text = this.displaySettings.pdfUrl;
                isActive = this.displaySettings.pdfActive;
            } else if (type === 'ai_active') {
                // Delegate completely to showAIOnTV which handles both ON (with content) and OFF
                // If turning ON but no content, showAIOnTV might fail or do nothing, which is fine.
                // If turning OFF, showAIOnTV calls update_ai_status.
                if (isActive && !this.aiResult) {
                    alert('Generate konten terlebih dahulu!');
                    // We need to revert the switch visually next tick
                    this.$nextTick(() => {
                        this.displaySettings.aiActive = false;
                    });
                    return;
                }
                this.showAIOnTV();
                return;
            }
            
            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?slug=${this.classSlug}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type: type,
                        text: text,
                        isActive: isActive
                    })
                });
                const j = await res.json();
                if (j.success) {
                    if (type !== 'image_override' && type !== 'pdf_override') { // Don't alert for toggle switch
                        alert('Settings updated successfully!');
                    }
                    // Refresh settings to ensure mutual exclusion is reflected in UI
                    await this.fetchDisplaySettings();
                } else {
                    alert('Failed: ' + j.message);
                    // Revert toggle if failed
                    if (type === 'image_override') {
                        this.displaySettings.imageActive = !isActive;
                    } else if (type === 'pdf_override') {
                        this.displaySettings.pdfActive = !isActive;
                    }
                }
            } catch (e) {
                alert('Error saving settings');
            }
        },
        async saveMaterial() {
            if (!this.classSlug) return;
            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?slug=${this.classSlug}&action=save_material`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        text: this.displaySettings.materialText
                    })
                });
                const j = await res.json();
                if (j.success) {
                    alert('Materi berhasil diunggah!');
                } else {
                    alert('Gagal: ' + j.message);
                }
            } catch (e) {
                alert('Error menyimpan materi');
            }
        },
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link copied to clipboard!');
            });
        },
        isCurrentHour(h) {
            const now = new Date();
            const currentH = now.getHours();
            return parseInt(h) === currentH;
        },
        hasScheduleAt(h) {
            if (!this.teacherTodaySchedule || this.teacherTodaySchedule.length === 0) return false;
            return this.teacherTodaySchedule.some(s => {
                const t = s.time || '';
                const start = parseInt(t.split(':')[0]);
                return start === h;
            });
        },
        scrollToCurrentTime() {
            const now = new Date();
            const h = now.getHours();
            if (h < 7 || h > 16) return;
            
            const el = document.getElementById('time-slot-' + h);
            if (el && this.$refs.timelineContainer) {
                const container = this.$refs.timelineContainer;
                const scrollLeft = el.offsetLeft - (container.offsetWidth / 2) + (el.offsetWidth / 2);
                container.scrollTo({ left: scrollLeft, behavior: 'smooth' });
            }
        },
        updatePositionName() {
            if (this.currentPosition === 'kepala') this.positionName = 'Kepala Sekolah';
            else if (this.currentPosition === 'wakasek') this.positionName = 'Wakil Kepala';
            else if (this.currentPosition === 'wali') this.positionName = 'Wali Kelas';
            else if (this.currentPosition === 'guru') this.positionName = 'Guru Mapel';
            else this.positionName = 'Staff';
        },
        async safeFetch(url, options = {}) {
            try {
                const res = await fetch(url, options);
                if (!res.ok) {
                    // console.warn(`HTTP Error ${res.status} for ${url}`);
                    return { success: false, message: `HTTP Error ${res.status}` };
                }
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    // console.warn('Invalid JSON from', url, text.substring(0, 50) + '...');
                    return { success: false, message: 'Invalid JSON response' };
                }
            } catch (e) {
                console.error('Network/Fetch error:', url, e);
                return { success: false, message: 'Network error' };
            }
        },

        async fetchAccessibleUnits() {
            try {
                this.normalizeBaseUrl();
                const res = await this.safeFetch(`${this.baseUrl}api/workspace_academic.php?action=get_accessible_units`);
                if (res && res.success && Array.isArray(res.units)) {
                    this.accessibleUnits = res.units.map(u => u.toLowerCase());
                } else {
                    // Fallback: assume current unit is the only one
                    this.accessibleUnits = [this.currentUnit];
                }
            } catch (e) {
                console.error('Error fetching accessible units:', e);
            }
        },

        async fetchAvailableUnits() {
            try {
                this.normalizeBaseUrl();
                // Use existing get_units.php or similar endpoint
                // We'll use a new action in workspace_academic.php to be safe and consistent
                // Or just use the one we have? 
                // Let's use api/get_units.php which returns array of units
                const res = await this.safeFetch(`${this.baseUrl}api/get_units.php`);
                if (Array.isArray(res)) {
                    this.availableUnits = res;
                } else if (res && res.data && Array.isArray(res.data)) {
                     this.availableUnits = res.data;
                } else {
                    this.availableUnits = [];
                }
            } catch (e) {
                console.error('Error fetching available units:', e);
                this.availableUnits = [];
            }
        },

        async fetchBestUnit() {
            try {
                this.normalizeBaseUrl();
                const res = await this.safeFetch(`${this.baseUrl}api/workspace_academic.php?action=get_best_unit`);
                if (res && res.success && res.unit) {
                    // Only switch if current unit is not set or not allowed, or if server suggests a better one
                    // For now, let's trust the server's suggestion if it's different
                    
                    // Fix [OBJECT OBJECT] bug: res.unit is an object, we need the code
                    const suggestedUnitCode = (res.unit.code || '').toLowerCase();
                    
                    if (suggestedUnitCode && this.currentUnit !== suggestedUnitCode) {
                        console.log(`Auto-switching unit from ${this.currentUnit} to ${suggestedUnitCode}`);
                        this.currentUnit = suggestedUnitCode;
                        localStorage.setItem('workspaceUnit', suggestedUnitCode);
                    }
                }
            } catch (e) {
                console.error('Error fetching best unit:', e);
            }
        },

        async fetchUserRoles() {
            try {
                this.normalizeBaseUrl();
                const res = await this.safeFetch(`${this.baseUrl}api/workspace_academic.php?action=get_user_roles&unit=${this.currentUnit}`);
                if (res && res.success && res.data) {
                    this.userRoles = res.data;
                    
                    // Auto-switch logic: If current position is invalid, switch to best available
                    const pos = this.currentPosition;
                    let isValid = false;
                    
                    if (pos === 'kepala' && this.userRoles.kepala) isValid = true;
                    else if (pos === 'wakasek' && this.userRoles.wakasek) isValid = true;
                    else if (pos === 'wali' && this.userRoles.wali) isValid = true;
                    else if (pos === 'guru' && this.userRoles.guru) isValid = true;
                    
                    // Special case: Admin/Superadmin (backend returns all true, so isValid is true)
                    
                    if (!isValid) {
                        if (this.userRoles.kepala) this.currentPosition = 'kepala';
                        else if (this.userRoles.wakasek) this.currentPosition = 'wakasek';
                        else if (this.userRoles.wali) this.currentPosition = 'wali';
                        else if (this.userRoles.guru) this.currentPosition = 'guru';
                    }
                }
            } catch (e) {
                console.error('Error fetching user roles:', e);
            }
        },

        isPositionLocked(pos) {
            // 1. Superadmins can access everything
            const r = String(this.userRole || '').toUpperCase();
            if (r === 'SUPERADMIN' || r === 'ADMIN') return false;

            // 2. Check specific role access for this unit based on fetched roles
            // This allows multi-role users (e.g. Principal + Teacher) to switch views
            if (pos === 'kepala' && this.userRoles.kepala) return false;
            if (pos === 'wakasek' && this.userRoles.wakasek) return false;
            if (pos === 'wali' && this.userRoles.wali) return false;
            if (pos === 'guru' && this.userRoles.guru) return false;

            // 3. Default: Locked
            return true;
        },
        async fetchTeacherData() {
            try {
                this.normalizeBaseUrl();
                // Fetch ALL units for teacher data as requested: "tampilan guru... selector semua menyala"
                const res = await this.safeFetch(`${this.baseUrl}api/workspace_academic.php?action=get_teacher_data&unit=all`);
                
                if (res && res.success && res.data) {
                    // Map schedule to include unit name in class display
                    this.teacherSchedule = (res.data.schedule || []).map(s => {
                        if (s.unit) {
                            // Append unit code to class name, e.g. "XC (SMA)"
                            // Check if class already has parens to avoid double
                            if (!s.class.includes('(')) {
                                s.class = `${s.class} (${s.unit})`;
                            }
                        }
                        return s;
                    });

                    this.teacherSubjects = res.data.subjects || [];
                    
                    // Update Stats
                    this.teacherStats = {
                        totalJP: res.data.total_jp || 0,
                        totalSubjects: res.data.total_subjects_taught || 0,
                        // totalStudents: 102 // Still mocked or need aggregation
                    };
                    
                    // Map Inventory fields
                    this.teacherInventory = (res.data.inventory || []).map(i => ({
                        id: i.id,
                        name: i.name,
                        code: i.code,
                        condition: i.condition_status || 'Baik',
                        status: i.location || 'Digunakan', // Map location to status for display
                        location: i.location
                    }));

                    // Map Task fields
                    this.teacherTasks = (res.data.tasks || []).map(t => ({
                        id: t.id,
                        title: t.title,
                        description: t.description,
                        due: t.due_date, // Map due_date to due
                        status: t.status,
                        priority: t.priority
                    }));
                } else {
                    console.error("Failed to fetch teacher data:", res ? res.error : 'Unknown error');
                    // Fallback to empty or keep previous state
                }
            } catch (e) {
                console.error("Error fetching teacher data:", e);
            }
        },
        positionClass(code) {
            const active = this.currentPosition === code;
            let cls = active ? 'bg-white text-slate-800 shadow ring-1 ring-slate-200' : 'text-slate-600';
            
            // Add visual lock if needed (though disabled attribute handles most interaction)
            if (this.isPositionLocked(code)) {
                cls += ' opacity-50 cursor-not-allowed pointer-events-none';
            }
            return cls;
        },
        getMonthName(m) {
            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return months[m-1] || '';
        },
        confirmValidateMonth(m) {
            this.openValidationModal(m);
        },
        openValidationModal(m) {
            if (!this.homeroomClassId) return;
            this.attendanceBatchMonth = m.val;
            this.attendanceBatchYear = m.year;
            this.attendanceValidationModal = true;
            this.fetchBatchStudents();
        },
        async fetchBatchStudents() {
            this.attendanceBatchLoading = true;
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + `api/attendance.php?action=get_students_for_attendance&class_id=${this.homeroomClassId}&month=${this.attendanceBatchMonth}&year=${this.attendanceBatchYear}`);
                if (data && data.success) {
                    this.attendanceBatchStudents = data.data.map(s => ({
                        ...s,
                        izin: Number(s.izin) || 0,
                        sakit: Number(s.sakit) || 0,
                        alfa: Number(s.alfa) || 0,
                        cuti: Number(s.cuti) || 0,
                        hadir: (typeof s.hadir === 'number' ? s.hadir : Number(s.hadir)) || this.attendanceBatchActiveDays,
                        remarks: s.remarks || ''
                    }));
                    if (this.attendanceBatchStudents.length > 0 && this.attendanceBatchStudents[0].active_days) {
                        this.attendanceBatchActiveDays = this.attendanceBatchStudents[0].active_days;
                    }
                    this.recalculateAllBatch();
                }
            } catch (e) {
                alert('Gagal mengambil data siswa');
            } finally {
                this.attendanceBatchLoading = false;
            }
        },
        recalculateBatch(student) {
            const active = Number(this.attendanceBatchActiveDays) || 0;
            const izin = Number(student.izin) || 0;
            const sakit = Number(student.sakit) || 0;
            const alfa = Number(student.alfa) || 0;
            const cuti = Number(student.cuti) || 0;
            const exceptions = izin + sakit + alfa + cuti;
            student.hadir = Math.max(0, active - exceptions);
        },
        recalculateAllBatch() {
            this.attendanceBatchStudents.forEach(s => this.recalculateBatch(s));
        },
        async saveBatchAttendance() {
            this.attendanceBatchSaving = true;
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=save_attendance_batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        class_id: this.homeroomClassId,
                        month: this.attendanceBatchMonth,
                        year: this.attendanceBatchYear,
                        active_days: this.attendanceBatchActiveDays,
                        records: this.attendanceBatchStudents
                    })
                });
                if (data && data.success) {
                    alert('Presensi berhasil disimpan!');
                    this.attendanceValidationModal = false;
                    await this.fetchClassAttendanceSummary();
                    this.generateAcademicMonths();
                } else {
                    alert((data && data.message) ? data.message : 'Gagal menyimpan presensi');
                }
            } catch (e) {
                alert('Terjadi kesalahan sistem');
            } finally {
                this.attendanceBatchSaving = false;
            }
        },
        async fetchClassAttendanceSummary() {
            if (!this.homeroomClassId) { this.classAttendanceSummary = []; return; }
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=get_class_attendance_summary&class_id=' + encodeURIComponent(this.homeroomClassId));
                this.classAttendanceSummary = j && j.success ? (j.data || []) : [];
            } catch (_) {
                this.classAttendanceSummary = [];
            }
        },
        async fetchHomeroomClass() {
            // If user has selector access and has selected a class, don't overwrite with assigned class
            if (this.canSwitchClass && this.selectedClassId) return;

            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=get_homeroom_class');
                const d = j && j.success ? j.data : null;
                if (d && d.id) {
                    this.homeroomClassId = d.id;
                    this.className = d.name || '';
                    this.classSlug = d.slug || '';
                    
                    // Check Lock Status
                    if (this.currentPosition === 'wali') {
                        this.checkLockStatus();
                    }

                    await this.fetchDailyAttendance();
                    await this.fetchClassAttendanceSummary();
                    await this.fetchPendingIssues();
                    await this.fetchClassSchedule();
                    await this.fetchBkSchedule();
                    await this.fetchBkProfile();
                    await this.fetchBkArticles();
                    this.generateAcademicMonths(); // Ensure data is ready
                } else {
                    this.homeroomClassId = null;
                    this.className = '';
                    this.classStudents = [];
                    this.attendancePercent = 0;
                    this.absentStudents = [];
                    this.classAttendanceSummary = [];
                    await this.fetchPendingIssues();
                    this.classScheduleMap = {};
                    this.bkSchedule = [];
                    this.bkProfile = { profile: {}, team: [] };
                    this.bkArticles = [];
                }
            } catch (_) {
                this.homeroomClassId = null;
                this.className = '';
                this.classStudents = [];
                this.attendancePercent = 0;
                this.absentStudents = [];
                this.classAttendanceSummary = [];
                await this.fetchPendingIssues();
                this.classScheduleMap = {};
                this.bkSchedule = [];
                this.bkProfile = { profile: {}, team: [] };
                this.bkArticles = [];
            }
        },
        async fetchClassesForUnit() {
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/get_academic_data.php?unit=' + encodeURIComponent(this.currentUnit));
                const arr = Array.isArray(j.classes) ? j.classes : [];
                this.classesOptions = arr.map(c => ({ id: c.id, name: c.name }));
                const key = 'waliSelectedClassId_' + this.currentUnit;
                const saved = localStorage.getItem(key) || '';
                if (saved) {
                    this.selectedClassId = saved;
                    this.applySelectedClass();
                }
            } catch (_) {
                this.classesOptions = [];
            }
        },
        async applySelectedClass() {
            const id = this.selectedClassId;
            if (!id) {
                this.homeroomClassId = null;
                this.className = '';
                this.classStudents = [];
                this.classAttendanceSummary = [];
                return;
            }
            const found = this.classesOptions.find(c => String(c.id) === String(id));
            this.homeroomClassId = parseInt(id, 10);
            this.className = found ? (found.name || '') : '';
            localStorage.setItem('waliSelectedClassId_' + this.currentUnit, String(id));
            
            // Auto-switch to Wali view to see the selected class data
            if (this.currentPosition !== 'wali') {
                this.currentPosition = 'wali';
            }

            // Check Lock Status
            if (this.currentPosition === 'wali') {
                this.checkLockStatus();
            }

            this.fetchDailyAttendance();
            this.fetchMonthlyData();
            await this.fetchClassAttendanceSummary();
            this.fetchPendingIssues();
            this.fetchClassSchedule();
            this.fetchBkSchedule();
            this.fetchBkProfile();
            this.fetchBkArticles();
            this.generateAcademicMonths(); // Update on class change
        },
        getScheduleItem(dayLabel, timeHHMM) {
            const day = String(dayLabel || '').toUpperCase();
            const t = String(timeHHMM || '');
            const m = this.classScheduleMap || {};
            if (!m[day]) return null;
            const row = m[day][t] || null;
            return row || null;
        },
        async fetchClassSchedule() {
            if (!this.homeroomClassId) { this.classScheduleMap = {}; return; }
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/get_schedule.php?class_id=' + encodeURIComponent(this.homeroomClassId));
                // j is grouped: { SENIN: { '07:00': {...} }, ... }
                const map = (j && typeof j === 'object') ? j : {};
                // Normalize time keys to HH:MM
                const out = {};
                const days = ['SENIN','SELASA','RABU','KAMIS','JUMAT'];
                days.forEach(d => { out[d] = {}; });
                Object.keys(map).forEach(d => {
                    const dd = String(d || '').toUpperCase();
                    const slots = map[d] || {};
                    Object.keys(slots).forEach(k => {
                        const hhmm = String(k || '').slice(0,5);
                        out[dd][hhmm] = slots[k];
                    });
                });
                this.classScheduleMap = out;
            } catch (_) {
                this.classScheduleMap = {};
            }
        },
        async fetchBkSchedule() {
            if (!this.homeroomClassId) { this.bkSchedule = []; return; }
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/counseling.php?action=list_bk_schedule&class_id=' + encodeURIComponent(this.homeroomClassId));
                this.bkSchedule = j && j.success ? (j.data || []) : [];
            } catch (_) {
                this.bkSchedule = [];
            }
        },
        async fetchDailyAttendance() {
            if (!this.homeroomClassId) return;
            try {
                this.normalizeBaseUrl();
                const url = this.baseUrl + 'api/attendance.php?action=get_daily_attendance&class_id=' + encodeURIComponent(this.homeroomClassId) + '&date=' + encodeURIComponent(this.dailyDate);
                const j = await this.safeFetch(url);
                const rows = j && j.success ? (j.data || []) : [];
                this.attendanceSubmitted = rows.length > 0 && rows.some(r => r.status != null);
                this.classStudents = rows.map(s => {
                    const st = String(s.status || 'HADIR').toUpperCase();
                    const map = { 'HADIR': 'Hadir', 'IZIN': 'Izin', 'SAKIT': 'Sakit', 'ALFA': 'Alfa', 'CUTI': 'Cuti' };
                    return { 
                        id: s.id, 
                        name: s.name, 
                        nis: s.nis, 
                        gender: s.gender, 
                        birth_place: s.birth_place, 
                        birth_date: s.birth_date, 
                        status: map[st] || 'Hadir' 
                    };
                });
                
                // Calculate Stats
                const total = this.classStudents.length;
                const present = this.classStudents.filter(s => s.status === 'Hadir').length;
                this.attendancePercent = total > 0 ? Math.round((present / total) * 100) : 0;
                this.absentStudents = this.classStudents.filter(s => s.status !== 'Hadir');

                await this.fetchMonthlyData();
            } catch (_) {
                this.classStudents = [];
                this.attendancePercent = 0;
                this.absentStudents = [];
            }
        },
        async fetchMonthlyData() {
            if (!this.homeroomClassId) return;
            try {
                this.normalizeBaseUrl();
                const qs = `class_id=${encodeURIComponent(this.homeroomClassId)}&month=${encodeURIComponent(this.monthlyMonth)}&year=${encodeURIComponent(this.monthlyYear)}`;
                const j1 = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=get_class_attendance_month&' + qs);
                this.monthlySource = j1 && j1.success && j1.data && j1.data.source ? j1.data.source : '-';
                
                const j2 = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=get_daily_calendar&' + qs);
                const rawCalendar = j2 && j2.success ? (j2.data || []) : [];
                
                // Generate full calendar
                const year = parseInt(this.monthlyYear);
                const month = parseInt(this.monthlyMonth);
                const daysInMonth = new Date(year, month, 0).getDate();
                
                // Calculate spacers for Monday start
                const firstDayOfWeek = new Date(year, month - 1, 1).getDay(); // 0=Sun, 1=Mon...
                const spacerCount = (firstDayOfWeek + 6) % 7;
                
                const fullCalendar = [];
                // Add spacers
                for (let i = 0; i < spacerCount; i++) {
                    fullCalendar.push({ date: null });
                }

                for (let d = 1; d <= daysInMonth; d++) {
                    const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const found = rawCalendar.find(x => x.date === dateStr);
                    if (found) {
                        fullCalendar.push(found);
                    } else {
                        // Mark as pending/not validated
                        fullCalendar.push({
                            date: dateStr,
                            hadir: 0, izin: 0, sakit: 0, alfa: 0, cuti: 0,
                            present_pct: -1 // Special value for pending
                        });
                    }
                }
                this.monthlyCalendar = fullCalendar;

                const j3 = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=get_month_student_attendance&' + qs);
                this.monthlyStudentRows = j3 && j3.success ? (j3.data || []) : [];
                const scored = this.monthlyStudentRows.map(s => ({ ...s, score: (s.alfa + s.izin + s.sakit) }));
                scored.sort((a,b) => b.score - a.score);
                this.topAbsentees = scored.slice(0, 5);
            } catch (_) {
                this.monthlySource = '-';
                this.monthlyCalendar = [];
                this.monthlyStudentRows = [];
                this.topAbsentees = [];
            }
        },
        calendarBarClass(pct) {
            if (pct === -1) return 'bg-red-500'; // Pending/Not Validated
            if (pct >= 90) return 'bg-emerald-600';
            if (pct >= 75) return 'bg-amber-500';
            return 'bg-rose-700'; // Bad attendance
        },
        exportMonthlyCsv() {
            const rows = this.monthlyStudentRows || [];
            const header = ['Nama','NIS','Hadir','Izin','Sakit','Alfa','Cuti','Hari Aktif'];
            const lines = [header.join(',')].concat(rows.map(r => [r.name, r.nis || '', r.hadir, r.izin, r.sakit, r.alfa, r.cuti, r.active_days].join(',')));
            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `rekap_${this.className || 'kelas'}_${this.monthlyMonth}_${this.monthlyYear}.csv`;
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        },
        openDailyVerify() {
            if (!this.homeroomClassId) { alert('Kelas wali tidak ditemukan.'); return; }
            this.dailyVerifyModal = true;
        },
        async saveDailyBatch() {
            try {
                if (!this.homeroomClassId) return;
                const recs = this.classStudents.map(s => {
                    const up = String(s.status || 'Hadir').toUpperCase();
                    const map = { 'HADIR':'HADIR','IZIN':'IZIN','SAKIT':'SAKIT','ALFA':'ALFA','CUTI':'CUTI' };
                    return { id: s.id, status: map[up] || 'HADIR', note: '', request_id: String(Date.now()) + '-' + s.id };
                });
                const payload = { class_id: this.homeroomClassId, date: this.dailyDate, records: recs };
                const j = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=save_attendance_daily_batch', { method: 'POST', body: JSON.stringify(payload) });
                if (j && j.success) {
                    this.dailyVerifyModal = false;
                    await this.fetchDailyAttendance();
                    await this.fetchMonthlyData();
                    alert('Presensi harian tersimpan.');
                } else {
                    alert('Gagal menyimpan presensi.');
                }
            } catch (_) { alert('Gagal menyimpan presensi.'); }
        },
        async rollupMonthlyFromDaily() {
            try {
                if (!this.homeroomClassId) { alert('Kelas wali tidak ditemukan.'); return; }
                const d = new Date(this.dailyDate);
                const m = d.getMonth() + 1;
                const y = d.getFullYear();
                const form = new URLSearchParams();
                form.set('class_id', String(this.homeroomClassId));
                form.set('month', String(m));
                form.set('year', String(y));
                const j = await this.safeFetch(this.baseUrl + 'api/attendance.php?action=rollup_daily_to_monthly', { method: 'POST', body: form });
                if (j && j.success) {
                    alert('Rekap bulanan digenerate dari harian.');
                } else {
                    alert('Gagal generate rekap bulanan.');
                }
            } catch (_) { alert('Gagal generate rekap bulanan.'); }
        },
        openPortfolio(s) {
            this.portfolioStudent = { academic_avg: 86, academic_top_subject: 'Matematika', library_loans: 2, library_late: 0, bk_points: 3, bk_active_cases: 0 };
            this.studentPortfolioModal = true;
        },
        deleteTeacherNote(idx) {
            if (idx < 0 || idx >= this.teacherNotes.length) return;
            this.teacherNotes.splice(idx, 1);
        },
        addTeacherNote() {
            const t = (this.teacherNoteInput || '').trim();
            if (!t) return;
            this.teacherNotes.push({ id: Date.now(), text: t, ts: Date.now() });
            this.teacherNoteInput = '';
        },
        truncateName(name) {
            const n = String(name || '').trim();
            if (!n) return '';
            const words = n.split(/\s+/);
            const cut = words.slice(0, 3).join(' ');
            if (words.length > 3) return cut + '…';
            if (cut.length > 26) return cut.slice(0, 26) + '…';
            return cut;
        },
        openResolveModal(issue) {
            this.resolveIncident = issue;
            this.resolveNote = '';
            this.resolveModal = true;
        },
        async submitResolveInternal() {
            try {
                if (!this.resolveIncident) return;
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/student_incidents.php?action=resolve_internal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.resolveIncident.id, note: this.resolveNote })
                });
                if (j && j.success) {
                    this.resolveModal = false;
                    await this.fetchPendingIssues();
                    alert('Isu ditandai selesai dengan peringatan.');
                } else {
                    alert('Gagal menyelesaikan: ' + ((j && (j.error || j.message)) || ''));
                }
            } catch (_) {
                alert('Kesalahan saat menyelesaikan.');
            }
        },
        openEscalateModal(issue) {
            this.escalateIncident = issue;
            this.escalateNote = '';
            this.escalateModal = true;
        },
        async submitEscalateBK() {
            try {
                if (!this.escalateIncident) return;
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/student_incidents.php?action=escalate_to_bk', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.escalateIncident.id, intro_note: this.escalateNote })
                });
                if (j && j.success) {
                    this.escalateModal = false;
                    await this.fetchPendingIssues();
                    alert('Isu dieskalasi ke BK dengan catatan pengantar.');
                } else {
                    alert('Gagal eskalasi: ' + ((j && (j.error || j.message)) || ''));
                }
            } catch (_) {
                alert('Kesalahan saat eskalasi.');
            }
        },
        async fetchPendingIssues() {
            try {
                if (this.currentPosition !== 'wali') { this.pendingIssues = []; return; }
                this.normalizeBaseUrl();
                let url = this.baseUrl + 'api/student_incidents.php?action=list_pending_for_homeroom';
                if (!this.homeroomClassId && this.selectedClassId) {
                    url = this.baseUrl + 'api/student_incidents.php?action=list_pending_for_class&class_id=' + encodeURIComponent(this.selectedClassId);
                }
                const j = await this.safeFetch(url);
                this.pendingIssues = j && j.success ? (j.data || []) : [];
            } catch (_) {
                this.pendingIssues = [];
            }
        },
        openInventoryModal(asset) {
            this.inventorySelected = asset;
            this.inventoryReportCount = 1;
            this.inventoryReportDesc = '';
            this.inventoryReportPhotoName = '';
            this.inventoryModal = true;
        },
        submitInventoryReport() {
            if (!this.inventorySelected) return;
            const item = this.inventorySelected.name;
            const count = this.inventoryReportCount || 1;
            this.maintenanceTickets.push({ id: 'MT-' + Date.now(), item, count, status: 'Pending', date: new Date().toISOString() });
            this.inventoryModal = false;
            alert('Tiket maintenance dibuat (dummy).');
        },
        submitMonthlyRecap() {
            // Validate specific month
            if (this.monthlyRecapSelected) {
                const m = this.monthlyRecaps.find(x => x.id === this.monthlyRecapSelected.id);
                if (m) {
                    m.status = 'validated';
                    m.validated_at = new Date().toISOString();
                }
                this.monthlyRecapSelected = null;
                // Check if current month is validated to update the card status
                const currentMonthIdx = new Date().getMonth(); // 0-11
                const currentMonthId = 'm' + (currentMonthIdx + 1);
                // Simple logic: if the current month is validated, mark card as submitted
                const curr = this.monthlyRecaps.find(x => x.id === currentMonthId);
                if (curr && curr.status === 'validated') {
                    this.monthlyRecapSubmitted = true;
                }
                alert('Rekap presensi bulan ' + m.name + ' berhasil divalidasi.');
            } else {
                this.monthlyRecapSubmitted = true;
                this.monthlyRecapModal = false;
                alert('Rekap presensi bulanan berhasil divalidasi.');
            }
        },
        generateAcademicMonths() {
            // Generate July - June
            const now = new Date();
            const currentMonth = now.getMonth() + 1; // 1-12
            const currentYear = now.getFullYear();
            
            // Determine academic year start
            let startYear = currentYear;
            if (currentMonth < 7) {
                startYear = currentYear - 1;
            }
            
            const months = [
                { id: 'm7', val: 7, name: 'Juli', year: startYear },
                { id: 'm8', val: 8, name: 'Agustus', year: startYear },
                { id: 'm9', val: 9, name: 'September', year: startYear },
                { id: 'm10', val: 10, name: 'Oktober', year: startYear },
                { id: 'm11', val: 11, name: 'November', year: startYear },
                { id: 'm12', val: 12, name: 'Desember', year: startYear },
                { id: 'm1', val: 1, name: 'Januari', year: startYear + 1 },
                { id: 'm2', val: 2, name: 'Februari', year: startYear + 1 },
                { id: 'm3', val: 3, name: 'Maret', year: startYear + 1 },
                { id: 'm4', val: 4, name: 'April', year: startYear + 1 },
                { id: 'm5', val: 5, name: 'Mei', year: startYear + 1 },
                { id: 'm6', val: 6, name: 'Juni', year: startYear + 1 }
            ];

            // Set status based on summary and current date
            this.monthlyRecaps = months.map(m => {
                let status = 'future';
                
                // Check if validated in DB (exists in summary)
                const exists = this.classAttendanceSummary.find(x => x.month == m.val && x.year == m.year);
                
                if (exists) {
                    status = 'validated';
                } else {
                    // Check if past or current
                    const mDate = new Date(m.year, m.val - 1, 1);
                    const nowDate = new Date(currentYear, currentMonth - 1, 1);
                    
                    if (mDate <= nowDate) {
                        status = 'pending';
                    }
                }
                
                return {
                    ...m,
                    status: status, // validated, pending, future
                    validated_at: status === 'validated' ? new Date().toISOString() : null
                };
            });
            
            // Initial check for card status (based on current month)
            const currRecap = this.monthlyRecaps.find(x => x.val === currentMonth);
            this.monthlyRecapSubmitted = currRecap ? currRecap.status === 'validated' : false;
        },
        saveWaliTasks() {
            try {
                const payload = { d: this.tasksDaily, w: this.tasksWeekly, m: this.tasksMonthly };
                localStorage.setItem('wali_tasks', JSON.stringify(payload));
            } catch(_) {}
        },
        getWaitingHours(a) {
            const created = a.created_at ? new Date(a.created_at).getTime() : null;
            if (!created) return 0;
            const diffMs = Date.now() - created;
            return Math.max(0, Math.floor(diffMs / (1000 * 60 * 60)));
        },
        getWaitingBarWidth(a) {
            const h = this.getWaitingHours(a);
            const pct = Math.min(100, Math.floor((h / 72) * 100));
            return pct + '%';
        },
        getWaitingText(a) {
            const h = this.getWaitingHours(a);
            if (h < 24) return h + ' jam';
            const d = Math.floor(h / 24);
            const rem = h % 24;
            if (rem === 0) return d + ' hari';
            return d + ' hari ' + rem + ' jam';
        },
        unitClass(u) {
            const active = this.currentUnit === u;
            // Ensure this.isUnitLocked is a function before calling
            const locked = (typeof this.isUnitLocked === 'function') ? this.isUnitLocked(u) : false;
            
            // Guru View: Highlight ALL accessible units to indicate multi-unit view
            if (this.currentPosition === 'guru') {
                // If it's accessible (not locked), highlight it
                if (!locked) return 'bg-slate-800 text-white shadow-md';
                // If it is the current unit (fallback), highlight it
                if (active) return 'bg-slate-800 text-white shadow-md';
            }

            if (active) return 'bg-slate-800 text-white shadow-md';
            if (locked) return 'text-slate-400 bg-transparent cursor-not-allowed';
            return 'text-slate-600 hover:bg-white hover:shadow-sm';
        },
        waliTabClass(code) {
            return this.homeroomTab === code ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300';
        },
        bkTabClass(code) {
            return this.bkTab === code ? 'bg-pink-600 text-white' : 'bg-slate-100 text-slate-700';
        },
        formatDate(s) {
            const d = new Date(s);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },
        activityButtonClass(code) {
            return this.activeActivityTab === code ? 'bg-slate-800 text-white ring-1 ring-slate-200' : 'bg-slate-100 text-slate-600';
        },
        setActivityTab(code) {
            this.activeActivityTab = code;
            this.fetchActivity(code);
        },
        boardingGenderClass(code) {
            return this.boardingGender === code ? 'bg-amber-600 text-white' : 'bg-white text-amber-600';
        },
        async setBoardingGender(code) {
            this.boardingGender = code;
            if (this.currentUnit === 'asrama') {
                await this.fetchBoardingCount();
            }
        },
        async fetchAttendance() {
            try {
                this.attendanceSummary = { present: 0, permit: 0, absent: 0 };
            } catch (_) {}
        },
        async fetchStats() {
            try {
                const unit = this.currentUnit;
                // Classes & student_count per class
                const j1 = await this.safeFetch(this.baseUrl + 'api/get_academic_data.php?unit=' + encodeURIComponent(unit));
                const classes = Array.isArray(j1.classes) ? j1.classes : [];
                this.classCount = classes.length;
                this.studentCount = classes.reduce((acc, c) => acc + (parseInt(c.student_count || 0, 10) || 0), 0);
                this.academicSlots = Array.isArray(j1.timeSlots) ? j1.timeSlots : [];
                // Teachers
                const j2 = await this.safeFetch(this.baseUrl + 'api/get_unit_teachers.php?unit=' + encodeURIComponent(unit) + '&type=ACADEMIC');
                let tData = [];
                if (Array.isArray(j2)) tData = j2;
                else if (j2 && Array.isArray(j2.data)) tData = j2.data;
                this.teacherCount = tData.length;
            } catch (_) {
                this.studentCount = 0;
                this.classCount = 0;
                this.teacherCount = 0;
                this.academicSlots = [];
            }
        },
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        async fetchActivity(category) {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/get_activity_logs.php?module=' + encodeURIComponent(this.moduleCode) + '&category=' + encodeURIComponent(category) + '&limit=20');
                this.activities[category] = j.success ? (j.data || []) : [];
            } catch (_) {
                this.activities[category] = [];
            }
        },
        async refreshActivities() {
            await this.fetchActivity('PRESENSI');
            await this.fetchActivity('RAPAT');
            await this.fetchActivity('KEGIATAN');
        },
        async fetchAcademicAgenda() {
            try {
                const unit = this.currentUnit;
                const j = await this.safeFetch(this.baseUrl + 'api/get_academic_data.php?unit=' + encodeURIComponent(unit));
                this.academicSlots = j && Array.isArray(j.timeSlots) ? j.timeSlots : [];
            } catch (_) {
                this.academicSlots = [];
            }
        },
        async fetchBkProfile() {
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/counseling.php?action=get_bk_unit_profile');
                this.bkProfile = j.success ? (j.data || { profile:{}, team:[] }) : { profile:{}, team:[] };
            } catch(_) { this.bkProfile = { profile:{}, team:[] }; }
        },
        async fetchBkArticles() {
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(this.baseUrl + 'api/counseling.php?action=list_psychoedu');
                this.bkArticles = j.success ? (j.data || []) : [];
            } catch(_) { this.bkArticles = []; }
        },
        async fetchCounselingSummary() {
            try {
                const unit = this.currentUnit;
                const j = await this.safeFetch(this.baseUrl + 'api/counseling.php?action=get_unit_summary&unit=' + encodeURIComponent(unit));
                this.counselingSummary = j.success ? (j.data || { achievements_recent: 0, cases_recent: 0, counseling_sessions_recent: 0 }) : { achievements_recent: 0, cases_recent: 0, counseling_sessions_recent: 0 };
            } catch (_) {
                this.counselingSummary = { achievements_recent: 0, cases_recent: 0, counseling_sessions_recent: 0 };
            }
        },
        async fetchBoardingCount() {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/boarding.php?action=get_students&gender=' + encodeURIComponent(this.boardingGender));
                if (j && typeof j === 'object') {
                    if (Array.isArray(j.data)) this.boardingCount = j.data.length;
                    else if (Array.isArray(j)) this.boardingCount = j.length;
                    else if (j.debug && typeof j.debug.count === 'number') this.boardingCount = j.debug.count;
                    else this.boardingCount = 0;
                } else {
                    this.boardingCount = 0;
                }
            } catch (_) {
                this.boardingCount = 0;
            }
        },
        async fetchMeetings() {
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + 'api/meetings.php?action=list&module=BOARDING&limit=12');
                this.meetings = data && data.success && Array.isArray(data.data) ? data.data : [];
                const now = Date.now();
                this.recentMeetingCount = this.meetings.filter(m => {
                    const d = m.meeting_date ? new Date(m.meeting_date).getTime() : 0;
                    return d && (now - d) < (7 * 24 * 60 * 60 * 1000);
                }).length;
            } catch (_) {
                this.meetings = [];
                this.recentMeetingCount = 0;
            }
        },
        async fetchBoardingStudents() {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/boarding.php?action=get_students&gender=all');
                this.boardingStudents = Array.isArray(j.data) ? j.data : (Array.isArray(j) ? j : []);
            } catch (_) {
                this.boardingStudents = [];
            }
        },
        async fetchApprovals() {
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + 'api/approval.php?action=get_list&status=ALL');
                const rows = data && data.success ? (Array.isArray(data.data) ? data.data : []) : [];
                this.approvals = rows.filter(a => String(a.module || '').toUpperCase() === 'BOARDING').slice(0, 12);
            } catch (_) {
                this.approvals = [];
            }
        },
        async fetchDocuments() {
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + 'api/meetings.php?action=list_documents&module=BOARDING&limit=30');
                this.documents = data && data.success ? (data.data || []) : [];
            } catch (_) {
                this.documents = [];
            }
        },
        async fetchZeroTodayReports() {
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + 'api/zero_report.php?action=list_today');
                this.zeroTodayReports = data && data.success ? (data.data || []) : [];
                if (!(data && data.success) || this.zeroTodayReports.length === 0) {
                    const now = Date.now();
                    this.zeroTodayReports = [
                        { name: 'Musyrif Blok A', status: 'SAFE', incident_count: 0, created_at: new Date(now - 5 * 60 * 1000).toISOString() },
                        { name: 'Musyrif Blok B', status: 'INCIDENT', incident_count: 1, created_at: new Date(now - 10 * 60 * 1000).toISOString() },
                        { name: 'Musyrif Blok C', status: 'PENDING', incident_count: 0, created_at: new Date(now).toISOString() }
                    ];
                }
                const safe = this.zeroTodayReports.filter(r => String(r.status || '').toUpperCase() === 'SAFE').length;
                const incident = this.zeroTodayReports.filter(r => String(r.status || '').toUpperCase() === 'INCIDENT').length;
                const pendingRows = this.zeroTodayReports.filter(r => String(r.status || '').toUpperCase() === 'PENDING').length;
                const totalMusyrif = this.musyrifCount || 0;
                const pending = pendingRows > 0 ? pendingRows : Math.max(0, totalMusyrif - (safe + incident));
                this.zeroCounts = { safe, incident, pending };
                if (this.zeroTodayReports.length > 0) {
                    const sorted = [...this.zeroTodayReports].sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
                    const top = sorted[0];
                    this.topResponder = { name: top.name || null, time: new Date(top.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) };
                } else {
                    this.topResponder = { name: null, time: null };
                }
                this.updateZeroUrgency();
            } catch (_) {
                this.zeroTodayReports = [];
                this.zeroCounts = { safe: 0, incident: 0, pending: 0 };
                this.topResponder = { name: null, time: null };
            }
        },
        async fetchZeroSummary() {
            try {
                this.normalizeBaseUrl();
                const data = await this.safeFetch(this.baseUrl + 'api/zero_report.php?action=summary');
                if (data.success && data.data) {
                    this.weeklyMonthlyZero = {
                        weekly: data.data.weekly || { safe: 0, incident: 0 },
                        monthly: data.data.monthly || { safe: 0, incident: 0 }
                    };
                } else {
                    this.weeklyMonthlyZero = { weekly: { safe: 0, incident: 0 }, monthly: { safe: 0, incident: 0 } };
                }
            } catch (_) {
                this.weeklyMonthlyZero = { weekly: { safe: 0, incident: 0 }, monthly: { safe: 0, incident: 0 } };
            }
        },
        updateZeroUrgency() {
            const now = new Date();
            const start = new Date();
            const end = new Date();
            start.setHours(21, 0, 0, 0);
            end.setHours(22, 0, 0, 0);
            const pending = this.zeroCounts.pending || 0;
            if (now < start) {
                this.zeroUrgencyText = 'Menunggu jam laporan';
                this.zeroUrgencyClass = '';
                return;
            }
            if (now >= start && now <= end) {
                if (pending > 0) {
                    this.zeroUrgencyText = 'Jam laporan aktif, masih ada yang belum lapor';
                    this.zeroUrgencyClass = 'animate-pulse bg-red-50/60';
                } else {
                    this.zeroUrgencyText = 'Semua blok sudah lapor';
                    this.zeroUrgencyClass = 'bg-emerald-50/40';
                }
                return;
            }
            if (now > end) {
                if (pending > 0) {
                    this.zeroUrgencyText = 'Lewat jam 21:00, masih ada yang belum lapor';
                    this.zeroUrgencyClass = 'animate-pulse bg-red-100/70';
                } else {
                    this.zeroUrgencyText = 'Lewat jam laporan, semua blok sudah aman';
                    this.zeroUrgencyClass = 'bg-emerald-50/40';
                }
            }
        },
        loadTasks() {
            try {
                const raw = localStorage.getItem('boarding_tasks');
                const arr = JSON.parse(raw || '[]');
                this.tasks = Array.isArray(arr) ? arr : [];
            } catch(_) { this.tasks = []; }
        },
        saveTasks() {
            try {
                localStorage.setItem('boarding_tasks', JSON.stringify(this.tasks));
            } catch(_) {}
        },
        loadChatMessages() {
            try {
                const raw = localStorage.getItem('boarding_chat_messages');
                const arr = JSON.parse(raw || '[]');
                this.chatMessages = Array.isArray(arr) ? arr : [];
            } catch(_) { this.chatMessages = []; }
        },
        saveChatMessages() {
            try {
                localStorage.setItem('boarding_chat_messages', JSON.stringify(this.chatMessages));
            } catch(_) {}
        },
        sendChat() {
            const t = (this.chatInput || '').trim();
            if (!t) return;
            this.chatMessages.push({ id: Date.now(), text: t, ts: Date.now() });
            this.chatInput = '';
            this.saveChatMessages();
        },
        nudgeMusyrif(name) {
            const msg = `Pengingat: Mohon segera lakukan Zero Report - ${name}`;
            this.chatMessages.push({ id: Date.now(), text: msg, ts: Date.now() });
            this.saveChatMessages();
            alert('Notifikasi terkirim (dummy) ke ' + name);
        },
        deleteChatMessage(idx) {
            if (idx < 0 || idx >= this.chatMessages.length) return;
            this.chatMessages.splice(idx, 1);
            this.saveChatMessages();
        },
        formatChatTime(ts) {
            const d = new Date(ts);
            return d.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' });
        },
        async fetchPermissions() {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/boarding_permissions.php?action=get_permissions');
                const rows = j.success ? (j.data || []) : [];
                const now = new Date();
                this.permissionsActive = rows.filter(x => {
                    const s = x.start_date ? new Date(x.start_date) : null;
                    const e = x.end_date ? new Date(x.end_date) : null;
                    const activeRange = s && e ? (now >= s && now <= e) : (e ? now <= e : true);
                    const statusOk = ['APPROVED','PENDING'].includes((x.status || '').toUpperCase());
                    return statusOk && activeRange;
                });
            } catch (_) {
                this.permissionsActive = [];
            }
        },
        async fetchDiscipline(type) {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/boarding_discipline.php?action=get_records&type=' + encodeURIComponent(type));
                const rows = j.success ? (j.data || []) : [];
                if (type === 'ACHIEVEMENT') this.achievementsRecent = rows;
                else this.violationsRecent = rows;
            } catch (_) {
                if (type === 'ACHIEVEMENT') this.achievementsRecent = [];
                else this.violationsRecent = [];
            }
        },
        async fetchRooms() {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/boarding_rooms.php?action=get_rooms');
                const rows = j.success ? (j.data || []) : [];
                this.roomsTop = rows.sort((a,b) => (parseInt(b.occupants_count||0,10)) - (parseInt(a.occupants_count||0,10)));
            } catch (_) {
                this.roomsTop = [];
            }
        },
        async fetchHalaqoh() {
            try {
                const j = await this.safeFetch(this.baseUrl + 'api/boarding_halaqoh.php?action=get_halaqoh');
                const list = j.success ? (j.data || []) : [];
                const map = {};
                for (const s of this.boardingStudents) {
                    const hid = s.halaqoh_id || null;
                    if (!hid) continue;
                    const key = String(hid);
                    map[key] = (map[key] || 0) + 1;
                }
                const merged = list.map(h => {
                    const key = String(h.id);
                    const count = map[key] || 0;
                    return { id: h.id, name: h.name, ustadz_name: h.ustadz_name, count };
                });
                this.halaqohTop = merged.sort((a,b) => b.count - a.count);
            } catch (_) {
                this.halaqohTop = [];
            }
        },
        computeMusyrif() {
            const map = {};
            for (const s of this.boardingStudents) {
                const mid = s.musyrif_id || null;
                const name = s.musyrif_name || null;
                if (!mid && !name) continue;
                const key = String(mid || name);
                if (!map[key]) map[key] = { id: mid || null, name: name || 'Musyrif', count: 0 };
                map[key].count += 1;
            }
            const arr = Object.values(map).sort((a,b) => b.count - a.count);
            this.musyrifList = arr;
            this.musyrifCount = arr.length;
        },
        async fetchAsramaStats() {
            await this.fetchBoardingStudents();
            await this.fetchPermissions();
            await this.fetchDiscipline('ACHIEVEMENT');
            await this.fetchDiscipline('VIOLATION');
            await this.fetchRooms();
            await this.fetchHalaqoh();
            this.computeMusyrif();
        },
        clearAsramaStats() {
            this.permissionsActive = [];
            this.achievementsRecent = [];
            this.violationsRecent = [];
            this.roomsTop = [];
            this.halaqohTop = [];
            this.musyrifCount = 0;
            this.musyrifList = [];
            this.boardingStudents = [];
        },
        async checkLockStatus() {
            if (this.currentPosition !== 'wali') return;
            const classId = this.selectedClassId || this.homeroomClassId;
            if (!classId) return;
            
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(`${this.baseUrl}api/lock_check.php?action=check_status&class_id=${classId}`);
                if (j.success) {
                    this.lockStatus = j;
                }
            } catch (e) { console.error(e); }
        },
        async fetchUnitLocks() {
            if (this.currentPosition !== 'kepala') return;
            this.normalizeBaseUrl();
            const j = await this.safeFetch(`${this.baseUrl}api/lock_check.php?action=get_unit_locks&unit=${this.currentUnit}`);
            if (j.success) {
                this.unitLocks = j.data;
            }
        },
        handleUnlockClick(c) {
            console.group('handleUnlockClick Triggered');
            console.log('Class:', c);
            console.trace('Stack trace for handleUnlockClick');
            console.groupEnd();
            
            if (!c || !c.id) {
                console.warn('handleUnlockClick called without valid class object');
                return;
            }

            this.selectedLockClass = c;
            this.unlockReason = '';
            this.unlockDuration = 24;
            this.unlockType = 'ALL';
            this.showClassUnlockModal = true;
        },
        async submitUnlock() {
            if (!this.selectedLockClass) return;
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(`${this.baseUrl}api/lock_check.php?action=unlock`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        class_id: this.selectedLockClass.id,
                        lock_type: this.unlockType,
                        hours: this.unlockDuration,
                        reason: this.unlockReason
                    })
                });
                if (j.success) {
                    alert('Berhasil membuka kunci!');
                    this.showClassUnlockModal = false;
                    this.fetchUnitLocks();
                } else {
                    alert('Gagal: ' + j.message);
                }
            } catch (e) { alert('Error: ' + e.message); }
        },
        setUnit(u) {
            // Check if isUnitLocked is defined
            if (typeof this.isUnitLocked === 'function' && this.isUnitLocked(u)) return;
            this.currentUnit = u;
            // Trigger fetches
            this.fetchUnitStats();
            this.fetchBkCases();
            this.fetchCounselingSummary();
            this.fetchAcademicAgenda();
        },
        isUnitLocked(u) {
            // Guru view: Unlocked if accessible (to allow multi-unit indication)
            // But actually clicking them doesn't need to switch units if we are in "All Units" mode visually.
            // However, the user wants them to LOOK active.
            
            if (window.IS_WORKSPACE_FIXED) {
                return window.WORKSPACE_FIXED_UNIT !== u;
            }
            
            // Allow if unit is in accessibleUnits list
            if (this.accessibleUnits && this.accessibleUnits.length > 0) {
                // If guru, we consider them 'unlocked' so they can be highlighted
                // But wait, unitClass uses !locked to highlight.
                // So if it returns false (unlocked), it highlights.
                
                return !this.accessibleUnits.includes(String(u).toLowerCase());
            }

            // Fallback: For regular teachers (not Admin/Principal), lock unit switching
            const r = String(this.userRole || '').toUpperCase();
            if (r !== 'SUPERADMIN' && r !== 'ADMIN') {
                // Special case: Guru with multi-unit access
                // If we don't have accessibleUnits populated yet, we might be strict.
                return this.currentUnit !== u;
            }
            return false;
        },
        async fetchUnitStats() {
            if (this.currentPosition !== 'kepala') return;
            try {
                this.normalizeBaseUrl();
                
                const unit = this.currentUnit;
                const pMonth = this.workspaceMonth;
                const pYear = this.workspaceYear;
                const pDate = this.workspaceDailyDate;

                // Parallel Fetch for Performance
                const [locksRes, mainRes, monthlyRes, dailyRes] = await Promise.all([
                    this.safeFetch(`${this.baseUrl}api/lock_check.php?action=get_unit_locks&unit=${unit}`),
                    this.safeFetch(`${this.baseUrl}api/workspace_academic.php?unit=${encodeURIComponent(unit)}`),
                    this.safeFetch(`${this.baseUrl}api/attendance.php?action=get_attendance_summary&unit=${encodeURIComponent(unit)}&month=${pMonth}&year=${pYear}`),
                    this.safeFetch(`${this.baseUrl}api/attendance.php?action=get_unit_daily_status&unit=${encodeURIComponent(unit)}&date=${pDate}`)
                ]);
                
                // 1. Process Locks
                if (locksRes.success) {
                    this.unitLocks = locksRes.data;
                }

                // 2. Process Main Stats
                if (mainRes.success && mainRes.data) {
                    const d = mainRes.data;
                    this.unitStats.studentCount = d.students ? d.students.total : 0;
                    this.unitStats.classCount = d.classes ? d.classes.length : 0;
                    this.unitStats.subjectCount = d.subjects ? d.subjects.total : 0;
                    this.unitStats.bkHigh = d.bk ? d.bk.high : 0;
                    this.unitStats.bkMedium = d.bk ? d.bk.medium : 0;
                    this.unitStats.inventoryTotal = d.inventory ? d.inventory.total_items : 0;
                    this.unitStats.inventoryBad = d.inventory ? d.inventory.bad : 0;
                    
                    // Extra stats
                    this.unitStats.studentsMale = d.students ? d.students.male : 0;
                    this.unitStats.studentsFemale = d.students ? d.students.female : 0;
                    this.unitStats.studentsBoarding = d.students ? d.students.boarding : 0;
                    
                    // 3. Process Attendance
                    const monthlyStats = (monthlyRes.success && Array.isArray(monthlyRes.data)) ? monthlyRes.data : [];
                    const dailyStats = (dailyRes.success && Array.isArray(dailyRes.data)) ? dailyRes.data : [];
                    
                    // Map classes with status
                    this.unitStats.classes = (d.classes || []).map(c => {
                        const mData = monthlyStats.find(m => String(m.class_id) === String(c.id));
                        const dData = dailyStats.find(d => String(d.id) === String(c.id));
                        
                        // Check lock status from unitLocks
                        const lockedClass = this.unitLocks.find(l => String(l.id) === String(c.id));
                        
                        return {
                            id: c.id,
                            name: c.name,
                            male: parseInt(c.male_count)||0,
                            female: parseInt(c.female_count)||0,
                            dailyStatus: dData ? dData.status : 'Pending',
                            monthlyStatus: mData ? 'Validated' : 'Pending',
                            inventoryStatus: 'Pending', 
                            isLocked: !!lockedClass,
                            lockDetails: lockedClass ? lockedClass.locks : []
                        };
                    });
                }
            } catch (e) { console.error(e); }
        },
        async fetchBkCases() {
            if (this.currentPosition !== 'kepala') return;
            try {
                this.normalizeBaseUrl();
                const j = await this.safeFetch(`${this.baseUrl}api/workspace_academic.php?action=get_bk_cases&unit=${encodeURIComponent(this.currentUnit)}`);
                this.bkCases = j.success ? (j.data || []) : [];
            } catch (e) { this.bkCases = []; }
        },

        async uploadPDF(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.type !== 'application/pdf') {
                alert('Hanya file PDF yang diperbolehkan');
                return;
            }

            if (!this.classSlug) {
                alert('Kelas tidak ditemukan/dipilih');
                return;
            }

            this.uploadingPDF = true;
            const formData = new FormData();
            // PHP expects 'image' key for file upload (reused logic)
            formData.append('image', file);

            try {
                // Pass slug and action in URL as PHP reads from $_GET
                const response = await fetch(`${this.baseUrl}api/display_settings.php?action=upload_image&slug=${this.classSlug}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    this.displaySettings.pdfActive = true;
                    this.displaySettings.pdfUrl = result.file_path;
                    this.displaySettings.pdfId = result.id;
                    this.displaySettings.pdfPage = 1;
                    
                    this.currentPDF = {
                        id: result.id,
                        filename: result.file_path.split('/').pop(),
                        page: 1
                    };
                    
                    await this.fetchDisplaySettings();
                    alert('PDF berhasil diupload!');
                } else {
                    alert(result.message || 'Gagal mengupload PDF');
                }
            } catch (error) {
                console.error('Error uploading PDF:', error);
                alert('Terjadi kesalahan saat mengupload PDF');
            } finally {
                this.uploadingPDF = false;
                event.target.value = ''; // Reset input
            }
        },

        async changePDFPage(delta) {
            if (!this.currentPDF) return;
            
            let newPage = parseInt(this.currentPDF.page) + delta;
            
            // Constrain page number if document is loaded
            if (this.pdfDoc) {
                const maxPages = this.pdfDoc.numPages;
                if (newPage < 1) newPage = 1;
                if (newPage > maxPages) newPage = maxPages;
            } else {
                if (newPage < 1) newPage = 1;
            }
            
            // If no change, return
            if (newPage === parseInt(this.currentPDF.page)) return;

            this.currentPDF.page = newPage;
            this.displaySettings.pdfPage = newPage;
            
            await this.updatePDFPage(newPage);
        },

        async jumpToPage() {
            if (!this.currentPDF) return;
            
            let page = parseInt(this.currentPDF.page);
            if (isNaN(page) || page < 1) page = 1;
            
            if (this.pdfDoc) {
                const maxPages = this.pdfDoc.numPages;
                if (page > maxPages) page = maxPages;
            }
            
            this.currentPDF.page = page;
            this.displaySettings.pdfPage = page;
            await this.updatePDFPage(page);
        },

        async updatePDFPage(page) {
            if (!this.displaySettings.pdfId) {
                // alert('Error: PDF ID missing');
                return;
            }

            try {
                const res = await fetch(`${this.baseUrl}api/display_settings.php?action=update_pdf_control&slug=${this.classSlug}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.displaySettings.pdfId,
                        page: page
                    })
                });
                const j = await res.json();
                if (!j.success) {
                    console.error('Gagal update page: ' + j.message);
                }
            } catch (error) {
                console.error('Error updating PDF page:', error);
            }
        },

        async toggleLaser() {
             if (!this.displaySettings.pdfId) return;
             
             this.displaySettings.laserActive = !this.displaySettings.laserActive;
             
             try {
                 await fetch(`${this.baseUrl}api/display_settings.php?action=update_pdf_control&slug=${this.classSlug}`, {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify({
                         id: this.displaySettings.pdfId,
                         laserActive: this.displaySettings.laserActive
                     })
                 });
             } catch (e) {
                 console.error('Error toggling laser', e);
             }
        },
        
        async updateLaserPosition(e) {
             if (!this.displaySettings.pdfId || !this.displaySettings.laserActive) return;
             
             // Calculate relative position (0-100%)
             // e is the mouse/touch event
             const rect = e.target.getBoundingClientRect();
             const clientX = e.touches ? e.touches[0].clientX : e.clientX;
             const clientY = e.touches ? e.touches[0].clientY : e.clientY;
             
             const x = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
             const y = Math.max(0, Math.min(100, ((clientY - rect.top) / rect.height) * 100));
             
             this.displaySettings.laserX = x;
             this.displaySettings.laserY = y;
             
             const now = Date.now();
             if (this.lastLaserUpdate && (now - this.lastLaserUpdate < 100)) return;
             this.lastLaserUpdate = now;
             
             try {
                 await fetch(`${this.baseUrl}api/display_settings.php?action=update_pdf_control&slug=${this.classSlug}`, {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify({
                         id: this.displaySettings.pdfId,
                         laserX: x,
                         laserY: y
                     })
                 });
             } catch (err) {
                 console.error('Error updating laser', err);
             }
        },

        async generateAIContent() {
            if (!this.aiPrompt.trim()) return;
            this.aiLoading = true;
            this.aiResult = null;
            
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/ai_generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: this.aiPrompt })
                });
                const j = await res.json();
                
                if (j.success) {
                    this.aiResult = j.content;
                    // Auto render mermaid if present
                    if (this.aiResult.includes('mermaid')) {
                        this.$nextTick(() => {
                            if (window.mermaid) {
                                window.mermaid.init(undefined, document.querySelectorAll('.mermaid'));
                            }
                        });
                    }
                } else {
                    alert('Gagal generate AI: ' + j.message);
                }
            } catch (e) {
                console.error('AI Error:', e);
                alert('Terjadi kesalahan saat menghubungi AI.');
            } finally {
                this.aiLoading = false;
            }
        },

        async showAIOnTV() {
            // Force activate on button click
            if (this.aiResult) {
                 this.displaySettings.aiActive = true;
            }
            
            if (!this.aiResult && !this.displaySettings.aiActive) {
                // If no result and trying to turn off, just proceed
            } else if (!this.aiResult && this.displaySettings.aiActive) {
                 // If active but no result, do nothing (maybe refreshing?)
                 // Actually, if we just toggled switch ON but have no result, we can't show anything
                 return;
            }

            try {
                this.normalizeBaseUrl();
                let url = `${this.baseUrl}api/display_settings.php?slug=${this.classSlug}&action=set_ai_override`;
                let body = { content: this.aiResult };
                
                if (!this.displaySettings.aiActive) {
                    // Turn OFF
                    url = `${this.baseUrl}api/display_settings.php?slug=${this.classSlug}&action=update_ai_status`;
                    body = { isActive: false };
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                
                const j = await res.json();
                if (j.success) {
                    if (this.displaySettings.aiActive) {
                        alert('Konten AI ditampilkan di TV!');
                    }
                    this.fetchDisplaySettings();
                } else {
                    alert('Gagal: ' + j.message);
                    this.displaySettings.aiActive = !this.displaySettings.aiActive; // Revert switch
                }
            } catch (e) {
                console.error('Error showing AI on TV', e);
                alert('Gagal update display.');
            }
        },

        async stopPDF() {
            if (!this.currentPDF || !this.currentPDF.id) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_image'); // Deactivates override
                formData.append('id', this.currentPDF.id);

                const response = await fetch(`${this.baseUrl}api/display_settings.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    this.currentPDF = null;
                    this.displaySettings.pdfActive = false;
                }
            } catch (error) {
                console.error('Error stopping PDF:', error);
            }
        },

        async handleWindowFocus() {
            if (this.currentPosition === 'wali' && this.homeroomClassId) {
                await this.fetchClassAttendanceSummary();
                this.generateAcademicMonths();
            }
        }
    },
    beforeUnmount() {
        window.removeEventListener('focus', this.handleWindowFocus);
    }
};
