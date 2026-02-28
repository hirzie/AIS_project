# SIS MODULAR MONOLITH - TECHNICAL ARCHITECTURE DOCUMENT
**Project Name:** SekolahOS (SIS Modular)
**Version:** 1.4.0 (Added Product Development Protocol)
**Last Updated:** 2026-01-04

## 1. CORE PHILOSOPHY
Sistem ini dibangun dengan prinsip **Modular Monolith**.
- **Core System (Kernel):** Wajib ada. Menangani Auth, User, dan Data Induk Manusia.
- **Modules (Plugin):** Opsional. Menangani fitur spesifik (Akademik, Keuangan, Perpus).
- **Loose Coupling:** Modul tidak boleh saling ketergantungan secara keras (Hard Dependency). Gunakan *Event Bus* atau *Interface* untuk komunikasi.
- **Customization Friendly:** Mendukung *Custom Fields* (JSON) dan *Custom Pages* tanpa mengubah *Source Code* inti.

## 2. DATABASE ARCHITECTURE
Database menggunakan **Single Database** dengan pemisahan secara logikal menggunakan **Table Prefix**.

### A. Core Schema (`core_`) - *Immutable*
Tabel-tabel ini adalah pondasi. Tidak boleh diubah strukturnya sembarangan.
- `core_users`: Autentikasi & Login.
- `core_roles`: RBAC (Permissions).
- `core_people`: Single Source of Truth untuk semua manusia (Siswa/Guru/Staff).
- `core_modules`: Registry modul aktif/nonaktif.
- `core_settings`: Konfigurasi key-value storage.
- `core_devices`: Registry perangkat IoT (RFID, TV, Gate).

### B. Module Schemas - *Pluggable*
- **Akademik (`acad_`)**: Kelas, Mapel, Jadwal, Nilai.
- **Keuangan (`fin_`)**: Tagihan, Transaksi, Pos Bayar.
- **Perpustakaan (`lib_`)**: Buku, Peminjaman, Denda.
- **POS (`pos_`)**: Produk, Stok, Kasir.

### C. Data Integrity Rules
1. **Foreign Keys:** Modul boleh me-refer ke ID di `core_people`, tapi Core TIDAK BOLEH me-refer ke tabel modul.
2. **Soft Deletes:** Data master (Siswa) tidak boleh dihapus fisik, hanya di-flag `status = 'INACTIVE'`.
3. **JSON Columns:** Gunakan kolom `custom_attributes` di `core_people` untuk data dinamis sekolah (Golongan darah, Hobi, dll).

## 3. APPLICATION STRUCTURE (Folder)
```
/app
  /Core           <-- JANGAN DISENTUH (Kernel)
  /Modules
    /Academic     <-- Folder Modul Akademik
    /Finance      <-- Folder Modul Keuangan
    /Library      <-- Folder Modul Perpus
    /IoTHub       <-- Folder Modul Komunikasi Hardware
  /Custom         <-- Folder Khusus Kustomisasi Sekolah
    /sekolah_a
    /sekolah_b
```

## 4. DEPLOYMENT STRATEGY
- **Soft-Modular:** Semua kode modul didistribusikan dalam satu paket installer.
- **Activation:** Fitur diaktifkan via database `core_modules.is_active = 1`.
- **Installer:** Menggunakan "Preset Wizard" (Negeri, Swasta, Pesantren) untuk konfigurasi awal otomatis.

## 5. UI/UX GUIDELINES
- **Dynamic Menu:** Menu sidebar di-render berdasarkan `core_modules` yang aktif.
- **Hidden Config:** Fitur advanced disembunyikan dari user biasa.
- **Consistent UI:** Semua modul harus menggunakan komponen UI yang sama (Button, Table, Form) dari `SharedUI`.

## 6. IOT INTEGRATION STRATEGY
Konsep: **Dumb Device, Smart Server**.
- Perangkat IoT (RFID/TV) hanya berfungsi sebagai Input/Output.
- Logika bisnis tetap ada di Server (Module).
- **Security:** Setiap alat memiliki `device_token` unik.
- **Event Driven:**
  - RFID Reader -> Kirim API `POST /api/iot/scan {token, card_id}`.
  - Server -> Cek Modul aktif (POS/Attendance/Gate) -> Proses Logika -> Kirim Respon Balik.

## 7. MOBILE APP STRATEGY
Konsep: **One Codebase, Server-Driven UI**.
- **Single App:** Satu aplikasi di PlayStore ("SekolahOS Connect") untuk semua sekolah.
- **Dynamic UI:** Aplikasi merender menu berdasarkan respon API `/api/my-modules`.
  - Jika modul Keuangan aktif -> Munculkan Tab Tagihan.
  - Jika modul Keuangan mati -> Sembunyikan Tab Tagihan.
- **White Labeling (Premium):** Untuk sekolah premium, codebase yang sama di-build ulang dengan ganti Logo & Nama App, lalu upload terpisah.
- **Tech Stack:** Flutter / React Native (Cross Platform).

## 8. OFFLINE-FIRST STRATEGY (Hybrid)
Konsep: **PWA + Local Sync**.
Untuk mengatasi koneksi internet tidak stabil di sekolah:
- **Critical Modules Only:** POS (Kantin), Attendance (Absensi), Library Loan.
- **Technology:**
  - Frontend menggunakan **Service Workers** untuk caching aset (HTML/CSS/JS).
  - Data transaksional disimpan sementara di **IndexedDB** (Browser Database).
- **Sync Mechanism:**
  - Saat Online: Tarik data master (Saldo Siswa, Daftar Buku) ke lokal.
  - Saat Offline: Transaksi berjalan normal menggunakan data lokal.
  - Saat Reconnect: **Background Sync** mengirim antrian transaksi ke Cloud Server.
- **Conflict Resolution:** "Server Wins" strategy jika ada perbedaan data saldo.

## 9. DEVELOPMENT PROTOCOL: PRODUCT VS PROJECT
**PENTING:** Setiap fitur baru dari permintaan sekolah riset (Pilot Project) harus melewati validasi ini:

### The Acid Test (Uji Asam)
1. **Generalitas:** Apakah 100 sekolah lain membutuhkan fitur ini?
   - YES -> Masukkan ke Core/Module Codebase.
   - NO -> Masukkan ke folder `/Custom` atau gunakan `Settings`.
2. **Database:** Apakah permintaan ini membutuhkan kolom baru di tabel Master?
   - YES -> **DILARANG** mengubah tabel fisik. Gunakan kolom `custom_attributes` (JSON).
3. **Logika:** Apakah ini mengubah alur standar (misal: Rapor)?
   - YES -> Buat `Strategy Pattern` atau `Driver`. Jangan ubah logika default.
   
### Aturan AI Assistant
- Jika user meminta fitur spesifik, AI WAJIB mengingatkan: *"Apakah ini fitur umum atau khusus?"*
- AI harus menolak hardcoding ID Sekolah atau Logika Spesifik di folder `/Modules`.
- AI harus menyarankan penggunaan Config/JSON untuk variasi fitur.

---
*Dokumen ini adalah acuan utama pengembangan. Segala perubahan arsitektur harus dicatat di sini.*
