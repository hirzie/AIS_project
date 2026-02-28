<?php
require_once '../../config/database.php';
require_once '../../includes/header_inventory.php';
?>

<script>window.USE_GLOBAL_APP=false;</script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    try{document.querySelectorAll('[v-cloak]').forEach(function(n){n.removeAttribute('v-cloak');});}catch(_){}
});
</script>

<div id="inventoryDashboard" v-cloak>
<nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-emerald-200">
            <i class="fas fa-boxes text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-none">Inventory & Aset</h1>
            <span class="text-xs text-slate-500 font-medium">Asset Management System</span>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ currentDate }}</span>
        <button onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'index.php'" class="text-slate-400 hover:text-red-500 transition-colors" title="Keluar ke Menu Utama">
            <i class="fas fa-power-off text-lg"></i>
        </button>
    </div>
</nav>

<main class="flex-1 overflow-y-auto p-8 flex flex-col items-center bg-slate-50 relative">
    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50">
        <div class="absolute -top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-emerald-100/50 blur-3xl"></div>
        <div class="absolute -bottom-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-teal-100/50 blur-3xl"></div>
    </div>

    <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative z-10">
        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/fixed.php'; return false;" class="block">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Aset Tidak Bergerak</h3>
                    <p class="text-sm text-slate-500 mb-4">Pengelolaan tanah, bangunan, dan infrastruktur fisik sekolah.</p>
                </a>
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/fixed.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                        <i class="fas fa-landmark w-5 text-center"></i> Tanah & Sertifikat
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/fixed.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                        <i class="fas fa-school w-5 text-center"></i> Gedung & Ruangan
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/fixed.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-amber-600 transition-colors">
                        <i class="fas fa-calculator w-5 text-center"></i> Hitung Penyusutan
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/movable.php'; return false;" class="block">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <i class="fas fa-chair"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Aset Bergerak</h3>
                    <p class="text-sm text-slate-500 mb-4">Inventarisasi mebel, elektronik, dan peralatan operasional.</p>
                </a>
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/movable.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-laptop w-5 text-center"></i> Elektronik & IT
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/movable.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-couch w-5 text-center"></i> Mebelair
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/movable.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-qrcode w-5 text-center"></i> Labeling & QR Code
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/vehicles.php'; return false;" class="block">
                    <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Kendaraan</h3>
                    <p class="text-sm text-slate-500 mb-4">Daftar kendaraan dinas, jadwal service, dan pajak.</p>
                </a>
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/vehicles.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                        <i class="fas fa-list w-5 text-center"></i> Daftar Unit
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/vehicles.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                        <i class="fas fa-wrench w-5 text-center"></i> Riwayat Service
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/vehicles.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-emerald-600 transition-colors">
                        <i class="fas fa-gas-pump w-5 text-center"></i> BBM & Operasional
                    </a>
                </div>
            </div>
        </div>

        <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-slate-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex flex-col h-full">
                <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/settings.php'; return false;" class="block">
                    <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-2xl mb-6 shadow-sm group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Pengaturan</h3>
                    <p class="text-sm text-slate-500 mb-4">Master data kategori, lokasi, dan laporan inventaris.</p>
                </a>
                <div class="mt-auto pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/settings.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fas fa-tags w-5 text-center"></i> Kategori Aset
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/settings.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fas fa-map-marker-alt w-5 text-center"></i> Master Lokasi
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/settings.php'; return false;" class="flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fas fa-file-alt w-5 text-center"></i> Laporan Aset
                    </a>
                    <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/procurement/submit.php?module=SARPRAS&label=Fasilitas'; return false;" class="flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700 transition-colors mt-1 pt-1 border-t border-slate-50">
                        <i class="fas fa-shopping-cart w-5 text-center"></i> Procurement
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Tabs Section -->
    <div class="mt-8 max-w-6xl w-full mx-auto relative z-10">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 flex gap-6 overflow-x-auto">
                <button @click="activeTab = 'vehicle_calendar'" :class="['pb-2 text-sm font-semibold transition-colors border-b-2 whitespace-nowrap', activeTab === 'vehicle_calendar' ? 'text-emerald-600 border-emerald-600' : 'text-slate-500 border-transparent hover:text-emerald-500']">
                    <i class="fas fa-calendar-alt mr-2"></i>Kalender Mobil
                </button>
                <button @click="activeTab = 'damage_report'" :class="['pb-2 text-sm font-semibold transition-colors border-b-2 whitespace-nowrap', activeTab === 'damage_report' ? 'text-red-600 border-red-600' : 'text-slate-500 border-transparent hover:text-red-500']">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Laporan Kerusakan
                </button>
                <button @click="activeTab = 'room_calendar'" :class="['pb-2 text-sm font-semibold transition-colors border-b-2 whitespace-nowrap', activeTab === 'room_calendar' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent hover:text-indigo-500']">
                    <i class="fas fa-door-open mr-2"></i>Peminjaman Ruangan & Alat
                </button>
            </div>
            
            <div class="p-6">
                <!-- Vehicle Calendar Tab -->
                <div v-if="activeTab === 'vehicle_calendar'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Jadwal Penggunaan Kendaraan</h2>
                        <div class="flex items-center gap-2">
                            <button @click="changeMonth(-1)" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="font-semibold text-slate-700 min-w-[150px] text-center">{{ currentMonthName }} {{ currentYear }}</span>
                            <button @click="changeMonth(1)" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200">
                            <div v-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day" class="py-2 text-center text-xs font-semibold text-slate-500 uppercase">
                                {{ day }}
                            </div>
                        </div>
                        <div class="grid grid-cols-7 auto-rows-fr bg-white">
                            <div v-for="(day, index) in calendarDays" :key="index" 
                                :class="['min-h-[120px] p-2 border-b border-r border-slate-100 relative group transition-colors hover:bg-slate-50', day.isCurrentMonth ? 'bg-white' : 'bg-slate-50/50']">
                                
                                <div :class="['text-xs font-medium mb-2 w-6 h-6 flex items-center justify-center rounded-full', day.isToday ? 'bg-emerald-600 text-white' : 'text-slate-500']">
                                    {{ day.date.getDate() }}
                                </div>

                                <div class="space-y-1 overflow-y-auto max-h-[80px] custom-scrollbar">
                                    <div v-for="event in day.events" :key="event.id" 
                                        class="text-[10px] p-1.5 rounded border border-l-2 shadow-sm cursor-pointer hover:opacity-80 transition-opacity"
                                        :class="getEventColor(event)"
                                        :title="event.borrower_name + ' (' + event.purpose + ')'"
                                        @click="showEventDetail(event)">
                                        <div class="font-bold truncate">{{ event.vehicle_name }}</div>
                                        <div class="truncate opacity-75">{{ event.borrower_name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Damage Report Tab -->
                <div v-if="activeTab === 'damage_report'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Tiket Laporan Kerusakan</h2>
                        <button @click="showTicketForm = true" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors shadow-sm">
                            <i class="fas fa-plus mr-2"></i>Buat Laporan Baru
                        </button>
                    </div>

                    <!-- Ticket Form -->
                    <div v-if="showTicketForm" class="mb-6 bg-red-50 p-6 rounded-xl border border-red-100 animate-fade-in">
                        <h3 class="font-bold text-red-800 mb-4">Form Laporan Kerusakan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Pelapor</label>
                                <input type="text" v-model="newTicket.reporter_name" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none" placeholder="Nama Anda">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Judul Masalah</label>
                                <input type="text" v-model="newTicket.title" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none" placeholder="Contoh: AC Bocor di Lab Kom">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Prioritas</label>
                                <select v-model="newTicket.priority" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none">
                                    <option value="LOW">Rendah (Low)</option>
                                    <option value="MEDIUM">Sedang (Medium)</option>
                                    <option value="HIGH">Tinggi (High)</option>
                                    <option value="CRITICAL">Kritis (Critical)</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Deskripsi Detail</label>
                                <textarea v-model="newTicket.description" class="w-full border rounded px-3 py-2 text-sm h-24 focus:ring-2 focus:ring-red-200 outline-none" placeholder="Jelaskan detail kerusakan dan lokasi tepatnya..."></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button @click="showTicketForm = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded text-sm transition-colors">Batal</button>
                            <button @click="saveTicket" class="bg-red-600 text-white px-6 py-2 rounded text-sm hover:bg-red-700 transition-colors shadow-sm">Kirim Laporan</button>
                        </div>
                    </div>

                    <!-- Tickets List -->
                    <div class="space-y-4">
                        <div v-for="ticket in tickets" :key="ticket.id" class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-slate-800">{{ ticket.title }}</h3>
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide', getPriorityColor(ticket.priority)]">
                                        {{ ticket.priority }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 mb-3">{{ ticket.description }}</p>
                                <div class="flex items-center gap-4 text-xs text-slate-400">
                                    <span><i class="fas fa-user mr-1"></i> {{ ticket.reporter_name }}</span>
                                    <span><i class="fas fa-clock mr-1"></i> {{ formatDate(ticket.created_at) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2 min-w-[120px]">
                                <span :class="['px-3 py-1 rounded-full text-xs font-bold border', getStatusColor(ticket.status)]">
                                    {{ ticket.status }}
                                </span>
                                <div class="flex gap-1 mt-auto">
                                    <button v-if="ticket.status !== 'CLOSED'" @click="updateTicketStatus(ticket.id, 'IN_PROGRESS')" class="w-8 h-8 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition-colors" title="Proses">
                                        <i class="fas fa-tools"></i>
                                    </button>
                                    <button v-if="ticket.status !== 'CLOSED'" @click="updateTicketStatus(ticket.id, 'RESOLVED')" class="w-8 h-8 rounded bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center transition-colors" title="Selesai">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button v-if="ticket.status !== 'CLOSED'" @click="updateTicketStatus(ticket.id, 'CLOSED')" class="w-8 h-8 rounded bg-slate-50 text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Tutup">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="tickets.length === 0" class="text-center py-12 text-slate-400">
                            <i class="fas fa-check-circle text-4xl mb-3 text-slate-200"></i>
                            <p>Tidak ada laporan kerusakan aktif.</p>
                        </div>
                    </div>
                </div>

                <!-- Room & Lab Calendar Tab -->
                <div v-if="activeTab === 'room_calendar'">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h2 class="text-lg font-bold text-slate-800">Jadwal Ruangan & Laboratorium</h2>
                        <div class="flex gap-4">
                            <select v-model="resourceType" @change="fetchResourceEvents" class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                                <option value="ROOM">Ruangan Umum</option>
                                <option value="LAB">Laboratorium</option>
                                <option value="TOOL">Peralatan</option>
                            </select>
                            <button @click="showResourceForm = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                                <i class="fas fa-plus mr-2"></i>Pinjam
                            </button>
                        </div>
                    </div>

                    <!-- Resource Lending Form -->
                    <div v-if="showResourceForm" class="mb-6 bg-indigo-50 p-6 rounded-xl border border-indigo-100 animate-fade-in">
                        <h3 class="font-bold text-indigo-800 mb-4">Form Peminjaman {{ resourceType }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Peminjam</label>
                                <input type="text" v-model="newResource.borrower_name" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Nama {{ resourceType }}</label>
                                <input type="text" v-model="newResource.resource_name" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 outline-none" placeholder="Misal: Lab Komputer 1">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Mulai</label>
                                <input type="datetime-local" v-model="newResource.borrow_date" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Rencana Selesai</label>
                                <input type="datetime-local" v-model="newResource.return_date_planned" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Keperluan</label>
                                <textarea v-model="newResource.purpose" class="w-full border rounded px-3 py-2 text-sm h-20 focus:ring-2 focus:ring-indigo-200 outline-none"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button @click="showResourceForm = false" class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded text-sm transition-colors">Batal</button>
                            <button @click="saveResourceLending" class="bg-indigo-600 text-white px-6 py-2 rounded text-sm hover:bg-indigo-700 transition-colors shadow-sm">Simpan Jadwal</button>
                        </div>
                    </div>

                    <!-- Room Calendar Grid -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200">
                            <div v-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day" class="py-2 text-center text-xs font-semibold text-slate-500 uppercase">
                                {{ day }}
                            </div>
                        </div>
                        <div class="grid grid-cols-7 auto-rows-fr bg-white">
                            <div v-for="(day, index) in calendarDays" :key="index" 
                                :class="['min-h-[120px] p-2 border-b border-r border-slate-100 relative group transition-colors hover:bg-slate-50', day.isCurrentMonth ? 'bg-white' : 'bg-slate-50/50']">
                                
                                <div :class="['text-xs font-medium mb-2 w-6 h-6 flex items-center justify-center rounded-full', day.isToday ? 'bg-indigo-600 text-white' : 'text-slate-500']">
                                    {{ day.date.getDate() }}
                                </div>

                                <div class="space-y-1 overflow-y-auto max-h-[80px] custom-scrollbar">
                                    <div v-for="event in day.resourceEvents" :key="event.id" 
                                        class="text-[10px] p-1.5 rounded border border-l-2 shadow-sm cursor-pointer hover:opacity-80 transition-opacity bg-indigo-50 border-indigo-500 text-indigo-700"
                                        :title="event.borrower_name + ' (' + event.resource_name + ')'">
                                        <div class="font-bold truncate">{{ event.resource_name }}</div>
                                        <div class="truncate opacity-75">{{ event.borrower_name }}</div>
                                        <div v-if="event.status === 'BORROWED'" class="mt-1 text-[9px] text-right">
                                            <button @click.stop="returnResource(event)" class="text-indigo-600 hover:text-indigo-800 underline">Selesai</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Vehicle Event Detail Modal -->
    <div v-if="showEventModal && selectedEvent" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @click.self="closeEventDetail">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
            <div class="bg-slate-50 border-b border-slate-100 p-4 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Detail Peminjaman</h3>
                <button @click="closeEventDetail" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fas fa-car"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800">{{ selectedEvent.vehicle_name }}</h4>
                        <p class="text-sm text-slate-500">{{ selectedEvent.license_plate }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="block text-xs font-bold text-slate-500 mb-1">Peminjam</span>
                        <span class="font-medium text-slate-800">{{ selectedEvent.borrower_name }}</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="block text-xs font-bold text-slate-500 mb-1">Status</span>
                        <span :class="['inline-block px-2 py-0.5 rounded text-[10px] font-bold border', selectedEvent.status === 'BORROWED' ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-slate-100 text-slate-500 border-slate-200']">
                            {{ selectedEvent.status }}
                        </span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="block text-xs font-bold text-slate-500 mb-1">Mulai</span>
                        <span class="font-medium text-slate-800">{{ formatDate(selectedEvent.borrow_date) }}</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <span class="block text-xs font-bold text-slate-500 mb-1">Selesai</span>
                        <span class="font-medium text-slate-800">{{ selectedEvent.return_date_actual ? formatDate(selectedEvent.return_date_actual) : (selectedEvent.return_date_planned ? formatDate(selectedEvent.return_date_planned) + ' (Rencana)' : '-') }}</span>
                    </div>
                </div>
                
                <div class="bg-slate-50 p-3 rounded-lg">
                    <span class="block text-xs font-bold text-slate-500 mb-1">Keperluan</span>
                    <p class="text-sm text-slate-700">{{ selectedEvent.purpose || '-' }}</p>
                </div>

                <div v-if="selectedEvent.notes" class="bg-slate-50 p-3 rounded-lg">
                    <span class="block text-xs font-bold text-slate-500 mb-1">Catatan</span>
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ selectedEvent.notes }}</p>
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-end">
                <button @click="closeEventDetail" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</main>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            currentDate: new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
            activeTab: 'vehicle_calendar',
            
            // Calendar Shared
            currentDateObj: new Date(),
            calendarDays: [],
            
            // Vehicle
            vehicleEvents: [],
            
            // Facility Tickets
            tickets: [],
            showTicketForm: false,
            newTicket: { priority: 'MEDIUM' },
            
            // Resource Lending
            resourceType: 'ROOM',
            resourceEvents: [],
            showResourceForm: false,
            newResource: {},

            // Event Modal
            showEventModal: false,
            selectedEvent: null
        }
    },
    computed: {
        currentMonthName() {
            return this.currentDateObj.toLocaleDateString('id-ID', { month: 'long' });
        },
        currentYear() {
            return this.currentDateObj.getFullYear();
        }
    },
    mounted() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'tickets') this.activeTab = 'damage_report';
        else if (tab === 'vehicle' || tab === 'vehicles') this.activeTab = 'vehicle_calendar';
        else if (tab === 'room' || tab === 'resources') this.activeTab = 'room_calendar';

        this.generateCalendar();
        this.fetchVehicleEvents();
        this.fetchFacilityTickets();
        this.fetchResourceEvents();
        this.fetchNotificationCounts();
        
        // Poll notifications every minute
        setInterval(() => this.fetchNotificationCounts(), 60000);
    },
    methods: {
        // Notifications
        async fetchNotificationCounts() {
            try {
                const res = await fetch(window.BASE_URL + 'api/notifications.php?action=get_counts');
                const data = await res.json();
                if (data.success) {
                    this.notificationCounts = data.data;
                }
            } catch (e) { console.error(e); }
        },
        toggleNotifications() {
            this.showNotifications = !this.showNotifications;
        },

        // Shared
        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        },
        changeMonth(step) {
            this.currentDateObj = new Date(this.currentDateObj.setMonth(this.currentDateObj.getMonth() + step));
            this.generateCalendar();
            this.fetchVehicleEvents();
            this.fetchResourceEvents();
        },
        generateCalendar() {
            const year = this.currentDateObj.getFullYear();
            const month = this.currentDateObj.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            const days = [];
            const startPadding = firstDay.getDay();
            
            // Previous month padding
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = startPadding - 1; i >= 0; i--) {
                days.push({
                    date: new Date(year, month - 1, prevMonthLastDay - i),
                    isCurrentMonth: false,
                    isToday: false,
                    events: [],
                    resourceEvents: []
                });
            }
            
            // Current month
            const today = new Date();
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                days.push({
                    date: date,
                    isCurrentMonth: true,
                    isToday: date.toDateString() === today.toDateString(),
                    events: [],
                    resourceEvents: []
                });
            }
            
            // Next month padding
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) {
                days.push({
                    date: new Date(year, month + 1, i),
                    isCurrentMonth: false,
                    isToday: false,
                    events: [],
                    resourceEvents: []
                });
            }
            
            this.calendarDays = days;
            this.mapEventsToCalendar();
            this.mapResourceEventsToCalendar();
        },

        // Vehicle Events
        async fetchVehicleEvents() {
            const year = this.currentDateObj.getFullYear();
            const month = this.currentDateObj.getMonth() + 1;
            const startStr = `${year}-${String(month).padStart(2, '0')}-01`;
            const endStr = `${year}-${String(month).padStart(2, '0')}-${new Date(year, month, 0).getDate()}`;
            
            try {
                const res = await fetch(window.BASE_URL + `api/inventory.php?action=get_all_lendings&start_date=${startStr}&end_date=${endStr}`);
                const data = await res.json();
                if (data.success) {
                    this.vehicleEvents = data.data;
                    this.mapEventsToCalendar();
                }
            } catch (e) { console.error(e); }
        },
        mapEventsToCalendar() {
            if (!this.calendarDays.length || !this.vehicleEvents.length) return;
            
            this.calendarDays.forEach(day => day.events = []);
            
            this.vehicleEvents.forEach(event => {
                const borrow = new Date(event.borrow_date);
                const ret = event.return_date_actual ? new Date(event.return_date_actual) : (event.return_date_planned ? new Date(event.return_date_planned) : new Date());
                
                // Simple check: if day is between borrow and return
                this.calendarDays.forEach(day => {
                    // Normalize times for comparison
                    const d = new Date(day.date); d.setHours(0,0,0,0);
                    const b = new Date(borrow); b.setHours(0,0,0,0);
                    const r = new Date(ret); r.setHours(23,59,59,999);
                    
                    if (d >= b && d <= r) {
                        day.events.push(event);
                    }
                });
            });
        },

        // Facility Tickets
        async fetchFacilityTickets() {
            try {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_facility_tickets');
                const data = await res.json();
                if (data.success) {
                    this.tickets = data.data;
                }
            } catch (e) { console.error(e); }
        },
        async saveTicket() {
            if (!this.newTicket.title || !this.newTicket.description) {
                alert('Judul dan Deskripsi wajib diisi!');
                return;
            }
            
            try {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_facility_ticket', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.newTicket)
                });
                const data = await res.json();
                if (data.success) {
                    this.showTicketForm = false;
                    this.newTicket = { priority: 'MEDIUM' };
                    this.fetchFacilityTickets();
                    alert('Laporan berhasil dikirim!');
                } else {
                    alert('Gagal mengirim laporan: ' + data.message);
                }
            } catch (e) { console.error(e); alert('Terjadi kesalahan sistem'); }
        },
        async updateTicketStatus(id, status) {
            if (!confirm('Ubah status tiket ini?')) return;
            
            try {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=update_facility_ticket_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status })
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchFacilityTickets();
                }
            } catch (e) { console.error(e); }
        },
        getPriorityColor(priority) {
            const colors = {
                'LOW': 'bg-slate-100 text-slate-600',
                'MEDIUM': 'bg-amber-100 text-amber-600',
                'HIGH': 'bg-orange-100 text-orange-600',
                'CRITICAL': 'bg-red-100 text-red-600'
            };
            return colors[priority] || 'bg-slate-100';
        },
        getStatusColor(status) {
            const colors = {
                'OPEN': 'bg-red-50 text-red-600 border-red-200',
                'IN_PROGRESS': 'bg-blue-50 text-blue-600 border-blue-200',
                'RESOLVED': 'bg-emerald-50 text-emerald-600 border-emerald-200',
                'CLOSED': 'bg-slate-50 text-slate-500 border-slate-200'
            };
            return colors[status] || 'bg-slate-50';
        },

        // Resource Lending
        async fetchResourceEvents() {
            const year = this.currentDateObj.getFullYear();
            const month = this.currentDateObj.getMonth() + 1;
            const startStr = `${year}-${String(month).padStart(2, '0')}-01`;
            const endStr = `${year}-${String(month).padStart(2, '0')}-${new Date(year, month, 0).getDate()}`;
            
            try {
                const res = await fetch(window.BASE_URL + `api/inventory.php?action=get_resource_lendings&resource_type=${this.resourceType}&start_date=${startStr}&end_date=${endStr}`);
                const data = await res.json();
                if (data.success) {
                    this.resourceEvents = data.data;
                    this.mapResourceEventsToCalendar();
                }
            } catch (e) { console.error(e); }
        },
        mapResourceEventsToCalendar() {
            if (!this.calendarDays.length) return;
            
            this.calendarDays.forEach(day => day.resourceEvents = []);
            if (!this.resourceEvents.length) return;
            
            this.resourceEvents.forEach(event => {
                const borrow = new Date(event.borrow_date);
                const ret = event.return_date_actual ? new Date(event.return_date_actual) : (event.return_date_planned ? new Date(event.return_date_planned) : new Date());
                
                this.calendarDays.forEach(day => {
                    const d = new Date(day.date); d.setHours(0,0,0,0);
                    const b = new Date(borrow); b.setHours(0,0,0,0);
                    const r = new Date(ret); r.setHours(23,59,59,999);
                    
                    if (d >= b && d <= r) {
                        day.resourceEvents.push(event);
                    }
                });
            });
        },
        async saveResourceLending() {
            if (!this.newResource.resource_name || !this.newResource.borrow_date || !this.newResource.borrower_name) {
                alert('Mohon lengkapi data peminjaman!');
                return;
            }
            
            this.newResource.resource_type = this.resourceType;
            
            try {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_resource_lending', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.newResource)
                });
                const data = await res.json();
                if (data.success) {
                    this.showResourceForm = false;
                    this.newResource = {};
                    this.fetchResourceEvents();
                    alert('Jadwal berhasil disimpan!');
                } else {
                    alert('Gagal menyimpan: ' + data.message);
                }
            } catch (e) { console.error(e); alert('Terjadi kesalahan sistem'); }
        },
        async returnResource(event) {
            if (!confirm('Tandai sebagai selesai/dikembalikan?')) return;
            
            try {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=return_resource', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: event.id })
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchResourceEvents();
                }
            } catch (e) { console.error(e); }
        },

        // Event Modal Helpers
        getEventColor(event) {
            if (event.status === 'BORROWED') return 'bg-amber-50 border-amber-500 text-amber-700';
            if (event.status === 'RETURNED') return 'bg-emerald-50 border-emerald-500 text-emerald-700';
            return 'bg-slate-50 border-slate-500 text-slate-700';
        },
        showEventDetail(event) {
            this.selectedEvent = event;
            this.showEventModal = true;
        },
        closeEventDetail() {
            this.showEventModal = false;
            this.selectedEvent = null;
        }
    }
}).mount('#inventoryDashboard');
</script>
