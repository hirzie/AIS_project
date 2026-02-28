<?php
require_once '../../config/database.php';
require_once '../../includes/header_personnel.php';
?>

<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
            <i class="fas fa-users text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Kepegawaian</h1>
            <span class="text-xs text-slate-500 font-medium">Personnel Dashboard</span>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <div class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200" v-if="availableUnits && availableUnits.length > 0">
            <button @click="currentUnit = 'all'" :class="currentUnit === 'all' ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-xs transition-all">Semua</button>
            <button v-for="unit in availableUnits" :key="unit.id" @click="currentUnit = unit.unit_level" :class="currentUnit === unit.unit_level ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-xs transition-all">{{ (unit.prefix || unit.name).substring(0, 7) }}</button>
        </div>

        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
        <button onclick="window.location.href='<?php echo $baseUrl; ?>index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>

<main class="flex-1 overflow-y-auto p-8 flex items-center justify-center bg-slate-50 relative">
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
        <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-indigo-100/50 blur-3xl"></div>
        <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-blue-100/50 blur-3xl"></div>
    </div>

    <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
        
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/employees.php" class="block">
                    <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Data Pegawai</h3>
                    <p class="text-sm text-slate-500 mb-4">Kelola data guru, staf, dan karyawan sekolah.</p>
                </a>
                
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/personnel/employees.php?filter=active" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-user-check w-5 text-center"></i> Pegawai Aktif
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/personnel/employees.php?filter=teacher" class="flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-chalkboard-teacher w-5 text-center"></i> Data Guru
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/organization.php" class="block">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Struktur Organisasi</h3>
                    <p class="text-sm text-slate-500 mb-4">Manajemen jabatan dan hierarki organisasi sekolah.</p>
                </a>
                
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/personnel/organization.php?tab=positions" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-briefcase w-5 text-center"></i> Jabatan
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/attendance.php" class="block">
                    <div class="w-14 h-14 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-green-600 group-hover:text-white transition-colors">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Absensi</h3>
                    <p class="text-sm text-slate-500 mb-4">Rekap kehadiran, jam masuk/pulang, dan lembur.</p>
                </a>
                
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/personnel/attendance.php?view=daily" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                        <i class="fas fa-calendar-day w-5 text-center"></i> Absensi Harian
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/personnel/attendance.php?view=report" class="flex items-center text-xs font-medium text-slate-500 hover:text-green-600 transition-colors">
                        <i class="fas fa-file-alt w-5 text-center"></i> Laporan Bulanan
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/leave.php" class="block">
                    <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition-colors">
                        <i class="fas fa-plane-departure"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Cuti & Izin</h3>
                    <p class="text-sm text-slate-500 mb-4">Pengajuan dan persetujuan cuti pegawai.</p>
                </a>
                
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/personnel/leave.php?status=pending" class="flex items-center text-xs font-medium text-slate-500 hover:text-orange-600 transition-colors">
                        <i class="fas fa-hourglass-half w-5 text-center"></i> Menunggu Persetujuan
                    </a>
                    <a href="<?php echo $baseUrl; ?>modules/personnel/leave.php?status=history" class="flex items-center text-xs font-medium text-slate-500 hover:text-orange-600 transition-colors">
                        <i class="fas fa-history w-5 text-center"></i> Riwayat Cuti
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/payroll.php" class="block">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Penggajian</h3>
                    <p class="text-sm text-slate-500 mb-4">Slip gaji, tunjangan, dan potongan.</p>
                </a>
                
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="<?php echo $baseUrl; ?>modules/personnel/payroll.php?view=slip" class="flex items-center text-xs font-medium text-slate-500 hover:text-purple-600 transition-colors">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i> Slip Gaji
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-slate-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/performance.php" class="block">
                    <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Kinerja & Pelatihan</h3>
                    <p class="text-sm text-slate-500 mb-4">Penilaian kinerja (KPI) dan riwayat pelatihan.</p>
                </a>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-pink-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/employment_status.php" class="block">
                    <div class="w-14 h-14 bg-pink-100 text-pink-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-pink-600 group-hover:text-white transition-colors">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Status Kepegawaian</h3>
                    <p class="text-sm text-slate-500 mb-4">Manajemen referensi status pegawai (Tetap, Kontrak, dll).</p>
                </a>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-cyan-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="<?php echo $baseUrl; ?>modules/personnel/positions.php" class="block">
                    <div class="w-14 h-14 bg-cyan-100 text-cyan-600 rounded-xl flex items_center justify-center text-2xl mb-6 shadow-sm group-hover:bg-cyan-600 group-hover:text-white transition-colors">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Manajemen Jabatan</h3>
                    <p class="text-sm text-slate-500 mb-4">Atur penempatan pegawai dalam jabatan dan SK.</p>
                </a>
            </div>
        </div>
    </div>
</main>
</div>

<script>
    const { createApp } = Vue
    createApp({
        data() {
            return {
                availableUnits: [],
                currentUnit: 'all'
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        },
        mounted() {
            fetch((window.BASE_URL || '/') + 'api/get_units.php')
                .then(r => r.json())
                .then(d => { this.availableUnits = d || []; })
                .catch(() => { this.availableUnits = []; });
        }
    }).mount('#app')
</script>
</body>
</html>
