#!/bin/sh

set -e

#todo: enable intentional container crash with the release of version 0.2.0
#if [ ! -f /well-known-security.txt ]; then
#  echo "Error: Unable to start Ember Nexus API due to missing security.txt file at path /well-known-security.txt ."
#  echo "See https://ember-nexus.github.io/api/ for details."
#  exit 1
#fi

# request handling is CPU bound, so start one thread per CPU core; upload requests hold a thread while the client is
# sending data, so up to 8 threads per CPU core may be added under load
CPU_COUNT="$(nproc)"
export FRANKENPHP_NUM_THREADS="${FRANKENPHP_NUM_THREADS:-$CPU_COUNT}"
export FRANKENPHP_MAX_THREADS="${FRANKENPHP_MAX_THREADS:-$((CPU_COUNT * 8))}"

if [ -z "$@" ]; then
  if [ "$APP_ENV" = "prod" ]; then
    # warmup is an optimization only; if it fails (e.g. out of memory), let frankenphp start anyway so the app
    # can still serve requests (and build caches on demand), rather than crashing the whole container
    php bin/console cache:warmup || true
  fi
  exec frankenphp run --config /etc/frankenphp/Caddyfile
else
  exec "$@"
fi
