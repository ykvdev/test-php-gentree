FROM php:7.1-cli

# Use the default development configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

#RUN apt-get update && apt-get install -y wget git unzip nano htop mc

#RUN docker-php-ext-install -j$(nproc) pdo_mysql \
#    && docker-php-ext-install -j$(nproc) mysqli \
#    && docker-php-ext-install -j$(nproc) pcntl \
#    && docker-php-ext-install -j$(nproc) exif

#RUN pecl install xdebug && docker-php-ext-enable xdebug

#RUN apt-get update && apt-get install -y \
#        libfreetype6-dev \
#        libjpeg62-turbo-dev \
#        libpng-dev \
#    && docker-php-ext-configure gd --with-freetype --with-jpeg \
#    && docker-php-ext-install -j$(nproc) gd

#RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
#    && pecl install memcached \
#    && docker-php-ext-enable memcached

#RUN apt-get install -y zlib1g-dev libicu-dev g++ \
#    && docker-php-ext-configure intl \
#    && docker-php-ext-install intl

RUN wget https://getcomposer.org/installer -O - -q | php -- --install-dir=/bin --filename=composer --quie \
    composer install