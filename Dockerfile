# =============================================================================
# Hubnet TIRUAN — image untuk deployment di VPS PLD.
#
# Dipindahkan dari host lamanya (202.155.132.181) karena FortiGate di jaringan
# VPS memblokir SETIAP permintaan bernama ke IP itu — bukan hanya .nip.io, tapi
# nama apa pun, termasuk A record baru. Yang lolos hanya permintaan tanpa
# hostname, dan itu tak terpakai karena sertifikatnya cuma untuk nama lama.
#
# Kaki OAuth yang mematikan adalah yang ketiga: pld-user memanggil
# /sso/oauth/token dan /sso/api/user dari DALAM VPS. Dengan IdP tinggal serumah,
# panggilan itu jadi hairpin lokal dan tak pernah melewati FortiGate sama sekali.
# =============================================================================

# ---- aset Vite ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
# Setelan jaringan yang sama dengan pld-backoffice: runner/host di lingkungan ini
# pernah menjatuhkan npm ci dengan ECONNRESET pada 15 koneksi paralel bawaan.
RUN npm config set maxsockets 5 \
 && npm config set fetch-retries 5 \
 && npm config set fetch-retry-mintimeout 20000 \
 && npm config set fetch-retry-maxtimeout 120000 \
 && npm ci --no-audit --no-fund
COPY . .
RUN npm run build

# ---- runtime: Apache + mod_php ----
# 8.4, bukan 8.3 seperti yang tertulis di `require` composer.json: composer.lock
# sudah mengunci komponen Symfony 8 yang menuntut php >=8.4.1, jadi build di 8.3
# gagal di composer install dengan 17 konflik platform sekaligus.
FROM php:8.4-apache
# Ekstensi dipasang SEBELUM composer install supaya composer memverifikasi
# platform sungguhan, bukan dilewati dengan --ignore-platform-reqs.
#
# pdo_mysql WAJIB: migrasi aplikasi ini menuliskan collate 'utf8mb4_unicode_ci'
# eksplisit, jadi SQLite (yang aktif bawaan di image resmi) menolaknya dengan
# "no such collation sequence". mbstring juga tidak aktif bawaan, dan Laravel
# menuntutnya.
RUN apt-get update && apt-get install -y --no-install-recommends \
      libzip-dev libicu-dev libonig-dev unzip git \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip intl bcmath opcache \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Laravel melayani dari public/, bukan akar repo. Tanpa ini seluruh berkas
# aplikasi — termasuk .env — terekspos lewat HTTP.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!\${APACHE_DOCUMENT_ROOT}!g" \
      /etc/apache2/sites-available/*.conf \
      /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
      > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
 && rm -f .env \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
