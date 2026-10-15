#!/bin/sh
# Orchestrates the Playwright e2e run: starts a preview server of dist/ inside
# the `docs` container, runs the tests inside the separate `e2e` (Chromium)
# container against it, then tears the preview server back down. See
# `task docs:check:e2e` in taskfile.yml.
set -e

cd "$(dirname "$0")"
# The `docs` container (preview server target) lives in the main dev stack; the `e2e` (Chromium)
# container lives in this directory's own compose file, attached to that stack's network.
DOCS_COMPOSE="docker compose -f ../../tools/docker-compose.yml"
E2E_COMPOSE="docker compose -f ./docker-compose.yml"

# `pgrep -f <pattern>` also matches the very shell invoking it (the pattern is
# right there in that shell's own argv) — filter out $$ before killing, or
# this kills its own exec session with SIGKILL instead of the target.
KILL_PREVIEW='
    self=$$
    for pid in $(pgrep -f "preview --host"); do
        [ "$pid" = "$self" ] && continue
        kill -9 "$pid" 2>/dev/null || true
    done
'

if [ ! -d ../../docs/dist ]; then
    echo "docs/dist does not exist — run \`task docs:check:build\` first." >&2
    exit 1
fi

echo "==> clearing any stale preview server from a previous run"
$DOCS_COMPOSE exec --user astro docs sh -c "$KILL_PREVIEW"

echo "==> starting preview server (docs:4322)"
$DOCS_COMPOSE exec -d --user astro docs sh -c \
    "cd /docs && pnpm run preview --host 0.0.0.0 --port 4322 > /tmp/preview.log 2>&1"

echo "==> waiting for preview server to come up"
# Use 127.0.0.1, not localhost: this musl/Alpine container resolves "localhost" to
# ::1 first, but astro preview (--host 0.0.0.0) only binds the IPv4 socket, so a
# "localhost" request gets connection-refused forever even once the server is up.
$DOCS_COMPOSE exec --user astro docs sh -c '
    for i in $(seq 1 30); do
        wget -q -O /dev/null http://127.0.0.1:4322 && exit 0
        sleep 1
    done
    echo "preview server did not start in time" >&2
    cat /tmp/preview.log >&2
    exit 1
'

echo "==> starting e2e (Chromium) container"
$E2E_COMPOSE up -d e2e

status=0
echo "==> running playwright"
$E2E_COMPOSE exec e2e npx playwright test "$@" || status=$?

echo "==> stopping preview server"
$DOCS_COMPOSE exec --user astro docs sh -c "$KILL_PREVIEW"

exit $status
