FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN mkdir -p runtime/logs runtime/cache database web/assets && \
    chmod -R 777 runtime database web/assets && \
    php yii migrate --interactive=0 && \
    php yii rbac/init

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "web"]