FROM php:7.4-fpm

RUN apt update && apt install -y wget git unzip libxml2-dev libicu-dev
RUN pecl install xdebug
RUN docker-php-ext-install pdo mysqli pdo_mysql soap intl
RUN docker-php-ext-enable xdebug

ADD ./php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www

RUN wget https://getcomposer.org/installer -O - -q | php -- --install-dir=/bin --filename=composer --quiet
RUN curl -sS https://get.symfony.com/cli/installer | bash && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

USER 1000:1000