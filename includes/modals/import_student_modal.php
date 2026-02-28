<!-- MODAL: IMPORT STUDENTS -->
<div v-if="showImportModal" v-cloak style="display: none;" :style="{ display: showImportModal ? 'flex' : 'none' }" class="fixed inset-0 bg-black/50 z-[100] items-center justify-center p-4 overflow-y-auto" @click.self="showImportModal = false">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden relative" @click.stop>
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="text-xl font-bold text-slate-800">Import Data Siswa</h3>
            <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 p-2"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <div class="mb-6 bg-blue-50 text-blue-800 p-4 rounded-lg text-sm">
                <p class="font-bold mb-1"><i class="fas fa-info-circle"></i> Petunjuk Import</p>
                <ul class="list-disc ml-4 space-y-1">
                    <li>Gunakan format CSV (Comma Separated Values).</li>
                    <li>Baris pertama harus berisi Header.</li>
                    <li>Data NIS dan Nama wajib diisi.</li>
                    <li>Jika sedang di menu kelas, siswa akan otomatis masuk kelas ini.</li>
                </ul>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Upload File (CSV / Excel)</label>
                <input type="file" accept=".csv, .xlsx, .xls" @change="handleFileUpload" class="w-full border border-slate-300 rounded-lg p-2 text-sm">
            </div>

            <div class="flex justify-end gap-3">
                <button @click="showImportModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                <button @click="uploadStudentCsv" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-bold">
                    <i class="fas fa-upload mr-2"></i> Upload Data
                </button>
            </div>
        </div>
    </div>
</div>
