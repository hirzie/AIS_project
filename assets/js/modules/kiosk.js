export const kioskMixin = {
    data() {
        return {
            activeTab: 'tamu',
            saving: false,
            settings: {
                tamu: { welcome_text: '', running_text: '' },
                kantor: { teacher_agenda: [] },
                masjid: { prayer_times: { subuh:'', dzuhur:'', ashar:'', maghrib:'', isya:'' } },
                aula: { news_items: [] }
            }
        }
    },
    mounted() {
        if (window.SERVER_SETTINGS) {
            // Merge server settings
            if (window.SERVER_SETTINGS.tamu) this.settings.tamu = window.SERVER_SETTINGS.tamu;
            if (window.SERVER_SETTINGS.kantor) this.settings.kantor = window.SERVER_SETTINGS.kantor;
            if (window.SERVER_SETTINGS.masjid) this.settings.masjid = window.SERVER_SETTINGS.masjid;
            if (window.SERVER_SETTINGS.aula) this.settings.aula = window.SERVER_SETTINGS.aula;
        }
    },
    methods: {
        addAgenda() {
            this.settings.kantor.teacher_agenda.push({ time: '', activity: '', location: '' });
        },
        removeAgenda(index) {
            this.settings.kantor.teacher_agenda.splice(index, 1);
        },
        addNews() {
            this.settings.aula.news_items.push({ title: '', content: '' });
        },
        removeNews(index) {
            this.settings.aula.news_items.splice(index, 1);
        },
        async saveSettings() {
            this.saving = true;
            
            // Transform to array for API
            const payload = [];
            
            // Tamu
            payload.push({ zone: 'tamu', key: 'welcome_text', value: this.settings.tamu.welcome_text });
            payload.push({ zone: 'tamu', key: 'running_text', value: this.settings.tamu.running_text });
            
            // Kantor
            payload.push({ zone: 'kantor', key: 'teacher_agenda', value: this.settings.kantor.teacher_agenda });
            
            // Masjid
            payload.push({ zone: 'masjid', key: 'prayer_times', value: this.settings.masjid.prayer_times });
            
            // Aula
            payload.push({ zone: 'aula', key: 'news_items', value: this.settings.aula.news_items });

            try {
                const targetUrl = window.location && window.location.href ? window.location.href : (window.BASE_URL || '/') + 'modules/kiosk/settings.php';
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    alert('Pengaturan berhasil disimpan!');
                } else {
                    alert('Gagal menyimpan: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan koneksi.');
                console.error(error);
            } finally {
                this.saving = false;
            }
        }
    }
};
