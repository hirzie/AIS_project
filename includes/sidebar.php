<?php
// includes/sidebar.php
// PHP-based Menu Definition for SSR
$menuStructurePHP = [
    "MENU UTAMA" => [
        ['id' => 'executive-view', 'label' => 'Managerial View', 'icon' => 'fas fa-chart-line', 'required' => 'executive', 'url' => 'modules/executive/index.php', 'tag' => 'custom'],
        ['id' => 'foundation-portal', 'label' => 'Portal Yayasan', 'icon' => 'fas fa-building', 'required' => 'foundation', 'url' => 'modules/foundation/index.php', 'tag' => 'custom'],
        ['id' => 'workspace-portal', 'label' => 'Workspace', 'icon' => 'fas fa-chalkboard-teacher', 'required' => 'workspace', 'url' => 'modules/workspace/index.php', 'tag' => 'custom'],
        ['id' => 'dash', 'label' => 'Dashboard Utama', 'icon' => 'fas fa-th-large', 'required' => 'core', 'url' => 'index.php', 'tag' => 'core'],
        ['id' => 'academic-portal', 'label' => 'Akademik', 'icon' => 'fas fa-graduation-cap', 'required' => 'academic', 'url' => 'modules/academic/index.php', 'tag' => 'core'],
        ['id' => 'finance-portal', 'label' => 'Keuangan Sekolah', 'icon' => 'fas fa-wallet', 'required' => 'finance', 'url' => 'modules/finance/dashboard.php', 'tag' => 'core'],
        ['id' => 'hr-portal', 'label' => 'Kepegawaian Basic', 'icon' => 'fas fa-users', 'required' => 'hr', 'url' => 'modules/personnel/dashboard.php', 'tag' => 'core'],
        ['id' => 'library-portal', 'label' => 'Perpustakaan', 'icon' => 'fas fa-book', 'required' => 'library', 'url' => 'modules/library/index.php', 'tag' => 'core'],
        ['id' => 'inventory-portal', 'label' => 'Inventory & Aset', 'icon' => 'fas fa-boxes', 'required' => 'inventory', 'url' => 'modules/inventory/dashboard.php', 'tag' => 'add-on'],
        ['id' => 'kiosk-portal', 'label' => 'Info Kiosk Display', 'icon' => 'fas fa-tv', 'required' => 'kiosk', 'url' => 'modules/kiosk/settings.php', 'tag' => 'add-on'],
        ['id' => 'boarding-portal', 'label' => 'Asrama', 'icon' => 'fas fa-bed', 'required' => 'boarding', 'url' => 'modules/boarding/index.php', 'tag' => 'add-on'],
        ['id' => 'pos-portal', 'label' => 'POS (Kantin/Toko)', 'icon' => 'fas fa-cash-register', 'required' => 'pos', 'url' => 'modules/pos/dashboard.php', 'tag' => 'add-on'],
        ['id' => 'hr-payroll', 'label' => 'HR & Payroll', 'icon' => 'fas fa-money-check-alt', 'required' => 'hr', 'tag' => 'add-on'],
        ['id' => 'counseling-portal', 'label' => 'BK & Kesiswaan', 'icon' => 'fas fa-user-friends', 'required' => 'counseling', 'url' => 'modules/counseling/index.php', 'tag' => 'add-on'],
        ['id' => 'cleaning-portal', 'label' => 'Kebersihan', 'icon' => 'fas fa-broom', 'required' => 'cleaning', 'url' => 'modules/cleaning/index.php', 'tag' => 'add-on'],
        ['id' => 'security-portal', 'label' => 'Keamanan', 'icon' => 'fas fa-shield-alt', 'required' => 'security', 'url' => 'modules/security/index.php', 'tag' => 'add-on']
    ],
    "PENGATURAN" => [
        ['id' => 'profile', 'label' => 'Pengaturan Profile', 'icon' => 'fas fa-id-card', 'required' => 'core', 'url' => 'index.php?page=profile', 'tag' => 'system'],
        ['id' => 'settings', 'label' => 'Pengaturan Sekolah', 'icon' => 'fas fa-school', 'required' => 'core', 'url' => 'index.php?page=settings', 'tag' => 'system'],
        ['id' => 'users', 'label' => 'Manajemen User', 'icon' => 'fas fa-users-cog', 'required' => 'core', 'url' => 'index.php?page=users', 'tag' => 'system'],
        ['id' => 'backup', 'label' => 'Backup & Restore', 'icon' => 'fas fa-history', 'required' => 'core', 'url' => 'modules/admin/backup.php', 'tag' => 'admin']
    ]
];

