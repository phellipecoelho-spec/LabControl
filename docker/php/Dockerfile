FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    linux-headers \
    && docker-php-ext-install \
    pdo_pgsql \
    zip \
    bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

RUN addgroup -g 1000 -S labcontrol && \
    adduser -u 1000 -S labcontrol -G labcontrol && \
    mkdir -p /var/www/backend/storage && \
    chown -R labcontrol:labcontrol /var/www/backend

USER labcontrol

EXPOSE 9000

CMD ["php-fpm"]
