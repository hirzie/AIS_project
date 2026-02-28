<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>
    
    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto space-y-8">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">PSB (Admisi)</h2>
                    <p class="text-slate-500 text-sm">Penerimaan Siswa Baru per Unit</p>
                </div>
            </div>


            <!-- Tambah Peserta Didik Baru (Modal-trigger only on main view) -->

            <!-- Setting Kategori & Kuota -->
            <section id="settings-psb" class="bg-white rounded-2xl shadow-sm border border-slate-200" v-if="isSettingsView">
                <div class="flex items-center justify-between p-4 border-b border-slate-100">
                    <div class="text-[12px] font-bold text-slate-600">Setting Kategori & Kuota</div>
                    <div class="flex items-center gap-2" v-if="isAdminRole">
                        <button @click="resetAllConfirm" class="text-[11px] font-bold px-3 py-1 rounded bg-red-600 text-white"><i class="fas fa-trash mr-1"></i> Reset TA Aktif</button>
                        <select v-model="programUnitId" class="border border-slate-300 rounded px-3 py-1.5 text-[12px]">
                            <option v-for="u in filteredUnits" :key="u.id" :value="u.id">{{ u.name }} ({{ u.prefix || u.code }})</option>
                        </select>
                        <button @click="resetUnitConfirm" :disabled="!programUnitId" class="text-[11px] font-bold px-3 py-1 rounded bg-amber-600 text-white disabled:opacity-50"><i class="fas fa-trash mr-1"></i> Reset Unit</button>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-700">Kategori & Kuota per Unit</div>
                        <div class="flex items-center gap-2">
                            <select v-model="programUnitId" class="border border-slate-300 rounded px-3 py-1.5 text-[12px]">
                                <option v-for="u in filteredUnits" :key="u.id" :value="u.id">{{ u.name }} ({{ u.prefix || u.code }})</option>
                            </select>
                            <button @click="refreshPrograms" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700"><i class="fas fa-sync-alt mr-1"></i> Muat Ulang</button>
                            <button @click="saveProgramsAndQuotas" :disabled="!programUnitId || savingProgramsAndQuotas" class="text-[11px] font-bold px-3 py-1 rounded bg-blue-600 text-white disabled:opacity-50">
                                <i class="fas fa-save mr-1" :class="{'fa-spin': savingProgramsAndQuotas}"></i> Simpan Kategori & Kuota
                            </button>
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Awalan Penomoran</label>
                            <input type="text" v-model="numberingPrefix" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: PSD27">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Panjang Nomor Urut</label>
                            <input type="number" min="1" v-model.number="numberingSeqLen" class="w-24 border border-slate-300 rounded px-2 py-1 text-sm" placeholder="4">
                        </div>
                        <div class="text-right">
                            <button @click="saveNumberingPrefix" :disabled="!programUnitId || savingNumbering" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white disabled:opacity-50">
                                <i class="fas fa-save mr-1" :class="{'fa-spin': savingNumbering}"></i> Simpan Awalan
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Program</th>
                                    <th class="px-3 py-2">Kuota</th>
                                    <th class="px-3 py-2">Terpakai</th>
                                    <th class="px-3 py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="px-3 py-2">
                                        <input v-model="newProgram.name" type="text" placeholder="Nama program baru" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" min="0" v-model.number="newProgram.quota" class="w-24 border border-slate-300 rounded px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-3 py-2 text-slate-400">Baris baru</td>
                                    <td class="px-3 py-2"></td>
                                </tr>
                                <tr v-for="p in programs" :key="p.id">
                                    <template v-if="editingProgramId === p.id">
                                        <td class="px-3 py-2">
                                            <input v-model="editProgramInputs[p.id].name" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="0" v-model.number="programInputs[p.id]" :disabled="!programUnitId" class="w-24 border border-slate-300 rounded px-2 py-1 text-sm">
                                        </td>
                                        <td class="px-3 py-2">{{ p.used || 0 }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex gap-2">
                                                <button @click="saveEditProgram(p)" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white">Simpan</button>
                                                <button @click="cancelEditProgram()" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Batal</button>
                                            </div>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-3 py-2 text-slate-800 font-medium">{{ p.name }}</td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="0" v-model.number="programInputs[p.id]" :disabled="!programUnitId" class="w-24 border border-slate-300 rounded px-2 py-1 text-sm">
                                        </td>
                                        <td class="px-3 py-2">{{ p.used || 0 }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex gap-2">
                                                <button @click="startEditProgram(p)" class="text-[11px] font-bold px-3 py-1 rounded bg-blue-600 text-white">Edit</button>
                                                <button @click="deleteProgram(p)" :disabled="(p.used || 0) > 0" class="text-[11px] font-bold px-3 py-1 rounded bg-red-600 text-white disabled:opacity-50">Hapus</button>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="programs.length === 0">
                                    <td colspan="4" class="px-3 py-3 text-slate-400">Belum ada program.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Daftar Pendaftar -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-200" v-if="!isSettingsView">
                <div class="flex items-center justify-between p-4 border-b border-slate-100">
                    <div class="text-[12px] font-bold text-slate-600">Daftar Pendaftar</div>
                    <div class="flex items-center gap-2">
                        <select v-model="listUnitId" class="border border-slate-300 rounded px-3 py-1.5 text-[12px]">
                            <option v-for="u in filteredUnits" :key="u.id" :value="u.id">{{ u.name }} ({{ u.prefix || u.code }})</option>
                        </select>
                        <select v-model="listProgramId" class="border border-slate-300 rounded px-3 py-1.5 text-[12px]">
                            <option :value="null">Semua Kategori</option>
                            <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <button @click="refreshApplicants" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700"><i class="fas fa-sync-alt mr-1"></i> Muat Ulang</button>
                        <button v-if="!isSettingsView" @click="openAddModal" class="text-[11px] font-bold px-3 py-1 rounded bg-rose-600 text-white"><i class="fas fa-user-plus mr-1"></i> Tambah Pendaftar</button>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">No. Daftar</th>
                                    <th class="px-3 py-2">Nama</th>
                                    <th class="px-3 py-2">JK</th>
                                    <th class="px-3 py-2">Program</th>
                                    <th class="px-3 py-2">Wali</th>
                                    <th class="px-3 py-2">HP</th>
                                    <th class="px-3 py-2">Tanggal</th>
                                    <th class="px-3 py-2">Souvenir</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="listLoading">
                                    <td colspan="8" class="px-3 py-3 text-slate-400">Memuat data...</td>
                                </tr>
                                <tr v-for="a in applicants" :key="a.id">
                                    <td class="px-3 py-2 font-mono text-slate-700">{{ a.reg_number || '-' }}</td>
                                    <td class="px-3 py-2 text-slate-800 font-medium">{{ a.name }}</td>
                                    <td class="px-3 py-2">{{ a.gender }}</td>
                                    <td class="px-3 py-2">{{ a.program_name || '-' }}</td>
                                    <td class="px-3 py-2">{{ a.guardian_name || '-' }}</td>
                                    <td class="px-3 py-2">{{ a.phone || '-' }}</td>
                                    <td class="px-3 py-2 text-[12px] text-slate-500">{{ formatDateTime(a.created_at) }}</td>
                                    <td class="px-3 py-2">
                                        <span :class="a.souvenir_received ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded text-[11px] font-bold">{{ a.souvenir_received ? 'Sudah' : 'Belum' }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button @click="openDetail(a)" class="text-[11px] font-bold px-3 py-1 rounded bg-blue-600 text-white">Detail</button>
                                    </td>
                                </tr>
                                <tr v-if="!listLoading && applicants.length === 0">
                                    <td colspan="8" class="px-3 py-3 text-slate-400">Belum ada pendaftar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <div v-if="showAddModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-30">
                <div class="bg-white rounded-2xl shadow-lg w-[640px] max-w-[95vw]">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <div class="text-[12px] font-bold text-slate-700">Tambah Pendaftar</div>
                        <button @click="showAddModal = false" class="text-slate-500 hover:text-slate-700"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="p-4 text-sm space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-[11px] text-slate-500">Unit Sekolah</div>
                                <select v-model="applicantForm.unit_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                    <option v-for="u in filteredUnits" :key="u.id" :value="u.id">{{ u.name }} ({{ u.prefix || u.code }})</option>
                                </select>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Kategori Penerimaan</div>
                                <select v-model="applicantForm.program_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                    <option :value="null">Pilih Kategori</option>
                                    <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }} <span v-if="p.gender==='L'">(Ikhwan)</span><span v-else-if="p.gender==='P'">(Akhwat)</span></option>
                                </select>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Nama Lengkap</div>
                                <input v-model="applicantForm.name" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Jenis Kelamin</div>
                                <select v-model="applicantForm.gender" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Tanggal Lahir</div>
                                <input v-model="applicantForm.birth_date" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Nama Wali/Orang Tua</div>
                                <input v-model="applicantForm.guardian_name" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Nomor HP</div>
                                <input v-model="applicantForm.phone" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Email</div>
                                <input v-model="applicantForm.email" type="email" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-[11px] text-slate-500">Alamat</div>
                                <textarea v-model="applicantForm.address" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500 mb-2">Kelengkapan Dokumen</div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" v-model="completeness.KK"> Kartu Keluarga</label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" v-model="completeness.Akte"> Akte Kelahiran</label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" v-model="completeness.KTPWali"> KTP Wali</label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" v-model="completeness.Foto"> Foto</label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" v-model="completeness.Ijazah"> Ijazah</label>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button @click="showAddModal = false" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Batal</button>
                        <button @click="saveApplicant" :disabled="savingApplicant" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white disabled:opacity-50"><i class="fas fa-save mr-1" :class="{'fa-spin': savingApplicant}"></i> Simpan</button>
                    </div>
                </div>
            </div>
            <div v-if="showDetailModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-30">
                <div class="bg-white rounded-2xl shadow-lg w-[520px] max-w-[95vw]">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <div class="text-[12px] font-bold text-slate-700">Detail Pendaftar</div>
                        <button @click="showDetailModal = false" class="text-slate-500 hover:text-slate-700"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="p-4 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[11px] text-slate-500">No. Daftar</div>
                                <div class="font-mono font-bold text-slate-800">{{ detailApplicant ? detailApplicant.reg_number : '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Nama</div>
                                <div class="font-bold text-slate-800">{{ detailApplicant ? detailApplicant.name : '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">JK</div>
                                <div class="text-slate-800">{{ detailApplicant ? detailApplicant.gender : '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Wali</div>
                                <div class="text-slate-800">{{ detailApplicant ? (detailApplicant.guardian_name || '-') : '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">HP</div>
                                <div class="text-slate-800">{{ detailApplicant ? (detailApplicant.phone || '-') : '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500">Tanggal Daftar</div>
                                <div class="text-slate-800">{{ formatDateTime(detailApplicant && detailApplicant.created_at) }}</div>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <input id="souvenirChk" type="checkbox" v-model="detailSouvenirReceived">
                            <label for="souvenirChk" class="text-slate-800 font-medium">Sudah menerima souvenir</label>
                        </div>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button @click="showDetailModal = false" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700">Tutup</button>
                        <button @click="saveSouvenir" :disabled="savingSouvenir" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white disabled:opacity-50"><i class="fas fa-save mr-1" :class="{'fa-spin': savingSouvenir}"></i> Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
<script>
(function() {
    function getBaseUrl() {
        var baseUrl = window.BASE_URL || '/';
        if (baseUrl === '/' || !baseUrl) {
            var m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
            baseUrl = m ? ('/' + m[1] + '/') : '/';
        }
        return baseUrl;
    }
    function normalizeUnitCode(s) {
        return String(s || '').trim().toUpperCase();
    }
    const { createApp } = window.Vue || {};
    if (!createApp) {
        var s = document.createElement('script');
        s.src = getBaseUrl() + 'assets/js/vue.global.js';
        s.onload = initApp;
        document.head.appendChild(s);
    } else {
        initApp();
    }
    function initApp() {
        const app = (window.Vue || {}).createApp({
            data() {
                return {
                    isSettingsView: false,
                    availableUnits: [],
                    currentUnit: 'all',
                    applicantForm: { unit_id: null, name: '', gender: 'L', birth_date: '', guardian_name: '', phone: '', email: '', address: '' },
                    completeness: { KK: false, Akte: false, KTPWali: false, Foto: false, Ijazah: false },
                    savingApplicant: false,
                    saveMessage: '',
                    saveSuccess: false,
                    programs: [],
                    programUnitId: null,
                    newProgram: { name: '', quota: 0 },
                    programInputs: {},
                    savingProgramsAndQuotas: false,
                    numberingPrefix: '',
                    numberingSeqLen: 4,
                    savingNumbering: false,
                    editingProgramId: null,
                    editProgramInputs: {},
                    listUnitId: null,
                    listProgramId: null,
                    applicants: [],
                    listLoading: false,
                    showDetailModal: false,
                    showAddModal: false,
                    detailApplicant: null,
                    detailSouvenirReceived: false,
                    savingSouvenir: false
                };
            },
            computed: {
                currentDate() {
                    try {
                        return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    } catch (_) {
                        const d = new Date();
                        const pad = n => String(n).padStart(2, '0');
                        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
                    }
                },
                isAdminRole() {
                    const role = String(window.USER_ROLE || '').toUpperCase();
                    return ['SUPERADMIN','ADMIN'].includes(role);
                },
                allowedUnitCodes() {
                    const arr = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                    return arr.map(normalizeUnitCode);
                },
                filteredUnits() {
                    if (this.isAdminRole) return this.availableUnits;
                    const codes = new Set(this.allowedUnitCodes);
                    return (this.availableUnits || []).filter(u => codes.has(normalizeUnitCode(u.code)) || codes.has(normalizeUnitCode(u.prefix)));
                },
                
            },
            watch: {
                currentUnit() {
                    const target = (this.currentUnit && this.currentUnit !== 'all') ? this.availableUnits.find(u => normalizeUnitCode(u.code) === normalizeUnitCode(this.currentUnit) || normalizeUnitCode(u.prefix) === normalizeUnitCode(this.currentUnit)) : null;
                    if (target) this.applicantForm.unit_id = target.id;
                    if (target) this.listUnitId = target.id;
                    if (target && !this.programUnitId) this.programUnitId = target.id;
                },
                'applicantForm.unit_id'(val) {
                    if (!val) return;
                    if (!this.programUnitId) this.programUnitId = val;
                    this.fetchNumberingPrefix();
                    this.refreshPrograms();
                },
                programUnitId() {
                    this.fetchNumberingPrefix();
                    this.refreshPrograms();
                },
                listUnitId() {
                    if (this.listUnitId) {
                        this.programUnitId = this.listUnitId;
                        this.listProgramId = null;
                        this.fetchNumberingPrefix();
                        this.refreshPrograms();
                        this.refreshApplicants();
                    }
                },
                listProgramId() {
                    this.refreshApplicants();
                }
            },
            async mounted() {
                this.isSettingsView = (window.location.hash === '#settings-psb');
                window.addEventListener('hashchange', () => {
                    this.isSettingsView = (window.location.hash === '#settings-psb');
                });
                await this.fetchUnits();
                if (!this.isAdminRole) {
                    if (this.filteredUnits.length > 0) {
                        this.applicantForm.unit_id = this.filteredUnits[0].id;
                        this.listUnitId = this.filteredUnits[0].id;
                            this.programUnitId = this.filteredUnits[0].id;
                    }
                }
                    await this.refreshPrograms();
                await this.refreshApplicants();
            },
            methods: {
                getBaseUrl,
                formatDateTime(s) {
                    if (!s) return '-';
                    const d = new Date(s);
                    if (isNaN(d.getTime())) return s;
                    const pad = n => String(n).padStart(2, '0');
                    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                },
                async fetchUnits() {
                    try {
                        const r = await fetch(getBaseUrl() + 'api/get_units.php');
                        const data = await r.json();
                        let units = Array.isArray(data) ? data : [];
                        if (!this.isAdminRole) {
                            const codes = new Set(this.allowedUnitCodes);
                            units = units.filter(u => codes.has(normalizeUnitCode(u.code)) || codes.has(normalizeUnitCode(u.prefix)));
                        }
                        this.availableUnits = units;
                    } catch (_) { this.availableUnits = []; }
                },
                async refreshPrograms() {
                    try {
                        const uid = this.programUnitId || (this.applicantForm.unit_id || 0);
                        const url = getBaseUrl() + 'api/psb.php?action=list_programs' + (uid ? ('&unit_id=' + encodeURIComponent(uid)) : '');
                        const r = await fetch(url);
                        const j = await r.json();
                        if (j && j.success) {
                            this.programs = j.data || [];
                            const inputs = {};
                            for (const p of this.programs) inputs[p.id] = p.quota || 0;
                            this.programInputs = inputs;
                        }
                    } catch (_) { this.programs = []; this.programInputs = {}; }
                },
                async refreshApplicants() {
                    this.listLoading = true;
                    try {
                        const uid = this.listUnitId || (this.applicantForm.unit_id || null);
                        if (!uid) { this.applicants = []; return; }
                        let url = getBaseUrl() + 'api/psb.php?action=list_applicants&unit_id=' + encodeURIComponent(uid);
                        if (this.listProgramId) {
                            url += '&program_id=' + encodeURIComponent(this.listProgramId);
                        }
                        const r = await fetch(url);
                        const j = await r.json();
                        this.applicants = (j && j.success) ? (j.data || []) : [];
                    } catch (_) { this.applicants = []; }
                    finally { this.listLoading = false; }
                },
                async saveProgramsAndQuotas() {
                    if (!this.programUnitId) { alert('Pilih unit terlebih dahulu'); return; }
                    this.savingProgramsAndQuotas = true;
                    try {
                        const items = (this.programs || []).map(p => ({ program_id: p.id, quota: Number(this.programInputs[p.id] ?? p.quota) || 0 }));
                        if (String(this.newProgram.name || '').trim() !== '') {
                            items.unshift({
                                program_id: null,
                                name: String(this.newProgram.name).trim(),
                                quota: Number(this.newProgram.quota || 0) || 0
                            });
                        }
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=save_programs_and_quotas', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ unit_id: this.programUnitId, items })
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            this.newProgram = { name: '', quota: 0 };
                            await this.refreshPrograms();
                        } else {
                            alert(j.message || 'Gagal menyimpan kategori & kuota');
                        }
                    } catch (_) {
                        alert('Gagal menyimpan kategori & kuota');
                    } finally {
                        this.savingProgramsAndQuotas = false;
                    }
                },
                async fetchNumberingPrefix() {
                    try {
                        const uid = this.programUnitId || 0;
                        if (!uid) return;
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=get_numbering_prefix&unit_id=' + encodeURIComponent(uid));
                        const j = await r.json();
                        if (j && j.success) {
                            this.numberingPrefix = String((j.data || {}).prefix || '');
                            this.numberingSeqLen = Number((j.data || {}).seq_length || 4);
                        }
                    } catch (_) {}
                },
                async saveNumberingPrefix() {
                    if (!this.programUnitId) { alert('Pilih unit terlebih dahulu'); return; }
                    const prefix = String(this.numberingPrefix || '').trim();
                    const seqLen = Number(this.numberingSeqLen || 4);
                    if (!prefix) { alert('Awalan penomoran wajib diisi'); return; }
                    this.savingNumbering = true;
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=save_numbering_prefix', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ unit_id: this.programUnitId, prefix, seq_length: seqLen })
                        });
                        const j = await r.json();
                        if (!(j && j.success)) alert(j.message || 'Gagal menyimpan awalan penomoran');
                    } catch (_) {
                        alert('Gagal menyimpan awalan penomoran');
                    } finally {
                        this.savingNumbering = false;
                    }
                },
                startEditProgram(p) {
                    this.editingProgramId = p.id;
                    this.$set ? this.$set(this.editProgramInputs, p.id, { name: p.name }) : (this.editProgramInputs[p.id] = { name: p.name });
                },
                cancelEditProgram() {
                    this.editingProgramId = null;
                },
                async saveEditProgram(p) {
                    const payload = { program_id: p.id, name: String((this.editProgramInputs[p.id] || {}).name || '').trim() };
                    if (!payload.name) { alert('Nama program wajib diisi'); return; }
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=update_program', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            this.editingProgramId = null;
                            await this.refreshPrograms();
                        } else {
                            alert(j.message || 'Gagal memperbarui program');
                        }
                    } catch (_) {
                        alert('Gagal memperbarui program');
                    }
                },
                async deleteProgram(p) {
                    if (!confirm('Hapus program ini?')) return;
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=delete_program', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ program_id: p.id })
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            await this.refreshPrograms();
                        } else {
                            alert(j.message || 'Gagal menghapus program');
                        }
                    } catch (_) {
                        alert('Gagal menghapus program');
                    }
                },
                async resetAllConfirm() {
                    if (!confirm('Reset seluruh data PSB tahun aktif? Tindakan ini tidak bisa dibatalkan.')) return;
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=reset_psb', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ scope: 'all' })
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            this.programInputs = {};
                            this.programs = [];
                            await this.refreshPrograms();
                            await this.refreshApplicants();
                            alert('Reset selesai');
                        } else {
                            alert(j.message || 'Gagal reset PSB');
                        }
                    } catch (_) { alert('Gagal reset PSB'); }
                },
                async resetUnitConfirm() {
                    if (!this.programUnitId) { alert('Pilih unit terlebih dahulu'); return; }
                    if (!confirm('Reset data PSB untuk unit terpilih?')) return;
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=reset_psb', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ scope: 'unit', unit_id: this.programUnitId })
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            await this.refreshPrograms();
                            await this.refreshApplicants();
                            alert('Reset unit selesai');
                        } else {
                            alert(j.message || 'Gagal reset unit');
                        }
                    } catch (_) { alert('Gagal reset unit'); }
                },
                makeRequestId(unitId, name, birth, phone) {
                    const norm = String(name || '').trim().toLowerCase().replace(/\s+/g, ' ');
                    const sig = norm + '|' + (birth || '-') + '|' + (phone || '-');
                    let h = 0;
                    for (let i = 0; i < sig.length; i++) {
                        h = ((h << 5) - h) + sig.charCodeAt(i);
                        h |= 0;
                    }
                    const hex = Math.abs(h).toString(16).padStart(8, '0');
                    return 'PSB-' + unitId + '-' + hex;
                },
                async saveApplicant() {
                    this.saveMessage = '';
                    this.saveSuccess = false;
                    if (!this.applicantForm.unit_id) {
                        alert('Silakan pilih unit');
                        return;
                    }
                    if (this.programs.length > 0 && !this.applicantForm.program_id) {
                        alert('Silakan pilih kategori penerimaan');
                        return;
                    }
                    if (!this.applicantForm.name) {
                        alert('Nama wajib diisi');
                        return;
                    }
                    if (!this.isAdminRole) {
                        const u = (this.availableUnits || []).find(x => x.id === this.applicantForm.unit_id);
                        const ok = u && (this.allowedUnitCodes.includes(normalizeUnitCode(u.code)) || this.allowedUnitCodes.includes(normalizeUnitCode(u.prefix)));
                        if (!ok) {
                            alert('Unit tidak diizinkan untuk role Anda');
                            return;
                        }
                    }
                    const reqId = this.makeRequestId(this.applicantForm.unit_id, this.applicantForm.name, this.applicantForm.birth_date, this.applicantForm.phone);
                    const payload = {
                        ...this.applicantForm,
                        completeness: this.completeness,
                        request_id: reqId
                    };
                    this.savingApplicant = true;
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=save_applicant', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            this.saveMessage = j.message || 'Pendaftar disimpan';
                            this.saveSuccess = true;
                            this.applicantForm.name = '';
                            this.applicantForm.birth_date = '';
                            this.completeness = { KK: false, Akte: false, KTPWali: false, Foto: false, Ijazah: false };
                            await this.refreshApplicants();
                            this.showAddModal = false;
                        } else {
                            this.saveMessage = j.message || 'Gagal menyimpan';
                            this.saveSuccess = false;
                        }
                    } catch (e) {
                        this.saveMessage = 'Gagal menyimpan';
                        this.saveSuccess = false;
                    } finally {
                        this.savingApplicant = false;
                    }
                },
                openAddModal() {
                    this.saveMessage = '';
                    this.saveSuccess = false;
                    if (!this.isAdminRole) {
                        if (!this.applicantForm.unit_id && this.filteredUnits.length > 0) {
                            this.applicantForm.unit_id = this.filteredUnits[0].id;
                        }
                    }
                    if (this.applicantForm.unit_id && !this.programUnitId) this.programUnitId = this.applicantForm.unit_id;
                    this.showAddModal = true;
                },
                openDetail(a) {
                    this.detailApplicant = a;
                    this.detailSouvenirReceived = !!a.souvenir_received;
                    this.showDetailModal = true;
                },
                async saveSouvenir() {
                    if (!this.detailApplicant) return;
                    this.savingSouvenir = true;
                    try {
                        const r = await fetch(getBaseUrl() + 'api/psb.php?action=update_souvenir', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ applicant_id: this.detailApplicant.id, received: !!this.detailSouvenirReceived })
                        });
                        const j = await r.json();
                        if (j && j.success) {
                            this.detailApplicant.souvenir_received = this.detailSouvenirReceived ? 1 : 0;
                            const idx = this.applicants.findIndex(x => x.id === this.detailApplicant.id);
                            if (idx >= 0) this.applicants[idx].souvenir_received = this.detailApplicant.souvenir_received;
                            this.showDetailModal = false;
                        } else {
                            alert(j.message || 'Gagal menyimpan status souvenir');
                        }
                    } catch (_) {
                        alert('Gagal menyimpan status souvenir');
                    } finally {
                        this.savingSouvenir = false;
                    }
                }
            }
        });
        app.mount('#app');
    }
})();
</script>
