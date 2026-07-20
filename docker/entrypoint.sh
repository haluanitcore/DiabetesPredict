#!/usr/bin/env bash
set -e

# Railway menyuntikkan $PORT secara dinamis. Apache harus mendengarkan port itu.
PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${PORT}/"            /etc/apache2/ports.conf
sed -i "s/\*:8080>/\*:${PORT}>/"                /etc/apache2/sites-available/000-default.conf

# Pastikan APP_KEY ada (kalau lupa di-set, generate sekali — lebih baik set manual di env)
if [ -z "${APP_KEY}" ]; then
    echo ">> APP_KEY kosong, generate sementara (SET manual di Railway untuk produksi)"
    php artisan key:generate --force || true
fi

# Cache konfigurasi/route/view untuk performa produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Symlink storage publik (aman diulang)
php artisan storage:link || true

# Migrasi database (idempotent). Set RUN_MIGRATIONS=false untuk menonaktifkan.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force || echo ">> WARNING: migrate gagal (cek koneksi DB)"
fi

exec apache2-foreground
