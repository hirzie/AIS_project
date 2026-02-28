<?php
require_once '../../config/database.php';
require_once '../../includes/header_inventory.php';
?>

<div class="flex flex-col h-screen overflow-hidden">
    <nav class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative">
        <div class="flex items-center gap-4">
            <a href="#" onclick="window.location.href=(window.BASE_URL||(window.location.pathname.includes('/AIS/')?'/AIS/':'/'))+'modules/inventory/dashboard.php'; return false;" class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-car text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 leading-none">Kendaraan Dinas</h1>
                    <span class="text-xs text-slate-500 font-medium">Manajemen Armada Sekolah</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <button class="text-slate-500 hover:text-slate-700 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-wrench"></i> Jadwal Service
            </button>
            <div class="h-6 w-px bg-slate-300"></div>
            <span class="text-sm font-medium text-slate-500">{{ currentDate }}</span>
        </div>
    </nav>

    <main class="flex-1 overflow-hidden p-4 bg-slate-100 flex flex-col">
        <div class="bg-white p-4 rounded-t-xl border-b border-slate-100 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" v-model="searchQuery" placeholder="Cari kendaraan..." class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 w-64">
                </div>
            </div>
            <button @click="openModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm shadow-emerald-200">
                <i class="fas fa-plus"></i> Tambah Kendaraan
            </button>
        </div>

        <div class="bg-white flex-1 rounded-b-xl shadow-sm overflow-hidden flex flex-col">
            <div class="overflow-x-auto flex-1 custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 w-16">No</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Plat Nomor</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Kendaraan</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Tahun</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Warna</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Status Service</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Pajak STNK</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in filteredItems" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-sm text-slate-500">{{ index + 1 }}</td>
                            <td class="p-4 text-sm font-mono font-bold text-slate-800 bg-slate-50 rounded w-32 text-center border border-slate-200">{{ item.license_plate }}</td>
                            <td class="p-4 text-sm font-medium text-slate-800">{{ item.name }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ item.year }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ item.color }}</td>
                            <td class="p-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden w-24">
                                        <div class="h-full bg-emerald-500" :style="{ width: item.service_health + '%' }"></div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ item.service_health }}%</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">Last: {{ item.last_service }}</div>
                            </td>
                            <td class="p-4 text-sm">
                                <span :class="isTaxExpired(item.tax_expiry_date) ? 'text-red-600 font-bold' : 'text-slate-600'">
                                    {{ item.tax_expiry_date || '-' }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <button @click="openDetail(item)" class="text-slate-400 hover:text-emerald-600 mx-1" title="Detail & Dokumen"><i class="fas fa-eye"></i></button>
                                <button @click="openModal(item)" class="text-slate-400 hover:text-blue-600 mx-1" title="Edit"><i class="fas fa-edit"></i></button>
                                <button @click="deleteItem(item)" class="text-slate-400 hover:text-red-600 mx-1" title="Hapus"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="filteredItems.length === 0">
                            <td colspan="8" class="p-8 text-center text-slate-500 italic">Data tidak ditemukan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" v-cloak>
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="font-bold text-lg text-slate-800">{{ detailItem.name }}</h3>
                    <div class="text-sm text-slate-500 font-mono">{{ detailItem.license_plate }}</div>
                </div>
                <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="flex border-b border-slate-200">
                <button @click="activeTab = 'info'" :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'info' ? 'text-emerald-600 border-b-2 border-emerald-600 bg-emerald-50' : 'text-slate-500 hover:bg-slate-50']">
                    <i class="fas fa-info-circle mr-2"></i> Info & Dokumen
                </button>
                <button @click="activeTab = 'service'" :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'service' ? 'text-emerald-600 border-b-2 border-emerald-600 bg-emerald-50' : 'text-slate-500 hover:bg-slate-50']">
                    <i class="fas fa-wrench mr-2"></i> Riwayat Service
                </button>
                <button @click="activeTab = 'lending'" :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'lending' ? 'text-emerald-600 border-b-2 border-emerald-600 bg-emerald-50' : 'text-slate-500 hover:bg-slate-50']">
                    <i class="fas fa-key mr-2"></i> Peminjaman
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 bg-slate-50">
                <!-- Tab Info & Dokumen -->
                <div v-if="activeTab === 'info'" class="space-y-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                        <h4 class="font-bold text-slate-700 mb-3 border-b pb-2">Informasi Kendaraan</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div><div class="text-xs text-slate-500">Tipe</div><div class="font-medium">{{ detailItem.type }}</div></div>
                            <div><div class="text-xs text-slate-500">Tahun</div><div class="font-medium">{{ detailItem.year }}</div></div>
                            <div><div class="text-xs text-slate-500">Warna</div><div class="font-medium">{{ detailItem.color }}</div></div>
                            <div><div class="text-xs text-slate-500">Pajak STNK</div><div class="font-medium" :class="isTaxExpired(detailItem.tax_expiry_date) ? 'text-red-600' : ''">{{ detailItem.tax_expiry_date || '-' }}</div></div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                        <div class="flex justify-between items-center mb-3 border-b pb-2">
                            <h4 class="font-bold text-slate-700">Dokumen (STNK, BPKB, dll)</h4>
                            <button @click="showUploadForm = !showUploadForm" class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded hover:bg-blue-100">
                                <i class="fas fa-plus"></i> Upload
                            </button>
                        </div>

                        <!-- Upload Form -->
                        <div v-if="showUploadForm" class="mb-4 bg-blue-50 p-3 rounded border border-blue-100 text-sm">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                                <select v-model="newDoc.type" class="border rounded px-2 py-1">
                                    <option value="STNK">STNK</option>
                                    <option value="BPKB">BPKB</option>
                                    <option value="INSURANCE">Asuransi</option>
                                    <option value="OTHER">Lainnya</option>
                                </select>
                                <input type="date" v-model="newDoc.expiry_date" class="border rounded px-2 py-1" placeholder="Masa Berlaku">
                                <input type="text" v-model="newDoc.notes" class="border rounded px-2 py-1" placeholder="Catatan">
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="file" ref="fileInput" class="text-xs">
                                <button @click="uploadDocument" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">Upload</button>
                            </div>
                        </div>

                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="p-2">Tipe</th>
                                    <th class="p-2">File</th>
                                    <th class="p-2">Exp</th>
                                    <th class="p-2">Catatan</th>
                                    <th class="p-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="doc in documents" :key="doc.id">
                                    <td class="p-2 font-medium">{{ doc.type }}</td>
                                    <td class="p-2"><a :href="doc.url" target="_blank" class="text-blue-600 hover:underline"><i class="fas fa-file-alt mr-1"></i> Lihat</a></td>
                                    <td class="p-2">{{ doc.expiry_date || '-' }}</td>
                                    <td class="p-2 text-slate-500">{{ doc.notes }}</td>
                                    <td class="p-2 text-right">
                                        <button @click="deleteDocument(doc)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr v-if="documents.length === 0"><td colspan="5" class="p-4 text-center text-slate-400 italic">Belum ada dokumen</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Riwayat Service -->
                <div v-if="activeTab === 'service'" class="space-y-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                        <div class="flex justify-between items-center mb-3 border-b pb-2">
                            <h4 class="font-bold text-slate-700">Catat Service Baru</h4>
                            <button @click="showServiceForm = !showServiceForm" class="text-xs bg-emerald-50 text-emerald-600 px-2 py-1 rounded hover:bg-emerald-100">
                                <i :class="showServiceForm ? 'fas fa-minus' : 'fas fa-plus'"></i> Toggle Form
                            </button>
                        </div>
                        
                        <div v-if="showServiceForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4 bg-emerald-50 p-4 rounded border border-emerald-100">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal</label>
                                <input type="date" v-model="newService.service_date" class="w-full border rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Bengkel</label>
                                <input type="text" v-model="newService.workshop_name" class="w-full border rounded px-2 py-1">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Deskripsi Pengerjaan</label>
                                <textarea v-model="newService.description" class="w-full border rounded px-2 py-1 h-16"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Biaya (Rp)</label>
                                <input type="number" v-model="newService.cost" class="w-full border rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Odometer (KM)</label>
                                <input type="number" v-model="newService.odometer_reading" class="w-full border rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Next Service</label>
                                <input type="date" v-model="newService.next_service_date" class="w-full border rounded px-2 py-1">
                            </div>
                            <div class="flex items-end">
                                <button @click="saveService" class="bg-emerald-600 text-white px-4 py-2 rounded text-sm hover:bg-emerald-700 w-full">Simpan Service</button>
                            </div>
                        </div>

                        <h4 class="font-bold text-slate-700 mb-3">Riwayat Service</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                    <tr>
                                        <th class="p-2">Tanggal</th>
                                        <th class="p-2">Bengkel</th>
                                        <th class="p-2">Deskripsi</th>
                                        <th class="p-2">Biaya</th>
                                        <th class="p-2">KM</th>
                                        <th class="p-2">Next Due</th>
                                        <th class="p-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="svc in services" :key="svc.id">
                                        <td class="p-2 whitespace-nowrap">{{ svc.service_date }}</td>
                                        <td class="p-2 font-medium">{{ svc.workshop_name }}</td>
                                        <td class="p-2 text-slate-600 max-w-xs truncate" :title="svc.description">{{ svc.description }}</td>
                                        <td class="p-2 font-mono">Rp {{ parseInt(svc.cost).toLocaleString('id-ID') }}</td>
                                        <td class="p-2">{{ svc.odometer_reading }}</td>
                                        <td class="p-2">{{ svc.next_service_date || '-' }}</td>
                                        <td class="p-2 text-right">
                                            <button @click="deleteService(svc)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr v-if="services.length === 0"><td colspan="7" class="p-4 text-center text-slate-400 italic">Belum ada riwayat service</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Peminjaman -->
                <div v-if="activeTab === 'lending'" class="space-y-6">
                     <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                        <div class="flex justify-between items-center mb-3 border-b pb-2">
                            <h4 class="font-bold text-slate-700">Peminjaman Baru</h4>
                            <button @click="showLendingForm = !showLendingForm" class="text-xs bg-purple-50 text-purple-600 px-2 py-1 rounded hover:bg-purple-100">
                                <i :class="showLendingForm ? 'fas fa-minus' : 'fas fa-plus'"></i> Toggle Form
                            </button>
                        </div>

                         <div v-if="showLendingForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4 bg-purple-50 p-4 rounded border border-purple-100">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Peminjam</label>
                                <input type="text" v-model="newLending.borrower_name" class="w-full border rounded px-2 py-1" placeholder="Nama Peminjam">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tgl Pinjam</label>
                                <input type="datetime-local" v-model="newLending.borrow_date" class="w-full border rounded px-2 py-1">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Keperluan</label>
                                <input type="text" v-model="newLending.purpose" class="w-full border rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Rencana Kembali</label>
                                <input type="datetime-local" v-model="newLending.return_date_planned" class="w-full border rounded px-2 py-1">
                            </div>
                            <div class="flex items-end">
                                <button @click="saveLending" class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700 w-full">Catat Peminjaman</button>
                            </div>
                        </div>

                        <h4 class="font-bold text-slate-700 mb-3">Riwayat Peminjaman</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                    <tr>
                                        <th class="p-2">Status</th>
                                        <th class="p-2">Peminjam</th>
                                        <th class="p-2">Tgl Pinjam</th>
                                        <th class="p-2">Rencana Kembali</th>
                                        <th class="p-2">Aktual Kembali</th>
                                        <th class="p-2">Keperluan</th>
                                        <th class="p-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="loan in lending" :key="loan.id">
                                        <td class="p-2">
                                            <span :class="['px-2 py-1 rounded text-xs font-bold', loan.status === 'BORROWED' ? 'bg-orange-100 text-orange-600' : 'bg-green-100 text-green-600']">
                                                {{ loan.status === 'BORROWED' ? 'DIPINJAM' : 'KEMBALI' }}
                                            </span>
                                        </td>
                                        <td class="p-2 font-medium">{{ loan.borrower_name }}</td>
                                        <td class="p-2 text-xs">{{ formatDate(loan.borrow_date) }}</td>
                                        <td class="p-2 text-xs">{{ formatDate(loan.return_date_planned) }}</td>
                                        <td class="p-2 text-xs">{{ formatDate(loan.return_date_actual) || '-' }}</td>
                                        <td class="p-2 text-slate-600 max-w-xs truncate">{{ loan.purpose }}</td>
                                        <td class="p-2 text-right">
                                            <button v-if="loan.status === 'BORROWED'" @click="returnVehicle(loan)" class="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600">Kembalikan</button>
                                        </td>
                                    </tr>
                                    <tr v-if="lending.length === 0"><td colspan="7" class="p-4 text-center text-slate-400 italic">Belum ada riwayat peminjaman</td></tr>
                                </tbody>
                            </table>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800">{{ form.id ? 'Edit Kendaraan' : 'Tambah Kendaraan' }}</h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Kendaraan</label>
                    <input type="text" v-model="form.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" placeholder="Contoh: Toyota Hiace">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Plat Nomor</label>
                        <input type="text" v-model="form.license_plate" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" placeholder="B 1234 CD">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tipe</label>
                        <input type="text" v-model="form.type" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" placeholder="Contoh: Minibus">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tahun</label>
                        <input type="number" v-model="form.year" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" placeholder="YYYY">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Warna</label>
                        <input type="text" v-model="form.color" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" placeholder="Warna">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Service Terakhir</label>
                        <input type="date" v-model="form.last_service" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Pajak STNK</label>
                        <input type="date" v-model="form.tax_expiry_date" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="closeModal" class="px-4 py-2 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200">Batal</button>
                <button @click="saveItem" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-200">Simpan</button>
            </div>
        </div>
    </div>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                searchQuery: '',
                items: [],
                showModal: false,
                // Detail Modal Data
                showDetailModal: false,
                activeTab: 'info',
                detailItem: {},
                documents: [],
                services: [],
                lending: [],
                
                // Forms
                showUploadForm: false,
                newDoc: { type: 'STNK', expiry_date: '', notes: '' },
                
                showServiceForm: false,
                newService: { service_date: '', workshop_name: '', description: '', cost: '', odometer_reading: '', next_service_date: '' },
                
                showLendingForm: false,
                newLending: { borrower_name: '', borrow_date: '', return_date_planned: '', purpose: '' },

                form: {
                    id: null,
                    name: '',
                    license_plate: '',
                    type: '',
                    year: '',
                    color: '',
                    last_service: '',
                    tax_expiry_date: ''
                }
            }
        },
        computed: {
            currentDate() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },
            filteredItems() {
                let res = this.items;
                if (this.searchQuery) {
                    const lower = this.searchQuery.toLowerCase();
                    res = res.filter(item => 
                        (item.name || '').toLowerCase().includes(lower) || 
                        (item.license_plate || '').toLowerCase().includes(lower)
                    );
                }
                return res;
            }
        },
        methods: {
            async fetchData() {
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=get_vehicles');
                const data = await res.json();
                if (data.success) this.items = data.data;
            },
            formatDate(str) {
                if (!str) return '';
                return new Date(str).toLocaleString('id-ID');
            },
            isTaxExpired(dateStr) {
                if (!dateStr || dateStr === '-') return false;
                const d = new Date(dateStr);
                const today = new Date();
                return d < today;
            },
            
            // Detail Logic
            async openDetail(item) {
                this.detailItem = item;
                this.activeTab = 'info';
                this.showDetailModal = true;
                await Promise.all([
                    this.fetchDocuments(item.id),
                    this.fetchServices(item.id),
                    this.fetchLending(item.id)
                ]);
            },
            closeDetailModal() {
                this.showDetailModal = false;
                this.fetchData(); // Refresh main list to update statuses
            },
            
            // Documents
            async fetchDocuments(id) {
                const res = await fetch(window.BASE_URL + `api/inventory.php?action=get_vehicle_documents&vehicle_id=${id}`);
                const data = await res.json();
                if (data.success) this.documents = data.data;
            },
            async uploadDocument() {
                const file = this.$refs.fileInput.files[0];
                if (!file) return alert('Pilih file!');
                
                const fd = new FormData();
                fd.append('action', 'upload_vehicle_document');
                fd.append('vehicle_id', this.detailItem.id);
                fd.append('type', this.newDoc.type);
                fd.append('expiry_date', this.newDoc.expiry_date);
                fd.append('notes', this.newDoc.notes);
                fd.append('file', file);
                
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchDocuments(this.detailItem.id);
                        this.showUploadForm = false;
                        this.newDoc = { type: 'STNK', expiry_date: '', notes: '' };
                        this.$refs.fileInput.value = '';
                    } else alert(data.message);
                } catch(e) { console.error(e); }
            },
            async deleteDocument(doc) {
                if (!confirm('Hapus dokumen?')) return;
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_vehicle_document', {
                    method: 'POST',
                    body: JSON.stringify({ id: doc.id })
                });
                const data = await res.json();
                if (data.success) this.fetchDocuments(this.detailItem.id);
            },
            
            // Services
            async fetchServices(id) {
                const res = await fetch(window.BASE_URL + `api/inventory.php?action=get_vehicle_services&vehicle_id=${id}`);
                const data = await res.json();
                if (data.success) this.services = data.data;
            },
            async saveService() {
                const payload = { ...this.newService, vehicle_id: this.detailItem.id };
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_vehicle_service', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchServices(this.detailItem.id);
                    this.showServiceForm = false;
                    this.newService = { service_date: '', workshop_name: '', description: '', cost: '', odometer_reading: '', next_service_date: '' };
                } else alert(data.message);
            },
            async deleteService(svc) {
                if (!confirm('Hapus riwayat service?')) return;
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_vehicle_service', {
                    method: 'POST',
                    body: JSON.stringify({ id: svc.id })
                });
                const data = await res.json();
                if (data.success) this.fetchServices(this.detailItem.id);
            },
            
            // Lending
            async fetchLending(id) {
                const res = await fetch(window.BASE_URL + `api/inventory.php?action=get_vehicle_lending&vehicle_id=${id}`);
                const data = await res.json();
                if (data.success) this.lending = data.data;
            },
            async saveLending() {
                const payload = { ...this.newLending, vehicle_id: this.detailItem.id };
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_vehicle_lending', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.fetchLending(this.detailItem.id);
                    this.showLendingForm = false;
                    this.newLending = { borrower_name: '', borrow_date: '', return_date_planned: '', purpose: '' };
                } else alert(data.message);
            },
            async returnVehicle(loan) {
                const note = prompt('Catatan Pengembalian (kondisi, bbm, dll):');
                if (note === null) return;
                const res = await fetch(window.BASE_URL + 'api/inventory.php?action=return_vehicle', {
                    method: 'POST',
                    body: JSON.stringify({ id: loan.id, notes: note })
                });
                const data = await res.json();
                if (data.success) this.fetchLending(this.detailItem.id);
            },

            openModal(item = null) {
                if (item) {
                    this.form = { ...item };
                } else {
                    this.form = {
                        id: null,
                        name: '',
                        license_plate: '',
                        type: '',
                        year: '',
                        color: '',
                        last_service: new Date().toISOString().slice(0, 10),
                        tax_expiry_date: ''
                    };
                }
                this.showModal = true;
            },
            closeModal() {
                this.showModal = false;
            },
            async saveItem() {
                if (!this.form.name || !this.form.license_plate) return alert('Nama dan Plat Nomor wajib diisi!');
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=save_vehicle', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(data.message);
                        this.closeModal();
                        this.fetchData();
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            async deleteItem(item) {
                if (!confirm(`Hapus kendaraan ${item.name}?`)) return;
                try {
                    const res = await fetch(window.BASE_URL + 'api/inventory.php?action=delete_vehicle', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: item.id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fetchData();
                    } else {
                        alert(data.message);
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        },
        mounted() {
            this.fetchData();
        }
    }).mount('#app')
</script>
</body>
</html>
