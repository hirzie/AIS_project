            <!-- WALIKELAS LOCK OVERLAY (MOVED TO TUGAS TAB ONLY) -->
            <div v-if="currentPosition === 'wali' && homeroomTab==='TUGAS' && lockStatus && lockStatus.is_locked" class="fixed inset-0 top-16 z-50 bg-slate-100 flex items-center justify-center backdrop-blur-sm bg-slate-100/95">
                <div class="max-w-3xl w-full mx-6 bg-white border-l-8 border-red-500 rounded-2xl shadow-2xl overflow-hidden relative">
                    <!-- Background Icon -->
                    <div class="absolute top-0 right-0 -mt-8 -mr-8 text-red-50 text-9xl opacity-50 transform rotate-12 pointer-events-none">
                        <i class="fas fa-lock"></i>
                    </div>
                    
                    <div class="p-10 relative z-10">
                        <div class="flex items-start gap-8">
                            <div class="flex-shrink-0">
                                <div class="w-24 h-24 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-5xl shadow-inner animate-pulse">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-3xl font-bold text-slate-800 mb-2">Akses Terkunci</h2>
                                <p class="text-lg text-slate-600 mb-6 leading-relaxed">
                                    Mohon maaf, akses halaman Walikelas Anda dikunci sementara.<br>
                                    <span class="font-bold text-red-600">Harap menghadap Kepala Sekolah untuk membuka kunci.</span>
                                </p>
                                
                                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200 mb-8">
                                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">Penyebab Kunci</h3>
                                    <ul class="space-y-4">
                                        <li v-for="lock in lockStatus.locks" :key="lock.type || lock" class="flex items-start gap-4">
                                            <i class="fas fa-times-circle text-red-500 text-xl mt-0.5"></i>
                                            <div>
                                                <div class="font-bold text-slate-800 text-lg">{{ lock.message || lock }}</div>
                                                <div v-if="lock.detail" class="text-slate-500 mt-1">{{ lock.detail }}</div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <div class="flex flex-wrap gap-4">
                                    <button @click="checkLockStatus" class="bg-white hover:bg-slate-50 text-slate-700 border-2 border-slate-200 px-8 py-4 rounded-xl font-bold text-lg transition-all flex items-center gap-3 hover:border-slate-300">
                                        <i class="fas fa-sync-alt"></i>
                                        Cek Status Lagi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama'" class="mb-6 border-b border-slate-200">
            <!-- Class Selector for authorized roles -->
            <div v-if="canSwitchClass" class="mb-4 flex items-center gap-3 bg-blue-50 p-3 rounded-lg border border-blue-100">
                <div class="text-sm font-bold text-blue-800">Lihat Kelas:</div>
                <select v-model="selectedClassId" @change="applySelectedClass" class="border border-blue-300 rounded px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="" disabled>-- Pilih Kelas --</option>
                    <option v-for="c in classesOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <div v-if="loading" class="text-xs text-blue-600 animate-pulse">Memuat data...</div>
            </div>

            <div class="flex items-center gap-6">
                <button @click="homeroomTab='DASH'" :class="waliTabClass('DASH')" class="pb-3 text-sm font-bold border-b-2 transition-colors">Dashboard</button>
                <button @click="homeroomTab='SISWA'" :class="waliTabClass('SISWA')" class="pb-3 text-sm font-bold border-b-2 transition-colors">Data Siswa</button>
                <button @click="homeroomTab='JADWAL'" :class="waliTabClass('JADWAL')" class="pb-3 text-sm font-bold border-b-2 transition-colors">Pelajaran</button>
                <button @click="homeroomTab='ABSENSI'" :class="waliTabClass('ABSENSI')" class="pb-3 text-sm font-bold border-b-2 transition-colors">Absensi</button>
                <button @click="homeroomTab='BK'" :class="waliTabClass('BK')" class="pb-3 text-sm font-bold border-b-2 transition-colors">BK</button>
                <button @click="homeroomTab='INVENTARIS'" :class="waliTabClass('INVENTARIS')" class="pb-3 text-sm font-bold border-b-2 transition-colors">Inventaris</button>
                <button @click="homeroomTab='TUGAS'" :class="homeroomTab==='TUGAS' ? 'border-red-600 text-red-600' : 'border-transparent text-red-600 hover:text-red-800'" class="pb-3 text-sm font-bold border-b-2 transition-colors">
                    Tugas Saya <i v-if="lockStatus && lockStatus.is_locked" class="fas fa-lock ml-1 text-xs"></i>
                </button>
                <button @click="homeroomTab='DISPLAY'" :class="waliTabClass('DISPLAY')" class="pb-3 text-sm font-bold border-b-2 transition-colors">
                    <i class="fas fa-tv mr-1"></i> Display Kelas
                </button>
            </div>
        </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='DASH'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500 mb-1">Total Siswa</div>
                    <div class="text-2xl font-bold text-slate-800">{{ classStudents.length }}</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500 mb-1">Kehadiran Hari Ini</div>
                    <div class="flex items-end gap-2">
                        <div class="text-2xl font-bold text-emerald-600">{{ attendancePercent }}%</div>
                        <div class="text-xs text-slate-400 mb-1">Hadir</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500 mb-1">Tidak Hadir</div>
                    <div class="text-2xl font-bold text-red-600">{{ absentStudents.length }}</div>
                    <div class="text-xs text-slate-400">Sakit/Izin/Alfa</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500 mb-1">Kasus BK Aktif</div>
                    <div class="text-2xl font-bold text-amber-600">{{ pendingIssues.length }}</div>
                </div>
            </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='SISWA'" class="grid grid-cols-1 gap-6">
                <div class="flex items-center justify-between mb-4">
                     <div class="text-sm font-bold text-slate-800">Daftar Siswa {{ className || 'Kelas Wali' }}</div>
                     <div class="flex items-center gap-2">
                         <input v-model="studentSearch" type="text" placeholder="Cari nama/NIS..." class="border border-slate-300 rounded px-3 py-1 text-sm w-64" />
                     </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 w-16 text-center">No</th>
                                <th class="px-6 py-3">NIS</th>
                                <th class="px-6 py-3">Nama Siswa</th>
                                <th class="px-6 py-3 text-center">L/P</th>
                                <th class="px-6 py-3">Tempat, Tgl Lahir</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(s, index) in filteredClassStudents" :key="s.id" class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-center text-slate-500">{{ index + 1 }}</td>
                                <td class="px-6 py-4 font-mono text-slate-600">{{ s.nis || '-' }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ s.name }}</div>
                                </td>
                                <td class="px-6 py-4 text-center font-bold" :class="s.gender==='L'?'text-blue-600':'text-pink-600'">{{ s.gender || '-' }}</td>
                                <td class="px-6 py-4 text-slate-600 text-xs">
                                    {{ s.birth_place || '-' }}, {{ s.birth_date ? new Date(s.birth_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openStudentModal(s)" class="text-xs font-bold text-slate-600 hover:text-slate-800 bg-slate-100 px-3 py-1 rounded mr-2">Lihat Detail</button>
                                    <button @click="openPortfolio(s)" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1 rounded">Portfolio</button>
                                </td>
                            </tr>
                            <tr v-if="filteredClassStudents.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">Tidak ada data siswa.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='DISPLAY'" class="grid grid-cols-1 gap-6">
                <!-- AI ASSISTANT -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6 relative overflow-hidden">
                    <div class="absolute right-0 top-0 p-6 opacity-5 pointer-events-none">
                        <i class="fas fa-brain text-9xl text-indigo-600"></i>
                    </div>
                    
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-lg">
                            <i class="fas fa-robot text-indigo-600"></i> AI Assistant (Google Gemini)
                        </h3>
                        <div class="flex items-center gap-2">
                             <div class="form-check form-switch">
                                <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="displaySettings.aiActive" @change="saveDisplaySettings('ai_active')" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative z-10">
                        <p class="text-sm text-slate-500 mb-4">
                            Minta AI untuk membuat konten edukasi, rumus, soal, atau penjelasan visual untuk ditampilkan di TV.
                        </p>
                        
                        <div class="flex gap-2 mb-4">
                            <input type="text" v-model="aiPrompt" @keyup.enter="generateAIContent" class="flex-1 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" placeholder="Contoh: Rumus Energi Kinetik, Soal Matematika SD, Rantai Makanan...">
                            <button @click="generateAIContent" :disabled="aiLoading" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 flex items-center gap-2 min-w-[140px] justify-center">
                                <i class="fas" :class="aiLoading ? 'fa-spinner fa-spin' : 'fa-magic'"></i>
                                {{ aiLoading ? 'Generating...' : 'Generate' }}
                            </button>
                        </div>
                        
                        <div class="text-xs text-slate-500 mb-4 bg-indigo-50 p-2 rounded border border-indigo-100">
                            <i class="fas fa-lightbulb text-indigo-500 mr-1"></i>
                            <strong>Tips:</strong> Ketik "buat grafik..." untuk visualisasi otomatis.
                        </div>

                        <!-- AI Result Preview -->
                        <div v-if="aiResult" class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 mt-4 shadow-sm">
                            <div class="bg-slate-100 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Preview Konten</div>
                                <button @click="aiResult = null" class="text-slate-400 hover:text-red-500 transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="p-6 overflow-y-auto max-h-[300px] prose prose-sm max-w-none bg-white border-b border-slate-200" v-html="aiResult"></div>
                            <div class="bg-slate-50 px-4 py-3 flex justify-end gap-2">
                                <button @click="showAIOnTV" class="bg-emerald-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                    <i class="fas fa-tv"></i> Tampilkan di TV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Header / TV Link -->
                <div class="bg-indigo-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
                    <div class="absolute right-0 top-0 p-6 opacity-10">
                        <i class="fas fa-tv text-9xl text-white"></i>
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">TV Mode Kelas {{ className }}</h2>
                            <p class="text-indigo-100 mb-6 max-w-xl">
                                Tampilan layar penuh untuk di kelas. Menampilkan jadwal pelajaran, guru, jam, dan pengumuman penting secara real-time.
                            </p>
                            <div class="flex items-center gap-4">
                                <a v-if="classSlug" :href="baseUrl + 'index.php/' + classSlug" target="_blank" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-bold hover:bg-indigo-50 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                                    <i class="fas fa-external-link-alt"></i> Buka Layar TV
                                </a>
                                <div v-else class="bg-white/20 px-4 py-2 rounded text-sm">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Menyiapkan Link...
                                </div>
                                <button @click="copyToClipboard(baseUrl + 'index.php/' + classSlug)" class="text-sm underline hover:text-indigo-200 ml-2">
                                    Salin Link
                                </button>
                            </div>
                        </div>
                        <div class="bg-white/10 p-4 rounded-lg backdrop-blur-sm border border-white/20 text-center min-w-[200px]">
                            <div class="text-xs uppercase tracking-widest text-indigo-200 mb-1">Status Koneksi</div>
                            <div class="font-bold text-lg"><i class="fas fa-wifi mr-2"></i> Online</div>
                            <div class="text-xs text-indigo-200 mt-2">Auto-refresh: 30s</div>
                        </div>
                    </div>
                </div>

                <!-- Settings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- PDF Remote Control -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:col-span-2 border-l-8 border-red-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-file-pdf text-red-600"></i> PDF Remote Control
                        </h3>
                        <div class="flex items-center gap-2">
                             <div class="form-check form-switch">
                                <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="displaySettings.pdfActive" @change="saveDisplaySettings('pdf_active')" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-4">
                        <!-- Upload Section -->
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <input type="file" ref="pdfInput" accept="application/pdf" class="hidden" @change="uploadPDF">
                            <button @click="$refs.pdfInput.click()" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 transition-colors flex items-center gap-2" :disabled="uploadingPDF">
                                <i class="fas" :class="uploadingPDF ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                                {{ uploadingPDF ? 'Mengupload...' : 'Upload PDF' }}
                            </button>
                            <div v-if="displaySettings.pdfUrl" class="text-sm text-slate-600 truncate flex-1">
                                <i class="fas fa-check-circle text-emerald-500 mr-1"></i> {{ displaySettings.pdfUrl.split('/').pop() }}
                            </div>
                            <div v-else class="text-sm text-slate-400 italic">Belum ada PDF</div>
                        </div>

                        <!-- Remote Controls (Only visible if active) -->
                        <div v-if="displaySettings.pdfActive && displaySettings.pdfUrl" class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                            <!-- PDF Thumbnail Preview -->
                            <div class="col-span-1 md:col-span-3 mb-2 flex justify-center">
                                <div class="relative h-[200px] aspect-[4/3] bg-slate-100 rounded-lg overflow-hidden border border-slate-300 shadow-inner flex items-center justify-center">
                                    <canvas ref="pdfThumbnailCanvas" class="max-w-full max-h-full"></canvas>
                                    <div v-if="pdfLoading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                                        <i class="fas fa-circle-notch fa-spin text-3xl text-indigo-500"></i>
                                    </div>
                                    <div class="absolute bottom-2 right-2 bg-black/50 text-white text-[10px] px-2 py-1 rounded">
                                        Page {{ displaySettings.pdfPage }}
                                    </div>
                                </div>
                            </div>

                            <!-- Prev/Next -->
                            <div class="md:col-span-2 flex items-center justify-between bg-slate-800 text-white p-4 rounded-xl shadow-lg">
                                <button @click="changePDFPage(-1)" class="p-3 bg-slate-700 rounded-lg hover:bg-slate-600 active:bg-slate-500 transition-all w-16 flex items-center justify-center">
                                    <i class="fas fa-chevron-left text-xl"></i>
                                </button>
                                
                                <div class="text-center">
                                    <div class="text-xs text-slate-400 uppercase tracking-widest mb-1">Halaman</div>
                                    <div class="text-3xl font-mono font-bold">{{ displaySettings.pdfPage || 1 }}</div>
                                </div>

                                <button @click="changePDFPage(1)" class="p-3 bg-slate-700 rounded-lg hover:bg-slate-600 active:bg-slate-500 transition-all w-16 flex items-center justify-center">
                                    <i class="fas fa-chevron-right text-xl"></i>
                                </button>
                            </div>

                            <!-- Jump to Page -->
                            <div class="bg-slate-100 p-4 rounded-xl border border-slate-200 flex flex-col justify-center">
                                <label class="text-xs font-bold text-slate-500 mb-2 uppercase">Lompat ke</label>
                                <div class="flex gap-2">
                                    <input type="number" v-model="pdfJumpPage" min="1" class="w-full border border-slate-300 rounded px-3 py-2 text-center font-bold" @keyup.enter="updatePDFPage(pdfJumpPage)">
                                    <button @click="updatePDFPage(pdfJumpPage)" class="bg-slate-800 text-white px-3 rounded hover:bg-slate-700">
                                        Go
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Material Input -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:col-span-2 border-l-8 border-indigo-500">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-book-open text-indigo-600"></i> Materi Hari Ini (Real-time)
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Materi yang diinput akan langsung tampil di layar TV kelas.</p>
                        <textarea v-model="displaySettings.materialText" rows="2" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Contoh: Bab 5 - Hukum Newton (Fisika)"></textarea>
                        <div class="mt-4 flex justify-end">
                            <button @click="saveMaterial()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-colors shadow-md hover:shadow-lg flex items-center gap-2">
                                <i class="fas fa-upload"></i> Unggah Materi
                            </button>
                        </div>
                    </div>

                    <!-- Running Text / Ticker -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-scroll text-blue-600"></i> Pesan Berjalan (Ticker)
                            </h3>
                            <div class="form-check form-switch">
                                <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="displaySettings.tickerActive" @change="saveDisplaySettings('ticker')" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Pesan ini akan berjalan di bagian bawah layar TV.</p>
                        <textarea v-model="displaySettings.tickerText" rows="3" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Contoh: Jangan lupa mengumpulkan tugas Bahasa Indonesia besok..."></textarea>
                        <div class="mt-4 flex justify-end">
                            <button @click="saveDisplaySettings('ticker')" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-700 transition-colors">
                                <i class="fas fa-save mr-2"></i> Update Ticker
                            </button>
                        </div>
                    </div>

                    <!-- Urgent Message -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle text-amber-500"></i> Pesan Penting (Overlay)
                            </h3>
                            <div class="form-check form-switch">
                                <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="displaySettings.urgentActive" @change="saveDisplaySettings('urgent')" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Pesan ini akan menutupi layar (Full Screen) untuk pengumuman darurat.</p>
                        <textarea v-model="displaySettings.urgentText" rows="3" class="w-full border border-red-200 bg-red-50 rounded-lg p-3 text-sm text-red-900 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" placeholder="Contoh: Harap tenang, ujian sedang berlangsung..."></textarea>
                        <div class="mt-4 flex justify-end">
                            <button @click="displaySettings.urgentActive = true; saveDisplaySettings('urgent')" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 transition-colors">
                                <i class="fas fa-bullhorn mr-2"></i> Tayangkan Alert
                            </button>
                        </div>
                    </div>

                    <!-- Override Image -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-image text-purple-500"></i> Override Gambar (Full Screen)
                            </h3>
                            <div class="form-check form-switch">
                                <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="displaySettings.imageActive" @change="saveDisplaySettings('image_override')" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Upload gambar untuk ditampilkan di TV (menutupi jadwal).</p>
                        
                        <div class="mb-4">
                            <div v-if="displaySettings.imageOverride" class="mb-3 relative group">
                                <img :src="baseUrl + displaySettings.imageOverride" class="w-full h-48 object-cover rounded-lg border border-slate-200">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-lg">
                                    <a :href="baseUrl + displaySettings.imageOverride" target="_blank" class="text-white text-sm font-bold hover:underline"><i class="fas fa-external-link-alt mr-1"></i> Lihat Full</a>
                                </div>
                            </div>
                            <div v-else class="w-full h-48 bg-slate-100 rounded-lg flex flex-col items-center justify-center text-slate-400 border-2 border-dashed border-slate-300 mb-3">
                                <i class="fas fa-image text-3xl mb-2"></i>
                                <span class="text-xs">Belum ada gambar</span>
                            </div>
                            
                            <div class="flex gap-2 items-center">
                                <input type="file" ref="overrideImageInput" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                <button @click="uploadOverrideImage" :disabled="isUploadingImage" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-purple-700 transition-colors disabled:opacity-50 flex items-center whitespace-nowrap">
                                    <i v-if="isUploadingImage" class="fas fa-spinner fa-spin mr-2"></i>
                                    <i v-else class="fas fa-upload mr-2"></i> Upload
                                </button>
                            </div>

                            <!-- Image History List -->
                            <div v-if="displaySettings.imageHistory && displaySettings.imageHistory.length > 0" class="mt-6 border-t border-slate-100 pt-4">
                                <h4 class="text-sm font-bold text-slate-700 mb-3">Daftar Gambar</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div v-for="img in displaySettings.imageHistory" :key="img.id" class="relative group rounded-lg overflow-hidden border border-slate-200 transition-all hover:shadow-md" :class="{'ring-2 ring-purple-500 ring-offset-1': img.is_active == 1}">
                                        <div class="aspect-video bg-slate-100">
                                            <img :src="baseUrl + img.content" class="w-full h-full object-cover">
                                        </div>
                                        
                                        <!-- Active Badge -->
                                        <div v-if="img.is_active == 1" class="absolute top-2 right-2 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm z-10">
                                            Aktif
                                        </div>

                                        <!-- Actions Overlay -->
                                        <div class="absolute inset-0 bg-slate-900/70 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-2 transition-opacity p-3 backdrop-blur-sm">
                                            <button v-if="img.is_active == 0" @click="setActiveImage(img.id)" class="bg-emerald-500 text-white text-xs px-3 py-1.5 rounded-md font-bold hover:bg-emerald-600 w-full shadow-lg transform hover:scale-105 transition-transform">
                                                <i class="fas fa-play mr-1"></i> Tampilkan
                                            </button>
                                            <button v-else class="bg-slate-500 text-white text-xs px-3 py-1.5 rounded-md font-bold cursor-default w-full opacity-80">
                                                <i class="fas fa-check mr-1"></i> Sedang Tampil
                                            </button>
                                            
                                            <button @click="deleteImage(img.id)" class="bg-red-500/80 text-white text-xs px-3 py-1.5 rounded-md font-bold hover:bg-red-600 w-full hover:text-white transition-colors">
                                                <i class="fas fa-trash mr-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='ABSENSI'" class="grid grid-cols-1 gap-6">
                <div v-if="lockStatus && lockStatus.is_locked" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                     <div class="flex">
                        <div class="flex-shrink-0">
                          <i class="fas fa-lock text-red-500"></i>
                        </div>
                        <div class="ml-3">
                          <p class="text-sm text-red-700">
                            Fitur Absensi terkunci karena ada tanggungan. Silakan cek tab <button @click="homeroomTab='TUGAS'" class="font-bold underline">Tugas Saya</button> untuk detail.
                          </p>
                        </div>
                      </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6" :class="{'opacity-50 pointer-events-none': lockStatus && lockStatus.is_locked}">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 mb-1">Absensi Harian</h3>
                            <p class="text-xs text-slate-500">Kelola kehadiran siswa untuk tanggal terpilih.</p>
                        </div>
                        <div class="flex items-center gap-3">
                             <input type="date" v-model="dailyDate" class="border border-slate-300 rounded px-3 py-2 text-sm font-bold text-slate-700 focus:outline-none focus:border-blue-500">
                             <button @click="saveDailyBatch()" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700 transition-colors">Simpan Absensi</button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center">No</th>
                                    <th class="px-4 py-3">Nama Siswa</th>
                                    <th class="px-4 py-3 text-center w-24">Hadir</th>
                                    <th class="px-4 py-3 text-center w-24">Sakit</th>
                                    <th class="px-4 py-3 text-center w-24">Izin</th>
                                    <th class="px-4 py-3 text-center w-24">Alfa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(s, idx) in classStudents" :key="s.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-center text-slate-500">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-700">{{ s.name }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <label class="cursor-pointer block">
                                            <input type="radio" :name="'att-'+s.id" value="Hadir" v-model="s.status" class="accent-emerald-600 w-4 h-4">
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <label class="cursor-pointer block">
                                            <input type="radio" :name="'att-'+s.id" value="Sakit" v-model="s.status" class="accent-blue-600 w-4 h-4">
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <label class="cursor-pointer block">
                                            <input type="radio" :name="'att-'+s.id" value="Izin" v-model="s.status" class="accent-amber-500 w-4 h-4">
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <label class="cursor-pointer block">
                                            <input type="radio" :name="'att-'+s.id" value="Alfa" v-model="s.status" class="accent-red-600 w-4 h-4">
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='JADWAL'" class="grid grid-cols-1 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Jadwal Pelajaran</div>
                            <div class="text-2xl font-bold text-slate-800">{{ className || 'Kelas Wali' }}</div>
                        </div>
                    </div>
                    <div v-if="academicSlots && academicSlots.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-4 py-2 w-28 border-r border-slate-200">Waktu</th>
                                    <th v-for="d in ['Senin','Selasa','Rabu','Kamis','Jumat']" :key="d" class="px-4 py-2 text-center border-r border-slate-200 min-w-[120px]">{{ d }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="slot in academicSlots" :key="(slot.id||slot.start)+'-'+(slot.end||'')" v-show="!slot.isBreak">
                                    <td class="px-4 py-2 font-mono text-xs text-slate-500 border-r border-slate-200 bg-slate-50/50">
                                        <div class="font-bold">{{ (slot.start||'').slice(0,5) }} - {{ (slot.end||'').slice(0,5) }}</div>
                                        <div class="text-[10px]">{{ slot.name || '' }}</div>
                                    </td>
                                    <td v-for="d in ['Senin','Selasa','Rabu','Kamis','Jumat']" :key="d + (slot.id||slot.start)" class="p-1 border-r border-slate-100 align-top h-16">
                                        <div v-if="getScheduleItem(d, (slot.start||'').slice(0,5))" class="bg-blue-50 border border-blue-100 p-2 rounded h-full">
                                            <div class="font-bold text-blue-800 text-xs mb-1 line-clamp-2">
                                                {{ getScheduleItem(d, (slot.start||'').slice(0,5)).subject_name }}
                                            </div>
                                            <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                                <i class="fas fa-user-tie text-[9px]"></i>
                                                <span class="truncate">{{ getScheduleItem(d, (slot.start||'').slice(0,5)).teacher_name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-sm text-slate-500">Belum ada slot jadwal.</div>
                </div>
            </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && homeroomTab==='BK'" class="grid grid-cols-1 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center text-xl">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">BK</div>
                                <div class="text-xs text-slate-400">Tim BK, Konseling, Psikoedukasi</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="bkTab='KONSELING'" :class="bkTabClass('KONSELING')" class="px-3 py-1 rounded text-[11px] font-bold">Konseling</button>
                            <button @click="bkTab='TIMBK'" :class="bkTabClass('TIMBK')" class="px-3 py-1 rounded text-[11px] font-bold">Tim BK</button>
                            <button @click="bkTab='PSIKO'" :class="bkTabClass('PSIKO')" class="px-3 py-1 rounded text-[11px] font-bold">Psikoedukasi</button>
                        </div>
                    </div>
                    <div v-show="bkTab==='KONSELING'">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-[11px] text-slate-500">Daftar kejadian menunggu tindakan untuk kelas wali.</div>
                            <button @click="fetchPendingIssues" class="px-2.5 py-1 rounded bg-slate-100 text-slate-600 text-[11px] hover:bg-slate-200">Muat Ulang</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="i in pendingIssues" :key="i.id" class="p-3 border border-slate-200 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-bold text-slate-800 truncate">{{ i.title }}</div>
                                    <div class="text-[10px] text-slate-400">{{ formatDate(i.created_at) }}</div>
                                </div>
                                <div class="text-[11px] text-slate-500 mb-2">{{ i.student_name }} â€¢ {{ i.category }} â€¢ {{ i.severity }}</div>
                                <div class="flex items-center gap-2">
                                    <button @click="openResolveModal(i)" class="px-3 py-1 rounded bg-emerald-600 text-white text-[11px] font-bold">Selesaikan Internal</button>
                                    <button @click="openEscalateModal(i)" class="px-3 py-1 rounded bg-red-600 text-white text-[11px] font-bold">Eskalasi ke BK</button>
                                </div>
                            </div>
                            <div v-if="pendingIssues.length===0" class="text-xs text-slate-500">Tidak ada isu menunggu.</div>
                        </div>
                    </div>
                    <div v-show="bkTab==='TIMBK'">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Tim BK Unit</div>
                                <div class="text-xs text-slate-400">Kontak & Peran</div>
                            </div>
                        </div>
                        <div v-if="bkProfile.team && bkProfile.team.length > 0" class="space-y-2">
                            <div v-for="m in bkProfile.team" :key="m.id || m.name" class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ m.name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ m.role || 'BK' }}</div>
                                </div>
                                <div class="text-[10px] text-slate-400">{{ m.phone || '-' }}</div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-500">Belum ada data tim BK.</div>
                    </div>
                    <div v-show="bkTab==='PSIKO'">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">Psikoedukasi</div>
                                <div class="text-xs text-slate-400">Materi untuk siswa/ortu</div>
                            </div>
                        </div>
                        <div v-if="bkArticles.length > 0" class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="divide-y divide-slate-100">
                                <div v-for="a in bkArticles" :key="a.id || a.title" class="px-4 py-3 bg-white hover:bg-slate-50 transition-colors flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm">{{ a.title }}</div>
                                        <div class="text-[10px] text-slate-500">{{ a.category || '' }}</div>
                                    </div>
                                    <a :href="a.url || (baseUrl + (a.file_name ? ('uploads/managerial/docs/' + a.file_name) : ''))" target="_blank" class="text-[11px] font-bold text-indigo-600 hover:underline">Buka</a>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-500">Belum ada artikel.</div>
                    </div>
                </div>
            </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && !lockStatus.is_locked && homeroomTab==='INVENTARIS'" class="grid grid-cols-1 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Inventaris Kelas</h3>
                            <p class="text-xs text-slate-500">Daftar aset dan kondisi barang di kelas.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 w-16 text-center">No</th>
                                    <th class="px-6 py-3">Kode Barang</th>
                                    <th class="px-6 py-3">Nama Barang</th>
                                    <th class="px-6 py-3 text-center">Jumlah</th>
                                    <th class="px-6 py-3 text-center">Kondisi</th>
                                    <th class="px-6 py-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in inventoryAssets" :key="item.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-center text-slate-500">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-600 text-xs">{{ item.id }}</td>
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-center font-bold">{{ item.count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 rounded text-xs font-bold" :class="item.condition === 'Baik' ? 'bg-emerald-100 text-emerald-700' : (item.condition === 'Rusak Ringan' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')">
                                            {{ item.condition || 'Baik' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-xs">{{ item.note || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div v-if="currentPosition === 'wali' && currentUnit !== 'asrama' && !lockStatus.is_locked && homeroomTab==='TUGAS'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Presensi Harian -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                        <i class="fas fa-calendar-check text-8xl text-emerald-600"></i>
                    </div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mb-4">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 mb-1">Presensi Harian</h3>
                            <p class="text-sm text-slate-500 mb-6">Wajib diisi setiap hari sekolah sebelum jam 08:00.</p>
                            
                            <div v-if="attendanceSubmitted" class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-200 text-emerald-700 flex items-center justify-center text-sm font-bold">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-emerald-800">Sudah Dilakukan</div>
                                        <div class="text-xs text-emerald-600">Terima kasih telah melakukan presensi.</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="bg-red-50 rounded-xl p-4 border border-red-100 mb-6 animate-pulse">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-red-200 text-red-700 flex items-center justify-center text-sm font-bold">
                                        <i class="fas fa-exclamation"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-red-800">Belum Dilakukan</div>
                                        <div class="text-xs text-red-600">Mohon segera lakukan presensi siswa.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button @click="homeroomTab='ABSENSI'" class="w-full py-3 rounded-xl font-bold transition-colors" :class="attendanceSubmitted ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-200'">
                            {{ attendanceSubmitted ? 'Lihat Detail Absensi' : 'Isi Absensi Sekarang' }}
                        </button>
                    </div>
                </div>

                <!-- Inventaris Mingguan -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                        <i class="fas fa-boxes text-8xl text-orange-600"></i>
                    </div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl mb-4">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 mb-1">Laporan Inventaris</h3>
                            <p class="text-sm text-slate-500 mb-6">Wajib dilaporkan setiap hari Sabtu (pekanan).</p>
                            
                            <div v-if="isSaturday" class="bg-orange-50 rounded-xl p-4 border border-orange-100 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-200 text-orange-700 flex items-center justify-center text-sm font-bold animate-bounce">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-orange-800">Jadwal Lapor Hari Ini!</div>
                                        <div class="text-xs text-orange-600">Mohon cek kondisi barang di kelas.</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-sm font-bold">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-600">Belum Jadwal Lapor</div>
                                        <div class="text-xs text-slate-500">Jadwal lapor berikutnya hari Sabtu.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button @click="homeroomTab='INVENTARIS'" class="w-full py-3 rounded-xl font-bold transition-colors" :class="isSaturday ? 'bg-orange-600 text-white hover:bg-orange-700 shadow-lg shadow-orange-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                            {{ isSaturday ? 'Lapor Inventaris Sekarang' : 'Cek Data Inventaris' }}
                        </button>
                    </div>
                </div>

                <!-- Presensi Bulanan -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                        <i class="fas fa-file-invoice text-8xl text-indigo-600"></i>
                    </div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl mb-4">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 mb-1">Rekap Bulanan</h3>
                            <p class="text-sm text-slate-500 mb-6">Validasi kehadiran siswa per bulan.</p>
                            
                            <div v-if="monthlyRecapSubmitted" class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-200 text-emerald-700 flex items-center justify-center text-sm font-bold">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-emerald-800">Sudah Validasi</div>
                                        <div class="text-xs text-emerald-600">Data bulan ini aman.</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="bg-red-50 rounded-xl p-4 border border-red-100 mb-6" :class="{'animate-pulse': monthlyMonth === 1}">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-red-200 text-red-700 flex items-center justify-center text-sm font-bold">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-red-800">{{ monthlyMonth === 1 ? 'Peringatan Januari!' : 'Belum Divalidasi' }}</div>
                                        <div class="text-xs text-red-600">Wajib rekap akhir bulan.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button @click="monthlyRecapModal=true" class="w-full py-3 rounded-xl font-bold transition-colors" :class="monthlyRecapSubmitted ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-200'">
                            {{ monthlyRecapSubmitted ? 'Lihat Rekap' : 'Validasi Sekarang' }}
                        </button>
                    </div>
                </div>
            </div>
