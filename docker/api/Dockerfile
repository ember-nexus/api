# PHP is compiled from source in the `php_base` stage below instead of reusing the prebuilt
# `dunglas/frankenphp:*-alpine` image, for control over the build (pinned/reproducible toolchain, SQL
# stripped out, etc.) and to apply the SHA-NI patches further down -- building vanilla source without them
# still leaves ext/hash's SHA-256 on the slow path on Alpine/musl (~330 MB/s, same as the prebuilt image).
#
# `php_base`'s RUN steps are adapted from https://github.com/docker-library/php (MIT licensed), with all
# SQL-related configure flags/extensions removed (this project has no SQL dependency) and ZTS + embed kept
# enabled (required by FrankenPHP). The `frankenphp_builder` stage is adapted from FrankenPHP's own
# https://github.com/dunglas/frankenphp/blob/main/alpine.Dockerfile (MIT licensed), building FrankenPHP's
# Go binary via xcaddy/cgo against `php_base` instead of against docker-library's own PHP image.
#
# Helper scripts in ./php-embed/ (docker-php-source, docker-php-ext-*, docker-php-entrypoint) are copied
# verbatim from docker-library/php, unmodified.

ARG ALPINE_VERSION='3.24'
ARG PHP_VERSION='8.5.13'
# see https://github.com/dunglas/frankenphp/releases
ARG FRANKENPHP_VERSION='1.13.5'
# pinned to a full patch version (not just "1.26") so `golang:${GO_VERSION}-alpine` resolves to the exact
# same toolchain on every rebuild, matching the Go version FrankenPHP v1.12.7 itself builds with.
ARG GO_VERSION='1.27.4'
# composer is only needed while building (dev image, `composer install` of the production image); it is never part of the production image
ARG COMPOSER_VERSION='2'
# expr-cli is an independent, statically linked Go binary (see ./lib/ExprCli) and does not share FrankenPHP's
# toolchain pin above; it is built with the latest Go release, pinned to a full patch version for reproducibility.
ARG EXPR_CLI_GO_VERSION='1.27.4'
# pinned to a full patch version; used both for the xcaddy/caddy-builder image (which supplies the xcaddy build
# tool) and passed to `xcaddy build` as the embedded Caddy core version, so the binary's Caddy version is known at
# build time. It is exposed at runtime as the CADDY_VERSION environment variable, which the healthcheck reads
# instead of executing the (UPX-packed, slow to start) frankenphp binary.
ARG CADDY_VERSION='2.11.9'


FROM caddy:${CADDY_VERSION}-builder-alpine AS caddy_builder


FROM golang:${GO_VERSION}-alpine AS golang_base


FROM composer:${COMPOSER_VERSION} AS composer_bin


FROM alpine:${ALPINE_VERSION} AS frankenphp_source
ARG FRANKENPHP_VERSION
RUN apk add --no-cache curl tar
RUN set -eux; \
    mkdir -p /usr/local/src/frankenphp; \
    curl -fsSL "https://github.com/dunglas/frankenphp/archive/refs/tags/v${FRANKENPHP_VERSION}.tar.gz" -o /tmp/frankenphp.tar.gz; \
    tar -xzf /tmp/frankenphp.tar.gz -C /usr/local/src/frankenphp --strip-components=1; \
    rm /tmp/frankenphp.tar.gz


FROM alpine:${ALPINE_VERSION} AS php_base
LABEL org.opencontainers.image.source="https://github.com/docker-library/php" \
    org.opencontainers.image.title="Custom PHP, ZTS + embed, no SQL" \
    org.opencontainers.image.description="PHP compiled from source using the docker-library/php Alpine ZTS recipe (MIT licensed), with all SQL-related extensions/flags removed."

ARG PHP_VERSION

# dependencies required for running "phpize"
# these get automatically installed and removed by "docker-php-ext-*" (unless they're already installed)
ENV PHPIZE_DEPS="\
		autoconf \
		dpkg-dev dpkg \
		file \
		g++ \
		gcc \
		libc-dev \
		make \
		pkgconf \
		re2c" \
    PHP_INI_DIR="/usr/local/etc/php" \
