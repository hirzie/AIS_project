<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative shrink-0">
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-200 hover:text-blue-600 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center font-bold">
                <i class="fas fa-file-signature"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-800 leading-tight">Proposal Disetujui</h1>
                <p class="text-xs text-slate-500 font-medium">Daftar pengajuan dana yang siap dicairkan</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="bg-amber-50 text-amber-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-amber-100">
                <i class="fas fa-info-circle mr-1"></i> {{ approvedList.length }} Proposal Siap Cair
            </div>
        </div>
    </nav>

    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <div v-if="loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-slate-300"></i>
                <p class="mt-4 text-slate-500 font-bold">Memuat data...</p>
            </div>
            <div v-else-if="approvedList.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-white rounded-full shadow-sm flex items-center justify-center mb-6 text-slate-200 border border-slate-100">
                    <i class="fas fa-check-double text-4xl"></i>
                </div>
                <h3 class="font-bold text-lg text-slate-600 mb-2">Tidak ada proposal pending</h3>
                <p class="text-sm text-slate-400 max-w-xs">Saat ini tidak ada pengajuan dana yang perlu diproses.</p>
            </div>
            <div v-else class="grid grid-cols-1 gap-4">
                <div v-for="item in approvedList" :key="item.id" class="bg-white rounded-xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold tracking-wider">#{{ item.reference_no }}</span>
                                <span v-if="item.payout_trans_number" class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold tracking-wider font-mono border border-blue-100">
                                    {{ item.payout_trans_number }}
                                </span>
                                <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase">Approved</span>
                                <span class="text-xs text-slate-400 font-medium">
                                    <i class="far fa-clock mr-1"></i> 
                                    {{ item.payout_date ? formatDateTime(item.payout_date) : formatDateTime(item.created_at) }}
                                </span>
                            </div>
                            <h3 class="font-bold text-lg text-slate-800 mb-1 group-hover:text-blue-600 transition-colors">{{ item.title }}</h3>
                            <p class="text-sm text-slate-500 mb-3 line-clamp-1">{{ item.description }} <span v-if="item.payout_trans_number" class="text-slate-400 font-mono text-xs ml-1">(Ref: {{ item.reference_no }})</span></p>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
                                    <div class="flex items-center gap-1 bg-slate-50 px-2 py-1 rounded">
                                        <i class="fas fa-user text-slate-400"></i> Pemohon: {{ item.requester }}
                                    </div>
                                </div>
                                <div v-if="item.approved_by" class="flex items-center gap-2 text-xs font-bold text-emerald-600">
                                    <div class="flex items-center gap-1 bg-emerald-50 px-2 py-1 rounded border border-emerald-100">
                                        <i class="fas fa-user-check"></i> Disetujui: {{ item.approved_by }}
                                    </div>
                                    <span v-if="item.approved_at" class="text-slate-500 font-mono ml-1">{{ formatDateTime(item.approved_at) }}</span>
                                </div>
                                <div v-if="item.payout_pic" class="text-xs font-bold text-slate-500">
                                    <span class="text-slate-400 ml-1">Petugas:</span> {{ item.payout_pic }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-3 min-w-[200px]">
                            <div class="text-right">
                                <p class="text-[10px] uppercase font-bold text-slate-400">Nominal Pengajuan</p>
                                <h4 class="text-xl font-bold text-emerald-600 font-mono">{{ formatMoney(item.amount) }}</h4>
                            </div>
                            <div v-if="item.payout_trans_number" class="w-full flex gap-2 relative z-10">
                                <div class="bg-slate-100 text-slate-500 px-4 py-2 rounded-lg text-sm font-bold border border-slate-200 text-center shadow-inner flex-1">
                                    <i class="fas fa-check-circle text-emerald-500 mr-2"></i> SUDAH DIPROSES
                                </div>
                            </div>
                            <a v-else :href="baseUrl + 'modules/finance/cash_advance.php?proposal_ref=' + item.reference_no" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-lg shadow-blue-100 transition-all flex items-center gap-2 justify-center w-full">
                                <i class="fas fa-money-bill-wave"></i> Proses via Kas Bon
                            </a>
                        </div>
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
                approvedList: [],
                loading: true,
                baseUrl: (window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/'))
            }
        },
        mounted() {
            this.fetchData();
        },
        methods: {
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(this.baseUrl + 'api/approval.php?action=get_list&status=APPROVED');
                    const data = await res.json();
                    if (data.success) {
                        this.approvedList = data.data.filter(item => Number(item.amount) > 0);
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
            formatMoney(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
            },
            formatDateTime(dateStr) {
                return new Date(dateStr).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
            }
        }
    }).mount('#app')
</script>
<?php require_once '../../includes/footer_finance.php'; ?>
    </body>
    </html>
