FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# System deps
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpng-dev \
    libzip-dev \
    nodejs \
    npm \
    oniguruma-dev \
    supervisor \
    zip \
    unzip

# PHP extensions
RUN docker-php-ext-install \
    bcmath \
    intl \
    mbstring \
    pdo_mysql \
    zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App files
COPY . .

# Install PHP deps
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Build frontend assets
RUN npm ci && npm run build

# Permissions
RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache public/build

# Configure PHP-FPM to listen on 0.0.0.0:9000
RUN sed -i 's/^listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Entrypoint: migrate + cache + serve
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
