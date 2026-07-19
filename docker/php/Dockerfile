FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    zip \
    unzip \
    curl \
    git \
    linux-headers \
    autoconf \
    gcc \
    g++ \
    make \
    && docker-php-ext-install \
    pdo_pgsql \
    zip \
    bcmath \
    gd \
    && pecl install redis && docker-php-ext-enable redis \
    && apk del autoconf gcc g++ make

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 -S labcontrol && \
    adduser -u 1000 -S labcontrol -G labcontrol

WORKDIR /var/www/backend

USER labcontrol

EXPOSE 9000

CMD ["php-fpm"]
