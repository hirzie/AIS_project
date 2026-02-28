<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="glass-header shadow-lg z-50 shrink-0 h-16 flex items-center justify-between px-6">
        <div class="flex items-center gap-4">
            <a href="<?php echo $baseUrl; ?>modules/executive/index.php" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center text-white hover:bg-white/20 transition-all">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-lg border border-white/20">
                <i class="fas fa-stamp"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-white leading-tight">Approval Center</h1>
                <p class="text-xs text-slate-400 font-medium">Pusat Persetujuan & Validasi</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button @click="openModal" class="hidden md:flex bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-lg shadow-emerald-500/30 transition-all items-center gap-2">
                <i class="fas fa-plus"></i> Tambah Pengajuan
            </button>
            <div class="hidden md:flex text-right mr-4 border-l border-white/10 pl-4">
                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Pending</p>
                <p class="text-xl font-bold text-white font-mono">{{ pendingCount }}</p>
            </div>
            <div class="w-9 h-9 bg-amber-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg border-2 border-slate-800">M</div>
        </div>
    </nav>
    <main class="flex-1 flex overflow-hidden">
        <aside class="w-full md:w-96 bg-white border-r border-slate-200 flex flex-col z-10">
            <div class="flex border-b border-slate-100">
                <button @click="filterStatus = 'PENDING'" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors border-b-2" :class="filterStatus === 'PENDING' ? 'border-indigo-600 text-indigo-600 bg-indigo-50' : 'border-transparent'">Pending</button>
                <button @click="filterStatus = 'APPROVED'" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors border-b-2" :class="filterStatus === 'APPROVED' ? 'border-emerald-600 text-emerald-600 bg-emerald-50' : 'border-transparent'">Disetujui</button>
                <button @click="filterStatus = 'REJECTED'" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors border-b-2" :class="filterStatus === 'REJECTED' ? 'border-red-600 text-red-600 bg-red-50' : 'border-transparent'">Ditolak</button>
            </div>
            <div class="p-4 border-b border-slate-100">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                    <input v-model="searchQuery" type="text" placeholder="Cari nomor ref, judul..." class="w-full pl-9 pr-4 py-2 bg-slate-100 border-transparent focus:bg-white focus:border-indigo-500 rounded-lg text-sm transition-all outline-none border">
                </div>
            </div>
            <div class="flex-1 overflow-y-auto bg-slate-50">
                <div v-if="filteredList.length === 0" class="p-8 text-center text-slate-400 italic text-sm">Tidak ada data.</div>
                <div v-for="item in filteredList" :key="item.id" @click="selectItem(item)" class="p-4 border-b border-slate-100 cursor-pointer hover:bg-white transition-colors relative group" :class="selectedItem && selectedItem.id === item.id ? 'bg-white border-l-4 border-l-indigo-600 shadow-sm' : 'border-l-4 border-l-transparent'">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" :class="getModuleColor(item.module)">{{ item.module }}</span>
                        <span class="text-[10px] text-slate-400">{{ formatDate(item.created_at) }}</span>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1 leading-tight">{{ item.title }}</h4>
                    <p class="text-xs text-slate-500 truncate">{{ item.reference_no }} • {{ item.requester }}</p>
                    <div v-if="item.status !== 'PENDING'" class="mt-2">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="item.status === 'APPROVED' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'">{{ item.status }}</span>
                        <div v-if="item.status === 'APPROVED' && item.approved_by" class="text-[10px] text-slate-400 mt-1">Disetujui oleh {{ item.approved_by }}</div>
                    </div>
                </div>
            </div>
        </aside>
        <main class="flex-1 bg-slate-50/50 relative overflow-hidden flex flex-col">
            <div v-if="selectedItem" class="flex-1 flex flex-col h-full overflow-y-auto p-8">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 max-w-3xl mx-auto w-full overflow-hidden">
                    <div class="p-6 md:p-8 border-b border-slate-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                            <i class="fas fa-file-signature text-8xl text-slate-800"></i>
                        </div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-xs font-bold px-2 py-1 rounded bg-slate-100 text-slate-600 tracking-wider">#{{ selectedItem.reference_no }}</span>
                            <span class="text-xs font-bold px-2 py-1 rounded tracking-wider" :class="getModuleColor(selectedItem.module)">{{ selectedItem.module }}</span>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">{{ selectedItem.title }}</h2>
                        <div class="flex items-center gap-4 text-sm text-slate-500 mt-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="fas fa-user"></i></div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-400">Diajukan Oleh</p>
                                    <p class="font-bold text-slate-700">{{ selectedItem.requester }}</p>
                                </div>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-400">Tanggal Pengajuan</p>
                                <p class="font-bold text-slate-700">{{ formatDateTime(selectedItem.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-6">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-2">Deskripsi / Keterangan</h3>
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-slate-700 leading-relaxed text-sm">{{ selectedItem.description }}</div>
                        </div>
                        <div v-if="selectedItem.meeting_id">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-2">Rapat Terkait</h3>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 rounded bg-indigo-100 text-indigo-700 text-[12px] font-bold">{{ selectedItem.meeting_title || ('MEETING #' + selectedItem.meeting_id) }}</span>
                                <button @click="openRelatedMeeting(selectedItem)" class="px-3 py-1 rounded border border-slate-200 text-slate-600 text-[12px] font-bold hover:bg-slate-50">Lihat Detail Rapat</button>
                            </div>
                        </div>
                        <div v-if="Number(selectedItem.amount) > 0">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-2">Nominal Pengajuan</h3>
                            <div class="flex items-center gap-3">
                                <span class="text-3xl font-bold text-indigo-600 font-mono tracking-tight">{{ formatCurrency(selectedItem.amount) }}</span>
                            </div>
                        </div>
                        <div v-if="selectedItem.attachment">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-2">Lampiran Dokumen</h3>
                            <div class="flex gap-3">
                                <a :href="selectedItem.attachment" target="_blank" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer w-fit group">
                                    <i class="fas fa-external-link-alt text-blue-500 text-xl group-hover:scale-110 transition-transform"></i>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">Buka Tautan Dokumen</p>
                                        <p class="text-xs text-slate-400 truncate max-w-[200px]">{{ selectedItem.attachment }}</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div v-else>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-2">Lampiran Dokumen</h3>
                            <p class="text-sm text-slate-400 italic">Tidak ada lampiran.</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-end gap-3" v-if="selectedItem.status === 'PENDING'">
                        <button @click="deleteItem(selectedItem)" class="mr-auto px-4 py-2.5 rounded-lg border border-slate-200 text-slate-400 font-bold hover:text-red-600 hover:bg-red-50 transition-all flex items-center gap-2 shrink-0">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                        <button @click="editItem(selectedItem)" class="px-6 py-2.5 rounded-lg border border-indigo-200 text-indigo-600 font-bold hover:bg-indigo-50 transition-all flex items-center gap-2 shrink-0">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button @click="updateStatus(selectedItem, 'REJECTED')" class="px-6 py-2.5 rounded-lg border border-red-200 text-red-600 font-bold hover:bg-red-50 transition-all flex items-center gap-2 shrink-0">
                            <i class="fas fa-times"></i> Tolak
                        </button>
                        <button @click="updateStatus(selectedItem, 'APPROVED')" class="px-6 py-2.5 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition-all flex items-center gap-2 shrink-0">
                            <i class="fas fa-check"></i> Setujui Pengajuan
                        </button>
                    </div>
                    <div class="p-6 md:p-8 bg-slate-50 border-t border-slate-200 text-center" v-else>
                        <div v-if="selectedItem.status === 'APPROVED'" class="text-emerald-600 font-bold flex flex-col items-center justify-center gap-2 mb-2">
                            <div><i class="fas fa-check-circle text-xl"></i> Pengajuan ini telah disetujui</div>
                            <div v-if="selectedItem.approved_by" class="mt-2 bg-emerald-50 border border-emerald-100 px-4 py-3 rounded-lg flex items-center gap-3 text-emerald-700">
                                <div class="bg-emerald-100 text-emerald-600 w-10 h-10 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-user-check text-lg"></i></div>
                                <div class="text-left">
                                    <div class="text-[10px] uppercase font-bold text-slate-400">Disetujui Oleh</div>
                                    <div class="text-sm font-bold">{{ selectedItem.approved_by }}</div>
                                    <div v-if="selectedItem.approved_at" class="text-xs text-slate-500 font-mono mt-0.5">{{ formatDateTime(selectedItem.approved_at) }}</div>
                                </div>
                            </div>
                            <div v-if="selectedItem.payout_trans_number" class="mt-2 bg-blue-50 border border-blue-100 px-4 py-3 rounded-lg flex items-center gap-3">
                                <div class="bg-blue-100 text-blue-600 w-10 h-10 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-money-check-alt text-lg"></i></div>
                                <div class="text-left">
                                    <div class="text-[10px] uppercase font-bold text-slate-400">Status Pencairan</div>
                                    <div class="text-sm font-bold text-blue-800">SUDAH DICAIRKAN</div>
                                    <div class="text-xs text-slate-500 font-mono mt-0.5">Ref: {{ selectedItem.payout_trans_number }} • {{ formatDate(selectedItem.payout_date) }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="selectedItem.status === 'APPROVED' && Number(selectedItem.amount) > 0 && !selectedItem.payout_trans_number" class="mb-2">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-600 rounded-lg border border-amber-200 font-bold text-sm"><i class="fas fa-clock"></i> Menunggu Proses Pencairan (Finance)</div>
                        </div>
                        <div v-if="selectedItem.status === 'REJECTED'" class="text-red-600 font-bold flex items-center justify-center gap-2 mb-2"><i class="fas fa-times-circle text-xl"></i> Pengajuan ini telah ditolak</div>
                        <div class="mt-4">
                            <button @click="updateStatus(selectedItem, 'PENDING')" class="px-6 py-2.5 rounded-lg border border-amber-200 text-amber-600 font-bold hover:bg-amber-50 transition-all flex items-center gap-2"><i class="fas fa-undo"></i> Kembalikan ke Pending (Demo)</button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-400">
                <div class="w-24 h-24 bg-white rounded-full shadow-sm flex items-center justify-center mb-6 text-slate-200 border border-slate-100"><i class="fas fa-inbox text-4xl"></i></div>
                <h3 class="font-bold text-lg text-slate-600 mb-2">Pilih Item</h3>
                <p class="text-sm max-w-xs text-center">Pilih salah satu item pengajuan di sebelah kiri untuk melihat detail dan mengambil tindakan.</p>
            </div>
        </main>
    </main>
    <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">{{ form.id ? 'Edit Pengajuan' : 'Buat Pengajuan' }}</h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Judul</label>
                    <input v-model="form.title" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Rapat Terkait</label>
                    <div class="w-full border border-slate-300 rounded-lg px-2 py-2 text-sm bg-white">
                        <div class="flex items-center gap-2 mb-2">
                            <span v-if="form.meeting_id" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold">{{ form.meeting_title || ('MEETING #' + form.meeting_id) }}</span>
                            <button v-if="form.meeting_id" @click="clearSelectedMeeting" class="text-indigo-700 hover:text-indigo-900 text-[11px] font-bold">Hapus</button>
                        </div>
                        <input v-model="meetingQuery" @input="updateMeetingSuggestions" placeholder="Cari rapat (judul atau nomor)..."
                               class="w-full outline-none">
                    </div>
                    <div v-if="meetingSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm max-h-40 overflow-y-auto">
                        <div v-for="m in meetingSuggestions" :key="m.id" @click="selectMeeting(m)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                            {{ m.title }} • {{ m.meeting_number }}
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Modul</label>
                        <select v-model="form.module" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                            <option>UMUM</option>
                            <option>KEUANGAN</option>
                            <option>HRD</option>
                            <option>SARPRAS</option>
                            <option>PROJECT</option>
                            <option>AKADEMIK</option>
                            <option>BOARDING</option>
                            <option>LIBRARY</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Nominal</label>
                        <input v-model="form.amount" type="number" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Pemohon</label>
                        <div class="w-full border border-slate-300 rounded-lg px-2 py-2 text-sm bg-white">
                            <div class="flex items-center gap-2 mb-2">
                                <span v-if="form.requester" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">{{ form.requester }}</span>
                            </div>
                            <input v-model="requesterQuery" @input="updateRequesterSuggestions" @focus="ensureStaffLoaded"
                                   placeholder="Cari nama staf (misal: fahm → Fahmi Hirzi)" class="w-full outline-none">
                        </div>
                        <div v-if="requesterSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm max-h-40 overflow-y-auto">
                            <div v-for="s in requesterSuggestions" :key="s.id || s.name" @click="setRequester(s.name)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                                {{ s.name }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Lampiran (URL)</label>
                        <input v-model="form.attachment" type="url" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" placeholder="https://...">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Deskripsi</label>
                    <textarea v-model="form.description" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="showModal = false" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="submitApproval" :disabled="isSubmitting" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200">{{ isSubmitting ? 'Menyimpan...' : (form.id ? 'Simpan Perubahan' : 'Buat Pengajuan') }}</button>
            </div>
        </div>
    </div>
    <div v-if="showMeetingDetail" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showMeetingDetail = false"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-6 relative z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Detail Rapat</h3>
                <button @click="showMeetingDetail = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div v-if="meetingDetail" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[11px] text-slate-500 font-bold">Judul</div>
                        <div class="text-sm font-bold text-slate-800">{{ meetingDetail.title }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-slate-500 font-bold">Tanggal</div>
                        <div class="text-[12px] text-slate-600">{{ formatDate(meetingDetail.meeting_date) }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[11px] text-slate-500 font-bold">Nomor Rapat</div>
                        <div class="text-[12px] font-mono text-slate-700">{{ meetingDetail.meeting_number }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-slate-500 font-bold">Divisi</div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ meetingDetail.module_tag }}</span>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-bold">Peserta</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <span v-for="p in (meetingDetail.attendees || [])" :key="p" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">{{ p }}</span>
                        <div v-if="!meetingDetail.attendees || meetingDetail.attendees.length === 0" class="text-[12px] text-slate-400 italic">Tidak tercatat</div>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-bold">Notulensi</div>
                    <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-100 rounded p-3">{{ meetingDetail.notes || '-' }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-bold">Keputusan</div>
                    <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-100 rounded p-3">{{ meetingDetail.decisions || '-' }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-bold">Komentar Peserta</div>
                    <div v-if="meetingComments.length === 0" class="text-[12px] text-slate-400 italic">Belum ada komentar.</div>
                    <div v-else class="space-y-2">
                        <div v-for="c in meetingComments" :key="c.id" class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                            <div class="flex items-center justify-between">
                                <div class="text-[12px] font-bold text-slate-700">{{ c.people_name || c.username || 'Peserta' }}</div>
                                <div class="text-[10px] text-slate-400">{{ formatDateTime(c.created_at) }}</div>
                            </div>
                            <div class="text-[12px] text-slate-700 mt-1">{{ c.comment }}</div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-bold">Catatan Peserta</div>
                    <div v-if="participantNotes.length === 0" class="text-[12px] text-slate-400 italic">Belum ada catatan peserta.</div>
                    <div v-else class="space-y-2">
                        <div v-for="n in participantNotes" :key="n.participant" class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                            <div class="flex items-center justify-between">
                                <div class="text-[12px] font-bold text-slate-700">{{ n.participant }}</div>
                                <div class="text-[10px] text-slate-400">{{ formatDateTime(n.updated_at) }}</div>
                            </div>
                            <div class="text-[12px] text-slate-700 mt-1">{{ n.note || '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const { createApp } = Vue;
createApp({
    data() {
        return {
            list: [],
            searchQuery: '',
            filterStatus: 'PENDING',
            selectedItem: null,
            selectedApprover: '',
            employees: [],
            pendingCount: 0,
            showModal: false,
            isSubmitting: false,
            form: { id: null, title: '', module: 'UMUM', amount: '', requester: '', attachment: '', description: '', meeting_id: null, meeting_title: '' },
            showMeetingDetail: false,
            meetingDetail: null,
            meetingComments: [],
            participantNotes: [],
            // requester search
            requesterQuery: '',
            requesterSuggestions: [],
            staffDirectory: [],
            staffLoaded: false,
            // meeting search
            meetingQuery: '',
            meetingSuggestions: [],
            meetingsCache: []
        }
    },
    computed: {
        filteredList() {
            return this.list.filter(item => {
                const matchesStatus = this.filterStatus === 'ALL' || item.status === this.filterStatus;
                const matchesSearch = item.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                      item.reference_no.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                      item.requester.toLowerCase().includes(this.searchQuery.toLowerCase());
                return matchesStatus && matchesSearch;
            });
        }
    },
    methods: {
        getBaseUrl() {
            let baseUrl = window.BASE_URL || '/';
            if (baseUrl === '/' || !baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                baseUrl = m ? `/${m[1]}/` : '/';
            }
            return baseUrl;
        },
        openModal() {
            this.fetchEmployees();
            this.form = { id: null, title: '', module: 'UMUM', amount: '', requester: '', attachment: '', description: '', meeting_id: null, meeting_title: '' };
            this.showModal = true;
        },
        editItem(item) {
            this.fetchEmployees();
            this.form = { ...item };
            this.showModal = true;
        },
        async deleteItem(item) {
            if (!confirm('Apakah Anda yakin ingin menghapus pengajuan ini?')) return;
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/approval.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: item.id })
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedItem = null;
                    this.fetchData();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Gagal menghapus data');
            }
        },
        async fetchEmployees() {
            if (this.employees.length > 0) return;
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/approval.php?action=get_employees');
                const data = await res.json();
                if (data.success) {
                    this.employees = data.data;
                }
            } catch (e) {}
        },
        async ensureStaffLoaded() {
            if (this.staffLoaded) return;
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/get_all_staff.php');
                const data = await res.json();
                this.staffDirectory = Array.isArray(data) ? data : [];
                this.staffLoaded = true;
            } catch (e) {
                this.staffDirectory = [];
                this.staffLoaded = false;
            }
        },
        updateRequesterSuggestions() {
            const q = (this.requesterQuery || '').trim().toLowerCase();
            if (q.length < 2 || !Array.isArray(this.staffDirectory)) { this.requesterSuggestions = []; return; }
            this.requesterSuggestions = this.staffDirectory
                .filter(s => (s.name || '').toLowerCase().includes(q))
                .slice(0, 8);
        },
        setRequester(name) {
            this.form.requester = name;
            this.requesterQuery = '';
            this.requesterSuggestions = [];
        },
        async fetchMeetingsCache() {
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/meetings.php?action=list&limit=100');
                const data = await res.json();
                if (data.success) this.meetingsCache = data.data || [];
                else this.meetingsCache = [];
            } catch (e) { this.meetingsCache = []; }
        },
        updateMeetingSuggestions() {
            if (!this.meetingsCache || this.meetingsCache.length === 0) { this.fetchMeetingsCache(); }
            const q = (this.meetingQuery || '').trim().toLowerCase();
            if (q.length < 1) { this.meetingSuggestions = []; return; }
            this.meetingSuggestions = (this.meetingsCache || [])
                .filter(m => ((m.title || '').toLowerCase().includes(q)) || ((m.meeting_number || '').toLowerCase().includes(q)))
                .slice(0, 10);
        },
        selectMeeting(m) {
            this.form.meeting_id = m.id;
            this.form.meeting_title = m.title || '';
            this.meetingQuery = '';
            this.meetingSuggestions = [];
        },
        clearSelectedMeeting() {
            this.form.meeting_id = null;
            this.form.meeting_title = '';
        },
        async submitApproval() {
            if (!this.form.title || !this.form.requester || !this.form.description) {
                alert('Mohon lengkapi judul, pemohon, dan deskripsi.');
                return;
            }
            this.isSubmitting = true;
            try {
                const action = this.form.id ? 'update' : 'create';
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + `api/approval.php?action=${action}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    alert(this.form.id ? 'Pengajuan berhasil diperbarui!' : 'Pengajuan berhasil dibuat!');
                    this.showModal = false;
                    this.fetchData();
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan sistem');
            } finally {
                this.isSubmitting = false;
            }
        },
        async fetchData() {
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/approval.php?action=get_list&status=ALL');
                const data = await res.json();
                if (data.success) {
                    this.list = data.data;
                    this.pendingCount = this.list.filter(i => i.status === 'PENDING').length;
                    if (this.selectedItem) {
                        const freshItem = this.list.find(i => i.id === this.selectedItem.id);
                        if (freshItem) this.selectedItem = freshItem;
                    }
                }
            } catch (e) {}
        },
        selectItem(item) {
            this.selectedItem = item;
        },
        async openRelatedMeeting(item) {
            const id = item.meeting_id;
            if (!id) return;
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/meetings.php?action=get&meeting_id=' + id);
                const data = await res.json();
                if (data.success) {
                    const m = data.data || {};
                    let attendees = [];
                    if (m.attendees) {
                        if (typeof m.attendees === 'string') {
                            try { attendees = JSON.parse(m.attendees) || []; } catch (_) { attendees = []; }
                        } else if (Array.isArray(m.attendees)) {
                            attendees = m.attendees;
                        }
                    }
                    this.meetingDetail = {
                        id: m.id,
                        title: m.title,
                        meeting_number: m.meeting_number,
                        meeting_date: m.meeting_date,
                        module_tag: m.module_tag,
                        tags: m.tags || '',
                        attendees: attendees,
                        notes: m.notes || '',
                        decisions: m.decisions || ''
                    };
                    await this.fetchMeetingComments(m.id);
                    await this.fetchParticipantNotes(m.id);
                    this.showMeetingDetail = true;
                } else {
                    alert('Rapat tidak ditemukan');
                }
            } catch (e) {
                alert('Gagal memuat detail rapat');
            }
        },
        async fetchMeetingComments(meetingId) {
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/meetings.php?action=list_comments&meeting_id=' + meetingId + '&limit=100');
                const data = await res.json();
                this.meetingComments = data.success ? (data.data || []) : [];
            } catch (e) { this.meetingComments = []; }
        },
        async fetchParticipantNotes(meetingId) {
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/meetings.php?action=list_participant_notes&meeting_id=' + meetingId);
                const data = await res.json();
                this.participantNotes = data.success ? (data.data || []) : [];
            } catch (e) { this.participantNotes = []; }
        },
        getModuleColor(module) {
            const colors = {
                'KEUANGAN': 'bg-blue-100 text-blue-700',
                'HRD': 'bg-purple-100 text-purple-700',
                'SARPRAS': 'bg-orange-100 text-orange-700',
                'PROJECT': 'bg-amber-100 text-amber-700',
                'AKADEMIK': 'bg-emerald-100 text-emerald-700',
                'BOARDING': 'bg-indigo-100 text-indigo-700',
                'LIBRARY': 'bg-teal-100 text-teal-700'
            };
            return colors[module] || 'bg-slate-100 text-slate-700';
        },
        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
        },
        formatDate(dateStr) {
            const d = new Date(dateStr);
            const today = new Date();
            if (d.toDateString() === today.toDateString()) {
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },
        formatDateTime(dateStr) {
            return new Date(dateStr).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
        },
        async updateStatus(item, status) {
            const msg = status === 'APPROVED' ? 'MENYETUJUI' : status === 'REJECTED' ? 'MENOLAK' : 'MENGEMBALIKAN KE PENDING';
            if(!confirm(`Apakah Anda yakin ingin ${msg} pengajuan ini?`)) return;
            try {
                const baseUrl = this.getBaseUrl();
                const res = await fetch(baseUrl + 'api/approval.php?action=update_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: item.id, status: status })
                });
                const data = await res.json();
                if (data.success) {
                    alert(status === 'APPROVED' ? 'Berhasil disetujui' : status === 'REJECTED' ? 'Berhasil ditolak' : 'Berhasil dikembalikan ke pending');
                    await this.fetchData();
                    if (this.filterStatus === 'PENDING' && status !== 'PENDING') {
                        this.selectedItem = null;
                    }
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan sistem');
            }
        }
    },
    mounted() {
        this.fetchData();
    }
}).mount('#app');
</script>