# https://github.com/docker-library/php/issues/272, -D_LARGEFILE_SOURCE and -D_FILE_OFFSET_BITS=64
# (https://www.php.net/manual/en/intro.filesystem.php); -DHAVE_FUNC_ATTRIBUTE_TARGET=1 works around
# configure's flaky GCC attribute probe (see https://github.com/php/php-src/issues/15384) never granting
# ext/hash's SHA-256 a hardware-accelerated code path on Alpine; GCC there does support the attribute in
# real code, so this skips the probe instead of trusting it. Harmless on arm64, where the gated code is
# scoped to x86 anyway.
    PHP_CFLAGS="-fstack-protector-strong -fpic -fpie -O2 -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64 -DHAVE_FUNC_ATTRIBUTE_TARGET=1" \
    PHP_CPPFLAGS="-fstack-protector-strong -fpic -fpie -O2 -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64 -DHAVE_FUNC_ATTRIBUTE_TARGET=1" \
    PHP_LDFLAGS="-Wl,-O1 -pie" \
    PHP_BUILD_PROVIDER="https://github.com/ember-nexus/api" \
    PHP_UNAME="Linux - Docker" \
    GPG_KEYS="1198C0117593497A5EC5C199286AF1F9897469DC 49D9AF6BC72A80D6691719C8AA23F5BE9C7097D4 D95C03BC702BE9515344AE3374E44BC9067701A5" \
    PHP_VERSION="${PHP_VERSION}" \
    PHP_URL="https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz" \
    PHP_ASC_URL="https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz.asc" \
# pinned to PHP_VERSION 8.5.13; bump together when bumping the ARG above, see https://github.com/docker-library/php
    PHP_SHA256="607602acaf0c3061818e9916ec44a058e8b3d43959a83862b9cd3ec7bf5b785f"

# persistent / runtime deps; curl, tar and xz are only needed while building and are not installed permanently
RUN apk add --no-cache ca-certificates

# the container runs as the dedicated `worker` user (created in the `php` stage); there is no www-data user and
# /var/www/html does not need to be world writable
RUN set -eux; \
	mkdir -p "$PHP_INI_DIR/conf.d" /var/www/html

RUN set -eux; \
	apk add --no-cache --virtual .fetch-deps curl gnupg; \
	mkdir -p /usr/src; \
	cd /usr/src; \
	curl -fsSL -o php.tar.xz "$PHP_URL"; \
	echo "$PHP_SHA256 *php.tar.xz" | sha256sum -c -; \
	curl -fsSL -o php.tar.xz.asc "$PHP_ASC_URL"; \
	export GNUPGHOME="$(mktemp -d)"; \
	for key in $GPG_KEYS; do \
		gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$key"; \
	done; \
	gpg --batch --verify php.tar.xz.asc php.tar.xz; \
	gpgconf --kill all; \
	rm -rf "$GNUPGHOME"; \
	apk del --no-network .fetch-deps

COPY ./docker/api/php-embed/docker-php-source /usr/local/bin/
COPY ./docker/api/php-embed/patches /usr/local/src/php-patches

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		argon2-dev \
		coreutils \
		curl-dev \
		gnu-libiconv-dev \
		libsodium-dev \
		libxml2-dev \
		linux-headers \
		openssl-dev \
		patch \
		sed \
		tar \
		xz \
		zlib-dev \
	; \
# make sure musl's iconv doesn't get used (https://www.php.net/manual/en/intro.iconv.php)
	rm -vf /usr/include/iconv.h; \
	export \
		CFLAGS="$PHP_CFLAGS" \
		CPPFLAGS="$PHP_CPPFLAGS" \
		LDFLAGS="$PHP_LDFLAGS" \
	; \
	docker-php-source extract; \
	cd /usr/src/php; \
# ext/hash's hash_update_stream() reads in a 1024-byte buffer; once SHA-256 itself is SHA-NI-accelerated, the
# per-read stream-wrapper overhead dominates for large upload chunks. Bump it to 16KiB, the point where
# throughput plateaus (measured; larger buffers regress from allocation/copy overhead). Only the buffer used
# for stream hashing is touched; hash()/hash_hmac()/hash_update_file() keep their own buf[1024].
	sed -i '/char buf\[1024\];/{N;s/char buf\[1024\];\n\([ \t]*\)zend_long toread = 1024;/char buf[16384];\n\1zend_long toread = 16384;/}' ext/hash/hash.c; \
	grep -q 'char buf\[16384\];' ext/hash/hash.c; \
	grep -q 'zend_long toread = 16384;' ext/hash/hash.c; \
	[ "$(grep -c 'char buf\[1024\];' ext/hash/hash.c)" = 3 ]; \
# SHA256Transform() (the SHA-NI dispatch this patch targets) re-checks the CPU feature support on every
# 64-byte block, unlike other SIMD-dispatched code in php-src that resolves it once. This patch moves the
# check into a one-time static flag, in line with that existing convention.
	patch -p1 --forward < /usr/local/src/php-patches/0001-hash-sha-minit-shani-resolver.patch; \
	gnuArch="$(dpkg-architecture --query DEB_BUILD_GNU_TYPE)"; \
	test "$PHP_INI_DIR" != "${PHP_INI_DIR%/php}"; \
	./configure \
		--build="$gnuArch" \
		--sysconfdir="${PHP_INI_DIR%/php}" \
		--with-config-file-path="$PHP_INI_DIR" \
		--with-config-file-scan-dir="$PHP_INI_DIR/conf.d" \
