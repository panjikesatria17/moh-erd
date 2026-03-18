#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   bash scripts/build-deploy-zip.sh
#   bash scripts/build-deploy-zip.sh --with-vendor
#   bash scripts/build-deploy-zip.sh --name my-app --output dist

APP_NAME="c-procurement"
OUTPUT_DIR="dist"
WITH_VENDOR=0
WITH_NODE_MODULES=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --name)
      APP_NAME="${2:-}"
      shift 2
      ;;
    --output)
      OUTPUT_DIR="${2:-}"
      shift 2
      ;;
    --with-vendor)
      WITH_VENDOR=1
      shift
      ;;
    --with-node-modules)
      WITH_NODE_MODULES=1
      shift
      ;;
    *)
      echo "Unknown option: $1" >&2
      exit 1
      ;;
  esac
done

if ! command -v zip >/dev/null 2>&1; then
  echo "Command 'zip' tidak ditemukan. Install zip dulu." >&2
  exit 1
fi

mkdir -p "$OUTPUT_DIR"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
ARCHIVE_PATH="$OUTPUT_DIR/${APP_NAME}-${TIMESTAMP}.zip"

EXCLUDES=(
  ".git/*"
  ".github/*"
  ".vscode/*"
  "dist/*"
  "node_modules/*"
  "storage/logs/*"
  "storage/framework/cache/*"
  "storage/framework/sessions/*"
  "storage/framework/testing/*"
  "storage/framework/views/*"
  "storage/app/private/*"
  ".env"
  "*.zip"
)

if [[ "$WITH_VENDOR" -eq 0 ]]; then
  EXCLUDES+=("vendor/*")
fi

if [[ "$WITH_NODE_MODULES" -eq 1 ]]; then
  # Hapus exclude node_modules jika user memang ingin menyertakan
  FILTERED=()
  for item in "${EXCLUDES[@]}"; do
    if [[ "$item" != "node_modules/*" ]]; then
      FILTERED+=("$item")
    fi
  done
  EXCLUDES=("${FILTERED[@]}")
fi

zip -r "$ARCHIVE_PATH" . -x "${EXCLUDES[@]}"

echo "Paket deploy berhasil dibuat: $ARCHIVE_PATH"
echo "Catatan: file .git tidak ikut, jadi aman untuk extract di hosting."
