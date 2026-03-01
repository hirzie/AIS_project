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
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Import Saldo Tabungan</h1>
                <span class="text-xs text-slate-500 font-medium">Wizard Impor Saldo Awal Tabungan Siswa</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden p-6 flex gap-6">
        <div v-if="step === 1" class="w-full max-w-4xl mx-auto flex flex-col gap-6">
            <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-cogs text-blue-500"></i>
                    Konfigurasi Template
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">1. Pilih Unit Sekolah</label>
                        <select v-model="config.unitId" @change="fetchClasses" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">-- Pilih Unit --</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">2. Pilih Kelas</label>
                        <select v-model="config.classId" :disabled="!config.unitId" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="!config.unitId" class="text-xs text-slate-400 mt-1">Pilih unit terlebih dahulu</p>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-200 flex justify-end">
                    <button @click="generateAndDownload" :disabled="!isConfigValid" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-200 flex items-center gap-2">
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
                        <li>Pilih kelas yang ingin di-import saldonya.</li>
                        <li>Sistem membuat file Excel berisi daftar siswa di kelas tersebut.</li>
                        <li>Isi Saldo Awal dan Catatan lalu upload pada langkah berikutnya.</li>
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
                        <button @click="processImport" :disabled="previewData.length === 0 || isProcessing" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-200">
                            <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
                            {{ isProcessing ? 'Memproses Import...' : 'Import Saldo Sekarang' }}
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
                                <th class="px-4 py-3 bg-slate-50 text-right">Saldo Awal</th>
                                <th class="px-4 py-3 bg-slate-50">Catatan</th>
                                <th class="px-4 py-3 bg-slate-50">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, idx) in previewData" :key="idx" class="hover:bg-slate-50">
                                <td class="px-4 py-2 text-slate-400">{{ idx + 1 }}</td>
                                <td class="px-4 py-2 font-mono font-bold text-slate-600">{{ row.nis }}</td>
                                <td class="px-4 py-2">{{ row.nama_siswa }}</td>
                                <td class="px-4 py-2 text-right text-blue-700">{{ formatNumber(row.saldo_awal) }}</td>
                                <td class="px-4 py-2">{{ row.catatan }}</td>
                                <td class="px-4 py-2">
                                    <span v-if="row._status" :class="row._status.startsWith('OK') ? 'text-green-600' : 'text-red-500'">{{ row._status }}</span>
                                </td>
                            </tr>
                            <tr v-if="previewData.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">
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
            classMembers: [],
            config: {
                unitId: '',
                classId: ''
            },
            isGenerating: false,
            file: null,
            previewData: [],
            isProcessing: false
        }
    },
    computed: {
        isConfigValid() {
            return this.config.unitId && this.config.classId
        }
    },
    methods: {
        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num || 0)
        },
        async fetchUnits() {
            try {
                const res = await fetch(window.BASE_URL + 'api/finance.php?action=get_settings')
                const data = await res.json()
                if (data.success) {
                    this.units = data.data.units
                }
            } catch (e) {}
        },
        async fetchClasses() {
            if (!this.config.unitId) {
                this.classes = []
                return
            }
            const unit = this.units.find(u => u.id === this.config.unitId)
            if (!unit) return
            try {
                const res = await fetch(window.BASE_URL + `api/get_academic_data.php?unit=${unit.code}`)
                const data = await res.json()
                if (data.classes) {
                    this.classes = data.classes
                }
            } catch (e) {}
        },
        async generateAndDownload() {
            this.isGenerating = true
            try {
                const res = await fetch(window.BASE_URL + `api/get_class_members.php?class_id=${this.config.classId}`)
                const students = await res.json()
                if (!students || students.length === 0) {
                    alert("Tidak ada siswa di kelas ini.")
                    this.isGenerating = false
                    return
                }
                this.classMembers = students
                const rows = students.map(s => {
                    return {
                        'NIS': s.identity_number,
                        'Nama Siswa': s.name,
                        'Saldo Tabungan': 0, // Disesuaikan dengan nama kolom di Excel user
                        'Catatan': 'Saldo Awal'
                    }
                })
                const ws = XLSX.utils.json_to_sheet(rows)
                ws['!cols'] = [{wch:15},{wch:30},{wch:20},{wch:25}]
                const wb = XLSX.utils.book_new()
                XLSX.utils.book_append_sheet(wb, ws, "Import Tabungan")
                const className = this.classes.find(c => c.id === this.config.classId)?.name || 'Kelas'
                const filename = `Import_Saldo_Tabungan_${className}.xlsx`
                XLSX.writeFile(wb, filename)
                setTimeout(() => {
                    this.step = 2
                    this.resetUpload()
                }, 600)
            } catch (e) {
                alert("Gagal generate template: " + e.message)
            } finally {
                this.isGenerating = false
            }
        },
        resetUpload() {
            this.file = null
            this.previewData = []
            if (this.$refs.fileInput) this.$refs.fileInput.value = ''
        },
        handleFileUpload(event) {
            const file = event.target.files[0]
            if (!file) return
            this.file = file
            this.parseExcel(file)
        },
        parseExcel(file) {
            const reader = new FileReader()
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result)
                    const workbook = XLSX.read(data, { type: 'array' })
                    const firstSheetName = workbook.SheetNames[0]
                    const worksheet = workbook.Sheets[firstSheetName]
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" })
                    this.previewData = jsonData.map(row => {
                        const getVal = (keys) => {
                            const k = Object.keys(row).find(key => keys.some(search => key.toLowerCase().includes(search.toLowerCase())))
                            return k ? row[k] : ''
                        }
                        const nis = getVal(['nis', 'induk'])
                        const nama = getVal(['nama'])
                        // Modifikasi: Mendeteksi 'saldo tabungan' atau 'saldo awal' atau 'total setoran' (jika itu yang dimaksud saldo)
                        // Karena Excel user punya kolom 'Saldo Tabungan' yang bersih
                        let rawSaldo = getVal(['saldo tabungan', 'saldo awal', 'saldo'])
                        
                        // Bersihkan format Rupiah (Rp, titik, spasi)
                        if (typeof rawSaldo === 'string') {
                            rawSaldo = rawSaldo.replace(/[^0-9]/g, '') // Hapus non-angka
                        } else if (typeof rawSaldo === 'number') {
                            // Jika sudah angka, biarkan saja
                        } else {
                            rawSaldo = 0
                        }
                        
                        const saldo = Number(rawSaldo || 0)
                        const catatan = getVal(['catatan', 'deskripsi']) || 'Saldo Awal'
                        return {
                            nis: nis,
                            nama_siswa: nama,
                            saldo_awal: saldo,
                            catatan: catatan
                        }
                    }).filter(r => r.nis || r.nama_siswa)
                } catch (err) {
                    alert("Gagal membaca file Excel. Pastikan format valid: " + err.message)
                }
            }
            reader.readAsArrayBuffer(file)
        },
        async processImport() {
            if (this.previewData.length === 0) return alert("Data kosong!")
            if (!confirm(`Yakin ingin mengimport ${this.previewData.length} baris saldo?`)) return
            this.isProcessing = true
            try {
                let ok = 0
                let fail = 0
                for (let i = 0; i < this.previewData.length; i++) {
                    const r = this.previewData[i]
                    // Convert to string safely before trim
                    const rowNis = String(r.nis || '').trim()
                    const rowNama = String(r.nama_siswa || '').trim().toLowerCase()
                    
                    const student = this.classMembers.find(s => String(s.identity_number || '').trim() === rowNis) || 
                                    this.classMembers.find(s => String(s.name || '').trim().toLowerCase() === rowNama)
                    if (!student) {
                        r._status = 'ERROR: Siswa tidak ditemukan'
                        fail++
                        continue
                    }
                    if (!r.saldo_awal || r.saldo_awal <= 0) {
                        r._status = 'SKIP: Saldo <= 0'
                        continue
                    }
                    const reqId = `SAVINIT-${student.id}-${this.config.classId}-${Math.round((r.saldo_awal || 0) * 100)}`
                    const payload = {
                        student_id: student.id,
                        type: 'DEPOSIT',
                        amount: r.saldo_awal,
                        description: r.catatan || 'Saldo Awal',
                        request_id: reqId
                    }
                    try {
                        const res = await fetch(window.BASE_URL + 'api/finance.php?action=save_savings', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        })
                        const result = await res.json()
                        if (result && result.success) {
                            r._status = 'OK'
                            ok++
                        } else {
                            r._status = 'ERROR'
                            fail++
                        }
                    } catch (e) {
                        r._status = 'ERROR'
                        fail++
                    }
                }
                alert(`Berhasil: ${ok}, Gagal: ${fail}`)
            } catch (e) {
                alert("Terjadi kesalahan sistem: " + e.message)
            } finally {
                this.isProcessing = false
            }
        }
    },
    mounted() {
        this.fetchUnits()
    }
}).mount('#app')
</script>
