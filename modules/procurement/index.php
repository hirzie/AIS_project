<?php
require_once '../../config/database.php';
// Fix for session save path error - use local project directory
$sessPath = __DIR__ . '/../../sessions';
if (file_exists($sessPath)) { session_save_path($sessPath); }
elseif (file_exists('C:/xampp/tmp')) { session_save_path('C:/xampp/tmp'); }
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengajuan Procurement - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.4/vue.global.min.js"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        [v-cloak] { display: none; }
    </style>
</head>
<body class="p-6">

<div id="app" v-cloak class="max-w-5xl mx-auto">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Data Pengajuan Masuk</h1>
            <p class="text-slate-500 text-sm">Monitoring status pengajuan barang dan jasa</p>
        </div>
        <div class="flex gap-3">
            <a href="../../index.php" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center gap-2">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="submit.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i> Buat Pengajuan
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Menunggu</p>
            <p class="text-2xl font-bold text-amber-500">{{ stats.pending }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Disetujui</p>
            <p class="text-2xl font-bold text-emerald-500">{{ stats.approved }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Ditolak</p>
            <p class="text-2xl font-bold text-red-500">{{ stats.rejected }}</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Ref / Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Modul / Pemohon</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Detail Pengajuan</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Nominal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading" v-for="i in 3" :key="i" class="animate-pulse">
                        <td colspan="5" class="px-6 py-4"><div class="h-4 bg-slate-100 rounded w-full"></div></td>
                    </tr>
                    <tr v-if="!loading && list.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">
                            Belum ada data pengajuan.
                        </td>
                    </tr>
                    <tr v-for="item in list" :key="item.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-slate-800">#{{ item.reference_no }}</p>
                            <p class="text-[10px] text-slate-400">{{ formatDate(item.created_at) }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 uppercase mb-1 inline-block">
                                {{ item.module }}
                            </span>
                            <p class="text-xs font-medium text-slate-700">{{ item.requester }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-800 mb-1">{{ item.title }}</p>
                            <p class="text-xs text-slate-500 truncate max-w-xs">{{ item.description }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-800 font-mono">{{ formatCurrency(item.amount) }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full inline-block" 
                                :class="getStatusClass(item.status)">
                                {{ item.status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                list: [],
                loading: true,
                stats: { pending: 0, approved: 0, rejected: 0 }
            }
        },
        methods: {
            async fetchData() {
                this.loading = true;
                try {
                    // Reuse approval API list action
                    const res = await fetch('../../api/approval.php?action=get_list&status=ALL');
                    const data = await res.json();
                    if (data.success) {
                        this.list = data.data;
                        this.calculateStats();
                    }
                } catch (e) {
                    console.error('Failed to load data', e);
                } finally {
                    this.loading = false;
                }
            },
            calculateStats() {
                this.stats.pending = this.list.filter(i => i.status === 'PENDING').length;
                this.stats.approved = this.list.filter(i => i.status === 'APPROVED').length;
                this.stats.rejected = this.list.filter(i => i.status === 'REJECTED').length;
            },
            getStatusClass(status) {
                if (status === 'PENDING') return 'bg-amber-100 text-amber-600';
                if (status === 'APPROVED') return 'bg-emerald-100 text-emerald-600';
                if (status === 'REJECTED') return 'bg-red-100 text-red-600';
                return 'bg-slate-100 text-slate-600';
            },
            formatCurrency(val) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
            },
            formatDate(dateStr) {
                return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        },
        mounted() {
            this.fetchData();
        }
    }).mount('#app')
</script>

</body>
</html>
