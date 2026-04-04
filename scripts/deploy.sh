#!/usr/bin/env bash
set -euo pipefail

APP_SLUG="${APP_SLUG:-sapa-lppm}"
APP_DOMAIN="${APP_DOMAIN:-sapa.unisap.ac.id}"
HOME_DIR="${HOME_DIR:-$HOME}"

REPO_DIR="${REPO_DIR:-$HOME_DIR/repositories/$APP_SLUG}"
APP_DIR="${APP_DIR:-$HOME_DIR/apps/$APP_SLUG}"
RELEASES_DIR="$APP_DIR/releases"
SHARED_DIR="$APP_DIR/shared"
CURRENT_LINK="$APP_DIR/current"
PUBLIC_HTML="${PUBLIC_HTML:-$HOME_DIR/domains/$APP_DOMAIN/public_html}"
ENV_SOURCE="${ENV_SOURCE:-$SHARED_DIR/.env}"
RELEASE_ID="${RELEASE_ID:-$(date +%Y%m%d-%H%M%S)}"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"

echo "[1/8] Validasi path..."
if [ ! -d "$REPO_DIR" ]; then
    echo "Repo tidak ditemukan: $REPO_DIR"
    exit 1
fi

mkdir -p "$RELEASES_DIR" "$SHARED_DIR"

echo "[2/8] Update repo hosting..."
git -C "$REPO_DIR" fetch origin main
git -C "$REPO_DIR" reset --hard origin/main

echo "[3/8] Siapkan release baru: $RELEASE_DIR"
mkdir -p "$RELEASE_DIR"
cp -a "$REPO_DIR"/. "$RELEASE_DIR"/
rm -rf "$RELEASE_DIR/.git"

echo "[4/8] Pasang file .env jika tersedia..."
if [ -f "$ENV_SOURCE" ]; then
    cp "$ENV_SOURCE" "$RELEASE_DIR/.env"
else
    echo "Peringatan: .env shared tidak ditemukan di $ENV_SOURCE"
fi

echo "[5/8] Install dependency production jika composer tersedia..."
if command -v composer >/dev/null 2>&1; then
    (
        cd "$RELEASE_DIR"
        composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    )
else
    echo "Peringatan: composer tidak ditemukan. Pastikan vendor sudah tersedia atau install manual."
fi

echo "[6/8] Update symlink current..."
ln -sfn "$RELEASE_DIR" "$CURRENT_LINK"

echo "[7/8] Update symlink public_html..."
if [ -e "$PUBLIC_HTML" ] && [ ! -L "$PUBLIC_HTML" ]; then
    echo "Path public_html sudah ada dan bukan symlink: $PUBLIC_HTML"
    echo "Hapus atau ubah manual dulu, lalu jalankan ulang script."
    exit 1
fi
ln -sfn "$CURRENT_LINK" "$PUBLIC_HTML"

echo "[8/8] Selesai."
echo "Current : $CURRENT_LINK"
echo "Public  : $PUBLIC_HTML"
echo "Release : $RELEASE_DIR"
