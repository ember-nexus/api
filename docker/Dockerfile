# PHP itself is compiled from source in the `php_base` stage below instead of reusing the prebuilt
# `dunglas/frankenphp:*-alpine` image, for control over the build (pinned/reproducible toolchain, SQL
# stripped out, etc.). Note this does NOT get ext/hash's SHA-NI (hardware-accelerated SHA-256) code path
# working on Alpine/musl either -- confirmed by building it here too (still ~330 MB/s, same as the prebuilt
# image); see ext/hash/php_hash_sha.h (PHP_HASH_INTRIN_SHA_RESOLVER) in php-src for why.
#
# `php_base`'s RUN steps are adapted from https://github.com/docker-library/php (MIT licensed), with all
# SQL-related configure flags/extensions removed (this project has no SQL dependency) and ZTS + embed kept
# enabled (required by FrankenPHP). The `frankenphp_builder` stage is adapted from FrankenPHP's own
# https://github.com/dunglas/frankenphp/blob/main/alpine.Dockerfile (MIT licensed), building FrankenPHP's
# Go binary via xcaddy/cgo against `php_base` instead of against docker-library's own PHP image.
#
# Helper scripts in ./php-embed/ (docker-php-source, docker-php-ext-*, docker-php-entrypoint) are copied
# verbatim from docker-library/php, unmodified.

ARG ALPINE_VERSION='3.23'
ARG PHP_VERSION='8.5.11'
# 1.12.7 is FrankenPHP's latest stable release as of 2026-10; bumped from 1.12.1 to pick up several
# crash/stability fixes landed in 1.12.2-1.12.7 (fatal-shutdown prevention, an HTTP/2 crash-on-read-after-
# finish fix, a path-splitting security fix, and output-handling-after-fastcgi_finish_request() fixes) --
# see https://github.com/dunglas/frankenphp/releases
ARG FRANKENPHP_VERSION='1.12.7'
ARG WATCHER_VERSION='0.14.5'
# pinned to a full patch version (not just "1.26") so `golang:${GO_VERSION}-alpine` resolves to the exact
# same toolchain on every rebuild instead of silently floating to whatever the latest 1.26.x patch is;
# matches the Go version FrankenPHP v1.12.7 itself builds with (see its go.mod)
ARG GO_VERSION='1.26.8'
# pinned to a full patch version of the xcaddy/caddy-builder image (FrankenPHP v1.12.7 itself depends on
# the 2.11.x line of github.com/caddyserver/caddy/v2); this image only supplies the xcaddy build tool, not
# the embedded Caddy core (that version comes from Go module resolution against frankenphp's go.mod), but
# pinning it avoids floating to whatever "2.11" resolves to on a later rebuild
ARG CADDY_BUILDER_VERSION='2.11.6'


FROM caddy:${CADDY_BUILDER_VERSION}-builder-alpine AS caddy_builder


