import { academicMixin } from '../modules/academic.js?v=5';
// adminMixin removed as it is not used and might cause conflicts
// import { adminMixin } from '../modules/admin.js?v=2.2';

const { createApp } = Vue;

try {
    const app = createApp({
        mixins: [academicMixin],
        data() {
            return {
                // Enable auto-fetch for this dashboard page
                manualFetchOnly: false, 
                currentUnit: 'all',
                isLoaded: false, // UI State to prevent FOUC
                
                // UI States
                showLevelModal: false,
                showCreateClassModal: false,
                showEditClassModal: false,
                
                // Local Forms
                levelForm: { name: '', order_index: 1 },
                newClassForm: { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 },
                editClassData: { id: '', name: '', level_id: '', homeroom_teacher_id: '', capacity: 30, sort_order: 0 }
            };
        },
        created() {
            // Set loaded immediately to show content, data will populate reactively
            // If we wait for mounted/fetch, user stares at skeleton too long if JS is slow
            this.isLoaded = true;
        },
        mounted() {
            // Initial fetch handled by mixin because manualFetchOnly is false
            // But we ensure staff list is loaded for modals
            if (this.fetchStaffList) {
                this.fetchStaffList();
            }
        },
        computed: {
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            },
            teachers() {
                return this.staffList || []; 
            }
        },
        methods: {
            // Wrapper methods if mixin methods need UI handling
            openCreateClassModal() {
                this.newClassForm = { name: '', level_id: '', homeroom_teacher_id: '', capacity: 30 };
                this.showCreateClassModal = true;
            },
            openEditClassModal(cls) {
                this.editClassData = { ...cls };
                this.showEditClassModal = true;
            },
            
            async saveLevel() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'create_level');
                    formData.append('unit_level', this.currentUnit === 'all' ? 'SD' : this.currentUnit); 
                    formData.append('name', this.levelForm.name);
                    formData.append('order_index', this.levelForm.order_index);
                    
                    const res = await axios.post('../../api/academic.php', formData);
                    if (res.data.success) {
                        this.showLevelModal = false;
                        this.fetchAcademicData(this.currentUnit);
                        Swal.fire('Berhasil', 'Tingkatan berhasil ditambahkan', 'success');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Error', 'Gagal menyimpan tingkatan', 'error');
                }
            },
            
            async deleteLevel(lvl) {
                const result = await Swal.fire({
                    title: 'Hapus Tingkatan?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!'
                });

                if (result.isConfirmed) {
                    try {
                        await axios.post('../../api/academic.php', {
                            action: 'delete_level',
                            id: lvl.id
                        }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
                        
                        this.fetchAcademicData(this.currentUnit);
                        Swal.fire('Terhapus!', 'Tingkatan telah dihapus.', 'success');
                    } catch (e) {
                        Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus.', 'error');
                    }
                }
            },

            async saveNewClass() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'create_class');
                    formData.append('name', this.newClassForm.name);
                    formData.append('level_id', this.newClassForm.level_id);
                    formData.append('homeroom_teacher_id', this.newClassForm.homeroom_teacher_id || '');
                    formData.append('capacity', this.newClassForm.capacity);
                    
                    const res = await axios.post('../../api/academic.php', formData);
                    if (res.data.success) {
                        this.showCreateClassModal = false;
                        this.fetchAcademicData(this.currentUnit);
                        Swal.fire('Berhasil', 'Kelas berhasil dibuat', 'success');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal membuat kelas', 'error');
                }
            },

            async updateClass() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_class');
                    formData.append('id', this.editClassData.id);
                    formData.append('name', this.editClassData.name);
                    formData.append('level_id', this.editClassData.level_id);
                    formData.append('homeroom_teacher_id', this.editClassData.homeroom_teacher_id || '');
                    formData.append('capacity', this.editClassData.capacity);
                    formData.append('sort_order', this.editClassData.sort_order);

                    const res = await axios.post('../../api/academic.php', formData);
                    if (res.data.success) {
                        this.showEditClassModal = false;
                        this.fetchAcademicData(this.currentUnit);
                        Swal.fire('Berhasil', 'Data kelas diperbarui', 'success');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal update kelas', 'error');
                }
            },

            async deleteClass(cls) {
                const result = await Swal.fire({
                    title: 'Hapus Kelas?',
                    text: `Kelas ${cls.name} akan dihapus permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!'
                });

                if (result.isConfirmed) {
                    try {
                        await axios.post('../../api/academic.php', {
                            action: 'delete_class',
                            id: cls.id
                        }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
                        
                        this.fetchAcademicData(this.currentUnit);
                        Swal.fire('Terhapus!', 'Kelas telah dihapus.', 'success');
                    } catch (e) {
                        Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
                    }
                }
            },
            
            openClassDetail(cls) {
                window.location.href = `class_detail.php?id=${cls.id}`;
            }
        }
    });

    app.mount('#app');
} catch (err) {
    console.error("Vue Mount Error:", err);
    // Fallback: Remove hidden style if Vue fails completely so user sees something (even if broken)
    // Or keep skeleton visible
    document.getElementById('skeleton-loader').innerHTML = '<div class="p-4 text-red-500">Error loading application. Please refresh.</div>';
}
