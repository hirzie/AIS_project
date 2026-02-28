<?php
require_once '../../includes/guard.php';
require_login_and_module('boarding');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Perizinan - SekolahOS</title>
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

    <?php require_once '../../includes/header_boarding.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Daftar & Riwayat Perizinan</h2>
                    <p class="text-slate-500 text-sm">Pantau status santri yang sedang izin atau lihat riwayat lampau</p>
                </div>
                <div class="flex gap-2">
                    <select v-model="filterStatus" class="border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="all">Semua Status</option>
                        <option value="APPROVED">Sedang Izin (Aktif)</option>
                        <option value="RETURNED">Sudah Kembali</option>
                        <option value="PENDING">Menunggu Persetujuan</option>
                    </select>
                    <a href="permission_input.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-bold text-sm shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                        <i class="fas fa-plus"></i> Input Izin Baru
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Nama Santri</th>
                            <th class="px-6 py-4">Jenis Izin</th>
                            <th class="px-6 py-4">Waktu Izin</th>
                            <th class="px-6 py-4">Alasan</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in filteredPermissions" :key="p.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                        {{ p.student_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-700">{{ p.student_name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ p.nis }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getTypeClass(p.permission_type)" class="px-2 py-1 rounded text-[10px] font-bold border">
                                    {{ p.permission_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div>Mulai: {{ formatDate(p.start_date) }}</div>
                                <div>Rencana: {{ formatDate(p.end_date) }}</div>
                                <div v-if="p.return_date" class="text-emerald-600 font-bold mt-1">Kembali: {{ formatDate(p.return_date) }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-xs truncate max-w-xs">{{ p.reason }}</td>
                            <td class="px-6 py-4">
                                <span :class="getStatusClass(p.status)" class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                                    {{ p.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button v-if="p.status === 'APPROVED'" @click="updateStatus(p.id, 'RETURNED')" 
                                        class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded-lg hover:bg-emerald-600 hover:text-white font-bold text-xs transition-all border border-emerald-100">
                                    Konfirmasi Kembali
                                </button>
                                <span v-else class="text-slate-300">-</span>
                            </td>
                        </tr>
                        <tr v-if="permissions.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <i class="fas fa-history text-4xl mb-3 opacity-20"></i>
                                <p>Belum ada riwayat perizinan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue
    createApp({
        data() {
            return {
                currentGender: 'all',
                filterStatus: 'all',
                permissions: []
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredPermissions() {
                if (this.filterStatus === 'all') return this.permissions;
                return this.permissions.filter(p => p.status === this.filterStatus);
            }
        },
        watch: {
            currentGender() { this.fetchPermissions(); }
        },
        methods: {
            async fetchPermissions() {
                try {
                    const res = await fetch(`../../api/boarding_permissions.php?action=get_permissions&gender=${this.currentGender}`);
                    const data = await res.json();
                    if (data.success) {
                        this.permissions = data.data;
                    }
                } catch (e) {}
            },
            formatDate(d) {
                if (!d) return '-';
                return new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
            },
            getTypeClass(type) {
                switch(type) {
                    case 'PULANG': return 'bg-orange-50 text-orange-600 border-orange-100';
                    case 'KELUAR': return 'bg-blue-50 text-blue-600 border-blue-100';
                    case 'SAKIT': return 'bg-red-50 text-red-600 border-red-100';
                    default: return 'bg-slate-50 text-slate-600 border-slate-100';
                }
            },
            getStatusClass(status) {
                switch(status) {
                    case 'APPROVED': return 'bg-blue-100 text-blue-700';
                    case 'RETURNED': return 'bg-green-100 text-green-700';
                    case 'PENDING': return 'bg-yellow-100 text-yellow-700';
                    case 'REJECTED': return 'bg-red-100 text-red-700';
                    default: return 'bg-slate-100 text-slate-700';
                }
            },
            async updateStatus(id, status) {
                if (!confirm('Konfirmasi santri telah kembali ke asrama?')) return;
                try {
                    const res = await fetch('../../api/boarding_permissions.php?action=update_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, status })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchPermissions();
                    }
                } catch (e) {}
            }
        },
        mounted() {
            this.fetchPermissions();
        }
    }).mount('#app')
</script>
</body>
</html>
