#!/usr/bin/env bash
# Push local demo catalog (seed + images + demo_data) to the live VPS.
#
# From repo root:
#   ./deploy/push-demo-to-live.sh
#
# Env overrides:
#   SERVER=root@94.103.163.218
#   REMOTE_PATH=/var/www/unique-solution
#   SKIP_WIPE=1   # only sync images + demo SQL (no migrate:fresh)
#   DRY_RUN=1     # rsync dry-run only; print remote commands

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SERVER="${SERVER:-root@94.103.163.218}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/unique-solution}"
DRY_RUN="${DRY_RUN:-0}"
SKIP_WIPE="${SKIP_WIPE:-0}"

PUBLIC_SRC="$ROOT/storage/app/public"
SQL_SQLITE="$ROOT/database/demo_data.sql"
SQL_MYSQL="$ROOT/database/demo_data.mysql.sql"

[[ -d "$PUBLIC_SRC" ]] || { echo "Missing $PUBLIC_SRC"; exit 1; }
[[ -f "$SQL_SQLITE" && -f "$SQL_MYSQL" ]] || { echo "Missing demo SQL files"; exit 1; }

echo "==> Target: $SERVER:$REMOTE_PATH"
echo "==> Media:  $(du -sh "$PUBLIC_SRC" | awk '{print $1}')"
echo
echo "WARNING: Default flow WIPES live DB (migrate:fresh --seed), then loads demo extras."
echo "         Existing live orders/users will be deleted. Use SKIP_WIPE=1 to avoid wipe."
echo
read -r -p "Continue? [y/N] " ok
[[ "${ok:-}" =~ ^[Yy]$ ]] || exit 0

echo "==> 1/3 Rsync demo media"
RSYNC=(rsync -avz --progress)
[[ "$DRY_RUN" == "1" ]] && RSYNC+=(--dry-run)
"${RSYNC[@]}" --exclude='.gitignore' \
  "$PUBLIC_SRC/" "$SERVER:$REMOTE_PATH/storage/app/public/"

if [[ "$DRY_RUN" == "1" ]]; then
  echo "[DRY_RUN] Skipping scp/ssh. On server run:"
  cat <<EOF
cd $REMOTE_PATH
php artisan storage:link
# If wipe OK:
php artisan migrate:fresh --seed --force
# Then SQLite:
sqlite3 database/database.sqlite < database/demo_data.sql
# Or MySQL (after uploading demo_data.mysql.sql):
# mysql -u USER -p DB_NAME < database/demo_data.mysql.sql
php artisan optimize:clear
EOF
  exit 0
fi

echo "==> 2/3 Upload demo SQL"
scp "$SQL_SQLITE" "$SQL_MYSQL" "$SERVER:$REMOTE_PATH/database/"

echo "==> 3/3 Seed + import on server"
ssh -t "$SERVER" bash -s <<EOF
set -euo pipefail
cd '$REMOTE_PATH'
php artisan storage:link 2>/dev/null || true

DB_CONN=\$(grep -E '^DB_CONNECTION=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
echo "DB_CONNECTION=\$DB_CONN"

if [[ '$SKIP_WIPE' != '1' ]]; then
  php artisan migrate:fresh --seed --force
else
  echo "SKIP_WIPE=1 — keeping DB, applying demo SQL only"
fi

if [[ "\$DB_CONN" == "sqlite" ]]; then
  sqlite3 database/database.sqlite < database/demo_data.sql
else
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env | sed 's/^/export /')
  set +a
  mysql -h"\${DB_HOST:-127.0.0.1}" -P"\${DB_PORT:-3306}" -u"\$DB_USERNAME" \${DB_PASSWORD:+-p"\$DB_PASSWORD"} "\$DB_DATABASE" < database/demo_data.mysql.sql
fi

php artisan optimize:clear
echo "OK — verify: curl -s http://127.0.0.1:8088/api/v1/home | head -c 200"
EOF

echo
echo "Done. From your Mac:"
echo "  curl -s http://94.103.163.218:8088/api/v1/home | head"
echo "  APK → pull-to-refresh home"
echo
echo "Logins after fresh seed (password for all): password"
echo "  Admin:    admin@uniquesolution.com"
echo "  Customer: customer1@example.com … customer5@example.com"
