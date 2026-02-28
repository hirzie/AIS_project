# Cheatsheet Deploy AA Panel

## Setup sekali
1. Buat SSH key server:
   ```
   ssh-keygen -t ed25519 -C "aapanel-AIS" -f ~/.ssh/id_ed25519_aapanel -N ""
   eval "$(ssh-agent -s)"; ssh-add ~/.ssh/id_ed25519_aapanel
   cat ~/.ssh/id_ed25519_aapanel.pub
   ```
   Tambahkan ke GitHub > Settings > SSH and GPG keys.

2. Clone repo ke /tmp:
   ```
   rm -rf /tmp/AIS_tmp
   GIT_SSH_COMMAND='ssh -i ~/.ssh/id_ed25519_aapanel -o IdentitiesOnly=yes' \
   git clone git@github.com:hirzie/AIS_project.git /tmp/AIS_tmp
   cd /tmp/AIS_tmp && git checkout main
   ```

3. Install command deploy:
   ```
   cp /tmp/AIS_tmp/tools/install_deploy_commands.sh /root/
   chmod +x /root/install_deploy_commands.sh
   sudo /root/install_deploy_commands.sh
   ```

## Jalankan deploy
Staging:
```
deploy_staging
```
Production:
```
deploy_prod
```

## Catatan
- File lingkungan di server tidak ditimpa: `.user.ini`, `config/database.php`, `sessions/`, `uploads/`, `backups/`.
- Jika muncul peringatan `.user.ini` immutable, abaikan.
- Jika pull repo di /tmp error “dubious ownership”, jalankan:
  ```
  sudo git config --global --add safe.directory /tmp/AIS_tmp
  ```

## Opsi Auto-Deploy via Webhook (push langsung jalan)
1. Install command deploy seperti di atas.
2. Salin file `tools/webhook_deploy.php` ke lokasi yang diakses web (mis. `/www/wwwroot/AIStest/webhook_deploy.php`).
3. Set environment di server:
   - `GH_WEBHOOK_SECRET` (nilai rahasia sama dengan yang dibuat di GitHub)
   - Opsional `GH_ALLOWED_IPS` (daftar IP yang diizinkan, dipisah koma)
4. Buat GitHub Webhook:
   - Repo Settings > Webhooks > Add webhook
   - Payload URL: `https://<domain>/webhook_deploy.php`
   - Content type: `application/json`
   - Secret: isi `GH_WEBHOOK_SECRET`
   - Pilih event: `Just the push event`
5. Uji dengan push ke branch `main`. Endpoint akan mengeksekusi `deploy_staging`.

Alternatif yang lebih sederhana: cron
```
*/5 * * * * deploy_staging >/var/log/deploy_staging.log 2>&1
```

### Opsi simpan secret via file (tanpa ENV)
1. Buat folder config di webroot staging:
   ```
   mkdir -p /www/wwwroot/AIStest/config
   ```
2. Buat file `/www/wwwroot/AIStest/config/webhook_secret.php` berisi:
   ```php
   <?php
   return [
     'secret' => 'ISI_SECRET_WEBHOOK',
     'allowed_ips' => [] // atau "ip1,ip2"
   ];
   ```
3. Simpan `tools/webhook_deploy.php` ke webroot, lalu uji dengan push.
