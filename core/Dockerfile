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

# Copy the entire project into /app
# The web root is /app (Files/) and Laravel core is at /app/core
WORKDIR /app

# Copy everything from the repo root (Files/)
COPY . /app

# Install Laravel dependencies
RUN cd /app/core && composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions on Laravel storage
RUN chown -R www-data:www-data /app/core/storage /app/core/bootstrap/cache \
    && chmod -R 775 /app/core/storage /app/core/bootstrap/cache

# Copy nginx config
COPY core/docker/nginx.conf /etc/nginx/nginx.conf

# Copy startup script
COPY core/docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
