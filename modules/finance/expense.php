<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-red-200">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Transaksi Pengeluaran</h1>
                <span class="text-xs text-slate-500 font-medium">Catat Biaya & Operasional</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
             <div class="flex bg-slate-100 p-1 rounded-lg border border-slate-200">
                <button @click="mode = 'MANUAL'" :class="mode === 'MANUAL' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1 rounded text-xs font-bold transition-all">Manual</button>
                <button @click="mode = 'KASBON'" :class="mode === 'KASBON' ? 'bg-amber-100 text-amber-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1 rounded text-xs font-bold transition-all flex items-center gap-1">
                    <i class="fas fa-receipt"></i> Dari Kas Bon
                </button>
                <button @click="mode = 'PROPOSAL'" :class="mode === 'PROPOSAL' ? 'bg-indigo-100 text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1 rounded text-xs font-bold transition-all flex items-center gap-1">
                    <i class="fas fa-file-signature"></i> Dari Proposal
                </button>
             </div>

             <div class="flex items-center gap-2 bg-slate-100 rounded-lg px-3 py-1.5 border border-slate-200">
                <i class="fas fa-calendar-alt text-slate-400"></i>
                <input type="date" v-model="globalDate" class="bg-transparent text-sm font-bold text-slate-700 focus:outline-none">
             </div>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden flex flex-col md:flex-row">
        
        <div v-if="mode === 'KASBON'" class="w-full md:w-[450px] bg-amber-50 border-r border-amber-100 flex flex-col h-full shadow-xl z-10 overflow-y-auto">
            <div class="p-6">
                <h3 class="font-bold text-amber-800 text-sm mb-4"><i class="fas fa-list mr-1"></i> Pilih Realisasi Kas Bon</h3>
                
                <div v-if="loadingKasbon" class="text-center py-8 text-amber-600">
                    <i class="fas fa-circle-notch fa-spin"></i> Memuat...
                </div>

                <div v-else class="space-y-3">
                    <div v-if="settledAdvances.length === 0" class="text-center py-8 text-amber-400 italic bg-white/50 rounded-xl border border-amber-100">
                        Tidak ada kas bon yang siap dicatat (settled).
                    </div>

                    <div v-for="adv in settledAdvances" :key="adv.id" class="bg-white p-4 rounded-xl border border-amber-200 shadow-sm hover:shadow-md transition-all cursor-pointer relative group" @click="selectAdvance(adv)">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-bold text-slate-800">{{ adv.requester_name }}</div>
                                <div class="text-xs text-slate-500">{{ formatDate(adv.settlement_date) }}</div>
                            </div>
                            <span class="bg-emerald-100 text-emerald-600 text-[10px] font-bold px-2 py-1 rounded">SETTLED</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-2 line-clamp-2">{{ adv.purpose }}</p>
                        <div class="flex justify-between items-end border-t border-slate-100 pt-2">
                            <div>
                                <div class="text-[10px] text-slate-400">Realisasi:</div>
                                <div class="font-mono font-bold text-amber-600">{{ formatNumber(adv.actual_amount) }}</div>
                            </div>
                            <button class="bg-amber-100 text-amber-700 px-3 py-1 rounded text-xs font-bold hover:bg-amber-200">
                                Pilih <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mode === 'PROPOSAL'" class="w-full md:w-[450px] bg-indigo-50 border-r border-indigo-100 flex flex-col h-full shadow-xl z-10 overflow-y-auto">
            <div class="p-6">
                <h3 class="font-bold text-indigo-800 text-sm mb-4"><i class="fas fa-file-signature mr-1"></i> Pilih Proposal Disetujui</h3>
                
                <div v-if="loadingProposals" class="text-center py-8 text-indigo-600">
                    <i class="fas fa-circle-notch fa-spin"></i> Memuat...
                </div>

                <div v-else class="space-y-3">
                    <div v-if="approvedProposals.length === 0" class="text-center py-8 text-indigo-400 italic bg-white/50 rounded-xl border border-indigo-100">
                        Tidak ada proposal pending.
                    </div>

                    <div v-for="prop in approvedProposals" :key="prop.id" class="bg-white p-4 rounded-xl border border-indigo-200 shadow-sm hover:shadow-md transition-all cursor-pointer relative group" @click="selectProposal(prop)">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-bold text-slate-800 text-sm line-clamp-1">{{ prop.title }}</div>
                                <div class="text-[10px] text-slate-500">{{ formatDate(prop.created_at) }}</div>
                            </div>
                            <span class="bg-indigo-100 text-indigo-600 text-[10px] font-bold px-2 py-1 rounded">#{{ prop.reference_no }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-2 line-clamp-2">{{ prop.description }}</p>
                        <div class="flex justify-between items-end border-t border-slate-100 pt-2">
                            <div>
                                <div class="text-[10px] text-slate-400">Nominal Disetujui:</div>
                                <div class="font-mono font-bold text-indigo-600">{{ formatNumber(prop.amount) }}</div>
                            </div>
                            <button class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-bold hover:bg-indigo-200">
                                Pilih <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="w-full md:w-[450px] bg-white border-r border-slate-200 flex flex-col h-full shadow-xl z-10 overflow-y-auto">
            <div class="p-6 space-y-5">
                
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <h3 class="font-bold text-blue-800 text-sm mb-1"><i class="fas fa-edit mr-1"></i> Input Transaksi</h3>
                    <p class="text-xs text-blue-600">Lengkapi form di bawah ini untuk menambahkan item pengeluaran.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">1. Pilih Unit / Departemen</label>
                    <select v-model="form.unit_id" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none transition-all">
                        <option value="">-- Pilih Unit --</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">2. Jenis Pengeluaran</label>
                    <select v-model="form.category_id" @change="onCategoryChange" :disabled="!form.unit_id" class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none transition-all disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">-- Pilih Kategori --</option>
                        <option v-for="c in filteredCategories" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">3. Nominal (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 font-bold text-slate-400">Rp</span>
                        <input type="number" v-model="form.amount" class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-lg font-bold text-slate-800 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none transition-all" placeholder="0">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">No. Nota Toko / Eksternal (Opsional)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-400"><i class="fas fa-receipt"></i></span>
                        <input type="text" v-model="form.invoice_number" class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-800 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none transition-all" placeholder="Nomor Bukti Transaksi">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">*Nomor Nota Sistem (SD26...) akan digenerate otomatis.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">4. Keterangan / Keperluan</label>
                    <textarea v-model="form.description" rows="2" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none transition-all" placeholder="Detail pengeluaran..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Pengguna (User)</label>
                        <input type="text" v-model="form.pic" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm font-medium focus:border-red-500 outline-none" placeholder="Nama Pemohon">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Penerima</label>
                        <input type="text" v-model="form.receiver" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm font-medium focus:border-red-500 outline-none" placeholder="Pihak Luar">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Sumber Dana (Kredit)</label>
                    <select v-model="form.cash_account_id" class="w-full border border-slate-300 bg-blue-50 rounded-lg px-3 py-2.5 text-sm font-bold text-blue-800 focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Akun Kas --</option>
                        <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">{{ acc.code }} - {{ acc.name }}</option>
                    </select>
                </div>

                <button @click="addToCart" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-slate-900 transition-all flex items-center justify-center gap-2 shadow-lg">
                    <i class="fas fa-plus-circle"></i> Tambah ke Daftar
                </button>

            </div>
        </div>

        <div class="flex-1 flex flex-col bg-slate-50 relative overflow-hidden">
            
            <div class="flex-1 overflow-y-auto p-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden min-h-[400px] flex flex-col">
                    
                    <div class="bg-slate-100 px-6 py-3 border-b border-slate-200 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700">Daftar Pengeluaran (Draft)</h3>
                        <span class="text-xs font-bold bg-slate-200 text-slate-600 px-2 py-1 rounded">{{ cart.length }} Item</span>
                    </div>

                    <div class="flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center">#</th>
                                    <th class="px-4 py-3">Pengeluaran</th>
                                    <th class="px-4 py-3">Detail Transaksi</th>
                                    <th class="px-4 py-3">Sumber Dana</th>
                                    <th class="px-4 py-3 text-right">Jumlah</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in cart" :key="index" class="hover:bg-slate-50 group">
                                    <td class="px-4 py-3 text-center text-slate-400">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-700">{{ getCategoryName(item.category_id) }}</div>
                                        <div class="text-xs text-slate-500">{{ getUnitName(item.unit_id) }}</div>
                                        <div v-if="item.invoice_number" class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded inline-block mt-1 border border-slate-200">
                                            <i class="fas fa-receipt mr-1 text-slate-400"></i> {{ item.invoice_number }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-700">{{ item.description }}</div>
                                        <div class="flex gap-3 mt-1 text-xs text-slate-500">
                                            <span v-if="item.pic"><i class="fas fa-user mr-1"></i> {{ item.pic }}</span>
                                            <span v-if="item.receiver"><i class="fas fa-hand-holding-usd mr-1"></i> {{ item.receiver }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                                        {{ getAccountCode(item.cash_account_id) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-800">
                                        Rp {{ formatNumber(item.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="removeFromCart(index)" class="text-slate-300 hover:text-red-600 transition-colors"><i class="fas fa-times-circle text-lg"></i></button>
                                    </td>
                                </tr>
                                <tr v-if="cart.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-shopping-basket text-4xl mb-3 opacity-20"></i>
                                            <p>Belum ada item ditambahkan.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end items-center gap-4">
                        <span class="text-slate-500 font-bold uppercase text-xs tracking-wider">Total Pengeluaran</span>
                        <span class="text-2xl font-bold text-slate-800">Rp {{ formatNumber(totalAmount) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex justify-between items-center z-20">
                <button @click="cart = []" :disabled="cart.length === 0" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 disabled:opacity-50 transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i> Bersihkan
                </button>
                <button @click="saveTransaction" :disabled="cart.length === 0" class="bg-red-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-200 disabled:opacity-50 disabled:shadow-none transition-all active:scale-[0.98]">
                    <i class="fas fa-save mr-2"></i> Simpan & Cetak Struk
                </button>
            </div>

        </div>

    </main>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                mode: 'MANUAL',
                loadingKasbon: false,
                loadingProposals: false,
                settledAdvances: [],
                approvedProposals: [],
                
                globalDate: new Date().toISOString().split('T')[0],
                units: [],
                categories: [],
                accounts: [],
                
                form: {
                    unit_id: '',
                    category_id: '',
                    amount: '',
                    invoice_number: '',
                    description: '',
                    cash_account_id: '',
                    pic: '',
                    receiver: '',
                    advance_id: null,
                    proposal_ref: null
                },

                cart: []
            }
        },
        computed: {
            filteredCategories() {
                if (!this.form.unit_id) return [];
                return this.categories.filter(c => 
                    !c.department_id || c.department_id == this.form.unit_id
                );
            },
            cashAccounts() {
                return this.accounts.filter(a => ['ASSET', 'EQUITY'].includes(a.type));
            },
            totalAmount() {
                return this.cart.reduce((sum, item) => sum + Number(item.amount), 0);
            }
        },
        methods: {
            async fetchApprovedProposals() {
                this.loadingProposals = true;
                try {
                    const res = await fetch(window.BASE_URL + 'api/approval.php?action=get_list&status=APPROVED');
                    const data = await res.json();
                    if (data.success) {
                        this.approvedProposals = data.data.filter(p => !p.payout_trans_number && Number(p.amount) > 0);
                    }
                } catch (e) { console.error(e); }
                finally { this.loadingProposals = false; }
            },
            selectProposal(prop) {
                this.form.amount = prop.amount;
                this.form.description = `${prop.title} - ${prop.description} (Ref: ${prop.reference_no})`;
                this.form.pic = prop.requester;
                this.form.proposal_ref = prop.reference_no;
            },
            async fetchSettledAdvances() {
                this.loadingKasbon = true;
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_cash_advances&status=SETTLED&unrecorded_only=1');
                    const data = await res.json();
                    if (data.success) this.settledAdvances = data.data;
                } catch (e) { console.error(e); }
                finally { this.loadingKasbon = false; }
            },
            selectAdvance(adv) {
                this.form.amount = adv.actual_amount;
                this.form.description = `Realisasi Kas Bon: ${adv.purpose} (Oleh: ${adv.requester_name})`;
                if (adv.settlement_note) {
                    this.form.description += ` - Catatan: ${adv.settlement_note}`;
                }
                this.form.pic = adv.requester_name;
                this.form.advance_id = adv.id;
                this.form.expected_amount = adv.actual_amount;
            },
            formatDate(str) {
                if (!str) return '-';
                return new Date(str).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            },
            async fetchInit() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.categories = data.data.expenseCategories;
                        this.units = data.data.units;
                    }
                    const resAcc = await fetch(window.BASE_URL + 'api/finance.php?action=get_accounts');
                    const dataAcc = await resAcc.json();
                    if (dataAcc.success) {
                        this.accounts = dataAcc.data;
                    }

                    const urlParams = new URLSearchParams(window.location.search);
                    const refNo = urlParams.get('ref');
                    if (refNo) {
                        this.loadApprovalData(refNo);
                    }
                } catch (e) {}
            },
            async loadApprovalData(refNo) {
                try {
                    const res = await fetch(window.BASE_URL + `api/approval.php?action=get_by_ref&ref=${refNo}`);
                    const data = await res.json();
                    if (data.success && data.data) {
                        const item = data.data;
                        this.form.amount = item.amount;
                        this.form.description = `${item.title} - ${item.description} (Ref: ${item.reference_no})`;
                        this.form.pic = item.requester;
                    } else {
                        alert('Data persetujuan tidak ditemukan atau belum disetujui.');
                    }
                } catch (e) {
                    console.error('Failed to load approval', e);
                }
            },
            getCategoryName(id) {
                const c = this.categories.find(x => x.id == id);
                return c ? c.name : '-';
            },
            getUnitName(id) {
                const u = this.units.find(x => x.id == id);
                return u ? u.name : '-';
            },
            getAccountCode(id) {
                const a = this.accounts.find(x => x.id == id);
                return a ? a.code : '-';
            },
            onCategoryChange() {
                const cat = this.categories.find(c => c.id == this.form.category_id);
                if (cat && cat.account_cash_id) {
                    this.form.cash_account_id = cat.account_cash_id;
                }
            },
            addToCart() {
                if (!this.form.unit_id) return alert('Pilih Unit!');
                if (!this.form.category_id) return alert('Pilih Kategori!');
                if (!this.form.amount || this.form.amount <= 0) return alert('Nominal tidak valid!');
                if (!this.form.cash_account_id) return alert('Pilih Sumber Dana!');
                if (!this.form.description) return alert('Isi Keterangan!');

                this.cart.push({ ...this.form });

                this.form.category_id = '';
                this.form.amount = '';
                this.form.description = '';
                this.form.invoice_number = '';
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
            },
            async saveTransaction() {
                const advanceTotals = {};
                this.cart.forEach(item => {
                    if (item.advance_id) {
                        if (!advanceTotals[item.advance_id]) {
                            advanceTotals[item.advance_id] = { total: 0, expected: item.expected_amount, requester: item.pic };
                        }
                        advanceTotals[item.advance_id].total += Number(item.amount);
                    }
                });
                
                let warningConfirmed = true;
                for (const [advId, data] of Object.entries(advanceTotals)) {
                    if (Number(data.total) !== Number(data.expected)) {
                        const diff = Number(data.total) - Number(data.expected);
                        const msg = `PERINGATAN KAS BON (${data.requester}):\n\n` +
                                    `Total Realisasi Kas Bon: Rp ${this.formatNumber(data.expected)}\n` +
                                    `Total Input Pengeluaran: Rp ${this.formatNumber(data.total)}\n` +
                                    `Selisih: Rp ${this.formatNumber(diff)}\n\n` +
                                    `Nominal pengeluaran tidak sama dengan kas bon.\n` +
                                    `Mohon perbaiki nominal agar sesuai dengan realisasi.\n` +
                                    `(Tidak bisa melanjutkan)`;
                        
                        alert(msg);
                        warningConfirmed = false;
                        break;
                    }
                }
                
                if (!warningConfirmed) return;
                
                if (!confirm('Simpan transaksi ini?')) return;

                try {
                    for (const item of this.cart) {
                        let res, data;
                        if (item.advance_id) {
                            res = await fetch(window.BASE_URL + 'api/finance.php?action=record_expense_from_advance', {
                                method: 'POST',
                                body: JSON.stringify({
                                    trans_date: this.globalDate,
                                    amount: item.amount,
                                    category_id: item.category_id,
                                    description: item.description,
                                    invoice_number: item.invoice_number || '',
                                    advance_id: item.advance_id,
                                    unit_id: item.unit_id,
                                    cash_account_id: item.cash_account_id,
                                    request_id: (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : ('exp-' + Date.now() + '-' + Math.random().toString(36).slice(2))
                                })
                            });
                        } else if (item.proposal_ref) {
                            res = await fetch(window.BASE_URL + 'api/finance.php?action=record_expense_from_proposal', {
                                method: 'POST',
                                body: JSON.stringify({
                                    trans_date: this.globalDate,
                                    amount: item.amount,
                                    category_id: item.category_id,
                                    description: item.description,
                                    unit_id: item.unit_id,
                                    cash_account_id: item.cash_account_id,
                                    pic: item.pic,
                                    receiver: item.receiver,
                                    reference_no: item.proposal_ref,
                                    request_id: (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : ('exp-' + Date.now() + '-' + Math.random().toString(36).slice(2))
                                })
                            });
                        } else {
                            res = await fetch(window.BASE_URL + 'api/finance.php?action=save_expense', {
                                method: 'POST',
                                body: JSON.stringify({
                                    date: this.globalDate,
                                    items: [item],
                                    request_id: (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : ('exp-' + Date.now() + '-' + Math.random().toString(36).slice(2))
                                })
                            });
                        }
                        
                        data = await res.json();
                        if (!data.success) {
                            throw new Error(data.message || 'Gagal menyimpan transaksi');
                        }
                    }
                    
                    alert('Transaksi Berhasil Disimpan!');
                    this.cart = [];
                    this.form = {
                        unit_id: '', category_id: '', amount: '', description: '', invoice_number: '',
                        cash_account_id: '', pic: '', receiver: '', advance_id: null
                    };
                    
                    if (this.mode === 'KASBON') this.fetchSettledAdvances();
                    if (this.mode === 'PROPOSAL') this.fetchApprovedProposals();
                     
                 } catch (e) {
                     console.error(e);
                     alert('ERROR: ' + e.message);
                 }
            }
        },
        mounted() {
            this.fetchInit();
        },
        watch: {
            mode(val) {
                if (val === 'KASBON') {
                    this.fetchSettledAdvances();
                } else if (val === 'PROPOSAL') {
                    this.fetchApprovedProposals();
                }
            }
        }
    }).mount('#app')
</script>
</body>
</html>
