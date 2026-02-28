<?php
require_once '../../includes/guard.php';
require_login_and_module('library');
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="../../assets/js/vue.global.js"></script>
    <style>
        [v-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .animate-fade { animation: fade 0.3s ease-out; }
        @keyframes fade { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div id="app" v-cloak class="flex flex-col h-screen">

    <?php require_once '../../includes/library_header.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Katalog Pustaka</h2>
                    <p class="text-slate-500 text-sm">Manajemen koleksi buku, kategori, dan rak.</p>
                </div>
                <div class="flex bg-slate-100 rounded-lg p-1 border border-slate-200">
                    <button @click="switchView('books')" :class="view === 'books' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Buku</button>
                    <button @click="switchView('categories')" :class="view === 'categories' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Kategori</button>
                    <button @click="switchView('shelves')" :class="view === 'shelves' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Rak</button>
                    <button @click="switchView('print')" :class="view === 'print' ? 'bg-white shadow text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Cetak Label</button>
                </div>
            </div>
            <div v-if="view === 'books'" class="animate-fade">
                <!-- Inline Form for Books -->
                <div v-if="showBookForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">{{ bookForm.id ? 'Edit' : 'Tambah' }} Koleksi Buku</h3>
                        <button @click="showBookForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="saveBook" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Judul Buku</label>
                            <input v-model="bookForm.title" type="text" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Penulis</label>
                            <input v-model="bookForm.author" type="text" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Penerbit</label>
                            <input v-model="bookForm.publisher" type="text" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Barcode (ID Unik)</label>
                            <input v-model="bookForm.barcode" type="text" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">ISBN</label>
                            <input v-model="bookForm.isbn" type="text" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kategori</label>
                            <select v-model="bookForm.category_id" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option :value="null">-- Pilih Kategori --</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Rak</label>
                            <select v-model="bookForm.shelf_id" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option :value="null">-- Pilih Rak --</option>
                                <option v-for="s in shelves" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Stok</label>
                            <input v-model.number="bookForm.stock" type="number" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        </div>
                        <div class="md:col-span-4 flex justify-end gap-2 mt-4">
                            <button type="button" @click="showBookForm = false" class="px-6 py-2 border border-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                            <button type="submit" class="px-8 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Simpan Buku</button>
                        </div>
                    </form>
                </div>

                <div class="flex justify-end items-center mb-6">
                    <div class="flex gap-2">
                        <button @click="openBookForm()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all">
                            <i class="fas fa-plus mr-2"></i> Tambah Buku
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Info Buku</th>
                                <th class="px-6 py-4">Barcode / ISBN</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4">Rak</th>
                                <th class="px-6 py-4 text-center">Stok</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="b in books" :key="b.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700">{{ b.title }}</div>
                                    <div class="text-[10px] text-slate-400">Penulis: {{ b.author || '-' }} | Penerbit: {{ b.publisher || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs text-slate-600">{{ b.barcode }}</div>
                                    <div class="text-[10px] text-slate-400 italic">ISBN: {{ b.isbn || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="b.category_name" class="bg-emerald-50 text-emerald-700 px-2 py-1 rounded text-[10px] font-bold border border-emerald-100 uppercase">
                                        {{ b.category_name }}
                                    </span>
                                    <span v-else class="text-slate-300 italic text-[10px]">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="b.shelf_name" class="text-slate-600 text-xs font-medium">
                                        <i class="fas fa-archive mr-1 text-slate-300"></i> {{ b.shelf_name }}
                                    </div>
                                    <span v-else class="text-slate-300 italic text-[10px]">-</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="font-bold text-slate-700">{{ b.available_stock }} / {{ b.stock }}</div>
                                    <div class="text-[10px] text-slate-400">Tersedia</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openBookForm(b)" class="text-indigo-600 hover:text-indigo-800 mr-3 font-bold text-xs">Edit</button>
                                    <button @click="deleteBook(b)" class="text-red-400 hover:text-red-600 font-bold text-xs">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="books.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada koleksi buku.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: CATEGORIES -->
            <div v-if="view === 'categories'" class="animate-fade max-w-4xl mx-auto">
                <!-- Inline Form for Categories -->
                <div v-if="showCategoryForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">{{ categoryForm.id ? 'Edit' : 'Tambah' }} Kategori</h3>
                        <button @click="showCategoryForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="saveCategory" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Kategori</label>
                            <input v-model="categoryForm.name" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Keterangan</label>
                            <input v-model="categoryForm.description" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div class="md:col-span-2 flex justify-end gap-2">
                            <button type="button" @click="showCategoryForm = false" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600">Batal</button>
                            <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg font-bold text-sm">Simpan</button>
                        </div>
                    </form>
                </div>

                <div class="flex justify-end items-center mb-6">
                    <button @click="openCategoryForm()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Kategori
                    </button>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Nama Kategori</th>
                                <th class="px-6 py-4">Keterangan</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="c in categories" :key="c.id">
                                <td class="px-6 py-4 font-bold text-slate-700">{{ c.name }}</td>
                                <td class="px-6 py-4 text-slate-500 text-xs">{{ c.description || '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openCategoryForm(c)" class="text-indigo-600 hover:text-indigo-800 mr-3 font-bold text-xs">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: SHELVES -->
            <div v-if="view === 'shelves'" class="animate-fade max-w-4xl mx-auto">
                <!-- Inline Form for Shelves -->
                <div v-if="showShelfForm" class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 mb-8 animate-fade">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">{{ shelfForm.id ? 'Edit' : 'Tambah' }} Rak</h3>
                        <button @click="showShelfForm = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="saveShelf" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Rak</label>
                            <input v-model="shelfForm.name" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Lokasi</label>
                            <input v-model="shelfForm.location" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div class="md:col-span-2 flex justify-end gap-2">
                            <button type="button" @click="showShelfForm = false" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold text-slate-600">Batal</button>
                            <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg font-bold text-sm">Simpan</button>
                        </div>
                    </form>
                </div>

                <div class="flex justify-end items-center mb-6">
                    <button @click="openShelfForm()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Rak
                    </button>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Nama Rak</th>
                                <th class="px-6 py-4">Lokasi</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="s in shelves" :key="s.id">
                                <td class="px-6 py-4 font-bold text-slate-700">{{ s.name }}</td>
                                <td class="px-6 py-4 text-slate-500 text-xs">{{ s.location || '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openShelfForm(s)" class="text-indigo-600 hover:text-indigo-800 mr-3 font-bold text-xs">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: PRINT LABELS -->
            <div v-if="view === 'print'" class="animate-fade">
                <div class="flex justify-end items-center mb-6">
                    <div class="flex gap-2">
                        <button @click="selectedBooks = []" class="text-slate-500 px-4 py-2 text-sm font-bold">Bersihkan</button>
                        <button @click="doPrint" :disabled="selectedBooks.length === 0" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 shadow-lg shadow-emerald-100 disabled:bg-slate-200 transition-all">
                            <i class="fas fa-print mr-2"></i> Cetak {{ selectedBooks.length }} Label
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="b in books" :key="b.id" @click="toggleSelectBook(b)" 
                         class="bg-white p-4 rounded-xl border-2 cursor-pointer transition-all hover:border-emerald-200"
                         :class="isSelected(b) ? 'border-emerald-500 shadow-md' : 'border-slate-100'">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-bold text-slate-700 text-sm">{{ b.title }}</div>
                                <div class="text-[10px] text-slate-400">{{ b.barcode }}</div>
                            </div>
                            <div v-if="isSelected(b)" class="text-emerald-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- PRINT PREVIEW OVERLAY -->
        <div v-if="isPrinting" class="fixed inset-0 bg-white z-[100] p-8 overflow-y-auto">
            <div class="flex justify-between mb-8 print:hidden">
                <h2 class="text-xl font-bold">Preview Cetak Label</h2>
                <div class="flex gap-2">
                    <button @click="isPrinting = false" class="bg-slate-100 px-4 py-2 rounded-lg font-bold">Tutup</button>
                    <button onclick="window.print()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold">Cetak Sekarang</button>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div v-for="b in selectedBooks" :key="b.id" class="border border-slate-300 p-4 rounded text-center font-sans">
                    <div class="text-[10px] font-bold uppercase mb-1">Perpustakaan SekolahOS</div>
                    <div class="text-xs font-bold leading-tight mb-2 truncate">{{ b.title }}</div>
                    <div class="bg-slate-100 h-12 flex items-center justify-center mb-1 font-mono text-sm tracking-[5px] border border-dashed border-slate-300">
                        {{ b.barcode }}
                    </div>
                    <div class="text-[8px] text-slate-500">{{ b.author }}</div>
                    <div class="text-[8px] font-bold mt-1">{{ b.shelf_name || 'RAK: -' }}</div>
                </div>
            </div>
        </div>

    </main>

</div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                view: 'books',
                books: [],
                categories: [],
                shelves: [],
                showBookForm: false,
                showCategoryForm: false,
                showShelfForm: false,
                bookForm: { id: null, title: '', author: '', publisher: '', isbn: '', barcode: '', category_id: null, shelf_id: null, stock: 1 },
                categoryForm: { id: null, name: '', description: '' },
                shelfForm: { id: null, name: '', location: '' },
                selectedBooks: [],
                isPrinting: false
            }
        },
        methods: {
            switchView(v) {
                this.view = v;
                this.showBookForm = false;
                this.showCategoryForm = false;
                this.showShelfForm = false;
            },
            async fetchBooks() {
                const res = await fetch('../../api/library.php?action=get_books');
                const data = await res.json();
                if (data.success) this.books = data.data;
            },
            async fetchCategories() {
                const res = await fetch('../../api/library.php?action=get_categories');
                const data = await res.json();
                if (data.success) this.categories = data.data;
            },
            async fetchShelves() {
                const res = await fetch('../../api/library.php?action=get_shelves');
                const data = await res.json();
                if (data.success) this.shelves = data.data;
            },
            openBookForm(book = null) {
                if (book) {
                    this.bookForm = { ...book };
                } else {
                    this.bookForm = { id: null, title: '', author: '', publisher: '', isbn: '', barcode: '', category_id: null, shelf_id: null, stock: 1 };
                }
                this.showBookForm = true;
                this.$nextTick(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            async saveBook() {
                const res = await fetch('../../api/library.php?action=save_book', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.bookForm)
                });
                if ((await res.json()).success) {
                    this.showBookForm = false;
                    this.fetchBooks();
                }
            },
            async deleteBook(book) {
                if (!confirm(`Hapus buku "${book.title}"?`)) return;
                const res = await fetch('../../api/library.php?action=delete_book', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: book.id })
                });
                if ((await res.json()).success) this.fetchBooks();
            },
            openCategoryForm(cat = null) {
                if (cat) this.categoryForm = { ...cat };
                else this.categoryForm = { id: null, name: '', description: '' };
                this.showCategoryForm = true;
                this.$nextTick(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            async saveCategory() {
                const res = await fetch('../../api/library.php?action=save_category', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.categoryForm)
                });
                if ((await res.json()).success) {
                    this.showCategoryForm = false;
                    this.fetchCategories();
                }
            },
            openShelfForm(shelf = null) {
                if (shelf) this.shelfForm = { ...shelf };
                else this.shelfForm = { id: null, name: '', location: '' };
                this.showShelfForm = true;
                this.$nextTick(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            async saveShelf() {
                const res = await fetch('../../api/library.php?action=save_shelf', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.shelfForm)
                });
                if ((await res.json()).success) {
                    this.showShelfForm = false;
                    this.fetchShelves();
                }
            },
            isSelected(book) {
                return this.selectedBooks.some(b => b.id === book.id);
            },
            toggleSelectBook(book) {
                const index = this.selectedBooks.findIndex(b => b.id === book.id);
                if (index === -1) this.selectedBooks.push(book);
                else this.selectedBooks.splice(index, 1);
            },
            doPrint() {
                this.isPrinting = true;
            }
        },
        mounted() {
            this.fetchBooks();
            this.fetchCategories();
            this.fetchShelves();
        }
    }).mount('#app')
</script>
</body>
</html>
