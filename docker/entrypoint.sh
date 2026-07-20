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

# Migrasi database dengan retry (DB mungkin belum siap saat container start).
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    n=0
    until [ "$n" -ge 10 ]; do
        if php artisan migrate --force; then
            echo ">> Migrasi berhasil."
            break
        fi
        n=$((n+1))
        echo ">> DB belum siap, retry migrate ($n/10) dalam 3 dtk..."
        sleep 3
    done
    [ "$n" -ge 10 ] && echo ">> WARNING: migrate gagal setelah 10 percobaan (cek DB_* env)."
fi

exec apache2-foreground
