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
