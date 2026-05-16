FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        gd \
        zip \
        bcmath \
        pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Web root is /app (contains index.php, assets/, install/, core/)
WORKDIR /app

# Copy the entire Files/ directory
COPY . /app

# Install Laravel dependencies in core/
RUN cd /app/core && composer install --no-dev --optimize-autoloader --no-interaction

# Set Laravel permissions
RUN chown -R www-data:www-data /app/core/storage /app/core/bootstrap/cache \
    && chmod -R 775 /app/core/storage /app/core/bootstrap/cache

# Nginx config
RUN mkdir -p /etc/nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
