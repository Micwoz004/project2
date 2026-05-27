#!/usr/bin/env bash
set -euo pipefail

# Deploy script for BO application
#
# Usage:
#   ./deploy.sh        - standard in-place deploy
#   ./deploy.sh --test - standard deploy + application test suite
#
# Steps:
#   1. Detect incoming changes to skip unnecessary heavy steps
#   2. Validate runtime writable paths
#   3. Enter maintenance mode
#   4. Pull code and install dependencies conditionally
#   5. Run migrations and rebuild Laravel caches
#   6. Build assets only when frontend files changed
#   7. Restart queues and leave maintenance mode
#   8. Optionally run tests with --test

RUN_TESTS=false
MAINTENANCE_ENABLED=false
APP_RUNTIME_USER="${APP_RUNTIME_USER:-www-data}"
PHP_BIN="${APP_PHP_BIN:-/usr/bin/php}"
COMPOSER_BIN="${APP_COMPOSER_BIN:-/usr/bin/composer}"
DEPLOY_REQUIRE_SUPERVISORCTL="${DEPLOY_REQUIRE_SUPERVISORCTL:-false}"

usage() {
    cat <<'USAGE'
Usage:
  ./deploy.sh [--test] [--help]

Options:
  --test    Run the application test suite after deploy
  --help    Show help
USAGE
}

for arg in "$@"; do
    case "$arg" in
        --test)
            RUN_TESTS=true
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            # Unknown flags are ignored for compatibility with deployment wrappers.
            ;;
    esac
done

env_value() {
    local key="$1"

    if [ ! -f .env ]; then
        return 0
    fi

    (grep -E "^${key}=" .env || true) | tail -n1 | cut -d '=' -f2- | tr -d '"' | tr -d "'" | xargs
}

run_as_runtime_user() {
    if [ "$(id -u)" -eq 0 ] && id "$APP_RUNTIME_USER" >/dev/null 2>&1; then
        runuser -u "$APP_RUNTIME_USER" -- "$@"
        return
    fi

    "$@"
}

artisan() {
    run_as_runtime_user "$PHP_BIN" artisan "$@"
}

composer_install() {
    run_as_runtime_user "$PHP_BIN" "$(command -v "$COMPOSER_BIN")" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
}

npm_ci() {
    run_as_runtime_user npm ci --no-audit --no-fund --prefer-offline
}

npm_build() {
    run_as_runtime_user npm run build
}

can_use_supervisorctl() {
    command -v supervisorctl >/dev/null 2>&1 && supervisorctl status >/dev/null 2>&1
}

ensure_runtime_writable_paths() {
    local bad_paths

    echo "[INFO] Checking storage and cache permissions..."
    mkdir -p storage bootstrap/cache

    if [ "$(id -u)" -eq 0 ]; then
        chown -R "${APP_RUNTIME_USER}:${APP_RUNTIME_USER}" storage bootstrap/cache
        chmod -R ug+rwX storage bootstrap/cache
        return
    fi

    bad_paths="$(find storage bootstrap/cache -mindepth 0 ! -writable -print -quit)"
    if [ -n "$bad_paths" ]; then
        echo "[ERROR] Missing write permissions for: $bad_paths"
        echo "        Fix storage/bootstrap cache ownership or ACL before deploying without sudo."
        exit 1
    fi

    chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
}

preflight_deploy_runtime() {
    local worker_programs

    if [ ! -x "$PHP_BIN" ] && ! command -v "$PHP_BIN" >/dev/null 2>&1; then
        echo "[ERROR] PHP binary not found: $PHP_BIN"
        exit 1
    fi

    if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
        echo "[ERROR] Composer binary not found: $COMPOSER_BIN"
        exit 1
    fi

    ensure_runtime_writable_paths

    worker_programs="$(env_value QUEUE_WORKER_SUPERVISOR_PROGRAMS)"
    if [ "$DEPLOY_REQUIRE_SUPERVISORCTL" = "true" ] && [ -n "$worker_programs" ] && ! can_use_supervisorctl; then
        echo "[ERROR] supervisorctl is not available for the deployment user."
        exit 1
    fi
}

restart_supervisor_program() {
    local program="$1"
    local status
    local attempt

    echo "[INFO] Restarting Supervisor program: ${program}"
    supervisorctl restart "$program"

    for attempt in {1..10}; do
        status="$(supervisorctl status "$program")"
        echo "$status"

        if echo "$status" | awk '{print $2}' | grep -qx 'RUNNING'; then
            return
        fi

        sleep 1
    done

    echo "[ERROR] Supervisor program is not RUNNING after restart: ${program}"
    exit 1
}

restart_supervisor_programs_csv() {
    local programs_csv="$1"
    local program

    IFS=',' read -ra programs <<< "$programs_csv"
    for program in "${programs[@]}"; do
        program="$(echo "$program" | xargs)"
        if [ -n "$program" ]; then
            restart_supervisor_program "$program"
        fi
    done
}

