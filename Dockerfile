FROM php:8.3.9-fpm

RUN apt-get update \
    && apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql zip

# RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/html

COPY composer.lock composer.json /var/www/html/

USER www-data

RUN composer install --no-interaction --no-scripts --no-autoloader

COPY --chown=www-data:www-data . /var/www/html/

USER root

# RUN chown www-data:www-data -R /var/www/html
RUN chmod -R 777 /var/www/html/storage
RUN chmod -R 777 /var/www/html/bootstrap/cache

USER www-data

RUN composer dump-autoload --optimize

USER root

CMD ["php-fpm"]
