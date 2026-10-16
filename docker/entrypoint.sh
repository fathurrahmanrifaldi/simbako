#!/bin/sh
set -e

# Pastikan folder runtime storage dan bootstrap/cache ada
mkdir -p /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/bootstrap/cache

# Atur permission
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Generate storage symlink jika belum ada
php artisan storage:link --force || true

# Optimasi cache Laravel untuk environment produksi
if [ "$APP_ENV" = "production" ]; then
    echo "==> Mengoptimasi cache Laravel..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Jalankan migrasi database otomatis saat deploy
if [ -n "$DB_HOST" ] || [ -n "$DATABASE_URL" ]; then
    echo "==> Menjalankan migrasi database..."
    php artisan migrate --force || echo "Migrasi dilewati atau gagal (cek koneksi DB)."
fi

echo "==> Memulai FrankenPHP server..."
exec "$@"
