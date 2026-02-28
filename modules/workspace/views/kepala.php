            <div v-if="currentPosition === 'kepala'">
                <div class="mb-6 border-b border-slate-200">
                    <div class="flex items-center gap-6 overflow-x-auto">
                        <button @click="workspaceTab='DASHBOARD'" :class="workspaceTab==='DASHBOARD' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">Dashboard</button>
                        <button @click="workspaceTab='SISWA'" :class="workspaceTab==='SISWA' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">Data Siswa</button>
                        <button @click="workspaceTab='PELAJARAN'" :class="workspaceTab==='PELAJARAN' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">Pelajaran</button>
                        <button @click="workspaceTab='ABSENSI'" :class="workspaceTab==='ABSENSI' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">Absensi</button>
                        <button @click="workspaceTab='BK'" :class="workspaceTab==='BK' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">BK</button>
                        <button @click="workspaceTab='INVENTARIS'" :class="workspaceTab==='INVENTARIS' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">Inventaris</button>
                        <button @click="workspaceTab='TUGAS'" :class="workspaceTab==='TUGAS' ? 'border-red-600 text-red-600' : 'border-transparent text-red-600 hover:text-red-800'" class="pb-3 text-sm font-bold border-b-2 transition-colors whitespace-nowrap">Tugas Saya</button>
                    </div>
                </div>

                <div v-if="workspaceTab==='DASHBOARD'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Locked Classes Warning (HIDDEN IN DASHBOARD, MOVED TO TUGAS TAB) -->
                    <!-- <div v-if="unitLocks.length > 0" ...> </div> -->

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Jumlah Siswa</div>
                                <div class="text-2xl font-bold text-slate-800">{{ unitStats.studentCount || 0 }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">Total siswa aktif pada unit ini.</div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Jumlah Kelas</div>
                                <div class="text-2xl font-bold text-slate-800">{{ unitStats.classCount || 0 }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">Kelas aktif dalam tahun ajaran berjalan.</div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Jumlah Guru</div>
                                <div class="text-2xl font-bold text-slate-800">{{ unitStats.teacherCount || 0 }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">Guru akademik terkait unit ini.</div>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Jumlah Pelajaran</div>
                                <div class="text-2xl font-bold text-slate-800">{{ unitStats.subjectCount || 0 }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">Total mata pelajaran aktif.</div>
                    </div>
                </div>

                <div v-if="workspaceTab==='SISWA'" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="font-bold text-slate-800 mb-4">Statistik Siswa Per Kelas</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3">Kelas</th>
                                    <th class="px-4 py-3 text-center">Laki-laki</th>
                                    <th class="px-4 py-3 text-center">Perempuan</th>
                                    <th class="px-4 py-3 text-center">Total</th>
                                    <th class="px-4 py-3 text-center">Status Lock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in unitStats.classes" :key="c.name" class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-bold">{{ c.name }}</td>
                                    <td class="px-4 py-3 text-center">{{ c.male || 0 }}</td>
                                    <td class="px-4 py-3 text-center">{{ c.female || 0 }}</td>
                                    <td class="px-4 py-3 text-center font-bold">{{ (c.male||0) + (c.female||0) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div v-if="c.isLocked" class="flex flex-col items-center gap-1">
                                            <span class="inline-flex items-center gap-1 bg-red-100 text-red-600 px-2 py-1 rounded-full text-xs font-bold">
                                                <i class="fas fa-lock"></i> Locked
                                            </span>
                                            <button @click="handleUnlockClick(c)" class="text-xs text-blue-600 underline hover:text-blue-800 font-bold">Buka Kunci</button>
                                        </div>
                                        <span v-else class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-600 px-2 py-1 rounded-full text-xs font-bold">
                                            <i class="fas fa-check-circle"></i> Aman
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!unitStats.classes || unitStats.classes.length===0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada data kelas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="workspaceTab==='PELAJARAN'" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="text-center py-12">
                        <div class="text-6xl text-blue-200 mb-4"><i class="fas fa-book-open"></i></div>
                        <h3 class="text-lg font-bold text-slate-800">Data Pelajaran</h3>
                        <p class="text-slate-500 text-sm mb-4">Total Mata Pelajaran: {{ unitStats.subjectCount || 0 }}</p>
                        <div class="mt-4 inline-block bg-slate-100 text-slate-600 px-3 py-1 rounded text-xs font-bold">Detail kurikulum sedang dalam pengembangan.</div>
                    </div>
                </div>

                <div v-if="workspaceTab==='ABSENSI'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800">Laporan Harian ({{ new Date(workspaceDailyDate).toLocaleDateString('id-ID') }})</h3>
                            <input type="date" v-model="workspaceDailyDate" @change="fetchUnitStats" class="text-xs border border-slate-200 rounded px-2 py-1 outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div class="space-y-3">
                            <div v-for="c in unitStats.classes" :key="'daily-'+c.name" class="flex items-center justify-between p-3 border border-slate-100 rounded-xl">
                                <div class="font-bold text-slate-700">{{ c.name }}</div>
                                <span :class="c.dailyStatus==='Submitted' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded text-xs font-bold">
                                    {{ c.dailyStatus==='Submitted' ? 'Sudah Rekap' : 'Belum Rekap' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800">Rekap Bulanan ({{ getMonthName(workspaceMonth) }} {{ workspaceYear }})</h3>
                            <div class="flex gap-1">
                                <select v-model="workspaceMonth" @change="fetchUnitStats" class="text-xs border border-slate-200 rounded px-2 py-1 outline-none focus:ring-1 focus:ring-blue-500">
                                    <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
                                </select>
                                <input type="number" v-model="workspaceYear" @change="fetchUnitStats" class="text-xs border border-slate-200 rounded px-2 py-1 w-16 outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div v-for="c in unitStats.classes" :key="'monthly-'+c.name" class="flex items-center justify-between p-3 border border-slate-100 rounded-xl">
                                <div class="font-bold text-slate-700">{{ c.name }}</div>
                                <span :class="c.monthlyStatus==='Validated' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded text-xs font-bold">
                                    {{ c.monthlyStatus==='Validated' ? 'Sudah Validasi' : 'Belum Validasi' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="workspaceTab==='BK'" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-slate-800">Aduan & Eskalasi BK</h3>
                        <div class="flex gap-2">
                            <div class="bg-red-50 text-red-700 px-3 py-1 rounded text-xs font-bold">High: {{ unitStats.bkHigh || 0 }}</div>
                            <div class="bg-amber-50 text-amber-700 px-3 py-1 rounded text-xs font-bold">Medium: {{ unitStats.bkMedium || 0 }}</div>
                        </div>
                    </div>
                    
                    <div v-if="bkCases.length > 0" class="space-y-3">
                        <div v-for="c in bkCases" :key="c.id" class="p-4 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-slate-800">{{ c.title }}</div>
                                    <div class="text-xs text-slate-500">{{ c.student_name }} â€¢ {{ c.class_name }}</div>
                                </div>
                                <span :class="c.severity==='HIGH'?'bg-red-100 text-red-700':(c.severity==='MEDIUM'?'bg-amber-100 text-amber-700':'bg-blue-100 text-blue-700')" class="px-2 py-1 rounded text-[10px] font-bold uppercase">{{ c.severity }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-slate-400">
                                <div>{{ formatDate(c.created_at) }}</div>
                                <div class="font-bold" :class="c.status==='OPEN'?'text-emerald-600':'text-slate-500'">{{ c.status }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-slate-500 text-sm">
                        Belum ada data kasus aktif untuk hari ini.
                    </div>
                </div>

                <div v-if="workspaceTab==='INVENTARIS'" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="font-bold text-slate-800 mb-4">Laporan Inventaris</h3>
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-blue-700">{{ unitStats.inventoryTotal || 0 }}</div>
                            <div class="text-xs text-blue-600">Total Aset</div>
                        </div>
                        <div class="bg-emerald-50 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-emerald-700">{{ (unitStats.inventoryTotal||0) - (unitStats.inventoryBad||0) }}</div> <!-- Assuming good is total - bad -->
                            <div class="text-xs text-emerald-600">Kondisi Baik</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-red-700">{{ unitStats.inventoryBad || 0 }}</div>
                            <div class="text-xs text-red-600">Rusak/Hilang</div>
                        </div>
                    </div>
                    <h4 class="font-bold text-slate-700 mb-3 text-sm">Status Laporan Kelas (Sabtu Pekanan)</h4>
                    <div class="space-y-3">
                        <div v-for="c in unitStats.classes" :key="'inv-'+c.name" class="flex items-center justify-between p-3 border border-slate-100 rounded-xl">
                            <div class="font-bold text-slate-700">{{ c.name }}</div>
                            <span :class="c.inventoryStatus==='Reported' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'" class="px-2 py-1 rounded text-xs font-bold">
                                {{ c.inventoryStatus==='Reported' ? 'Sudah Lapor' : 'Belum Lapor' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="workspaceTab==='TUGAS'" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-slate-800">Manajemen Kunci Kelas (Lock Override)</h3>
                        <button @click="fetchUnitLocks" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1 rounded font-bold"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                    </div>
                     
                     <div v-if="unitLocks.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="l in unitLocks" :key="l.id" class="bg-red-50 border border-red-200 rounded-xl p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-bold text-lg text-slate-800">{{ l.name }}</div>
                                    <div class="bg-red-200 text-red-800 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">Locked</div>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <div v-for="(lock, idx) in l.lock_details" :key="idx" class="text-sm bg-white p-2 rounded border border-red-100">
                                        <div class="font-bold text-red-700 flex items-center gap-2">
                                            <i class="fas fa-lock text-xs"></i> {{ lock.message }}
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1 pl-5">{{ lock.detail }}</div>
                                    </div>
                                    <div v-if="!l.lock_details || l.lock_details.length === 0" class="text-sm bg-white p-2 rounded border border-red-100">
                                        <div class="font-bold text-red-700 flex items-center gap-2">
                                            <i class="fas fa-lock text-xs"></i> Terkunci
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1 pl-5">Kelas ini terkunci karena ada tanggungan.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-red-200">
                                <button @click="handleUnlockClick(l)" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-bold text-sm shadow-sm transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-key"></i> Buka Kunci Kelas Ini
                                </button>
                            </div>
                        </div>
                     </div>
                     
                     <div v-else class="text-center py-12 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-check-circle"></i></div>
                        <h3 class="text-lg font-bold text-slate-700">Semua Kelas Aman</h3>
                        <p class="text-slate-500 text-sm">Tidak ada kelas yang terkunci saat ini.</p>
                    </div>
                </div>
            </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='DASH'" class="space-y-6">
                <!-- Row 1: Key Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1: Attendance Visual -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-4">
                        <div class="relative w-20 h-20 flex-shrink-0">
                            <svg width="80" height="80" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" stroke="#e5e7eb" stroke-width="10" fill="none"></circle>
                                <circle cx="60" cy="60" r="54" :stroke="attendanceRingColor" stroke-width="10" fill="none" stroke-linecap="round" transform="rotate(-90 60 60)" :style="{ strokeDasharray: attendanceRingDasharray, strokeDashoffset: attendanceRingDashoffset }"></circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-base font-bold" :class="attendancePercent>=90 ? 'text-emerald-600' : (attendancePercent>=75 ? 'text-amber-600' : 'text-red-600')">{{ attendancePercent }}%</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Kehadiran Hari Ini</div>
                            <div class="text-xl font-bold text-slate-800 line-clamp-1">{{ className }}</div>
                            <div class="text-xs text-slate-400">{{ dailyDate }}</div>
                        </div>
                    </div>

                    <!-- Card 2: Class Health -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-slate-500">Kondisi Kelas</div>
                            <span :class="classCondition==='Hijau' ? 'bg-emerald-100 text-emerald-700' : (classCondition==='Kuning' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')" class="px-2 py-1 rounded text-xs font-bold">{{ classCondition }}</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <div>
                                <div class="text-2xl font-bold text-slate-800">{{ behaviorPointsAvg }}</div>
                                <div class="text-xs text-slate-500">Poin Perilaku Avg</div>
                            </div>
                            <div class="text-right cursor-pointer hover:opacity-75" @click="showAbsentModal=true">
                                <div class="text-2xl font-bold text-red-600">{{ absentStudents.length }}</div>
                                <div class="text-xs text-slate-500">Siswa Absen</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Issues & Actions -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Pending Issues</div>
                                <div class="text-xl font-bold text-slate-800">{{ pendingIssues.length }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button @click="openDailyVerify()" class="px-3 py-2 rounded bg-slate-800 text-white text-xs font-bold hover:bg-slate-700 transition-colors">Verifikasi Absen</button>
                            <button @click="rollupMonthlyFromDaily()" class="px-3 py-2 rounded bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition-colors">Rekap Bulanan</button>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Attendance Calendar & Top Absentees -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Calendar (Takes 2 columns) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="font-bold text-slate-800">Kalender Presensi</h3>
                                <div class="text-xs text-slate-500">Overview kehadiran bulanan</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <select v-model="monthlyMonth" class="border border-slate-200 rounded px-2 py-1 text-xs outline-none focus:border-indigo-500">
                                    <option v-for="m in 12" :value="m">{{ m }}</option>
                                </select>
                                <input type="number" v-model.number="monthlyYear" class="border border-slate-200 rounded px-2 py-1 text-xs w-16 outline-none focus:border-indigo-500" />
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-2 mb-2 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Sen</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Sel</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Rab</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Kam</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Jum</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Sab</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Min</div>
                        </div>
                        <div class="grid grid-cols-7 gap-2">
                            <div v-for="(d, idx) in monthlyCalendar" :key="idx" class="p-2 rounded-lg border border-slate-100 text-center hover:bg-slate-50 transition-colors" :class="{'opacity-0 pointer-events-none border-transparent': !d.date}">
                                <div class="text-[10px] text-slate-400 mb-1">{{ d.date ? d.date.substring(8,10) : '' }}</div>
                                <div v-if="d.date" class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full" :class="calendarBarClass(d.present_pct)" :style="{ width: (d.present_pct === -1 ? 100 : d.present_pct) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Absentees (Takes 1 column) -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <h3 class="font-bold text-slate-800 mb-4">Perlu Perhatian</h3>
                        <div class="space-y-3">
                            <div v-for="(s, idx) in topAbsentees.slice(0,5)" :key="s.id" class="flex items-center justify-between text-sm group cursor-default">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-600 group-hover:bg-slate-200 transition-colors">{{ idx+1 }}</div>
                                    <span class="truncate text-slate-700 group-hover:text-slate-900 transition-colors">{{ truncateName(s.name) }}</span>
                                </div>
                                <div class="font-bold text-red-600 text-xs bg-red-50 px-2 py-0.5 rounded-full">{{ s.alfa + s.izin + s.sakit }}x</div>
                            </div>
                            <div v-if="topAbsentees.length===0" class="text-xs text-slate-500 text-center py-8 bg-slate-50 rounded-xl border border-slate-100 border-dashed">
                                Data absensi aman.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="dailyVerifyModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 w-full max-w-lg">
                    <div class="text-lg font-bold mb-3">Verifikasi Absensi Hari Ini</div>
                    <div v-if="!attendanceSubmitted" class="mb-3 bg-red-50 text-red-700 p-3 rounded-xl border border-red-100 text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle animate-pulse"></i>
                        <span>Belum divalidasi. Default: Semua Hadir.</span>
                    </div>
                    <div v-else class="mb-3 bg-emerald-50 text-emerald-700 p-3 rounded-xl border border-emerald-100 text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>Sudah divalidasi.</span>
                    </div>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <div v-for="s in classStudents" :key="s.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-slate-800 truncate">{{ truncateName(s.name) }}</div>
                                <div class="text-[10px] text-slate-500 truncate">{{ s.nis || 'NIS' }}</div>
                            </div>
                            <select v-model="s.status" class="border border-slate-300 rounded px-2 py-1 text-sm">
                                <option>Hadir</option>
                                <option>Izin</option>
                                <option>Sakit</option>
                                <option>Alfa</option>
                                <option>Cuti</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button @click="dailyVerifyModal=false" class="px-3 py-2 rounded bg-slate-100 text-slate-700 text-sm">Batal</button>
                        <button @click="saveDailyBatch()" class="px-3 py-2 rounded bg-slate-800 text-white text-sm">{{ attendanceSubmitted ? 'Update' : 'Validasi & Simpan' }}</button>
                    </div>
                </div>
            </div>
            <div v-if="showAbsentModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 w-full max-w-md">
                    <div class="text-lg font-bold mb-3">Daftar Absen Hari Ini</div>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div v-for="s in absentStudents" :key="s.id" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                            <div class="text-sm font-bold text-slate-800">{{ s.name }}</div>
                            <div class="text-[11px] text-slate-500">{{ s.status }}</div>
                        </div>
                        <div v-if="absentStudents.length===0" class="text-xs text-slate-500">Tidak ada siswa absen.</div>
                    </div>
                    <div class="mt-4 text-right">
                        <button @click="showAbsentModal=false" class="px-3 py-2 rounded bg-slate-800 text-white text-sm">Tutup</button>
                    </div>
                </div>
            </div>
            <div v-if="inventoryModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 w-full max-w-md">
                    <div class="text-lg font-bold mb-3">Lapor Kerusakan</div>
                    <div class="space-y-3">
                        <div class="text-sm text-slate-700">{{ inventorySelected ? inventorySelected.name : '' }}</div>
                        <div class="flex items-center gap-2">
                            <input type="number" min="1" v-model.number="inventoryReportCount" class="w-24 border border-slate-300 rounded px-2 py-1 text-sm" />
                            <input type="text" v-model="inventoryReportDesc" placeholder="Deskripsi singkat" class="flex-1 border border-slate-300 rounded px-3 py-1 text-sm" />
                        </div>
                        <input type="text" v-model="inventoryReportPhotoName" placeholder="Nama file foto (dummy)" class="w-full border border-slate-300 rounded px-3 py-1 text-sm" />
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button @click="inventoryModal=false" class="px-3 py-2 rounded bg-slate-100 text-slate-700 text-sm">Batal</button>
                        <button @click="submitInventoryReport()" class="px-3 py-2 rounded bg-slate-800 text-white text-sm">Kirim</button>
                    </div>
                </div>
            </div>
            <div v-if="studentPortfolioModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 w-full max-w-lg">
                    <div class="text-lg font-bold mb-3">Student Portfolio</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-xl p-3 border border-slate-200">
                            <div class="text-[11px] text-slate-500">Akademik</div>
                            <div class="text-sm font-bold text-slate-800">Rata-rata: {{ portfolioStudent.academic_avg }}</div>
                            <div class="text-[10px] text-slate-500">Top: {{ portfolioStudent.academic_top_subject }}</div>
                        </div>
                        <div class="rounded-xl p-3 border border-slate-200">
                            <div class="text-[11px] text-slate-500">Perpus</div>
                            <div class="text-sm font-bold text-slate-800">Pinjaman Aktif: {{ portfolioStudent.library_loans }}</div>
                            <div class="text-[10px] text-slate-500">Telat: {{ portfolioStudent.library_late }}</div>
                        </div>
                        <div class="rounded-xl p-3 border border-slate-200">
                            <div class="text-[11px] text-slate-500">BK</div>
                            <div class="text-sm font-bold text-slate-800">Poin Pelanggaran: {{ portfolioStudent.bk_points }}</div>
                            <div class="text-[10px] text-slate-500">Kasus Aktif: {{ portfolioStudent.bk_active_cases }}</div>
                        </div>
                    </div>
                    <div class="mt-4 text-right">
                        <button @click="studentPortfolioModal=false" class="px-3 py-2 rounded bg-slate-800 text-white text-sm">Tutup</button>
                    </div>
                </div>
            </div>
            <div v-if="monthlyRecapModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 w-full max-w-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-lg font-bold text-slate-800">Validasi Presensi Bulanan</div>
                        <button @click="monthlyRecapModal=false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>
                    
                    <div class="mb-4">
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                            <div>
                                <div class="text-sm font-bold text-blue-800">Tahun Ajaran Aktif</div>
                                <div class="text-xs text-blue-600 leading-relaxed">
                                    Silakan validasi rekap kehadiran siswa setiap akhir bulan. Bulan yang belum divalidasi akan berwarna merah.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-6">
                        <div v-for="m in monthlyRecaps" :key="m.id" 
                             class="relative p-3 rounded-xl border transition-all"
                             :class="m.status === 'validated' ? 'bg-emerald-50 border-emerald-200' : (m.status === 'pending' ? 'bg-white border-red-300 shadow-sm hover:shadow-md cursor-pointer group' : 'bg-slate-50 border-slate-100 opacity-60')"
                             @click="confirmValidateMonth(m)">
                            
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider" :class="m.status==='validated'?'text-emerald-700':(m.status==='pending'?'text-red-700':'text-slate-500')">{{ m.name }}</span>
                                <i v-if="m.status === 'validated'" class="fas fa-check-circle text-emerald-500"></i>
                                <i v-else-if="m.status === 'pending'" class="fas fa-exclamation-circle text-red-500 animate-pulse"></i>
                            </div>
                            
                            <div class="text-[10px]" :class="m.status==='validated'?'text-emerald-600':(m.status==='pending'?'text-red-600':'text-slate-400')">
                                {{ m.status === 'validated' ? 'Sudah Validasi' : (m.status === 'pending' ? 'Belum Validasi' : 'Belum Tersedia') }}
                            </div>
                            
                            <div v-if="m.status === 'pending'" class="absolute inset-0 bg-red-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <button @click="monthlyRecapModal=false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
