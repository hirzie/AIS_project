# Dokumentasi Sistem Database: Backup, Restore & Migrasi

Dokumen ini menjelaskan cara kerja dan penggunaan fitur manajemen database di aplikasi AIS. Fitur ini terletak di menu **Admin > System Database** (Backup & Restore).

## 1. Backup Database

Fitur ini digunakan untuk membuat salinan (snapshot) database saat ini ke dalam file `.sql`.

### Cara Menggunakan
1. Buka menu **System Database** -> tab **Backup & Restore**.
2. Pada panel "Buat Backup", isi catatan opsional (misal: "Sebelum update fitur raport").
3. Klik tombol **Buat Backup**.
4. File backup akan muncul di daftar "Riwayat Backup".

### Teknis
- **Lokasi File:** `backups/` (di root project).
- **Format:** SQL Dump standar (kompatibel dengan phpMyAdmin/MySQL Workbench).
- **Metadata:** Informasi tambahan (catatan, user pembuat) disimpan di `backups/backup_meta.json`.

---

## 2. Restore Database

Fitur ini digunakan untuk mengembalikan kondisi database ke titik tertentu dari file backup.

**PERINGATAN:** Proses restore bersifat destruktif. Data database saat ini akan dihapus dan digantikan sepenuhnya dengan data dari file backup.

### Cara Menggunakan
1. Pilih file dari daftar "Riwayat Backup" atau upload file `.sql` baru melalui panel "Upload Backup".
2. Klik tombol **Restore** pada file yang diinginkan.
3. Konfirmasi peringatan yang muncul.
4. Jendela **Restore Console** akan terbuka menampilkan log proses secara real-time.
5. Tunggu hingga muncul status `STATUS: SUKSES`.

### Mekanisme & Penanganan Error (Anti-Timeout)
Sistem menggunakan strategi bertingkat untuk menangani database besar dan mencegah timeout server:

1.  **Strategi 1: Native MySQL Client (Prioritas Utama)**
    *   Menggunakan perintah `mysql` bawaan sistem operasi (CLI) via `proc_open` / `system` / `exec`.
    *   **Keunggulan:** Sangat cepat, hemat memori, tidak terpengaruh `max_execution_time` PHP.
    *   **Auto-Detect:** Otomatis mendeteksi path MySQL di Windows (XAMPP: `C:/xampp/mysql/bin/mysql.exe`) dan Linux (`/usr/bin/mysql`, dll).

2.  **Strategi 2: PHP Stream Loop (Fallback)**
    *   Jika Native Client gagal/diblokir hosting, sistem beralih ke pembacaan file baris-per-baris menggunakan PHP.
    *   **Fitur Keep-Alive:** Mengirim output titik (`.`) ke browser setiap detik untuk mencegah koneksi diputus oleh Nginx/Browser karena idle.
    *   **Streaming Response:** Menggunakan header `X-Accel-Buffering: no` untuk memastikan log tampil real-time.

---

## 3. Database Migration (Versioning)

Fitur ini digunakan untuk mengelola perubahan struktur database (DDL) secara terstruktur, menggantikan cara manual (edit di phpMyAdmin) yang rawan konflik antar environment (Local vs Test vs Production).

### Workflow Pengembangan (Development)
1.  Buka menu **System Database** -> tab **Database Migrations**.
2.  Isi deskripsi perubahan (misal: `tambah_kolom_nik_guru`) dan klik **Buat File Migrasi**.
3.  Sistem akan membuat file baru di `backups/migrations/` dengan nama timestamp unik (contoh: `20240225_143000_tambah_kolom_nik_guru.sql`).
4.  Buka file tersebut di Code Editor dan tulis query SQL perubahan Anda.
    ```sql
    -- Contoh isi file migrasi
    ALTER TABLE hr_employees ADD COLUMN nik VARCHAR(20) DEFAULT NULL;
    CREATE INDEX idx_nik ON hr_employees(nik);
    ```
5.  Kembali ke halaman Migrasi, klik **Jalankan** untuk menerapkan ke database lokal Anda.

### Workflow Deployment (Test/Production)
1.  Upload file migrasi baru ke server (via Git/FTP) ke folder `backups/migrations/`.
2.  Buka menu **System Database** -> tab **Database Migrations** di server tujuan.
3.  Sistem akan mendeteksi migrasi baru dengan status **BELUM**.
4.  Klik **Jalankan** untuk menerapkan perubahan.

### Tabel Sistem
Status migrasi dicatat di tabel `sys_migrations` untuk memastikan satu file migrasi tidak dijalankan dua kali.

---

## 4. Troubleshooting Umum

*   **Error "MySQL command not found":**
    *   Pastikan path MySQL bin sudah benar atau terdaftar di Environment Variable sistem operasi.
    *   Di hosting Shared, fitur `exec`/`proc_open` mungkin dinonaktifkan. Sistem akan otomatis fallback ke metode PHP.

*   **Error "Network Error" saat Restore:**
    *   Biasanya terjadi jika proses PHP terhenti atau kehabisan memori di metode Fallback.
    *   Solusi: Coba gunakan file backup yang lebih kecil atau perbesar `memory_limit` di php.ini.

*   **Akses Ditolak:**
    *   Fitur ini hanya dapat diakses oleh role **SUPERADMIN** dan **ADMIN**.
