# ROADMAP PENGEMBANGAN BERTAHAP (PHASED ROLLOUT)

Dokumen ini adalah panduan untuk "mencicil" pengembangan sistem SekolahOS agar terarah dan tidak *overwhelming*.

## PRINSIP PENGEMBANGAN: "Build Small, Release Often"
Jangan menunggu semua modul jadi baru dirilis. Rilis per modul agar segera dapat feedback.

---

## FASE 1: PONDASI & DATA INDUK (Bulan 1)
*Fokus: Memastikan data manusia dan akses aman.*

1.  **Core Database (`core_`)**:
    *   [ ] Tabel Users & Roles (RBAC).
    *   [ ] Tabel People (Siswa, Guru, Staff, Ortu).
    *   [ ] Tabel Settings (Identitas Sekolah).
2.  **Admin Panel (Basic)**:
    *   [ ] Login & Logout.
    *   [ ] CRUD Data Siswa & Guru.
    *   [ ] Import Data dari Excel (Fitur Wajib Migrasi).

*Goal: Admin bisa login dan input data seluruh warga sekolah.*

---

## FASE 2: OPERASIONAL AKADEMIK DASAR (Bulan 2)
*Fokus: Guru bisa mengajar dan menilai.*

1.  **Modul Akademik (`acad_`) - Part 1**:
    *   [ ] Manajemen Kelas (Rombel).
    *   [ ] Jadwal Pelajaran (Simpel dulu).
    *   [ ] Presensi Harian.
2.  **Executive View (Early)**:
    *   [ ] Statistik Kehadiran Harian (Real-time).

*Goal: Guru mulai menggunakan sistem untuk absen. Kertas absen mulai ditinggalkan.*

---

## FASE 3: KEUANGAN & PEMBAYARAN (Bulan 3)
*Fokus: Sekolah mulai menerima uang via sistem.*

1.  **Modul Keuangan (`fin_`)**:
    *   [ ] Setup Tagihan (SPP, Gedung).
    *   [ ] Kasir Pembayaran (Terima Cash/Transfer).
    *   [ ] Cetak Kuitansi.
    *   [ ] Laporan Tunggakan.
2.  **Executive View (Update)**:
    *   [ ] Dashboard Arus Kas Harian.
    *   [ ] Approval Pengajuan Biaya (Kecil-kecilan).

*Goal: Bendahara tidak lagi mencatat di buku besar manual.*

---

## FASE 4: E-RAPOR & PENILAIAN (Akhir Semester)
*Fokus: Cetak hasil belajar.*

1.  **Modul Akademik (`acad_`) - Part 2**:
    *   [ ] Input Nilai (Harian, UTS, UAS).
    *   [ ] Rumus Nilai Akhir.
    *   [ ] Template Cetak Rapor.

*Goal: Pembagian rapor menggunakan sistem.*

---

## FASE 5: CUSTOM & ADVANCED FEATURES (Berkelanjutan)
*Fokus: Fitur "Wow" di Executive View & Fasilitas.*

1.  **Fasilitas**:
    *   [ ] Inventaris & Aset.
    *   [ ] Manajemen Dapur/Kantin.
    *   [ ] Tracking Armada.
2.  **Smart Features**:
    *   [ ] WA Notifikasi Otomatis (Tagihan/Absen).
    *   [ ] Anjungan TV Lobby.

---

## SARAN PENGEMBANGAN EXECUTIVE VIEW
Selain fitur yang sudah ada, berikut ide tambahan untuk *Executive View*:

1.  **Approval Center (Pusat Persetujuan)**
    *   Eksekutif seringkali menjadi "bottle-neck" birokrasi. Buatkan fitur "One-Click Approval" untuk:
        *   Disposisi Surat Masuk.
        *   ACC Anggaran/Pembelian.
        *   ACC Cuti Pegawai.

2.  **Admission Funnel (PPDB Monitor)**
    *   Melihat tren pendaftar baru:
        *   Berapa yang ambil formulir?
        *   Berapa yang sudah tes?
        *   Berapa yang sudah bayar daftar ulang?
    *   Ini krusial untuk proyeksi pendapatan tahun depan.

3.  **Voice of Customer (Suara Ortu)**
    *   Agregasi keluhan/masukan dari "Tanya Jawab Orang Tua".
    *   Sentimen analisis sederhana (Positif/Negatif).

---
*Catatan: Kerangka ini fleksibel. Jika sekolah mendesak fitur Keuangan duluan, Fase 3 bisa ditukar dengan Fase 2.*