detect_queue_supervisor_programs() {
    local app_dir
    local conf

    app_dir="$(pwd)"

    for conf in /etc/supervisor/conf.d/*.conf; do
        [ -f "$conf" ] || continue

        if grep -E '^command=' "$conf" | grep -F "${app_dir}/artisan queue:work" >/dev/null 2>&1; then
            sed -n 's/^\[program:\(.*\)\]$/\1/p' "$conf" | head -n1
        fi
    done
}

restart_queue_runtime() {
    local queue_connection
    local worker_programs

    queue_connection="$(env_value QUEUE_CONNECTION)"
    [ -n "$queue_connection" ] || queue_connection="database"

    if [ "$queue_connection" = "sync" ]; then
        echo "[INFO] Queue connection is sync; skipping queue worker restart."
        return
    fi

    echo "[INFO] Signalling Laravel queue workers to reload code..."
    artisan queue:restart

    worker_programs="$(env_value QUEUE_WORKER_SUPERVISOR_PROGRAMS)"
    if [ -z "$worker_programs" ]; then
        worker_programs="$(detect_queue_supervisor_programs | paste -sd ',' -)"
    fi

    if [ -z "$worker_programs" ]; then
        echo "[WARN] No Supervisor queue program configured or detected; queue:restart signal was sent only."
        return
    fi

    if can_use_supervisorctl; then
        restart_supervisor_programs_csv "$worker_programs"
    else
        echo "[WARN] supervisorctl unavailable; queue workers will restart gracefully after queue:restart."
    fi
}

cleanup() {
    local exit_code=$?

    if [ "$MAINTENANCE_ENABLED" = true ]; then
        echo "[INFO] Disabling maintenance mode..."
        artisan up || true
        MAINTENANCE_ENABLED=false
    fi

    exit "$exit_code"
}

ensure_storage_link() {
    local storage_link
    local storage_target
    local current_target

    storage_link="public/storage"
    mkdir -p storage/app/public
    storage_target="$(realpath storage/app/public)"
    current_target="$(readlink "$storage_link" 2>/dev/null || true)"

    if [ -L "$storage_link" ] && [ "$current_target" = "$storage_target" ]; then
        echo "[INFO] Storage symlink is correct."
        return
    fi

    echo "[INFO] Repairing storage symlink..."
    rm -f "$storage_link"
    artisan storage:link
}

trap cleanup EXIT INT TERM

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
CURRENT_HEAD="$(git rev-parse HEAD)"
TARGET_HEAD="$CURRENT_HEAD"
CHANGED_FILES=""
NEEDS_COMPOSER_INSTALL=true
NEEDS_NPM_INSTALL=true
NEEDS_ASSET_BUILD=true

echo "[INFO] Analyzing incoming changes..."
if git fetch origin "$CURRENT_BRANCH" >/dev/null 2>&1; then
    TARGET_HEAD="$(git rev-parse "origin/$CURRENT_BRANCH")"
    if [ "$CURRENT_HEAD" != "$TARGET_HEAD" ]; then
        CHANGED_FILES="$(git diff --name-only "$CURRENT_HEAD" "$TARGET_HEAD" || true)"
        echo "[INFO] Incoming commits detected."
    else
        echo "[INFO] No incoming commits detected; redeploying current version."
    fi
else
    echo "[WARN] Could not fetch metadata from origin; running full deploy steps."
fi

if [ -d vendor ] && ! echo "$CHANGED_FILES" | grep -Eq '(^|/)(composer\.json|composer\.lock)$'; then
    NEEDS_COMPOSER_INSTALL=false
fi

if [ -d node_modules ] && ! echo "$CHANGED_FILES" | grep -Eq '(^|/)(package\.json|package-lock\.json)$'; then
    NEEDS_NPM_INSTALL=false
fi

if ! echo "$CHANGED_FILES" | grep -Eq '^(resources/|public/|package\.json|package-lock\.json|vite\.config\.js|tailwind\.config\.js|postcss\.config\.js)'; then
    NEEDS_ASSET_BUILD=false
fi

echo "[INFO] composer install: $NEEDS_COMPOSER_INSTALL"
echo "[INFO] npm ci: $NEEDS_NPM_INSTALL"
echo "[INFO] asset build: $NEEDS_ASSET_BUILD"

preflight_deploy_runtime

echo "[INFO] Enabling maintenance mode..."
artisan down
MAINTENANCE_ENABLED=true

echo "[INFO] Pulling code from repository..."
git pull --ff-only

if [ "$NEEDS_COMPOSER_INSTALL" = true ]; then
    echo "[INFO] Installing PHP dependencies..."
    composer_install
else
    echo "[INFO] Skipping composer install."
fi

if [ "$NEEDS_NPM_INSTALL" = true ]; then
    echo "[INFO] Installing JS dependencies..."
    npm_ci
else
    echo "[INFO] Skipping npm ci."
fi

echo "[INFO] Running migrations..."
artisan migrate --force

ensure_runtime_writable_paths
ensure_storage_link

echo "[INFO] Rebuilding Laravel caches..."
artisan optimize:clear
artisan filament:clear-cached-components || true
artisan filament:assets
artisan schedule:clear-cache || true
artisan config:cache
artisan event:cache
artisan view:cache
artisan route:cache

if [ "$NEEDS_ASSET_BUILD" = true ]; then
    echo "[INFO] Building frontend assets..."
    npm_build
else
    echo "[INFO] Skipping asset build."
fi

restart_queue_runtime

artisan up
MAINTENANCE_ENABLED=false
trap - EXIT INT TERM

if [ "$RUN_TESTS" = true ]; then
    echo "[INFO] Running application tests..."
    artisan test
fi

echo "[INFO] Deploy completed successfully."
