<?php
require_once '../../config/database.php';
// Fix for session save path error - use local project directory
$sessPath = __DIR__ . '/../../sessions';
if (file_exists($sessPath)) { session_save_path($sessPath); }
elseif (file_exists('C:/xampp/tmp')) { session_save_path('C:/xampp/tmp'); }
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['SUPERADMIN', 'ADMIN', 'FINANCE'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../../includes/header_finance.php';
?>

<div id="app" v-cloak class="flex flex-col h-screen">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white shadow-lg shadow-amber-200 hover:bg-amber-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Kas Bon / Panjar</h1>
                <span class="text-xs text-slate-500 font-medium">Pencatatan Uang Muka (Belum SPJ)</span>
            </div>
        </div>
        <button @click="openProposalModal()" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-200 flex items-center gap-2 border border-indigo-200">
            <i class="fas fa-file-signature"></i> Ambil dari Proposal
        </button>
        <button @click="openAdvanceModal()" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-amber-600 flex items-center gap-2 shadow-lg shadow-amber-200/50">
            <i class="fas fa-plus"></i> Buat Kas Bon Manual
        </button>
    </nav>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-slate-500 text-xs font-bold uppercase mb-1">Total Dana Keluar (Open)</p>
                            <h3 class="text-3xl font-bold text-amber-600">{{ formatCurrency(totalOpenAdvance) }}</h3>
                        </div>
                        <div class="bg-amber-100 p-3 rounded-lg text-amber-600">
                            <i class="fas fa-hand-holding-usd text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">Uang fisik yang sedang dibawa karyawan (Belum ada Nota).</p>
                </div>
            </div>

            <div class="flex gap-4">
                <button @click="filterStatus = 'OUTSTANDING'" :class="filterStatus === 'OUTSTANDING' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors">
                    Sedang Berjalan (Open / Unposted)
                </button>
                <button @click="filterStatus = 'ALL'" :class="filterStatus === 'ALL' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors">
                    Semua Riwayat
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Tanggal Ambil</th>
                            <th class="px-6 py-4">Nama Peminjam</th>
                            <th class="px-6 py-4">Keperluan</th>
                            <th class="px-6 py-4 text-right">Nominal Awal</th>
                            <th class="px-6 py-4 text-right">Realisasi (SPJ)</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="adv in filteredAdvances" :key="adv.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-slate-600">{{ formatDate(adv.request_date) }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ adv.requester_name }}</div>
                                <span v-if="adv.proposal_ref" class="bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded text-[10px] font-bold">#{{ adv.proposal_ref }}</span>
                                <div v-if="adv.proposal_ref && approvalsByRef[adv.proposal_ref]?.approved_by" class="mt-1 text-[11px]">
                                    <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded border border-emerald-100 inline-flex items-center gap-1">
                                        <i class="fas fa-user-check"></i>
                                        Disetujui: {{ approvalsByRef[adv.proposal_ref].approved_by }}
                                    </span>
                                    <span v-if="approvalsByRef[adv.proposal_ref]?.approved_at" class="text-slate-500 font-mono ml-1">
                                        {{ formatDateTime(approvalsByRef[adv.proposal_ref].approved_at) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ adv.purpose }}</td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-700">{{ formatCurrency(adv.amount) }}</td>
                            <td class="px-6 py-4 text-right font-mono">
                                <span v-if="adv.status === 'SETTLED'" class="font-bold text-emerald-600">{{ formatCurrency(adv.actual_amount) }}</span>
                                <span v-else class="text-slate-300">-</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="adv.status === 'OPEN'" class="bg-amber-100 text-amber-600 px-2 py-1 rounded text-[10px] font-bold">BELUM SPJ</span>
                                <span v-else-if="adv.status === 'SETTLED' && adv.is_recorded == 0" class="bg-orange-100 text-orange-600 px-2 py-1 rounded text-[10px] font-bold">MENUNGGU POSTING</span>
                                <span v-else class="bg-emerald-100 text-emerald-600 px-2 py-1 rounded text-[10px] font-bold">SELESAI</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button v-if="adv.status === 'OPEN'" @click="openSettleModal(adv)" class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-3 py-1 rounded-lg text-xs font-bold hover:bg-emerald-100 transition-colors">
                                    <i class="fas fa-check-circle mr-1"></i> Realisasi
                                </button>
                                <button v-if="adv.status === 'OPEN'" @click="deleteAdvance(adv)" class="ml-2 text-slate-300 hover:text-red-500 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <div v-if="adv.status === 'SETTLED'" class="text-[10px] text-slate-400">
                                    {{ formatDate(adv.settlement_date) }}
                                    <div v-if="Number(adv.amount) - Number(adv.actual_amount) > 0" class="text-emerald-500 font-bold">Kembali: {{ formatCurrency(Number(adv.amount) - Number(adv.actual_amount)) }}</div>
                                    <div v-else-if="Number(adv.amount) - Number(adv.actual_amount) < 0" class="text-red-500 font-bold">Kurang: {{ formatCurrency(Math.abs(Number(adv.amount) - Number(adv.actual_amount))) }}</div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredAdvances.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">
                                Tidak ada data kas bon.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showCreateModal = false">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl animate-fade">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Buat Kas Bon Baru</h3>
            <div v-if="form.proposal_ref && selectedProposal" class="bg-emerald-50 p-3 rounded-lg border border-emerald-100 mb-4">
                <div class="text-[10px] uppercase font-bold text-slate-400">Proposal</div>
                <div class="text-sm font-bold text-slate-800">{{ selectedProposal.title }}</div>
                <div class="text-xs text-slate-600">{{ selectedProposal.description }}</div>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">#{{ selectedProposal.reference_no }}</span>
                    <span v-if="selectedProposal.approved_by" class="text-emerald-700 font-bold flex items-center gap-1">
                        <i class="fas fa-user-check"></i> Disetujui: {{ selectedProposal.approved_by }}
                    </span>
                    <span v-if="selectedProposal.approved_at" class="text-slate-500 font-mono">{{ formatDateTime(selectedProposal.approved_at) }}</span>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Peminjam / Karyawan</label>
                    <input v-model="form.requester_name" type="text" placeholder="Contoh: Pak Budi (Logistik)" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nominal (Rp)</label>
                    <input v-model="form.amount" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-amber-500 font-mono text-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Keperluan</label>
                    <textarea v-model="form.purpose" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-amber-500 h-20" placeholder="Contoh: Beli cat dinding di toko material"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Ambil</label>
                    <input v-model="form.request_date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-amber-500">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="showCreateModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Batal</button>
                <button @click="submitAdvance" class="bg-amber-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-amber-600 shadow-lg shadow-amber-200">Simpan</button>
            </div>
        </div>
    </div>

    <div v-if="showSettleModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showSettleModal = false">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl animate-fade">
            <h3 class="text-xl font-bold text-slate-800 mb-1">Realisasi Kas Bon</h3>
            <p class="text-sm text-slate-500 mb-4">Penyelesaian nota belanja untuk <span class="font-bold text-slate-700">{{ selectedAdvance.requester_name }}</span></p>
            <div class="bg-amber-50 p-4 rounded-lg border border-amber-100 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Nominal Awal:</span>
                    <span class="font-mono font-bold text-slate-800">{{ formatCurrency(selectedAdvance.amount) }}</span>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Total Realisasi (Sesuai Nota)</label>
                    <input v-model="settleForm.actual_amount" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-emerald-500 font-mono text-lg">
                </div>
                <div v-if="settleForm.actual_amount" class="text-sm font-bold text-center py-2 bg-slate-50 rounded-lg">
                    <span v-if="Number(selectedAdvance.amount) - Number(settleForm.actual_amount) > 0" class="text-emerald-600">
                        Sisa Uang Kembali: {{ formatCurrency(Number(selectedAdvance.amount) - Number(settleForm.actual_amount)) }}
                    </span>
                    <span v-else-if="Number(selectedAdvance.amount) - Number(settleForm.actual_amount) < 0" class="text-red-600">
                        Kurang Bayar (Reimburse): {{ formatCurrency(Math.abs(Number(selectedAdvance.amount) - Number(settleForm.actual_amount))) }}
                    </span>
                    <span v-else class="text-slate-500">Pas (Tidak ada sisa)</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Penyelesaian</label>
                    <input v-model="settleForm.settlement_date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Catatan Tambahan</label>
                    <textarea v-model="settleForm.settlement_note" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-emerald-500 h-20" placeholder="No. Nota, Keterangan barang, dll"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="showSettleModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Batal</button>
                <button @click="submitSettle" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-200">Selesaikan</button>
            </div>
        </div>
    </div>

    <div v-if="showProposalModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showProposalModal = false">
        <div class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-2xl animate-fade h-[80vh] flex flex-col">
            <h3 class="text-xl font-bold text-slate-800 mb-1 flex-none"><i class="fas fa-file-signature text-indigo-600 mr-2"></i> Pilih Proposal Disetujui</h3>
            <p class="text-sm text-slate-500 mb-4 flex-none">Pilih proposal yang akan dicairkan dananya melalui Kas Bon.</p>
            <div class="flex-1 overflow-y-auto space-y-3 pr-2">
                <div v-if="loadingProposals" class="text-center py-12 text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                </div>
                <div v-else-if="approvedProposals.length === 0" class="text-center py-12 text-slate-400 italic border border-dashed border-slate-200 rounded-xl">
                    Tidak ada proposal yang siap dicairkan.
                </div>
                <div v-for="prop in approvedProposals" :key="prop.id" class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer group" @click="selectProposal(prop)">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded text-[10px] font-bold">#{{ prop.reference_no }}</span>
                                <span class="text-xs text-slate-400">{{ formatDate(prop.created_at) }}</span>
                            </div>
                            <h4 class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ prop.title }}</h4>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-emerald-600 font-mono">{{ formatCurrency(prop.amount) }}</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 line-clamp-2 mb-2">{{ prop.description }}</p>
                    <div v-if="prop.approved_by" class="flex items-center gap-2 text-xs mb-2">
                        <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded border border-emerald-100 flex items-center gap-1">
                            <i class="fas fa-user-check"></i> Disetujui: {{ prop.approved_by }}
                        </span>
                        <span v-if="prop.approved_at" class="text-slate-500 font-mono">{{ formatDateTime(prop.approved_at) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500 border-t border-slate-50 pt-2">
                        <span><i class="fas fa-user mr-1"></i> {{ prop.requester }}</span>
                        <span class="text-indigo-600 font-bold group-hover:underline">Pilih & Buat Kas Bon <i class="fas fa-arrow-right ml-1"></i></span>
                    </div>
                </div>
            </div>
            <div class="flex justify-end pt-4 border-t border-slate-100 mt-4 flex-none">
                <button @click="showProposalModal = false" class="px-6 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                advances: [],
                filterStatus: 'OUTSTANDING',
                baseUrl: (window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/')),
                approvalsByRef: {},
                showCreateModal: false,
                form: {
                    requester_name: '',
                    amount: 0,
                    purpose: '',
                    request_date: new Date().toISOString().split('T')[0],
                    proposal_ref: ''
                },
                showSettleModal: false,
                selectedAdvance: null,
                settleForm: {
                    actual_amount: 0,
                    settlement_date: new Date().toISOString().split('T')[0],
                    settlement_note: ''
                },
                showProposalModal: false,
                loadingProposals: false,
                approvedProposals: [],
                selectedProposal: null
            }
        },
        computed: {
            filteredAdvances() {
                if (this.filterStatus === 'OUTSTANDING') {
                    return this.advances.filter(a => a.status === 'OPEN');
                }
                return this.advances;
            },
            totalOpenAdvance() {
                return this.advances.filter(a => a.status === 'OPEN')
                    .reduce((sum, adv) => sum + Number(adv.amount), 0);
            }
        },
        mounted() {
            this.fetchData();
        },
        methods: {
            formatCurrency(v) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
            },
            formatDate(d) {
                return new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
            },
            formatDateTime(d) {
                return new Date(d).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            },
            async fetchData() {
                try {
                    const res = await fetch(this.baseUrl + 'api/finance.php?action=get_cash_advances');
                    const data = await res.json();
                    if (data.success) {
                        this.advances = data.data || [];
                    }
                    const resApp = await fetch(this.baseUrl + 'api/approval.php?action=get_list&status=APPROVED');
                    const dataApp = await resApp.json();
                    if (dataApp.success) {
                        const map = {};
                        (dataApp.data || []).forEach(item => { map[item.reference_no] = item; });
                        this.approvalsByRef = map;
                        this.approvedProposals = dataApp.data.filter(x => Number(x.amount) > 0);
                    }
                } catch (e) {}
            },
            openAdvanceModal() {
                this.selectedProposal = null;
                this.showCreateModal = true;
            },
            openProposalModal() {
                this.showProposalModal = true;
                this.loadingProposals = true;
                fetch(this.baseUrl + 'api/approval.php?action=get_list&status=APPROVED')
                    .then(r => r.json())
                    .then(d => {
                        this.approvedProposals = (d.data || []).filter(x => Number(x.amount) > 0);
                    })
                    .finally(() => this.loadingProposals = false);
            },
            selectProposal(prop) {
                this.selectedProposal = prop;
                this.form.proposal_ref = prop.reference_no;
                this.showProposalModal = false;
                this.showCreateModal = true;
            },
            async submitAdvance() {
                const payload = { ...this.form };
                try {
                    const res = await fetch(this.baseUrl + 'api/finance.php?action=create_cash_advance', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ...payload, request_id: (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : ('adv-' + Date.now() + '-' + Math.random().toString(36).slice(2)) })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Kas Bon berhasil dibuat');
                        this.showCreateModal = false;
                        this.fetchData();
                    } else {
                        alert(data.message || 'Gagal menyimpan');
                    }
                } catch (e) {}
            },
            openSettleModal(adv) {
                this.selectedAdvance = adv;
                this.settleForm.actual_amount = Number(adv.actual_amount) || 0;
                this.settleForm.settlement_date = new Date().toISOString().split('T')[0];
                this.settleForm.settlement_note = '';
                this.showSettleModal = true;
            },
            async submitSettle() {
                if (!this.selectedAdvance) return;
                const payload = {
                    advance_id: this.selectedAdvance.id,
                    actual_amount: Number(this.settleForm.actual_amount),
                    settlement_date: this.settleForm.settlement_date,
                    settlement_note: this.settleForm.settlement_note
                };
                try {
                    const res = await fetch(this.baseUrl + 'api/finance.php?action=settle_cash_advance', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Realisasi berhasil disimpan');
                        this.showSettleModal = false;
                        this.fetchData();
                    } else {
                        alert(data.message || 'Gagal menyimpan');
                    }
                } catch (e) {}
            },
            async deleteAdvance(adv) {
                if (!confirm('Hapus kas bon ini?\n\n' + adv.requester_name + ' - ' + formatCurrency(adv.amount))) return;
                try {
                    const res = await fetch(this.baseUrl + 'api/finance.php?action=delete_cash_advance', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ advance_id: adv.id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Kas Bon dihapus');
                        this.fetchData();
                    } else {
                        alert(data.message || 'Gagal menghapus');
                    }
                } catch (e) {}
            }
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
