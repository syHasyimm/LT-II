#!/usr/bin/env bash
# Pembaruan aplikasi di server produksi. Jalankan dari folder aplikasi sebagai
# pengguna pemilik file (mis. www-data atau pengguna deploy):
#   bash deploy/deploy.sh
set -euo pipefail

cd "$(dirname "$0")/.."

echo "→ Mode pemeliharaan"
php artisan down --retry=30 --refresh=15 || true
trap 'php artisan up' EXIT

echo "→ Ambil kode terbaru"
git pull --ff-only

echo "→ Dependensi PHP (tanpa paket dev)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "→ Build aset frontend"
npm ci --no-audit --no-fund
npm run build

echo "→ Migrasi database"
php artisan migrate --force

echo "→ Cache konfigurasi, route, view, event"
php artisan optimize

echo "→ Restart queue worker agar memakai kode baru"
php artisan queue:restart

echo "✓ Selesai"
