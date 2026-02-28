# SKEMA FITUR & MODUL - SCHOOL CORE SYSTEM

Dokumen ini memetakan fitur-fitur berdasarkan analisis visual UI dan kebutuhan sistem sekolah modern. Fitur dikategorikan menjadi **Core (Wajib)**, **Main (Utama)**, dan **Add-on (Tambahan)**.

## 1. CORE SYSTEM (KERNEL)
*Fondasi sistem yang wajib ada agar sistem bisa berjalan.*

| Fitur | Deskripsi | Kategori |
| :--- | :--- | :--- |
| **List Akun** | Manajemen User, Role, dan Hak Akses (RBAC). | Core |
| **Data Pokok** | Database Master Siswa, Guru, dan Staff (`core_people`). | Core |
| **Bagan Lembaga** | Struktur organisasi sekolah. | Core |
| **Master Lembar Data** | Konfigurasi formulir dan atribut data dinamis. | Core |
| **Pengaturan Sekolah** | Identitas sekolah, Logo, Config dasar. | Core |

---

## 2. MODUL AKADEMIK (KBM & EVALUASI)
*Jantung operasional sekolah untuk kegiatan belajar mengajar.*

### Fitur Utama (Main)
*   **Ruang Kelas**: Manajemen rombongan belajar.
*   **List Mapel**: Manajemen Mata Pelajaran.
*   **Jadwal Pelajaran**: (Implied) Pengaturan waktu KBM.
*   **Presensi Harian Siswa**: Absensi harian kelas.
*   **Nilai Mapel**: Input nilai pengetahuan/keterampilan.
*   **e-Raport**: Halaman cetak hasil belajar.
*   **Kenaikan Kelas**: (Implied) Proses akhir tahun ajaran.

### Fitur Tambahan (Add-on)
*   **Penilaian Dengan Rubrik**: Penilaian kualitatif/deskriptif.
*   **Observasi Fokus Karakter**: Penilaian sikap/karakter (P5/K13).
*   **Ceklis Karakter Harian**: Monitoring kebiasaan siswa.
*   **Data P5 & Report P5**: Manajemen Projek Penguatan Profil Pelajar Pancasila.
*   **Catatan Kegiatan**: Jurnal kelas/guru.
*   **Buku Penghubung**: Komunikasi harian dengan ortu.
*   **Kegiatan Kelas**: Log aktivitas spesifik di kelas.
*   **Cari Siswa**: Fitur pencarian cepat (Quick Search).
*   **WA Monitor**: Integrasi notifikasi WhatsApp untuk akademik.

---

## 3. MODUL PERENCANAAN (PLANNING)
*Modul untuk persiapan dan administrasi kurikulum.*

### Fitur Utama (Main)
*   **Kalender Kegiatan**: Kalender akademik tahunan.
*   **Rencana Pembelajaran**: RPP/Modul Ajar digital.
*   **Program Asesmen**: Perencanaan ujian/ulangan.

### Fitur Tambahan (Add-on)
*   **Weekly Plan**: Rencana mingguan guru.
*   **Asesmen Online**: CBT / Ujian berbasis komputer.
*   **Setup Kegiatan**: Konfigurasi acara sekolah.
*   **PS Setup**: (Kemungkinan) Parenting Session atau Program Sekolah Setup.
*   **Tanya Jawab Orang Tua**: Forum diskusi/konsultasi.
*   **Observasi Bakat**: Tracking minat bakat siswa.
*   **Admisi**: Penerimaan Peserta Didik Baru (PPDB).

---

## 4. MODUL KEUANGAN (FINANCE)
*Manajemen tagihan dan arus kas sekolah.*

### Fitur Utama (Main)
*   **Setup Keuangan**: Pos-pos pembayaran (SPP, Gedung, dll).
*   **Status Pembayaran**: Cek status lunas/belum per siswa.
*   **Pembayaran Orang Tua**: Interface/Pencatatan pembayaran masuk.
*   **Riwayat Penerimaan**: Laporan transaksi masuk.
*   **Data Tunggakan**: Monitoring piutang siswa.

### Fitur Tambahan (Add-on)
*   **Pengajuan Biaya**: Reimbursment/Cashbon guru.
*   **List Pengajuan Biaya**: Approval pengajuan dana.
*   **Penggunaan Uang Saku**: Jika menggunakan sistem deposit/e-money.
*   **Dashboard FLIP**: Integrasi Payment Gateway (Flip).
*   **Struktur Gaji & Komponen Gaji**: Setup payroll guru/staff.
*   **Slip Gaji (Admin & User)**: Cetak slip gaji.
*   **Setup Pembiayaan**: Konfigurasi cicilan/beasiswa.

---

## 5. MODUL KEPEGAWAIAN (HR)
*Manajemen SDM sekolah.*

### Fitur Utama (Main)
*   **Daftar Pegawai**: Database guru dan karyawan.
*   **Presensi Geolokasi**: Absensi berbasis GPS/Radius.
*   **Rekap Presensi Staf**: Laporan kehadiran bulanan.

### Fitur Tambahan (Add-on)
*   **Perizinan Staf**: Pengajuan Cuti/Sakit.
*   **Data Kinerja**: KPI atau penilaian kinerja pegawai.
*   **Penugasan**: Surat tugas atau delegasi pekerjaan.
*   **Slip Gaji**: Akses mandiri slip gaji pegawai.

---

## 6. FITUR EKSTRA & INTEGRASI (ADDITIONAL)
*Fitur canggih untuk sekolah modern (Digital School).*

*   **Gateways**:
    *   WhatsApp / SMS / Telegram / Email Gateway (Notifikasi otomatis).
    *   Payment Gateway (Virtual Account, QRIS).
*   **Hardware Integration**:
    *   Presensi Fingerprint & Wajah.
    *   Anjungan (Kiosk Info).
    *   Card Maker (Cetak Kartu Pelajar).
*   **Layanan Siswa**:
    *   Perpustakaan (Library).
    *   School Tube (Video Learning).
    *   Data Antar Jemput (Shuttle).
*   **Utilities**:
    *   Backup Restore.
    *   Live Update.
