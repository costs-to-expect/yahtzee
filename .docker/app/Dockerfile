FROM php:8.2-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html
COPY .docker/app/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN docker-php-ext-install pdo_mysql bcmath
RUN apt-get update
RUN apt-get install -y git zip unzip

RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

WORKDIR /var/www/html
