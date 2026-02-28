<?php
// VIEW: DASHBOARD CARDS GRID
// Extracted from index.php
?>
<!-- SECTION 1: PERENCANAAN AKADEMIK -->
<div class="mb-8">
    <h2 class="text-lg font-bold text-slate-700 mb-4 flex items-center gap-2">
        <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
        Perencanaan Akademik
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-blue-50 rounded-bl-[80px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Pengaturan</h3>
                <p class="text-xs text-slate-500 mb-3">Sub menu konfigurasi akademik</p>
                <div class="space-y-2 mt-auto">
                    <a href="years.php" class="flex items-center text-xs font-medium text-slate-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-calendar-check w-5 text-center"></i> Tahun Ajaran
                    </a>
                    <a href="references.php" class="flex items-center text-xs font-medium text-slate-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-sliders-h w-5 text-center"></i> Pengaturan & Referensi
                    </a>
                    <a href="activity_log.php" class="flex items-center text-xs font-medium text-slate-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-clipboard-list w-5 text-center"></i> Log Aktivitas
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-50 rounded-bl-[80px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <i class="fas fa-chalkboard"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Data Kelas</h3>
                <p class="text-xs text-slate-500 mb-3">Rombel & Kurikulum</p>
                <div class="space-y-2 mt-auto">
                    <a href="classes.php" class="flex items-center text-xs font-medium text-slate-600 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-chalkboard w-5 text-center"></i> Manajemen Kelas
                    </a>
                    <a href="subjects.php" class="flex items-center text-xs font-medium text-slate-600 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-list-ul w-5 text-center"></i> List Mapel
                    </a>
                </div>
            </div>
        </div>

        <a href="school_agenda.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-purple-50 rounded-bl-[80px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full items-center text-center">
                <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Agenda Sekolah</h3>
                <p class="text-xs text-slate-500">Kalender Kegiatan & Google Sync</p>
            </div>
        </a>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-rose-50 rounded-bl-[80px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-rose-600 group-hover:text-white transition-colors">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">PSB (Admisi)</h3>
                <p class="text-xs text-slate-500 mb-3">Penerimaan Siswa Baru</p>
                <div class="space-y-2 mt-auto">
                    <a href="psb.php" class="flex items-center text-xs font-medium text-slate-600 hover:text-rose-600 transition-colors">
                        <i class="fas fa-user-plus w-5 text-center"></i> Buka PSB
                    </a>
                    <a href="psb.php#settings-psb" class="flex items-center text-xs font-medium text-slate-600 hover:text-rose-600 transition-colors">
                        <i class="fas fa-sliders-h w-5 text-center"></i> Setting PSB (Kategori & Kuota)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 2: PELAKSANAAN KBM -->
<div>
    <h2 class="text-lg font-bold text-slate-700 mb-4 flex items-center gap-2">
        <span class="w-1 h-6 bg-teal-600 rounded-full"></span>
        Pelaksanaan & Evaluasi
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden group hover:shadow-md transition-all">
            <div class="p-6">
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center text-teal-600 mb-4 group-hover:scale-110 transition-transform">
                    <i class="far fa-calendar-alt text-xl"></i>
                </div>
                <h3 class="font-bold text-slate-800 mb-1">Jadwal Pelajaran</h3>
                <p class="text-xs text-slate-500 mb-4">Distribusi Kelas</p>
                <a href="schedule.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-teal-600 transition-colors">
                    <i class="fas fa-calendar-alt w-5 text-center"></i> Buka Jadwal
                </a>
                <a href="time_slots.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-teal-600 transition-colors mt-1">
                    <i class="fas fa-clock w-5 text-center"></i> Setting Jam Mapel
                </a>
            </div>
        </div>

        <!-- CARD: JADWAL GURU -->
        <a href="teacher_schedule.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-50 rounded-bl-[80px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full items-center text-center">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Jadwal Guru</h3>
                <p class="text-xs text-slate-500">Lihat Jadwal Mengajar</p>
            </div>
        </a>

        <!-- CARD: PRESENSI -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden group hover:shadow-md transition-all">
            <div class="p-6">
                <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center text-pink-600 mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-check text-xl"></i>
                </div>
                <h3 class="font-bold text-slate-800 mb-1">Presensi Harian</h3>
                <p class="text-xs text-slate-500 mb-4">Input & Rekap Kehadiran Siswa Bulanan.</p>
                <a href="attendance_batch.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-pink-600 transition-colors">
                    <i class="fas fa-plus-circle w-5 text-center"></i> Input Cepat (Batch)
                </a>
                <a href="attendance_summary.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-pink-600 transition-colors mt-1">
                    <i class="fas fa-list-alt w-5 text-center"></i> Rekap Bulanan
                </a>
            </div>
        </div>

        <!-- CARD: E-RAPORT -->
        <a href="#" class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-orange-50 rounded-bl-[80px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full items-center text-center">
                <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-2xl mb-4 shadow-sm group-hover:bg-orange-100 group-hover:text-white transition-colors">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">e-Raport</h3>
                <p class="text-xs text-slate-500">Penilaian Akhir</p>
            </div>
        </a>
    </div>
</div>
