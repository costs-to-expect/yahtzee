#!/usr/bin/env bash
#
# Runs the browser tests: the real app, a mock of the Costs to Expect API and Chromium driven by Playwright. Nothing
# leaves this machine and nothing touches your .env or database, the app is given its settings in the environment and a
# throwaway SQLite file.
#
#   tests/e2e/run.sh              the pages, the flows, the score sheet and the share links
#   tests/e2e/run.sh pages        only the files whose name contains "pages"
#
# Needs php, node and Playwright with a browser (npm install -g playwright && npx playwright install chromium). If node
# can't find Playwright set PLAYWRIGHT_PATH, to use a browser Playwright did not download set PLAYWRIGHT_CHROMIUM.
# accessibility.js also needs axe-core (npm install -g axe-core) and is only run when you ask for it.
# Screenshots of the failures worth a look are written to E2E_SHOTS (the temp directory by default).

set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
APP_PORT="${E2E_APP_PORT:-8001}"
API_PORT="${E2E_API_PORT:-8099}"
WORK="$(mktemp -d)"
PIDS=()

stop() {
    # The app runs in its own process group (setsid), so the workers PHP starts go with it
    [ -n "${1:-}" ] && { kill -- "-$1" 2>/dev/null || kill "$1" 2>/dev/null; wait "$1" 2>/dev/null; }
    return 0
}

cleanup() {
    stop "${APP_PID:-}"
    for pid in "${PIDS[@]:-}"; do [ -n "$pid" ] && kill "$pid" 2>/dev/null; done
    if [ -z "${E2E_KEEP:-}" ]; then rm -rf "$WORK"; else echo "Kept $WORK"; fi
}
trap cleanup EXIT

export APP_ENV=local APP_DEBUG=true APP_DEV=false APP_NAME="Yahtzee Game Scorer"
export APP_KEY="base64:AeNHS3cE4YSEvEq84wgN9Zi+JNkk8nS8kqWI/6So6c8="
export APP_URL="http://127.0.0.1:${APP_PORT}"
export API_URL="http://127.0.0.1:${API_PORT}" API_URL_DEV="http://127.0.0.1:${API_PORT}"
export ITEM_TYPE_ID=item-type-id ITEM_SUBTYPE_ID=item-subtype-id
export ERROR_EMAIL=errors@yahtzee.test COSTS_TO_EXPECT_INTERNAL_API_KEY=local-key
export DB_CONNECTION=sqlite DB_DATABASE="$WORK/e2e.sqlite"
export SESSION_DRIVER=file CACHE_DRIVER=file QUEUE_CONNECTION=sync MAIL_MAILER=log LOG_CHANNEL=stderr
export SESSION_NAME_USER=yahtzee_user SESSION_NAME_BEARER=yahtzee_bearer SESSION_DOMAIN=null SESSION_CONNECTION=null
export E2E_APP="http://127.0.0.1:${APP_PORT}" E2E_API="http://127.0.0.1:${API_PORT}"

touch "$DB_DATABASE"
(cd "$ROOT" && php artisan migrate --force --quiet) || { echo "The migrations failed" >&2; exit 1; }

port_is_free() { ! (exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null; }

port_is_free "$APP_PORT" || { echo "Port $APP_PORT is in use, stop what is using it or set E2E_APP_PORT" >&2; exit 1; }
port_is_free "$API_PORT" || { echo "Port $API_PORT is in use, stop what is using it or set E2E_API_PORT" >&2; exit 1; }

node "$HERE/mock-api.js" "$API_PORT" > "$WORK/api.log" 2>&1 &
PIDS+=($!)

start_app() {
    # Always a fresh server, it reads its settings when it starts
    stop "${APP_PID:-}"
    PHP_CLI_SERVER_WORKERS=4 setsid bash -c 'cd "$1" && exec php -S "$2" "$3"' _ "$ROOT/public" "127.0.0.1:${APP_PORT}" "$ROOT/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php" > "$WORK/app.log" 2>&1 &
    APP_PID=$!
    for _ in $(seq 1 40); do curl -s -o /dev/null "http://127.0.0.1:${APP_PORT}/" && return 0; sleep 0.25; done
    echo "The app did not start, see $WORK/app.log" >&2
    exit 1
}

FILTER="${1:-}"
FAILED=0

run() {
    local file="$1"
    if [ -n "$FILTER" ] && [[ "$file" != *"$FILTER"* ]]; then return; fi
    echo; echo "=== $file ${2:-}"
    (cd "$HERE" && env ${2:-} node "$file") || FAILED=1
}

export SCORE_CORRECTIONS=false
start_app
run pages.js
run landing.js
run flows.js
run score-sheet.js
run share-and-account.js
[ -n "$FILTER" ] && [[ "accessibility.js" == *"$FILTER"* ]] && run accessibility.js

if [ -z "$FILTER" ] || [[ "score-sheet.js" == *"$FILTER"* ]]; then
    export SCORE_CORRECTIONS=true
    start_app
    run score-sheet.js "CORRECTIONS=1"
fi

echo
if [ "$FAILED" -ne 0 ]; then echo "SOME BROWSER TESTS FAILED (logs: $WORK, removed on exit)"; else echo "All browser tests passed"; fi
exit "$FAILED"