# make sure invalid --configure-flags are fatal errors instead of just warnings
		--enable-option-checking=fatal \
# only the extensions listed below are built, plus the always enabled core ones (Core, date, hash, json, pcre,
# random, Reflection, SPL, standard, uri, Zend OPcache)
		--disable-all \
# https://github.com/docker-library/php/issues/822
		--with-pic \
# --enable-mbstring is included here because otherwise there's no way to get pecl to use it properly (see https://github.com/docker-library/php/issues/195)
# mbregex is not used and would pull in oniguruma
		--enable-mbstring \
		--disable-mbregex \
# https://wiki.php.net/rfc/argon2_password_hash
		--with-password-argon2 \
# https://wiki.php.net/rfc/libsodium
		--with-sodium=shared \
# required by symfony/*, phpunit and composer
		--enable-ctype \
		--enable-dom \
		--enable-fileinfo \
		--enable-filter \
		--enable-phar \
		--enable-posix \
		--enable-simplexml \
		--enable-tokenizer \
		--enable-xml \
		--enable-xmlreader \
		--enable-xmlwriter \
		--with-libxml \
		--with-curl \
		--with-iconv=/usr \
		--with-openssl \
		--with-zlib \
# in PHP 7.4+, the pecl/pear installers are officially deprecated (requiring an explicit "--with-pear")
		--with-pear \
# https://externals.io/message/118859
		--disable-zend-signals \
# required by FrankenPHP; the CGI SAPI is not built at all (FrankenPHP replaces it), nor is phpdbg
		--enable-embed \
		--enable-zts \
		--disable-cgi \
		--disable-phpdbg \
# bundled pcre does not support JIT on s390x
# https://manpages.debian.org/bullseye/libpcre3-dev/pcrejit.3.en.html#AVAILABILITY_OF_JIT_SUPPORT
		$(test "$gnuArch" = 's390x-linux-musl' && echo '--without-pcre-jit') \
	; \
	make -j "$(nproc)"; \
	find -type f -name '*.a' -delete; \
	make install; \
	find \
		/usr/local \
		-type f \
		-perm '/0111' \
		-exec sh -euxc ' \
			strip --strip-all "$@" || : \
		' -- '{}' + \
	; \
	make clean; \
# https://github.com/docker-library/php/issues/692 (copy default example "php.ini" files somewhere easily discoverable)
	cp -v php.ini-* "$PHP_INI_DIR/"; \
	cd /; \
	docker-php-source delete; \
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache $runDeps; \
	apk del --no-network .build-deps; \
# update pecl channel definitions https://github.com/docker-library/php/issues/443
	pecl update-channels; \
	rm -rf /tmp/pear ~/.pearrc; \
# smoke test
	php --version

# informational only (never fails the build): reports whether ext/hash's SHA-256 actually got the SHA-NI
# hardware path this time (see the -DHAVE_FUNC_ATTRIBUTE_TARGET=1 workaround above). The sample is generated
# in memory, nothing is read from or stored in the repo.
RUN if [ "$(uname -m)" = "x86_64" ]; then \
		php -r ' \
			$data = str_repeat(random_bytes(1024 * 1024), 32); \
			$t0 = microtime(true); \
			hash("sha256", $data); \
			$t1 = microtime(true); \
			$mbPerSec = (strlen($data) / 1024 / 1024) / ($t1 - $t0); \
			fwrite(STDERR, sprintf("[php_base] ext/hash SHA-256 throughput: %.0f MB/s -- %s\n", $mbPerSec, $mbPerSec > 700 ? "SHA-NI accelerated" : "generic/SSE2 fallback, no hardware acceleration")); \
		'; \
	fi

COPY ./docker/api/php-embed/docker-php-ext-* ./docker/api/php-embed/docker-php-entrypoint /usr/local/bin/
COPY ./docker/api/php-embed/php.prod.ini "$PHP_INI_DIR/php.ini"

# sodium was built as a shared module (so that it can be replaced later if so desired), so let's enable it too (https://github.com/docker-library/php/issues/598)
RUN docker-php-ext-enable sodium



FROM php_base AS frankenphp_builder
ARG FRANKENPHP_VERSION
ARG CADDY_VERSION
ARG GO_VERSION

