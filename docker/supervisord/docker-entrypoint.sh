#!/bin/sh

set -e

mkdir -p /var/www/html/var/logs
touch /var/www/html/var/logs/log.log

if [ -z "$@" ]; then
  supervisord --nodaemon --configuration /etc/supervisord.conf
else
  exec "$@"
fi
