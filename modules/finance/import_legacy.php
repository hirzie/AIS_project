<?php
require_once '../../config/database.php';
require_once '../../includes/header_finance.php';
?>

<div id="app" class="flex flex-col h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm flex-none">
        <div class="flex items-center gap-3">
            <a href="<?php echo $baseUrl; ?>modules/finance/dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-file-import text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Import Tagihan Lama</h1>
                <span class="text-xs text-slate-500 font-medium">Wizard Import Data Piutang Siswa</span>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 px-4 py-2 rounded-full" :class="step === 1 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-400'">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" :class="step === 1 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500'">1</div>
                <span class="text-sm font-bold hidden md:inline">Konfigurasi</span>
            </div>
            <div class="w-8 h-px bg-slate-300"></div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-full" :class="step === 2 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-400'">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" :class="step === 2 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500'">2</div>
                <span class="text-sm font-bold hidden md:inline">Upload & Preview</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden p-6 flex gap-6">
        
        <div v-if="step === 1" class="w-full max-w-4xl mx-auto flex flex-col gap-6">
            <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-cogs text-indigo-500"></i>
                    Konfigurasi Template
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">1. Pilih Unit Sekolah</label>
                        <select v-model="config.unitId" @change="fetchClasses" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">-- Pilih Unit --</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">2. Pilih Kelas</label>
                        <select v-model="config.classId" :disabled="!config.unitId" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="!config.unitId" class="text-xs text-slate-400 mt-1">Pilih unit terlebih dahulu</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">3. Tahun Ajar (Format: YYYY/YYYY)</label>
                        <input type="text" v-model="config.year" placeholder="Contoh: 2023/2024" class="w-full border border-slate-300 rounded-lg px-4 py-3 font-mono font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Masukkan tahun ajar lampau untuk data migrasi ini.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">4. Tipe Tagihan</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" v-model="config.type" value="SPP" class="peer sr-only">
                                <div class="p-4 border-2 border-slate-200 rounded-lg hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-center h-full">
                                    <i class="fas fa-calendar-alt text-2xl text-slate-400 peer-checked:text-indigo-600 mb-2 block"></i>
                                    <span class="font-bold text-slate-700 block">SPP (Bulanan)</span>
                                    <span class="text-xs text-slate-500">Input nominal per bulan & jumlah bulan lunas</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" v-model="config.type" value="ANNUAL" class="peer sr-only">
                                <div class="p-4 border-2 border-slate-200 rounded-lg hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-center h-full">
                                    <i class="fas fa-money-bill-wave text-2xl text-slate-400 peer-checked:text-indigo-600 mb-2 block"></i>
                                    <span class="font-bold text-slate-700 block">Tahunan / 1x</span>
                                    <span class="text-xs text-slate-500">Input total tagihan & total terbayar</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-200 flex justify-end">
                    <button @click="generateAndDownload" :disabled="!isConfigValid" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-200 flex items-center gap-2">
                        <i v-if="isGenerating" class="fas fa-spinner fa-spin"></i>
                        <span v-else><i class="fas fa-file-excel mr-2"></i> Generate & Download Template</span>
                    </button>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-4 items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                <div class="text-sm text-blue-700">
                    <p class="font-bold mb-1">Panduan:</p>
                    <ul class="list-disc ml-4 space-y-1">
                        <li>Pilih kelas yang ingin di-import datanya.</li>
                        <li>Sistem akan membuat file Excel berisi daftar siswa di kelas tersebut.</li>
                        <li>Isi data keuangan di Excel, lalu upload di langkah berikutnya.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div v-if="step === 2" class="flex-1 flex gap-6 h-full overflow-hidden">
            <div class="w-1/3 flex flex-col gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col">
                    <h3 class="font-bold text-slate-700 mb-4">Upload File Template</h3>
                    
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:bg-slate-50 transition-colors relative flex-1 flex flex-col justify-center items-center">
                        <input type="file" ref="fileInput" @change="handleFileUpload" accept=".xlsx, .xls" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div v-if="!file">
                            <i class="fas fa-cloud-upload-alt text-5xl text-slate-300 mb-4"></i>
                            <p class="text-lg font-bold text-slate-500">Upload File Excel</p>
                            <p class="text-sm text-slate-400 mt-2">Pastikan format sesuai template yang di-download</p>
                        </div>
                        <div v-else>
                            <i class="fas fa-file-excel text-5xl text-green-500 mb-4"></i>
                            <p class="text-lg font-bold text-slate-700">{{ file.name }}</p>
                            <p class="text-sm text-slate-500 mt-1">{{ (file.size / 1024).toFixed(1) }} KB</p>
                            <button @click.stop="resetUpload" class="mt-4 text-red-500 text-sm font-bold hover:underline">Ganti File</button>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button @click="step = 1" class="px-4 py-3 border border-slate-300 rounded-lg text-slate-600 font-bold hover:bg-slate-50">
                            Kembali
                        </button>
                        <button @click="processImport" :disabled="previewData.length === 0 || isProcessing" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-200">
                            <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
                            {{ isProcessing ? 'Memproses Import...' : 'Import Data Sekarang' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Preview Data ({{ previewData.length }} Baris)</h3>
                    <span class="text-xs text-slate-500">Cek kembali data sebelum import</span>
                </div>
                
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-white text-slate-500 font-bold uppercase text-xs sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 bg-slate-50">No</th>
                                <th class="px-4 py-3 bg-slate-50">NIS</th>
                                <th class="px-4 py-3 bg-slate-50">Nama</th>
                                <th class="px-4 py-3 bg-slate-50">Tahun</th>
                                <th class="px-4 py-3 bg-slate-50">Tagihan</th>
                                <th class="px-4 py-3 bg-slate-50 text-right">Total Tagihan</th>
                                <th class="px-4 py-3 bg-slate-50 text-right">Total Terbayar</th>
                                <th class="px-4 py-3 bg-slate-50 text-right">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, idx) in previewData" :key="idx" class="hover:bg-slate-50">
                                <td class="px-4 py-2 text-slate-400">{{ idx + 1 }}</td>
                                <td class="px-4 py-2 font-mono font-bold text-slate-600">{{ row.nis }}</td>
                                <td class="px-4 py-2">{{ row.nama_siswa }}</td>
                                <td class="px-4 py-2">{{ row.tahun_ajar }}</td>
                                <td class="px-4 py-2">{{ row.jenis_tagihan }}</td>
                                <td class="px-4 py-2 text-right">{{ formatNumber(row.tagihan) }}</td>
                                <td class="px-4 py-2 text-right text-green-600">{{ formatNumber(row.terbayar) }}</td>
                                <td class="px-4 py-2 text-right font-bold" :class="(row.tagihan - row.terbayar) > 0 ? 'text-red-500' : 'text-slate-400'">
                                    {{ formatNumber(row.tagihan - row.terbayar) }}
                                </td>
                            </tr>
                            <tr v-if="previewData.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 italic">
                                    Belum ada data. Upload file Excel terlebih dahulu.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                step: 1,
                units: [],
                classes: [],
                config: {
                    unitId: '',
                    classId: '',
                    year: '2023/2024',
                    type: 'SPP'
                },
                isGenerating: false,
                file: null,
                previewData: [],
                isProcessing: false
            }
        },
        computed: {
            isConfigValid() {
                const yearValid = /^\d{4}\/\d{4}$/.test(this.config.year);
                return this.config.unitId && this.config.classId && yearValid && this.config.type;
            }
        },
        methods: {
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num || 0);
            },
            async fetchUnits() {
                try {
                    const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings');
                    const data = await res.json();
                    if (data.success) {
                        this.units = data.data.units;
                    }
                } catch (e) {}
            },
            async fetchClasses() {
                if (!this.config.unitId) {
                    this.classes = [];
                    return;
                }
                const unit = this.units.find(u => u.id === this.config.unitId);
                if (!unit) return;

                try {
                    const res = await fetch(window.BASE_URL + `api/get_academic_data.php?unit=${unit.code}`);
                    const data = await res.json();
                    if (data.classes) {
                        this.classes = data.classes;
                    }
                } catch (e) {
                    console.error("Error fetching classes", e);
                }
            },
            async generateAndDownload() {
                this.isGenerating = true;
                try {
                    const res = await fetch(window.BASE_URL + `api/get_class_members.php?class_id=${this.config.classId}`);
                    const students = await res.json();
                    
                    if (!students || students.length === 0) {
                        alert("Tidak ada siswa di kelas ini.");
                        this.isGenerating = false;
                        return;
                    }

                    const rows = students.map(s => {
                        const base = {
                            'NIS': s.identity_number,
                            'Nama Siswa': s.name,
                            'Tahun Ajar': this.config.year,
                            'Jenis Tagihan': this.config.type === 'SPP' ? 'SPP' : 'Uang Tahunan'
                        };

                        if (this.config.type === 'SPP') {
                            base['Nominal SPP (Per Bulan)'] = 0;
                            base['Jumlah Bulan Terbayar (0-12)'] = 0;
                        } else {
                            base['Nominal Tagihan'] = 0;
                            base['Nominal Terbayar'] = 0;
                        }
                        return base;
                    });

                    const ws = XLSX.utils.json_to_sheet(rows);
                    const wscols = [
                        {wch: 15},
                        {wch: 30},
                        {wch: 15},
                        {wch: 20},
                        {wch: 25},
                        {wch: 25}
                    ];
                    ws['!cols'] = wscols;

                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Template Import");
                    
                    const className = this.classes.find(c => c.id === this.config.classId)?.name || 'Kelas';
                    const filename = `Template_Import_${this.config.type}_${className}_${this.config.year.replace('/','-')}.xlsx`;
                    XLSX.writeFile(wb, filename);

                    setTimeout(() => {
                        this.step = 2;
                        this.resetUpload();
                    }, 1000);

                } catch (e) {
                    alert("Gagal generate template: " + e.message);
                } finally {
                    this.isGenerating = false;
                }
            },
            resetUpload() {
                this.file = null;
                this.previewData = [];
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            },
            handleFileUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.file = file;
                this.parseExcel(file);
            },
            parseExcel(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });
                        
                        this.previewData = jsonData.map(row => {
                            const getVal = (keys) => {
                                const k = Object.keys(row).find(key => keys.some(search => key.toLowerCase().includes(search.toLowerCase())));
                                return k ? row[k] : '';
                            };

                            const nis = getVal(['nis', 'induk']);
                            const nama = getVal(['nama']);
                            const tahun = getVal(['tahun']);
                            const jenis = getVal(['jenis', 'tipe']);

                            let tagihan = 0;
                            let terbayar = 0;

                            const nominalBulan = Number(getVal(['nominal spp', 'per bulan']) || 0);
                            const bulanBayar = Number(getVal(['jumlah bulan', 'bulan terbayar']) || 0);

                            if (nominalBulan > 0 || Object.keys(row).some(k => k.toLowerCase().includes('per bulan'))) {
                                tagihan = nominalBulan * 12;
                                terbayar = nominalBulan * bulanBayar;
                            } else {
                                tagihan = Number(getVal(['nominal tagihan', 'tagihan']) || 0);
                                terbayar = Number(getVal(['nominal terbayar', 'terbayar']) || 0);
                            }

                            return {
                                nis: nis,
                                nama_siswa: nama,
                                tahun_ajar: tahun,
                                jenis_tagihan: jenis,
                                tagihan: tagihan,
                                terbayar: terbayar
                            };
                        }).filter(r => r.nis || r.nama_siswa);

                    } catch (err) {
                        alert("Gagal membaca file Excel. Pastikan format valid.");
                        console.error(err);
                    }
                };
                reader.readAsArrayBuffer(file);
            },
            async processImport() {
                if (this.previewData.length === 0) return alert("Data kosong!");
                if (!confirm(`Yakin ingin mengimport ${this.previewData.length} data?`)) return;

                this.isProcessing = true;
                
                try {
                    const payload = {
                        unit_id: this.config.unitId,
                        data: this.previewData
                    };

                    const res = await fetch(window.BASE_URL + 'api/finance_import_bills.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    
                    const result = await res.json();

                    if (result.success) {
                        alert(result.message);
                        this.resetUpload();
                    } else {
                        alert("Gagal: " + result.message);
                        if (result.data && result.data.errors) {
                            console.log(result.data.errors);
                            alert("Cek console untuk detail error.");
                        }
                    }

                } catch (e) {
                    alert("Terjadi kesalahan sistem: " + e.message);
                } finally {
                    this.isProcessing = false;
                }
            }
        },
        mounted() {
            this.fetchUnits();
        }
    }).mount('#app')
</script>
</body>
</html>
