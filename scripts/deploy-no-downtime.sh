#!/usr/bin/env bash
set -euo pipefail

# Zero-downtime style deployment for Laravel using releases + current symlink.
#
# Example:
#   bash scripts/deploy-no-downtime.sh \
#     --archive /home/satriame/c-procurement-20260314-124958.zip \
#     --app-root /home/satriame/apps/c-procurement

ARCHIVE=""
APP_ROOT=""
RELEASE_NAME=""
KEEP_RELEASES=5
PHP_BIN="php"
COMPOSER_BIN="composer"
RUN_MIGRATE=1
RUN_COMPOSER=1
RUN_OPTIMIZE=1

usage() {
  cat <<'EOF'
Usage:
  bash scripts/deploy-no-downtime.sh --archive <zip> --app-root <path> [options]

Required:
  --archive <zip>         Path ke file zip hasil build deploy.
  --app-root <path>       Root aplikasi di server (akan dibuat releases/current/shared).

Optional:
  --release-name <name>   Nama release manual (default: yyyymmddHHMMSS).
  --keep <n>              Simpan n release terakhir (default: 5).
  --php-bin <bin>         Binary PHP (default: php).
  --composer-bin <bin>    Binary composer (default: composer).
  --skip-migrate          Tidak menjalankan migrate.
  --skip-composer         Tidak menjalankan composer install.
  --skip-optimize         Tidak menjalankan optimize clear/cache.

Layout:
  <app-root>/
    releases/<release-name>
    shared/.env
    shared/storage
    current -> releases/<release-name>
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --archive)
      ARCHIVE="${2:-}"
      shift 2
      ;;
    --app-root)
      APP_ROOT="${2:-}"
      shift 2
      ;;
    --release-name)
      RELEASE_NAME="${2:-}"
      shift 2
      ;;
    --keep)
      KEEP_RELEASES="${2:-5}"
      shift 2
      ;;
    --php-bin)
      PHP_BIN="${2:-php}"
      shift 2
      ;;
    --composer-bin)
      COMPOSER_BIN="${2:-composer}"
      shift 2
      ;;
    --skip-migrate)
      RUN_MIGRATE=0
      shift
      ;;
    --skip-composer)
      RUN_COMPOSER=0
      shift
      ;;
    --skip-optimize)
      RUN_OPTIMIZE=0
      shift
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

if [[ -z "$ARCHIVE" || -z "$APP_ROOT" ]]; then
  echo "Error: --archive and --app-root wajib diisi." >&2
  usage
  exit 1
fi

if [[ ! -f "$ARCHIVE" ]]; then
  echo "Error: archive tidak ditemukan: $ARCHIVE" >&2
  exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
  echo "Error: command unzip tidak ditemukan." >&2
  exit 1
fi

if [[ -z "$RELEASE_NAME" ]]; then
  RELEASE_NAME="$(date +%Y%m%d%H%M%S)"
fi

if ! [[ "$KEEP_RELEASES" =~ ^[0-9]+$ ]]; then
  echo "Error: --keep harus angka." >&2
  exit 1
fi

RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
CURRENT_LINK="$APP_ROOT/current"
NEW_RELEASE_DIR="$RELEASES_DIR/$RELEASE_NAME"

mkdir -p "$RELEASES_DIR" "$SHARED_DIR/storage" "$APP_ROOT"
mkdir -p "$SHARED_DIR/storage/app" \
         "$SHARED_DIR/storage/framework/cache" \
         "$SHARED_DIR/storage/framework/sessions" \
         "$SHARED_DIR/storage/framework/views" \
         "$SHARED_DIR/storage/logs"

if [[ ! -f "$SHARED_DIR/.env" ]]; then
  if [[ -f "$APP_ROOT/.env" ]]; then
    cp "$APP_ROOT/.env" "$SHARED_DIR/.env"
    echo "Info: shared .env dibuat dari $APP_ROOT/.env"
  else
    echo "Warning: $SHARED_DIR/.env belum ada. Buat manual sebelum akses aplikasi."
    touch "$SHARED_DIR/.env"
  fi
fi

if [[ -e "$NEW_RELEASE_DIR" ]]; then
  echo "Error: release sudah ada: $NEW_RELEASE_DIR" >&2
  exit 1
fi

mkdir -p "$NEW_RELEASE_DIR"
unzip -q "$ARCHIVE" -d "$NEW_RELEASE_DIR"

rm -rf "$NEW_RELEASE_DIR/storage"
ln -sfn "$SHARED_DIR/storage" "$NEW_RELEASE_DIR/storage"
rm -f "$NEW_RELEASE_DIR/.env"
ln -sfn "$SHARED_DIR/.env" "$NEW_RELEASE_DIR/.env"

if [[ "$RUN_COMPOSER" -eq 1 ]]; then
  if [[ -f "$NEW_RELEASE_DIR/composer.json" ]]; then
    (
      cd "$NEW_RELEASE_DIR"
      "$COMPOSER_BIN" install --no-dev --prefer-dist --no-interaction --optimize-autoloader
    )
  else
    echo "Info: composer.json tidak ada, skip composer install."
  fi
fi

if [[ "$RUN_MIGRATE" -eq 1 ]]; then
  (
    cd "$NEW_RELEASE_DIR"
    "$PHP_BIN" artisan migrate --force
  )
fi

if [[ "$RUN_OPTIMIZE" -eq 1 ]]; then
  (
    cd "$NEW_RELEASE_DIR"
    "$PHP_BIN" artisan optimize:clear
    "$PHP_BIN" artisan config:cache
    "$PHP_BIN" artisan route:cache
    "$PHP_BIN" artisan view:cache
  )
fi

ln -sfn "$NEW_RELEASE_DIR" "$CURRENT_LINK"

if [[ -d "$RELEASES_DIR" ]]; then
  mapfile -t RELEASE_LIST < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d | sort)
  RELEASE_COUNT="${#RELEASE_LIST[@]}"
  if (( RELEASE_COUNT > KEEP_RELEASES )); then
    REMOVE_COUNT=$((RELEASE_COUNT - KEEP_RELEASES))
    for ((i=0; i<REMOVE_COUNT; i++)); do
      rm -rf "${RELEASE_LIST[$i]}"
    done
  fi
fi

echo "Deploy selesai."
echo "Current release: $NEW_RELEASE_DIR"
echo "Symlink current -> $CURRENT_LINK"