COPY --from=caddy_builder /usr/bin/xcaddy /usr/bin/xcaddy
COPY --from=golang_base /usr/local/go /usr/local/go
ENV PATH="/usr/local/go/bin:${PATH}" \
    GOTOOLCHAIN=local \
    GOBIN=/usr/local/bin

# same build deps as https://github.com/dunglas/frankenphp/blob/main/alpine.Dockerfile's "builder" stage, minus
# sqlite-dev (this project's php_base has no SQL support to link against), cmake/libcap (no watcher, no setcap),
# brotli-dev (no brotli), readline-dev and oniguruma-dev (PHP is built without them)
RUN apk add --no-cache --virtual .frankenphp-build-deps \
		$PHPIZE_DEPS \
		argon2-dev \
		bash \
		coreutils \
		curl-dev \
		git \
		gnu-libiconv-dev \
		libsodium-dev \
		libstdc++ \
		libxml2-dev \
		linux-headers \
		openssl-dev \
		upx

COPY --from=frankenphp_source /usr/local/src/frankenphp /go/src/app
WORKDIR /go/src/app
RUN go mod download
WORKDIR /go/src/app/caddy
RUN go mod download
WORKDIR /go/src/app

# see also https://github.com/docker-library/php/blob/master/8.3/alpine3.20/zts/Dockerfile#L53-L55 and
# https://github.com/dunglas/frankenphp/blob/main/alpine.Dockerfile
# build tags: nobadger/nomysql/nopgx drop unused storage backends, nomercure drops the (AGPL licensed) Mercure hub,
# nowatcher drops the file watcher (no worker mode hot reload, so no e-dant/watcher and no libwatcher) and nobrotli
# drops the brotli dependency (responses are compressed with zstd and gzip only, see the Caddyfiles)
# no setcap: the container is meant to run with all capabilities dropped; binding to port 80 works through docker's
# default `net.ipv4.ip_unprivileged_port_start=0`
RUN CGO_ENABLED=1 \
    XCADDY_GO_BUILD_FLAGS="-tags=nobadger,nomysql,nopgx,nomercure,nowatcher,nobrotli -ldflags=\"-w -s -extldflags '-Wl,-z,stack-size=0x800000' -X 'github.com/caddyserver/caddy/v2.CustomVersion=FrankenPHP ${FRANKENPHP_VERSION} PHP ${PHP_VERSION} Caddy' -X 'github.com/caddyserver/caddy/v2/modules/caddyhttp.ServerHeader=FrankenPHP Caddy'\"" \
    CGO_CFLAGS="-DFRANKENPHP_VERSION=${FRANKENPHP_VERSION} $(php-config --includes) $PHP_CFLAGS" \
    CGO_CPPFLAGS="$PHP_CPPFLAGS" \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs) -lssl -lcrypto -largon2 -lcurl -lz $PHP_LDFLAGS" \
    xcaddy build "v${CADDY_VERSION}" \
        --output /usr/local/bin/frankenphp \
        --with github.com/dunglas/frankenphp=./ \
        --with github.com/dunglas/frankenphp/caddy=./caddy/ \
        --with github.com/baldinof/caddy-supervisor \
    && /usr/local/bin/frankenphp version \
    && /usr/local/bin/frankenphp version | grep -q "Caddy v${CADDY_VERSION} " \
    && /usr/local/bin/frankenphp build-info \
    && strip --strip-all /usr/local/bin/frankenphp \
    && upx --best --lzma /usr/local/bin/frankenphp \
    && /usr/local/bin/frankenphp version


FROM golang:${EXPR_CLI_GO_VERSION}-alpine AS expr_cli_builder
COPY ./lib/ExprCli /build
RUN cd /build && \
    apk add upx && \
    go mod download && \
    go build -ldflags="-s -w" -o expr-cli main.go && \
    upx --best --lzma expr-cli


FROM php_base AS php
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
ARG CADDY_VERSION
ENV CADDY_VERSION=${CADDY_VERSION}

# runtime libraries only: curl, tar, xz, the openssl CLI, pcre and libcap are not needed at runtime (libcurl/libssl/libz
# are pulled in as dependencies of the compiled extensions). tar and xz are build-only, docker-php-ext-install unpacks
# the PHP sources with them.
RUN apk add --no-cache \
        ca-certificates \
        icu \
        icu-data-full \
        libstdc++ \
        libzip \
        mailcap \
        supercronic \
        zlib \
    && apk add --no-cache --virtual .build-deps \
        ${PHPIZE_DEPS} \
        curl-dev \
        icu-dev \
        libzip-dev \
        linux-headers \
        openssl-dev \
        tar \
        xz \
        zlib-dev \
