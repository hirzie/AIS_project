            <div v-if="currentUnit !== 'asrama' && currentPosition !== 'wali'" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Aktivitas Terkini</h3>
                    <div class="flex items-center gap-2">
                        <button @click="setActivityTab('PRESENSI')" :class="activityButtonClass('PRESENSI')" class="text-xs font-bold px-3 py-1 rounded">Presensi</button>
                        <button @click="setActivityTab('RAPAT')" :class="activityButtonClass('RAPAT')" class="text-xs font-bold px-3 py-1 rounded">Rapat</button>
                        <button @click="setActivityTab('KEGIATAN')" :class="activityButtonClass('KEGIATAN')" class="text-xs font-bold px-3 py-1 rounded">Kegiatan</button>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="activeActivityTab === 'PRESENSI'">
                        <div v-if="activities.PRESENSI.length > 0" class="space-y-3">
                            <div v-for="a in activities.PRESENSI" :key="a.created_at + (a.entity_id || '')" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ a.title || (a.action + ' Presensi') }}</div>
                                    <div class="text-[10px] text-slate-500">{{ formatDate(a.created_at) }}</div>
                                </div>
                                <div class="text-xs text-slate-500">{{ a.people_name || a.username || '-' }}</div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-500">Belum ada aktivitas presensi.</div>
                    </div>
                    <div v-if="activeActivityTab === 'RAPAT'">
                        <div v-if="activities.RAPAT.length > 0" class="space-y-3">
                            <div v-for="a in activities.RAPAT" :key="a.created_at + (a.entity_id || '')" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ a.title || 'Rapat' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ formatDate(a.created_at) }}</div>
                                </div>
                                <div class="text-xs text-slate-500">{{ a.people_name || a.username || '-' }}</div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-500">Belum ada aktivitas rapat.</div>
                    </div>
                    <div v-if="activeActivityTab === 'KEGIATAN'">
                        <div v-if="activities.KEGIATAN.length > 0" class="space-y-3">
                            <div v-for="a in activities.KEGIATAN" :key="a.created_at + (a.entity_id || '')" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ a.title || 'Kegiatan' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ formatDate(a.created_at) }}</div>
                                </div>
                                <div class="text-xs text-slate-500">{{ a.people_name || a.username || '-' }}</div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-500">Belum ada aktivitas kegiatan.</div>
                    </div>
                </div>
            </div>
