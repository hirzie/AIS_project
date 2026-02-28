<?php
require_once '../../config/database.php';
require_once '../../includes/header_personnel.php';
?>

<div class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/personnel/dashboard.php" class="w-10 h-10 bg-cyan-50 text-cyan-600 rounded-lg flex items-center justify-center hover:bg-cyan-600 hover:text-white transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Manajemen Jabatan</h1>
                <span class="text-xs text-slate-500 font-medium">Penempatan Pegawai & SK Jabatan</span>
            </div>
        </div>
        <div>
            <button @click="fetchData" class="text-slate-400 hover:text-cyan-600 mr-4" title="Refresh Data">
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
                        <input type="text" v-model="searchQuery" placeholder="Cari Jabatan atau Pegawai..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 text-sm">
                    </div>
                    <select v-model="filterUnit" class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none">
                        <option value="">Semua Unit</option>
                        <option v-for="unit in availableUnits" :key="unit.id" :value="unit.id">{{ (unit.prefix || unit.name).substring(0, 7) }}</option>
                    </select>
                </div>
            </div>

            <div class="flex-1 overflow-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Nama Jabatan</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Unit</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Pejabat Saat Ini</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">No. SK</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading" class="animate-pulse">
                            <td colspan="5" class="p-8 text-center text-slate-400">Sedang memuat data...</td>
                        </tr>
                        <tr v-else-if="filteredPositions.length === 0">
                            <td colspan="5" class="p-8 text-center text-slate-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-4xl mb-2 opacity-20"></i>
                                    <span>Tidak ada data jabatan ditemukan</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="pos in filteredPositions" :key="pos.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-slate-800 text-sm">{{ pos.position_name }}</div>
                                <div class="text-xs text-slate-500" v-if="pos.parent_name">Atasan: {{ pos.parent_name }}</div>
                            </td>
                            <td class="p-4">
                                <span v-if="pos.unit_name" class="px-2 py-1 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200" :title="pos.unit_name">
                                    {{ pos.prefix || pos.unit_name }}
                                </span>
                                <span v-else class="text-slate-400 text-xs">Global / Yayasan</span>
                                
                                <span v-if="pos.sub_unit" class="ml-2 px-2 py-1 rounded text-xs font-bold bg-purple-100 text-purple-600 border border-purple-200">
                                    {{ pos.sub_unit }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div v-if="pos.official_name" class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center text-xs font-bold">
                                        {{ getInitials(pos.official_name) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-700">{{ pos.official_name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ pos.nip || 'NIP: -' }}</div>
                                    </div>
                                </div>
                                <span v-else class="text-slate-400 text-xs italic bg-slate-50 px-2 py-1 rounded border border-slate-100">
                                    - Kosong -
                                </span>
                            </td>
                            <td class="p-4">
                                <span v-if="pos.sk_number" class="font-mono text-xs text-slate-600 bg-slate-50 px-2 py-1 rounded border border-slate-200">
                                    {{ pos.sk_number }}
                                </span>
                                <span v-else class="text-slate-400 text-xs">-</span>
                            </td>
                            <td class="p-4 text-right">
                                <button @click="openAssignModal(pos)" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-600 hover:text-cyan-600 hover:border-cyan-300 rounded-lg text-xs font-medium transition-colors shadow-sm">
                                    <i class="fas fa-user-edit mr-1"></i> Atur Pejabat
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all scale-100">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">Atur Pejabat</h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="bg-cyan-50 border border-cyan-100 rounded-lg p-3 mb-4">
                    <div class="text-xs text-cyan-600 font-bold uppercase mb-1">Jabatan</div>
                    <div class="font-bold text-slate-800">{{ selectedPosition?.position_name }}</div>
                    <div class="text-xs text-slate-500">{{ selectedPosition?.unit_name || 'Global' }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Pegawai</label>
                    <select v-model="form.employee_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all">
                        <option :value="null">- Kosongkan Jabatan -</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                            {{ emp.name }} ({{ emp.employee_number || 'No ID' }})
                        </option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Pilih pegawai yang akan mengisi jabatan ini.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nomor SK</label>
                    <input type="text" v-model="form.sk_number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="Contoh: SK/2024/001">
                </div>
                
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 mt-4">
                    <button @click="closeModal" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium transition-colors">Batal</button>
                    <button @click="saveAssignment" :disabled="saving" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                        <i v-if="saving" class="fas fa-circle-notch fa-spin"></i>
                        {{ saving ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
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
                positions: [],
                employees: [],
                availableUnits: [],
                loading: false,
                saving: false,
                searchQuery: '',
                filterUnit: '',
                
                showModal: false,
                selectedPosition: null,
                form: {
                    employee_id: null,
                    sk_number: ''
                }
            }
        },
        computed: {
            filteredPositions() {
                return this.positions.filter(pos => {
                    const search = this.searchQuery.toLowerCase();
                    const matchesSearch = 
                        (pos.position_name && pos.position_name.toLowerCase().includes(search)) ||
                        (pos.official_name && pos.official_name.toLowerCase().includes(search));
                    
                    const matchesUnit = this.filterUnit === '' || 
                        (pos.unit_id == this.filterUnit);

                    return matchesSearch && matchesUnit;
                });
            }
        },
        mounted() {
            this.fetchData();
            this.fetchUnits();
            this.fetchEmployees();
        },
        methods: {
            getInitials(name) {
                if (!name) return '';
                return name.split(' ').map(x => x[0]).slice(0, 2).join('').toUpperCase();
            },
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch((window.BASE_URL || '/') + 'api/get_positions.php');
                    this.positions = await res.json();
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
            async fetchUnits() {
                try {
                    const res = await fetch((window.BASE_URL || '/') + 'api/get_units.php');
                    this.availableUnits = await res.json();
                } catch (e) {
                    console.error(e);
                }
            },
            async fetchEmployees() {
                try {
                    const res = await fetch((window.BASE_URL || '/') + 'api/get_employees.php');
                    this.employees = await res.json();
                } catch (e) {
                    console.error(e);
                }
            },
            closeModal() {
                this.showModal = false;
                this.selectedPosition = null;
                this.form = { employee_id: null, sk_number: '' };
            },
            openAssignModal(pos) {
                this.selectedPosition = pos;
                this.showModal = true;
            },
            async saveAssignment() {
                this.saving = true;
                try {
                    const payload = {
                        position_id: this.selectedPosition.id,
                        employee_id: this.form.employee_id,
                        sk_number: this.form.sk_number
                    };
                    const res = await fetch((window.BASE_URL || '/') + 'api/manage_position.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (result.success) {
                        alert('Penetapan pejabat berhasil disimpan.');
                        this.closeModal();
                        this.fetchData();
                    } else {
                        alert('Gagal menyimpan: ' + (result.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan saat menyimpan.');
                } finally {
                    this.saving = false;
                }
            }
        }
    }).mount('#app')
</script>
</body>
</html>
