<?php
require_once '../../includes/guard.php';
require_login_and_module('executive');
require_once '../../includes/header.php';
?>
<?php
$__userRole = strtoupper($_SESSION['role'] ?? '');
?>
<script>
    window.USER_ROLE = <?php echo json_encode($__userRole); ?>;
</script>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/executive/index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-handshake text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Rapat & Notulensi</h1>
                <span class="text-xs text-slate-500 font-medium">Executive View</span>
            </div>
        </div>
        <div class="flex items-center gap-2"></div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-handshake text-indigo-600"></i> Daftar Rapat
                    </h3>
                    <div class="flex items-center gap-2">
                        <select v-model="selectedModule" @change="fetchMeetings" class="px-2 py-1 rounded text-[10px] font-bold border border-slate-300 bg-white">
                            <option value="ALL">Semua Divisi</option>
                            <option v-for="m in modulesList" :key="m.code" :value="m.code">{{ m.name || m.code }}</option>
                        </select>
                        <button @click="fetchMeetings" class="px-2 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600 bg-white">Refresh</button>
                        <button @click="openLogModal" class="px-2 py-1 rounded text-[10px] font-bold text-slate-600 hover:text-indigo-600">Log Rapat</button>
                        <button @click="openNewMeeting" class="px-3 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i> Buat Rapat</button>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="meetings.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="divide-y divide-slate-100">
                            <div v-for="m in meetings" :key="m.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ m.module_tag }}</span>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 text-sm truncate">{{ m.title }}</div>
                                            <div class="text-[10px] font-mono text-slate-500">{{ m.meeting_number }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center -space-x-2 shrink-0">
                                        <template v-for="(att, idx) in getAttendeesList(m).slice(0,5)" :key="att + idx">
                                            <div class="w-7 h-7 rounded-full border border-white text-[10px] font-bold flex items-center justify-center"
                                                 :style="{ backgroundColor: getAvatarColor(att) }">
                                                {{ getInitials(att) }}
                                            </div>
                                        </template>
                                        <div v-if="getAttendeesList(m).length > 5" class="w-7 h-7 rounded-full border border-white bg-slate-200 text-[10px] font-bold flex items-center justify-center">
                                            +{{ getAttendeesList(m).length - 5 }}
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[10px] text-slate-400">{{ formatDate(m.meeting_date) }}</div>
                                        <div v-if="pendingDeleteId !== m.id" class="flex gap-2 justify-end mt-1">
                                            <button @click="openMeeting(m)" class="text-[10px] font-bold text-slate-600 hover:text-indigo-600">Detail</button>
                                            <button @click="openEditMeeting(m)" class="text-[10px] font-bold text-amber-600 hover:text-amber-800">Edit</button>
                                            <button v-if="['MANAGERIAL','ADMIN','SUPERADMIN'].includes(userRole)" 
                                                    @click="sendMeetingWa(m)" 
                                                    :disabled="sendingMeetingId===m.id"
                                                    class="text-[10px] font-bold text-green-700 hover:text-green-800">
                                                {{ sendingMeetingId===m.id ? 'Mengirim...' : 'Kirim' }}
                                            </button>
                                            <button v-if="canDelete()" @click="askDelete(m)" class="text-[10px] font-bold text-red-600 hover:text-red-800">Hapus</button>
                                        </div>
                                        <div v-else class="flex gap-2 justify-end mt-1">
                                            <span class="text-[10px] text-slate-500 font-bold">Konfirmasi?</span>
                                            <button @click="performDelete(m.id)" class="px-2 py-1 text-[10px] font-bold bg-red-600 text-white rounded hover:bg-red-700">Ya</button>
                                            <button @click="cancelDelete" class="px-2 py-1 text-[10px] font-bold border border-slate-300 text-slate-600 rounded hover:bg-slate-100">Batal</button>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-2" v-if="m.notes">{{ m.notes }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-slate-400 py-6 text-sm">Belum ada rapat.</div>
                </div>
            </div>
            <div v-show="activeTab==='FORM'" id="formSection" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800">{{ isEdit ? 'Edit Rapat' : '' }}</h3>
                    <div class="flex items-center gap-2">
                        <button @click="activeTab='LIST'" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Batal</button>
                        <button @click="saveMeeting" :disabled="savingMeeting" class="px-3 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                            <i class="fas fa-save mr-1" :class="{'fa-spin': savingMeeting}"></i> Simpan
                        </button>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Judul</label>
                            <input v-model="meetingForm.title" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label>
                                <input type="date" v-model="meetingForm.meeting_date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Divisi</label>
                                <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span v-if="meetingForm.module_tag" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">{{ meetingForm.module_tag }}</span>
                                    </div>
                                    <input v-model="moduleQueryPrimary" @input="updateModuleSuggestionsPrimary" placeholder="Cari divisi utama (misal: fin → FINANCE)" class="w-full outline-none">
                                </div>
                                <div v-if="moduleSuggestionsPrimary.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                    <div v-for="s in moduleSuggestionsPrimary" :key="s" @click="setPrimaryModuleTag(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Rapat</label>
                                <input v-model="meetingForm.meeting_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="M-YYYYMMDD-XXXX">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Divisi terkait</label>
                                <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <span v-for="mod in (meetingForm.modules_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="mod" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                            {{ mod }}
                                            <button @click="removeRelatedModule(mod)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                                        </span>
                                    </div>
                                    <input v-model="moduleQueryRelated" @input="updateModuleSuggestionsRelated" placeholder="Cari divisi (misal: board → BOARDING)" class="w-full outline-none">
                                </div>
                                <div v-if="moduleSuggestionsRelated.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                    <div v-for="s in moduleSuggestionsRelated" :key="s" @click="addRelatedModule(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi Rapat</label>
                            <input v-model="meetingForm.location" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Ruang Rapat A">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Notulensi</label>
                            <textarea v-model="meetingForm.notes" rows="10" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Keputusan</label>
                            <textarea v-model="meetingForm.decisions" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-bold text-slate-500 mb-1">Cari Peserta</label>
                            <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                <input v-model="attendeeQuery" @input="updateAttendeeSuggestions" @focus="ensureStaffLoaded" placeholder="Cari nama peserta..." class="w-full outline-none">
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                    {{ att }}
                                    <button @click="removeAttendee(att)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                                </span>
                            </div>
                            <div v-if="attendeeSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                <div v-for="s in attendeeSuggestions" :key="s.id || s.name" @click="addAttendee(s.name)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                                    {{ s.name }}
                                </div>
                            </div>
                        </div>
                        <div v-if="(meetingForm.attendees_text || '').trim()" class="space-y-2">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-500">Catatan Peserta</label>
                                <button v-if="meetingForm.id" @click="saveEditorPointsForEdit" :disabled="savingEditorPoints" class="px-2 py-1 text-[11px] font-bold bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50">
                                    <i class="fas fa-save mr-1" :class="{'fa-spin': savingEditorPoints}"></i> Simpan Catatan
                                </button>
                            </div>
                            <div class="border border-slate-200 rounded p-3 bg-white max-h-80 overflow-y-auto space-y-2">
                                <div v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="space-y-1">
                                    <div class="text-[11px] font-bold text-slate-700">{{ att }}</div>
                                    <textarea v-model="editorPoints[att]" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-[12px]" placeholder="Catatan peserta"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="showLogs" id="logsSection" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-slate-600"></i> Log Rapat
                    </h3>
                    <div class="flex items-center gap-2">
                        <select v-model="logsActionFilter" class="px-2 py-1 rounded border border-slate-300 text-[12px]">
                            <option value="ALL">Semua Aksi</option>
                            <option value="CREATE">CREATE</option>
                            <option value="UPDATE">UPDATE</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                        <button @click="fetchLogs" class="px-2 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600 bg-white">Refresh</button>
                        <button @click="hideLogSection" class="px-2 py-1 rounded text-[10px] font-bold text-slate-600 hover:text-indigo-600">Hide Log</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Entity</th>
                                <th class="px-4 py-3">Judul</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada catatan aktivitas rapat.</td>
                            </tr>
                            <tr v-for="row in filteredLogs" :key="(row.created_at || '') + (row.title || '')" class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2 text-slate-500 text-xs">{{ formatDateTime(row.created_at) }}</td>
                                <td class="px-4 py-2 text-slate-700 text-xs">{{ row.action }}</td>
                                <td class="px-4 py-2 text-slate-700 text-xs">
                                    <div class="font-mono">{{ row.entity_type || '-' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ row.entity_id || '-' }}</div>
                                </td>
                                <td class="px-4 py-2 text-slate-700 text-sm">{{ row.title || '-' }}</td>
                                <td class="px-4 py-2 text-slate-600 text-sm">{{ row.description || '-' }}</td>
                                <td class="px-4 py-2 text-slate-600 text-xs">{{ row.people_name || row.username || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <div v-if="showMeetingModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-800">{{ isEdit ? 'Edit Rapat' : 'Buat Rapat' }}</h3>
                <button @click="showMeetingModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Judul</label>
                        <input v-model="meetingForm.title" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label>
                            <input type="date" v-model="meetingForm.meeting_date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Divisi</label>
                            <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                <div class="flex items-center gap-2 mb-2">
                                    <span v-if="meetingForm.module_tag" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">{{ meetingForm.module_tag }}</span>
                                </div>
                                <input v-model="moduleQueryPrimary" @input="updateModuleSuggestionsPrimary" placeholder="Cari divisi utama (misal: fin → FINANCE)" class="w-full outline-none">
                            </div>
                            <div v-if="moduleSuggestionsPrimary.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                <div v-for="s in moduleSuggestionsPrimary" :key="s" @click="setPrimaryModuleTag(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Rapat</label>
                            <input v-model="meetingForm.meeting_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="M-YYYYMMDD-XXXX">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Divisi terkait</label>
                            <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span v-for="mod in (meetingForm.modules_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="mod" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                        {{ mod }}
                                        <button @click="removeRelatedModule(mod)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                                    </span>
                                </div>
                                <input v-model="moduleQueryRelated" @input="updateModuleSuggestionsRelated" placeholder="Cari divisi (misal: board → BOARDING)" class="w-full outline-none">
                            </div>
                            <div v-if="moduleSuggestionsRelated.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                                <div v-for="s in moduleSuggestionsRelated" :key="s" @click="addRelatedModule(s)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">{{ s }}</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi Rapat</label>
                        <input v-model="meetingForm.location" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Contoh: Ruang Rapat A">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Notulensi</label>
                        <textarea v-model="meetingForm.notes" rows="10" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Keputusan</label>
                        <textarea v-model="meetingForm.decisions" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-slate-500 mb-1">Cari Peserta</label>
                        <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                            <input v-model="attendeeQuery" @input="updateAttendeeSuggestions" @focus="ensureStaffLoaded" placeholder="Cari nama peserta..." class="w-full outline-none">
                        </div>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                {{ att }}
                                <button @click="removeAttendee(att)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                            </span>
                        </div>
                        <div v-if="attendeeSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm">
                            <div v-for="s in attendeeSuggestions" :key="s.id || s.name" @click="addAttendee(s.name)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                                {{ s.name }}
                            </div>
                        </div>
                    </div>
                    <div v-if="(meetingForm.attendees_text || '').trim()" class="space-y-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-bold text-slate-500">Catatan Peserta</label>
                            <button v-if="meetingForm.id" @click="saveEditorPointsForEdit" :disabled="savingEditorPoints" class="px-2 py-1 text-[11px] font-bold bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50">
                                <i class="fas fa-save mr-1" :class="{'fa-spin': savingEditorPoints}"></i> Simpan Catatan
                            </button>
                        </div>
                        <div class="border border-slate-200 rounded p-3 bg-white max-h-80 overflow-y-auto space-y-2">
                            <div v-for="att in (meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="att" class="space-y-1">
                                <div class="text-[11px] font-bold text-slate-700">{{ att }}</div>
                                <textarea v-model="editorPoints[att]" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-[12px]" placeholder="Catatan peserta"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="showMeetingModal=false" class="px-3 py-1 rounded text-[10px] font-bold border border-slate-300 text-slate-600">Batal</button>
                <button @click="saveMeeting" :disabled="savingMeeting" class="px-3 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                    <i class="fas fa-save mr-1" :class="{'fa-spin': savingMeeting}"></i> Simpan
                </button>
            </div>
        </div>
    </div>
    <div v-if="showDetailModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-800">Detail Rapat</h3>
                <button @click="showDetailModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div v-if="meetingDetail" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[11px] text-slate-500 font-bold">Judul</div>
                        <div class="text-sm font-bold text-slate-800">{{ meetingDetail.title }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-slate-500 font-bold">Tanggal</div>
                        <div class="text-[12px] text-slate-600">{{ formatDateFull(meetingDetail.meeting_date) }}</div>
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
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[11px] text-slate-500 font-bold">Lokasi</div>
                        <div class="text-[12px] text-slate-700">{{ meetingDetail.location || '-' }}</div>
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
                    <div class="text-[11px] text-slate-500 font-bold">Kesimpulan</div>
                    <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-100 rounded p-3">{{ meetingDetail.decisions || '-' }}</div>
                </div>
                </div>
                <div class="space-y-3">
                    <div class="text-[11px] text-slate-500 font-bold mb-1">Catatan per Peserta (KPI Keaktifan)</div>
                    <div class="space-y-2 border border-slate-200 rounded p-3 bg-white">
                        <div v-for="p in (meetingDetail.attendees || [])" :key="p" class="flex items-start gap-2">
                            <div class="text-[11px] font-bold text-slate-700 w-40 shrink-0">{{ p }}</div>
                            <textarea v-model="notesPerParticipant[p]" rows="2" class="flex-1 border border-slate-300 rounded px-3 py-2 text-[12px]" placeholder="Catatan keaktifan peserta"></textarea>
                        </div>
                        <div v-if="!meetingDetail.attendees || meetingDetail.attendees.length === 0" class="text-[12px] text-slate-400">Peserta belum ditentukan.</div>
                        <div class="text-right mt-2">
                            <button @click="saveParticipantNotes" :disabled="savingNotes" class="px-3 py-1 text-[12px] font-bold bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50">
                                <i class="fas fa-save mr-1" :class="{'fa-spin': savingNotes}"></i> Simpan Catatan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <button @click="showDetailModal=false" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">
                        Tutup
                    </button>
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
            baseUrl: window.BASE_URL || '/',
            meetings: [],
            selectedModule: 'ALL',
            activeTab: 'LIST',
            showLogs: false,
            pendingDeleteId: null,
            showMeetingModal: false,
            savingMeeting: false,
            userRole: (window.USER_ROLE || '').toUpperCase(),
            meetingForm: { id: null, meeting_number: '', title: '', meeting_date: new Date().toISOString().slice(0,10), module_tag: '', modules_text: '', tags: '', attendees_text: '', notes: '', decisions: '' },
            isEdit: false,
            showDocModal: false,
            savingDoc: false,
            docForm: { meeting_id: null, module_tag: '', doc_title: '', doc_url: '', doc_type: 'FILE', tags: '', doc_file: null },
            meetingDetail: null,
            showDetailModal: false,
            comments: [],
            commentInput: '',
            notesPerParticipant: {},
            savingNotes: false,
            editorPoints: {},
            savingEditorPoints: false,
            staffDirectory: [],
            staffLoaded: false,
            attendeeQuery: '',
            attendeeSuggestions: [],
            // Modules search state
            modulesList: [],
            moduleQueryPrimary: '',
            moduleSuggestionsPrimary: [],
            moduleQueryRelated: '',
            moduleSuggestionsRelated: [],
            showLogModal: false,
            logs: [],
            logsActionFilter: 'ALL'
            ,sendingMeetingId: null
        }
    },
    methods: {
        async fetchModules() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_modules.php');
                const data = await res.json();
                if (data.success) this.modulesList = Array.isArray(data.data) ? data.data : [];
                else this.modulesList = [];
            } catch(_) { this.modulesList = []; }
        },
        async sendMeetingWa(m) {
            if (!m || !m.id) return;
            this.sendingMeetingId = m.id;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=send_wa_meeting', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meeting_id: m.id })
                });
                const data = await res.json();
                if (data && data.success) {
                    alert('WA terkirim: OK ' + ((data.data && data.data.ok) || 0) + ' • Gagal ' + ((data.data && data.data.fail) || 0));
                } else {
                    alert('Gagal mengirim: ' + (data && data.message ? data.message : 'Unknown error'));
                }
            } catch(_) {
                alert('Terjadi kesalahan sistem');
            } finally {
                this.sendingMeetingId = null;
            }
        },
        canDelete() {
            const r = (this.userRole || '').toUpperCase();
            return r === 'SUPERADMIN' || r === 'ADMIN';
        },
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        async fetchMeetings() {
            try {
                this.normalizeBaseUrl();
                const url = this.selectedModule === 'ALL' ? (this.baseUrl + 'api/meetings.php?action=list&limit=20') : (this.baseUrl + 'api/meetings.php?action=list&module=' + this.selectedModule + '&limit=20');
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) this.meetings = data.data || [];
                else this.meetings = [];
            } catch (_) {}
        },
        formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },
        formatDateFull(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },
        formatDateTime(dateStr) {
            const d = new Date(dateStr);
            const datePart = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const timePart = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return datePart + ' ' + timePart;
        },
        getAttendeesList(m) {
            if (!m || !m.attendees) return [];
            if (Array.isArray(m.attendees)) return m.attendees;
            try { const arr = JSON.parse(m.attendees); return Array.isArray(arr) ? arr : []; } catch(_) { return []; }
        },
        getInitials(name) {
            const s = String(name || '').trim();
            if (!s) return '';
            const parts = s.split(/\s+/).filter(Boolean);
            const first = (parts[0] || '').charAt(0);
            const last = (parts.length > 1 ? parts[parts.length - 1] : '').charAt(0);
            return (first + (last || '')).toUpperCase();
        },
        getAvatarColor(name) {
            const colors = ['#bfdbfe','#fed7aa','#fde68a','#bbf7d0','#fbcfe8','#ddd6fe','#fca5a5','#a7f3d0'];
            let hash = 0;
            const s = String(name || '');
            for (let i = 0; i < s.length; i++) { hash = (hash * 31 + s.charCodeAt(i)) >>> 0; }
            return colors[hash % colors.length];
        },
        openLogModal() {
            this.showLogModal = false;
            this.showLogs = true;
            this.fetchLogs();
            this.$nextTick(() => {
                const el = document.getElementById('logsSection');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
        hideLogSection() { this.showLogs = false; },
        async fetchLogs() {
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_activity_logs.php?module=EXECUTIVE&category=MEETING&limit=200');
                const data = await res.json();
                if (data.success) this.logs = data.data || [];
                else this.logs = [];
            } catch (_) { this.logs = []; }
        },
        openMeeting(m) {
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
                location: m.location || '',
                attendees: attendees,
                notes: m.notes || '',
                decisions: m.decisions || ''
            };
            this.showDetailModal = true;
            this.fetchComments();
            this.initializeParticipantNotes();
            this.fetchParticipantNotes();
        },
        async fetchComments() {
            try {
                if (!this.meetingDetail || !this.meetingDetail.id) { this.comments = []; return; }
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=list_comments&meeting_id=' + this.meetingDetail.id + '&limit=100');
                const data = await res.json();
                if (data.success) this.comments = data.data || [];
                else this.comments = [];
            } catch (_) { this.comments = []; }
        },
        initializeEditorPointsForEdit() {
            this.editorPoints = {};
            (String(this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean)).forEach(n => { this.editorPoints[n] = this.editorPoints[n] || ''; });
            this.savingEditorPoints = false;
        },
        async fetchEditorPointsForEdit() {
            try {
                if (!this.meetingForm || !this.meetingForm.id) return;
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=list_participant_notes&meeting_id=' + this.meetingForm.id);
                const data = await res.json();
                if (data.success) {
                    const arr = data.data || [];
                    arr.forEach(r => { const key = r.participant || ''; if (key) this.editorPoints[key] = r.note || ''; });
                }
            } catch (_) {}
        },
        async saveEditorPointsForEdit() {
            if (!this.meetingForm || !this.meetingForm.id) return;
            this.savingEditorPoints = true;
            try {
                this.normalizeBaseUrl();
                const notesPayload = Object.entries(this.editorPoints || {}).map(([participant, note]) => ({ participant, note }));
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=save_participant_notes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meeting_id: this.meetingForm.id, notes: notesPayload })
                });
                const data = await res.json();
                if (!data.success) { alert('Gagal menyimpan: ' + (data.message || '')); }
            } catch (_) { alert('Terjadi kesalahan sistem'); }
            finally { this.savingEditorPoints = false; }
        },
        initializeParticipantNotes() {
            this.notesPerParticipant = {};
            (this.meetingDetail.attendees || []).forEach(n => { this.notesPerParticipant[n] = this.notesPerParticipant[n] || ''; });
            this.savingNotes = false;
        },
        async fetchParticipantNotes() {
            try {
                if (!this.meetingDetail || !this.meetingDetail.id) return;
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=list_participant_notes&meeting_id=' + this.meetingDetail.id);
                const data = await res.json();
                if (data.success) {
                    const arr = data.data || [];
                    arr.forEach(r => { const key = r.participant || ''; if (key) this.notesPerParticipant[key] = r.note || ''; });
                }
            } catch (_) {}
        },
        async saveParticipantNotes() {
            if (!this.meetingDetail || !this.meetingDetail.id) return;
            this.savingNotes = true;
            try {
                this.normalizeBaseUrl();
                const notesPayload = Object.entries(this.notesPerParticipant || {}).map(([participant, note]) => ({ participant, note }));
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=save_participant_notes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meeting_id: this.meetingDetail.id, notes: notesPayload })
                });
                const data = await res.json();
                if (!data.success) { alert('Gagal menyimpan: ' + (data.message || '')); }
            } catch (_) { alert('Terjadi kesalahan sistem'); }
            finally { this.savingNotes = false; }
        },
        async sendComment() {
            const text = String(this.commentInput || '').trim();
            if (!text || !this.meetingDetail || !this.meetingDetail.id) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=add_comment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ meeting_id: this.meetingDetail.id, comment: text })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    this.comments.unshift(data.data);
                    this.commentInput = '';
                } else {
                    alert('Gagal menambah komentar: ' + (data.message || ''));
                }
            } catch (_) { alert('Terjadi kesalahan sistem'); }
        },
        openNewMeeting() {
            this.showMeetingModal = true;
            this.isEdit = false;
            this.meetingForm.meeting_number = ('M-' + new Date().toISOString().slice(0,10).replaceAll('-','') + '-' + Math.floor(Math.random()*9000+1000));
            this.meetingForm.title = '';
            this.meetingForm.module_tag = (this.selectedModule && this.selectedModule !== 'ALL') ? this.selectedModule : '';
            this.meetingForm.modules_text = '';
            this.meetingForm.tags = '';
            this.meetingForm.location = '';
            this.meetingForm.attendees_text = '';
            this.meetingForm.notes = '';
            this.meetingForm.decisions = '';
            this.meetingForm.id = null;
            this.attendeeQuery = '';
            this.attendeeSuggestions = [];
            this.ensureStaffLoaded();
            this.fetchModules();
            this.moduleQueryPrimary = '';
            this.moduleSuggestionsPrimary = [];
            this.moduleQueryRelated = '';
            this.moduleSuggestionsRelated = [];
        },
        openEditMeeting(m) {
            this.showMeetingModal = true;
            this.isEdit = true;
            let attendeesArr = [];
            if (m.attendees) {
                if (typeof m.attendees === 'string') {
                    try { attendeesArr = JSON.parse(m.attendees) || []; } catch (_) { attendeesArr = []; }
                } else if (Array.isArray(m.attendees)) { attendeesArr = m.attendees; }
            }
            this.meetingForm = {
                id: m.id,
                meeting_number: m.meeting_number || '',
                title: m.title || '',
                meeting_date: m.meeting_date || new Date().toISOString().slice(0,10),
                module_tag: m.module_tag || 'BOARDING',
                modules_text: m.modules || '',
                tags: m.tags || '',
                location: m.location || '',
                attendees_text: attendeesArr.join(', '),
                notes: m.notes || '',
                decisions: m.decisions || ''
            };
            this.attendeeQuery = '';
            this.attendeeSuggestions = [];
            this.ensureStaffLoaded();
            this.fetchModules();
            this.moduleQueryPrimary = '';
            this.moduleSuggestionsPrimary = [];
            this.moduleQueryRelated = '';
            this.moduleSuggestionsRelated = [];
            this.initializeEditorPointsForEdit();
            this.fetchEditorPointsForEdit();
        },
        async saveMeeting() {
            if (!this.meetingForm.title) { alert('Judul rapat wajib diisi'); return; }
            this.savingMeeting = true;
            try {
                this.normalizeBaseUrl();
                const payload = {
                    id: this.meetingForm.id || undefined,
                    meeting_number: this.meetingForm.meeting_number,
                    title: this.meetingForm.title,
                    meeting_date: this.meetingForm.meeting_date,
                    module_tag: this.meetingForm.module_tag,
                    modules: (this.meetingForm.modules_text || '').split(',').map(s => s.trim().toUpperCase()).filter(Boolean).filter(m => this.modulesList.map(x=>x.code).includes(m)),
                    tags: this.meetingForm.tags,
                    location: this.meetingForm.location || '',
                    attendees: (this.meetingForm.attendees_text || '').split(',').map(s => s.trim()).filter(Boolean),
                    notes: this.meetingForm.notes,
                    decisions: this.meetingForm.decisions
                };
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=save', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) { this.showMeetingModal = false; this.fetchMeetings(); alert('Rapat berhasil disimpan'); } else { alert('Gagal: ' + data.message); }
            } catch(_) { alert('Terjadi kesalahan sistem'); } finally { this.savingMeeting = false; }
        },
        async ensureStaffLoaded() {
            if (this.staffLoaded) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/get_all_staff.php');
                const data = await res.json();
                this.staffDirectory = Array.isArray(data) ? data : [];
                this.staffLoaded = true;
            } catch (_) {
                this.staffDirectory = [];
                this.staffLoaded = false;
            }
        },
        updateAttendeeSuggestions() {
            const q = (this.attendeeQuery || '').trim().toLowerCase();
            if (q.length < 2 || !Array.isArray(this.staffDirectory)) { this.attendeeSuggestions = []; return; }
            const selected = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim().toLowerCase());
            this.attendeeSuggestions = this.staffDirectory
                .filter(s => (s.name || '').toLowerCase().includes(q))
                .filter(s => !selected.includes((s.name || '').toLowerCase()))
                .slice(0, 8);
        },
        addAttendee(name) {
            const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
            if (!cur.map(s=>s.toLowerCase()).includes(String(name).toLowerCase())) {
                cur.push(name);
            }
            this.meetingForm.attendees_text = cur.join(', ');
            this.attendeeQuery = '';
            this.attendeeSuggestions = [];
        },
        removeAttendee(name) {
            const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
            this.meetingForm.attendees_text = cur.filter(s => s.toLowerCase() !== String(name).toLowerCase()).join(', ');
        },
        updateModuleSuggestionsPrimary() {
            const q = (this.moduleQueryPrimary || '').trim().toLowerCase();
            if (q.length < 1 || !Array.isArray(this.modulesList)) { this.moduleSuggestionsPrimary = []; return; }
            this.moduleSuggestionsPrimary = this.modulesList
                .map(m => m.code)
                .filter(code => code.toLowerCase().includes(q))
                .slice(0, 8);
        },
        updateModuleSuggestionsRelated() {
            const q = (this.moduleQueryRelated || '').trim().toLowerCase();
            if (q.length < 1 || !Array.isArray(this.modulesList)) { this.moduleSuggestionsRelated = []; return; }
            const selected = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase());
            this.moduleSuggestionsRelated = this.modulesList
                .map(m => m.code)
                .filter(code => code.toLowerCase().includes(q))
                .filter(code => !selected.includes(code))
                .slice(0, 8);
        },
        addRelatedModule(tag) {
            const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
            if (!cur.includes(tag)) cur.push(tag);
            this.meetingForm.modules_text = cur.join(', ');
            this.moduleQueryRelated = '';
            this.moduleSuggestionsRelated = [];
        },
        removeRelatedModule(tag) {
            const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
            this.meetingForm.modules_text = cur.filter(s => s !== tag).join(', ');
        },
        setPrimaryModuleTag(tag) {
            this.meetingForm.module_tag = tag;
            this.moduleQueryPrimary = '';
            this.moduleSuggestionsPrimary = [];
        },
        askDelete(m) {
            if (!this.canDelete()) { alert('Anda tidak memiliki hak hapus'); return; }
            this.pendingDeleteId = m && m.id ? m.id : null;
        },
        cancelDelete() { this.pendingDeleteId = null; },
        async performDelete(id) {
            if (!id) return;
            try {
                this.normalizeBaseUrl();
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    this.pendingDeleteId = null;
                    this.fetchMeetings();
                } else {
                    alert('Gagal menghapus: ' + (data.message || ''));
                }
            } catch (_) { alert('Terjadi kesalahan sistem'); }
        },
        openDocModal(m) {
            this.showDocModal = true;
            this.docForm = { meeting_id: m.id, module_tag: m.module_tag, doc_title: '', doc_url: '', doc_type: 'FILE', tags: '', doc_file: null };
        },
        async saveDocument() {
            if (!this.docForm.doc_title) { alert('Judul dokumen wajib diisi'); return; }
            this.savingDoc = true;
            try {
                this.normalizeBaseUrl();
                if (this.docForm.doc_type === 'FILE' && this.docForm.doc_file) {
                    const fd = new FormData(); fd.append('file', this.docForm.doc_file);
                    const upRes = await fetch(this.baseUrl + 'api/meetings.php?action=upload_document', { method: 'POST', body: fd });
                    const upData = await upRes.json();
                    if (!upData.success) { alert('Upload gagal: ' + upData.message); this.savingDoc = false; return; }
                    this.docForm.doc_url = upData.data.url;
                }
                const payload = { meeting_id: this.docForm.meeting_id, module_tag: this.docForm.module_tag, doc_title: this.docForm.doc_title, doc_url: this.docForm.doc_url, doc_type: this.docForm.doc_type, tags: this.docForm.tags, allowed_roles: [] };
                const res = await fetch(this.baseUrl + 'api/meetings.php?action=add_document', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) { this.showDocModal = false; alert('Dokumen ditambahkan'); } else { alert('Gagal: ' + data.message); }
            } catch(_) { alert('Terjadi kesalahan sistem'); } finally { this.savingDoc = false; }
        },
        updateAttendeeSuggestions() {
            const q = (this.attendeeQuery || '').trim().toLowerCase();
            if (q.length < 2 || !Array.isArray(this.staffDirectory)) { this.attendeeSuggestions = []; return; }
            const selected = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim().toLowerCase());
            this.attendeeSuggestions = this.staffDirectory
                .filter(s => (s.name || '').toLowerCase().includes(q))
                .filter(s => !selected.includes((s.name || '').toLowerCase()))
                .slice(0, 8);
        },
        addAttendee(name) {
            const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
            if (!cur.map(s=>s.toLowerCase()).includes(String(name).toLowerCase())) {
                cur.push(name);
            }
            this.meetingForm.attendees_text = cur.join(', ');
            this.attendeeQuery = '';
            this.attendeeSuggestions = [];
        },
        removeAttendee(name) {
            const cur = (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
            this.meetingForm.attendees_text = cur.filter(s => s.toLowerCase() !== String(name).toLowerCase()).join(', ');
        },
        updateModuleSuggestionsPrimary() {
            const q = (this.moduleQueryPrimary || '').trim().toLowerCase();
            if (q.length < 1 || !Array.isArray(this.modulesList)) { this.moduleSuggestionsPrimary = []; return; }
            this.moduleSuggestionsPrimary = this.modulesList
                .map(m => m.code)
                .filter(code => code.toLowerCase().includes(q))
                .slice(0, 8);
        },
        updateModuleSuggestionsRelated() {
            const q = (this.moduleQueryRelated || '').trim().toLowerCase();
            if (q.length < 1 || !Array.isArray(this.modulesList)) { this.moduleSuggestionsRelated = []; return; }
            const selected = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase());
            this.moduleSuggestionsRelated = this.modulesList
                .map(m => m.code)
                .filter(code => code.toLowerCase().includes(q))
                .filter(code => !selected.includes(code))
                .slice(0, 8);
        },
        addRelatedModule(tag) {
            const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
            if (!cur.includes(tag)) cur.push(tag);
            this.meetingForm.modules_text = cur.join(', ');
            this.moduleQueryRelated = '';
            this.moduleSuggestionsRelated = [];
        },
        removeRelatedModule(tag) {
            const cur = (this.meetingForm.modules_text || '').split(',').map(s=>s.trim().toUpperCase()).filter(Boolean);
            this.meetingForm.modules_text = cur.filter(s => s !== tag).join(', ');
        },
        setPrimaryModuleTag(tag) {
            this.meetingForm.module_tag = tag;
            this.moduleQueryPrimary = '';
            this.moduleSuggestionsPrimary = [];
        },
    },
    computed: {
        filteredLogs() {
            const a = (this.logsActionFilter || 'ALL').toUpperCase();
            if (a === 'ALL') return this.logs;
            return (this.logs || []).filter(l => String(l.action || '').toUpperCase() === a);
        }
    },
    mounted() { this.fetchMeetings(); this.fetchModules(); }
}).mount('#app');
</script>
