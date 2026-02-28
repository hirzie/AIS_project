<?php
require_once '../../config/database.php';
require_once '../../includes/header_personnel.php';
?>

<div class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/personnel/dashboard.php" class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Data Pegawai</h1>
                <span class="text-xs text-slate-500 font-medium">Manajemen Sumber Daya Manusia</span>
            </div>
        </div>
        <div>
            <button @click="fetchEmployees" class="text-slate-400 hover:text-indigo-600 mr-4" title="Refresh Data">
                <i class="fas fa-sync-alt" :class="{'fa-spin': loading}"></i>
            </button>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden p-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 h-full flex flex-col">
            <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-1 w-full gap-4">
                    <div class="relative flex-1 max-w-md">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" v-model="searchQuery" placeholder="Cari Nama, NIP, atau Jabatan..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-sm">
                    </div>
                    <select v-model="filterUnit" class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none">
                        <option value="">Semua Unit</option>
                        <option v-for="unit in availableUnits" :key="unit.id" :value="unit.prefix || unit.unit_level">{{ (unit.prefix || unit.name).substring(0, 7) }}</option>
                    </select>
                    <select v-model="filterDivision" class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none">
                        <option value="">Semua Divisi</option>
                        <option value="ACADEMIC">Akademik</option>
                        <option value="EXECUTIVE">Executive</option>
                        <option value="FINANCE">Finance</option>
                        <option value="FOUNDATION">Foundation</option>
                        <option value="SECURITY">Security</option>
                        <option value="CLEANING">Kebersihan</option>
                        <option value="LIBRARY">Perpustakaan</option>
                        <option value="BOARDING">Boarding</option>
                    </select>
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <button @click="triggerImport" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-file-upload"></i> <span class="hidden md:inline">Import CSV</span>
                    </button>
                    <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept=".csv">
                    <button @click="exportData" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> <span class="hidden md:inline">Export</span>
                    </button>
                    <button @click="addEmployee" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> <span>Tambah Pegawai</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Pegawai</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">NIP / ID</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">TTL</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Jabatan</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Jenis</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Divisi</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Unit Akses</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-center">Status</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading" class="animate-pulse">
                            <td colspan="9" class="p-8 text-center text-slate-400">Sedang memuat data...</td>
                        </tr>
                        <tr v-else-if="filteredEmployees.length === 0">
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-4xl mb-2 opacity-20"></i>
                                    <span>Tidak ada data pegawai ditemukan</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="emp in filteredEmployees" :key="emp.employee_id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img :src="'https://ui-avatars.com/api/?name=' + emp.name + '&background=random'" class="w-10 h-10 rounded-full border border-slate-200">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm">{{ emp.name }}</div>
                                        <div class="text-xs text-slate-500">{{ emp.gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-mono text-slate-600">{{ emp.employee_number || '-' }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm text-slate-700">
                                    <div class="font-medium">{{ emp.birth_place || '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ emp.birth_date ? new Date(emp.birth_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}</div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span v-if="emp.position" class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                                    {{ emp.position }}
                                </span>
                                <span v-else class="text-slate-400 text-xs italic">- Belum ada jabatan -</span>
                            </td>
                            <td class="p-4">
                                <span :class="{
                                    'bg-indigo-50 text-indigo-600': emp.employee_type === 'ACADEMIC',
                                    'bg-slate-100 text-slate-600': emp.employee_type === 'NON_ACADEMIC'
                                }" class="px-2.5 py-1 rounded-full text-xs font-medium border border-transparent">
                                    {{ emp.employee_type === 'ACADEMIC' ? 'Akademik' : 'Non-Akademik' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span v-if="emp.division" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ emp.division === 'SECURITY' ? 'Security' : (emp.division === 'CLEANING' ? 'Kebersihan' : emp.division) }}
                                </span>
                                <span v-else class="text-slate-400 text-xs">-</span>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="unit in (emp.access_units ? emp.access_units.split(', ') : [])" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ unit }}
                                    </span>
                                    <span v-if="!emp.access_units" class="text-slate-400 text-xs">-</span>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <span :class="{
                                    'bg-emerald-50 text-emerald-600 border-emerald-100': emp.status === 'ACTIVE',
                                    'bg-red-50 text-red-600 border-red-100': emp.status === 'INACTIVE',
                                    'bg-slate-50 text-slate-600 border-slate-100': emp.status === 'ALUMNI' || emp.status === 'MUTASI'
                                }" class="px-2 py-1 rounded-full text-xs font-medium border uppercase">
                                    {{ emp.status || 'ACTIVE' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="viewEmployee(emp)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editEmployee(emp)" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteEmployee(emp)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-slate-200 bg-slate-50 text-xs text-slate-500 flex justify-between items-center">
                <span>Menampilkan {{ filteredEmployees.length }} dari {{ employees.length }} pegawai</span>
            </div>
        </div>
    </main>

    <?php include '../../includes/modals/add_employee_modal.php'; ?>

    <div v-if="confirmModal.show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden animate-fade transform transition-all scale-100">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">{{ confirmModal.title }}</h3>
                <p class="text-sm text-slate-500">{{ confirmModal.message }}</p>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-3">
                <button @click="confirmModal.show = false" class="flex-1 px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="executeConfirm" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors shadow-sm">
                    Ya, Lanjutkan
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
                employees: [],
                availableUnits: [],
                availablePositions: [],
                employmentStatuses: [],
                loading: false,
                searchQuery: '',
                filterUnit: '',
                filterStatus: '',
                filterDivision: '',
                showAddModal: false,
                isEditMode: false,
                editingId: null,
                confirmModal: { show: false, title: '', message: '', onConfirm: null },
                form: {
                    employee_number: '',
                    name: '',
                    nickname: '',
                    nuptk: '',
                    nrg: '',
                    join_date: '',
                    department: 'Akademik',
                    employment_status: 'TETAP',
                    status: 'ACTIVE',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    marital_status: 'Belum',
                    religion: 'Islam',
                    ethnicity: '',
                    identity_number: '',
                    address: '',
                    phone: '',
                    mobile_phone: '',
                    email: '',
                    facebook: '',
                    twitter: '',
                    website: '',
                    notes: '',
                    is_bk_team: false
                }
            }
        },
        computed: {
            filteredEmployees() {
                return this.employees.filter(emp => {
                    const search = this.searchQuery.toLowerCase();
                    const matchesSearch = 
                        (emp.name && emp.name.toLowerCase().includes(search)) ||
                        (emp.employee_number && emp.employee_number.toLowerCase().includes(search)) ||
                        (emp.position && emp.position.toLowerCase().includes(search));
                    
                    const matchesUnit = this.filterUnit === '' || 
                        (emp.access_units && emp.access_units.includes(this.filterUnit));

                    const matchesStatus = this.filterStatus === '' || 
                        (emp.status && emp.status.toLowerCase() === this.filterStatus.toLowerCase());

                    const matchesDivision = this.filterDivision === '' || 
                        (String(emp.division || '').toUpperCase() === this.filterDivision.toUpperCase());

                    return matchesSearch && matchesUnit && matchesStatus && matchesDivision;
                });
            }
        },
        mounted() {
            const urlParams = new URLSearchParams(window.location.search);
            const filterParam = urlParams.get('filter');
            if (filterParam) {
                this.filterStatus = filterParam;
            }
            const divParam = urlParams.get('division');
            if (divParam) {
                this.filterDivision = String(divParam).toUpperCase();
            }
            
            this.fetchEmployees();
            this.fetchGlobalUnits();
            this.fetchPositions();
            this.fetchEmploymentStatuses();
        },
        methods: {
            confirmAction(title, message, callback) {
                this.confirmModal = { show: true, title, message, onConfirm: callback };
            },
            executeConfirm() {
                if (this.confirmModal.onConfirm) this.confirmModal.onConfirm();
                this.confirmModal.show = false;
            },
            getBaseUrl() {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                return baseUrl;
            },
            async fetchEmploymentStatuses() {
                try {
                    const response = await fetch(this.getBaseUrl() + 'api/get_employment_statuses.php');
                    this.employmentStatuses = await response.json();
                } catch (error) {
                    console.error('Error fetching employment statuses:', error);
                }
            },
            async fetchGlobalUnits() {
                try {
                    const response = await fetch(this.getBaseUrl() + 'api/get_units.php');
                    this.availableUnits = await response.json();
                } catch (error) {
                    console.error('Error fetching global units:', error);
                }
            },
            async fetchPositions() {
                try {
                    const response = await fetch(this.getBaseUrl() + 'api/get_positions.php');
                    this.availablePositions = await response.json();
                } catch (error) {
                    console.error('Error fetching positions:', error);
                }
            },
            async fetchEmployees() {
                this.loading = true;
                try {
                    const response = await fetch(this.getBaseUrl() + 'api/get_employees.php');
                    if (!response.ok) throw new Error('Network error');
                    this.employees = await response.json();
                } catch (error) {
                    console.error("Error fetching employees:", error);
                    alert("Gagal memuat data pegawai.");
                } finally {
                    this.loading = false;
                }
            },
            addEmployee() {
                this.isEditMode = false;
                this.editingId = null;
                this.resetForm();
                this.showAddModal = true;
            },
            closeAddModal() {
                this.showAddModal = false;
                this.resetForm();
            },
            resetForm() {
                this.form = {
                    employee_number: '',
                    name: '',
                    nickname: '',
                    nuptk: '',
                    nrg: '',
                    join_date: '',
                    department: 'Akademik',
                    division: '',
                    position_id: '',
                    employment_status: 'TETAP',
                    status: 'ACTIVE',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    marital_status: 'Belum',
                    religion: 'Islam',
                    ethnicity: '',
                    identity_number: '',
                    address: '',
                    phone: '',
                    mobile_phone: '',
                    email: '',
                    facebook: '',
                    twitter: '',
                    website: '',
                    notes: '',
                    unit_access: []
                };
            },
            async submitEmployee() {
                try {
                    const payload = {
                        action: this.isEditMode ? 'update' : 'create',
                        ...this.form
                    };
                    
                    if (this.isEditMode) {
                        payload.employee_id = this.editingId;
                    }

                    const response = await fetch(this.getBaseUrl() + 'api/manage_employee.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(this.isEditMode ? 'Data pegawai berhasil diperbarui!' : 'Pegawai berhasil ditambahkan!');
                        this.closeAddModal();
                        this.fetchEmployees();
                    } else {
                        alert('Gagal menyimpan: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error submitting employee:', error);
                    alert('Terjadi kesalahan saat menyimpan data.');
                }
            },
            exportData() {
                window.location.href = this.getBaseUrl() + 'api/export_employees.php';
            },
            triggerImport() {
                this.$refs.fileInput.click();
            },
            async handleFileUpload(event) {
                const file = event.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                this.loading = true;
                try {
                    const response = await fetch(this.getBaseUrl() + 'api/import_employees.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        let msg = `Import berhasil!\nSukses: ${result.imported} data\nGagal: ${result.failed} data`;
                        if (result.errors && result.errors.length > 0) {
                            msg += '\n\nError details (first 5):\n' + result.errors.slice(0, 5).join('\n');
                        }
                        alert(msg);
                        this.fetchEmployees();
                    } else {
                        alert('Import gagal: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error("Import error:", error);
                    alert("Terjadi kesalahan saat mengupload file.");
                } finally {
                    this.loading = false;
                    event.target.value = '';
                }
            },
            async viewEmployee(emp) {
                await this.editEmployee(emp);
            },
            async editEmployee(emp) {
                this.loading = true;
                try {
                    const response = await fetch(this.getBaseUrl() + `api/get_employee_detail.php?id=${emp.employee_id}`);
                    if (!response.ok) throw new Error('Failed to fetch detail');
                    
                    const detail = await response.json();
                    
                    this.form = {
                        employee_number: detail.employee_number || '',
                        name: detail.name || '',
                        nickname: detail.nickname || '',
                        nuptk: detail.nuptk || '',
                        nrg: detail.nrg || '',
                        join_date: detail.join_date || '',
                        department: detail.department || 'Akademik',
                        division: detail.division || '',
                        position_id: detail.position_id || '',
                        employment_status: detail.employment_status || 'TETAP',
                        status: detail.status || 'ACTIVE',
                        gender: detail.gender || 'L',
                        birth_place: detail.birth_place || '',
                        birth_date: detail.birth_date || '',
                        marital_status: detail.marital_status || 'Belum',
                        religion: detail.religion || 'Islam',
                        ethnicity: detail.ethnicity || '',
                        identity_number: detail.identity_number || '',
                        address: detail.address || '',
                        phone: detail.phone || '',
                        mobile_phone: detail.mobile_phone || '',
                        email: detail.email || '',
                        facebook: detail.facebook || '',
                        twitter: detail.twitter || '',
                        website: detail.website || '',
                        notes: detail.notes || '',
                        is_bk_team: Array.isArray(detail.teams) ? detail.teams.includes('BK') : false,
                        unit_access: detail.unit_access_ids ? detail.unit_access_ids.split(',').map(Number) : []
                    };

                    this.isEditMode = true;
                    this.editingId = emp.employee_id;
                    this.showAddModal = true;

                } catch (error) {
                    console.error("Error fetching detail:", error);
                    alert("Gagal mengambil data detail pegawai.");
                } finally {
                    this.loading = false;
                }
            },
            async saveCustomAttributes() {
                if (!this.isEditMode || !this.editingId) return;
                try {
                    const payload = {
                        action: 'update_custom_attributes',
                        employee_id: this.editingId,
                        division: this.form.division || null,
                        mobile_phone: this.form.mobile_phone || null,
                        set_bk: !!this.form.is_bk_team
                    };
                    const response = await fetch(this.getBaseUrl() + 'api/manage_employee.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result && result.success) {
                        alert('Atribut kustom berhasil disimpan.');
                        this.fetchEmployees();
                    } else {
                        alert('Gagal menyimpan atribut: ' + ((result && result.message) || ''));
                    }
                } catch (error) {
                    console.error('Error saving custom attributes:', error);
                    alert('Terjadi kesalahan sistem saat menyimpan atribut.');
                }
            },
            async deleteEmployee(emp) {
                this.confirmAction('Hapus Pegawai?', "Apakah Anda yakin ingin menghapus pegawai " + emp.name + "?", async () => {
                    try {
                        const response = await fetch(this.getBaseUrl() + 'api/manage_employee.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                employee_id: emp.employee_id
                            }),
                        });

                        const result = await response.json();
                        if (result.success) {
                            alert('Pegawai berhasil dihapus.');
                            this.fetchEmployees();
                        } else {
                            alert('Gagal menghapus: ' + (result.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error deleting employee:', error);
                        alert('Terjadi kesalahan saat menghapus data. Cek console untuk detail.');
                    }
                });
            }
        }
    }).mount('#app')
</script>
</body>
</html>
