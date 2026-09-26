#!/bin/sh

set -e

#todo: enable intentional container crash with the release of version 0.2.0
#if [ ! -f /well-known-security.txt ]; then
#  echo "Error: Unable to start Ember Nexus API due to missing security.txt file at path /well-known-security.txt ."
#  echo "See https://ember-nexus.github.io/api/ for details."
#  exit 1
#fi

# upload requests hold a PHP thread while the client is sending data, so start 2 (FrankenPHP's default) and allow up to
# 8 threads per CPU core, see Caddyfile
CPU_COUNT="$(nproc)"
export FRANKENPHP_NUM_THREADS="${FRANKENPHP_NUM_THREADS:-$((CPU_COUNT * 2))}"
export FRANKENPHP_MAX_THREADS="${FRANKENPHP_MAX_THREADS:-$((CPU_COUNT * 8))}"

if [ -z "$@" ]; then
  frankenphp run --config /etc/frankenphp/Caddyfile
else
  exec "$@"
fi
