# Panduan Migrasi Development: Mac (Utama) -> Windows (Backup) -> Server (Staging/Prod)

Karena Anda menggunakan **Laravel Herd**, **DBngin**, dan **TablePlus** di Mac sebagai mesin utama, berikut adalah alur kerja (workflow) yang disesuaikan agar Windows bisa melakukan `pull` dengan aman tanpa konflik konfigurasi.

---

## 1. Konsep Utama: Git Mengabaikan Config Lokal

Kunci agar Mac, Windows, dan Server bisa hidup berdampingan adalah:
**File konfigurasi rahasia (.env) JANGAN PERNAH di-upload ke Git.**

Sebagai gantinya, kita upload file template bernama `.env.example`.

---

## 2. Langkah Persiapan di MacBook (Mesin Utama - Herd & DBngin)

Lakukan ini di Terminal MacBook Anda:

### A. Siapkan Struktur Project
1.  Pastikan project berada di folder Herd (biasanya `~/Herd`).
2.  Buat file `.gitignore` (jika belum ada) dan isi dengan:
    ```text
    .env
    .DS_Store
    /vendor
    /node_modules
    ```
    *Ini mencegah settingan Mac (password DB Mac, path Mac) terkirim ke Windows/Server.*

### B. Buat File Config Mac (.env)
Buat file bernama `.env` di root folder project Mac Anda. Sesuaikan dengan settingan **DBngin** (biasanya port 3306, user root, pass kosong):
```ini
APP_ENV=local
APP_URL=http://ais.test/

DB_HOST=127.0.0.1
DB_NAME=ais_school
DB_USER=root
DB_PASS=
```

### C. Buat Template Config (.env.example)
Buat file bernama `.env.example`. File ini **AKAN** di-push ke Git agar Windows/Server tahu variabel apa saja yang dibutuhkan.
```ini
APP_ENV=local
APP_URL=http://localhost/AIS/

DB_HOST=localhost
DB_NAME=ais_school
DB_USER=root
DB_PASS=
```

### D. Update Kode PHP (Agar Membaca .env)
Pastikan `config/database.php` menggunakan `getenv()` atau library `dotenv`.
*Jika belum pakai library, gunakan script `includes/env_loader.php` yang sudah saya buatkan sebelumnya.*

### E. Push Perubahan ke Git
```bash
git add .
git commit -m "Setup environment variables for multi-device support"
git push origin main
```

---

## 3. Langkah di Windows (Mesin Backup/Secondary)

Sekarang, buka PC Windows Anda.

### A. Tarik Kode Terbaru
Buka Terminal/PowerShell di folder project Windows:
```powershell
git pull origin main
```

### B. Buat File Config Windows (.env)
Karena `.env` Mac tidak ikut ter-download (karena di-ignore), Anda harus membuatnya manual di Windows **satu kali saja**.
1.  Copy file `.env.example` menjadi `.env`.
2.  Edit `.env` sesuai settingan XAMPP Windows:
    ```ini
    APP_ENV=local
    APP_URL=http://localhost/AIS/
    
    DB_HOST=localhost
    DB_NAME=ais_school
    DB_USER=root
    DB_PASS=          <-- Kosongkan jika XAMPP default
    ```

### C. Selesai!
Sekarang Windows siap digunakan.
*   Jika Mac update kode fitur baru -> Windows tinggal `git pull`.
*   Settingan database Windows **TIDAK AKAN** tertimpa settingan Mac.

---

## 4. Langkah di Server (AA Panel)

Prosesnya sama persis dengan Windows.

1.  Login ke File Manager / Terminal Server.
2.  `git pull origin main`.
3.  Pastikan file `.env` di server sudah ada dan isinya benar (sesuai database server).
    *   Jika belum ada, buat file `.env` baru dari `.env.example`.
    *   Isi dengan password database server hosting.

---

## Ringkasan Workflow Baru

| Aktivitas | Di MacBook (Utama) | Di Windows (Backup) | Di Server (Staging) |
| :--- | :--- | :--- | :--- |
| **Tools** | Herd, DBngin, TablePlus | XAMPP, VS Code | Nginx, MySQL (AA Panel) |
| **Coding Fitur** | Edit kode, Save, Test di `ais.test` | - | - |
| **Simpan Kode** | `git add .`, `git commit`, `git push` | - | - |
| **Update App** | - | `git pull` | `git pull` |
| **Ubah DB Config** | Edit `.env` Mac | Edit `.env` Windows | Edit `.env` Server |

**Tips TablePlus:**
Gunakan TablePlus untuk mengelola database lokal (DBngin) dan remote (Server Staging) sekaligus. Anda bisa export struktur/data dari Mac dan import ke Server dengan sangat mudah via TablePlus.
