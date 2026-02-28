<div v-if="currentPosition === 'guru'">
    <!-- Mobile View -->
    <div class="md:hidden">
        <!-- Name Card -->
        <div class="mb-4">
            <div class="bg-gradient-to-r from-indigo-600 to-blue-500 rounded-xl shadow-md p-4 flex items-center gap-4 text-white relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-xl"></div>
                
                <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center text-white text-2xl backdrop-blur-sm border border-white/30 shadow-sm flex-shrink-0">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-indigo-100 font-medium uppercase tracking-wider mb-0.5">Selamat Datang,</div>
                    <div class="font-bold text-lg leading-tight truncate"><?php echo htmlspecialchars($__displayName); ?></div>
                    <div class="flex items-center gap-2 mt-2 text-xs font-medium text-indigo-50">
                        <span class="bg-white/20 px-2 py-0.5 rounded backdrop-blur-sm"><i class="far fa-calendar-alt mr-1"></i> {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                        <span class="bg-white/20 px-2 py-0.5 rounded backdrop-blur-sm"><i class="far fa-clock mr-1"></i> {{ currentTime }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Timeline (Horizontal Scroll) -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2 px-1">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Timeline Jadwal</h3>
                <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded-full font-bold">{{ teacherTodaySchedule.length }} Kelas Hari Ini</span>
            </div>
            <!-- Timeline Container -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-2">
                <div ref="timelineContainer" class="flex items-center overflow-x-auto gap-2 py-2 px-1 scrollbar-hide snap-x">
                    <!-- Hours 07:00 to 16:00 -->
                    <div v-for="h in 10" :key="h" 
                         :id="'time-slot-' + (h+6)"
                         class="flex-shrink-0 w-16 flex flex-col items-center justify-center py-3 rounded-lg transition-all snap-center relative border"
                         :class="isCurrentHour(h+6) ? 'bg-indigo-600 text-white border-indigo-600 shadow-md ring-2 ring-offset-1 ring-indigo-200' : 'bg-white border-slate-100 text-slate-400'">
                        
                        <span class="text-sm font-bold tracking-tight">{{ (h+6).toString().padStart(2, '0') }}.00</span>
                        
                        <!-- Dot if class exists in this hour -->
                        <div v-if="hasScheduleAt(h+6)" 
                             class="mt-1.5 w-1.5 h-1.5 rounded-full"
                             :class="isCurrentHour(h+6) ? 'bg-white' : 'bg-indigo-500'">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2x2 Menu Grid -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <button @click="teacherTab='JADWAL'" class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform h-28 relative overflow-hidden group">
                <div class="absolute inset-0 bg-blue-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-lg relative z-10 shadow-sm">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span class="font-bold text-slate-700 text-xs relative z-10 text-center leading-tight">Jadwal<br>Pelajaran</span>
            </button>
            
            <button @click="teacherTab='MAPEL'" class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform h-28 relative overflow-hidden group">
                <div class="absolute inset-0 bg-emerald-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-lg relative z-10 shadow-sm">
                    <i class="fas fa-book"></i>
                </div>
                <span class="font-bold text-slate-700 text-xs relative z-10 text-center leading-tight">Daftar<br>Mapel</span>
            </button>
            
            <button @click="teacherTab='INVENTARIS'" class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform h-28 relative overflow-hidden group">
                <div class="absolute inset-0 bg-amber-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-lg relative z-10 shadow-sm">
                    <i class="fas fa-box-open"></i>
                </div>
                <span class="font-bold text-slate-700 text-xs relative z-10 text-center leading-tight">Inventaris<br>Pribadi</span>
            </button>
            
            <button @click="teacherTab='TUGAS'" class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform h-28 relative overflow-hidden group">
                <div class="absolute inset-0 bg-rose-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-10 h-10 bg-rose-100 text-red-600 rounded-full flex items-center justify-center text-lg relative z-10 shadow-sm">
                    <i class="fas fa-tasks"></i>
                </div>
                <span class="font-bold text-slate-700 text-xs relative z-10 text-center leading-tight">Tugas<br>Saya</span>
            </button>
        </div>

        <!-- Mobile Tab Content (Full Screen Overlay style or Inline) -->
        <div v-if="teacherTab !== 'DASHBOARD'" class="fixed inset-0 bg-slate-50 z-50 overflow-y-auto">
            <!-- Mobile Header for Sub-pages -->
            <div class="bg-white px-4 py-3 flex items-center gap-3 border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <button @click="teacherTab='DASHBOARD'" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-600">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h2 class="font-bold text-lg text-slate-800">
                    {{ teacherTab === 'JADWAL' ? 'Jadwal Pelajaran' : 
                       teacherTab === 'MAPEL' ? 'Daftar Mapel' : 
                       teacherTab === 'INVENTARIS' ? 'Inventaris Pribadi' : 'Tugas Saya' }}
                </h2>
            </div>
            
            <div class="p-4 pb-20">
                <!-- Reusing the desktop content sections but ensuring they render nicely -->
                <div v-if="teacherTab==='JADWAL'">
                    <div class="space-y-4">
                         <div v-for="s in teacherScheduleSorted" :key="s.id" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                             <div class="flex justify-between items-start mb-2">
                                 <span class="font-bold text-slate-800 text-lg">{{ s.subject }}</span>
                                 <span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded font-bold">{{ s.time }}</span>
                             </div>
                             <div class="flex items-center gap-2 text-sm text-slate-500 mb-3">
                                <i class="fas fa-calendar-day text-slate-400"></i> {{ s.day }}
                             </div>
                             <div class="flex justify-between items-center pt-3 border-t border-slate-50">
                                 <div class="text-sm font-medium text-slate-700">{{ s.class }}</div>
                                 <div class="text-xs text-slate-400">{{ s.room }}</div>
                             </div>
                         </div>
                    </div>
                </div>

                <div v-if="teacherTab==='MAPEL'">
                     <div class="grid grid-cols-1 gap-4">
                        <div v-for="m in teacherSubjects" :key="m.id" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas fa-book"></i>
                                </div>
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">{{ m.students }} Siswa</span>
                            </div>
                            <h3 class="font-bold text-slate-800 mb-2">{{ m.name }}</h3>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="c in m.classes" class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-medium">{{ c }}</span>
                            </div>
                        </div>
                     </div>
                </div>

                <div v-if="teacherTab==='INVENTARIS'">
                     <div class="space-y-3">
                        <div v-for="i in teacherInventory" :key="i.id" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex justify-between items-center">
                            <div>
                                <div class="font-bold text-slate-800">{{ i.name }}</div>
                                <div class="text-xs text-slate-500">{{ i.status }}</div>
                            </div>
                            <span :class="i.condition==='Baik'?'text-green-600 bg-green-50':'text-red-600 bg-red-50'" class="px-2 py-1 rounded text-xs font-bold">{{ i.condition }}</span>
                        </div>
                     </div>
                </div>

                <div v-if="teacherTab==='TUGAS'">
                    <div class="space-y-3">
                        <div v-for="t in teacherTasks" :key="t.id" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                            <div class="flex items-center gap-3 mb-3">
                                <div :class="t.status==='Done'?'bg-green-100 text-green-600':'bg-orange-100 text-orange-600'" class="w-8 h-8 rounded-full flex items-center justify-center">
                                    <i :class="t.status==='Done'?'fas fa-check':'fas fa-clock'"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-slate-800 text-sm">{{ t.title }}</div>
                                    <div class="text-xs text-slate-500">Due: {{ t.due }}</div>
                                </div>
                            </div>
                            <button v-if="t.status!=='Done'" class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700">Kerjakan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop View (Hidden on Mobile) -->
    <div class="hidden md:block">
    <!-- Header Area (optional, since Navbar already has info) -->
    
    <!-- Tab Navigation -->
    <div class="flex gap-4 border-b border-slate-200 mb-6 overflow-x-auto">
        <button @click="teacherTab='DASHBOARD'" :class="teacherTab==='DASHBOARD' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 px-1 border-b-2 font-bold transition-colors whitespace-nowrap">Dashboard</button>
        <button @click="teacherTab='JADWAL'" :class="teacherTab==='JADWAL' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 px-1 border-b-2 font-bold transition-colors whitespace-nowrap">Jadwal Pelajaran</button>
        <button @click="teacherTab='MAPEL'" :class="teacherTab==='MAPEL' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 px-1 border-b-2 font-bold transition-colors whitespace-nowrap">Daftar Mapel</button>
        <button @click="teacherTab='INVENTARIS'" :class="teacherTab==='INVENTARIS' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 px-1 border-b-2 font-bold transition-colors whitespace-nowrap">Inventaris Pribadi</button>
        <button @click="teacherTab='TUGAS'" :class="teacherTab==='TUGAS' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="pb-3 px-1 border-b-2 font-bold transition-colors whitespace-nowrap">Tugas Saya</button>
    </div>

    <!-- Dashboard Content -->
    <div v-if="teacherTab==='DASHBOARD'">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-sm mb-1">Total Jam Mengajar</div>
                <div class="text-2xl font-bold text-slate-800">{{ teacherStats.totalJP }} JP</div>
            </div>
             <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-sm mb-1">Total Pelajaran Diampu</div>
                <div class="text-2xl font-bold text-slate-800">{{ teacherStats.totalSubjects }}</div>
            </div>
             <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-sm mb-1">Tugas Pending</div>
                <div class="text-2xl font-bold text-orange-600">{{ teacherTasks.filter(t=>t.status!=='Done').length }}</div>
            </div>
             <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="text-slate-500 text-sm mb-1">Inventaris</div>
                <div class="text-2xl font-bold text-slate-800">{{ teacherInventory.length }} Item</div>
            </div>
        </div>
        
        <!-- Today's Schedule -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Jadwal Hari Ini ({{ new Date().toLocaleDateString('id-ID', { weekday: 'long' }) }})</h3>
            <div v-if="teacherTodaySchedule.length === 0" class="text-slate-500 text-center py-8">Tidak ada jadwal hari ini.</div>
            <div v-else class="space-y-3">
                <div v-for="s in teacherTodaySchedule" :key="s.id" class="flex items-center p-3 rounded-lg border border-slate-100 hover:bg-slate-50 transition-colors">
                    <div class="w-32 font-mono text-sm font-bold text-indigo-600">{{ s.time }}</div>
                    <div class="flex-1 ml-4">
                        <div class="font-bold text-slate-800">{{ s.subject }}</div>
                        <div class="text-sm text-slate-500">{{ s.class }} · {{ s.room }}</div>
                    </div>
                    <button class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded text-xs font-bold hover:bg-indigo-100">Jurnal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Jadwal Content -->
    <div v-if="teacherTab==='JADWAL'">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
             <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Hari</th>
                        <th class="p-4">Waktu</th>
                        <th class="p-4">Kelas</th>
                        <th class="p-4">Mata Pelajaran</th>
                        <th class="p-4">Ruangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="s in teacherScheduleSorted" :key="s.id" class="hover:bg-slate-50">
                        <td class="p-4 font-medium">{{ s.day }}</td>
                        <td class="p-4 font-mono text-slate-600">{{ s.time }}</td>
                        <td class="p-4"><span class="bg-slate-100 px-2 py-1 rounded text-xs font-bold text-slate-700">{{ s.class }}</span></td>
                        <td class="p-4 font-bold text-indigo-600">{{ s.subject }}</td>
                        <td class="p-4 text-slate-500">{{ s.room }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mapel Content -->
    <div v-if="teacherTab==='MAPEL'">
         <div class="mb-4">
             <a :href="'../../modules/academic/subjects.php?unit=' + currentUnit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-indigo-700 inline-flex items-center gap-2">
                 <i class="fas fa-edit"></i> Kelola Mata Pelajaran (Multi Unit)
             </a>
         </div>
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="m in teacherSubjects" :key="m.id" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl">
                        <i class="fas fa-book"></i>
                    </div>
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">{{ m.students }} Siswa</span>
                </div>
                <h3 class="font-bold text-lg text-slate-800 mb-1">{{ m.name }}</h3>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span v-for="c in m.classes" class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-medium">{{ c }}</span>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 flex gap-2">
                    <button class="flex-1 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700">Perangkat Ajar</button>
                    <button class="flex-1 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50">Nilai</button>
                </div>
            </div>
         </div>
    </div>

    <!-- Inventaris Content -->
    <div v-if="teacherTab==='INVENTARIS'">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
             <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Nama Barang</th>
                        <th class="p-4">Kondisi</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="i in teacherInventory" :key="i.id" class="hover:bg-slate-50">
                        <td class="p-4 font-bold text-slate-800">{{ i.name }}</td>
                        <td class="p-4">
                            <span :class="i.condition==='Baik'?'text-green-600 bg-green-50':'text-red-600 bg-red-50'" class="px-2 py-1 rounded text-xs font-bold">{{ i.condition }}</span>
                        </td>
                        <td class="p-4 text-slate-500">{{ i.status }}</td>
                        <td class="p-4 text-right">
                            <button class="text-indigo-600 hover:text-indigo-800 font-bold text-xs">Lapor Kerusakan</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tugas Content -->
    <div v-if="teacherTab==='TUGAS'">
        <div class="space-y-4">
            <div v-for="t in teacherTasks" :key="t.id" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div :class="t.status==='Done'?'bg-green-100 text-green-600':'bg-orange-100 text-orange-600'" class="w-10 h-10 rounded-full flex items-center justify-center text-lg">
                        <i :class="t.status==='Done'?'fas fa-check':'fas fa-clock'"></i>
                    </div>
                    <div>
                        <div class="font-bold text-slate-800">{{ t.title }}</div>
                        <div class="text-xs text-slate-500">Tenggat: {{ t.due }}</div>
                    </div>
                </div>
                <div>
                    <button v-if="t.status!=='Done'" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700">Kerjakan</button>
                    <span v-else class="text-green-600 font-bold text-sm">Selesai</span>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>