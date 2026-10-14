#!/bin/sh
# Orchestrates the Playwright e2e run: starts a preview server of dist/ inside
# the `core` container, runs the tests inside the separate `e2e` (Chromium)
# container against it, then tears the preview server back down. See
# `task check:e2e` in taskfile.yml.
set -e

cd "$(dirname "$0")"
COMPOSE="docker compose -f ./docker-compose.yml"

# `pgrep -f <pattern>` also matches the very shell invoking it (the pattern is
# right there in that shell's own argv) — filter out $$ before killing, or
# this kills its own exec session with SIGKILL instead of the target.
KILL_PREVIEW='
    self=$$
    for pid in $(pgrep -f "preview -- --host"); do
        [ "$pid" = "$self" ] && continue
        kill -9 "$pid" 2>/dev/null || true
    done
'

if [ ! -d ../docs/dist ]; then
    echo "docs/dist does not exist — run \`task check:build\` first." >&2
    exit 1
fi

echo "==> clearing any stale preview server from a previous run"
$COMPOSE exec --user astro core sh -c "$KILL_PREVIEW"

echo "==> starting preview server (core:4322)"
$COMPOSE exec -d --user astro core sh -c \
    "cd /core && pnpm run preview -- --host 0.0.0.0 --port 4322 > /tmp/preview.log 2>&1"

echo "==> waiting for preview server to come up"
$COMPOSE exec --user astro core sh -c '
    for i in $(seq 1 30); do
        wget -q -O /dev/null http://localhost:4322 && exit 0
        sleep 1
    done
    echo "preview server did not start in time" >&2
    cat /tmp/preview.log >&2
    exit 1
'

echo "==> starting e2e (Chromium) container"
$COMPOSE up -d e2e

status=0
echo "==> running playwright"
$COMPOSE exec e2e npx playwright test "$@" || status=$?

echo "==> stopping preview server"
$COMPOSE exec --user astro core sh -c "$KILL_PREVIEW"

exit $status
