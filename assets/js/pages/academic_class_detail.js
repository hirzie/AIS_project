import { academicMixin } from '../modules/academic.js?v=5';
import { studentMixin } from '../modules/student.js?v=3';
import { adminMixin } from '../modules/admin.js?v=2.2';

const { createApp } = Vue;

const app = createApp({
    mixins: [studentMixin, academicMixin, adminMixin],
    data() {
        return {
            manualFetchOnly: true, // Prevents academicMixin from auto-fetching on mount
            currentUnit: 'all',
            activeTab: 'students',
            showEditClassModal: false,
            isDetailLoading: true // Global loading state for this page
        };
    },
    created() {
        // HYBRID RENDERING OPTIMIZATION (Moved from mounted to created for faster render)
        if (window.INITIAL_DETAIL && window.INITIAL_DETAIL.class) {
            const urlParams = new URLSearchParams(window.location.search);
            let id = urlParams.get('id');

            // Validate ID match if possible, but trust PHP for now
            if (!id || String(window.INITIAL_DETAIL.class.id) === String(id)) {
                console.log("🚀 Hybrid Rendering: Hydrating data...");
                this.selectedClass = window.INITIAL_DETAIL.class;
                
                if (this.selectedClass.unit_code) {
                    this.currentUnit = this.selectedClass.unit_code;
                }

                this.classMembers = window.INITIAL_DETAIL.students || [];
                this.isDetailLoading = false;
                
                // Log success
                console.log("✅ Vue Hydrated with " + this.classMembers.length + " students");
            }
        }
    },
    computed: {
        // Override mixin's filteredClassMembers to be null-safe (Prevent Vue Crash)
        filteredClassMembers() {
            if (!this.classMembers || !Array.isArray(this.classMembers)) return [];
            if (!this.searchQuery) return this.classMembers;
            
            const lower = (this.searchQuery || '').toLowerCase();
            return this.classMembers.filter(s => {
                if (!s) return false;
                const name = (s.name || '').toLowerCase();
                const nis = (s.identity_number || '').toLowerCase();
                return name.includes(lower) || nis.includes(lower);
            });
        }
    },
    async mounted() {
        // Fetch auxiliary data for modals (Staff & Levels) asynchronously
        // Put in mounted to avoid blocking initial render
        setTimeout(() => {
            this.fetchStaffList(); 
            if (this.currentUnit) {
                this.fetchAcademicData(this.currentUnit).catch(err => console.warn("Background fetch warning:", err));
            }
        }, 100);

        // If data already loaded in created(), skip fetch
        if (this.selectedClass) return;

        const urlParams = new URLSearchParams(window.location.search);
        let id = urlParams.get('id');

        if (id) {
            try {
                // Load detail directly using mixin method
                await this.loadClassDetailDirectly(id);
            } catch (e) {
                console.error("Error loading class detail:", e);
            } finally {
                // Ensure loading state is turned off regardless of success/failure
                this.isDetailLoading = false;
            }
        } else {
            // No ID found, stop loading immediately to show empty state (if any)
            this.isDetailLoading = false;
        }
    }
});

app.mount('#app');
