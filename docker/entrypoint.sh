#!/usr/bin/env bash
# Tidak pakai `set -e`: satu langkah prep yang gagal TIDAK boleh mencegah Apache
# menyala. Kalau Apache tidak menyala, healthcheck /up pasti gagal.

# --- 1. Apache dengarkan port dari Railway ($PORT dinamis) ---
PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${PORT}/"     /etc/apache2/ports.conf
sed -i "s/\*:8080>/\*:${PORT}>/"         /etc/apache2/sites-available/000-default.conf
# Redam warning "Could not determine server's fully qualified domain name"
grep -q "ServerName" /etc/apache2/apache2.conf || echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo ">> Apache akan listen di port ${PORT}"

# --- 2. Pastikan ada .env berisi HANYA baris APP_KEY= ---
# key:generate hanya bisa mengganti baris "APP_KEY=" yang sudah ada.
# Semua konfigurasi lain (DB, dll) tetap diambil dari Variables Railway (env asli).
if [ ! -f .env ]; then
    printf 'APP_KEY=\n' > .env
fi

# --- 3. APP_KEY wajib ada, kalau tidak app 500 di rute yang pakai session ---
if [ -z "${APP_KEY}" ]; then
    echo ">> APP_KEY kosong -> generate sementara. SET APP_KEY di Variables Railway agar sesi tidak hilang tiap deploy."
    php artisan key:generate --force 2>&1 || echo ">> WARNING: key:generate gagal"
fi

# --- 4. Cache konfigurasi (semua non-fatal) ---
php artisan config:clear 2>&1 || true
php artisan config:cache 2>&1 || echo ">> WARNING: config:cache gagal"
php artisan route:cache  2>&1 || echo ">> WARNING: route:cache gagal"
php artisan view:cache   2>&1 || echo ">> WARNING: view:cache gagal"
php artisan storage:link 2>&1 || true

# --- 5. Migrasi DB dengan retry, TIDAK menggagalkan boot ---
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    n=0
    until [ "$n" -ge 10 ]; do
        if php artisan migrate --force 2>&1; then
            echo ">> Migrasi berhasil."
            break
        fi
        n=$((n+1))
        echo ">> DB belum siap, retry migrate ($n/10) dalam 3 dtk..."
        sleep 3
    done
    [ "$n" -ge 10 ] && echo ">> WARNING: migrate gagal 10x (cek DB_* env). App tetap dilayani."
fi

# --- 6. Jalankan Apache (foreground) ---
echo ">> Menjalankan Apache..."
exec apache2-foreground
