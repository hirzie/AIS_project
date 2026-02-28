<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Agenda Sekolah</h1>
                    <p class="text-slate-500 text-sm">Kalender Kegiatan Akademik & Non-Akademik</p>
                </div>
                <div class="flex gap-3">
                    <button @click="syncGoogle" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center gap-2">
                        <i class="fab fa-google text-red-500"></i> Sync Google Calendar
                    </button>
                    <button @click="openSettings()" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors flex items-center gap-2">
                        <i class="fas fa-cog"></i> Pengaturan
                    </button>
                    
                </div>
            </div>

            <!-- Calendar / List Toggle -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2">
                        <button @click="changeMonth(-1)" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100"><i class="fas fa-chevron-left"></i></button>
                        <h2 class="text-xl font-bold text-slate-800 w-48 text-center">{{ currentMonthName }} {{ currentYear }}</h2>
                        <button @click="changeMonth(1)" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="flex gap-2 items-center">
                        <span class="px-2 py-1 rounded text-xs font-bold border bg-red-50 text-red-600 border-red-200 flex items-center gap-1">
                            <i class="fab fa-google"></i> Google
                        </span>
                        <button @click="viewMode = 'calendar'" :class="viewMode === 'calendar' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500'" class="px-3 py-1 rounded text-sm font-bold">Kalender</button>
                        <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500'" class="px-3 py-1 rounded text-sm font-bold">List</button>
                    </div>
                </div>

                <!-- Calendar View -->
                <div v-if="viewMode === 'calendar'">
                    <div class="grid grid-cols-7 gap-1 mb-2 text-center text-sm font-bold text-slate-400 uppercase">
                        <div class="py-2">Min</div>
                        <div class="py-2">Sen</div>
                        <div class="py-2">Sel</div>
                        <div class="py-2">Rab</div>
                        <div class="py-2">Kam</div>
                        <div class="py-2">Jum</div>
                        <div class="py-2">Sab</div>
                    </div>
                    <div class="grid grid-cols-7 gap-1">
                        <div v-for="day in calendarDays" :key="day.date" 
                             class="min-h-[100px] border border-slate-100 rounded-lg p-2 relative group hover:border-blue-200 transition-colors"
                             :class="{'bg-slate-50 opacity-50': !day.isCurrentMonth, 'bg-white': day.isCurrentMonth}">
                            <span class="text-sm font-bold" :class="day.isToday ? 'bg-blue-600 text-white w-6 h-6 flex items-center justify-center rounded-full' : 'text-slate-700'">{{ day.day }}</span>
                            
                            <div class="mt-2 space-y-1">
                                <div v-for="event in getEventsForDate(day.date)" :key="event.id" 
                                     @click="editEvent(event)"
                                     class="text-[10px] px-1.5 py-1 rounded truncate cursor-pointer font-medium border-l-2"
                                    :style="getEventStyle(event)"
                                    :class="getEventClass(event)">
                                    {{ event.title }} 
                                    <i v-if="isGoogleEvent(event)" class="fab fa-google ml-1 text-red-500"></i>
                                    <i v-else-if="event.google_event_id" class="fas fa-check-circle ml-1 text-emerald-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else class="space-y-3">
                    <div v-if="events.length === 0" class="text-center py-10 text-slate-400">Tidak ada agenda bulan ini.</div>
                    <div v-for="event in events" :key="event.id" class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 transition-all" :style="getEventStyle(event)">
                        <div class="w-16 text-center">
                            <div class="text-xs font-bold text-slate-500 uppercase">{{ formatDate(event.start_date, 'month') }}</div>
                            <div class="text-xl font-bold text-slate-800">{{ formatDate(event.start_date, 'day') }}</div>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-800">
                                {{ event.title }}
                                <i v-if="isGoogleEvent(event)" class="fab fa-google ml-2 text-red-500"></i>
                                <i v-else-if="event.google_event_id" class="fas fa-check-circle ml-2 text-emerald-600" title="Tersinkron ke Google"></i>
                            </h3>
                            <p class="text-sm text-slate-500">{{ event.description }}</p>
                            <div class="flex gap-3 mt-1 text-xs font-bold text-slate-400">
                                <span><i class="far fa-clock mr-1"></i> {{ formatTime(event.start_date) }} - {{ formatTime(event.end_date) }}</span>
                                <span v-if="event.location"><i class="fas fa-map-marker-alt mr-1"></i> {{ event.location }}</span>
                            </div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                <span class="font-bold">Start:</span> {{ event.start_date }} 
                                <span class="mx-2">•</span>
                                <span class="font-bold">End:</span> {{ event.end_date }}
                            </div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                <span class="font-bold">Color:</span> {{ event.color || '-' }}
                                <span class="mx-2">•</span>
                                <span class="font-bold">Google ID:</span> {{ getGoogleId(event) || '-' }}
                                <span class="mx-2">•</span>
                                <span class="font-bold">Calendar:</span> {{ event.calendar_id || '-' }}
                            </div>
                        </div>
                        <div>
                            <span class="px-2 py-1 rounded text-xs font-bold border" :class="getEventBadge(event.type)">{{ event.type }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button @click="editEvent(event)" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-blue-600 hover:bg-blue-50 flex items-center justify-center"><i class="fas fa-pencil-alt"></i></button>
                            <button v-if="!isGoogleEvent(event)" @click="deleteEvent(event)" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-red-600 hover:bg-red-50 flex items-center justify-center"><i class="fas fa-trash"></i></button>
                            <button v-if="!isGoogleEvent(event) && event.google_event_id" @click="unsyncGoogle(event)" class="px-2 h-8 rounded-full bg-yellow-100 border border-yellow-300 text-yellow-700 text-[11px] font-bold hover:bg-yellow-200 flex items-center justify-center">
                                Putus Sync
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl transform transition-all scale-100">
                <h3 class="text-xl font-bold text-slate-800 mb-4">{{ form.id ? 'Edit Agenda' : 'Tambah Agenda Baru' }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Judul Kegiatan</label>
                        <input v-model="form.title" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Mulai</label>
                            <input v-model="form.start_date" type="datetime-local" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label>
                            <input v-model="form.end_date" type="datetime-local" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tipe</label>
                        <select v-model="form.type" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500 bg-white">
                            <option value="ACADEMIC">Akademik</option>
                            <option value="HOLIDAY">Libur</option>
                            <option value="EVENT">Event / Acara</option>
                            <option value="MEETING">Rapat</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Lokasi</label>
                        <input v-model="form.location" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Deskripsi</label>
                        <textarea v-model="form.description" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500 h-24"></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="pushGoogle" type="checkbox" v-model="form.push_to_google">
                        <label for="pushGoogle" class="text-xs font-bold text-slate-600">Sync ke Google Calendar</label>
                    </div>
                    <div v-if="form.google_event_id" class="flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-2 rounded-lg">
                        <div class="text-xs font-bold">Status Sync: Terhubung</div>
                        <button @click="unsyncGoogle(form); form.google_event_id=null;" class="text-[11px] font-bold px-2 py-1 bg-yellow-100 border border-yellow-300 text-yellow-700 rounded hover:bg-yellow-200">Putus Sync</button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Batal</button>
                    <button @click="saveEvent" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">Simpan</button>
                </div>
            </div>
        </div>
        <div v-if="showSettingsModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify_center backdrop-blur-sm" @click.self="showSettingsModal = false">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Pengaturan Google Calendar</h3>
                <div class="space-y-4">
                    <div class="text-sm text-slate-600">Status Token: <span :class="googleSettings.token_present ? 'text-emerald-600' : 'text-red-600'">{{ googleSettings.token_present ? 'Terhubung' : 'Belum Terhubung' }}</span></div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Calendar ID</label>
                        <input v-model="googleSettings.calendar_id" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500" placeholder="primary atau alamat kalender">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showSettingsModal=false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Tutup</button>
                    <button @click="saveGoogleSettings" class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-slate-900">Simpan</button>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                viewMode: 'calendar',
                currentDateObj: new Date(),
                events: [],
                eventsRaw: [],
                showModal: false,
                availableUnits: [],
                currentUnit: 'all',
                showSettingsModal: false,
                googleSettings: { token_present: false, calendar_id: '' },
                        lastSyncRequestAt: 0,
                        syncCooldownMs: 60000,
                form: {
                    id: null,
                    title: '',
                    description: '',
                    start_date: '',
                    end_date: '',
                    location: '',
                    type: 'EVENT',
                    push_to_google: false,
                    google_event_id: null
                }
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
            currentMonthName() {
                return this.currentDateObj.toLocaleString('id-ID', { month: 'long' });
            },
            currentYear() {
                return this.currentDateObj.getFullYear();
            },
            calendarDays() {
                const year = this.currentDateObj.getFullYear();
                const month = this.currentDateObj.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                
                const days = [];
                
                // Previous month days
                const startPad = firstDay.getDay(); // 0 (Sun) - 6 (Sat)
                for (let i = startPad; i > 0; i--) {
                    const d = new Date(year, month, 1 - i);
                    days.push({
                        date: this.formatYMD(d),
                        day: d.getDate(),
                        isCurrentMonth: false,
                        isToday: false
                    });
                }
                
                // Current month days
                for (let i = 1; i <= lastDay.getDate(); i++) {
                    const d = new Date(year, month, i);
                    const today = new Date();
                    days.push({
                        date: this.formatYMD(d),
                        day: i,
                        isCurrentMonth: true,
                        isToday: d.toDateString() === today.toDateString()
                    });
                }
                
                // Next month days to fill grid (42 cells = 6 rows x 7 cols)
                const remaining = 42 - days.length;
                for (let i = 1; i <= remaining; i++) {
                    const d = new Date(year, month + 1, i);
                    days.push({
                        date: this.formatYMD(d),
                        day: i,
                        isCurrentMonth: false,
                        isToday: false
                    });
                }
                
                return days;
            }
        },
        watch: {
            currentUnit() {
                this.fetchEvents();
            }
        },
        async mounted() {
            await this.fetchUnits();
            this.fetchEvents();
        },
        methods: {
            async fetchUnits() {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const res = await fetch(baseUrl + 'api/get_units.php');
                    const data = await res.json();
                    this.availableUnits = Array.isArray(data) ? data : [];
                } catch (e) {
                    this.availableUnits = [];
                }
            },
            async fetchEvents() {
                const year = this.currentDateObj.getFullYear();
                const month = this.currentDateObj.getMonth() + 1;
                const start = `${year}-${String(month).padStart(2, '0')}-01`;
                const end = this.formatYMD(new Date(year, month, 0));
                
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const res = await fetch(baseUrl + `api/manage_agenda.php?action=get_agenda&start=${start}&end=${end}&unit=${encodeURIComponent(this.currentUnit)}&debug=1`);
                    const ct = res.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) {
                        const txt = await res.text();
                        console.error('Agenda API returned non-JSON', txt);
                        return;
                    }
                    const data = await res.json();
                    if (data.success) {
                        this.eventsRaw = data.data;
                        this.events = this.eventsRaw;
                        if (data.debug) { console.log('Agenda debug:', data.debug); }
                        // Trigger background sync to update cache without blocking UI
                        this.syncGoogleCache(start, end);
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            async syncGoogleCache(start, end) {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const now = Date.now();
                    if ((now - this.lastSyncRequestAt) < this.syncCooldownMs) return; // throttle
                    this.lastSyncRequestAt = now;
                    // Jika 'Semua', sync semua unit pada map
                    if (String(this.currentUnit).toLowerCase() === 'all') {
                        const settingsRes = await fetch(baseUrl + 'api/manage_agenda.php?action=get_google_settings&unit=all');
                        const settings = await settingsRes.json();
                        const map = (settings && settings.data && settings.data.map) ? settings.data.map : {};
                        const units = Object.keys(map || {});
                        // Fallback if no map: sync 'all' anyway to hit default calendar
                        if (units.length === 0) {
                             const url = baseUrl + `api/manage_agenda.php?action=sync_google_cache&start=${start}&end=${end}&unit=all&force=1`;
                             try { await fetch(url); } catch (e) {}
                        } else {
                            for (const u of units) {
                                const url = baseUrl + `api/manage_agenda.php?action=sync_google_cache&start=${start}&end=${end}&unit=${encodeURIComponent(u)}&force=1`;
                                try { await fetch(url); } catch (e) {}
                            }
                        }
                        setTimeout(() => this.fetchEvents(), 600);
                        return;
                    } else {
                        const url = baseUrl + `api/manage_agenda.php?action=sync_google_cache&start=${start}&end=${end}&unit=${encodeURIComponent(this.currentUnit)}&force=1`;
                        const res = await fetch(url);
                        const j = await res.json();
                        if (j && j.success && !j.skipped && (j.updated || 0) > 0) {
                            setTimeout(() => this.fetchEvents(), 400);
                        }
                    }
                } catch (e) {
                    // ignore failures; UI already shows cached data
                }
            },
            openSettings() {
                this.fetchGoogleSettings().then(() => { this.showSettingsModal = true; });
            },
            async fetchGoogleSettings() {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const r = await fetch(baseUrl + 'api/manage_agenda.php?action=get_google_settings&unit=' + encodeURIComponent(this.currentUnit));
                    const j = await r.json();
                    if (j.success) this.googleSettings = j.data || { token_present: false, calendar_id: '' };
                } catch (e) {}
            },
            async saveGoogleSettings() {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const r = await fetch(baseUrl + 'api/manage_agenda.php?action=save_google_settings', {
                        method: 'POST',
                        body: JSON.stringify({ calendar_id: this.googleSettings.calendar_id, unit: this.currentUnit })
                    });
                    const j = await r.json();
                    if (j.success) { this.showSettingsModal = false; this.fetchEvents(); } else { alert(j.message || 'Gagal menyimpan'); }
                } catch (e) { alert('Gagal menyimpan'); }
            },
                    isGoogleEvent(event) {
                        return String(event && event.id || '').startsWith('google:');
                    },
            getEventsForDate(dateStr) {
                return this.events.filter(e => {
                    const s = String(e.start_date || '');
                    const t = String(e.end_date || '');
                    let sd = s.slice(0, 10);
                    let ed = t.slice(0, 10);
                    if (!sd || !ed) return false;
                    const st = s.slice(11, 19);
                    const et = t.slice(11, 19);
                    const diffDays = (new Date(ed + 'T00:00:00').getTime() - new Date(sd + 'T00:00:00').getTime()) / 86400000;
                    const looksAllDay = (st === '00:00:00' && et === '23:59:59');
                    // Guard: jika terlihat all-day dan selisih <= 1 hari, tampilkan hanya pada start date
                    if (looksAllDay && diffDays <= 1) {
                        return dateStr === sd;
                    }
                    if (ed < sd) ed = sd;
                    return sd <= dateStr && dateStr <= ed;
                });
            },
            changeMonth(delta) {
                this.currentDateObj = new Date(this.currentDateObj.getFullYear(), this.currentDateObj.getMonth() + delta, 1);
                this.fetchEvents();
            },
            openModal() {
                this.form = {
                    id: null,
                    title: '',
                    description: '',
                    start_date: new Date().toISOString().slice(0, 16),
                    end_date: new Date().toISOString().slice(0, 16),
                    location: '',
                    type: 'EVENT',
                    push_to_google: false
                };
                this.showModal = true;
            },
            editEvent(event) {
                this.form = { ...event };
                // Format datetime for input
                this.form.start_date = event.start_date.replace(' ', 'T').slice(0, 16);
                this.form.end_date = event.end_date.replace(' ', 'T').slice(0, 16);
                this.form.push_to_google = false;
                this.form.google_event_id = event.google_event_id || null;
                this.showModal = true;
            },
            async saveEvent() {
                if (!this.form.title || !this.form.start_date) return alert('Judul dan Tanggal Mulai wajib diisi');
                
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const payload = { ...this.form, unit: this.currentUnit };
                    const res = await fetch(baseUrl + 'api/manage_agenda.php?action=save_agenda', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showModal = false;
                        this.fetchEvents();
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    alert('Error saving event');
                }
            },
            async deleteEvent(event) {
                if (!confirm('Hapus agenda ini?')) return;
                
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const res = await fetch(baseUrl + 'api/manage_agenda.php?action=delete_agenda', {
                        method: 'POST',
                        body: JSON.stringify({ id: event.id, unit: this.currentUnit })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchEvents();
                    }
                } catch (e) {
                    alert('Error deleting event');
                }
            },
            async unsyncGoogle(event) {
                if (!confirm('Putus sinkronisasi dengan Google? Event di Google akan tetap ada.')) return;
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const res = await fetch(baseUrl + 'api/manage_agenda.php?action=unsync_google', {
                        method: 'POST',
                        body: JSON.stringify({ id: event.id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchEvents();
                    } else {
                        alert(data.message || 'Gagal memutus sinkronisasi');
                    }
                } catch (e) {
                    alert('Gagal memutus sinkronisasi');
                }
            },
            async syncGoogle() {
                try {
                    let baseUrl = window.BASE_URL || '/';
                    if (baseUrl === '/' || !baseUrl) {
                        const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                        baseUrl = m ? `/${m[1]}/` : '/';
                    }
                    const res = await fetch(baseUrl + 'api/manage_agenda.php?action=sync_google', { method: 'POST' });
                    const data = await res.json();
                    
                    if (data.auth_required && data.auth_url) {
                        // Redirect to Google Auth
                        window.location.href = data.auth_url;
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    alert('Gagal menghubungi server sync');
                }
            },
            getEventClass(event) {
                if (event && event.color) return 'border-l-2';
                const type = event ? event.type : 'EVENT';
                const colors = {
                    'ACADEMIC': 'bg-blue-100 text-blue-700 border-blue-500',
                    'HOLIDAY': 'bg-red-100 text-red-700 border-red-500',
                    'EVENT': 'bg-purple-100 text-purple-700 border-purple-500',
                    'MEETING': 'bg-orange-100 text-orange-700 border-orange-500'
                };
                return colors[type] || 'bg-slate-100 text-slate-700 border-slate-500';
            },
            getEventBadge(type) {
                const colors = {
                    'ACADEMIC': 'bg-blue-50 text-blue-600 border-blue-200',
                    'HOLIDAY': 'bg-red-50 text-red-600 border-red-200',
                    'EVENT': 'bg-purple-50 text-purple-600 border-purple-200',
                    'MEETING': 'bg-orange-50 text-orange-600 border-orange-200'
                };
                return colors[type];
            },
            hexToRgb(hex) {
                let h = String(hex || '').trim();
                if (!h) return null;
                if (h[0] === '#') h = h.slice(1);
                if (h.length === 3) h = h.split('').map(c => c + c).join('');
                const num = parseInt(h, 16);
                const r = (num >> 16) & 255, g = (num >> 8) & 255, b = num & 255;
                return { r, g, b };
            },
            rgbToHex(rgb) {
                const to = n => Math.max(0, Math.min(255, Math.round(n))).toString(16).padStart(2, '0');
                return '#' + to(rgb.r) + to(rgb.g) + to(rgb.b);
            },
            mixRgb(a, b, ratio) {
                const t = Math.max(0, Math.min(1, ratio));
                return {
                    r: Math.round(a.r * (1 - t) + b.r * t),
                    g: Math.round(a.g * (1 - t) + b.g * t),
                    b: Math.round(a.b * (1 - t) + b.b * t),
                };
            },
            luminance(c) {
                const srgb = [c.r, c.g, c.b].map(v => {
                    v /= 255;
                    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
                });
                return 0.2126 * srgb[0] + 0.7152 * srgb[1] + 0.0722 * srgb[2];
            },
            getEventStyle(event) {
                const c = event && event.color ? this.hexToRgb(event.color) : null;
                if (!c) return {};
                let bgA = 0.18, borderA = 1.0, textA = 0.95;
                const lum = this.luminance(c);
                let borderRgb = c, textRgb = c;
                if (lum > 0.7) {
                    const dark = this.mixRgb(c, { r: 0, g: 0, b: 0 }, 0.5);
                    borderRgb = dark;
                    textRgb = this.mixRgb(c, { r: 0, g: 0, b: 0 }, 0.6);
                    bgA = 0.30;
                }
                const bg = `rgba(${c.r},${c.g},${c.b},${bgA})`;
                const border = `rgba(${borderRgb.r},${borderRgb.g},${borderRgb.b},${borderA})`;
                const text = `rgba(${textRgb.r},${textRgb.g},${textRgb.b},${textA})`;
                return { backgroundColor: bg, borderLeftColor: border, color: text };
            },
            formatDate(dateStr, type) {
                const d = new Date(dateStr);
                if (type === 'month') return d.toLocaleString('id-ID', { month: 'short' });
                if (type === 'day') return d.getDate();
                return d.toLocaleDateString('id-ID');
            },
            formatTime(dateStr) {
                return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            },
            getGoogleId(event) {
                const id = String(event && event.id || '');
                if (id.startsWith('google:')) return id.slice(7);
                return event.google_event_id || '';
            },
            formatYMD(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const da = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${da}`;
            }
        }
    }).mount('#app')
</script>
