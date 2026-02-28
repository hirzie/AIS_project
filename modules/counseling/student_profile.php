<?php
require_once '../../includes/guard.php';
ais_init_session();
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Siswa - BK & Kesiswaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
    <style>
        [v-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .animate-fade { animation: fade 0.3s ease-out; }
        @keyframes fade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div id="app" v-cloak class="flex flex-col h-screen">

    <?php require_once '../../includes/counseling_header.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- LOADING STATE -->
            <div v-if="loading && !selectedStudent" class="flex flex-col items-center justify-center min-h-[400px] animate-fade">
                <div class="w-16 h-16 border-4 border-pink-100 border-t-pink-600 rounded-full animate-spin mb-4"></div>
                <div class="text-slate-500 font-medium">Mencari data siswa...</div>
            </div>

            <!-- NOT FOUND STATE -->
            <div v-if="!loading && !selectedStudent" class="flex flex-col items-center justify-center min-h-[400px] animate-fade text-center">
                <div class="w-20 h-20 bg-slate-100 text-slate-300 rounded-3xl flex items-center justify-center text-3xl mb-4">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Siswa Tidak Ditemukan</h2>
                <p class="text-slate-500 mb-6">Maaf, data siswa dengan NIS tersebut tidak ditemukan atau belum terdaftar.</p>
                <a href="index.php" class="bg-pink-600 text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-pink-200 hover:bg-pink-700 transition-all">
                    Kembali ke Dashboard
                </a>
                
                <div class="mt-8 w-full max-w-xl text-left">
                    <h3 class="text-sm font-bold text-slate-700 mb-2">Cari Siswa</h3>
                    <div class="relative">
                        <input type="text" v-model="searchQuery" @input="searchStudents" @keyup.enter="doSearch" placeholder="Ketik Nama atau NIS siswa..." 
                               class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:ring-4 focus:ring-pink-100 focus:border-pink-500 outline-none transition-all pr-12 shadow-inner">
                        <button @click="doSearch" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-pink-600 text-white rounded-xl flex items-center justify-center hover:bg-pink-700 transition-colors shadow-lg shadow-pink-200">
                            <i class="fas fa-search text-sm"></i>
                        </button>
                    </div>
                    <div v-if="searchResults.length > 0" class="mt-3 bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <div v-for="s in searchResults" :key="s.id" 
                             class="px-4 py-3 hover:bg-slate-50 cursor-pointer flex items-center justify-between"
                             @click="selectStudent(s)">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">{{ s.name }}</div>
                                <div class="text-[11px] text-slate-500">NIS: {{ s.identity_number }} • {{ s.class_name || '-' }}</div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROFILE VIEW -->
            <div v-if="selectedStudent" class="animate-fade">
                <!-- Header Profile -->
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 mb-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-pink-50 rounded-bl-[200px] -mr-16 -mt-16 opacity-50"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                        <div class="w-32 h-32 bg-slate-100 rounded-3xl border-4 border-white shadow-lg flex items-center justify-center text-slate-300 text-5xl">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <div class="flex flex-col md:flex-row md:items-center gap-2 mb-2">
                                <h2 class="text-3xl font-bold text-slate-800">{{ profile.basic.name }}</h2>
                                <span class="bg-pink-100 text-pink-700 px-3 py-1 rounded-full text-xs font-bold uppercase">{{ profile.basic.identity_number }}</span>
                            </div>
                            <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-slate-500">
                                <div class="flex items-center gap-2"><i class="fas fa-school text-pink-400"></i> {{ profile.basic.unit_name || '-' }}</div>
                                <div class="flex items-center gap-2"><i class="fas fa-chalkboard text-pink-400"></i> {{ profile.basic.class_name || '-' }}</div>
                                <div class="flex items-center gap-2"><i class="fas fa-user-tie text-pink-400"></i> Wali: {{ profile.basic.teacher_name || '-' }}</div>
                            </div>
                        </div>
                        <a href="index.php" class="bg-slate-100 text-slate-500 px-6 py-2 rounded-xl text-sm font-bold hover:bg-slate-200 transition-all">
                            <i class="fas fa-search mr-2"></i> Cari Lainnya
                        </a>
                    </div>
                </div>

                <!-- Tabs & Content -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Tab Navigation -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-2 space-y-1 sticky top-6">
                            <button @click="activeTab = 'academic'" :class="activeTab === 'academic' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-graduation-cap w-5"></i> Data Akademik
                            </button>
                            <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-calendar-alt w-5"></i> Jadwal Pelajaran
                            </button>
                            <button @click="activeTab = 'library'" :class="activeTab === 'library' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-book-reader w-5"></i> Kunjungan Perpus
                            </button>
                            <button @click="activeTab = 'cases'" :class="activeTab === 'cases' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-book-dead w-5"></i> Buku Kasus
                            </button>
                            <button @click="activeTab = 'counseling'" :class="activeTab === 'counseling' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-comments w-5"></i> Konseling
                            </button>
                            <button @click="activeTab = 'achievements'" :class="activeTab === 'achievements' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-trophy w-5"></i> Prestasi
                            </button>
                            <button @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-clipboard-check w-5"></i> Rekap Presensi
                            </button>
                            <button v-if="profile.boarding" @click="activeTab = 'boarding'" :class="activeTab === 'boarding' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                                <i class="fas fa-home w-5"></i> Data Asrama
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="lg:col-span-3">
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 min-h-[500px]">
                            
                            <!-- CONTENT: ACADEMIC -->
                            <div v-if="activeTab === 'academic'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Biodata & Informasi Akademik</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 mb-8">
                                    <!-- Personal Info -->
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-bold text-pink-600 uppercase tracking-widest border-b border-pink-100 pb-2 flex items-center gap-2">
                                            <i class="fas fa-user-circle"></i> Data Pribadi
                                        </h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="col-span-2">
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nama Lengkap</label>
                                                <div class="text-slate-800 font-bold text-lg">{{ profile.basic.name || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">NIS / ID</label>
                                                <div class="text-slate-700 font-mono font-bold">{{ profile.basic.identity_number || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">NISN / NIK</label>
                                                <div class="text-slate-700 font-medium text-xs">{{ profile.basic.nisn || '-' }} / {{ profile.basic.nik || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jenis Kelamin</label>
                                                <div class="text-slate-700 font-medium">{{ profile.basic.gender === 'L' ? 'Laki-laki' : (profile.basic.gender === 'P' ? 'Perempuan' : '-') }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tempat, Tgl Lahir</label>
                                                <div class="text-slate-700 font-medium text-xs">{{ profile.basic.birth_place || '-' }}, {{ profile.basic.birth_date ? formatDate(profile.basic.birth_date) : '-' }}</div>
                                            </div>
                                            <div class="col-span-2 pt-2 border-t border-slate-50">
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nama Orang Tua</label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <span class="text-[9px] text-slate-400 block">Ayah:</span>
                                                        <span class="text-slate-700 font-medium text-xs">{{ profile.basic.father_name || '-' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[9px] text-slate-400 block">Ibu:</span>
                                                        <span class="text-slate-700 font-medium text-xs">{{ profile.basic.mother_name || '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-span-2">
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Alamat</label>
                                                <div class="text-slate-700 font-medium text-xs leading-relaxed">{{ profile.basic.address || '-' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Academic Info -->
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest border-b border-blue-100 pb-2">Informasi Sekolah</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Unit Sekolah</label>
                                                <div class="text-slate-700 font-medium">{{ profile.basic.unit_name || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jenjang / Level</label>
                                                <div class="text-slate-700 font-medium">{{ profile.basic.level_name || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kelas Saat Ini</label>
                                                <div class="text-slate-700 font-medium">{{ profile.basic.class_name || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Wali Kelas</label>
                                                <div class="text-slate-700 font-medium">{{ profile.basic.teacher_name || '-' }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</label>
                                                <div>
                                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase">AKTIF</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENT: ATTENDANCE -->
                            <div v-if="activeTab === 'attendance'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Rekap Kehadiran Siswa</h3>
                                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left">
                                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                                <tr>
                                                    <th class="px-6 py-3">Periode</th>
                                                    <th class="px-6 py-3 text-center">H. Aktif</th>
                                                    <th class="px-6 py-3 text-center">Hadir</th>
                                                    <th class="px-6 py-3 text-center">Izin</th>
                                                    <th class="px-6 py-3 text-center">Sakit</th>
                                                    <th class="px-6 py-3 text-center">Alfa</th>
                                                    <th class="px-6 py-3 text-center">Cuti</th>
                                                    <th class="px-6 py-3 text-center">Persentase</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <tr v-for="att in profile.attendance" :key="att.month + '-' + att.year" class="hover:bg-slate-50">
                                                    <td class="px-6 py-4 font-bold text-slate-700">
                                                        {{ monthNames[att.month-1] }} {{ att.year }}
                                                    </td>
                                                    <td class="px-6 py-4 text-center text-slate-500">{{ att.active_days }}</td>
                                                    <td class="px-6 py-4 text-center font-bold text-blue-600">{{ att.hadir }}</td>
                                                    <td class="px-6 py-4 text-center text-amber-600">{{ att.izin }}</td>
                                                    <td class="px-6 py-4 text-center text-blue-500">{{ att.sakit }}</td>
                                                    <td class="px-6 py-4 text-center text-red-600">{{ att.alfa }}</td>
                                                    <td class="px-6 py-4 text-center text-purple-600">{{ att.cuti }}</td>
                                                    <td class="px-6 py-4 text-center">
                                                        <span class="px-2 py-1 rounded text-xs font-bold" 
                                                              :class="calculatePercentage(att) >= 90 ? 'bg-emerald-100 text-emerald-700' : (calculatePercentage(att) >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')">
                                                            {{ calculatePercentage(att) }}%
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr v-if="!profile.attendance || profile.attendance.length === 0">
                                                    <td colspan="8" class="px-6 py-8 text-center text-slate-400 italic">
                                                        Belum ada data kehadiran untuk siswa ini.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENT: SCHEDULE -->
                            <div v-if="activeTab === 'schedule'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Jadwal Pelajaran Kelas</h3>
                                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                            <tr>
                                                <th class="px-4 py-3 w-32 border-r border-slate-200">Waktu</th>
                                                <th v-for="day in profile.days" :key="day" class="px-4 py-3 text-center border-r border-slate-200 min-w-[120px]">
                                                    {{ day }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="slot in profile.timeslots" :key="slot.id">
                                                <td class="px-4 py-2 font-mono text-[10px] text-slate-500 border-r border-slate-200 bg-slate-50/50">
                                                    <div class="font-bold">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</div>
                                                    <div class="text-[9px]">{{ slot.name }}</div>
                                                </td>
                                                
                                                <!-- Jika Istirahat -->
                                                <td v-if="Number(slot.is_break) === 1" :colspan="profile.days.length" class="bg-amber-50 text-center py-2 text-amber-600 font-bold text-[10px] tracking-widest uppercase">
                                                    ISTIRAHAT
                                                </td>

                                                <!-- Jika Jam Pelajaran -->
                                                <template v-else>
                                                    <td v-for="day in profile.days" :key="day + slot.id" class="p-1 border-r border-slate-100 align-top h-16">
                                                        <div v-if="getScheduleItem(day, slot.start_time)" 
                                                            class="bg-blue-50 border border-blue-100 p-2 rounded h-full">
                                                            <div class="font-bold text-blue-800 text-[10px] mb-1 line-clamp-2 leading-tight">
                                                                {{ getScheduleItem(day, slot.start_time).subject_name }}
                                                            </div>
                                                            <div class="text-[9px] text-slate-500 flex items-center gap-1">
                                                                <i class="fas fa-user-tie text-[8px]"></i>
                                                                <span class="truncate">{{ getScheduleItem(day, slot.start_time).teacher_name }}</span>
                                                            </div>
                                                        </div>
                                                        <div v-else class="h-full flex items-center justify-center text-slate-200 text-[10px]">
                                                            -
                                                        </div>
                                                    </td>
                                                </template>
                                            </tr>
                                            <tr v-if="!profile.timeslots || profile.timeslots.length === 0">
                                                <td :colspan="profile.days.length + 1" class="px-6 py-12 text-center text-slate-400 italic">
                                                    Jadwal belum tersedia untuk unit/kelas ini.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- CONTENT: BOARDING -->
                            <div v-if="activeTab === 'boarding'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Informasi Keasramaan</h3>
                                
                                <div v-if="profile.boarding">
                                    <!-- Basic Boarding Info -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                        <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-100">
                                            <label class="block text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1">Kamar</label>
                                            <div class="text-indigo-800 font-bold text-lg">{{ profile.boarding.room_name || '-' }}</div>
                                        </div>
                                        <div class="bg-purple-50 p-4 rounded-2xl border border-purple-100">
                                            <label class="block text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-1">Halaqoh</label>
                                            <div class="text-purple-800 font-bold text-lg">{{ profile.boarding.halaqoh_name || '-' }}</div>
                                        </div>
                                        <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                                            <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Musyrif / Pembimbing</label>
                                            <div class="text-blue-800 font-bold text-lg">{{ profile.boarding.musyrif_name || '-' }}</div>
                                        </div>
                                    </div>

                                    <!-- Boarding Discipline Records -->
                                    <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                        <i class="fas fa-history text-pink-500"></i> Catatan Kedisiplinan & Prestasi Asrama
                                    </h4>
                                    <div class="space-y-3">
                                        <div v-for="record in profile.boarding.discipline" :key="record.id" 
                                            class="flex items-center gap-4 p-4 rounded-2xl border transition-all"
                                            :class="record.type === 'ACHIEVEMENT' ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shadow-sm"
                                                :class="record.type === 'ACHIEVEMENT' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'">
                                                <i :class="record.type === 'ACHIEVEMENT' ? 'fas fa-trophy' : 'fas fa-exclamation-circle'"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <div class="font-bold text-slate-800">{{ record.category }}</div>
                                                    <div class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                        :class="record.type === 'ACHIEVEMENT' ? 'bg-emerald-200 text-emerald-800' : 'bg-red-200 text-red-800'">
                                                        {{ record.type === 'ACHIEVEMENT' ? '+' : '' }}{{ record.points }} Poin
                                                    </div>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-1">{{ record.description }}</div>
                                                <div class="text-[9px] text-slate-400 mt-1 uppercase font-bold">{{ formatDate(record.record_date) }}</div>
                                            </div>
                                        </div>
                                        <div v-if="!profile.boarding.discipline || profile.boarding.discipline.length === 0" class="py-12 text-center text-slate-400 italic bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                            Tidak ada catatan kedisiplinan atau prestasi asrama.
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-20 text-center text-slate-400 italic bg-slate-50 rounded-3xl border border-dashed border-slate-200">
                                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 text-3xl">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <p class="font-bold text-slate-600 mb-1">Bukan Siswa Asrama</p>
                                    <p class="text-sm">Siswa ini tidak terdaftar dalam sistem manajemen asrama.</p>
                                </div>
                            </div>

                            <!-- CONTENT: LIBRARY -->
                            <div v-if="activeTab === 'library'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Aktivitas Perpustakaan</h3>
                                <div class="space-y-4">
                                    <div v-for="log in profile.library" :key="log.read_at" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-lg shadow-sm">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-bold text-slate-700">{{ log.book_title }}</div>
                                            <div class="text-xs text-slate-400">Dibaca pada kunjungan tgl {{ formatDate(log.visit_date) }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Waktu Log</div>
                                            <div class="text-xs font-mono text-slate-400">{{ formatDateTime(log.read_at) }}</div>
                                        </div>
                                    </div>
                                    <div v-if="profile.library.length === 0" class="py-12 text-center text-slate-400 italic">Belum ada riwayat bacaan.</div>
                                </div>
                            </div>

                            <!-- CONTENT: CASES -->
                            <div v-if="activeTab === 'cases'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Catatan Buku Kasus</h3>
                                <div v-if="!profile.cases || profile.cases.length === 0" class="py-12 text-center text-slate-400 italic">
                                    <i class="fas fa-shield-alt text-5xl mb-4 text-slate-200"></i>
                                    <p>Belum ada catatan pelanggaran disiplin.</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div v-for="c in profile.cases" :key="c.incident_id" class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-200">
                                        <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-lg">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-slate-800 truncate">{{ c.title }}</div>
                                            <div class="text-[11px] text-slate-500">Kategori {{ c.category || '-' }} • Tingkat {{ c.severity || '-' }}</div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">Insiden: {{ c.incident_status || '-' }}</span>
                                                <span v-if="c.ticket_id" :class="ticketStatusClass(c.ticket_status)" class="px-2 py-0.5 rounded text-[10px] font-bold">Ticket: {{ c.ticket_status }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[10px] font-bold text-slate-300 uppercase">Tgl</div>
                                            <div class="text-xs text-slate-600">{{ formatDateTime(c.created_at) }}</div>
                                            <div class="mt-2">
                                                <a v-if="c.ticket_id" :href="'index.php#ticket-'+c.ticket_id" class="px-3 py-1 rounded bg-pink-600 text-white text-[11px] font-bold">Lihat Ticket</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENT: COUNSELING -->
                            <div v-if="activeTab === 'counseling'" class="animate-fade">
                                <h3 class="text-xl font-bold text-slate-800 mb-6">Riwayat Konseling</h3>
                                <div class="py-12 text-center text-slate-400 italic">
                                    <i class="fas fa-comments text-5xl mb-4 text-slate-200"></i>
                                    <p>Belum ada riwayat sesi konseling.</p>
                                </div>
                            </div>

                            <div v-if="activeTab === 'achievements'" class="animate-fade">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-bold text-slate-800">Prestasi & Penghargaan</h3>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="a in profile.achievements" :key="a.id" class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-200">
                                        <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-lg">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-bold text-slate-800">{{ a.title }}</div>
                                            <div class="text-[11px] text-slate-500">Kategori {{ a.category }} • Tingkat {{ a.level }} • {{ a.rank }}</div>
                                            <div class="text-[10px] text-slate-400 uppercase font-bold">{{ formatDate(a.date) }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[10px] font-bold text-slate-300 uppercase">Penyelenggara</div>
                                            <div class="text-xs text-slate-600">{{ a.organizer }}</div>
                                            <div v-if="a.points" class="mt-1 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">{{ a.points }} Poin</div>
                                        </div>
                                    </div>
                                    <div v-if="!profile.achievements || profile.achievements.length === 0" class="py-12 text-center text-slate-400 italic">
                                        Belum ada catatan prestasi.
                                    </div>
                                </div>
                            </div>

                        </div>
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
                searchQuery: '<?php echo $_GET['q'] ?? ''; ?>',
                searchResults: [],
                selectedStudent: null,
                loading: true,
                activeTab: '<?php echo $_GET['tab'] ?? 'academic'; ?>',
                profile: {
                    basic: {},
                    schedule: [],
                    timeslots: [],
                    days: [],
                    library: [],
                    boarding: null,
                    cases: [],
                    counseling: [],
                    achievements: [],
                    attendance: []
                },
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            }
        },
        methods: {
            baseUrl() {
                let b = window.BASE_URL || '';
                if (!b) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//);
                    b = m ? `/${m[1]}/` : '/';
                }
                return b;
            },
            async readJsonSafe(res) {
                const txt = await res.text();
                try { return JSON.parse(txt); } catch (_) { return { success: false, error: 'bad_json', raw: txt }; }
            },
            async searchStudents() {
                if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=search_students&q=${encodeURIComponent(this.searchQuery)}`);
                    const data = await this.readJsonSafe(res);
                    if (data && data.success) this.searchResults = data.data || []; else this.searchResults = [];
                } catch (e) {
                    console.error('Failed to search students', e);
                }
            },
            ticketStatusClass(s) {
                const st = String(s || '').toUpperCase();
                if (st === 'OPEN') return 'bg-pink-100 text-pink-700';
                if (st === 'IN_PROGRESS') return 'bg-amber-100 text-amber-700';
                if (st === 'REOPEN') return 'bg-indigo-100 text-indigo-700';
                return 'bg-emerald-100 text-emerald-700';
            },
            doSearch() {
                if (this.searchQuery.trim()) {
                    this.searchStudents();
                }
            },
            calculatePercentage(att) {
                if (!att.active_days || att.active_days == 0) return 0;
                return Math.round((att.hadir / att.active_days) * 100);
            },
            getScheduleItem(day, startTime) {
                if (!this.profile.schedule) return null;
                const d = day.toUpperCase();
                const t = startTime.substring(0, 5);
                return this.profile.schedule.find(s => s.day_name.toUpperCase() === d && s.start_time.substring(0, 5) === t);
            },
            async fetchStudentByNIS() {
                if (!this.searchQuery) { this.loading = false; return; }
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=search_students&q=${encodeURIComponent(this.searchQuery)}`);
                    const data = await this.readJsonSafe(res);
                    if (data.success && data.data && data.data.length > 0) {
                        // If exact match by identity_number exists, take it
                        const exactMatch = data.data.find(s => s.identity_number === this.searchQuery);
                        // If only one result, take it
                        const firstResult = data.data.length === 1 ? data.data[0] : null;
                        
                        const target = exactMatch || firstResult;
                        if (target) {
                            await this.selectStudent(target);
                        } else {
                            // If multiple results and no exact match, we can't auto-select
                            this.loading = false;
                        }
                    } else {
                        this.loading = false;
                    }
                } catch (e) {
                    console.error(e);
                    this.loading = false;
                }
            },
            async selectStudent(s) {
                if (!s || !s.id) return;
                this.loading = true;
                this.selectedStudent = s;
                
                try {
                    const res = await fetch(`${this.baseUrl()}api/counseling.php?action=get_student_full_profile&id=${encodeURIComponent(s.id)}`);
                    const data = await this.readJsonSafe(res);
                    if (data.success && data.data) {
                        this.profile = {
                            basic: data.data.basic || {},
                            schedule: data.data.schedule || [],
                            timeslots: data.data.timeslots || [],
                            days: data.data.days || [],
                            library: data.data.library || [],
                            boarding: data.data.boarding || null,
                            cases: data.data.cases || [],
                            counseling: data.data.counseling || [],
                            achievements: data.data.achievements || [],
                            attendance: []
                        };

                        // Fetch Attendance Summary
                        try {
                            const attRes = await fetch(`${this.baseUrl()}api/attendance.php?action=get_student_attendance_summary&student_id=${encodeURIComponent(s.id)}`);
                            const attData = await this.readJsonSafe(attRes);
                            if (attData.success) {
                                this.profile.attendance = attData.data;
                            }
                        } catch (attErr) {
                            console.error("Failed to fetch attendance:", attErr);
                        }
                    } else {
                        this.selectedStudent = null;
                    }
                } catch (e) {
                    console.error(e);
                    this.selectedStudent = null;
                } finally {
                    this.loading = false;
                }
            },
            formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); },
            formatTime(t) { return t ? t.substring(0, 5) : ''; },
            formatDateTime(dt) { const date = new Date(dt); return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0'); }
        },
        mounted() {
            this.fetchStudentByNIS();
        }
    }).mount('#app')
</script>
</body>
</html>
