export const adminMixin = {
    data() {
        return {
            schoolSettings: Object.assign({
                name: '',
                npsn: '',
                address: '',
                phone: '',
                email: '',
                logo_url: '',
                footer_text: '',
                wa_api_url: '',
                wa_api_token: '',
                wa_security_target: '',
                google_gemini_api_key: ''
            }, (window.INITIAL_SETTINGS || {})),
            userList: [],
            userForm: {
                id: null,
                username: '',
                password: '',
                role: 'ADMIN',
                status: 'ACTIVE',
                email: '',
                access_modules: [],
                people_id: null
            },
            staffLookup: [],
            showUserModal: false,
            isUserEdit: false,
            adminLoading: false,
            
            // UNITS
            unitList: [],
            unitForm: { id: null, name: '', unit_level: 'SD', prefix: '', headmaster: '', address: '' },
            showUnitModal: false,
            isUnitEdit: false,
            
            // CONFIRMATION
            confirmModal: {
                show: false,
                title: '',
                message: '',
                onConfirm: null
            }
        };
    },
    methods: {
        // --- HELPERS ---
        confirmAction(title, message, callback) {
            this.confirmModal = {
                show: true,
                title: title,
                message: message,
                onConfirm: callback
            };
        },
        executeConfirm() {
            if (this.confirmModal.onConfirm) {
                this.confirmModal.onConfirm();
            }
            this.confirmModal.show = false;
        },

        // --- SETTINGS ---
        async fetchSettings() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = window.location.pathname.match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_settings.php');
                const data = await response.json();
                if (data) {
                    this.schoolSettings = { ...this.schoolSettings, ...data };
                }
                this.fetchUnits(); // Also fetch units when settings are loaded
            } catch (error) {
                console.error('Error fetching settings:', error);
            }
        },
        async saveSettings() {
            this.confirmAction('Simpan Pengaturan?', 'Apakah Anda yakin ingin menyimpan perubahan pengaturan sekolah?', async () => {
                this.adminLoading = true;
                try {
                    const baseUrl = window.BASE_URL || '/';
                    const formData = new FormData();
                    // Append all text settings
                    for (const key in this.schoolSettings) {
                        if (key !== 'logo_file') {
                            formData.append(key, this.schoolSettings[key] || '');
                        }
                    }
                    // Append file if exists
                    if (this.schoolSettings.logo_file) {
                        formData.append('logo_file', this.schoolSettings.logo_file);
                    }

                    const response = await fetch(baseUrl + 'api/save_settings.php', {
                        method: 'POST',
                        // Headers are not set manually for FormData to allow browser to set boundary
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Pengaturan berhasil disimpan!');
                        if (result.logo_url) {
                            this.schoolSettings.logo_url = result.logo_url;
                            // Update root app logo if possible
                            if (this.$root) this.$root.schoolLogo = result.logo_url;
                        }
                    } else {
                        alert('Gagal menyimpan: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error saving settings:', error);
                    alert('Terjadi kesalahan sistem.');
                } finally {
                    this.adminLoading = false;
                }
            });
        },
        async testWaNotification() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const msg = (this.waTestMessage || '').trim();
                if (!msg) {
                    alert('Isi pesan uji WA terlebih dahulu');
                    return;
                }
                const target = (this.schoolSettings.wa_security_target || '').trim();
                const res = await fetch(baseUrl + 'api/security.php?action=send_wa_test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg, target })
                });
                const j = await res.json();
                alert(j.success ? 'Uji kirim WA berhasil' : ('Uji kirim WA gagal: ' + (j.message || '')));
            } catch (e) {
                alert('Terjadi kesalahan sistem saat mengirim WA');
                console.error(e);
            }
        },
        handleLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.schoolSettings.logo_file = file;
                // Preview
                this.schoolSettings.logo_url = URL.createObjectURL(file);
            }
        },

        // --- UNITS ---
        async fetchUnits() {
            try {
                // FALLBACK
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_units.php');
                this.unitList = await response.json();
                // Update global units for switcher if needed (though app.js handles global state, we might need to emit or update root)
                // Since this is a mixin, we can try to update the root data if it exists there
                if (this.availableUnits) {
                    // This assumes the mixin is used in the main app where availableUnits is defined
                    // However, we should probably let app.js fetch this globally on load, 
                    // but for now, let's just keep local list up to date.
                }
            } catch (error) {
                console.error('Error fetching units:', error);
            }
        },
        openAddUnitModal() {
            this.unitForm = { id: null, name: '', unit_level: 'SD', prefix: '', headmaster: '', address: '' };
            this.isUnitEdit = false;
            this.showUnitModal = true;
        },
        editUnit(unit) {
            this.unitForm = { ...unit };
            this.isUnitEdit = true;
            this.showUnitModal = true;
        },
        async saveUnit() {
            if (!this.unitForm.name) return alert('Nama unit wajib diisi');
            
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_unit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: this.isUnitEdit ? 'update' : 'create',
                        ...this.unitForm
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Data unit berhasil disimpan!');
                    this.showUnitModal = false;
                    this.fetchUnits();
                } else {
                    alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving unit:', error);
            }
        },
        async deleteUnit(id) {
            this.confirmAction('Hapus Unit?', 'Apakah Anda yakin ingin menghapus unit ini? Data yang terkait mungkin akan error.', async () => {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const response = await fetch(baseUrl + 'api/manage_unit.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    });
                    if ((await response.json()).success) this.fetchUnits();
                } catch (e) { console.error(e); }
            });
        },

        // --- USERS ---
        async fetchUsers() {
            this.adminLoading = true;
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/get_users.php');
                const data = await response.json();
                this.userList = (data || []).map(u => {
                    const modules = Array.isArray(u.access_modules) ? u.access_modules : [];
                    return {
                        ...u,
                        email: u.email || '',
                        status: (u.status || 'ACTIVE').toUpperCase(),
                        access_modules: modules
                    };
                });
            } catch (error) {
                console.error('Error fetching users:', error);
            } finally {
                this.adminLoading = false;
            }
        },
        openAddUserModal() {
            this.userForm = { id: null, username: '', password: '', role: 'STAFF', status: 'ACTIVE', email: '', access_modules: [], people_id: null };
            this.isUserEdit = false;
            this.showUserModal = true;
            this.fetchStaffLookup();
        },
        editUser(user) {
            const role = (user.role || 'ADMIN').toUpperCase();
            const mods = (user.access_modules || []);
            this.userForm = { 
                ...user, 
                password: '', 
                role: role, 
                status: (user.status || 'ACTIVE'), 
                email: (user.email || ''), 
                access_modules: (['ADMIN','SUPERADMIN'].includes(role) ? [] : mods),
                people_id: (user.people_id || null) 
            };
            this.isUserEdit = true;
            this.showUserModal = true;
            this.fetchStaffLookup();
        },
        async fetchStaffLookup() {
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const res = await fetch(baseUrl + 'api/get_all_staff.php');
                if (!res.ok) { this.staffLookup = []; return; }
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    this.staffLookup = [];
                    return;
                }
                this.staffLookup = await res.json();
            } catch (e) { console.error(e); }
        },
        async saveUser() {
            if (!this.userForm.username) return alert('Username wajib diisi');
            if (!this.isUserEdit && !this.userForm.password) return alert('Password wajib diisi');

            this.adminLoading = true;
            try {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                const response = await fetch(baseUrl + 'api/manage_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: this.isUserEdit ? 'update' : 'create',
                        id: this.userForm.id,
                        username: this.userForm.username,
                        password: this.userForm.password,
                        role: this.userForm.role,
                        status: this.userForm.status,
                        email: this.userForm.email,
                        access_modules: this.userForm.access_modules,
                        people_id: this.userForm.people_id
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Data user berhasil disimpan!');
                    this.showUserModal = false;
                    this.fetchUsers();
                } else {
                    alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving user:', error);
                alert('Terjadi kesalahan sistem.');
            } finally {
                this.adminLoading = false;
            }
        },
        async deleteUser(id) {
            this.confirmAction('Hapus User?', 'Apakah Anda yakin ingin menghapus user ini?', async () => {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const response = await fetch(baseUrl + 'api/manage_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.fetchUsers();
                    } else {
                        alert('Gagal menghapus: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error deleting user:', error);
                }
            });
        }
    },
    mounted() {
        // We only fetch if we are on the specific page to save initial load time
        if (this.currentPage === 'settings') this.fetchSettings();
        if (this.currentPage === 'users') this.fetchUsers();
        
        // Ensure settings are fetched globally if not already present
        // This is handled by app.js calling fetchSettings, but we can double check
        if (!this.schoolSettings.name && this.currentPage !== 'settings') {
             this.fetchSettings();
        }
    },
    watch: {
        currentPage(newVal) {
            if (newVal === 'settings') this.fetchSettings();
            if (newVal === 'users') this.fetchUsers();
        }
    }
};
