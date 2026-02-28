export const utilsMixin = {
    methods: {
        formatDate(dateString) {
            if (!dateString) return '-';
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('id-ID', options);
        },
        getUnitName(unitCode) {
            if (unitCode === 'all') return 'Yayasan (Global)';
            
            // Check if availableUnits exists (it should if loaded)
            const units = this.availableUnits || (this.$root && this.$root.availableUnits) || [];
            
            // Try to find by ID or Level (case insensitive)
            const unit = units.find(u => 
                u.id == unitCode || 
                (u.unit_level && u.unit_level.toLowerCase() === unitCode.toString().toLowerCase())
            );

            if (unit) {
                // Use prefix if available, otherwise name. 
                // User asked for prefix in switcher, so we prioritize it for display
                return unit.prefix || unit.name;
            }
            
            // Fallback to static if not loaded yet or not found
            const staticUnits = { tk: 'TK Al-Amanah', sd: 'SD Al-Amanah', smp: 'SMP Al-Amanah', sma: 'SMA Al-Amanah' };
            return staticUnits[unitCode] || unitCode;
        },
        getModuleName(key) {
            const names = { 
                core: 'Core System', 
                people: 'Data Induk (People)', 
                academic: 'Akademik', 
                library: 'Perpustakaan', 
                finance: 'Keuangan', 
                boarding: 'Asrama', 
                pos: 'POS (Kantin/Toko)', 
                hr: 'HR Basic', 
                counseling: 'BK & Kesiswaan',
                executive: 'Executive View (Custom)',
                payroll: 'Payroll (Add-on)',
                workspace: 'Workspace'
            };
            return names[key] || key;
        },
        getUnitId(code) {
            if (!code) return null;
            const units = this.availableUnits || (this.$root && this.$root.availableUnits) || [];
            const unit = units.find(u => u.unit_level && u.unit_level.toLowerCase() === code.toString().toLowerCase());
            return unit ? unit.id : null;
        },
        getTagClass(tag) {
            if (tag === 'core') return 'bg-blue-900 text-blue-200 border border-blue-700';
            if (tag === 'custom') return 'bg-purple-900 text-purple-200 border border-purple-700';
            if (tag === 'add-on') return 'bg-emerald-900 text-emerald-200 border border-emerald-700';
            return 'bg-slate-700 text-slate-400';
        },
        async loadNotifications() {
            try {
                // Use window.BASE_URL if available, otherwise assume relative to root or current path
                const baseUrl = window.BASE_URL || (window.location.pathname.includes('/AIS/') ? '/AIS/' : '/');
                const response = await fetch(baseUrl + 'api/notifications.php?action=get_counts');
                const result = await response.json();
                if (result.success && this.notificationCounts) {
                    this.notificationCounts = result.data;
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }
    }
};
