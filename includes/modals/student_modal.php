<!-- MODAL: ADD/EDIT STUDENT -->
<div v-if="showStudentModal" v-cloak style="display: none;" :style="{ display: showStudentModal ? 'flex' : 'none' }" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden animate-fade">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="text-xl font-bold text-slate-800">{{ studentForm.id ? 'Edit' : 'Tambah' }} Siswa</h3>
                            <button @click="showStudentModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                        </div>
                        <div class="p-6">
                            <form @submit.prevent="saveStudent">
                                <!-- TABS NAVIGATION -->
                                <div class="flex border-b border-slate-200 mb-6">
                                    <button type="button" @click="activeTabStudent = 'data_diri'" :class="activeTabStudent === 'data_diri' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-sm font-bold border-b-2 transition-colors">Data Diri</button>
                                    <button type="button" @click="activeTabStudent = 'orang_tua'" :class="activeTabStudent === 'orang_tua' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-sm font-bold border-b-2 transition-colors">Orang Tua</button>
                                    <button type="button" @click="activeTabStudent = 'wali'" :class="activeTabStudent === 'wali' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-sm font-bold border-b-2 transition-colors">Wali</button>
                                    <button type="button" @click="activeTabStudent = 'lainnya'" :class="activeTabStudent === 'lainnya' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-sm font-bold border-b-2 transition-colors">Lainnya</button>
                                    <button type="button" @click="activeTabStudent = 'custom'" :class="activeTabStudent === 'custom' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-sm font-bold border-b-2 transition-colors">Custom (Sekolah)</button>
                                </div>

                                <!-- TAB CONTENT -->
                                <div class="max-h-[60vh] overflow-y-auto pr-2">
                                    <div :class="{'pointer-events-none opacity-90': currentPosition === 'wali'}">
                                    
                                    <!-- TAB: DATA DIRI -->
                                    <div v-show="activeTabStudent === 'data_diri'" class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                                                <input v-model="studentForm.name" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Panggilan</label>
                                                <input v-model="studentForm.nickname" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">NIS (Nomor Induk) *</label>
                                                <input v-model="studentForm.nis" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">NISN</label>
                                                <input v-model="studentForm.nisn" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">NIK (KTP/KIA)</label>
                                                <input v-model="studentForm.nik" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                                                <select v-model="studentForm.gender" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                                    <option value="L">Laki-laki</option>
                                                    <option value="P">Perempuan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir</label>
                                                <input v-model="studentForm.birth_place" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir</label>
                                                <input v-model="studentForm.birth_date" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Agama</label>
                                                <input v-model="studentForm.religion" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Bahasa Sehari-hari</label>
                                                <input v-model="studentForm.daily_language" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Kelas Saat Ini</label>
                                                <select v-model="studentForm.class_id" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                                    <option value="">-- Belum Masuk Kelas --</option>
                                                    <option v-for="cls in activeClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Status Siswa</label>
                                                <select v-model="studentForm.status" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                                    <option value="ACTIVE">Aktif</option>
                                                    <option value="MUTASI">Mutasi/Pindah</option>
                                                    <option value="ALUMNI">Lulus (Alumni)</option>
                                                    <option value="INACTIVE">Non-Aktif/Keluar</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
                                            <textarea v-model="studentForm.address" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                                        </div>
                                    </div>

                                    <!-- TAB: ORANG TUA -->
                                    <div v-show="activeTabStudent === 'orang_tua'" class="space-y-6">
                                        <!-- AYAH -->
                                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                            <h4 class="font-bold text-blue-800 text-sm mb-3 border-b border-blue-200 pb-1">Data Ayah</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Nama Ayah</label><input v-model="studentForm.father_name" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">NIK Ayah</label><input v-model="studentForm.father_pin" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Tahun Lahir</label><input v-model="studentForm.father_birth_date" type="date" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Pendidikan</label><input v-model="studentForm.father_education" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Pekerjaan</label><input v-model="studentForm.father_job" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Penghasilan</label><input v-model="studentForm.father_income" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                            </div>
                                        </div>
                                        <!-- IBU -->
                                        <div class="bg-pink-50 p-4 rounded-lg border border-pink-100">
                                            <h4 class="font-bold text-pink-800 text-sm mb-3 border-b border-pink-200 pb-1">Data Ibu</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Nama Ibu</label><input v-model="studentForm.mother_name" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">NIK Ibu</label><input v-model="studentForm.mother_pin" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Tahun Lahir</label><input v-model="studentForm.mother_birth_date" type="date" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Pendidikan</label><input v-model="studentForm.mother_education" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Pekerjaan</label><input v-model="studentForm.mother_job" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="text-[10px] uppercase font-bold text-slate-500">Penghasilan</label><input v-model="studentForm.mother_income" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: WALI -->
                                    <div v-show="activeTabStudent === 'wali'" class="space-y-4">
                                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                            <h4 class="font-bold text-slate-800 text-sm mb-3">Data Wali (Opsional)</h4>
                                            <div class="space-y-3">
                                                <div><label class="block text-xs font-bold text-slate-700 mb-1">Nama Wali</label><input v-model="studentForm.guardian_name" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                <div><label class="block text-xs font-bold text-slate-700 mb-1">Alamat Wali</label><textarea v-model="studentForm.guardian_address" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></textarea></div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div><label class="block text-xs font-bold text-slate-700 mb-1">No Telpon</label><input v-model="studentForm.guardian_phone" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                    <div><label class="block text-xs font-bold text-slate-700 mb-1">No HP</label><input v-model="studentForm.guardian_mobile_1" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: LAINNYA -->
                                    <div v-show="activeTabStudent === 'lainnya'" class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Sekolah Asal</label>
                                                <input v-model="studentForm.school_origin" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Masuk</label>
                                                <input v-model="studentForm.admission_year" type="number" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Tinggi (cm)</label>
                                                <input v-model="studentForm.height" type="number" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Berat (kg)</label>
                                                <input v-model="studentForm.weight" type="number" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Gol. Darah</label>
                                                <input v-model="studentForm.blood_type" type="text" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Riwayat Kesehatan / Penyakit</label>
                                            <textarea v-model="studentForm.health_history" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                                            <textarea v-model="studentForm.remarks" rows="2" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                                        </div>
                                    </div>

                                    <!-- TAB: CUSTOM -->
                                    <div v-show="activeTabStudent === 'custom'" class="space-y-4 p-1">
                                        <!-- DEBUG: Show raw custom values -->
                                        <!-- <pre class="text-[10px] bg-gray-100 p-2">{{ studentForm.custom_values }}</pre> -->
                                        
                                        <div v-if="!customFieldsDef || customFieldsDef.length === 0" class="text-center py-8 text-slate-400 bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                            <i class="fas fa-sliders-h text-2xl mb-2"></i>
                                            <p class="text-xs">Belum ada field custom.</p>
                                            <a href="references.php" class="text-blue-600 font-bold text-xs hover:underline mt-1 block">Atur di Pengaturan</a>
                                        </div>
                                        <template v-for="field in customFieldsDef" :key="field.id">
                                            <div v-if="field.is_active == 1">
                                                <label class="block text-xs font-bold text-slate-700 mb-1">{{ field.field_label }}</label>
                                                
                                                <!-- TEXT -->
                                                <input v-if="field.field_type === 'TEXT'" v-model="studentForm.custom_values[field.field_key]" type="text" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                                
                                                <!-- TEXTAREA -->
                                                <textarea v-if="field.field_type === 'TEXTAREA'" v-model="studentForm.custom_values[field.field_key]" class="w-full border border-slate-300 rounded px-3 py-2 text-sm"></textarea>
                                                
                                                <!-- DROPDOWN -->
                                                <select v-if="field.field_type === 'DROPDOWN'" v-model="studentForm.custom_values[field.field_key]" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                                    <option value="">-- Pilih --</option>
                                                    <option v-for="opt in field.field_options" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                
                                                <!-- BOOLEAN -->
                                                <select v-if="field.field_type === 'BOOLEAN'" v-model="studentForm.custom_values[field.field_key]" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                                                    <option value="">-- Pilih --</option>
                                                    <option value="1">Ya</option>
                                                    <option value="0">Tidak</option>
                                                </select>
                                                
                                                <!-- DATE -->
                                                <input v-if="field.field_type === 'DATE'" v-model="studentForm.custom_values[field.field_key]" type="date" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                                            </div>
                                        </template>
                                    </div>
                                </div></div>

                                <div class="flex justify-end gap-3 pt-6 mt-4 border-t border-slate-100">
                                    <button type="button" @click="showStudentModal = false" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50">Batal</button>
                                    <button v-if="currentPosition !== 'wali'" type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-bold">Simpan Data Siswa</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>