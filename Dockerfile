# syntax=docker/dockerfile:1

# =============================================================================
# base — runtime PHP + ekstensi. Dipakai bersama oleh development & production.
# =============================================================================
FROM php:8.4-fpm-alpine AS base

WORKDIR /var/www/html

# Library runtime yang harus tetap ada di image akhir.
RUN apk add --no-cache \
        libpq \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        icu-libs

# Header & toolchain hanya dibutuhkan saat compile ekstensi, dibuang lagi
# setelah selesai supaya image tidak membawa beban build.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
    # Header libpq berganti nama antar generasi Alpine: `libpq-dev` di versi
    # baru, `postgresql-dev` di versi lama. Coba keduanya supaya build tidak
    # bergantung pada versi Alpine yang kebetulan dipakai base image.
    && (apk add --no-cache --virtual .pq-dev libpq-dev \
        || apk add --no-cache --virtual .pq-dev postgresql-dev) \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        exif \
    && apk del .build-deps .pq-dev

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# =============================================================================
# development — dipakai docker-compose.yml. Source code masuk lewat bind mount,
# jadi image ini sengaja TIDAK membawa vendor/ maupun aset hasil build.
# =============================================================================
FROM base AS development

ENV APP_ENV=local

RUN apk add --no-cache git nodejs npm

# node_modules dipasang di dalam image supaya binari native-nya milik Linux.
# docker-compose.yml memasang volume anonim di /var/www/html/node_modules yang
# diinisialisasi dari isi image ini — itulah yang melindungi container dari
# node_modules milik host Windows (@esbuild/win32-x64, @rollup/...-msvc) yang
# tidak bisa dieksekusi di Alpine. Tanpa baris ini volume tersebut kosong dan
# npm di dalam container tidak bisa dipakai.
COPY package.json package-lock.json ./
RUN npm ci

# =============================================================================
# builder — menyiapkan vendor/ dan aset Vite untuk production.
#
# Composer dijalankan lebih dulu karena tailwind.config.js memindai
# ./vendor/laravel/framework/.../Pagination/resources/views/*.blade.php.
# Tanpa vendor/, Tailwind diam-diam menghasilkan CSS tanpa kelas paginasi.
# =============================================================================
FROM base AS builder

RUN apk add --no-cache git nodejs npm

COPY . .

RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

RUN npm ci && npm run build

# =============================================================================
# production — image mandiri: kode, vendor, dan aset sudah ter-bake di dalam.
# Tidak ada bind mount, jadi tidak ada composer install saat container start.
# =============================================================================
FROM base AS production

ENV APP_ENV=production

COPY . .
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache

# =============================================================================
# web — nginx untuk production.
#
# public/ disalin dari stage production yang sama persis, sehingga nginx dan
# PHP-FPM dijamin menyajikan revisi kode yang identik. Ini menggantikan bind
# mount ./:/var/www/html:ro yang sebelumnya mengambil kode dari host.
# =============================================================================
FROM nginx:alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public
