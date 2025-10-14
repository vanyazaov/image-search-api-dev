FROM php:8.3-fpm

# Устанавливаем зависимости в одном RUN слое для уменьшения размера образа
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    unzip \
    libssl-dev \
    zlib1g-dev \
    libbrotli-dev \
    pkg-config \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        zip \
        pcntl \
        sockets \
        gd \
    && pecl install \
        swoole \
        redis \
    && docker-php-ext-enable \
        swoole \
        redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Настройка рабочего каталога
WORKDIR /var/www/html

CMD ["php-fpm"]

