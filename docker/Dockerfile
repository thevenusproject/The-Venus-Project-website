FROM wordpress:4.8.1-fpm

MAINTAINER Aleksey Chuprov <ilovemistakes@mail.ru>

RUN apt-get update \
    && apt-get install -y libxml2-dev \
    && docker-php-ext-install soap \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug soap \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_enable_trigger=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
