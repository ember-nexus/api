ARG FRANKENPHP_VERSION='1.11.1'
ARG PHP_VERSION='8.5.1'

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-builder-php${PHP_VERSION}-alpine AS frankenphp_builder
COPY --from=caddy:2-builder-alpine /usr/bin/xcaddy /usr/bin/xcaddy
RUN CGO_ENABLED=1 \
    XCADDY_SETCAP=1 \
    XCADDY_GO_BUILD_FLAGS="-tags=nobadger,nomysql,nopgx -ldflags='-w -s -extldflags \'-Wl,-z,stack-size=0x80000\''" \
    CGO_CFLAGS=$(php-config --includes) \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
    xcaddy build \
        --output /usr/local/bin/frankenphp \
        --with github.com/dunglas/frankenphp=./ \
        --with github.com/dunglas/frankenphp/caddy=./caddy/ \
        --with github.com/dunglas/caddy-cbrotli \
        --with github.com/baldinof/caddy-supervisor


FROM golang:1.25-alpine AS go_builder
COPY ./lib/ExprCli /build
RUN cd /build && \
    apk add upx && \
    go mod download && \
    go build -ldflags="-s -w" -o expr-cli main.go && \
    upx --best --lzma expr-cli


FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-alpine AS php
# see also https://github.com/opencontainers/image-spec/blob/main/annotations.md
LABEL org.opencontainers.image.vendor="Ember Nexus" \
    org.opencontainers.image.authors="Sören Klein / Syndesi <soerenklein98@gmail.com>" \
    org.opencontainers.image.licenses="LicenseRef-Source-First-License-1.1" \
    org.opencontainers.image.source="https://github.com/ember-nexus/api" \
    org.opencontainers.image.title="API" \
    org.opencontainers.image.description="Knowledge Graph API."
# The environment variable VERSION is set to dev by default, only release builds get their actual release version set.
# Background: If the en user uses the dev mode, it is epected that he knows what he does.
ARG VERSION=dev
ENV VERSION=${VERSION}
ARG FRANKENPHP_VERSION
ENV FRANKENPHP_VERSION=${FRANKENPHP_VERSION}

COPY --from=frankenphp_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
RUN apk add --no-cache \
		ca-certificates \
		curl \
        icu \
        libzip \
# https://github.com/docker-library/php/issues/494
		openssl \
        pcre \
        supercronic \
		tar \
		xz \
        zlib \
    && apk add --no-cache --virtual .build-deps \
        ${PHPIZE_DEPS} \
        curl-dev \
        icu-dev \
        libzip-dev \
        linux-headers \
        openssl-dev \
        pcre-dev \
        pkgconf \
        zlib-dev \
    && pecl install mongodb-2.1.4 \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl bcmath sockets zip \
    && docker-php-ext-enable mongodb intl bcmath sockets zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apk del .build-deps

COPY ./docker/Caddyfile /etc/frankenphp/Caddyfile
COPY ./docker/docker-entrypoint.sh /usr/local/bin/
COPY ./docker/supercronic /etc/crontabs/supercronic
COPY --from=go_builder /build/expr-cli /usr/local/bin/expr-cli
RUN set -x \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && adduser -D worker \
	&& setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
	&& chown -R worker:worker /config/caddy /data/caddy /etc/crontabs/supercronic

STOPSIGNAL SIGTERM

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

WORKDIR /var/www/html
RUN rm -rf /app



FROM php AS production-build

COPY antlr/dist /var/www/html/antlr/dist
COPY bin/console /var/www/html/bin/console
COPY config /var/www/html/config
COPY public /var/www/html/public
COPY src /var/www/html/src
COPY lib/EmberNexusBundle /var/www/html/lib/EmberNexusBundle
COPY docker/.env.docker /var/www/html/.env
COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
COPY LICENSE /var/www/html/LICENSE
RUN APP_ENV=prod COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-cache \
    && rm -rf /usr/local/include/php \
    && mv /usr/local/lib/php/extensions /usr/local/lib/php_extensions \
    && rm -rf /usr/local/lib/php \
    && mkdir /usr/local/lib/php \
    && mv /usr/local/lib/php_extensions /usr/local/lib/php/extensions \
    && rm -rf /tmp/pear \
    && rm -rf /usr/src \
    && rm /usr/local/bin/php-cgi \
    && rm /usr/local/bin/phpdbg \
    && chown worker:worker -R /var/www/html



FROM scratch AS production
# see also https://github.com/opencontainers/image-spec/blob/main/annotations.md
LABEL org.opencontainers.image.vendor="Ember Nexus" \
    org.opencontainers.image.authors="Sören Klein / Syndesi <soerenklein98@gmail.com>" \
    org.opencontainers.image.licenses="LicenseRef-Source-First-License-1.1" \
    org.opencontainers.image.source="https://github.com/ember-nexus/api" \
    org.opencontainers.image.title="API" \
    org.opencontainers.image.description="Knowledge Graph API."
# The environment variable VERSION is set to dev by default, only release builds get their actual release version set.
# Background: If the en user uses the dev mode, it is epected that he knows what he does.
ARG VERSION=dev
ENV VERSION=${VERSION}
ARG FRANKENPHP_VERSION
ENV FRANKENPHP_VERSION=${FRANKENPHP_VERSION}

COPY --from=production-build / /

STOPSIGNAL SIGTERM

HEALTHCHECK --interval=30s --timeout=3s \
  CMD php bin/console healthcheck

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

EXPOSE 80

WORKDIR /var/www/html

USER worker


FROM php AS development

COPY ./docker/php-embed/php.dev.ini "$PHP_INI_DIR/php.ini"

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS judy-dev bsd-compat-headers linux-headers \
    && pecl install xdebug-3.5.0 \
    && docker-php-ext-enable xdebug \
    && apk del --no-network .build-deps \
    && apk add --no-cache judy aha git openssh openjdk21-jdk maven wget bash \
    && wget https://www.antlr.org/download/antlr-4.13.2-complete.jar -O /usr/local/lib/antlr-4.13.2-complete.jar

RUN mkdir /tmp/rrd-antlr4-build && \
    cd /tmp/rrd-antlr4-build && \
    git clone https://github.com/bkiers/rrd-antlr4.git . && \
    mvn clean package && \
    mv target/rrd-antlr4-0.1.2.jar /usr/local/lib/rrd-antlr4-0.1.2.jar && \
    cd / && \
    rm -rf /tmp/rrd-antlr4-build

EXPOSE 80

USER worker

ENV CLASSPATH=".:/usr/local/lib/antlr-4.13.2-complete.jar:/usr/local/lib/rrd-antlr4-0.1.2.jar:$CLASSPATH"

HEALTHCHECK --interval=30s --timeout=3s \
  CMD php bin/console healthcheck
