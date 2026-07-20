# syntax=docker/dockerfile:1
# =============================================================================
# DiaPredict — Production image (Laravel 13 + PHP 8.3 + Python ML inference)
# Target: Railway (Docker deploy). Single container: Apache + PHP + Python venv.
# =============================================================================

# ---------- Stage 1: Build frontend assets (Vite + Tailwind) ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build


# ---------- Stage 2: Install PHP (composer) dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: artisan tidak boleh jalan sebelum kode lengkap tersalin
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-interaction


# ---------- Stage 3: Final runtime image ----------
# PHP 8.4: composer.lock mewajibkan PHP >= 8.4.0 (platform_check). PHP 8.3 -> fatal.
FROM php:8.4-apache

# System packages + Python runtime (untuk model ML) + ekstensi PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-pip python3-venv \
        libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
        libzip-dev zip unzip git curl default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath gd zip exif \
    && a2enmod rewrite headers \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- Python virtual environment untuk inference model ---
ENV PY_VENV=/opt/venv
RUN python3 -m venv "$PY_VENV"
ENV PATH="$PY_VENV/bin:$PATH"
COPY model/requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir --upgrade pip && pip install --no-cache-dir -r /tmp/requirements.txt
# Laravel akan memanggil python lewat env ini (lihat AnalysisController)
ENV PYTHON_PATH="$PY_VENV/bin/python"

WORKDIR /var/www/html

# Salin kode aplikasi + dependency yang sudah dibuild
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Vendor sudah disalin dari stage 2. Jalankan package discovery pakai artisan
# (base image tidak punya composer). Non-fatal: runtime bisa auto-discover.
RUN php artisan package:discover --ansi || true

# Apache docroot -> /public, dan izin storage
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
