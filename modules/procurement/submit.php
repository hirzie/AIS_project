<?php
require_once '../../config/database.php';
// Fix for session save path error - use local project directory
$sessPath = __DIR__ . '/../../sessions';
if (file_exists($sessPath)) { session_save_path($sessPath); }
elseif (file_exists('C:/xampp/tmp')) { session_save_path('C:/xampp/tmp'); }
session_start();

$module = $_GET['module'] ?? 'UMUM';
$label = $_GET['label'] ?? 'Pengajuan Barang';
$source = $_GET['source'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement - <?= htmlspecialchars($label) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.4/vue.global.min.js"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="p-6">

<div id="app" class="max-w-2xl mx-auto">
    
    <div class="flex items-center gap-4 mb-8">
        <a href="javascript:history.back()" class="w-10 h-10 bg-white rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-all">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Form Procurement</h1>
            <p class="text-slate-500 text-sm">Modul: <?= htmlspecialchars($module) ?> • <?= htmlspecialchars($label) ?></p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 md:p-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Modul / Departemen</label>
                    <input type="text" value="<?= htmlspecialchars($module) ?>" readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Pemohon</label>
                    <select v-model="form.requester" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:border-indigo-500 outline-none bg-white">
                        <option value="">-- Pilih Pegawai --</option>
                        <option v-for="emp in employees" :key="emp.employee_number" :value="emp.name">
                            {{ emp.name }} ({{ emp.position || 'Staff' }})
                        </option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Judul Pengajuan</label>
                <input v-model="form.title" type="text" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:border-indigo-500 outline-none" placeholder="Contoh: Pengadaan Buku Perpustakaan Semester Genap">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Estimasi Biaya (Rp)</label>
                <input v-model="form.amount" type="number" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:border-indigo-500 outline-none" placeholder="0">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Detail & Keterangan</label>
                <textarea v-model="form.description" rows="4" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:border-indigo-500 outline-none" placeholder="Jelaskan barang/jasa yang dibutuhkan..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Link Dokumen Pendukung (Optional)</label>
                <input v-model="form.attachment" type="text" class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:border-indigo-500 outline-none" placeholder="https://drive.google.com/...">
            </div>

        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
            <button @click="submit" :disabled="isSubmitting" class="w-full md:w-auto px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                {{ isSubmitting ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </button>
        </div>
    </div>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                employees: [],
                isSubmitting: false,
                form: {
                    module: '<?= $module ?>',
                    title: '',
                    requester: '',
                    amount: '',
                    description: '',
                    attachment: ''
                }
            }
        },
        methods: {
            async fetchEmployees() {
                try {
                    const res = await fetch('../../api/approval.php?action=get_employees');
                    const data = await res.json();
                    if (data.success) {
                        this.employees = data.data;
                    }
                } catch (e) {
                    console.error('Failed to load employees', e);
                }
            },
            async submit() {
                if (!this.form.title || !this.form.requester || !this.form.description) {
                    alert('Mohon lengkapi data pengajuan.');
                    return;
                }

                this.isSubmitting = true;
                try {
                    const res = await fetch('../../api/approval.php?action=create', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        alert('Pengajuan berhasil dikirim ke Approval Center!');
                        
                        // Redirect kembali ke modul asal jika terdeteksi
                        const module = '<?= $module ?>';
                        let redirectUrl = 'index.php'; // Default ke monitoring procurement
                        
                        if (module === 'BOARDING') {
                            redirectUrl = '../boarding/index.php';
                        } else if (module === 'LIBRARY') {
                            redirectUrl = '../library/index.php';
                        } else if (module === 'SARPRAS') {
                            redirectUrl = '../inventory/dashboard.php';
                        }
                        
                        window.location.href = redirectUrl;
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem');
                } finally {
                    this.isSubmitting = false;
                }
            }
        },
        mounted() {
            this.fetchEmployees();
        }
    }).mount('#app')
</script>

</body>
</html>
