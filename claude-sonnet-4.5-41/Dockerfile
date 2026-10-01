FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install intl pdo_sqlite zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN mkdir -p data tmp/cache/models tmp/cache/persistent logs \
    && chmod -R 775 data tmp logs \
    && chown -R www-data:www-data data tmp logs

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/webroot|g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/webroot>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    AllowOverride All' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf

RUN php bin/init_db.php

EXPOSE 80

CMD ["apache2-foreground"]