<?php
require_once '../../includes/guard.php';
require_login_and_module('library');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perpustakaan - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
    <style>
        [v-cloak] { display: none; }
        .animate-fade { animation: fade 0.5s ease-in-out; }
        @keyframes fade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div id="app" v-cloak class="flex flex-col h-screen">

    <?php require_once '../../includes/library_header.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-8 flex items-center justify-center bg-slate-50 relative">
        <!-- Background Decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
            <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-emerald-100/50 blur-3xl"></div>
            <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-teal-100/50 blur-3xl"></div>
        </div>

        <div class="max-w-7xl w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 relative z-10">
            
            <!-- CARD 1: KATALOG BUKU -->
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="catalog.php" class="block">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <i class="fas fa-swatchbook"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Katalog</h3>
                        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Manajemen koleksi buku, e-book, dan kategori pustaka.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="catalog.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-list w-5 text-center"></i> Daftar Buku
                        </a>
                        <a href="../procurement/submit.php?module=LIBRARY&label=Buku/Pustaka" class="flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700 transition-colors mt-1 pt-1 border-t border-slate-50">
                            <i class="fas fa-shopping-cart w-5 text-center"></i> Procurement
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD 2: KUNJUNGAN KELAS -->
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="visits.php" class="block">
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Kunjungan</h3>
                        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Pendataan kunjungan kelas dan buku yang dibaca siswa.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="visits.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fas fa-calendar-check w-5 text-center"></i> Log Kunjungan
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD 3: JADWAL KUNJUNGAN -->
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="schedule.php" class="block">
                        <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Jadwal</h3>
                        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Atur jadwal rutin kunjungan perpustakaan per kelas.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="schedule.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                            <i class="fas fa-table w-5 text-center"></i> Jadwal Mingguan
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD 4: KEANGGOTAAN -->
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="members.php" class="block">
                        <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Anggota</h3>
                        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Data anggota perpustakaan, kartu anggota, dan riwayat.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="members.php" class="flex items-center text-xs font-medium text-slate-500 hover:text-purple-600 transition-colors">
                            <i class="fas fa-user-friends w-5 text-center"></i> Daftar Anggota
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD 5: LAPORAN & SIRKULASI -->
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 flex flex-col h-full">
                    <a href="reports.php" class="block">
                        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-red-600 group-hover:text-white transition-colors">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Sirkulasi</h3>
                        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Peminjaman, pengembalian buku, dan denda.</p>
                    </a>
                    <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                        <a href="reports.php?action=loans" class="flex items-center text-xs font-medium text-slate-500 hover:text-red-600 transition-colors">
                            <i class="fas fa-chart-line w-5 text-center"></i> Laporan
                        </a>
                    </div>
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
                // Dashboard Logic
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        }
    }).mount('#app')
</script>
</body>
</html>
