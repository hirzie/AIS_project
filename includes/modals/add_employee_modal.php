<!-- Add Employee Modal -->
<div v-if="showAddModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-slate-800">{{ isEditMode ? 'Edit Data Pegawai' : 'Tambah Data Pegawai' }}</h3>
            <button @click="closeAddModal" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form @submit.prevent="submitEmployee">
                <!-- Section 1: Identitas Utama -->
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b border-indigo-100 pb-2">Identitas Utama</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">NIP <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.employee_number" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.name" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Panggilan</label>
                            <input type="text" v-model="form.nickname" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Data Kepegawaian -->
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b border-indigo-100 pb-2">Data Kepegawaian</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">NUPTK</label>
                            <input type="text" v-model="form.nuptk" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">NRG</label>
                            <input type="text" v-model="form.nrg" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tgl Mulai Kerja</label>
                            <input type="date" v-model="form.join_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Bagian</label>
                            <select v-model="form.department" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="Akademik">Akademik</option>
                                <option value="Non Akademik">Non Akademik</option>
                                <option value="Manajemen">Manajemen</option>
                            </select>
                        </div>
                        <div>
                            <!-- Divisi dihilangkan dari bagian ini, kelola via 'Atribut Kustom (HR)' -->
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan Struktural</label>
                            <select v-model="form.position_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="">- Pilih Jabatan -</option>
                                <option v-for="pos in availablePositions" :key="pos.id" :value="pos.id">{{ pos.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Kepegawaian</label>
                            <select v-model="form.employment_status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option v-if="employmentStatuses.length === 0" value="TETAP">Tetap (Default)</option>
                                <option v-for="status in employmentStatuses" :key="status.id" :value="status.name">{{ status.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Aktif</label>
                            <select v-model="form.status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="ACTIVE">Aktif</option>
                                <option value="INACTIVE">Tidak Aktif</option>
                                <option value="CUTI">Cuti</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Unit Access Checkboxes -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <label class="block text-xs font-bold text-slate-700 mb-2">Akses Unit Sekolah (Pilih Minimal Satu)</label>
                        <div class="flex flex-wrap gap-4">
                            <label v-for="unit in availableUnits" :key="unit.id" class="inline-flex items-center">
                                <input type="checkbox" v-model="form.unit_access" :value="unit.id" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-slate-600">{{ (unit.prefix || unit.name).substring(0, 7) }}</span>
                            </label>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">* Pegawai/Guru hanya akan muncul di jadwal pelajaran unit yang dipilih. Pilih 'Yayasan' untuk staf pusat.</p>
                    </div>
                </div>

                <div class="mb-6" v-if="isEditMode">
                    <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b border-indigo-100 pb-2">Atribut Kustom (HR)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Divisi</label>
                            <select v-model="form.division" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="">- Pilih Divisi -</option>
                                <option value="SECURITY">Security</option>
                                <option value="CLEANING">Kebersihan</option>
                                <option value="FINANCE">Keuangan</option>
                                <option value="EXECUTIVE">Executive</option>
                                <option value="FOUNDATION">Yayasan</option>
                                <option value="ACADEMIC">Akademik</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor HP</label>
                            <input type="text" v-model="form.mobile_phone" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_bk_team" class="rounded border-slate-300 text-indigo-600">
                            <label class="text-xs font-bold text-slate-700">Tim BK</label>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" @click="saveCustomAttributes" class="px-6 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700">
                            Simpan Atribut Kustom
                        </button>
                    </div>
                </div>

                <!-- Section 3: Data Pribadi -->
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b border-indigo-100 pb-2">Data Pribadi</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                            <select v-model="form.gender" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tempat Lahir</label>
                            <input type="text" v-model="form.birth_place" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Lahir</label>
                            <input type="date" v-model="form.birth_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status Pernikahan</label>
                            <select v-model="form.marital_status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="Belum">Belum Menikah</option>
                                <option value="Nikah">Menikah</option>
                                <option value="Janda/Duda">Janda/Duda</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Agama</label>
                            <select v-model="form.religion" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                                <option value="Islam">Islam</option>
                                <option value="Kristen">Kristen</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Konghucu">Konghucu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Suku</label>
                            <input type="text" v-model="form.ethnicity" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">No. Identitas (KTP/SIM)</label>
                            <input type="text" v-model="form.identity_number" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
                        <textarea v-model="form.address" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500"></textarea>
                    </div>
                </div>

                <!-- Section 4: Kontak & Media Sosial -->
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b border-indigo-100 pb-2">Kontak & Media Sosial</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Telepon Rumah</label>
                            <input type="text" v-model="form.phone" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Handphone</label>
                            <input type="text" v-model="form.mobile_phone" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                            <input type="email" v-model="form.email" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Facebook</label>
                            <input type="text" v-model="form.facebook" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Twitter</label>
                            <input type="text" v-model="form.twitter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Website</label>
                            <input type="text" v-model="form.website" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Section 5: Keterangan -->
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan Tambahan</label>
                    <textarea v-model="form.notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="closeAddModal" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Batal</button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Simpan Pegawai</button>
                </div>
            </form>
        </div>
    </div>
</div>
