# Dokumentasi Integrasi Google Calendar (AIS Agenda)

## 1. Overview
Integrasi Google Calendar di AIS memungkinkan sinkronisasi agenda sekolah ke kalender Google per unit. Backend mengelola:
- OAuth 2.0 (mendapatkan dan menyegarkan akses token)
- Pemetaan Calendar ID per unit
- Pembuatan, pembaruan, penghapusan event di Google
- Cache event Google untuk performa tampilan
- Idempotensi agar tidak terjadi duplikasi event di Google

Referensi kode utama: [manage_agenda.php](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php)

---

## 2. Konfigurasi
- File konfigurasi: [google_calendar.php](file:///c:/xampp/htdocs/AIS/config/google_calendar.php)
  - Menentukan `redirect_uri` secara dinamis sesuai base URL (AIS/AIStest).
  - Menyimpan `client_id` dan `client_secret` (jangan commit nilai asli ke repo publik).
- Penyimpanan pengaturan di tabel `core_settings` melalui helper `getSetting/saveSetting`:
  - `google_calendar_token`: Access token aktif
  - `google_calendar_refresh_token`: Refresh token
  - `google_calendar_id`: Calendar ID global (legacy, fallback)
  - `google_calendar_map`: Pemetaan Calendar ID per unit (JSON: `{ "PRIMARY": "...", "SMP": "...", ... }`)
  - `google_calendar_colors`: Peta warna latar kalender (cache)
  - `google_event_colors`: Peta warna event Google (cache)
  - `google_sync_last`: Timestamp sinkronisasi cache terakhir per unit

Helper & warna:
- Ambil Calendar ID per unit: [getCalendarIdForUnit](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L91-L110)
- Refresh token: [refreshGoogleAccessToken](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L111-L139)
- Peta warna kalender: [getCalendarColorsMap](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L140-L199) dan [getCalendarBgColor](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L203-L207)
- Peta warna event: [getEventColorsMap](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L208-L264)

---

## 3. Skema Data
- Tabel lokal agenda: `acad_school_agenda`
  - Kolom penting: `title`, `description`, `start_date`, `end_date`, `location`, `type`, `google_event_id`
  - `google_event_id` menyimpan ID event di Google untuk sinkron update/delete
- Cache event Google: `acad_school_agenda_google_cache`
  - Kolom: `google_event_id`, `calendar_id`, `unit_code`, `title`, `description`, `location`, `start_date`, `end_date`, `color`, `updated_at`
  - Unique key: `(google_event_id, calendar_id, unit_code)`

Pembuatan skema dan cache ditangani di awal `manage_agenda.php`: [ensure schema & cache](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L62-L87)

---

## 4. Alur OAuth & Koneksi
- Inisiasi sinkron: `POST api/manage_agenda.php?action=sync_google`
  - Jika belum ada token, server mengembalikan `auth_url` untuk login Google
  - Jika sudah ada token, mengembalikan status tersambung
  - Implementasi: [sync_google](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L689-L729)
- Callback OAuth:
  - Ada dua jalur untuk kompatibilitas:
    - `api/manage_agenda.php?action=google_callback` [link](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L731-L759)
    - `api/google_callback.php` (mengatur refresh token, redirect balik ke UI) [link](file:///c:/xampp/htdocs/AIS/api/google_callback.php#L1-L74)

Catatan keamanan:
- Simpan `client_id`, `client_secret`, `refresh_token` di `core_settings` atau konfigurasi server, bukan di repo publik.

---

## 5. Endpoint Utama

### 5.1. Ambil Agenda (merge lokal + cache Google)
- `GET api/manage_agenda.php?action=get_agenda&start=YYYY-MM-DD&end=YYYY-MM-DD&unit=<code>&debug=1`
- Menggabungkan agenda lokal (`acad_school_agenda`) dan cache Google (`acad_school_agenda_google_cache`)
- Menginjeksikan warna dari cache untuk event lokal yang terhubung
- Implementasi: [get_agenda](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L468-L587)
- Respons:
  ```json
  { "success": true, "data": [ { 
      "id": "local|google:<id>", "title": "...", "start_date": "...", "end_date": "...",
      "location": "...", "type": "EVENT", "color": "#RRGGBB", "calendar_id": "<optional>"
  } ], "debug": { ... } }
  ```

### 5.2. Simpan Agenda (Create/Update) + Push ke Google
- `POST api/manage_agenda.php?action=save_agenda`
- Body JSON:
  ```json
  {
    "id": 123,                       // opsional untuk update
    "title": "Judul",
    "description": "Deskripsi",
    "start_date": "YYYY-MM-DD HH:MM:SS",
    "end_date": "YYYY-MM-DD HH:MM:SS",
    "location": "Lokasi",
    "type": "EVENT",
    "unit": "PRIMARY",               // opsional; default 'all'
    "push_to_google": true           // jika ingin sinkron ke Google
  }
  ```
- Idempotensi & anti duplikasi:
  - Server mendeteksi agenda lokal yang sama (title + waktu persis) dan mengadopsi `google_event_id` bila event Google sudah ada
  - Pada update, server selalu membaca `google_event_id` dari DB; bila kosong akan mencari/menautkan event Google yang cocok sebelum membuat baru
- Implementasi: [save_agenda](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L590-L659), pencarian & adopsi: [findGoogleEventIdByTitleAndTime](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L360-L425)
- Operasi Google:
  - Create: [createGoogleEvent](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L426-L474)
  - Update: [updateGoogleEvent](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L475-L501)

### 5.3. Hapus Agenda
- `POST api/manage_agenda.php?action=delete_agenda`
- Body JSON:
  ```json
  { "id": 123, "unit": "PRIMARY" }
  ```
- Jika `google_event_id` ada, server menghapus event di Google lalu menghapus agenda lokal
- Implementasi: [delete_agenda](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L664-L686)
- Operasi Google delete: [deleteGoogleEvent](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L443-L466)

### 5.4. Putus Sinkron (unsync)
- `POST api/manage_agenda.php?action=unsync_google`
- Body JSON:
  ```json
  { "id": 123 }
  ```
- Mengosongkan `google_event_id` di agenda lokal tanpa menghapus event di Google
- Implementasi: [unsync_google](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L986-L1009)

### 5.5. Sinkronisasi Cache Google
- `GET/POST api/manage_agenda.php?action=sync_google_cache&unit=<code>&start=YYYY-MM-DD&end=YYYY-MM-DD&force=1`
- Mengambil event dari Google API, menyimpannya ke `acad_school_agenda_google_cache` untuk rentang waktu dan unit tertentu
- Rate-limit 60 detik per unit kecuali `force=1`
- Implementasi: [sync_google_cache](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L814-L875)

### 5.6. Audit & Hapus Duplikat di Google
- `GET/POST api/manage_agenda.php?action=dedup_google&unit=<code>&start=YYYY-MM-DD&end=YYYY-MM-DD&delete=1`
- Mengelompokkan event dengan kunci `(summary|start|end)`; bila `delete=1`, menghapus event duplikat dan menyisakan satu
- Implementasi: [dedup_google](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L876-L978)

---

## 6. Warna Event & Kalender
- Warna latar default dari kalender: [getCalendarBgColor](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L203-L207)
- Warna event berdasarkan `colorId`: [getEventColorsMap](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L208-L264)
- Pada penggabungan `get_agenda`, warna dari cache diinjeksikan ke event lokal yang terhubung lewat `google_event_id`

---

## 7. Pemetaan Multi-Unit
- Calendar per unit ditentukan oleh `google_calendar_map` (key uppercase unit)
- Fallback: `google_calendar_id`, lalu `primary` jika tidak tersedia
- Implementasi pemilihan ID: [getCalendarIdForUnit](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L91-L110)

---

## 8. RBAC & Keamanan
- Endpoint Google hanya bisa dipakai oleh role: `SUPERADMIN`, `ADMIN`, `ACADEMIC`
- Guard modul: [includes/guard.php](file:///c:/xampp/htdocs/AIS/includes/guard.php)
- Frontend gunakan `window.BASE_URL` dinamis (AIS/AIStest) saat memanggil API

---

## 9. Troubleshooting
- Duplikasi event di Google:
  - Gunakan `dedup_google` dengan `delete=1` untuk menghapus duplikat
  - Pastikan `save_agenda` diaktifkan idempotensi (fitur adopsi `google_event_id`)
- Error 401 saat panggil Google API:
  - Token expired, jalankan `sync_google` untuk memperbarui via refresh token
- Tampilan kalender tidak berwarna:
  - Jalankan `sync_google_cache` agar cache warna terisi; pastikan `google_event_colors`/`google_calendar_colors` ada
- Respons non-JSON:
  - Cek header `Content-Type` dan log server; sebagian endpoint menyediakan `debug` untuk inspeksi

---

## 10. Contoh cURL

### Simpan Agenda & Push ke Google
```bash
curl -X POST "http://localhost/AIS/api/manage_agenda.php?action=save_agenda" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Rapat Guru",
    "description": "Koordinasi kurikulum",
    "start_date": "2026-02-10 13:00:00",
    "end_date": "2026-02-10 14:00:00",
    "location": "Ruang Rapat",
    "type": "EVENT",
    "unit": "PRIMARY",
    "push_to_google": true
  }'
```

### Sinkronisasi Cache Google
```bash
curl "http://localhost/AIS/api/manage_agenda.php?action=sync_google_cache&unit=PRIMARY&start=2026-02-01&end=2026-02-29&force=1"
```

### Audit & Hapus Duplikat Google
```bash
curl "http://localhost/AIS/api/manage_agenda.php?action=dedup_google&unit=PRIMARY&start=2026-02-01&end=2026-02-29&delete=1"
```

---

## 11. Catatan Implementasi
- `fetchGoogleEvents` menyediakan struktur event Google untuk cache/tampilan: [fetchGoogleEvents](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L265-L359)
- Idempotensi adopsi event existing: [findGoogleEventIdByTitleAndTime](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L360-L425)
- Operasi Google (Create/Update/Delete): [create](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L426-L474), [update](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L475-L501), [delete](file:///c:/xampp/htdocs/AIS/api/manage_agenda.php#L443-L466)

---

*Dokumen ini mengikuti standar proyek AIS (BASE_URL dinamis, multi‑unit, RBAC). Jangan menaruh kredensial Google di repo; gunakan pengaturan server atau tabel `core_settings`.*
