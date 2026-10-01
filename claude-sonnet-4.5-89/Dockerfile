FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN mkdir -p runtime/logs runtime/cache data && \
    chmod -R 777 runtime data

RUN php yii migrate/up --interactive=0

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "web"]