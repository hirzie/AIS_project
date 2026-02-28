<div :class="currentPosition === 'guru' ? 'hidden md:flex' : 'flex'" class="bg-gradient-to-r from-indigo-600 via-blue-600 to-emerald-600 text-white rounded-2xl shadow-md px-6 py-5 items-center justify-between">
    <div>
        <div class="text-sm opacity-90">Selamat Datang</div>
        <div class="text-2xl font-bold leading-tight"><?php echo htmlspecialchars($__displayName); ?></div>
    </div>
    <span class="text-xs bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">{{ unitName }} · {{ positionName }}</span>
</div>
