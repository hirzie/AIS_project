<div v-if="currentPosition === 'wakasek'">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">
        <h3 class="font-bold text-slate-800 mb-4">Dashboard Wakasek</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-700">Laporan Bulanan</div>
                        <div class="text-xs text-slate-500">Status Pengumpulan</div>
                    </div>
                </div>
                <div v-if="wakasekWarning" class="text-sm font-bold text-red-600 flex items-center gap-1">
                    <i class="fas fa-exclamation-circle"></i> Belum Selesai
                </div>
                <div v-else class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                    <i class="fas fa-check-circle"></i> Aman
                </div>
            </div>
            
            <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-700">Kehadiran Siswa</div>
                        <div class="text-xs text-slate-500">Rata-rata Unit</div>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-800">{{ unitStats.attendancePct || 0 }}%</div>
            </div>

            <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-700">Pelanggaran</div>
                        <div class="text-xs text-slate-500">Bulan Ini</div>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-800">{{ violationsRecent ? violationsRecent.length : 0 }}</div>
            </div>
        </div>
    </div>
</div>
