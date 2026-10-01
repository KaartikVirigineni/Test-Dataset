FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chmod -R 777 writable

EXPOSE 8000

CMD ["php", "spark", "serve", "--host=0.0.0.0", "--port=8000"]