#!/bin/sh
# `php` for this image: runs the CLI through FrankenPHP, so every CLI process (bin/console, cron, healthcheck) has the
# compiled-in `EmberNexus\Mphf` extension (the id -> type lookup), exactly like the web workers.
#
# FrankenPHP's CLI emulation (PHP < 8.6) only supports `php <script> [args...]` and `php -r <code>`; it has no `-d`,
# `-n`, `-v`, `-m`, `-i`, `--ini`, ... Invocations with such a flag are handed to the plain php binary if the image
# has one (the development image keeps it as php-plain for pecl, composer and friends; the production image has none,
# there FrankenPHP prints its own "not available" message).
#
# `exec -a` sets argv[0], so PHP_BINARY is this wrapper and child processes (paratest, symfony/process, composer
# scripts) start `php` through it again, instead of trying to execute `frankenphp <script>`.
case "${1-}" in
    -r) ;;
    -?*)
        if [ -x /usr/local/bin/php-plain ]; then
            exec /usr/local/bin/php-plain "$@"
        fi
        ;;
esac
exec -a /usr/local/bin/php /usr/local/bin/frankenphp php-cli "$@"
