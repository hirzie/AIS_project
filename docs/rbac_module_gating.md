# RBAC & Pola Gating Modul di AIS

- Dokumen ini merangkum aturan role, cara ekspor sesi ke frontend, dan pola pengecekan akses di setiap modul agar seragam dan aman, termasuk kasus khusus modul Security.

## Role & Sumber Akses

- Role inti: SUPERADMIN, ADMIN, MANAGERIAL, STAFF, ACADEMIC, FOUNDATION, FINANCE, POS, TEACHER, STUDENT, PARENT, SECURITY, CLEANING, LIBRARY, BOARDING, PRINCIPAL.
- Akses modul dibentuk saat login/guard:
  - Per-role: role tertentu otomatis mengaktifkan modul (mis. SECURITY → modul security).
  - Override per-user: kolom core_users.access_modules (JSON) dapat menambah/mengurangi akses untuk non-admin. Format didukung array atau object.
  - Per-division: core_people.custom_attributes.division (SECURITY, CLEANING, dsb.) menambah akses modul terkait.
- Implementasi pembentukan allowed_modules:
  - Login: [login.php](file:///c:/xampp/htdocs/AIS/login.php#L60-L90) dan override per-user [login.php](file:///c:/xampp/htdocs/AIS/login.php#L87-L127).
  - Guard: [guard.php](file:///c:/xampp/htdocs/AIS/includes/guard.php#L44-L77) dan override per-user + division [guard.php](file:///c:/xampp/htdocs/AIS/includes/guard.php#L78-L135).

## Ekspor Sesi ke Frontend (Standar Header)

- Semua halaman modul wajib memakai header untuk mengekspor:
  - window.BASE_URL
  - window.ALLOWED_MODULES
  - window.USER_ROLE
  - window.USER_ID
- Lokasi: [includes/header.php](file:///c:/xampp/htdocs/AIS/includes/header.php#L29-L34)
- Tujuan:
  - Menjaga konsistensi multi-instansi (AIS/AIStest).
  - Memungkinkan UI memfilter menu berdasarkan izin dan melakukan pengkondisian tampilan (bukan otorisasi).

## Guard Server-Side (Wajib)

- Sebelum render modul: panggil require_login_and_module('<kode_modul>').
- Jika tidak lolos, redirect ke login atau dashboard dengan noaccess=<modul>.
- Lokasi: [includes/guard.php](file:///c:/xampp/htdocs/AIS/includes/guard.php#L37-L50) dan pemeriksaan akses [guard.php](file:///c:/xampp/htdocs/AIS/includes/guard.php#L137-L149).
- Catatan:
  - Otorisasi sebenarnya hanya terjadi di server.
  - Frontend hanya mempengaruhi visibilitas UI, bukan hak akses ke API.

## Pola Gating di Frontend (Seragam)

- Menu/sidebar:
  - Filter item berdasarkan window.ALLOWED_MODULES + window.USER_ROLE.
  - Hindari hardcode path; pakai BASE_URL.
- Pemeriksaan per-user untuk tugas/shift (contoh Security):
  - hasOverride = (role === ADMIN || role === SUPERADMIN)
  - userMatch = hasOverride
    - atau s.user_id kosong
    - atau String(s.user_id) === String(currentUserId)
    - atau (role === SECURITY && nama pegawai shift === window.USER_FULL_NAME)
  - Gunakan waktu server untuk jendela aktif:
    - server_now dari API.
    - nowMinutes() memakai server_now bila ada; fallback ke jam browser bila tidak.
  - Implementasi contoh: [modules/security/index.php](file:///c:/xampp/htdocs/AIS/modules/security/index.php#L1568-L1642)

## API Standar untuk Ringkasan

- Zero Report & Checklist (Security):
  - zero_report_overview: harian/mingguan/bulanan + daily.shifts dan server_now.
  - list_checklist_runs_month, save_checklist_result, get_checklist_answers.
  - Lokasi: [api/security.php](file:///c:/xampp/htdocs/AIS/api/security.php#L239-L455), [api/security.php](file:///c:/xampp/htdocs/AIS/api/security.php#L531-L579).
- Catatan penting:
  - officer_user_id pada run diisi dari $_SESSION['user_id'] (server-side), bukan dari variabel frontend.
  - Pada overview harian, jika person pegawai sama dengan $_SESSION['person_id'], s.user_id disejajarkan ke $_SESSION['user_id'] untuk stabilitas tampilan (multi-akun satu orang).

## Model Data & Pengaturan Window

- security_shifts:
  - require_checklist (wajib checklist atau tidak)
  - window_minutes (durasi jendela sebelum end_time)
  - default_template_id (template checklist default)
  - days_json (hari aktif: senin/mon/all, kosong = setiap hari)
  - Lokasi pembuatan/alter: [api/security.php](file:///c:/xampp/htdocs/AIS/api/security.php#L42-L77)
- Zero Report:
  - Harian: jendela aktif ditentukan dari window_minutes (bukan fixed 1 jam).
  - Mingguan: minimal 1 rapat SECURITY per minggu.
  - Bulanan: evaluasi jalannya checklist dibandingkan jumlah window pada bulan berjalan.

## Keamanan & Anti-Pattern

- Frontend (window.USER_ID/ROLE) hanya untuk tampilan. Server selalu membaca sesi saat membuat/mengubah data.
- Hindari mengambil keputusan otorisasi di frontend.
- Gunakan BASE_URL yang dinamis; jangan hardcode '/AIS/' atau '/'.
- Bersihkan cache (OPcache/APC) saat deploy agar file terbaru aktif.

## Checklist Verifikasi Modul

- Header ekspor VAR sesi (BASE_URL, ALLOWED_MODULES, USER_ROLE, USER_ID).
- Guard require_login_and_module('<kode_modul>') aktif.
- UI memfilter menu berdasarkan ALLOWED_MODULES.
- API modul memvalidasi sesi; officer_user_id selalu diambil dari $_SESSION.
- Untuk penugasan per-user:
  - Normalisasi tipe id (String vs Number).
  - Fallback cocok-nama bila relevan (mis. SECURITY).
  - Pakai server_now untuk deteksi window aktif.

## Masalah Umum & Solusi Cepat

- Tombol hanya muncul di SUPERADMIN:
  - Cek userMatch: samakan tipe id, pastikan USER_FULL_NAME terisi, dan fallback nama aktif.
- Window tidak aktif padahal jam benar di server:
  - Pastikan UI memakai server_now, days_json cocok, end_time format HH:MM, effective_date tidak di masa depan.
- Modul tidak muncul di sidebar:
  - Pastikan ALLOWED_MODULES berisi kode modul (lowercase) dan header dimuat.

