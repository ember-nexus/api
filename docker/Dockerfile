FROM alpine:3.18 as php_embed
LABEL org.opencontainers.image.source="https://github.com/docker-library/php/tree/master/8.2/alpine3.18/cli" \
    org.opencontainers.image.title="Adapted php:8.2-cli-alpine3.18" \
    org.opencontainers.image.description="Based on php:8.2-cli-alpine3.18, without sql, with embed."

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
		zlib-dev \
        libzip-dev \
		icu-dev \
		linux-headers \
		re2c" \
    PHP_INI_DIR="/usr/local/etc/php" \
# Apply stack smash protection to functions using local buffers and alloca()
# Make PHP's main executable position-independent (improves ASLR security mechanism, and has no performance impact on x86_64)
# Enable optimization (-O2)
# Enable linker optimization (this sorts the hash buckets to improve cache locality, and is non-default)
# https://github.com/docker-library/php/issues/272
# -D_LARGEFILE_SOURCE and -D_FILE_OFFSET_BITS=64 (https://www.php.net/manual/en/intro.filesystem.php)
    PHP_CFLAGS="-fstack-protector-strong -fpic -fpie -O2 -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64" \
    PHP_CPPFLAGS="$PHP_CFLAGS" \
    PHP_LDFLAGS="-Wl,-O1 -pie" \
    GPG_KEYS="39B641343D8C104B2B146DC3F9C39DC0B9698544 E60913E4DF209907D8E30D96659A97C9CF2A795A 1198C0117593497A5EC5C199286AF1F9897469DC" \
    PHP_VERSION="8.2.11" \
    PHP_URL="https://www.php.net/distributions/php-8.2.11.tar.xz" \
    PHP_ASC_URL="https://www.php.net/distributions/php-8.2.11.tar.xz.asc" \
    PHP_SHA256="29af82e4f7509831490552918aad502697453f0869a579ee1b80b08f9112c5b8"

# persistent / runtime deps
RUN apk add --no-cache \
		ca-certificates \
		curl \
		tar \
		xz \
        bash \
        pcre \
        icu \
        libzip \
        zlib \
# https://github.com/docker-library/php/issues/494
		openssl

# ensure www-data user exists
RUN set -eux; \
	adduser -u 82 -D -S -G www-data www-data
# 82 is the standard uid/gid for "www-data" in Alpine
# https://git.alpinelinux.org/aports/tree/main/apache2/apache2.pre-install?h=3.14-stable
# https://git.alpinelinux.org/aports/tree/main/lighttpd/lighttpd.pre-install?h=3.14-stable
# https://git.alpinelinux.org/aports/tree/main/nginx/nginx.pre-install?h=3.14-stable

RUN set -eux; \
	mkdir -p "$PHP_INI_DIR/conf.d"; \
# allow running as an arbitrary user (https://github.com/docker-library/php/issues/743)
	[ ! -d /var/www/html ]; \
	mkdir -p /var/www/html; \
	chown www-data:www-data /var/www/html; \
	chmod 1777 /var/www/html

RUN set -eux; \
	\
	apk add --no-cache --virtual .fetch-deps gnupg; \
	\
	mkdir -p /usr/src; \
	cd /usr/src; \
	\
	curl -fsSL -o php.tar.xz "$PHP_URL"; \
	\
	if [ -n "$PHP_SHA256" ]; then \
		echo "$PHP_SHA256 *php.tar.xz" | sha256sum -c -; \
	fi; \
	\
	if [ -n "$PHP_ASC_URL" ]; then \
		curl -fsSL -o php.tar.xz.asc "$PHP_ASC_URL"; \
		export GNUPGHOME="$(mktemp -d)"; \
		for key in $GPG_KEYS; do \
			gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$key"; \
		done; \
		gpg --batch --verify php.tar.xz.asc php.tar.xz; \
		gpgconf --kill all; \
		rm -rf "$GNUPGHOME"; \
	fi; \
	\
	apk del --no-network .fetch-deps

COPY ./docker/php-embed/docker-php-source /usr/local/bin/

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		argon2-dev \
		coreutils \
		curl-dev \
		gnu-libiconv-dev \
		libsodium-dev \
		libxml2-dev \
		oniguruma-dev \
		openssl-dev \
		readline-dev \
	; \
	\
# make sure musl's iconv doesn't get used (https://www.php.net/manual/en/intro.iconv.php)
	rm -vf /usr/include/iconv.h; \
	\
	export \
		CFLAGS="$PHP_CFLAGS" \
		CPPFLAGS="$PHP_CPPFLAGS" \
		LDFLAGS="$PHP_LDFLAGS" \
	; \
	docker-php-source extract; \
	cd /usr/src/php; \
	gnuArch="$(dpkg-architecture --query DEB_BUILD_GNU_TYPE)"; \
	./configure \
		--build="$gnuArch" \
		--with-config-file-path="$PHP_INI_DIR" \
		--with-config-file-scan-dir="$PHP_INI_DIR/conf.d" \
		\
