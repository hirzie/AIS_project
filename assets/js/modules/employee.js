export const employeeMixin = {
    data() {
        return {
            employees: [],
            staffList: [],
            orgStructure: [],
            // Modal Add Position
            showAddPositionModal: false,
            newPositionName: '',
            targetParentPosition: null,
            // Modal Edit Position
            showEditPositionModal: false,
            editPositionData: { id: null, name: '', officials: [] }, // Array officials
            
            // Modal Employee
            showEmployeeModal: false,
            employeeForm: {
                id: null,
                name: '',
                identity_number: '',
                employee_number: '',
                gender: 'L',
                employee_type: 'ACADEMIC',
                status: 'CONTRACT',
                join_date: new Date().toISOString().split('T')[0],
                access_units: []
            },
        };
    },
    methods: {
        async fetchEmployees(unit) {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + `api/get_employees.php?unit=${unit}`);
                this.employees = await response.json();
            } catch (error) {
                console.error("Gagal mengambil data pegawai:", error);
            }
        },
        async fetchOrgStructure(unit) {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + `api/get_org_structure.php?unit=${unit}`);
                this.orgStructure = await response.json();
                this.fetchStaffList(); // Pre-load staff list
            } catch (error) {
                console.error("Gagal mengambil struktur:", error);
            }
        },
        async fetchStaffList() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                let url = baseUrl + 'api/get_unit_teachers.php?type=ACADEMIC';
                if (this.currentUnit && this.currentUnit !== 'all') {
                    url += `&unit=${this.currentUnit}`;
                }
                const response = await fetch(url);
                this.staffList = await response.json();
            } catch (error) { console.error("Gagal load staff:", error); }
        },
        openAddPositionModal(parentModel) {
            this.targetParentPosition = parentModel;
            this.newPositionName = '';
            this.showAddPositionModal = true;
        },
        openEditPositionModal(model) {
            // Populate officials
            let currentOfficials = [];
            
            if (model.officials && model.officials.length > 0) {
                // Jika multi officials
                currentOfficials = model.officials.map(o => ({
                    person_id: o.employee_id ? this.findPersonIdByEmpId(o.employee_id) : '', 
                    person_id: this.findPersonIdByName(o.official_name),
                    sk_number: o.sk_number
                }));
            } else if (model.official_name) {
                // Jika single (legacy)
                currentOfficials = [{
                    person_id: this.findPersonIdByName(model.official_name),
                    sk_number: model.sk_number || ''
                }];
            }

            // Jika kosong, sediakan 1 row kosong
            if (currentOfficials.length === 0) {
                currentOfficials.push({ person_id: '', sk_number: '' });
            }

            this.editPositionData = {
                id: model.id,
                name: model.position_name,
                officials: currentOfficials
            };
            this.showEditPositionModal = true;
        },
        findPersonIdByName(name) {
            const found = this.staffList.find(s => s.name === name);
            return found ? found.id : '';
        },
        findPersonIdByEmpId(eid) { return ''; }, // Helper dummy
        addOfficialRow() {
            this.editPositionData.officials.push({ person_id: '', sk_number: '' });
        },
        removeOfficialRow(index) {
            this.editPositionData.officials.splice(index, 1);
        },
        async updatePosition() {
            try {
                const payload = { 
                    action: 'update',
                    position_id: this.editPositionData.id,
                    name: this.editPositionData.name,
                    officials: this.editPositionData.officials.filter(o => o.person_id) // Filter yang kosong
                };

                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Data Jabatan Berhasil Diupdate!');
                    this.showEditPositionModal = false;
                    this.fetchOrgStructure(this.currentUnit);
                } else {
                    alert('Gagal: ' + result.error);
                }
            } catch (error) { console.error(error); }
        },
        async deletePosition() {
            if (!confirm('Apakah Anda yakin ingin menghapus jabatan ini?')) return;
            try {
                const payload = { position_id: this.editPositionData.id, action: 'delete' };
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Jabatan Berhasil Dihapus!');
                    this.showEditPositionModal = false;
                    this.fetchOrgStructure(this.currentUnit);
                } else {
                    alert('Gagal: ' + result.error);
                }
            } catch (error) { console.error(error); }
        },
        async saveNewPosition() {
            if (!this.newPositionName || !this.targetParentPosition) return;
            
            try {
                const payload = {
                    name: this.newPositionName,
                    parent_id: this.targetParentPosition.id,
                    unit_id: this.targetParentPosition.unit_id // Warisi Unit ID dari Parent
                };

                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/add_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Berhasil menambahkan jabatan baru!');
                    this.showAddPositionModal = false;
                    this.fetchOrgStructure(this.currentUnit); // Refresh Tree
                } else {
                    alert('Gagal menambah jabatan: ' + result.error);
                }
            } catch (error) {
                console.error("Error saving position:", error);
            }
        },
        openEmployeeModal(emp = null) {
            if (emp) {
                this.employeeForm = {
                    employee_id: emp.employee_id,
                    name: emp.name,
                    identity_number: '', 
                    employee_number: emp.employee_number,
                    gender: emp.gender,
                    employee_type: 'ACADEMIC', 
                    status: 'CONTRACT',
                    join_date: '',
                    access_units: emp.access_units ? emp.access_units.split(', ') : []
                };
            } else {
                this.employeeForm = {
                    id: null,
                    name: '',
                    identity_number: '',
                    employee_number: '',
                    gender: 'L',
                    employee_type: 'ACADEMIC',
                    status: 'CONTRACT',
                    join_date: new Date().toISOString().split('T')[0],
                    access_units: []
                };
            }
            this.showEmployeeModal = true;
        },
        async saveEmployee() {
            try {
                const payload = {
                    ...this.employeeForm,
                    action: this.employeeForm.employee_id ? 'update' : 'create'
                };
                
                const baseUrl = window.BASE_URL || '/';
                const response = await fetch(baseUrl + 'api/manage_employee.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    this.showEmployeeModal = false;
                    this.fetchEmployees(this.currentUnit);
                } else {
                    alert(result.error);
                }
            } catch(e) { console.error(e); }
        }
    }
};
