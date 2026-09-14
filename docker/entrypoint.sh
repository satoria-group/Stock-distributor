#!/bin/sh
set -e

cd /var/www/html

# storage/ biasanya dipasang sebagai named volume yang awalnya kosong, jadi
# struktur direktorinya perlu dibangun ulang setiap container start.
mkdir -p storage/app/private/livewire-tmp \
         storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Bind mount dari Windows (Docker Desktop) tidak mendukung chown/chmod.
# Jangan biarkan `set -e` mematikan container hanya karena itu.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Hanya terjadi di development: bind mount dari host belum punya vendor/.
# Image production sudah membawa vendor/ hasil build, jadi blok ini dilewati.
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ tidak ditemukan — menjalankan composer install..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ "${APP_ENV}" = "production" ]; then
    echo "[entrypoint] Menyiapkan cache produksi..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Migrasi TIDAK otomatis. Menjalankan migrate di setiap container start
    # berbahaya saat ada lebih dari satu replika. Aktifkan secara sadar.
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "[entrypoint] Menjalankan migrasi database..."
        php artisan migrate --force
    fi
else
    # Development: pastikan tidak ada cache basi yang menutupi perubahan .env
    php artisan config:clear >/dev/null 2>&1 || true
fi

exec "$@"
