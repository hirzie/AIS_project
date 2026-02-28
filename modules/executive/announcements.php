<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../includes/header.php';
require_once '../../config/database.php';
$__displayName = $_SESSION['username'] ?? 'Pengguna';
if (!empty($_SESSION['person_id'])) {
    try {
        $st = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
        $st->execute([$_SESSION['person_id']]);
        $nm = $st->fetchColumn();
        if ($nm) { $__displayName = $nm; }
    } catch (\Throwable $e) {}
}
?>
<style>
    [v-cloak] { display: none !important; }
</style>
<script>
    window.SKIP_GLOBAL_APP = true;
    window.USER_FULL_NAME = <?php echo json_encode($__displayName); ?>;
    window.USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
</script>
<div id="app" v-cloak class="flex-1 flex flex-col h-screen overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-orange-200">
                <i class="fas fa-bullhorn text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Kelola Pengumuman</h1>
                <span class="text-xs text-slate-500 font-medium">Executive Dashboard</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
            <a :href="baseUrl + 'modules/executive/index.php'" class="text-slate-400 hover:text-indigo-600 transition-colors" title="Kembali ke Managerial View">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <button @click="openAddModal" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-orange-700 transition-colors">
                            Tambah Pengumuman
                        </button>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" v-model="includeExpired" @change="fetchAnnouncements">
                            Tampilkan yang sudah lewat
                        </label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="searchQuery" placeholder="Cari judul/isi..." class="w-64 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        <select v-model="filterAudience" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                            <option value="ALL">Semua Audience</option>
                            <option value="EXECUTIVE">Executive</option>
                            <option value="ACADEMIC">Academic</option>
                            <option value="SECURITY">Security</option>
                            <option value="FINANCE">Finance</option>
                            <option value="FOUNDATION">Foundation</option>
                        </select>
                    </div>
                </div>
                <div id="announcementsList" class="space-y-3">
                    <div v-if="loading" class="text-center text-slate-400 italic py-4">Memuat pengumuman...</div>
                    <div v-if="error" class="text-center text-red-500 italic py-4">{{ errorMessage }}</div>
                    <div v-if="filteredAnnouncements.length === 0 && !loading && !error" class="text-center text-slate-400 italic py-4">Belum ada pengumuman.</div>
                    <div v-for="ann in filteredAnnouncements" :key="ann.id" class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-bold text-slate-800">{{ ann.title }}</div>
                                <div class="text-sm text-slate-600 mt-1">{{ ann.content }}</div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="openEditModal(ann)" class="text-blue-600 hover:text-blue-800"><i class="fas fa-pencil"></i></button>
                                <button @click="deleteAnnouncement(ann.id)" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                            <span>Oleh: {{ ann.created_by }}</span>
                            <span>Dibuat: {{ formatDate(ann.created_at) }}</span>
                            <span v-if="ann.expires_at">Tampil sampai: {{ formatDate(ann.expires_at) }}</span>
                            <span>Audience: {{ ann.audience || 'ALL' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div v-if="showAddModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Tambah Pengumuman</h3>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Judul</label>
                    <input v-model="newAnnouncement.title" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Isi Pengumuman</label>
                    <textarea v-model="newAnnouncement.content" rows="4" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Audience</label>
                    <select v-model="newAnnouncement.audience" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        <option value="ALL">Semua Pengguna</option>
                        <option value="EXECUTIVE">Executive</option>
                        <option value="ACADEMIC">Academic</option>
                        <option value="SECURITY">Security</option>
                        <option value="FINANCE">Finance</option>
                        <option value="FOUNDATION">Foundation</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Tampil Sampai (Hari)</label>
                    <input v-model="newAnnouncement.expires_at" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Pemberi Pengumuman (Opsional)</label>
                    <input v-model="newAnnouncement.created_by" type="text" placeholder="Contoh: Kepala Sekolah" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button @click="showAddModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="saveNewAnnouncement" :disabled="saving" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-bold hover:bg-orange-700 transition-colors disabled:opacity-50">
                    {{ saving ? 'Menyimpan...' : 'Simpan Pengumuman' }}
                </button>
            </div>
        </div>
    </div>
    <div v-if="showEditModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Edit Pengumuman</h3>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Judul</label>
                    <input v-model="editedAnnouncement.title" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Isi Pengumuman</label>
                    <textarea v-model="editedAnnouncement.content" rows="4" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Audience</label>
                    <select v-model="editedAnnouncement.audience" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        <option value="ALL">Semua Pengguna</option>
                        <option value="EXECUTIVE">Executive</option>
                        <option value="ACADEMIC">Academic</option>
                        <option value="SECURITY">Security</option>
                        <option value="FINANCE">Finance</option>
                        <option value="FOUNDATION">Foundation</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Tampil Sampai (Hari)</label>
                    <input v-model="editedAnnouncement.expires_at" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Pemberi Pengumuman</label>
                    <input v-model="editedAnnouncement.created_by" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button @click="showEditModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="updateAnnouncement" :disabled="saving" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-bold hover:bg-orange-700 transition-colors disabled:opacity-50">
                    {{ saving ? 'Memperbarui...' : 'Perbarui Pengumuman' }}
                </button>
            </div>
        </div>
    </div>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            baseUrl: window.BASE_URL || '/',
            currentDate: new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }),
            announcements: [],
            loading: false,
            error: false,
            errorMessage: '',
            includeExpired: false,
            searchQuery: '',
            filterAudience: 'ALL',
            showAddModal: false,
            showEditModal: false,
            saving: false,
            newAnnouncement: {
                title: '',
                content: '',
                audience: 'ALL',
                expires_at: '',
                created_by: ''
            },
            editedAnnouncement: {
                id: '',
                title: '',
                content: '',
                audience: 'ALL',
                expires_at: '',
                created_by: ''
            }
        };
    },
    computed: {
        filteredAnnouncements() {
            const q = this.searchQuery.toLowerCase();
            return (this.announcements || []).filter(a => {
                const audOk = this.filterAudience === 'ALL' || (String(a.audience || 'ALL').toUpperCase() === this.filterAudience);
                const text = ((a.title || '') + ' ' + (a.content || '')).toLowerCase();
                return audOk && text.includes(q);
            });
        }
    },
    methods: {
        normalizeBaseUrl() {
            let b = window.BASE_URL || '/';
            if (b === '/' || !b) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                b = m ? `/${m[1]}/` : '/';
            }
            this.baseUrl = b;
        },
        formatDate(d) {
            try { return new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }); } catch(_) { return d; }
        },
        async fetchAnnouncements() {
            this.loading = true;
            this.error = false;
            try {
                this.normalizeBaseUrl();
                const url = this.baseUrl + 'api/manage_agenda.php?action=get_announcements' + (this.includeExpired ? '&include_expired=1' : '');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    this.announcements = data.data || [];
                } else {
                    this.error = true;
                    this.errorMessage = data.message || 'Gagal memuat pengumuman';
                }
            } catch (e) {
                this.error = true;
                this.errorMessage = 'Gagal memuat pengumuman';
            } finally {
                this.loading = false;
            }
        },
        openAddModal() {
            this.newAnnouncement = { title: '', content: '', audience: 'ALL', expires_at: '', created_by: '' };
            this.showAddModal = true;
        },
        openEditModal(ann) {
            this.editedAnnouncement = {
                id: ann.id,
                title: ann.title || '',
                content: ann.content || '',
                audience: ann.audience || 'ALL',
                expires_at: ann.expires_at || '',
                created_by: ann.created_by || ''
            };
            this.showEditModal = true;
        },
        async saveNewAnnouncement() {
            const t = this.newAnnouncement.title.trim();
            const c = this.newAnnouncement.content.trim();
            if (!t || !c) { alert('Judul dan isi pengumuman wajib'); return; }
            this.saving = true;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/manage_agenda.php?action=save_announcement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: t,
                        content: c,
                        audience: this.newAnnouncement.audience,
                        expires_at: this.newAnnouncement.expires_at,
                        created_by: (this.newAnnouncement.created_by || '').trim()
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showAddModal = false;
                    this.fetchAnnouncements();
                } else {
                    alert(data.message || 'Gagal menyimpan pengumuman');
                }
            } catch (e) {
                alert('Gagal menyimpan pengumuman');
            } finally {
                this.saving = false;
            }
        },
        async updateAnnouncement() {
            const t = this.editedAnnouncement.title.trim();
            const c = this.editedAnnouncement.content.trim();
            if (!t || !c || !this.editedAnnouncement.id) { alert('ID, Judul dan isi pengumuman wajib'); return; }
            this.saving = true;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/manage_agenda.php?action=save_announcement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.editedAnnouncement.id,
                        title: t,
                        content: c,
                        audience: this.editedAnnouncement.audience,
                        expires_at: this.editedAnnouncement.expires_at,
                        created_by: (this.editedAnnouncement.created_by || '').trim()
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showEditModal = false;
                    this.fetchAnnouncements();
                } else {
                    alert(data.message || 'Gagal memperbarui pengumuman');
                }
            } catch (e) {
                alert('Gagal memperbarui pengumuman');
            } finally {
                this.saving = false;
            }
        },
        async deleteAnnouncement(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/manage_agenda.php?action=delete_announcement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchAnnouncements();
                } else {
                    alert(data.message || 'Gagal menghapus pengumuman');
                }
            } catch (e) {
                alert('Gagal menghapus pengumuman');
            }
        }
    },
    mounted() {
        this.fetchAnnouncements();
    }
}).mount('#app');
</script>