FROM golang:${GO_VERSION}-alpine AS golang_base


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
# Apply stack smash protection to functions using local buffers and alloca()
# Make PHP's main executable position-independent (improves ASLR security mechanism, and has no performance impact on x86_64)
# Enable optimization (-O2)
# Enable linker optimization (this sorts the hash buckets to improve cache locality, and is non-default)
# https://github.com/docker-library/php/issues/272
# -D_LARGEFILE_SOURCE and -D_FILE_OFFSET_BITS=64 (https://www.php.net/manual/en/intro.filesystem.php)
# -DHAVE_FUNC_ATTRIBUTE_TARGET=1: works around ext/hash's SHA-256 (and zend_cpuinfo's other SIMD dispatch)
# never getting a hardware-accelerated code path on Alpine. That path is gated behind this exact macro (see
# ext/hash/php_hash_sha.h / Zend/zend_cpuinfo.h), which configure only sets via build/ax_gcc_func_attribute.m4
# -- a probe that treats *any* compiler warning on a throwaway one-line test as "attribute unsupported", even
# though GCC only warns (never errors) for it; see https://github.com/php/php-src/issues/15384. GCC on Alpine
# does support `__attribute__((target(...)))` in the actual code (ordinary, well-supported syntax), so this
# just skips the flaky probe instead of trusting it; if that's wrong, `make` fails loudly on a real compiler
# error instead of silently keeping the slow path. Harmless on arm64: the attribute-gated code is additionally
# scoped to `__i386__`/`__x86_64__` at the point this macro is checked, so it has no effect there.
    PHP_CFLAGS="-fstack-protector-strong -fpic -fpie -O2 -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64 -DHAVE_FUNC_ATTRIBUTE_TARGET=1" \
    PHP_CPPFLAGS="-fstack-protector-strong -fpic -fpie -O2 -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64 -DHAVE_FUNC_ATTRIBUTE_TARGET=1" \
    PHP_LDFLAGS="-Wl,-O1 -pie" \
    PHP_BUILD_PROVIDER="https://github.com/ember-nexus/api" \
    PHP_UNAME="Linux - Docker" \
    GPG_KEYS="1198C0117593497A5EC5C199286AF1F9897469DC 49D9AF6BC72A80D6691719C8AA23F5BE9C7097D4 D95C03BC702BE9515344AE3374E44BC9067701A5" \
    PHP_VERSION="${PHP_VERSION}" \
    PHP_URL="https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz" \
    PHP_ASC_URL="https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz.asc" \
# pinned to PHP_VERSION 8.5.11; bump together when bumping the ARG above
    PHP_SHA256="d9be75c08e8c316f4c8f4194d8fbe1750a15f6a6d9d4e3fe72082abeeb800360"

# persistent / runtime deps
RUN apk add --no-cache \
		ca-certificates \
		curl \
		tar \
		xz

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
	apk add --no-cache --virtual .fetch-deps gnupg; \
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

COPY ./docker/php-embed/docker-php-source /usr/local/bin/
COPY ./docker/php-embed/patches /usr/local/src/php-patches

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
		oniguruma-dev \
		openssl-dev \
		patch \
		readline-dev \
		sed \
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
# ext/hash's hash_update_stream() (used by IncrementalHashService for upload chunk hashing) reads the
# resource through php_stream_read() in a 1024-byte buffer, which for a large chunk means well over 100,000
# small stream reads, each paying PHP's stream-wrapper dispatch overhead; that dominates once SHA-256 itself
# is SHA-NI-accelerated (see -DHAVE_FUNC_ATTRIBUTE_TARGET=1 above). Bump it to 16KiB: measured as the point
# where throughput plateaus (larger buffers, up to 1MiB+, gave no further gain in benchmarking -- and large
# buffers eventually regress due to allocation/copy overhead), while cutting the read-call count by ~16x and
# staying a total non-issue as a stack buffer. Only the buffer inside PHP_FUNCTION(hash_update_stream) is
# touched (matched by the `toread = 1024` line immediately following the declaration, which is unique to
# that function) -- hash()/hash_hmac()/hash_update_file()'s own `buf[1024]` (used for e.g. hashing a
# filename/path directly, not this project's upload path) are deliberately left as-is.
	sed -i '/char buf\[1024\];/{N;s/char buf\[1024\];\n\([ \t]*\)zend_long toread = 1024;/char buf[16384];\n\1zend_long toread = 16384;/}' ext/hash/hash.c; \
	grep -q 'char buf\[16384\];' ext/hash/hash.c; \
	grep -q 'zend_long toread = 16384;' ext/hash/hash.c; \
	[ "$(grep -c 'char buf\[1024\];' ext/hash/hash.c)" = 3 ]; \
# SHA256Transform() (the SHA-NI dispatch used by the hash above) re-checks zend_cpu_supports() -- a CPUID
# feature lookup -- on every single 64-byte block, i.e. over a million times for a large upload chunk, unlike
# every other SIMD-dispatched function in php-src (crc32_x86_simd_update(), php_base64_encode_ex(), etc.),
# which resolve their CPU-feature check exactly once via an ifunc resolver or a PHP_MINIT_FUNCTION-cached
# pointer. This patch brings SHA256Transform() in line with that existing convention: the check is resolved
# once in PHP_MINIT_FUNCTION(hash) into a plain static int, instead of being repeated every block.
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
# https://github.com/docker-library/php/issues/439
		--with-mhash \