$activeMenuGroupsPHP = [];
$userRolePHP = strtoupper($_SESSION['role'] ?? '');
$allowedModulesPHP = $_SESSION['allowed_modules'] ?? [];

foreach ($menuStructurePHP as $groupName => $items) {
    $activeItems = [];
    if ($groupName === 'PENGATURAN' && !in_array($userRolePHP, ['ADMIN', 'SUPERADMIN'])) {
        // Non-admin only gets profile
        foreach ($items as $item) {
            if ($item['id'] === 'profile' && isset($allowedModulesPHP[$item['required']])) {
                $activeItems[] = $item;
            }
        }
    } else {
        foreach ($items as $item) {
            $req = $item['required'];
            if (isset($allowedModulesPHP[$req]) && $allowedModulesPHP[$req]) {
                $activeItems[] = $item;
            }
        }
    }
    
    if (!empty($activeItems)) {
        $activeMenuGroupsPHP[$groupName] = $activeItems;
    }
}

// Helper for Tag Class
function getTagClassPHP($tag) {
    if ($tag === 'core') return 'bg-blue-900 text-blue-200 border border-blue-700';
    if ($tag === 'custom') return 'bg-purple-900 text-purple-200 border border-purple-700';
    if ($tag === 'add-on') return 'bg-emerald-900 text-emerald-200 border border-emerald-700';
    if ($tag === 'admin') return 'bg-indigo-900 text-indigo-200 border border-indigo-700';
    if ($tag === 'system') return 'bg-slate-700 text-slate-300 border border-slate-600';
    return 'bg-slate-700 text-slate-400';
}

