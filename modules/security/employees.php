<?php
require_once '../../includes/guard.php';
require_login_and_module('security');
require_once '../../includes/header.php';
?>
<div id="app" v-cloak class="flex flex-col h-screen">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Pegawai Keamanan</h1>
                <span class="text-xs text-slate-500 font-medium">Read-only dari HRD</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>index.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
                <i class="fas fa-power-off text-lg"></i>
            </a>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Daftar Staf Keamanan</h3>
                <div class="flex items-center gap-3">
                    <input v-model="q" type="text" placeholder="Cari nama..." class="border border-slate-300 rounded-lg px-3 py-1 text-sm">
                    <span class="text-xs text-slate-500">{{ filtered.length }} pegawai</span>
                    <button @click="openAddModal" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700">
                        <i class="fas fa-user-plus"></i> Tambah
                    </button>
                </div>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div v-for="s in filtered" :key="s.id" class="border border-slate-200 rounded-lg p-3 flex items-center justify-between hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-800">{{ s.name }}</div>
                                <div class="text-xs text-slate-500">{{ s.employee_number || '-' }}</div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px] font-bold">SECURITY</span>
                    </div>
                    <div v-if="filtered.length === 0" class="text-center text-slate-500 py-6">Tidak ada data</div>
                </div>
            </div>
        </div>
    </main>
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @click.self="closeAddModal">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">Tambah Pegawai Keamanan</h3>
                <button @click="closeAddModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="submitEmployee" class="p-6 space-y-3">
                <div>
                    <label class="text-xs text-slate-500">Nama</label>
                    <input v-model="form.name" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-xs text-slate-500">NIP</label>
                    <input v-model="form.employee_number" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-500">Jenis Kelamin</label>
                        <select v-model="form.gender" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Status Kepegawaian</label>
                        <select v-model="form.employment_status" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            <option value="TETAP">Tetap</option>
                            <option value="KONTRAK">Kontrak</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-500">Status Aktif</label>
                        <select v-model="form.status" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            <option value="ACTIVE">Aktif</option>
                            <option value="INACTIVE">Tidak Aktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Tanggal Mulai</label>
                        <input type="date" v-model="form.join_date" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <button type="button" @click="closeAddModal" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-200">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            staff: [],
            q: '',
            showAddModal: false,
            form: {
                employee_number: '',
                name: '',
                gender: 'L',
                employment_status: 'TETAP',
                status: 'ACTIVE',
                join_date: ''
            }
        };
    },
    computed: {
        filtered() {
            const f = (this.staff || []).filter(x => (x.role || '').toUpperCase() === 'SECURITY');
            if (!this.q) return f;
            const q = this.q.toLowerCase();
            return f.filter(x => (x.name || '').toLowerCase().includes(q));
        }
    },
    methods: {
        baseUrl() {
            let b = window.BASE_URL || '/';
            if (b === '/' || !b) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                b = m ? `/${m[1]}/` : '/';
            }
            return b;
        },
        async fetchStaff() {
            try {
                const res = await fetch(this.baseUrl() + 'api/security.php?action=list_employees');
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) { this.staff = []; return; }
                const j = await res.json();
                this.staff = j && j.success ? (j.data || []) : [];
            } catch (e) { this.staff = []; }
        },
        openAddModal() { this.showAddModal = true; },
        closeAddModal() { this.showAddModal = false; },
        async submitEmployee() {
            if (!this.form.name || !this.form.employee_number) { alert('Nama dan NIP wajib'); return; }
            try {
                const payload = {
                    action: 'create',
                    employee_number: this.form.employee_number,
                    name: this.form.name,
                    gender: this.form.gender,
                    join_date: this.form.join_date || null,
                    employment_status: this.form.employment_status,
                    status: this.form.status,
                    employee_type: 'SECURITY',
                    department: 'Non-Akademik'
                };
                const r = await fetch(this.baseUrl() + 'api/manage_employee.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const j = await r.json();
                if (j && j.success) {
                    alert('Pegawai ditambahkan');
                    this.closeAddModal();
                    await this.fetchStaff();
                } else {
                    alert(j.error || 'Gagal menyimpan');
                }
            } catch (e) { alert('Gagal menyimpan'); }
        }
    },
    async mounted() {
        await this.fetchStaff();
    }
}).mount('#app');
</script>
<?php require_once '../../includes/footer.php'; ?>
