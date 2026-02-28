SOP DEPLOY AIS KE AAPANEL (PORT 81) VIA GIT
===========================================

TUJUAN
- Deploy cepat dari GitHub ke server aaPanel port:81 dengan 2 perintah
- Menjaga konfigurasi portable (BASE_URL, DB)

PRASYARAT
- SSH Deploy Key aktif untuk repo GitHub (Allow read)
- Document Root (contoh): /www/wwwroot/AIS
- Database produksi: aiscore (user: aiscore, password kuat)
- PHP: pdo_mysql aktif

ALUR KERJA LOKAL
- Kerjakan perubahan, uji di lokal
- Commit dengan pesan jelas, push ke GitHub:
  - git add .
  - git commit -m "feat/fix: deskripsi singkat"
  - git push origin master

DEPLOY CEPAT DI SERVER (DUA PERINTAH)
1) Tarik versi terbaru ke /tmp
   rm -rf /tmp/ais_tmp
   GIT_SSH_COMMAND='ssh -i ~/.ssh/id_ed25519_aapanel -o IdentitiesOnly=yes' \
   git clone git@github.com:hirzie/AIS.git /tmp/ais_tmp

2) Sinkron ke Document Root (abaikan .user.ini)
   rsync -a --delete --exclude='.user.ini' /tmp/ais_tmp/ /www/wwwroot/AIS/

IZIN DASAR (SAAT PERLU)
- chown -R www:www /www/wwwroot/AIS || true
- find /www/wwwroot/AIS -type d -exec chmod 755 {} \;
- find /www/wwwroot/AIS -type f -exec chmod 644 {} \;
- mkdir -p /www/wwwroot/AIS/uploads && chmod 775 /www/wwwroot/AIS/uploads

SETUP DATABASE (PERTAMA KALI)
- Buat DB aiscore dan user aiscore di aaPanel
- Import schema: database/full_schema.sql (phpMyAdmin/CLI)
- Konfigurasi koneksi server di config/database.php (bagian server)

VERIFIKASI
- http://IP:81/login.php
- Cek modul terkait (Akademik/BK), simpan data, cetak form/QR

ROLLBACK CEPAT
- Gunakan tag/commit stabil:
  - GIT_SSH_COMMAND='ssh -i ~/.ssh/id_ed25519_aapanel -o IdentitiesOnly=yes' \
    git clone --branch <tag/branch> git@github.com:hirzie/AIS.git /tmp/ais_tmp
  - rsync -a --delete --exclude='.user.ini' /tmp/ais_tmp/ /www/wwwroot/AIS/

TROUBLESHOOTING
- Permission denied (publickey):
  - Pastikan deploy key ditambahkan di repo
  - Gunakan GIT_SSH_COMMAND dengan key yang benar
- Alternatif HTTPS + PAT:
  - export GHTOKEN="ghp_xxx"
  - git clone https://hirzie:${GHTOKEN}@github.com/hirzie/AIS.git /tmp/ais_tmp
  - unset GHTOKEN
- .user.ini permission errors: aman diabaikan (proteksi aaPanel)
- Jadwal BK kosong: pastikan is_break ditafsir sebagai integer (fix ada di modules/counseling/student_profile.php)

CATATAN PORTABILITAS
- BASE_URL produksi otomatis root "/" atau subpath "/AIS/" (includes/header.php)
- Semua fetch utama memakai window.BASE_URL

ATURAN GIT RINGKAS
- Satu commit per konteks perubahan, pesan jelas
- Optional branch ringan: master stabil, feature/* untuk fitur
- Tag rilis tiap milestone (v1.0.0-beta.1) untuk rollback mudah

SELESAI
Dengan prasyarat terpenuhi, siklus harian cukup: build lokal → commit/push → jalankan 2 perintah deploy → verifikasi.
