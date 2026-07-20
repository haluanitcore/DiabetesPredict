#!/usr/bin/env bash
# Prinsip: Apache HARUS menyala secepatnya. Tidak ada langkah (termasuk migrasi
# DB) yang boleh memblokir start Apache -> kalau tidak, healthcheck timeout.

# --- 1. Apache dengarkan port dari Railway ($PORT dinamis) ---
PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${PORT}/"     /etc/apache2/ports.conf
sed -i "s/\*:8080>/\*:${PORT}>/"         /etc/apache2/sites-available/000-default.conf
grep -q "ServerName" /etc/apache2/apache2.conf || echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo ">> Apache akan listen di port ${PORT}"

# --- 1b. Cegah APP_URL placeholder tak valid (mis. "https://") -> "Invalid URI" ---
case "${APP_URL}" in
    ""|"http://"|"https://")
        export APP_URL="http://localhost"
        echo ">> APP_URL tidak valid/placeholder -> sementara http://localhost. Set APP_URL ke domain Railway (mis. https://xxx.up.railway.app)."
        ;;
esac

# --- 2. Pastikan ada .env berisi HANYA baris APP_KEY= ---
if [ ! -f .env ]; then
    printf 'APP_KEY=\n' > .env
fi

# --- 3. APP_KEY wajib ada ---
if [ -z "${APP_KEY}" ]; then
    echo ">> APP_KEY kosong -> generate sementara. SET APP_KEY di Variables Railway."
    php artisan key:generate --force 2>&1 || echo ">> WARNING: key:generate gagal"
fi

# --- 4. Cache konfigurasi & view (TIDAK menyentuh DB, cepat) ---
php artisan config:clear 2>&1 || true
php artisan config:cache 2>&1 || echo ">> WARNING: config:cache gagal"
php artisan view:cache   2>&1 || echo ">> WARNING: view:cache gagal"
php artisan storage:link 2>&1 || true

# --- 5. Migrasi DB di BACKGROUND (tidak memblokir Apache/healthcheck) ---
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    (
        n=0
        until [ "$n" -ge 20 ]; do
            if php artisan migrate --force 2>&1; then
                echo ">> [bg] Migrasi berhasil."
                break
            fi
            n=$((n+1))
            echo ">> [bg] DB belum siap, retry migrate ($n/20) dalam 5 dtk..."
            sleep 5
        done
        [ "$n" -ge 20 ] && echo ">> [bg] WARNING: migrate gagal 20x (cek DB_* env)."
    ) &
fi

# --- 6. Jalankan Apache (foreground, jadi PID 1) SEKARANG ---
echo ">> Menjalankan Apache di port ${PORT}..."
exec apache2-foreground
