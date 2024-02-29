#!/bin/sh

set -e

#todo: enable intentional container crash with the release of version 0.2.0
#if [ ! -f /well-known-security.txt ]; then
#  echo "Error: Unable to start Ember Nexus API due to missing security.txt file at path /well-known-security.txt ."
#  echo "See https://ember-nexus.github.io/api/ for details."
#  exit 1
#fi

if [ -z "$@" ]; then
  /usr/local/bin/unit-entrypoint.sh unitd --no-daemon --control unix:/var/run/control.unit.sock
else
  exec "$@"
fi
