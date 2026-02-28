            <div v-if="currentUnit === 'asrama'" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[12px] font-bold text-slate-700">Rekap Mingguan</div>
                        <div class="text-[10px] text-slate-400">7 hari terakhir</div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg p-3 bg-emerald-50">
                            <div class="text-xl font-bold text-emerald-700">{{ weeklyMonthlyZero.weekly.safe }}</div>
                            <div class="text-[10px] text-emerald-600">Aman</div>
                        </div>
                        <div class="rounded-lg p-3 bg-red-50">
                            <div class="text-xl font-bold text-red-700">{{ weeklyMonthlyZero.weekly.incident }}</div>
                            <div class="text-[10px] text-red-600">Insiden</div>
                        </div>
                        <div class="rounded-lg p-3 bg-amber-50">
                            <div class="text-xl font-bold text-amber-700">{{ getWeeklyPending }}</div>
                            <div class="text-[10px] text-amber-600">Lupa</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[12px] font-bold text-slate-700">Rekap Bulanan</div>
                        <div class="text-[10px] text-slate-400">31 hari terakhir</div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg p-3 bg-emerald-50">
                            <div class="text-xl font-bold text-emerald-700">{{ weeklyMonthlyZero.monthly.safe }}</div>
                            <div class="text-[10px] text-emerald-600">Aman</div>
                        </div>
                        <div class="rounded-lg p-3 bg-red-50">
                            <div class="text-xl font-bold text-red-700">{{ weeklyMonthlyZero.monthly.incident }}</div>
                            <div class="text-[10px] text-red-600">Insiden</div>
                        </div>
                        <div class="rounded-lg p-3 bg-amber-50">
                            <div class="text-xl font-bold text-amber-700">{{ getMonthlyPending }}</div>
                            <div class="text-[10px] text-amber-600">Lupa</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 relative overflow-hidden">
                    <div class="absolute inset-0 pointer-events-none" :class="zeroUrgencyClass"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-[12px] font-bold text-slate-700">Status Laporan Malam</div>
                            <div class="text-[10px] text-slate-400">{{ zeroUrgencyText }}</div>
                        </div>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            <div v-for="r in zeroTodayReports" :key="r.name + r.created_at" class="flex items-center justify-between p-2 rounded border border-slate-200">
                                <div class="text-[12px] font-bold text-slate-800 truncate">{{ r.name }}</div>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <span :class="r.status==='SAFE' ? 'text-emerald-600' : (r.status==='INCIDENT' ? 'text-amber-600' : 'text-red-600')">{{ r.status }}</span>
                                    <span class="text-slate-400">{{ new Date(r.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                    <button v-if="String(r.status||'').toUpperCase()==='PENDING'" @click="nudgeMusyrif(r.name)" class="ml-2 px-2 py-0.5 rounded bg-red-600 text-white font-bold">Hubungi</button>
                                </div>
                            </div>
                            <div v-if="zeroTodayReports.length === 0" class="text-xs text-slate-500">Belum ada laporan.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="currentUnit === 'asrama'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Izin Berjalan</div>
                            <div class="text-2xl font-bold text-slate-800">{{ permissionsActive.length }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div v-for="p in permissionsActive.slice(0,6)" :key="p.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                            <div>
                                <div class="text-sm font-bold text-slate-800 truncate">{{ p.student_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ p.permission_type }} â€¢ {{ p.status }}</div>
                            </div>
                            <div class="text-[10px] text-slate-400">{{ formatDate(p.start_date) }}â€“{{ formatDate(p.end_date) }}</div>
                        </div>
                        <div v-if="permissionsActive.length === 0" class="text-xs text-slate-500">Tidak ada izin aktif.</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Prestasi Terbaru</div>
                            <div class="text-2xl font-bold text-slate-800">{{ achievementsRecent.length }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div v-for="a in achievementsRecent.slice(0,6)" :key="a.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                            <div>
                                <div class="text-sm font-bold text-slate-800 truncate">{{ a.student_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ a.category }} â€¢ {{ a.points }} Poin</div>
                            </div>
                            <div class="text-[10px] text-slate-400">{{ formatDate(a.record_date) }}</div>
                        </div>
                        <div v-if="achievementsRecent.length === 0" class="text-xs text-slate-500">Belum ada data prestasi.</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-xl">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Pelanggaran Terbaru</div>
                            <div class="text-2xl font-bold text-slate-800">{{ violationsRecent.length }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div v-for="v in violationsRecent.slice(0,6)" :key="v.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                            <div>
                                <div class="text-sm font-bold text-slate-800 truncate">{{ v.student_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ v.category }} â€¢ {{ v.points }} Poin</div>
                            </div>
                            <div class="text-[10px] text-slate-400">{{ formatDate(v.record_date) }}</div>
                        </div>
                        <div v-if="violationsRecent.length === 0" class="text-xs text-slate-500">Belum ada data pelanggaran.</div>
                    </div>
                </div>
            </div>
            <div v-if="currentUnit === 'asrama'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-layer-group text-indigo-600"></i> Center Asrama
                            </h3>
                            <div class="flex items-center gap-2">
                                <button @click="activeTab='MEETINGS'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='MEETINGS' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'">Rapat</button>
                                <button @click="activeTab='TASKS'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='TASKS' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700'">Task</button>
                                <button @click="activeTab='PROC'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='PROC' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'">Pengajuan</button>
                                <button @click="activeTab='DOCS'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='DOCS' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Dokumen</button>
                                <button @click="activeTab='ZERO'" class="text-[11px] font-bold px-3 py-1 rounded" :class="activeTab==='ZERO' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700'">Zero Report</button>
                            </div>
                        </div>
                        <div class="p-5">
                            <div v-show="activeTab==='MEETINGS'">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[12px] font-bold text-slate-600">Rapat Asrama</div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700" v-if="recentMeetingCount > 0">{{ recentMeetingCount }} baru</span>
                                </div>
                                <div v-if="meetings.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="m in meetings" :key="m.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ m.title }}</div>
                                                    <div class="text-[10px] font-mono text-slate-500">{{ m.meeting_number }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-[10px] text-slate-400">{{ formatDate(m.meeting_date) }}</div>
                                                    <div class="flex gap-2 justify-end mt-1">
                                                        <a :href="baseUrl + 'api/meetings.php?action=list_documents&meeting_id=' + m.id" target="_blank" class="text-[10px] font-bold text-indigo-600 hover:underline">Dokumen</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2" v-if="m.notes">{{ m.notes }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada rapat.</div>
                            </div>
                            <div v-show="activeTab==='TASKS'">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[12px] font-bold text-slate-600">Task Asrama</div>
                                    <button @click="tasks.push({ id: Date.now(), text: '', done: false })" class="text-[11px] font-bold px-3 py-1 rounded bg-amber-600 text-white">Tambah</button>
                                </div>
                                <div v-if="tasks.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="(t, idx) in tasks" :key="t.id" class="px-4 py-3 bg-white">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" v-model="t.done" @change="saveTasks">
                                                <input v-model="t.text" @change="saveTasks" class="flex-1 border border-slate-300 rounded px-3 py-1 text-sm">
                                                <button @click="tasks.splice(idx,1); saveTasks()" class="text-[11px] font-bold px-2 py-1 rounded bg-red-600 text-white">Hapus</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada task.</div>
                            </div>
                            <div v-show="activeTab==='PROC'">
                                <div class="text-[12px] font-bold text-slate-600 mb-3">Pengajuan</div>
                                <div v-if="approvals.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="a in approvals" :key="a.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ a.title || a.type }}</div>
                                                    <div class="text-[10px] text-slate-500">{{ a.status }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-[10px] text-slate-400">{{ formatDate(a.created_at) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada pengajuan.</div>
                            </div>
                            <div v-show="activeTab==='DOCS'">
                                <div class="text-[12px] font-bold text-slate-600 mb-3">Dokumen Rapat</div>
                                <div v-if="documents.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="d in documents" :key="d.id" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors flex items-center justify-between">
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">{{ d.title || d.file_name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ d.meeting_title || d.meeting_number }}</div>
                                            </div>
                                            <a :href="baseUrl + (d.url || ('uploads/managerial/docs/' + d.file_name))" target="_blank" class="text-[11px] font-bold px-3 py-1 rounded bg-indigo-600 text-white">Buka</a>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada dokumen.</div>
                            </div>
                            <div v-show="activeTab==='ZERO'">
                                <div class="text-[12px] font-bold text-slate-800 mb-3 flex items-center gap-2">
                                    <i class="fas fa-shield-alt text-red-600"></i> Monitoring Zero Report (Harian)
                                </div>
                                <div class="grid grid-cols-3 gap-3 mb-4">
                                    <div class="rounded-xl border border-slate-200 p-3 bg-emerald-50 text-center">
                                        <div class="text-2xl font-bold text-emerald-700">{{ zeroCounts.safe }}</div>
                                        <div class="text-[10px] text-emerald-600">Sudah Lapor Aman</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-3 bg-amber-50 text-center">
                                        <div class="text-2xl font-bold text-amber-700">{{ zeroCounts.incident }}</div>
                                        <div class="text-[10px] text-amber-600">Ada Insiden</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-3 bg-red-50 text-center">
                                        <div class="text-2xl font-bold text-red-700">{{ zeroCounts.pending }}</div>
                                        <div class="text-[10px] text-red-600">Belum Lapor</div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="p-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                        <div class="text-[12px] font-bold text-slate-700">Rekap Hari Ini</div>
                                        <div class="text-[10px] text-slate-500">Top Responder: {{ topResponder.name || '-' }} ({{ topResponder.time || '-' }})</div>
                                    </div>
                                    <div class="divide-y divide-slate-100">
                                        <div v-for="r in zeroTodayReports" :key="r.name + r.created_at" class="px-4 py-2 bg-white flex items-center justify-between">
                                            <div class="text-sm font-bold text-slate-800">{{ r.name }}</div>
                                            <div class="flex items-center text-[11px]">
                                                <span :class="r.status==='SAFE' ? 'text-emerald-600' : (r.status==='INCIDENT' ? 'text-amber-600' : 'text-red-600')">{{ r.status }}</span>
                                                <span class="text-slate-400 ml-2">{{ formatDate(r.created_at) }}</span>
                                                <button v-if="String(r.status||'').toUpperCase()==='PENDING'" @click="nudgeMusyrif(r.name)" class="ml-3 px-2 py-0.5 rounded bg-red-600 text-white font-bold">Hubungi</button>
                                            </div>
                                        </div>
                                        <div v-if="zeroTodayReports.length === 0" class="px-4 py-3 text-center text-slate-400 text-sm">Belum ada laporan.</div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="text-[12px] font-bold text-slate-800 mb-2">Butuh Persetujuan Anda Segera</div>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div v-for="a in approvals.filter(x => x.status!=='APPROVED').sort((x,y)=>getWaitingHours(y)-getWaitingHours(x)).slice(0,6)" :key="a.id" class="p-2 border border-slate-200 rounded bg-white">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="text-[12px] text-slate-700 truncate">{{ a.title || (a.module + ' #' + a.reference_no) }}</div>
                                                <div class="text-[10px] font-bold" :class="a.status==='PENDING' ? 'text-amber-600' : 'text-red-600'">{{ a.status }}</div>
                                            </div>
                                            <div class="h-2 rounded bg-red-100 overflow-hidden">
                                                <div class="h-2 bg-red-600" :style="{ width: getWaitingBarWidth(a) }"></div>
                                            </div>
                                            <div class="text-[10px] text-red-600 mt-1">Menunggu Anda selama {{ getWaitingText(a) }}</div>
                                        </div>
                                        <div v-if="approvals.filter(x => x.status!=='APPROVED').length === 0" class="text-xs text-slate-500">Tidak ada isu terbuka.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <aside class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden h-full flex flex-col">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-comments text-indigo-600"></i> Chat Asrama
                            </h3>
                        </div>
                        <div class="p-5 flex-1 overflow-y-auto">
                            <div v-if="chatMessages.length > 0" class="space-y-3">
                                <div v-for="(c, idx) in chatMessages" :key="c.id" class="flex items-start justify-between">
                                    <div class="max-w-[80%]">
                                        <div class="text-[12px] text-slate-700 bg-slate-50 border border-slate-200 rounded px-3 py-2">{{ c.text }}</div>
                                        <div class="text-[10px] text-slate-400 mt-1">{{ formatChatTime(c.ts) }}</div>
                                    </div>
                                    <button @click="deleteChatMessage(idx)" class="text-[10px] font-bold text-red-600">Hapus</button>
                                </div>
                            </div>
                            <div v-else class="text-center text-slate-400 text-sm py-3">Belum ada pesan.</div>
                        </div>
                        <div class="p-4 border-t border-slate-100 bg-white">
                            <div class="flex items-center gap-2">
                                <input v-model="chatInput" @keyup.enter="sendChat" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" placeholder="Tulis pesan...">
                                <button @click="sendChat" class="px-3 py-2 rounded bg-indigo-600 text-white text-[12px] font-bold">Kirim</button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
