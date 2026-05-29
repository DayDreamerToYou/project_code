FROM php:8.4-fpm-alpine

LABEL maintainer="Fishery System"
LABEL description="Backend container for Fishery Data Management System"

RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    vim \
    nginx \
    mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    tzdata

ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        opcache \
        mbstring \
        xml \
        bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

COPY php.ini /usr/local/etc/php/php.ini

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8001/api/health || exit 1

EXPOSE 8001

CMD php artisan serve --host=0.0.0.0 --port=8001
