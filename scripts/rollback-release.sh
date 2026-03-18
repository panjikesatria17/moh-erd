#!/usr/bin/env bash
set -euo pipefail

# Rollback active release by repointing current symlink.
#
# Examples:
#   bash scripts/rollback-release.sh --app-root /home/satriame/apps/c-procurement
#   bash scripts/rollback-release.sh --app-root /home/satriame/apps/c-procurement --release-name 20260314125958

APP_ROOT=""
RELEASE_NAME=""
PHP_BIN="php"
RUN_OPTIMIZE=1

usage() {
  cat <<'EOF'
Usage:
  bash scripts/rollback-release.sh --app-root <path> [options]

Required:
  --app-root <path>         Root aplikasi dengan layout releases/current/shared.

Optional:
  --release-name <name>     Target release tertentu. Jika kosong, otomatis rollback ke release sebelumnya.
  --php-bin <bin>           Binary PHP (default: php).
  --skip-optimize           Tidak menjalankan optimize clear/cache setelah rollback.

Layout expected:
  <app-root>/
    releases/<release-name>
    current -> releases/<release-name>
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --app-root)
      APP_ROOT="${2:-}"
      shift 2
      ;;
    --release-name)
      RELEASE_NAME="${2:-}"
      shift 2
      ;;
    --php-bin)
      PHP_BIN="${2:-php}"
      shift 2
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

if [[ -z "$APP_ROOT" ]]; then
  echo "Error: --app-root wajib diisi." >&2
  usage
  exit 1
fi

RELEASES_DIR="$APP_ROOT/releases"
CURRENT_LINK="$APP_ROOT/current"

if [[ ! -d "$RELEASES_DIR" ]]; then
  echo "Error: releases dir tidak ditemukan: $RELEASES_DIR" >&2
  exit 1
fi

if [[ ! -L "$CURRENT_LINK" ]]; then
  echo "Error: current bukan symlink: $CURRENT_LINK" >&2
  exit 1
fi

mapfile -t RELEASE_LIST < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d | sort)
if (( ${#RELEASE_LIST[@]} == 0 )); then
  echo "Error: tidak ada release di $RELEASES_DIR" >&2
  exit 1
fi

CURRENT_TARGET="$(readlink -f "$CURRENT_LINK")"
if [[ -z "$CURRENT_TARGET" || ! -d "$CURRENT_TARGET" ]]; then
  echo "Error: current symlink target tidak valid." >&2
  exit 1
fi

TARGET_DIR=""

if [[ -n "$RELEASE_NAME" ]]; then
  TARGET_DIR="$RELEASES_DIR/$RELEASE_NAME"
  if [[ ! -d "$TARGET_DIR" ]]; then
    echo "Error: target release tidak ditemukan: $TARGET_DIR" >&2
    exit 1
  fi
else
  CURRENT_INDEX=-1
  for i in "${!RELEASE_LIST[@]}"; do
    if [[ "$(readlink -f "${RELEASE_LIST[$i]}")" == "$CURRENT_TARGET" ]]; then
      CURRENT_INDEX="$i"
      break
    fi
  done

  if (( CURRENT_INDEX == -1 )); then
    echo "Error: current release tidak ditemukan di daftar releases." >&2
    exit 1
  fi

  if (( CURRENT_INDEX == 0 )); then
    echo "Error: tidak ada release sebelumnya untuk rollback." >&2
    exit 1
  fi

  TARGET_DIR="${RELEASE_LIST[$((CURRENT_INDEX - 1))]}"
fi

if [[ "$(readlink -f "$TARGET_DIR")" == "$CURRENT_TARGET" ]]; then
  echo "Info: target rollback sama dengan current, tidak ada perubahan."
  exit 0
fi

ln -sfn "$TARGET_DIR" "$CURRENT_LINK"

if [[ "$RUN_OPTIMIZE" -eq 1 ]]; then
  if [[ -f "$TARGET_DIR/artisan" ]]; then
    (
      cd "$TARGET_DIR"
      "$PHP_BIN" artisan optimize:clear
      "$PHP_BIN" artisan config:cache
      "$PHP_BIN" artisan route:cache
      "$PHP_BIN" artisan view:cache
    )
  else
    echo "Warning: artisan tidak ditemukan di target release, skip optimize."
  fi
fi

echo "Rollback selesai."
echo "Previous current: $CURRENT_TARGET"
echo "New current: $(readlink -f "$CURRENT_LINK")"
