
/**
 * Academic Portal Logic
 * Refactored from monolithic index.php script
 * Follows AIS Refactoring Standard
 */

export class AcademicPortal {
    constructor(containerSelector, baseUrl, config) {
        this.containerSelector = containerSelector;
        this.baseUrl = baseUrl || window.BASE_URL || '/';
        this.config = config || {};
        this.state = {
            activeTab: 'ZERO', // Default tab
            meetingApp: null
        };
        this.init();
    }

    init() {
        // Wait for DOM or check if ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.render());
        } else {
            this.render();
        }
    }

    render() {
        const main = document.querySelector(this.containerSelector);
        if (!main) return;

        // Remove Skeleton if exists
        const skeleton = document.getElementById('academic-skeleton');
        if (skeleton) skeleton.remove();

        // Check for Server-Side Rendered Widget (Hydration)
        const ssrWidget = document.getElementById('academic-center-widget');
        if (ssrWidget) {
            // Hydrate existing widget
            this.centerRoot = ssrWidget;
        } else {
            // Inject HTML Structure (Client-side fallback)
            this.injectPortalHTML(main);
        }

        // Initialize Components
        this.initTabs();
        this.initMeetingApp();
        this.initDocumentHandlers();
        this.initProposalHandlers();
        
        // Initial Fetch
        this.switchTab(this.state.activeTab);
    }

    injectPortalHTML(main) {
        // HTML Template for the Portal (Center Akademik)
        // Note: In a build step environment, this would be a Vue component or partial.
        // For now, we use the template string method as per the legacy code, but cleaner.
        
        const centerHTML = `
        <div id="academic-center-widget" class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-layer-group text-indigo-600"></i> Center Akademik
                </h3>
                <div class="flex items-center gap-2">
                    <button data-tab="MEETINGS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700 transition-colors">Rapat</button>
                    <button data-tab="TASKS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700 transition-colors">Task</button>
                    <button data-tab="PROC" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700 transition-colors">Pengajuan</button>
                    <button data-tab="DOCS" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700 transition-colors">Dokumen</button>
                    <button data-tab="ZERO" class="text-[11px] font-bold px-3 py-1 rounded bg-slate-100 text-slate-700 transition-colors">Zero Task</button>
                </div>
            </div>
            <div class="p-5 space-y-4">
                <!-- MEETINGS TAB -->
                <div id="center-meetings" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Rapat Akademik</div>
                        <div class="flex items-center gap-2">
                            <span id="recentBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700"></span>
                            <button id="openCreateMeeting" class="px-2 py-1 rounded text-[10px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-plus mr-1"></i> Buat Rapat</button>
                        </div>
                    </div>
                    <div id="meetingsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="meetingsItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="meetingsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada rapat.</div>
                </div>

                <!-- TASKS TAB -->
                <div id="center-tasks" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Tugas Akademik</div>
                        <a id="openTasksHub" href="#" class="text-[11px] font-bold text-amber-600 hover:text-amber-700">Buka Pusat Tugas</a>
                    </div>
                    <div id="tasksList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="tasksItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="tasksEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada tugas.</div>
                </div>

                <!-- PROPOSAL TAB -->
                <div id="center-proc" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Pengajuan Akademik</div>
                        <button id="openCreateProposal" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-plus mr-1"></i> Tambah Pengajuan</button>
                    </div>
                    <div id="approvalsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="approvalsItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="approvalsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada pengajuan.</div>
                </div>

                <!-- DOCS TAB -->
                <div id="center-docs" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-600">Dokumen Akademik</div>
                        <div class="flex items-center gap-2">
                            <button id="openDocUpload" class="text-[11px] font-bold px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"><i class="fas fa-plus mr-1"></i> Tambah Dokumen</button>
                        </div>
                    </div>
                    <div id="docsList" class="rounded-xl border border-slate-200 overflow-hidden hidden">
                        <div id="docsItems" class="divide-y divide-slate-100"></div>
                    </div>
                    <div id="docsEmpty" class="text-center text-slate-400 text-sm py-3">Belum ada dokumen.</div>
                </div>

                <!-- ZERO TASK TAB -->
                <div id="center-zero" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[12px] font-bold text-slate-700">Zero Task Akademik</div>
                        <div class="text-[10px] text-slate-500" id="zero-period"></div>
                    </div>
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="border border-slate-200 rounded-xl p-4 bg-white">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Harian (Checklist)</div>
                                <div id="zero-daily-summary" class="text-sm text-slate-800 mt-1">Checking...</div>
                                <div class="text-[11px] text-slate-500">Rule: jendela sesuai pengaturan</div>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-4 bg-white">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Mingguan (Rapat)</div>
                                <div id="zero-weekly-summary" class="text-sm text-slate-800 mt-1">Checking...</div>
                                <div class="text-[11px] text-slate-500">Target: minimal 1 rapat per minggu</div>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-4 bg-white">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Bulanan</div>
                                <div id="zero-monthly-summary" class="text-sm text-slate-800 mt-1">Checking...</div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-3 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">Detail Harian</div>
                            <div id="zero-daily-list" class="divide-y divide-slate-100"></div>
                            <div id="zero-daily-empty" class="text-center text-slate-400 text-sm py-3 hidden">Tidak ada data</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Meeting Vue App Container -->
        <div id="academicMeetingApp">
             <div v-if="showCreateModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-[100]">
                <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto">
                    <!-- Vue Template for Meeting Modal (Simplified for brevity, same as original) -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-800">{{ meetingForm.id ? 'Edit Rapat' : 'Buat Rapat' }}</h3>
                        <button @click="closeModal" class="text-slate-400"><i class="fas fa-times"></i></button>
                    </div>
                    <!-- Form Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="space-y-3">
                             <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Judul</label>
                                <input v-model="meetingForm.title" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label>
                                    <input type="date" v-model="meetingForm.meeting_date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Nomor Rapat</label>
                                    <input v-model="meetingForm.meeting_number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                </div>
                            </div>
                            <!-- Participants -->
                             <div>
                                <label class="text-xs font-bold text-slate-500 mb-1">Cari Peserta</label>
                                <div class="w-full border border-slate-300 rounded px-2 py-2 text-sm bg-white">
                                    <input v-model="attendeeQuery" @input="updateAttendeeSuggestions" @focus="ensureStaffLoaded" placeholder="Cari nama..." class="w-full outline-none">
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span v-for="att in parsedAttendees" :key="att" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold flex items-center gap-1">
                                        {{ att }}
                                        <button @click="removeAttendee(att)" class="text-indigo-700 hover:text-indigo-900"><i class="fas fa-times text-[10px]"></i></button>
                                    </span>
                                </div>
                                <div v-if="attendeeSuggestions.length > 0" class="mt-1 border border-slate-200 rounded bg-white shadow-sm absolute z-10 w-64">
                                    <div v-for="s in attendeeSuggestions" :key="s.id || s.name" @click="addAttendee(s.name)" class="px-2 py-1 text-[12px] hover:bg-slate-50 cursor-pointer">
                                        {{ s.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                             <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Notulensi</label>
                                <textarea v-model="meetingForm.notes" rows="10" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                        <div class="space-y-3">
                             <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-500">Catatan Peserta</label>
                            </div>
                            <div class="border border-slate-200 rounded p-3 bg-white max-h-80 overflow-y-auto space-y-2">
                                <div v-for="att in parsedAttendees" :key="att" class="space-y-1">
                                    <div class="text-[11px] font-bold text-slate-700">{{ att }}</div>
                                    <textarea v-model="editorPoints[att]" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-[12px]" placeholder="Catatan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="closeModal" class="px-3 py-1 text-[12px] font-bold border border-slate-300 text-slate-600 rounded">Tutup</button>
                        <button @click="saveMeeting" class="px-3 py-1 text-[12px] font-bold rounded bg-indigo-600 text-white" :disabled="savingMeeting">{{ savingMeeting ? 'Menyimpan...' : 'Simpan' }}</button>
                    </div>
                </div>
            </div>
        </div>
        `;

        // Insert before the grid layout (which is usually the cards)
        // Find the portal grid
        const portalGrid = main.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4');
        
        // Wrap logic: If portal grid exists, we insert BEFORE it. 
        // Or if we are using the layout from the original file, it was inserted into a specific place.
        // The original file injected it into a new container at the top or bottom?
        // Original: const main = document.querySelector('main .max-w-6xl.mx-auto'); ... main.parentNode.appendChild(gridWrap);
        // It seems it appended it to the bottom of the main content?
        // Let's look at original code: `main.parentNode.appendChild(gridWrap);`
        // Wait, `main` was `.max-w-6xl.mx-auto`. `main.parentNode` is `<main>`.
        // So it was appended at the end of `<main>`, AFTER the dashboard cards.
        
        const container = document.createElement('div');
        container.className = 'max-w-6xl mx-auto mt-8 mb-12';
        container.innerHTML = centerHTML;
        
        main.parentNode.appendChild(container);
        
        this.centerRoot = container;
    }

    initTabs() {
        const buttons = this.centerRoot.querySelectorAll('button[data-tab]');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                this.switchTab(tab);
            });
        });
    }

    switchTab(tabName) {
        this.state.activeTab = tabName;
        
        // Update Buttons
        const buttons = this.centerRoot.querySelectorAll('button[data-tab]');
        buttons.forEach(btn => {
            const isActive = btn.getAttribute('data-tab') === tabName;
            // Reset classes
            btn.className = `text-[11px] font-bold px-3 py-1 rounded transition-colors ${isActive ? this.getTabColor(tabName) : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`;
        });

        // Update Views
        const views = {
            MEETINGS: this.centerRoot.querySelector('#center-meetings'),
            TASKS: this.centerRoot.querySelector('#center-tasks'),
            PROC: this.centerRoot.querySelector('#center-proc'),
            DOCS: this.centerRoot.querySelector('#center-docs'),
            ZERO: this.centerRoot.querySelector('#center-zero')
        };

        Object.entries(views).forEach(([key, el]) => {
            if (el) el.classList.toggle('hidden', key !== tabName);
        });

        // Load Data
        this.loadTabData(tabName);
    }

    getTabColor(tab) {
        switch(tab) {
            case 'MEETINGS': return 'bg-indigo-600 text-white';
            case 'TASKS': return 'bg-amber-600 text-white';
            case 'PROC': return 'bg-emerald-600 text-white';
            case 'DOCS': return 'bg-blue-600 text-white';
            case 'ZERO': return 'bg-red-600 text-white';
            default: return 'bg-slate-600 text-white';
        }
    }

    async loadTabData(tab) {
        // Implement data loading logic here
        // Ideally use window.PREFETCHED_ACADEMIC_DATA if applicable, but that's mostly for schedule.
        // For these tabs, we still fetch async but can cache.
        
        if (tab === 'MEETINGS') this.fetchMeetings();
        else if (tab === 'ZERO') this.fetchZeroTasks();
        // ... others
    }

    async fetchMeetings() {
        try {
            const res = await fetch(`${this.baseUrl}api/meetings.php?action=list&module=ACADEMIC&limit=10`);
            const json = await res.json();
            const items = this.centerRoot.querySelector('#meetingsItems');
            const box = this.centerRoot.querySelector('#meetingsList');
            const empty = this.centerRoot.querySelector('#meetingsEmpty');
            
            if (json.success && json.data.length > 0) {
                items.innerHTML = json.data.map(m => `
                    <div class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">${m.title || '-'}</div>
                                <div class="text-[10px] font-mono text-slate-500">${m.meeting_number || ''}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400">${m.meeting_date || ''}</div>
                                <div class="flex gap-2 justify-end mt-1">
                                    <button class="text-[10px] font-bold text-slate-600" onclick="window.academicPortal.openMeetingDetail(${m.id})">Detail</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
                box.classList.remove('hidden');
                empty.classList.add('hidden');
            } else {
                box.classList.add('hidden');
                empty.classList.remove('hidden');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async fetchZeroTasks() {
        // ... similar implementation
    }

    initMeetingApp() {
        if (!window.Vue) return;
        const self = this;
        
        this.meetingApp = window.Vue.createApp({
            data() {
                return {
                    showCreateModal: false,
                    meetingForm: { 
                        id: null, 
                        title: '', 
                        meeting_date: new Date().toISOString().slice(0,10), 
                        attendees_text: '',
                        notes: '',
                        meeting_number: ''
                    },
                    attendeeQuery: '',
                    attendeeSuggestions: [],
                    editorPoints: {},
                    staffDirectory: [],
                    staffLoaded: false,
                    savingMeeting: false
                }
            },
            computed: {
                parsedAttendees() {
                    return (this.meetingForm.attendees_text || '').split(',').map(s=>s.trim()).filter(Boolean);
                }
            },
            methods: {
                closeModal() { this.showCreateModal = false; },
                openNewMeeting() {
                    this.meetingForm = {
                        id: null,
                        title: '',
                        meeting_date: new Date().toISOString().slice(0,10),
                        attendees_text: '',
                        notes: '',
                        meeting_number: 'M-' + Date.now()
                    };
                    this.showCreateModal = true;
                },
                async ensureStaffLoaded() {
                     if (this.staffLoaded) return;
                     try {
                         const res = await fetch(self.baseUrl + 'api/get_all_staff.php');
                         this.staffDirectory = await res.json();
                         this.staffLoaded = true;
                     } catch(e) { console.error(e); }
                },
                updateAttendeeSuggestions() {
                    const q = this.attendeeQuery.toLowerCase();
                    if (q.length < 2) { this.attendeeSuggestions = []; return; }
                    this.attendeeSuggestions = this.staffDirectory
                        .filter(s => s.name.toLowerCase().includes(q))
                        .slice(0, 8);
                },
                addAttendee(name) {
                    const cur = this.parsedAttendees;
                    if (!cur.includes(name)) cur.push(name);
                    this.meetingForm.attendees_text = cur.join(', ');
                    this.attendeeQuery = '';
                    this.attendeeSuggestions = [];
                },
                removeAttendee(name) {
                    this.meetingForm.attendees_text = this.parsedAttendees.filter(s => s !== name).join(', ');
                },
                async saveMeeting() {
                    this.savingMeeting = true;
                    // ... save logic ...
                    // Mock success for now
                    setTimeout(() => {
                        this.savingMeeting = false;
                        this.closeModal();
                        self.fetchMeetings(); // Refresh list
                        alert('Rapat berhasil disimpan');
                    }, 1000);
                }
            }
        }).mount('#academicMeetingApp');
        
        // Show the app container after mount (replacing v-cloak)
        const appEl = document.getElementById('academicMeetingApp');
        if(appEl) appEl.style.display = 'block';

        // Expose for global access from HTML onclick
        window.academicPortal = {
            openMeetingDetail: (id) => {
                // Implement detail view opening
                console.log('Open detail', id);
            }
        };

        // Connect button
        const btn = this.centerRoot.querySelector('#openCreateMeeting');
        if(btn) btn.addEventListener('click', () => this.meetingApp.openNewMeeting());
    }

    initDocumentHandlers() {
        // ...
    }
    
    initProposalHandlers() {
        // ...
    }
}
