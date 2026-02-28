<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>
    
    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Pengaturan & Referensi</h2>
                    <p class="text-slate-500">Kelola field custom, jabatan unit, dan referensi sistem</p>
                </div>
                <div class="flex gap-2" v-if="activeTab === 'CUSTOM_FIELDS'">
                    <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-bold text-sm shadow-lg shadow-blue-200 transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Field Custom
                    </button>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="flex gap-4 border-b border-slate-200 mb-6">
                <button @click="activeTab = 'CUSTOM_FIELDS'" 
                    class="pb-3 px-2 font-bold text-sm transition-colors border-b-2"
                    :class="activeTab === 'CUSTOM_FIELDS' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    Custom Fields
                </button>
                <button @click="activeTab = 'UNIT_POSITIONS'" 
                    class="pb-3 px-2 font-bold text-sm transition-colors border-b-2"
                    :class="activeTab === 'UNIT_POSITIONS' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    Jabatan Unit (Kepala & Wakil)
                </button>
            </div>
            
            <!-- Custom Fields Tab -->
            <div v-if="activeTab === 'CUSTOM_FIELDS'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-700">Custom Fields (Data Tambahan Siswa)</h3>
                </div>
                
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3">Label</th>
                            <th class="px-6 py-3">Key (Database)</th>
                            <th class="px-6 py-3">Tipe Input</th>
                            <th class="px-6 py-3">Opsi (Dropdown)</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="field in fields" :key="field.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-slate-700">{{ field.field_label }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ field.field_key }}</td>
                            <td class="px-6 py-4">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold">{{ field.field_type }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <span v-if="field.field_options">{{ field.field_options.join(', ') }}</span>
                                <span v-else>-</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="field.is_active == 1" class="text-green-600 bg-green-100 px-2 py-1 rounded-full text-xs font-bold">Aktif</span>
                                <span v-else class="text-slate-400 bg-slate-100 px-2 py-1 rounded-full text-xs font-bold">Non-Aktif</span>
                            </td>
                            <td class="px-6 py-4 text-center flex justify-center gap-2">
                                <button @click="openModal(field)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-orange-100 hover:text-orange-600 flex items-center justify-center transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button @click="deleteField(field)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="fields.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <i class="fas fa-database text-3xl mb-3 opacity-30"></i>
                                <p>Belum ada field custom.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Unit Positions Tab -->
            <div v-if="activeTab === 'UNIT_POSITIONS'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="unit in unitPrincipals" :key="unit.id" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-4 mb-4 border-b border-slate-100 pb-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center text-xl font-bold">
                            {{ (unit.code || 'UN').substring(0,2) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-slate-800 text-lg">{{ unit.name }}</h3>
                                    <p class="text-xs text-slate-500">Unit ID: {{ unit.id }}</p>
                                </div>
                                <button v-if="!unit.isEditing" @click="editPrincipal(unit)" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold bg-indigo-50 px-3 py-1 rounded-full">
                                    <i class="fas fa-pencil-alt mr-1"></i> Edit Jabatan
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Principal -->
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kepala Sekolah (Principal)</label>
                            <div v-if="!unit.isEditing" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <span class="font-bold text-slate-700">{{ unit.principal_name || 'Belum ditentukan' }}</span>
                            </div>
                            <div v-else>
                                <select v-model="unit.temp_principal_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                    <option :value="null">-- Pilih Kepala Sekolah --</option>
                                    <option v-for="p in potentialPrincipals" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                                <p v-if="potentialPrincipals.length === 0" class="text-[10px] text-red-500 mt-1">
                                    <i class="fas fa-exclamation-circle"></i> Tidak ada data staff/guru ditemukan.
                                </p>
                            </div>
                        </div>

                        <!-- Vice Principal -->
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 relative">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Wakil Kepala (Vice Principal)</label>
                            <div v-if="!unit.isEditing" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <span class="font-bold text-slate-700">{{ unit.vice_principal_name || 'Belum ditentukan' }}</span>
                            </div>
                            <div v-else>
                                <select v-model="unit.temp_vice_principal_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                    <option :value="null">-- Pilih Wakil Kepala --</option>
                                    <option v-for="p in potentialPrincipals" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                                <p v-if="potentialPrincipals.length === 0" class="text-[10px] text-red-500 mt-1">
                                    <i class="fas fa-exclamation-circle"></i> Tidak ada data staff/guru ditemukan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div v-if="unit.isEditing" class="mt-4 flex gap-2 justify-end pt-4 border-t border-slate-100">
                        <button @click="cancelEditPrincipal(unit)" class="text-xs font-bold text-slate-500 px-4 py-2 hover:bg-slate-100 rounded-lg transition-colors">Batal</button>
                        <button @click="savePrincipal(unit)" class="text-xs font-bold bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                            <i class="fas fa-save mr-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="col-span-1 md:col-span-2 py-12 text-center text-slate-500">
                    <i class="fas fa-circle-notch fa-spin text-3xl mb-3 text-indigo-500"></i>
                    <p>Memuat data unit...</p>
                </div>

                <!-- Error State -->
                <div v-if="error" class="col-span-1 md:col-span-2 py-12 text-center text-red-500 bg-red-50 rounded-xl border border-red-100">
                    <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
                    <p class="font-bold">{{ error }}</p>
                    <button @click="fetchUnitPrincipals" class="mt-4 px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50">Coba Lagi</button>
                </div>

                <!-- Empty State -->
                <div v-if="!loading && !error && unitPrincipals.length === 0" class="col-span-1 md:col-span-2 py-12 text-center text-slate-400">
                    <i class="fas fa-school text-4xl mb-3 opacity-20"></i>
                    <p>Tidak ada data unit ditemukan.</p>
                </div>
            </div>
        </div>
        
        <!-- MODAL (Custom Field) -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-fade">
                <h3 class="text-lg font-bold mb-4">{{ form.id ? 'Edit' : 'Tambah' }} Field Custom</h3>
                <form @submit.prevent="saveField">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Label Field</label>
                            <input v-model="form.field_label" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Contoh: Status Asrama" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Key (Unik)</label>
                            <input v-model="form.field_key" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 font-mono text-sm" placeholder="asrama_status" :readonly="!!form.id" required>
                            <p class="text-[10px] text-slate-500 mt-1">Hanya huruf kecil dan underscore. Tidak bisa diubah setelah dibuat.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Tipe Input</label>
                            <select v-model="form.field_type" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                <option value="TEXT">Teks Singkat</option>
                                <option value="TEXTAREA">Teks Panjang</option>
                                <option value="DROPDOWN">Pilihan (Dropdown)</option>
                                <option value="BOOLEAN">Ya / Tidak</option>
                                <option value="DATE">Tanggal</option>
                            </select>
                        </div>
                        
                        <div v-if="form.field_type === 'DROPDOWN'">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Pilihan Opsi</label>
                            <div class="flex gap-2 mb-2">
                                <input v-model="newOption" @keyup.enter="addOption" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Ketik opsi lalu Enter">
                                <button type="button" @click="addOption" class="bg-slate-200 text-slate-700 px-3 rounded-lg hover:bg-slate-300"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="(opt, idx) in form.field_options" :key="idx" class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-bold flex items-center gap-1">
                                    {{ opt }}
                                    <button type="button" @click="removeOption(idx)" class="hover:text-red-500"><i class="fas fa-times"></i></button>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input v-model="form.is_active" type="checkbox" id="isActive" class="w-4 h-4 text-blue-600 rounded">
                            <label for="isActive" class="text-sm font-medium text-slate-700">Aktif</label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        
    </main>
</div>

<!-- End of #app -->

<script>
    const { createApp } = Vue;
    createApp({
        data() {
            return {
                // Header Support
                currentUnit: 'all',
                availableUnits: [],
                
                activeTab: 'CUSTOM_FIELDS',
                
                // Custom Fields
                fields: [],
                showModal: false,
                form: {
                    id: null,
                    entity_type: 'STUDENT',
                    field_key: '',
                    field_label: '',
                    field_type: 'TEXT',
                    field_options: [],
                    is_active: true
                },
                newOption: '',
                
                // Unit Principals
                unitPrincipals: [],
                potentialPrincipals: [],
                loading: false,
                error: null
            }
        },
        computed: {
            isAdminRole() {
                const role = String(window.USER_ROLE || '').toUpperCase();
                return ['SUPERADMIN','ADMIN'].includes(role);
            },
            allowedUnitCodes() {
                const arr = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                return arr.map(s => String(s || '').trim().toUpperCase());
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
            activeTab(val) {
                if (val === 'UNIT_POSITIONS') {
                    this.fetchUnitPrincipals();
                    this.fetchPotentialPrincipals();
                }
            }
        },
        methods: {
            // Header Support
            async fetchGlobalUnits() {
                 try {
                    const url = (window.BASE_URL || '/') + 'api/get_units.php';
                    const res = await fetch(url);
                    const data = await res.json();
                    let units = Array.isArray(data) ? data : (data.data || []);
                    if (!this.isAdminRole) {
                        const allowed = new Set(this.allowedUnitCodes);
                        units = units.filter(u => allowed.has(String(u.code || '').toUpperCase()) || allowed.has(String(u.prefix || '').toUpperCase()));
                        if (units.length > 0) {
                            this.currentUnit = units[0].code || units[0].unit_level || 'all';
                        }
                    }
                    this.availableUnits = units;
                } catch (e) {
                    console.error('Failed to load units', e);
                }
            },
            async fetchFields() {
                try {
                    const url = (window.BASE_URL || '/') + 'api/manage_references.php?action=get_fields&entity_type=STUDENT';
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.success) {
                        this.fields = data.data;
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            openModal(field = null) {
                if (field) {
                    this.form = {
                        ...field,
                        is_active: field.is_active == 1,
                        field_options: field.field_options || []
                    };
                } else {
                    this.form = {
                        id: null,
                        entity_type: 'STUDENT',
                        field_key: '',
                        field_label: '',
                        field_type: 'TEXT',
                        field_options: [],
                        is_active: true
                    };
                }
                this.showModal = true;
            },
            addOption() {
                if (this.newOption.trim()) {
                    if (!this.form.field_options) this.form.field_options = [];
                    this.form.field_options.push(this.newOption.trim());
                    this.newOption = '';
                }
            },
            removeOption(idx) {
                this.form.field_options.splice(idx, 1);
            },
            async saveField() {
                try {
                    const res = await fetch('../../api/manage_references.php?action=save_field', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showModal = false;
                        this.fetchFields();
                        // alert(data.message);
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    alert('Error saving field');
                }
            },
            async deleteField(field) {
                if (!confirm('Hapus field ini? Data yang tersimpan di siswa mungkin akan hilang tampilannya.')) return;
                try {
                    const res = await fetch('../../api/manage_references.php?action=delete_field', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: field.id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchFields();
                    }
                } catch (e) {}
            },
            // Unit Positions Management
            async fetchUnitPrincipals() {
                this.loading = true;
                this.error = null;
                try {
                    const url = (window.BASE_URL || '/') + 'api/manage_references.php?action=get_unit_principals';
                    const res = await fetch(url);
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    const data = await res.json();
                    
                    if (data.success) {
                        this.unitPrincipals = data.data.map(u => ({
                            ...u,
                            isEditing: false,
                            temp_principal_id: u.principal_id,
                            temp_vice_principal_id: u.vice_principal_id
                        }));
                    } else {
                        throw new Error(data.message || 'Gagal memuat data unit.');
                    }
                } catch (e) {
                    this.error = e.message;
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
            
            async fetchPotentialPrincipals() {
                try {
                    const url = (window.BASE_URL || '/') + 'api/manage_references.php?action=get_potential_principals';
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.success) {
                        this.potentialPrincipals = data.data;
                    }
                } catch (e) {
                    console.error('Failed to load potential principals', e);
                }
            },
            
            editPrincipal(unit) {
                // Load potential principals if not loaded
                if (this.potentialPrincipals.length === 0) {
                    this.fetchPotentialPrincipals();
                }
                unit.isEditing = true;
            },
            
            cancelEditPrincipal(unit) {
                unit.isEditing = false;
                unit.temp_principal_id = unit.principal_id;
                unit.temp_vice_principal_id = unit.vice_principal_id;
            },
            
            async savePrincipal(unit) {
                try {
                    const formData = new FormData();
                    formData.append('unit_id', unit.id);
                    if (unit.temp_principal_id) formData.append('principal_id', unit.temp_principal_id);
                    if (unit.temp_vice_principal_id) formData.append('vice_principal_id', unit.temp_vice_principal_id);
                    
                    const url = (window.BASE_URL || '/') + 'api/manage_references.php?action=save_unit_principals';
                    const res = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await res.json();
                    
                    if (data.success) {
                        unit.principal_id = unit.temp_principal_id;
                        unit.vice_principal_id = unit.temp_vice_principal_id;
                        // Refresh to get updated names
                        this.fetchUnitPrincipals();
                        alert('Perubahan berhasil disimpan!');
                    } else {
                        alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan saat menyimpan data.');
                }
            }
        },
        mounted() {
            this.fetchGlobalUnits();
            this.fetchFields();
        }
    }).mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
