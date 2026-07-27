FROM php:8.5-fpm

ARG USER=app
ARG UID=1000

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    netcat-openbsd \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    cron \
    --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    bcmath \
    xml \
    zip \
    intl \
    opcache \
    exif \
    pcntl && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Redis extension (from PECL)
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/laravel.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-laravel.conf

# Entrypoint
COPY docker/entrypoint/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create application user
RUN useradd -m -u ${UID} -s /bin/bash ${USER} && \
    mkdir -p /var/www && \
    chown -R ${USER}:${USER} /var/www

# Create log directory
RUN mkdir -p /var/log/laravel && chown -R ${USER}:${USER} /var/log/laravel

WORKDIR /var/www

USER ${USER}

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