# only plain key/document access is used, so client side encryption, snappy, zstd and SASL are not built (TLS and zlib stay)
    && pecl install -D 'with-mongodb-system-libs="no" with-mongodb-client-side-encryption="no" with-mongodb-snappy="no" with-mongodb-zlib="system" with-mongodb-zstd="no" with-mongodb-sasl="no" with-mongodb-ssl="openssl"' mongodb-2.5.7 \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl bcmath sockets zip \
    && docker-php-ext-enable mongodb intl bcmath sockets zip \
    && apk del .build-deps

COPY --from=frankenphp_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp

ENV GODEBUG=cgocheck=0 \
    XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data \
    APP_ENV=prod

COPY ./docker/api/Caddyfile /etc/frankenphp/Caddyfile
COPY ./docker/api/docker-entrypoint.sh /usr/local/bin/
COPY ./docker/api/supercronic /etc/crontabs/supercronic
COPY --from=expr_cli_builder /build/expr-cli /usr/local/bin/expr-cli
RUN set -x \
    && mkdir -p /config/caddy /data/caddy \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && adduser -D worker \
	&& /usr/local/bin/frankenphp version \
	&& chown -R worker:worker /config/caddy /data/caddy /etc/crontabs/supercronic

STOPSIGNAL SIGTERM

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

WORKDIR /var/www/html
RUN rm -rf /app



FROM php AS production-build

COPY lib/Antlr/dist /var/www/html/lib/Antlr/dist
COPY bin/console /var/www/html/bin/console
COPY config /var/www/html/config
COPY public /var/www/html/public
COPY src /var/www/html/src
COPY lib/EmberNexusBundle /var/www/html/lib/EmberNexusBundle
COPY docker/api/.env.docker /var/www/html/.env
COPY ./docker/api/php-embed/php.prod.ini "$PHP_INI_DIR/php.ini"
COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
COPY LICENSE /var/www/html/LICENSE
# composer is mounted from its own stage for this single step, so it never ends up in an image layer
RUN --mount=from=composer_bin,source=/usr/bin/composer,target=/usr/local/bin/composer \
    APP_ENV=prod COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-cache \
    && rm -rf /var/www/html/var/cache \
    && mkdir -p /var/www/html/var/cache \
    && rm -rf /usr/local/include/php \
    && mv /usr/local/lib/php/extensions /usr/local/lib/php_extensions \
    && rm -rf /usr/local/lib/php \
    && mkdir /usr/local/lib/php \
    && mv /usr/local/lib/php_extensions /usr/local/lib/php/extensions \
    && rm -rf /tmp/pear \
    && rm -rf /usr/src \
    && rm -f /usr/local/bin/pear /usr/local/bin/peardev /usr/local/bin/pecl /usr/local/bin/phar /usr/local/bin/phar.phar \
        /usr/local/bin/phpize /usr/local/bin/php-config /usr/local/bin/docker-php-source /usr/local/bin/docker-php-ext-* \
    && find /var/www/html/vendor/symfony/emoji/Resources/data -type f -name '*.php' ! -name 'emoji-en.php' -delete \
    && php /var/www/html/vendor/symfony/emoji/Resources/bin/compress \
    && rm -rf /var/cache/apk /var/log /lib/apk/db /root/.composer \
    && chown worker:worker -R /var/www/html

RUN set -eux; \
    emojiDataSizeInBytes="$(du -sb /var/www/html/vendor/symfony/emoji/Resources/data | cut -f1)"; \
    [ "$emojiDataSizeInBytes" -le 102400 ]



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
ARG CADDY_VERSION
ENV CADDY_VERSION=${CADDY_VERSION}
ENV APP_ENV=prod

COPY --from=production-build / /

STOPSIGNAL SIGTERM

HEALTHCHECK --interval=30s --timeout=3s \
  CMD php bin/console healthcheck

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

EXPOSE 80

WORKDIR /var/www/html

USER worker


FROM php AS development

ENV APP_ENV=dev

COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer

# the dev image has no vendor/ installed by default (it's provided by the mounted source instead), so supercronic's
# scheduled `php bin/console cron` runs would fail until `composer install` is run manually; the dev Caddyfile drops
# the `supervisor` block so supercronic never starts
COPY ./docker/api/Caddyfile.dev /etc/frankenphp/Caddyfile

COPY ./docker/api/php-embed/php.dev.ini "$PHP_INI_DIR/php.ini"

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS judy-dev bsd-compat-headers linux-headers \
    && pecl install xdebug-3.5.3 \
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

HEALTHCHECK NONE
