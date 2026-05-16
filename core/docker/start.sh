#!/bin/sh
set -e

echo "==> Starting BetLab..."

# Create symlink for storage
php artisan storage:link --force 2>/dev/null || true

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
nginx -g "daemon off;"
