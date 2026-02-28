#!/bin/bash
# deploy.sh
# Script untuk sinkronisasi kode dari folder /tmp/ais_tmp ke /www/wwwroot/AIS

echo "--- START DEPLOYMENT ---"

echo "Preparing source..."
git config --global --add safe.directory /tmp/ais_tmp 2>/dev/null || true
if [ -d "/tmp/ais_tmp/.git" ]; then
  echo "Pulling latest code..."
  if [ -n "$GHTOKEN" ]; then
    git -C /tmp/ais_tmp pull origin master
  else
    GIT_SSH_COMMAND='ssh -i ~/.ssh/id_ed25519_aapanel -o IdentitiesOnly=yes' git -C /tmp/ais_tmp pull origin master
  fi
else
  echo "Cloning repository..."
  rm -rf /tmp/ais_tmp
  if [ -n "$GHTOKEN" ]; then
    git clone https://hirzie:${GHTOKEN}@github.com/hirzie/AIS.git /tmp/ais_tmp
  else
    GIT_SSH_COMMAND='ssh -i ~/.ssh/id_ed25519_aapanel -o IdentitiesOnly=yes' git clone git@github.com:hirzie/AIS.git /tmp/ais_tmp
  fi
fi

echo "Syncing files to /www/wwwroot/AIS..."
rsync -a --delete --exclude='.user.ini' --exclude='config/database.php' /tmp/ais_tmp/ /www/wwwroot/AIS/

echo "Setting permissions..."
chown -R www:www /www/wwwroot/AIS
find /www/wwwroot/AIS -type d -exec chmod 755 {} \;
find /www/wwwroot/AIS -type f -exec chmod 644 {} \;
chmod -R 775 /www/wwwroot/AIS/uploads 2>/dev/null || true

echo "--- DEPLOYMENT SUCCESSFUL ---"
