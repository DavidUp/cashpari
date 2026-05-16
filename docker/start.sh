#!/bin/sh
set -e

echo "==============================="
echo "  BetLab - Starting up..."
echo "==============================="

# Create nginx temp directories
mkdir -p /tmp/nginx_client /tmp/nginx_proxy /tmp/nginx_fastcgi

# Run Laravel setup commands
echo "--> Caching config..."
cd /app/core
php artisan config:cache --no-interaction || echo "Config cache failed (non-fatal)"
php artisan route:cache --no-interaction  || echo "Route cache failed (non-fatal)"
php artisan view:cache  --no-interaction  || echo "View cache failed (non-fatal)"

echo "--> Setting permissions..."
chown -R www-data:www-data /app/core/storage /app/core/bootstrap/cache 2>/dev/null || true
chmod -R 775 /app/core/storage /app/core/bootstrap/cache 2>/dev/null || true

echo "--> Starting PHP-FPM..."
php-fpm -D

echo "--> Starting Nginx..."
exec nginx -g "daemon off;"
