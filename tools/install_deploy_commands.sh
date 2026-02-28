#!/bin/bash
set -euo pipefail
SRC="${SRC:-/tmp/AIS_tmp/tools}"
STAGING_DST="/usr/local/bin/deploy_staging"
PROD_DST="/usr/local/bin/deploy_prod"
cp "$SRC/deploy_staging.sh" "$STAGING_DST"
chmod +x "$STAGING_DST"
cp "$SRC/deploy_prod.sh" "$PROD_DST"
chmod +x "$PROD_DST"
echo "deploy_staging and deploy_prod installed"
