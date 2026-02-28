<?php
require_once '../../includes/guard.php';
require_login_and_module('cleaning');
require_once '../../includes/header.php';
?>
<div id="app" class="flex-1 flex flex-col h-full overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-tasks text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Pengaturan Checklist</h1>
                <span class="text-xs text-slate-500 font-medium">Template Ceklist Kebersihan</span>
            </div>
        </div>
    </nav>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-slate-200">
                <i class="fas fa-clipboard-list text-6xl text-slate-200 mb-4"></i>
                <h3 class="text-xl font-bold text-slate-700">Halaman Pengaturan Checklist Sedang Dikembangkan</h3>
                <p class="text-sm text-slate-500 mt-2">Template dan kategori akan ditambahkan kemudian.</p>
            </div>
        </div>
    </main>
</div>
<?php require_once '../../includes/footer.php'; ?>

