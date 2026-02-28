# DATABASE SCHEMA DOCUMENTATION

Dokumen ini menjelaskan struktur database Core System dan Modul Akademik.

## 1. CORE SYSTEM (Pondasi)

### `core_units`
Tabel untuk menyimpan unit sekolah dalam yayasan.
- `code`: TK, SD, SMP, SMA.
- Digunakan untuk memisahkan data antar unit (Multi-Tenancy).

### `core_people`
**"Single Source of Truth"** untuk semua manusia di sekolah.
- Tidak ada tabel terpisah untuk `students` atau `teachers`. Semua ada di sini.
- Kolom `type` membedakan peran: 'STUDENT', 'TEACHER', 'STAFF'.
- Kolom `unit_id` menentukan orang tersebut milik unit mana.
- Kolom `custom_attributes` (JSON) menyimpan data spesifik yang tidak umum (misal: "Ukuran Baju", "Alergi Makanan").

### `core_users`
Tabel autentikasi untuk login ke aplikasi.
- Terhubung ke `core_people` (One-to-One).

### `core_academic_years`
Menyimpan periode akademik (Tahun Ajaran & Semester).
- Hanya satu tahun ajaran yang boleh `is_active = 1`.

---

## 2. ACADEMIC MODULE

### `acad_class_levels`
Tingkat kelas yang tersedia di setiap unit.
- SD: Kelas 1 - 6
- SMP: Kelas 7 - 9
- SMA: Kelas X - XII

### `acad_classes` (Rombel)
Kelas fisik/logis yang terbentuk di tahun ajaran tertentu.
- Contoh: "X-MIPA-1 Tahun 2025/2026".
- Memiliki Wali Kelas (`homeroom_teacher_id`).

### `acad_student_classes`
Tabel Pivot (Many-to-Many) untuk memetakan Siswa ke Kelas.
- Siswa bisa berada di kelas yang berbeda tiap tahun ajaran.
- Menyimpan histori kelas siswa (Kenaikan Kelas).

### `acad_subjects`
Daftar mata pelajaran per unit.

### `acad_schedules`
Jadwal pelajaran mingguan.
- Menghubungkan: Kelas + Mapel + Guru + Hari/Jam.