# make sure invalid --configure-flags are fatal errors instead of just warnings
		--enable-option-checking=fatal \
		\
# https://github.com/docker-library/php/issues/439
		--with-mhash \
		\
# https://github.com/docker-library/php/issues/822
		--with-pic \
		\
# --enable-mbstring is included here because otherwise there's no way to get pecl to use it properly (see https://github.com/docker-library/php/issues/195)
		--enable-mbstring \
# https://wiki.php.net/rfc/argon2_password_hash
		--with-password-argon2 \
# https://wiki.php.net/rfc/libsodium
		--with-sodium=shared \
# disable sqlite3, sql, pdo, ... \
        --disable-pdo \
        --without-sqlite3 \
        --without-pdo-sqlite \
        --without-pdo-mysql \
        --without-mysqli \
        --without-pdo-pgsql \
        --without-pdo-sqlite \
        --without-pdo-oci \
        --without-pdo-dblib \
        --without-pdo-odbc \
		\
		--with-curl \
		--with-iconv=/usr \
		--with-openssl \
		--with-readline \
		--with-zlib \
		\
# https://github.com/docker-library/php/pull/1259
		--enable-phpdbg \
		--enable-phpdbg-readline \
		\
# in PHP 7.4+, the pecl/pear installers are officially deprecated (requiring an explicit "--with-pear")
		--with-pear \
		\
# https://github.com/docker-library/php/pull/939#issuecomment-730501748
		--enable-embed \
        \
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
	\
# https://github.com/docker-library/php/issues/692 (copy default example "php.ini" files somewhere easily discoverable)
	cp -v php.ini-* "$PHP_INI_DIR/"; \
	\
	cd /; \
	docker-php-source delete; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache $runDeps; \
	\
	apk del --no-network .build-deps; \
	\
# update pecl channel definitions https://github.com/docker-library/php/issues/443
	pecl update-channels; \
	rm -rf /tmp/pear ~/.pearrc; \
	\
# smoke test
	php --version

COPY ./docker/php-embed/docker-php-ext-* /usr/local/bin/
COPY ./docker/php-embed/php.ini "$PHP_INI_DIR/php.ini"

# sodium was built as a shared module (so that it can be replaced later if so desired), so let's enable it too (https://github.com/docker-library/php/issues/598)
RUN docker-php-ext-enable sodium \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer



FROM php_embed as nginx_unit_builder
LABEL org.opencontainers.image.source="https://github.com/nginx/unit/blob/master/pkg/docker/Dockerfile.php8.1" \
    org.opencontainers.image.title="Adapted nginx/unit:8.1" \
    org.opencontainers.image.description="Based on nginx/unit:8.1, updated to PHP 8.2 and rewritten to work on Alpine."

RUN set -ex
RUN apk update
RUN apk add mercurial gcc g++ make pcre-dev openssl-dev
RUN mkdir -p /usr/lib/unit/modules /usr/lib/unit/debug-modules
RUN hg clone -u 1.31.1-1 https://hg.nginx.org/unit
RUN  cd unit \
    && NCPU="$(getconf _NPROCESSORS_ONLN)" \
    && CC_OPT="-fPIC" \
    && LD_OPT="-Wl,--as-needed" \
    && CONFIGURE_ARGS_MODULES="--prefix=/usr \
        --statedir=/var/lib/unit \
        --control=unix:/var/run/control.unit.sock \
        --pid=/var/run/unit.pid \
        --log=/var/log/unit.log \
        --tmpdir=/var/tmp \
        --user=unit \
        --group=unit \
        --openssl \
        --libdir=/usr/lib/amd64" \
    && CONFIGURE_ARGS="$CONFIGURE_ARGS_MODULES \
        --njs" \
    && make -j $NCPU -C pkg/contrib .njs \
    && export PKG_CONFIG_PATH=$(pwd)/pkg/contrib/njs/build \
    && ./configure $CONFIGURE_ARGS --cc-opt="$CC_OPT" --ld-opt="$LD_OPT" --modulesdir=/usr/lib/unit/debug-modules --debug \
    && make -j $NCPU unitd \
    && install -pm755 build/sbin/unitd /usr/sbin/unitd-debug \
    && make clean \
    && ./configure $CONFIGURE_ARGS --cc-opt="$CC_OPT" --ld-opt="$LD_OPT" --modulesdir=/usr/lib/unit/modules \
    && make -j $NCPU unitd \
    && install -pm755 build/sbin/unitd /usr/sbin/unitd \
    && make clean \
    && ./configure $CONFIGURE_ARGS_MODULES --cc-opt="$CC_OPT" --modulesdir=/usr/lib/unit/debug-modules --debug \
    && ./configure php \
    && make -j $NCPU php-install \
    && make clean \
    && ./configure $CONFIGURE_ARGS_MODULES --cc-opt="$CC_OPT" --modulesdir=/usr/lib/unit/modules \
    && ./configure php \
    && make -j $NCPU php-install \
    && cd ../ \
    && rm -rf ./unit \
    && ldd /usr/sbin/unitd | awk '/=>/{print $(NF-1)}' | while read n; do dpkg-query -S $n; done | sed 's/^\([^:]\+\):.*$/\1/' | sort | uniq > /requirements.apt