# https://github.com/docker-library/php/issues/822
		--with-pic \
# --enable-mbstring is included here because otherwise there's no way to get pecl to use it properly (see https://github.com/docker-library/php/issues/195)
		--enable-mbstring \
# opcache is bundled and enabled by default since PHP 7.3, no configure flag needed/accepted anymore
# https://wiki.php.net/rfc/argon2_password_hash
		--with-password-argon2 \
# https://wiki.php.net/rfc/libsodium
		--with-sodium=shared \
# this project has no SQL dependency; PDO and all SQL drivers are fully disabled/removed instead of just unused
		--disable-pdo \
		--without-sqlite3 \
		--without-pdo-sqlite \
		--with-curl \
		--with-iconv=/usr \
		--with-openssl \
		--with-readline \
		--with-zlib \
# https://github.com/docker-library/php/pull/1259
		--enable-phpdbg \
		--enable-phpdbg-readline \
# in PHP 7.4+, the pecl/pear installers are officially deprecated (requiring an explicit "--with-pear")
		--with-pear \
# https://github.com/docker-library/php/pull/939#issuecomment-730501748
		--enable-embed \
# required by FrankenPHP
		--enable-zts \
# https://externals.io/message/118859
		--disable-zend-signals \
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
# hardware path this time, see the -DHAVE_FUNC_ATTRIBUTE_TARGET=1 comment above for why this was in doubt.
# the sample is generated in memory (random_bytes + str_repeat), nothing is read from or stored in the repo.
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

COPY ./docker/php-embed/docker-php-ext-* ./docker/php-embed/docker-php-entrypoint /usr/local/bin/
COPY ./docker/php-embed/php.prod.ini "$PHP_INI_DIR/php.ini"

# sodium was built as a shared module (so that it can be replaced later if so desired), so let's enable it too (https://github.com/docker-library/php/issues/598)
RUN docker-php-ext-enable sodium \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer



FROM php_base AS frankenphp_builder
ARG FRANKENPHP_VERSION
ARG WATCHER_VERSION
ARG GO_VERSION

COPY --from=caddy_builder /usr/bin/xcaddy /usr/bin/xcaddy
COPY --from=golang_base /usr/local/go /usr/local/go
ENV PATH="/usr/local/go/bin:${PATH}" \
    GOTOOLCHAIN=local \
    GOBIN=/usr/local/bin

# same build deps as https://github.com/dunglas/frankenphp/blob/main/alpine.Dockerfile's "builder" stage,
# minus sqlite-dev (this project's php_base has no SQL support to link against)
RUN apk add --no-cache --virtual .frankenphp-build-deps \
		$PHPIZE_DEPS \
		argon2-dev \
		bash \
		brotli-dev \
		cmake \
		coreutils \
		curl-dev \
		git \
		gnu-libiconv-dev \
		libcap \
		libsodium-dev \
		libstdc++ \
		libxml2-dev \
		linux-headers \
		oniguruma-dev \
		openssl-dev \
		readline-dev \
		upx

# e-dant/watcher powers FrankenPHP's file watcher; pinned to a release instead of "latest" for reproducible
# builds (bump alongside FRANKENPHP_VERSION if FrankenPHP requires a newer watcher ABI)
WORKDIR /usr/local/src/watcher
RUN set -eux; \
	curl -fsSL "https://github.com/e-dant/watcher/archive/refs/tags/${WATCHER_VERSION}.tar.gz" -o /tmp/watcher.tar.gz; \
	tar -xzf /tmp/watcher.tar.gz -C /usr/local/src/watcher --strip-components=1; \
	rm /tmp/watcher.tar.gz; \
	cmake -S . -B build -DCMAKE_BUILD_TYPE=Release; \
	cmake --build build; \
	cmake --install build

COPY --from=frankenphp_source /usr/local/src/frankenphp /go/src/app
WORKDIR /go/src/app
RUN go mod download
WORKDIR /go/src/app/caddy
RUN go mod download
WORKDIR /go/src/app

