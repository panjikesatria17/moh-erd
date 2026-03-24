#!/usr/bin/env bash
set -euo pipefail

# Fix common Laravel permission issues after manual extract on hosting.
#
# Example:
#   bash scripts/fix-permissions.sh --path /home/user/public_html/c-procurement
#   bash scripts/fix-permissions.sh --path /home/user/public_html/c-procurement --web-user www-data --web-group www-data

APP_PATH=""
WEB_USER=""
WEB_GROUP=""

usage() {
  cat <<'EOF'
Usage:
  bash scripts/fix-permissions.sh --path <laravel-root> [options]

Required:
  --path <laravel-root>    Path root project Laravel (folder yang berisi artisan).

Optional:
  --web-user <user>        User web server untuk chown (opsional).
  --web-group <group>      Group web server untuk chown (opsional).
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --path)
      APP_PATH="${2:-}"
      shift 2
      ;;
    --web-user)
      WEB_USER="${2:-}"
      shift 2
      ;;
    --web-group)
      WEB_GROUP="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$APP_PATH" ]]; then
  echo "Error: --path wajib diisi." >&2
  usage
  exit 1
fi

if [[ ! -f "$APP_PATH/artisan" ]]; then
  echo "Error: path tidak valid (artisan tidak ditemukan): $APP_PATH" >&2
  exit 1
fi

if [[ -n "$WEB_USER" && -z "$WEB_GROUP" ]]; then
  WEB_GROUP="$WEB_USER"
fi

find "$APP_PATH" -type d -exec chmod 755 {} +
find "$APP_PATH" -type f -exec chmod 644 {} +

mkdir -p "$APP_PATH/storage/app" \
         "$APP_PATH/storage/framework/cache" \
         "$APP_PATH/storage/framework/sessions" \
         "$APP_PATH/storage/framework/views" \
         "$APP_PATH/storage/logs" \
         "$APP_PATH/bootstrap/cache"

chmod -R 775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
chmod 755 "$APP_PATH/artisan" || true

if [[ -n "$WEB_USER" ]]; then
  chown -R "$WEB_USER:$WEB_GROUP" "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" "$APP_PATH" || true
fi

echo "Permission fix selesai untuk: $APP_PATH"