FROM php_embed as php
# see also https://github.com/opencontainers/image-spec/blob/main/annotations.md
LABEL org.opencontainers.image.vendor="Ember Nexus" \
    org.opencontainers.image.authors="Sören Klein / Syndesi <soerenklein98@gmail.com>" \
    org.opencontainers.image.licenses="GPL-3.0-only" \
    org.opencontainers.image.source="https://github.com/ember-nexus/api" \
    org.opencontainers.image.title="API" \
    org.opencontainers.image.description="Knowledge Graph API."
# The environment variable VERSION is set to dev by default, only release builds get their actual release version set.
# Background: If the en user uses the dev mode, it is epected that he knows what he does.
ARG VERSION=dev
ENV VERSION=${VERSION}

RUN apk add pcre-dev ${PHPIZE_DEPS} curl-dev openssl-dev \
    && pecl install mongodb \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl bcmath sockets zip \
    && docker-php-ext-enable mongodb intl bcmath sockets zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apk del pcre-dev ${PHPIZE_DEPS} curl-dev openssl-dev

COPY --from=nginx_unit_builder /usr/sbin/unitd /usr/sbin/unitd
COPY --from=nginx_unit_builder /usr/sbin/unitd-debug /usr/sbin/unitd-debug
COPY --from=nginx_unit_builder /usr/lib/unit/ /usr/lib/unit/
COPY --from=nginx_unit_builder /requirements.apt /requirements.apt
COPY ./docker/nginx-unit/unit-entrypoint.sh /usr/local/bin/
COPY ./docker/nginx-unit/unit.json /docker-entrypoint.d/unit.json
COPY ./docker/supervisord/supervisord.conf /etc/supervisord.conf
COPY ./docker/supervisord/docker-entrypoint.sh /usr/local/bin/
#RUN ldconfig # this command seems to not work on alpine & image works without it?
RUN set -x \
    && chmod +x /usr/local/bin/unit-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && if [ -f "/tmp/libunit.a" ]; then \
        mv /tmp/libunit.a /usr/lib/amd64/libunit.a; \
        rm -f /tmp/libunit.a; \
    fi \
    && mkdir -p /var/lib/unit/ \
    && addgroup --system unit \
    && adduser -D -S -G unit unit \
    && apk update \
    && apk add curl supervisor $(cat /requirements.apt) \
    && rm -f /requirements.apt \
    && ln -sf /dev/stdout /var/log/unit.log

STOPSIGNAL SIGTERM

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

WORKDIR /var/www/html



FROM php as production-build

COPY bin /var/www/html/bin
COPY config /var/www/html/config
COPY public /var/www/html/public
COPY src /var/www/html/src
COPY lib /var/www/html/lib
COPY .env.docker /var/www/html/.env
COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
COPY LICENSE /var/www/html/LICENSE
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader



FROM scratch as production
# see also https://github.com/opencontainers/image-spec/blob/main/annotations.md
LABEL org.opencontainers.image.vendor="Ember Nexus" \
    org.opencontainers.image.authors="Sören Klein / Syndesi <soerenklein98@gmail.com>" \
    org.opencontainers.image.licenses="GPL-3.0-only" \
    org.opencontainers.image.source="https://github.com/ember-nexus/api" \
    org.opencontainers.image.title="API" \
    org.opencontainers.image.description="Knowledge Graph API."
# The environment variable VERSION is set to dev by default, only release builds get their actual release version set.
# Background: If the en user uses the dev mode, it is epected that he knows what he does.
ARG VERSION=dev
ENV VERSION=${VERSION}

COPY --from=production-build / /

STOPSIGNAL SIGTERM

HEALTHCHECK --interval=30s --timeout=3s \
  CMD php bin/console healthcheck

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

WORKDIR /var/www/html


FROM php as development

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS judy-dev bsd-compat-headers \
    && pecl install xdebug-3.2.1 memprof \
    && docker-php-ext-enable xdebug memprof \
    && apk del --no-network .build-deps \
    && apk add --no-cache judy aha git openssh

HEALTHCHECK --interval=30s --timeout=3s \
  CMD php bin/console healthcheck
