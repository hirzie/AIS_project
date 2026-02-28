    <div v-if="resolveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 bg-emerald-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm">Selesaikan Internal</h3>
                <button @click="resolveModal=false" class="text-slate-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-3">
                <div class="text-xs text-slate-500">Berikan catatan peringatan untuk siswa terkait.</div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Peringatan</label>
                    <textarea v-model="resolveNote" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
                <button @click="resolveModal=false" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
                <button @click="submitResolveInternal" class="ml-2 px-3 py-1 text-[12px] font-bold bg-emerald-600 text-white rounded">Tandai Selesai</button>
            </div>
        </div>
    </div>
    <div v-if="escalateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 bg-red-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm">Eskalasi ke BK</h3>
                <button @click="escalateModal=false" class="text-slate-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-3">
                <div class="text-xs text-slate-500">Berikan catatan pengantar untuk BK.</div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Pengantar</label>
                    <textarea v-model="escalateNote" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
                <button @click="escalateModal=false" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
                <button @click="submitEscalateBK" class="ml-2 px-3 py-1 text-[12px] font-bold bg-red-600 text-white rounded">Eskalasi</button>
            </div>
        </div>
    </div>
    <?php include '../../includes/modals/student_modal.php'; ?>

    <!-- Attendance Validation Modal -->
    <div v-if="attendanceValidationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Validasi Presensi Bulanan</h3>
                    <p class="text-sm text-slate-500">
                        Kelas: {{ className }} | Bulan: {{ attendanceBatchMonth }}/{{ attendanceBatchYear }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-blue-50 border border-blue-100 rounded-xl px-3 py-1.5 shadow-sm">
                        <span class="text-[10px] font-bold text-blue-400 uppercase mr-2">Hari Aktif</span>
                        <input v-model.number="attendanceBatchActiveDays" type="number" @input="recalculateAllBatch" class="bg-transparent border-none text-sm font-bold text-blue-700 focus:ring-0 outline-none w-12 text-center" placeholder="22">
                    </div>
                    <button @click="attendanceValidationModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-auto p-0 bg-slate-50">
                <div v-if="attendanceBatchLoading" class="flex items-center justify-center h-64">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse bg-white">
                        <thead class="sticky top-0 z-10 bg-slate-50 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-12 border-b border-slate-200">No</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-24 border-b border-slate-200">NIS</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-200">Nama Siswa</th>
                                <th class="px-2 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50 border-b border-slate-200">Izin</th>
                                <th class="px-2 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50 border-b border-slate-200">Sakit</th>
                                <th class="px-2 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50 border-b border-slate-200">Alfa</th>
                                <th class="px-2 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50 border-b border-slate-200">Cuti</th>
                                <th class="px-2 py-3 text-[10px] font-bold text-blue-600 uppercase tracking-widest text-center w-20 border-l border-slate-100 border-b border-slate-200">Hadir</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-200">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(s, idx) in attendanceBatchStudents" :key="s.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2 text-xs text-center text-slate-400 font-mono border-r border-slate-100">{{ idx + 1 }}</td>
                                <td class="px-4 py-2 text-xs text-slate-500 font-mono border-r border-slate-100">{{ s.nis }}</td>
                                <td class="px-4 py-2 text-sm font-bold text-slate-700">{{ s.name }}</td>
                                
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.izin" @input="recalculateBatch(s)" type="number" class="w-full text-center py-1 bg-white border border-slate-200 rounded text-sm font-bold text-slate-700 focus:border-blue-500 focus:ring-0" placeholder="0">
                                </td>
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.sakit" @input="recalculateBatch(s)" type="number" class="w-full text-center py-1 bg-white border border-slate-200 rounded text-sm font-bold text-slate-700 focus:border-blue-500 focus:ring-0" placeholder="0">
                                </td>
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.alfa" @input="recalculateBatch(s)" type="number" class="w-full text-center py-1 bg-white border border-slate-200 rounded text-sm font-bold text-slate-700 focus:border-blue-500 focus:ring-0" placeholder="0">
                                </td>
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.cuti" @input="recalculateBatch(s)" type="number" class="w-full text-center py-1 bg-white border border-slate-200 rounded text-sm font-bold text-slate-700 focus:border-blue-500 focus:ring-0" placeholder="0">
                                </td>
                                
                                <td class="px-2 py-2 border-l border-slate-100">
                                    <div class="w-full text-center py-1 bg-blue-50 text-blue-700 font-bold rounded text-sm">
                                        {{ s.hadir }}
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <input v-model="s.remarks" type="text" class="w-full px-2 py-1 border border-slate-100 rounded text-xs text-slate-500 italic focus:border-blue-500 focus:ring-0" placeholder="...">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="p-4 border-t border-slate-100 bg-white flex justify-end gap-3">
                <button @click="attendanceValidationModal = false" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">
                    Batal
                </button>
                <button @click="saveBatchAttendance" :disabled="attendanceBatchSaving || attendanceBatchLoading" class="px-6 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-lg shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                    <i v-if="attendanceBatchSaving" class="fas fa-spinner fa-spin"></i>
                    <span>{{ attendanceBatchSaving ? 'Menyimpan...' : 'Simpan & Validasi' }}</span>
                </button>
            </div>
        </div>
    </div>
    <!-- Unlock Modal -->
    <div v-if="showClassUnlockModal && selectedLockClass" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" v-cloak>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 bg-indigo-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm">Buka Kunci Walikelas</h3>
                <button @click="showClassUnlockModal=false" class="text-slate-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-4">
                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 flex items-start gap-3">
                    <i class="fas fa-info-circle text-indigo-500 mt-0.5"></i>
                    <div class="text-xs text-indigo-700">
                        Anda akan membuka kunci akses untuk kelas <b>{{ selectedLockClass ? selectedLockClass.name : '-' }}</b>.
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kunci yang Dibuka</label>
                    <select v-model="unlockType" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700">
                        <option value="ALL">Semua (Buka Total)</option>
                        <option value="ATTENDANCE">Absensi (M+5)</option>
                        <option value="FACILITY">Fasilitas (Sabtu)</option>
                        <option value="COMPLAINT">Aduan (Komplain)</option>
                    </select>
                    <p class="text-[10px] text-slate-500 mt-1">Pilih jenis kunci spesifik atau buka semua.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Durasi Buka (Jam)</label>
                    <input type="number" v-model.number="unlockDuration" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="24">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Pembukaan</label>
                    <textarea v-model="unlockReason" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Contoh: Sudah konfirmasi lisan..."></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
                <button @click="showUnlockModal=false" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Batal</button>
                <button @click="submitUnlock" class="ml-2 px-3 py-1 text-[12px] font-bold bg-indigo-600 text-white rounded hover:bg-indigo-700">Buka Kunci</button>
            </div>
        </div>
    </div>