// Helper for Tag Inline Style (Pre-Tailwind load)
function getTagStylePHP($tag) {
    if ($tag === 'core') return 'background-color: #1e3a8a; color: #bfdbfe; border: 1px solid #1d4ed8;';
    if ($tag === 'custom') return 'background-color: #581c87; color: #e9d5ff; border: 1px solid #7e22ce;';
    if ($tag === 'add-on') return 'background-color: #064e3b; color: #a7f3d0; border: 1px solid #047857;';
    if ($tag === 'admin') return 'background-color: #312e81; color: #c7d2fe; border: 1px solid #4338ca;';
    if ($tag === 'system') return 'background-color: #334155; color: #cbd5e1; border: 1px solid #475569;';
    return 'background-color: #334155; color: #94a3b8; border: 1px solid #475569;';
}
?>
        <!-- SIDEBAR NAVIGATION -->
        <!-- Static width and color to prevent layout shift during load -->
        <aside style="display: flex; flex-direction: column; width: 16rem; background-color: #0f172a;" 
               class="static-sidebar fixed inset-y-0 left-0 w-64 bg-slate-900 text-white flex flex-col shadow-xl z-30 transition-transform duration-300 lg:static"
               :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            
            <!-- LOGO AREA -->
            <?php $logoUrl = $schoolSettingsPHP['logo_url'] ?? ''; ?>
            
            <!-- PHP RENDERED LOGO (Default) -->
            <div class="w-full h-28 flex items-center justify-center p-4 border-b border-slate-700 overflow-hidden sidebar-skeleton"
                 style="<?php echo $logoUrl ? '' : 'display:none;'; ?>"
                 v-if="!isMounted"> <!-- Hidden once Vue mounts -->
                <img src="<?php echo $logoUrl; ?>" alt="School Logo" class="w-full h-full object-contain">
            </div>

            <!-- VUE RENDERED LOGO (Takes over) -->
            <div class="w-full h-28 flex items-center justify-center p-4 border-b border-slate-700 overflow-hidden"
                 v-if="isMounted" v-cloak
                 v-show="schoolSettings.logo_url || '<?php echo $logoUrl; ?>'">
                <img src="<?php echo $logoUrl; ?>" :src="schoolSettings.logo_url || '<?php echo $logoUrl; ?>'" alt="School Logo" class="w-full h-full object-contain">
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                
                <!-- 1. PHP STATIC MENU (Visible immediately, Removed by Vue) -->
                <div v-if="!isMounted" class="sidebar-skeleton">
                    <?php foreach ($activeMenuGroupsPHP as $groupName => $items): ?>
                        <div class="mb-6">
                            <h3 class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2" style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; padding-left: 1rem; padding-right: 1rem;"><?php echo htmlspecialchars($groupName); ?></h3>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($items as $item): ?>
                                    <li>
                                        <div class="flex items-center px-4 py-2.5 border-l-4 border-transparent text-slate-300" style="display: flex; align-items: center; padding: 0.625rem 1rem; border-left-width: 4px; border-color: transparent; color: #cbd5e1;">
                                            <i class="<?php echo $item['icon']; ?> w-6" style="width: 1.5rem; display: inline-block;"></i>
                                            <span class="text-sm font-medium" style="font-size: 0.875rem; font-weight: 500;"><?php echo htmlspecialchars($item['label']); ?></span>
                                            <?php if (isset($item['tag'])): ?>
                                                <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded uppercase font-bold tracking-wider <?php echo getTagClassPHP($item['tag']); ?>" style="margin-left: auto; font-size: 10px; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; font-weight: bold; <?php echo getTagStylePHP($item['tag']); ?>">
                                                    <?php echo htmlspecialchars($item['tag']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 2. VUE DYNAMIC MENU (Hidden until Vue mounts) -->
                <div v-if="isMounted" v-cloak>
                    
                    <div v-for="(items, groupName) in activeMenuGroups" :key="groupName" class="mb-6">
                        <h3 class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ groupName }}</h3>
                        <ul>
                            <li v-for="item in items" :key="item.id">
                                <!-- Fix: Use item.url if available, else item.id -->
                                <a href="#" @click.prevent="navigate(item.url || item.id)" 
                                   :class="(currentPage === item.id || (item.url && isUrlActive(item.url))) ? 'bg-slate-800 text-white border-blue-500' : 'text-slate-300 hover:bg-slate-800 hover:text-white border-transparent hover:border-blue-500'" 
                                   class="flex items-center px-4 py-2.5 transition-colors border-l-4">
                                    <i :class="item.icon + ' w-6'"></i>
                                    <span class="text-sm font-medium">{{ item.label }}</span>
                                    <!-- Use dynamic class based on tag -->
                                    <span v-if="item.tag" 
                                          :class="getTagClass(item.tag)" 
                                          class="ml-auto text-[10px] px-1.5 py-0.5 rounded uppercase font-bold tracking-wider">
                                          {{ item.tag }}
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <?php
                // Use global $displayName if available (from index.php), otherwise fallback to session
                $sidebarName = $displayName ?? $_SESSION['username'] ?? 'User';
            ?>
            <div class="p-4 border-t border-slate-700 bg-slate-900">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($sidebarName); ?>&background=random" class="w-9 h-9 rounded-full">
                    <div class="flex-1 overflow-hidden">
                        <p class="text-sm font-medium truncate"><?= htmlspecialchars($sidebarName); ?></p>
                        <p class="text-xs text-slate-400">Online</p>
                    </div>
                    <a href="logout.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </aside>
