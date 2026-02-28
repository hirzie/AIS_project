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
    <title>Menu Makan Asrama - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
    <style>
        [v-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
<div id="app" v-cloak class="flex flex-col h-screen">
    <?php require_once '../../includes/header_boarding.php'; ?>
    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Menu Makan Asrama</h2>
                    <p class="text-slate-500 text-sm">Daftar menu katering harian untuk santri</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-slate-200">
                <i class="fas fa-utensils text-6xl text-slate-200 mb-4"></i>
                <h3 class="text-xl font-bold text-slate-700">Modul Menu Makan Sedang Dikembangkan</h3>
                <p class="text-slate-500">Fitur ini akan segera hadir untuk memantau menu katering asrama.</p>
            </div>
        </div>
    </main>
</div>
<script>
    const { createApp } = Vue
    createApp({
        data() { return { currentGender: 'all' } },
        computed: { currentDate() { return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }); } }
    }).mount('#app')
</script>
</body>
</html>
