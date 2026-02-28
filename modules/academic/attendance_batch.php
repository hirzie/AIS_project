<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<!-- QR & Scan Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<div id="app-batch" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="max-w-6xl mx-auto">
            
            <!-- Standard Header with Selectors -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 no-print">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-bold text-slate-800">Presensi Harian</h2>
                    <p class="text-slate-500 text-sm">Pilih kelas untuk mulai melakukan input presensi.</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                        <i class="fas fa-building text-slate-400 mr-2"></i>
                        <select v-model="currentUnit" class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 outline-none min-w-[140px]">
                            <option value="all">Semua Unit</option>
                            <option v-for="unit in availableUnits" :key="unit.id" :value="unit.unit_level || unit.code">{{ unit.name }} ({{ unit.prefix }})</option>
                        </select>
                    </div>
                    <!-- Class Selector -->
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                        <i class="fas fa-chalkboard text-slate-400 mr-2"></i>
                        <select v-model="filters.class_id" @change="fetchStudents" class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 outline-none min-w-[120px]">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="cls in filteredClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>

                    <!-- Month Selector -->
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                        <i class="fas fa-calendar-alt text-slate-400 mr-2"></i>
                        <select v-model="filters.month" @change="fetchStudents" class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 outline-none">
                            <option v-for="(name, idx) in monthNames" :key="idx" :value="idx + 1">{{ name }}</option>
                        </select>
                    </div>

                    <!-- Year Selector -->
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                        <input v-model="filters.year" type="number" @change="fetchStudents" class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 outline-none w-20 text-center">
                    </div>

                    <!-- Active Days -->
                    <div class="flex items-center bg-blue-50 border border-blue-100 rounded-xl px-3 py-1.5 shadow-sm">
                        <span class="text-[10px] font-bold text-blue-400 uppercase mr-2">Hari Aktif</span>
                        <input v-model.number="activeDays" type="number" @input="recalculateAll" class="bg-transparent border-none text-sm font-bold text-blue-700 focus:ring-0 outline-none w-12 text-center" placeholder="22">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center mb-6 no-print" v-if="filters.class_id">
                <div class="flex gap-2">
                    <button @click="triggerUpload" class="px-4 py-2 bg-emerald-500 text-white rounded-xl text-sm font-bold hover:bg-emerald-600 transition-all flex items-center gap-2">
                        <i class="fas fa-magic"></i> Scan Pintar
                    </button>
                    <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept="image/jpeg,image/png,image/webp">
                    
                    <button @click="printTemplate" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all flex items-center gap-2">
                        <i class="fas fa-print"></i> Cetak Form
                    </button>
                </div>
                
                <button @click="saveAttendance" :disabled="isSaving || !students.length" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all flex items-center gap-2 disabled:opacity-50">
                    <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-save"></i>
                    Simpan Perubahan
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="!filters.class_id" class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-20 text-center no-print">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                    <i class="fas fa-chalkboard text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-700 mb-2">Pilih Unit & Kelas</h3>
                <p class="text-slate-500 max-w-sm mx-auto">Silakan pilih unit sekolah di bagian atas dan tentukan kelas untuk mulai mengelola presensi siswa.</p>
            </div>

            <!-- Data Table -->
            <div v-if="filters.class_id" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
                
                <!-- Scanning Overlay -->
                <div v-if="isScanning" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex flex-col items-center justify-center">
                    <div class="w-64 text-center">
                        <div class="mb-4">
                            <i class="fas fa-circle-notch fa-spin text-4xl text-emerald-500"></i>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-bold text-emerald-600">{{ scanStatus }}</span>
                            <span class="text-xs font-bold text-emerald-600">{{ scanProgress }}%</span>
                        </div>
                        <div class="w-full bg-emerald-100 rounded-full h-2">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" :style="{width: scanProgress + '%'}"></div>
                        </div>
                    </div>
                </div>

                <!-- Print Header (Hidden on Screen) -->
                <div class="print-only hidden p-2 border-b-2 border-slate-800 mb-2 print-header">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <canvas id="qr-canvas" class="w-16 h-16 border p-1 border-slate-200 qr-code"></canvas>
                            <div>
                                <h1 class="text-xl font-bold uppercase">Form Presensi</h1>
                                <p class="text-xs">Kls: {{ getClassName() }} | Bln: {{ monthNames[filters.month-1] }} {{ filters.year }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg">HARI AKTIF: ____</p>
                            <p class="text-[8px] text-slate-400 mt-1">ID: {{ filters.class_id }}-{{ filters.month }}-{{ filters.year }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="px-4 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-12 border-r border-slate-100">No</th>
                                <th class="px-4 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-24 border-r border-slate-100">NIS</th>
                                <th class="px-4 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Siswa</th>
                                <th class="px-2 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50">Izin</th>
                                <th class="px-2 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50">Sakit</th>
                                <th class="px-2 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50">Alfa</th>
                                <th class="px-2 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-20 bg-slate-100/50">Cuti</th>
                                <th class="px-2 py-4 text-[10px] font-bold text-blue-600 uppercase tracking-widest text-center w-20 border-l border-slate-100">Hadir</th>
                                <th class="px-4 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest no-print">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="loading" class="no-print">
                                <td colspan="9" class="py-20 text-center">
                                    <i class="fas fa-spinner fa-spin text-3xl text-blue-200"></i>
                                </td>
                            </tr>
                            <tr v-for="(s, idx) in students" :key="s.id" class="hover:bg-slate-50 transition-colors group">
                                <td class="px-4 py-3 text-xs text-center text-slate-400 font-mono border-r border-slate-100">{{ idx + 1 }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 font-mono border-r border-slate-100">{{ s.nis }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-slate-700">{{ s.name }}</td>
                                
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.izin" @input="recalculate(s)" type="number" class="w-full text-center py-1.5 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-700 table-input no-print focus:border-blue-500" placeholder="0">
                                    <div class="print-only hidden w-full h-8 border-b border-black"></div>
                                </td>
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.sakit" @input="recalculate(s)" type="number" class="w-full text-center py-1.5 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-700 table-input no-print focus:border-blue-500" placeholder="0">
                                    <div class="print-only hidden w-full h-8 border-b border-black"></div>
                                </td>
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.alfa" @input="recalculate(s)" type="number" class="w-full text-center py-1.5 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-700 table-input no-print focus:border-blue-500" placeholder="0">
                                    <div class="print-only hidden w-full h-8 border-b border-black"></div>
                                </td>
                                <td class="px-2 py-2 bg-slate-50/30">
                                    <input v-model.number="s.cuti" @input="recalculate(s)" type="number" class="w-full text-center py-1.5 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-700 table-input no-print focus:border-blue-500" placeholder="0">
                                    <div class="print-only hidden w-full h-8 border-b border-black"></div>
                                </td>
                                
                                <td class="px-2 py-2 border-l border-slate-100">
                                    <div class="w-full text-center py-1.5 bg-blue-50 text-blue-700 font-bold rounded-lg text-sm no-print">
                                        {{ s.hadir }}
                                    </div>
                                    <div class="print-only hidden w-full h-8 border-b border-black"></div>
                                </td>
                                <td class="px-4 py-2 no-print">
                                    <input v-model="s.remarks" type="text" class="w-full px-3 py-1.5 border border-slate-100 rounded-lg text-xs text-slate-500 italic table-input focus:border-blue-500" placeholder="...">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Print Footer -->
                <div class="print-only hidden mt-8 pt-4 border-t border-black px-6">
                    <div class="flex justify-between text-xs">
                        <div class="text-center w-48">
                            <p class="mb-16 italic">Dicetak pada: <?= date('d/m/Y') ?></p>
                            <p class="font-bold border-b border-black inline-block px-4">Guru Kelas / Wali Kelas</p>
                        </div>
                        <div class="text-center w-48">
                            <p class="mb-16">Mengetahui,</p>
                            <p class="font-bold border-b border-black inline-block px-4">Kepala Sekolah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            const urlParams = new URLSearchParams(window.location.search);
            return {
                loading: false,
                isSaving: false,
                activeDays: 22,
                currentUnit: 'all',
                availableUnits: [],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                filters: {
                    class_id: urlParams.get('class_id') || '',
                    month: parseInt(urlParams.get('month')) || new Date().getMonth() + 1,
                    year: parseInt(urlParams.get('year')) || new Date().getFullYear()
                },
                students: [],
                classList: [],
                isScanning: false,
                scanProgress: 0,
                scanStatus: 'Menyiapkan...'
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
            filteredClasses() {
                const role = String(window.USER_ROLE || '').toUpperCase();
                const currentUp = String(this.currentUnit || '').toUpperCase();
                if (['SUPERADMIN','ADMIN'].includes(role)) {
                    if (currentUp === 'ALL') return this.classList;
                    return this.classList.filter(c => String(c.unit_code || '').toUpperCase() === currentUp);
                }
                const allowedCodes = this.getAllowedUnitCodes();
                const base = this.classList.filter(c => allowedCodes.has(String(c.unit_code || '').toUpperCase()));
                if (currentUp === 'ALL') return base;
                return base.filter(c => String(c.unit_code || '').toUpperCase() === currentUp);
            }
        },
        watch: {
            'filters.class_id'() { this.generateQR(); },
            'filters.month'() { this.generateQR(); },
            'filters.year'() { this.generateQR(); },
            currentUnit(newUnit) {
                const role = String(window.USER_ROLE || '').toUpperCase();
                const currentCls = this.classList.find(c => c.id == this.filters.class_id);
                if (!currentCls) return;
                if (newUnit !== 'all') {
                    if (String(currentCls.unit_code || '').toUpperCase() !== String(newUnit || '').toUpperCase()) {
                        this.filters.class_id = '';
                        this.students = [];
                    }
                } else {
                    if (!['SUPERADMIN','ADMIN'].includes(role)) {
                        const allowedCodes = this.getAllowedUnitCodes();
                        if (!allowedCodes.has(String(currentCls.unit_code || '').toUpperCase())) {
                            this.filters.class_id = '';
                            this.students = [];
                        }
                    }
                }
            }
        },
        async mounted() {
            await this.fetchUnits();
            await this.fetchClasses();
            this.generateQR();
            if (this.filters.class_id) {
                this.fetchStudents();
            }
        },
        methods: {
            getBaseUrl() {
                let baseUrl = window.BASE_URL || '/';
                if (baseUrl === '/' || !baseUrl) {
                    const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                    baseUrl = m ? `/${m[1]}/` : '/';
                }
                return baseUrl;
            },
            getAllowedUnitCodes() {
                const raw = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                const allowUp = raw.map(s => String(s).toUpperCase());
                const units = Array.isArray(this.availableUnits) ? this.availableUnits : [];
                const codes = units
                    .filter(u => allowUp.includes(String(u.code || '').toUpperCase()) || allowUp.includes(String(u.prefix || '').toUpperCase()))
                    .map(u => String(u.code || '').toUpperCase());
                return new Set(codes);
            },
            async fetchUnits() {
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/get_units.php');
                    const all = await res.json();
                    const allowed = Array.isArray(window.ALLOWED_UNITS) ? window.ALLOWED_UNITS : [];
                    const role = String(window.USER_ROLE || '').toUpperCase();
                    if (allowed.length > 0 && !['SUPERADMIN','ADMIN'].includes(role)) {
                        const allowUp = allowed.map(u => String(u).toUpperCase());
                        this.availableUnits = (all || []).filter(u => {
                            const codeUp = String(u.code || u.unit_level || '').toUpperCase();
                            const rcUp = String(u.prefix || u.receipt_code || '').toUpperCase();
                            return allowUp.includes(codeUp) || allowUp.includes(rcUp);
                        });
                    } else {
                        this.availableUnits = all || [];
                    }
                } catch (e) {
                    console.error('Gagal mengambil data unit');
                }
            },
            async fetchClasses() {
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/get_academic_data.php?action=get_classes');
                    const data = await res.json();
                    if (data.success) {
                        this.classList = data.data;
                        // Auto-set unit if class_id provided in URL
                        if (this.filters.class_id) {
                            const cls = this.classList.find(c => c.id == this.filters.class_id);
                            if (cls) this.currentUnit = cls.unit_code;
                        }
                    }
                } catch (e) {
                    console.error('Gagal mengambil data kelas');
                }
            },
            generateQR() {
                this.$nextTick(() => {
                    const canvas = document.getElementById('qr-canvas');
                    if (!canvas) return;
                    new QRious({
                        element: canvas,
                        value: JSON.stringify({
                            c: this.filters.class_id,
                            m: this.filters.month,
                            y: this.filters.year
                        }),
                        size: 200,
                        level: 'M'
                    });
                });
            },
            triggerUpload() {
                this.$refs.fileInput.click();
            },
            async handleFileUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.isScanning = true;
                this.scanStatus = 'Membaca QR...';
                try {
                    const img = await this.loadImage(file);
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);
                    if (code) {
                        const qrData = JSON.parse(code.data);
                        if (qrData.c && qrData.m && qrData.y) {
                            this.filters.class_id = qrData.c;
                            this.filters.month = qrData.m;
                            this.filters.year = qrData.y;
                            const cls = this.classList.find(c => c.id == qrData.c);
                            if (cls) this.currentUnit = cls.unit_code;
                            await this.fetchStudents();
                        }
                    } else {
                        alert('QR Code tidak terdeteksi.');
                    }
                } catch (e) {
                    alert('Gagal membaca dokumen');
                } finally {
                    this.isScanning = false;
                    event.target.value = '';
                }
            },
            loadImage(file) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => resolve(img);
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            },
            getClassName() {
                const cls = this.classList.find(c => c.id == this.filters.class_id);
                return cls ? cls.name : '...';
            },
            async fetchStudents() {
                if (!this.filters.class_id) return;
                this.loading = true;
                try {
                    const res = await fetch(this.getBaseUrl() + `api/attendance.php?action=get_students_for_attendance&class_id=${this.filters.class_id}&month=${this.filters.month}&year=${this.filters.year}`);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (data && data.success) {
                        this.students = data.data.map(s => ({
                            ...s,
                            izin: Number(s.izin) || 0,
                            sakit: Number(s.sakit) || 0,
                            alfa: Number(s.alfa) || 0,
                            cuti: Number(s.cuti) || 0,
                            hadir: (typeof s.hadir === 'number' ? s.hadir : Number(s.hadir)) || this.activeDays,
                            remarks: s.remarks || ''
                        }));
                        if (this.students.length > 0 && this.students[0].active_days) {
                            this.activeDays = this.students[0].active_days;
                        }
                        this.recalculateAll();
                    } else {
                        alert((data && data.message) ? data.message : 'Gagal mengambil data siswa');
                    }
                } catch (e) {
                    alert('Gagal mengambil data siswa');
                } finally {
                    this.loading = false;
                }
            },
            recalculate(student) {
                const active = Number(this.activeDays) || 0;
                const izin = Number(student.izin) || 0;
                const sakit = Number(student.sakit) || 0;
                const alfa = Number(student.alfa) || 0;
                const cuti = Number(student.cuti) || 0;
                const exceptions = izin + sakit + alfa + cuti;
                student.hadir = Math.max(0, active - exceptions);
            },
            recalculateAll() {
                this.students.forEach(s => this.recalculate(s));
            },
            async saveAttendance() {
                this.isSaving = true;
                try {
                    const res = await fetch(this.getBaseUrl() + 'api/attendance.php?action=save_attendance_batch', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            class_id: this.filters.class_id,
                            month: this.filters.month,
                            year: this.filters.year,
                            active_days: this.activeDays,
                            records: this.students
                        })
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (data && data.success) {
                        alert('Presensi berhasil disimpan!');
                    } else {
                        alert((data && data.message) ? data.message : 'Gagal menyimpan presensi');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem');
                } finally {
                    this.isSaving = false;
                }
            },
            printTemplate() {
                if (!this.filters.class_id) {
                    alert('Pilih kelas terlebih dahulu');
                    return;
                }
                window.print();
            }
        }
    }).mount('#app-batch')
</script>

<?php require_once '../../includes/footer.php'; ?>
