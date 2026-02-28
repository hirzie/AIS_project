<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<script>
    window.USE_GLOBAL_APP = false;
    window.SKIP_GLOBAL_APP = true;
</script>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>
    
    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Tahun Ajaran</h2>
                <div class="text-sm text-slate-500">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold text-xs"><i class="fas fa-sync-alt mr-1"></i> Auto-Managed</span>
                </div>
            </div>
            
            <div v-if="yearLoading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
                <p class="mt-2 text-slate-500">Sinkronisasi Tahun Ajaran...</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Existing Years -->
                <div v-for="year in years" :key="year.id" 
                     class="rounded-xl shadow-sm border p-6 relative overflow-hidden h-48 transition-all"
                     :class="year.status === 'ACTIVE' ? 'bg-white border-blue-200 ring-2 ring-blue-100' : 'bg-slate-50 border-slate-200 opacity-80 hover:opacity-100'">
                    
                    <div class="absolute top-0 right-0 text-[10px] font-bold px-3 py-1 rounded-bl-lg uppercase shadow-sm"
                         :class="year.status === 'ACTIVE' ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-500'">
                        {{ year.status === 'ACTIVE' ? 'Aktif' : 'Arsip' }}
                    </div>

                    <h3 class="text-2xl font-bold mb-1" :class="year.status === 'ACTIVE' ? 'text-blue-700' : 'text-slate-700'">{{ year.name }}</h3>
                    <p class="text-sm mb-4 font-medium" :class="year.status === 'ACTIVE' ? 'text-blue-500' : 'text-slate-500'">
                        Semester {{ year.semester_active }}
                    </p>
                    
                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between">
                            <span>Mulai:</span>
                            <span class="font-mono font-bold">{{ formatDate(year.start_date) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Selesai:</span>
                            <span class="font-mono font-bold">{{ formatDate(year.end_date) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script type="module">
    import { academicMixin } from '../../assets/js/modules/academic.js';
    import { adminMixin } from '../../assets/js/modules/admin.js';
    const { createApp } = Vue;
    const app = createApp({
        mixins: [academicMixin],
        computed: {
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            }
        },
        async mounted() {
            try {
                await this.fetchAllUnits();
                if (this.syncYears) await this.syncYears();
            } catch (e) {
                console.error('Init Academic Years error:', e);
            }
        }
    });
    app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
