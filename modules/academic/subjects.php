<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<script>
    window.USE_GLOBAL_APP = false;
    window.SKIP_GLOBAL_APP = true;
</script>

<div id="app" v-cloak class="flex flex-col h-screen bg-slate-50">
    <?php require_once '../../includes/academic_header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 relative">
        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Mata Pelajaran</h2>
                </div>
                <div class="flex-1 text-right">
                    <button v-if="currentUnit !== 'all'" @click="openSubjectModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Tambah Mapel</button>
                </div>
            </div>
            
            <div class="mb-4 flex gap-2 overflow-x-auto pb-2" v-if="currentUnit !== 'all'">
                <button @click="activeTabSubject = 'ALL'" :class="activeTabSubject === 'ALL' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors whitespace-nowrap">Semua</button>
                <button @click="activeTabSubject = 'CORE'" :class="activeTabSubject === 'CORE' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors whitespace-nowrap">Core (Wajib)</button>
                <button @click="activeTabSubject = 'MULOK'" :class="activeTabSubject === 'MULOK' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors whitespace-nowrap">Mulok</button>
                <button @click="activeTabSubject = 'EKSTRA'" :class="activeTabSubject === 'EKSTRA' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors whitespace-nowrap">Ekstrakurikuler</button>
                <button @click="activeTabSubject = 'CUSTOM'" :class="activeTabSubject === 'CUSTOM' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'" class="px-4 py-2 rounded-lg text-sm font-bold border transition-colors whitespace-nowrap">Custom</button>
            </div>

            <div v-if="currentUnit === 'all'" class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-blue-200">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-500">
                    <i class="fas fa-school text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700 mb-2">Pilih Unit Sekolah</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-6">Silakan pilih unit (TK, SD, SMP, SMA) pada menu bagian atas untuk mengelola data mata pelajaran.</p>
                <div class="flex gap-2 justify-center">
                    <button @click="currentUnit = 'TK'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">TK</button>
                    <button @click="currentUnit = 'SD'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SD</button>
                    <button @click="currentUnit = 'SMP'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SMP</button>
                    <button @click="currentUnit = 'SMA'" class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-bold">SMA</button>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase"><tr><th class="px-6 py-3">Kode</th><th class="px-6 py-3">Nama Mapel</th><th class="px-6 py-3">Tipe</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sub in filteredSubjects" :key="sub.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono text-slate-500 uppercase">{{ sub.code }}</td>
                            <td class="px-6 py-4 font-bold">{{ sub.name }}</td>
                            <td class="px-6 py-4">
                                <span v-if="sub.category === 'CORE'" class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-bold">CORE</span>
                                <span v-else-if="sub.category === 'MULOK'" class="bg-emerald-50 text-emerald-600 px-2 py-1 rounded text-xs font-bold">MULOK</span>
                                <span v-else-if="sub.category === 'EKSTRA'" class="bg-purple-50 text-purple-600 px-2 py-1 rounded text-xs font-bold">EKSTRA</span>
                                <span v-else-if="sub.category === 'CUSTOM'" class="bg-amber-50 text-amber-600 px-2 py-1 rounded text-xs font-bold">CUSTOM</span>
                                <span v-else class="bg-slate-50 text-slate-600 px-2 py-1 rounded text-xs font-bold">{{ sub.category }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openSubjectTeachersListModal(sub)" class="text-slate-500 hover:text-indigo-600 mr-3" title="Atur Pengajar"><i class="fas fa-chalkboard-teacher"></i></button>
                                <button @click="openSubjectModal(sub)" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-edit"></i></button>
                                <button @click="deleteSubject(sub)" class="text-slate-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: MANAGE SUBJECT -->
        <div v-if="showSubjectModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-xl font-bold text-slate-800">{{ subjectForm.id ? 'Edit' : 'Tambah' }} Mata Pelajaran</h3>
                    <button @click="showSubjectModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveSubject">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kode Mapel</label>
                            <input type="text" v-model="subjectForm.code" class="w-full border border-slate-300 rounded-lg px-3 py-2 uppercase" placeholder="MTK" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Mata Pelajaran</label>
                            <input type="text" v-model="subjectForm.name" class="w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Matematika" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Mapel</label>
                            <select v-model="subjectForm.category" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                <option value="CORE">CORE (Wajib)</option>
                                <option value="MULOK">MULOK (Muatan Lokal)</option>
                                <option value="EKSTRA">EKSTRA (Ekstrakurikuler)</option>
                                <option value="CUSTOM">CUSTOM (Khusus)</option>
                            </select>
                        </div>

                        <div class="flex gap-4 mb-6">
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Beban Default (JP/Pekan)</label>
                                <input type="number" v-model="subjectForm.default_weekly_count" min="1" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Total jam per minggu</p>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Durasi Default (JP/Sesi)</label>
                                <input type="number" v-model="subjectForm.default_session_length" min="1" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Durasi sekali tatap muka</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showSubjectModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- MODAL: SUBJECT TEACHERS LIST -->
        <div v-if="showSubjectTeachersListModal" v-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Pengajar Mata Pelajaran</h3>
                        <p class="text-sm text-slate-500">Atur guru pengampu per kelas</p>
                    </div>
                    <button @click="showSubjectTeachersListModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6 overflow-y-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase">
                            <tr>
                                <th class="px-4 py-2">Tingkatan</th>
                                <th class="px-4 py-2">Kelas</th>
                                <th class="px-4 py-2">Guru Pengampu</th>
                                <th class="px-4 py-2 text-center">Beban (Jam/Pekan)</th>
                                <th class="px-4 py-2 text-center">Durasi (Sesi)</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="assign in subjectAssignments" :key="assign.class_id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-500">{{ assign.level_name }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ assign.class_name }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="assign.teacher_name" class="text-blue-600 font-medium">{{ assign.teacher_name }}</span>
                                    <span v-else class="text-slate-400 italic text-xs">Belum ditentukan</span>
                                </td>
                                <td class="px-4 py-3 text-center">{{ assign.weekly_count }} JP</td>
                                <td class="px-4 py-3 text-center">{{ assign.session_length }} JP</td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="openAssignTeacherModalFromSubject(assign)" class="text-blue-600 hover:text-blue-800 px-3 py-1 rounded border border-blue-200 hover:bg-blue-50 text-xs font-bold">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: ASSIGN TEACHER FORM -->
        <div v-if="showAssignTeacherModal" v-cloak class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-xl font-bold text-slate-800">Edit Penugasan Guru</h3>
                    <button @click="showAssignTeacherModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveAssignTeacher">
                        <div class="mb-4 bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-blue-600 font-bold uppercase mb-1">Mata Pelajaran</p>
                            <p class="font-bold text-slate-800">{{ assignTeacherForm.subject_name }} ({{ assignTeacherForm.code }})</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Guru Pengampu</label>
                            <select v-model="assignTeacherForm.teacher_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">
                                <option value="">-- Belum Ada Guru --</option>
                                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        

                        <div class="flex gap-4 mb-6">
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Beban (JP/Pekan)</label>
                                <input type="number" v-model="assignTeacherForm.weekly_count" min="1" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Total jam mengajar per minggu</p>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Durasi per Sesi</label>
                                <input type="number" v-model="assignTeacherForm.session_length" min="1" class="w-full border border-slate-300 rounded-lg px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Jumlah JP sekali masuk kelas</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showAssignTeacherModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script type="module">
    import { academicMixin } from '../../assets/js/modules/academic.js';
    import { adminMixin } from '../../assets/js/modules/admin.js';
    const { createApp } = Vue;
    const app = createApp({
        mixins: [academicMixin],
        computed: {
            currentDate() {
                const d = new Date();
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const pad = (n) => String(n).padStart(2, '0');
                return `${days[d.getDay()]}, ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
            }
        }
    });
    app.mount('#app');
</script>

<?php require_once '../../includes/footer.php'; ?>
