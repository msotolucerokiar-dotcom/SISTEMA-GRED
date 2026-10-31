FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql opcache \
    && apk del $PHPIZE_DEPS \
    && curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN printf 'upload_max_filesize=8M\npost_max_size=26M\nmax_file_uploads=10\n' > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/app