# see also https://github.com/docker-library/php/blob/master/8.3/alpine3.20/zts/Dockerfile#L53-L55 and
# https://github.com/dunglas/frankenphp/blob/main/alpine.Dockerfile
RUN CGO_ENABLED=1 \
    XCADDY_SETCAP=1 \
    XCADDY_GO_BUILD_FLAGS="-tags=nobadger,nomysql,nopgx -ldflags='-w -s -extldflags \'-Wl,-z,stack-size=0x80000\' -X \'github.com/caddyserver/caddy/v2.CustomVersion=FrankenPHP ${FRANKENPHP_VERSION} PHP ${PHP_VERSION} Caddy\' -X \'github.com/caddyserver/caddy/v2/modules/caddyhttp.ServerHeader=FrankenPHP Caddy\''" \
    CGO_CFLAGS="-DFRANKENPHP_VERSION=${FRANKENPHP_VERSION} $(php-config --includes) $PHP_CFLAGS" \
    CGO_CPPFLAGS="$PHP_CPPFLAGS" \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs) -lssl -lcrypto -lreadline -largon2 -lcurl -lonig -lz $PHP_LDFLAGS" \
    xcaddy build \
        --output /usr/local/bin/frankenphp \
        --with github.com/dunglas/frankenphp=./ \
        --with github.com/dunglas/frankenphp/caddy=./caddy/ \
        --with github.com/dunglas/caddy-cbrotli \
        --with github.com/baldinof/caddy-supervisor \
    && setcap cap_net_bind_service=+ep /usr/local/bin/frankenphp \
    && /usr/local/bin/frankenphp version \
    && /usr/local/bin/frankenphp build-info \
    && strip --strip-all /usr/local/bin/frankenphp \
    && upx --best --lzma /usr/local/bin/frankenphp \
    && /usr/local/bin/frankenphp version


FROM golang:${GO_VERSION}-alpine AS go_builder
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

RUN apk add --no-cache \
		ca-certificates \
		curl \
        icu \
        icu-data-full \
        libcap \
        libstdc++ \
        libzip \
        mailcap \
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

COPY --from=frankenphp_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
COPY --from=frankenphp_builder /usr/local/lib/libwatcher* /usr/local/lib/
RUN ldconfig /usr/local/lib || true

ENV GODEBUG=cgocheck=0 \
    XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data \
    APP_ENV=prod

COPY ./docker/Caddyfile /etc/frankenphp/Caddyfile
COPY ./docker/docker-entrypoint.sh /usr/local/bin/
COPY ./docker/supercronic /etc/crontabs/supercronic
COPY --from=go_builder /build/expr-cli /usr/local/bin/expr-cli
RUN set -x \
    && mkdir -p /config/caddy /data/caddy \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && adduser -D worker \
	&& setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
	&& /usr/local/bin/frankenphp version \
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
COPY ./docker/php-embed/php.prod.ini "$PHP_INI_DIR/php.ini"
COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
COPY LICENSE /var/www/html/LICENSE
RUN APP_ENV=prod COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-cache \
    && rm -rf /var/www/html/var/cache \
    && mkdir -p /var/www/html/var/cache \
    && rm -rf /usr/local/include/php \
    && mv /usr/local/lib/php/extensions /usr/local/lib/php_extensions \
    && rm -rf /usr/local/lib/php \
    && mkdir /usr/local/lib/php \
    && mv /usr/local/lib/php_extensions /usr/local/lib/php/extensions \
    && rm -rf /tmp/pear \
    && rm -rf /usr/src \
    && rm /usr/local/bin/php-cgi \
    && rm /usr/local/bin/phpdbg \
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

# the dev image has no vendor/ installed by default (it's provided by the mounted source instead), so supercronic's
# scheduled `php bin/console cron` runs would fail until `composer install` is run manually; the dev Caddyfile drops
# the `supervisor` block so supercronic never starts
COPY ./docker/Caddyfile.dev /etc/frankenphp/Caddyfile

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

HEALTHCHECK NONE
