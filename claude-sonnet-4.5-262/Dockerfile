FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    curl \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN mkdir -p runtime/logs runtime/cache db && \
    chmod -R 777 runtime db && \
    php yii migrate --interactive=0

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "web"]