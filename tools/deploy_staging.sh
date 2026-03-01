#!/bin/bash
set -euo pipefail
REPO_URL_SSH="git@github.com:hirzie/AIS_project.git"
BRANCH="${BRANCH:-main}"
TMP_DIR="${TMP_DIR:-/tmp/AIS_tmp}"
WEBROOT="/www/wwwroot/AIStest"
SSH_KEY="${SSH_KEY:-~/.ssh/id_ed25519_aapanel}"
echo "START"
git config --global --add safe.directory "$TMP_DIR" 2>/dev/null || true
if [ -d "$TMP_DIR/.git" ]; then
  if [ -n "${GHTOKEN:-}" ]; then
    # Ensure remote uses HTTPS when PAT is present
    git -C "$TMP_DIR" remote set-url origin "https://github.com/hirzie/AIS_project.git" || true
    # Attach Authorization header for this process
    git -C "$TMP_DIR" -c http.extraHeader="Authorization: bearer $GHTOKEN" fetch origin "$BRANCH"
    git -C "$TMP_DIR" checkout "$BRANCH"
    git -C "$TMP_DIR" -c http.extraHeader="Authorization: bearer $GHTOKEN" pull origin "$BRANCH"
  else
    GIT_SSH_COMMAND="ssh -i $SSH_KEY -o IdentitiesOnly=yes" git -C "$TMP_DIR" fetch origin "$BRANCH"
    git -C "$TMP_DIR" checkout "$BRANCH"
    GIT_SSH_COMMAND="ssh -i $SSH_KEY -o IdentitiesOnly=yes" git -C "$TMP_DIR" pull origin "$BRANCH"
  fi
else
  rm -rf "$TMP_DIR"
  if [ -n "${GHTOKEN:-}" ]; then
    # Use PAT embedded in URL for clone to avoid interactive prompt
    git clone "https://${GHTOKEN}@github.com/hirzie/AIS_project.git" "$TMP_DIR"
    # After clone, set remote back to HTTPS without token and attach header for subsequent pulls
    git -C "$TMP_DIR" remote set-url origin "https://github.com/hirzie/AIS_project.git"
    git -C "$TMP_DIR" config --local http.extraHeader "Authorization: bearer $GHTOKEN"
  else
    GIT_SSH_COMMAND="ssh -i $SSH_KEY -o IdentitiesOnly=yes" git clone "$REPO_URL_SSH" "$TMP_DIR"
  fi
  git -C "$TMP_DIR" checkout "$BRANCH"
fi
mkdir -p "$WEBROOT"
rsync -a --delete --chown=www:www \
  --exclude='.user.ini' \
  --exclude='config/database.php' \
  --exclude='sessions/' \
  --exclude='uploads/' \
  --exclude='backups/' \
  "$TMP_DIR/" "$WEBROOT/"
# Skip immutable .user.ini when setting permissions
find "$WEBROOT" -path "$WEBROOT/.user.ini" -prune -o -type d -exec chmod 755 {} \;
find "$WEBROOT" -path "$WEBROOT/.user.ini" -prune -o -type f -exec chmod 644 {} \;
mkdir -p "$WEBROOT/uploads" "$WEBROOT/sessions"
chmod -R 775 "$WEBROOT/uploads" "$WEBROOT/sessions"
echo "DONE"
