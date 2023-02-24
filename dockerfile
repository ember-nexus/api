FROM nginx/unit:1.29.0-php8.1

RUN apt update \
    && apt install -y --no-install-recommends libssl-dev git libzip-dev zip \
    && pecl install mongodb \
    && docker-php-ext-install zip \
    && docker-php-ext-enable mongodb zip \
    && apt clean \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && mkdir -p /srv/eon \
    && chown unit:unit /srv/eon \
    && chmod 777 /srv/eon

COPY unit.config.json /docker-entrypoint.d/config.json

WORKDIR /srv/eon

COPY . /srv/eon/

RUN chown -R www-data:www-data /srv/eon/

USER unit

ENV APP_ENV=prod

RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress \
    && composer clear-cache \
    && mkdir -p var/cache var/log \
    && composer dump-autoload --classmap-authoritative --no-dev \
    && composer dump-env prod \
    && composer run-script --no-dev post-install-cmd

EXPOSE 80

USER root
