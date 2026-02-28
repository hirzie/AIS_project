<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../includes/header.php';
require_once '../../config/database.php';

$logs = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        entity_id VARCHAR(64) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $stmt = $pdo->prepare("SELECT l.*, u.username, p.name AS people_name
                           FROM activity_logs l 
                           LEFT JOIN core_users u ON l.user_id = u.id 
                           LEFT JOIN core_people p ON u.people_id = p.id 
                           WHERE l.module = 'ACADEMIC'
                           ORDER BY l.created_at DESC, l.id DESC 
                           LIMIT 200");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $logs = []; }
?>
<div id="app" v-cloak class="flex-1 flex flex-col h-full overflow-hidden">
    <?php require_once '../../includes/academic_header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Aktivitas Terbaru</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Entity</th>
                                <th class="px-4 py-3">Judul</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($logs)) : ?>
                                <?php foreach ($logs as $row) : ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-2 text-slate-500 text-xs"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td class="px-4 py-2 text-slate-700 text-xs"><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td class="px-4 py-2 text-slate-700 text-xs"><?php echo htmlspecialchars($row['action']); ?></td>
                                        <td class="px-4 py-2 text-slate-700 text-xs">
                                            <div class="font-mono"><?php echo htmlspecialchars($row['entity_type'] ?: '-'); ?></div>
                                            <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($row['entity_id'] ?: '-'); ?></div>
                                        </td>
                                        <td class="px-4 py-2 text-slate-700 text-sm"><?php echo htmlspecialchars($row['title'] ?: '-'); ?></td>
                                        <td class="px-4 py-2 text-slate-600 text-sm"><?php echo htmlspecialchars($row['description'] ?: '-'); ?></td>
                                        <td class="px-4 py-2 text-slate-600 text-xs"><?php echo htmlspecialchars($row['people_name'] ?: ($row['username'] ?: '-')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Belum ada catatan aktivitas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = window.Vue || {};
createApp({
    data() {
        return {
            currentUnit: 'all',
            availableUnits: []
        };
    },
    computed: {
        isAdminRole() {
            const role = String(window.USER_ROLE || '').toUpperCase();
            return ['SUPERADMIN','ADMIN'].includes(role);
        },
        allowedUnitCodes() {
            const arr = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
            return arr.map(s => String(s || '').trim().toUpperCase());
        },
        currentDate() {
            try {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            } catch (_) {
                const d = new Date();
                const pad = n => String(n).padStart(2, '0');
                return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
            }
        }
    },
    async mounted() {
        try {
            let baseUrl = window.BASE_URL || '/';
            if (baseUrl === '/' || !baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                baseUrl = m ? `/${m[1]}/` : '/';
            }
            const res = await fetch(baseUrl + 'api/get_units.php');
            const data = await res.json();
            let units = Array.isArray(data) ? data : [];
            if (!this.isAdminRole) {
                const allowed = new Set(this.allowedUnitCodes);
                units = units.filter(u => allowed.has(String(u.code || '').toUpperCase()) || allowed.has(String(u.prefix || '').toUpperCase()));
                if (units.length > 0) {
                    this.currentUnit = units[0].code || units[0].unit_level || 'all';
                }
            }
            this.availableUnits = units;
        } catch (_) {
            this.availableUnits = [];
        }
    }
}).mount('#app');
</script>
