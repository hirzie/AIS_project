<?php
require_once '../../includes/guard.php';
require_login_and_module('foundation');
require_once '../../includes/header.php';
?>
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
    <div class="flex items-center gap-3">
        <a href="<?php echo $baseUrl; ?>modules/foundation/index.php" class="w-10 h-10 bg-teal-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-teal-200 hover:bg-teal-700 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Simpan Pinjam</h1>
            <span class="text-xs text-slate-500 font-medium">Koperasi Karyawan Yayasan</span>
        </div>
    </div>
    <button @click="openCreateLoanModal()" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-teal-700 flex items-center gap-2 shadow-lg shadow-teal-200/50">
        <i class="fas fa-plus"></i> Pengajuan Baru
    </button>
    </nav>

<main class="flex-1 overflow-y-auto p-6" id="app">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-xs font-bold uppercase mb-1">Pinjaman Aktif</div>
                <div class="text-2xl font-bold text-slate-800">{{ stats.active_loans_count }} <span class="text-sm font-normal text-slate-400">Kontrak</span></div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-xs font-bold uppercase mb-1">Total Outstanding (Piutang)</div>
                <div class="text-2xl font-bold text-orange-600">{{ formatCurrency(stats.outstanding) }}</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-xs font-bold uppercase mb-1">Total Terbayar (Lunas)</div>
                <div class="text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.total_paid) }}</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-xs font-bold uppercase mb-1">Peminjam Aktif</div>
                <div class="text-2xl font-bold text-teal-600">{{ stats.active_borrowers }} <span class="text-sm font-normal text-slate-400">Orang</span></div>
            </div>
        </div>

        <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Pengajuan Pinjaman Baru</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Peminjam</label>
                        <select v-model="form.employee_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500">
                            <option value="">Pilih Karyawan</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Pinjaman</label>
                            <select v-model="form.type" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500">
                                <option value="MONEY">UANG</option>
                                <option value="ITEM">BARANG</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Nominal (Rp)</label>
                            <input v-model="form.amount" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500 font-mono">
                        </div>
                    </div>
                    <div v-if="form.type === 'ITEM'">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nama Barang</label>
                        <input v-model="form.item_name" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Tenor (Bulan)</label>
                            <input v-model="form.tenor_months" type="number" min="1" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Bunga (%)</label>
                            <input v-model="form.interest_rate" type="number" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Keterangan</label>
                        <textarea v-model="form.description" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-teal-500 h-20"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showCreateModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Batal</button>
                    <button @click="submitLoan" :disabled="loading" class="bg-teal-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-teal-700 shadow-lg shadow-teal-200 disabled:opacity-50">Simpan</button>
                </div>
            </div>
        </div>

        <div class="flex gap-4 border-b border-slate-200">
            <button @click="currentView = 'loans'" :class="currentView === 'loans' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 font-bold transition-colors">Data Pinjaman</button>
            <button @click="currentView = 'payments'" :class="currentView === 'payments' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 font-bold transition-colors">Jadwal Pembayaran</button>
            <button @click="currentView = 'report'" :class="currentView === 'report' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 font-bold transition-colors">Laporan Keuangan</button>
        </div>

        <div v-if="currentView === 'loans'" class="space-y-6">
            <div class="flex gap-4">
                <button @click="filterStatus = ''" :class="filterStatus === '' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors">Semua</button>
                <button @click="filterStatus = 'PENDING'" :class="filterStatus === 'PENDING' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors">Pending</button>
                <button @click="filterStatus = 'ACTIVE'" :class="filterStatus === 'ACTIVE' ? 'bg-teal-600 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors">Aktif</button>
                <button @click="filterStatus = 'PAID'" :class="filterStatus === 'PAID' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors">Lunas</button>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Tanggal / No. Pinjaman</th>
                            <th class="px-6 py-4">Peminjam</th>
                            <th class="px-6 py-4">Tipe / Item</th>
                            <th class="px-6 py-4 text-right">Nominal Pinjaman</th>
                            <th class="px-6 py-4 text-center">Tenor</th>
                            <th class="px-6 py-4 text-right">Cicilan/Bulan</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="filteredLoans.length === 0">
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 italic">Belum ada data pinjaman.</td>
                        </tr>
                        <tr v-for="loan in filteredLoans" :key="loan.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ formatDate(loan.request_date) }}</div>
                                <div class="text-xs font-mono text-slate-400">{{ loan.loan_number }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">{{ loan.employee_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-[10px] font-bold border mr-2" :class="loan.type === 'MONEY' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-blue-50 text-blue-600 border-blue-200'">{{ loan.type === 'MONEY' ? 'UANG' : 'BARANG' }}</span>
                                <span v-if="loan.type === 'ITEM'" class="text-slate-600">{{ loan.item_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-700">{{ formatCurrency(loan.amount) }}</td>
                            <td class="px-6 py-4 text-center">{{ loan.tenor_months }} Bln</td>
                            <td class="px-6 py-4 text-right font-mono text-slate-600">{{ formatCurrency(loan.monthly_installment) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold" :class="getStatusClass(loan.status)">{{ loan.status }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="viewDetail(loan)" class="text-slate-400 hover:text-teal-600 transition-colors"><i class="fas fa-eye"></i> Detail</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="currentView === 'report'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-indigo-600 text-white p-6 rounded-xl shadow-lg shadow-indigo-200">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-indigo-200 text-xs font-bold uppercase mb-1">Total Modal Disetor</p>
                            <h3 class="text-3xl font-bold">{{ formatCurrency(finance.capital) }}</h3>
                        </div>
                        <div class="bg-indigo-500/50 p-3 rounded-lg">
                            <i class="fas fa-landmark text-2xl"></i>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="openCapitalModal('INJECTION')" class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded text-xs font-bold flex items-center gap-1"><i class="fas fa-plus"></i> Tambah Modal</button>
                        <button @click="openCapitalModal('WITHDRAWAL')" class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded text-xs font-bold flex items-center gap-1"><i class="fas fa-minus"></i> Tarik Modal</button>
                    </div>
                </div>
                <div class="bg-emerald-600 text-white p-6 rounded-xl shadow-lg shadow-emerald-200">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-emerald-200 text-xs font-bold uppercase mb-1">Saldo Kas Tersedia</p>
                            <h3 class="text-3xl font-bold">{{ formatCurrency(finance.saldo) }}</h3>
                        </div>
                        <div class="bg-emerald-500/50 p-3 rounded-lg">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-emerald-100 text-xs">Total Masuk: {{ formatCurrency(Number(finance.capital) + Number(finance.repaid)) }}<br>Total Keluar: {{ formatCurrency(Number(finance.disbursed) + Number(finance.expenses)) }}</p>
                </div>
                <div class="bg-orange-600 text-white p-6 rounded-xl shadow-lg shadow-orange-200">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-orange-200 text-xs font-bold uppercase mb-1">Aset Koperasi</p>
                            <h3 class="text-2xl font-bold">{{ formatCurrency(Number(finance.outstanding) + Number(finance.expenses)) }}</h3>
                        </div>
                        <div class="bg-orange-500/50 p-3 rounded-lg">
                            <i class="fas fa-chart-pie text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-orange-100 text-xs">Piutang: {{ formatCurrency(finance.outstanding) }}<br>Stok/Barang: {{ formatCurrency(finance.expenses) }}</p>
                    <button @click="showExpenseModal = true" class="mt-3 bg-white/20 hover:bg-white/30 px-3 py-1 rounded text-xs font-bold flex items-center gap-1 w-full justify-center"><i class="fas fa-shopping-cart"></i> Catat Pembelian Barang</button>
                </div>
            </div>
            <div class="flex justify-end">
                <button @click="submitToFoundation" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 flex items-center gap-2 transform transition-all active:scale-95"><i class="fas fa-paper-plane"></i> Kirim Laporan ke Yayasan</button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700">Riwayat Transaksi (Modal & Belanja)</h3>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="h in finance.history" :key="h.trans_date + h.type + h.amount">
                                <td class="px-4 py-3 font-mono text-xs">{{ formatDate(h.trans_date) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold whitespace-nowrap" :class="getHistoryBadgeClass(h.type)">{{ getHistoryLabel(h.type) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold whitespace-nowrap" :class="getHistoryAmountClass(h.type)">{{ (h.type === 'INJECTION' || h.type === 'REPAYMENT') ? '+' : '-' }} {{ formatCurrency(h.amount) }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs truncate max-w-[200px]" :title="h.description">{{ h.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200 h-fit">
                    <h3 class="font-bold text-slate-800 mb-4">Informasi Keuangan</h3>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><i class="fas fa-info-circle text-teal-500 mt-1"></i><div><span class="font-bold text-slate-700">Saldo Kas</span> adalah uang tunai yang tersedia saat ini untuk dipinjamkan.</div></li>
                        <li class="flex gap-3"><i class="fas fa-info-circle text-teal-500 mt-1"></i><div><span class="font-bold text-slate-700">Total Modal</span> adalah akumulasi dana yang disuntikkan oleh yayasan/koperasi.</div></li>
                        <li class="flex gap-3"><i class="fas fa-info-circle text-teal-500 mt-1"></i><div><span class="font-bold text-slate-700">Total Terpinjam</span> termasuk bunga yang diharapkan.</div></li>
                    </ul>
                </div>
            </div>
        </div>

        <div v-if="currentView === 'payments'" class="space-y-6">
            <div class="flex gap-4 bg-white p-4 rounded-xl border border-slate-200 items-center">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Bulan</label>
                    <select v-model="paymentFilter.month" @change="fetchDueInstallments" class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-teal-500">
                        <option v-for="m in 12" :value="m">{{ new Date(0, m-1).toLocaleString('id-ID', {month:'long'}) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tahun</label>
                    <input v-model="paymentFilter.year" @change="fetchDueInstallments" type="number" class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Status</label>
                    <select v-model="paymentFilter.status" @change="fetchDueInstallments" class="border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-teal-500">
                        <option value="UNPAID">Belum Bayar</option>
                        <option value="PAID">Sudah Bayar</option>
                    </select>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Peminjam</th>
                            <th class="px-6 py-4 text-center">Ke</th>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4 text-right">Nominal</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="ins in dueInstallments" :key="ins.id">
                            <td class="px-6 py-4">{{ ins.employee_name }}</td>
                            <td class="px-6 py-4 text-center">{{ ins.installment_number }}</td>
                            <td class="px-6 py-4">{{ formatDate(ins.due_date) }}</td>
                            <td class="px-6 py-4 text-right font-mono">{{ formatCurrency(ins.amount) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold" :class="ins.status === 'PAID' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">{{ ins.status }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button v-if="ins.status !== 'PAID'" @click="payInstallment(ins)" class="text-slate-400 hover:text-teal-600 transition-colors"><i class="fas fa-cash-register"></i> Bayar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showCapitalModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showCapitalModal = false">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-4">{{ capitalForm.type === 'INJECTION' ? 'Tambah Modal (Setor)' : 'Tarik Modal' }}</h3>
                <div class="space-y-4">
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label><input v-model="capitalForm.date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Nominal (Rp)</label><input v-model="capitalForm.amount" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-indigo-500 font-mono text-lg"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Keterangan</label><textarea v-model="capitalForm.description" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-indigo-500 h-20"></textarea></div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showCapitalModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Batal</button>
                    <button @click="submitCapital" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200">Simpan</button>
                </div>
            </div>
        </div>

        <div v-if="showExpenseModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showExpenseModal = false">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Catat Pembelian Barang</h3>
                <div class="space-y-4">
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label><input v-model="expenseForm.date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-orange-500"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Nama Barang</label><input v-model="expenseForm.item_name" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-orange-500"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Total Harga (Rp)</label><input v-model="expenseForm.amount" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-orange-500 font-mono text-lg"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Keterangan</label><textarea v-model="expenseForm.description" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-orange-500 h-20"></textarea></div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showExpenseModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Batal</button>
                    <button @click="submitExpense" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-orange-700 shadow-lg shadow-orange-200">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const { createApp } = Vue;
let baseUrl = window.BASE_URL || '/';
if (baseUrl === '/' || !baseUrl) {
    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
    baseUrl = m ? `/${m[1]}/` : '/';
}
createApp({
    data() {
        return {
            currentView: 'loans',
            loans: [],
            employees: [],
            dueInstallments: [],
            filterStatus: '',
            paymentFilter: { month: new Date().getMonth() + 1, year: new Date().getFullYear(), status: 'UNPAID' },
            stats: { outstanding: 0, total_paid: 0, active_borrowers: 0, active_loans_count: 0 },
            finance: { capital: 0, disbursed: 0, repaid: 0, outstanding: 0, saldo: 0, expenses: 0, history: [] },
            showCapitalModal: false,
            capitalForm: { type: 'INJECTION', amount: 0, date: new Date().toISOString().split('T')[0], description: '' },
            showExpenseModal: false,
            expenseForm: { item_name: '', amount: 0, date: new Date().toISOString().split('T')[0], description: '' },
            loading: false,
            showCreateModal: false,
            form: { employee_id: '', type: 'MONEY', item_name: '', amount: 0, tenor_months: 1, interest_rate: 0, description: '' },
            showDetailModal: false,
            selectedLoan: {},
            installments: []
        }
    },
    computed: {
        filteredLoans() {
            if (!this.filterStatus) return this.loans;
            return this.loans.filter(l => l.status === this.filterStatus);
        }
    },
    mounted() {
        this.fetchLoans();
        this.fetchEmployees();
        this.fetchDueInstallments();
        this.fetchStats();
        this.fetchFinance();
    },
    methods: {
        openCreateLoanModal() {
            this.form = { employee_id: '', type: 'MONEY', item_name: '', amount: 0, tenor_months: 1, interest_rate: 0, description: '' };
            this.showCreateModal = true;
        },
        async fetchFinance() {
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=get_finance_report');
                const data = await res.json();
                if (data.success) this.finance = data.data;
            } catch (e) {}
        },
        async fetchStats() {
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=get_stats');
                const data = await res.json();
                if (data.success) this.stats = data.data;
            } catch (e) {}
        },
        openCapitalModal(type) {
            this.capitalForm = { type, amount: 0, date: new Date().toISOString().split('T')[0], description: '' };
            this.showCapitalModal = true;
        },
        async submitCapital() {
            if (!this.capitalForm.amount) return alert('Nominal harus diisi');
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=add_capital', { method: 'POST', body: JSON.stringify(this.capitalForm) });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.showCapitalModal = false;
                    this.fetchFinance();
                }
            } catch (e) { alert('Error'); }
        },
        async submitExpense() {
            if (!this.expenseForm.item_name || !this.expenseForm.amount) return alert('Data tidak lengkap');
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=add_expense', { method: 'POST', body: JSON.stringify(this.expenseForm) });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.showExpenseModal = false;
                    this.expenseForm = { item_name: '', amount: 0, date: new Date().toISOString().split('T')[0], description: '' };
                    this.fetchFinance();
                }
            } catch (e) { alert('Error'); }
        },
        async submitToFoundation() {
            if (!confirm('Apakah Anda yakin ingin mengirimkan laporan posisi keuangan Koperasi/Simpan Pinjam ini ke Dashboard Yayasan? Data akan dikonsolidasikan.')) return;
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=submit_report_to_foundation', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    alert('Laporan berhasil dikirim ke Yayasan.');
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (e) { alert('Terjadi kesalahan koneksi.'); }
        },
        async fetchLoans() {
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=get_loans');
                const data = await res.json();
                if (data.success) this.loans = data.data;
            } catch (e) {}
        },
        async fetchDueInstallments() {
            try {
                const params = new URLSearchParams({ action: 'get_installments', month: this.paymentFilter.month, year: this.paymentFilter.year, status: this.paymentFilter.status });
                const res = await fetch(baseUrl + 'api/foundation_loans.php?' + params);
                const data = await res.json();
                if (data.success) this.dueInstallments = data.data;
            } catch (e) {}
        },
        async fetchEmployees() {
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=get_employees');
                const data = await res.json();
                if (data.success) this.employees = data.data;
            } catch (e) {}
        },
        async submitLoan() {
            if (!this.form.employee_id || !this.form.amount) return alert('Data tidak lengkap');
            this.loading = true;
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=create_loan', { method: 'POST', body: JSON.stringify(this.form) });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.showCreateModal = false;
                    this.fetchLoans();
                    this.form = { employee_id: '', type: 'MONEY', item_name: '', amount: 0, tenor_months: 1, interest_rate: 0, description: '' };
                } else {
                    alert(data.message);
                }
            } catch (e) { alert('Error submitting loan'); }
            finally { this.loading = false; }
        },
        async viewDetail(loan) {
            this.selectedLoan = loan;
            this.showDetailModal = true;
            this.installments = [];
            if (loan.status === 'ACTIVE' || loan.status === 'PAID') {
                try {
                    const res = await fetch(baseUrl + `api/foundation_loans.php?action=get_installments&loan_id=${loan.id}`);
                    const data = await res.json();
                    if (data.success) this.installments = data.data;
                } catch (e) {}
            }
        },
        async approveLoan(loan) {
            if (!confirm('Setujui pinjaman ini? Jadwal cicilan akan dibuat otomatis.')) return;
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=approve_loan', { method: 'POST', body: JSON.stringify({ id: loan.id, start_date: new Date().toISOString().split('T')[0] }) });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.showDetailModal = false;
                    this.fetchLoans();
                }
            } catch (e) { alert('Error'); }
        },
        async rejectLoan(loan) {
            const reason = prompt('Alasan penolakan:');
            if (reason === null) return;
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=reject_loan', { method: 'POST', body: JSON.stringify({ id: loan.id, reason }) });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.showDetailModal = false;
                    this.fetchLoans();
                }
            } catch (e) { alert('Error'); }
        },
        async payInstallment(ins) {
            if (!confirm(`Konfirmasi pembayaran cicilan ke-${ins.installment_number} sebesar ${this.formatCurrency(ins.amount)}?`)) return;
            try {
                const res = await fetch(baseUrl + 'api/foundation_loans.php?action=pay_installment', { method: 'POST', body: JSON.stringify({ id: ins.id }) });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    if (this.currentView === 'payments') {
                        this.fetchDueInstallments();
                    } else {
                        this.viewDetail(this.selectedLoan);
                    }
                    this.fetchLoans();
                }
            } catch (e) { alert('Error'); }
        },
        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
        },
        formatDate(str) {
            if (!str) return '-';
            return new Date(str).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        getStatusClass(status) {
            const classes = { 'PENDING': 'bg-amber-100 text-amber-600', 'APPROVED': 'bg-blue-100 text-blue-600', 'ACTIVE': 'bg-teal-100 text-teal-600', 'PAID': 'bg-emerald-100 text-emerald-600', 'REJECTED': 'bg-red-100 text-red-600', 'CANCELLED': 'bg-slate-100 text-slate-600' };
            return classes[status] || 'bg-slate-100';
        },
        getHistoryBadgeClass(type) {
            if (type === 'INJECTION') return 'bg-indigo-50 text-indigo-600';
            if (type === 'WITHDRAWAL') return 'bg-red-50 text-red-600';
            if (type === 'EXPENSE') return 'bg-orange-50 text-orange-600';
            if (type === 'DISBURSEMENT') return 'bg-blue-50 text-blue-600';
            if (type === 'REPAYMENT') return 'bg-emerald-50 text-emerald-600';
            return 'bg-slate-100 text-slate-600';
        },
        getHistoryLabel(type) {
            if (type === 'INJECTION') return 'Setoran Modal';
            if (type === 'WITHDRAWAL') return 'Tarik Modal';
            if (type === 'EXPENSE') return 'Pembelian Barang';
            if (type === 'DISBURSEMENT') return 'Pinjaman Keluar';
            if (type === 'REPAYMENT') return 'Cicilan Masuk';
            return type;
        },
        getHistoryAmountClass(type) {
            if (type === 'INJECTION' || type === 'REPAYMENT') return 'text-emerald-600';
            if (type === 'WITHDRAWAL' || type === 'EXPENSE') return 'text-red-600';
            return 'text-slate-600';
        }
    }
}).mount('#app');
</script>
</body>
</html>
