<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-white shadow-lg">
            <i class="fas fa-chalkboard-teacher text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Workspace</h1>
            <span class="text-xs text-slate-500 font-medium">Akademik & Kesiswaan</span>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Peran:</span>
            <div class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
                <button v-if="allowedPositions.kepala" @click="currentPosition='kepala'" :class="positionClass('kepala')" class="px-3 py-1 rounded text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all" :disabled="isPositionLocked('kepala')">Kepala Sekolah</button>
                <button v-if="allowedPositions.wakasek" @click="currentPosition='wakasek'" :class="positionClass('wakasek')" class="px-3 py-1 rounded text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all" :disabled="isPositionLocked('wakasek')">Wakasek</button>
                <button v-if="allowedPositions.wali" @click="currentPosition='wali'" :class="positionClass('wali')" class="px-3 py-1 rounded text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all" :disabled="isPositionLocked('wali')">Wali Kelas</button>
                <button v-if="allowedPositions.guru" @click="currentPosition='guru'" :class="positionClass('guru')" class="px-3 py-1 rounded text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all" :disabled="isPositionLocked('guru')">Guru</button>
            </div>
        </div>

        <!-- Unit Selector Removed as requested -->
        <!-- 
        <div class="h-6 w-px bg-slate-200"></div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Unit:</span>
            <div class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
                <template v-for="u in availableUnits" :key="u.code">
                    <button 
                        @click="setUnit(u.code.toLowerCase())" 
                        :class="unitClass(u.code.toLowerCase())" 
                        :disabled="isUnitLocked(u.code.toLowerCase())" 
                        class="px-3 py-1 rounded text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        {{ u.code.toUpperCase() }}
                    </button>
                </template>
                <button 
                    v-if="!availableUnits.find(u => u.code.toLowerCase() === 'asrama')"
                    @click="setUnit('asrama')" 
                    :class="unitClass('asrama')" 
                    :disabled="isUnitLocked('asrama')" 
                    class="px-3 py-1 rounded text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    ASRAMA
                </button>
            </div>
        </div> 
        -->
        <div v-if="showWaliClassSelector" class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
            <select v-model="selectedClassId" @change="applySelectedClass" class="px-3 py-1 rounded text-xs font-bold bg-white border border-slate-300">
                <option value="">Pilih Kelas</option>
                <option v-for="c in classesOptions" :value="c.id">{{ c.name }}</option>
            </select>
        </div>
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentTime }}</span>
        <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>
