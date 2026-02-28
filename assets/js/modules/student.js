export const studentMixin = {
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
        // Sometimes touch events propagate and close modals immediately
        document.addEventListener('touchend', (e) => {
            // Check if touch target is outside modal content but inside modal wrapper
            if (this.showImportModal) {
                const modalContent = document.querySelector('.bg-white.rounded-xl.shadow-xl.w-full.max-w-md');
                if (modalContent && !modalContent.contains(e.target) && e.target.closest('.fixed.inset-0')) {
                    // Let the click handler on the wrapper handle closing
                } else {
                    // Prevent accidental closes
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
                        // Ensure keys exist in studentForm for reactivity
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
                        // Ensure keys exist in studentForm for reactivity
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
                    // Fetch full detail
                    const studentId = student.id;
                    this.studentForm.id = studentId;
                    
                    // Set basic info immediately for UI feedback
                    this.studentForm.name = student.name || '';
                    this.studentForm.nis = student.nis || student.identity_number || '';
                    this.studentForm.gender = student.gender || 'L';
                    this.studentForm.status = student.status || 'ACTIVE';
                    this.studentForm.custom_values = {};
                    
                    // Ensure all fields are initialized to avoid undefined issues in v-model
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
                            console.log("Student Detail Fetched:", data); // Debugging
                            
                            // Merge data into studentForm
                            for (const key in this.studentForm) {
                                if (data.hasOwnProperty(key) && data[key] !== null && data[key] !== 'null') {
                                    this.studentForm[key] = data[key];
                                }
                            }
                            // Special handling for class_id if not in detail
                            if (!data.class_id && student.class_id) {
                                this.studentForm.class_id = student.class_id;
                            }
                            
                            // Custom Values
                            const cv = data.custom_values;
                            this.studentForm.custom_values = (Array.isArray(cv) && cv.length === 0) ? {} : (cv || {});
                        })
                        .catch(e => {
                            console.error("Gagal ambil detail siswa", e);
                            alert("Gagal mengambil data detail siswa.");
                        });

                } else {
                    // Reset Form (Should be handled by openAddStudentModal, but just in case)
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
                console.log("Saving Student Payload:", this.studentForm); // DEBUG
                const payload = {
                    ...this.studentForm,
                    unit_id: (this.getUnitId ? this.getUnitId(this.currentUnit) : 1), // Fallback to 1 if getUnitId missing
                    action: this.studentForm.id ? 'update' : 'create'
                };
                console.log("Final Payload:", payload); // DEBUG
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
                    
                    // Refresh Logic: Check context
                    if (this.selectedClassId && typeof this.loadClassDetailDirectly === 'function') {
                        // Context: Class Detail
                        this.loadClassDetailDirectly(this.selectedClassId);
                    } else if (this.fetchStudents) {
                        // Context: Student List
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
            // Fallback logic
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
            
            // Check if we have a valid unit ID
            let unitId = (this.getUnitId ? this.getUnitId(this.currentUnit) : null);
            if ((!unitId || unitId === 'all') && this.selectedClass && this.selectedClass.unit_id) {
                unitId = this.selectedClass.unit_id;
            }

            if (!unitId && !this.importTargetClassId) {
                alert('Unit tidak valid. Pastikan Anda memilih unit spesifik.'); 
                return; 
            }

            try {
                // PARSE FILE CLIENT-SIDE (SheetJS)
                const worksheet = await this.readExcelFile(this.importFile);
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });
                
                if (!jsonData || jsonData.length === 0) {
                    alert("File kosong atau format tidak terbaca.");
                    return;
                }

                // --- FIX: Normalize Headers ---
                // Convert all keys to lowercase to match backend logic
                const normalizedData = jsonData.map(row => {
                    const newRow = {};
                    for (const key in row) {
                        newRow[key.trim()] = row[key]; // Just trim, let backend handle case-insensitivity
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
                        'Accept': 'application/json' // Explicitly accept JSON
                    },
                    body: JSON.stringify(payload)
                });

                // --- FIX: Handle non-JSON responses (e.g. PHP Warnings/Errors) ---
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
                    
                    // Refresh Data
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
                    // Refresh Logic: Check context
                    if (this.selectedClassId && typeof this.loadClassDetailDirectly === 'function') {
                        this.loadClassDetailDirectly(this.selectedClassId);
                    } else if (this.selectedClass && typeof this.openClassDetail === 'function') {
                        this.openClassDetail(this.selectedClass);
                    } else {
                        // Fallback refresh
                        window.location.reload();
                    }
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error("Error removing student:", e); }
        }
    }
};
