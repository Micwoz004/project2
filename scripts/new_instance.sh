#!/usr/bin/env bash
set -euo pipefail

#############################################
# BO new instance provisioner
#############################################

BRANCH_DEFAULT="master"
WEB_ROOT_DEFAULT="/var/www/bo"
APP_OS_USER_DEFAULT="www-data"
DB_OWNER_DEFAULT="sbo"
PHP_BIN_DEFAULT="/usr/bin/php8.5"
COMPOSER_BIN_DEFAULT="/usr/bin/composer"
PHP_FPM_SERVICE_DEFAULT="php8.5-fpm"
PHP_FPM_SOCK_DEFAULT="/run/php/php8.5-fpm.sock"
REPO_URL_DEFAULT="git@github.com:Micwoz004/project2.git"

INSTANCE=""
DOMAIN=""
WITH_WWW="true"
REPO_URL="$REPO_URL_DEFAULT"
BRANCH="$BRANCH_DEFAULT"
WEB_ROOT="$WEB_ROOT_DEFAULT"
APP_OS_USER="$APP_OS_USER_DEFAULT"
DB_OWNER="$DB_OWNER_DEFAULT"
DB_PASSWORD=""
DB_NEW=""
DB_TEMPLATE=""
PHP_BIN="$PHP_BIN_DEFAULT"
COMPOSER_BIN="$COMPOSER_BIN_DEFAULT"
PHP_FPM_SERVICE="$PHP_FPM_SERVICE_DEFAULT"
PHP_FPM_SOCK="$PHP_FPM_SOCK_DEFAULT"
ENABLE_CERTBOT="true"
CERTBOT_EMAIL=""
START_STEP=1

APP_DIR=""
SLUG=""
ENV_FILE=""

info() {
  printf '[INFO] %s\n' "$*"
}

warn() {
  printf '[WARN] %s\n' "$*" >&2
}

err() {
  printf '[ERROR] %s\n' "$*" >&2
}

usage() {
  cat <<'USAGE'
Usage:
  sudo scripts/new_instance.sh --instance INSTANCE [options]

Options:
  --instance NAME            Required instance name. App path: /var/www/bo/NAME
  --domain DOMAIN            Public domain. Default: INSTANCE
  --db DB_NAME               Target database name. Default: bo_INSTANCE_SLUG
  --db-template DB_NAME      Optional PostgreSQL template database. Default: empty database
  --db-owner USER            PostgreSQL owner/user. Default: sbo
  --db-password PASSWORD     Database password written to .env. Default: empty
  --repo URL                 Git repository URL. Default: git@github.com:Micwoz004/project2.git
  --branch BRANCH            Git branch. Default: master
  --web-root PATH            Base web root. Default: /var/www/bo
  --php-bin PATH             PHP binary. Default: /usr/bin/php8.5
  --composer-bin PATH        Composer binary. Default: /usr/bin/composer
  --php-fpm-service NAME     PHP-FPM service. Default: php8.5-fpm
  --php-fpm-sock PATH        PHP-FPM socket. Default: /run/php/php8.5-fpm.sock
  --certbot-email EMAIL      Enable Let's Encrypt certificate with this email
  --no-www                   Do not add www.DOMAIN to nginx/certbot
  --no-certbot               Skip Let's Encrypt certificate
  --start-step N             Resume manually from step N, 1..9. Default: 1
  -h, --help                 Show help

Steps:
  1. Validate inputs and target paths
  2. Clone repository
  3. Create PostgreSQL database
  4. Create .env
  5. Install dependencies and build assets
  6. Run Laravel bootstrap
  7. Configure nginx
  8. Configure queue worker and scheduler cron
  9. Optional certbot and service reload
USAGE
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --instance)
      INSTANCE="${2:-}"
      shift 2
      ;;
    --domain)
      DOMAIN="${2:-}"
      shift 2
      ;;
    --db)
      DB_NEW="${2:-}"
      shift 2
      ;;
    --db-template)
      DB_TEMPLATE="${2:-}"
      shift 2
      ;;
    --db-owner)
      DB_OWNER="${2:-}"
      shift 2
      ;;
    --db-password)
      DB_PASSWORD="${2:-}"
      shift 2
      ;;
    --repo)
      REPO_URL="${2:-}"
      shift 2
      ;;
    --branch)
      BRANCH="${2:-}"
      shift 2
      ;;
    --web-root)
      WEB_ROOT="${2:-}"
      shift 2
      ;;
    --php-bin)
      PHP_BIN="${2:-}"
      shift 2
      ;;
    --composer-bin)
      COMPOSER_BIN="${2:-}"
      shift 2
      ;;
    --php-fpm-service)
      PHP_FPM_SERVICE="${2:-}"
      shift 2
      ;;
    --php-fpm-sock)
      PHP_FPM_SOCK="${2:-}"
      shift 2
      ;;
    --certbot-email)
      CERTBOT_EMAIL="${2:-}"
      shift 2
      ;;
    --no-www)
      WITH_WWW="false"
      shift
      ;;
    --no-certbot)
      ENABLE_CERTBOT="false"
      shift
      ;;
    --start-step)
      START_STEP="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      err "Unknown argument: $1"
      usage
      exit 1
      ;;
  esac
done

require_root() {
  if [ "$(id -u)" -ne 0 ]; then
    err "Run as root or via sudo."
    exit 1
  fi
}

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    err "Missing command: $1"
    exit 1
  fi
}

validate_step_number() {
  if ! [[ "$START_STEP" =~ ^[1-9]$ ]]; then
    err "Invalid --start-step: $START_STEP. Allowed: 1..9"
    exit 1
  fi
}

slugify() {
  printf '%s' "$1" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g;s/^-+//;s/-+$//'
}

db_name_from_slug() {
  printf 'bo_%s' "$(printf '%s' "$1" | tr '-' '_')"
}

prepare_runtime_values() {
  if [ -z "$INSTANCE" ]; then
    err "--instance is required."
    usage
    exit 1
  fi

  if ! [[ "$INSTANCE" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    err "Invalid instance name: ${INSTANCE}. Allowed characters: letters, digits, dot, underscore and dash."
    exit 1
  fi

  if [ "$INSTANCE" = "." ] || [ "$INSTANCE" = ".." ]; then
    err "Invalid instance name: ${INSTANCE}"
    exit 1
  fi

  SLUG="$(slugify "$INSTANCE")"
  if [ -z "$SLUG" ]; then
    err "Instance name cannot be converted to a safe slug: ${INSTANCE}"
    exit 1
  fi

  if [ -z "$DOMAIN" ]; then
    DOMAIN="$INSTANCE"
  fi

  if [ -z "$DB_NEW" ]; then
    DB_NEW="$(db_name_from_slug "$SLUG")"
  fi

  APP_DIR="${WEB_ROOT}/${INSTANCE}"
  ENV_FILE="${APP_DIR}/.env"
}

assert_safe_new_instance() {
  if [ "$START_STEP" -le 1 ]; then
    if [ -e "$APP_DIR" ] && [ "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 2>/dev/null | wc -l)" -gt 0 ]; then
      err "Target directory exists and is not empty: $APP_DIR"
      exit 1
    fi

    if [ -e "/etc/nginx/sites-available/${SLUG}" ] || [ -e "/etc/nginx/sites-enabled/${SLUG}" ]; then
      err "Nginx config already exists for ${SLUG}."
      exit 1
    fi

    if [ -e "/etc/supervisor/conf.d/${SLUG}-queue-default.conf" ]; then
      err "Queue supervisor config already exists for ${SLUG}."
      exit 1
    fi
  fi
}

assert_database_values_ok() {
  local template_exists

  if ! [[ "$DB_NEW" =~ ^[a-zA-Z0-9_.-]+$ ]]; then
    err "Invalid DB name: ${DB_NEW}"
    exit 1
  fi

  if ! [[ "$DB_OWNER" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]]; then
    err "Invalid DB owner: ${DB_OWNER}"
    exit 1
  fi

  if [ -n "$DB_TEMPLATE" ]; then
    if ! [[ "$DB_TEMPLATE" =~ ^[a-zA-Z0-9_.-]+$ ]]; then
      err "Invalid template DB name: ${DB_TEMPLATE}"
      exit 1
    fi

    template_exists="$(sudo -u postgres psql -Atqc "SELECT 1 FROM pg_database WHERE datname = '${DB_TEMPLATE}'" || true)"
    if [ "$template_exists" != "1" ]; then
      err "Template database does not exist: ${DB_TEMPLATE}"
      exit 1
    fi
  fi
}

ensure_system_requirements() {
  require_command git
  require_command "$COMPOSER_BIN"
  require_command npm
  require_command nginx
  require_command psql
  require_command createdb
  require_command supervisorctl
  require_command "$PHP_BIN"

  if [ ! -S "$PHP_FPM_SOCK" ]; then
    err "PHP-FPM socket not found: $PHP_FPM_SOCK"
    exit 1
  fi
}

run_as_app_user() {
  sudo -u "$APP_OS_USER" -H env HOME="$APP_DIR" "$@"
}

ensure_git_safe_directory() {
  if ! git config --global --get-all safe.directory 2>/dev/null | grep -Fx "$APP_DIR" >/dev/null; then
    git config --global --add safe.directory "$APP_DIR" || true
  fi

  if ! git config --system --get-all safe.directory 2>/dev/null | grep -Fx "$APP_DIR" >/dev/null; then
    git config --system --add safe.directory "$APP_DIR" || true
  fi
}

set_env_kv() {
  local file="$1"
  local key="$2"
  local value="$3"
  local escaped

  [ -f "$file" ] || { err "Missing env file: $file"; return 1; }

  sed -i 's/\r$//' "$file"
  escaped="$(printf '%s' "$value" | sed 's/[\/&]/\\&/g')"

  if grep -qE "^${key}=" "$file"; then
    sed -i "s/^${key}=.*/${key}=${escaped}/" "$file"
  else
    printf '\n%s=%s\n' "$key" "$value" >> "$file"
  fi
}

step_clone_repository() {
  info "Step 2: cloning repository to ${APP_DIR}"
  mkdir -p "$(dirname "$APP_DIR")"
  git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
  chown -R "$APP_OS_USER:$APP_OS_USER" "$APP_DIR"
  ensure_git_safe_directory
}

step_create_database() {
  local exists

  info "Step 3: creating database ${DB_NEW}"
  assert_database_values_ok

  exists="$(sudo -u postgres psql -Atqc "SELECT 1 FROM pg_database WHERE datname = '${DB_NEW}'" || true)"
  if [ "$exists" = "1" ]; then
    err "Database already exists: ${DB_NEW}"
    exit 1
  fi

  if [ -n "$DB_TEMPLATE" ]; then
    sudo -u postgres createdb -T "$DB_TEMPLATE" -O "$DB_OWNER" "$DB_NEW"
  else
    sudo -u postgres createdb -O "$DB_OWNER" "$DB_NEW"
  fi
}

step_create_env() {
  local app_key

  info "Step 4: creating .env"

  if [ ! -f "${APP_DIR}/.env.example" ]; then
    err "Missing ${APP_DIR}/.env.example"
    exit 1
  fi

  cp "${APP_DIR}/.env.example" "$ENV_FILE"

  set_env_kv "$ENV_FILE" APP_NAME "\"BO ${INSTANCE}\""
  set_env_kv "$ENV_FILE" APP_ENV production
  set_env_kv "$ENV_FILE" APP_DEBUG false
  set_env_kv "$ENV_FILE" APP_URL "https://${DOMAIN}"
  set_env_kv "$ENV_FILE" LOG_LEVEL info
  set_env_kv "$ENV_FILE" DB_CONNECTION pgsql
  set_env_kv "$ENV_FILE" DB_HOST 127.0.0.1
  set_env_kv "$ENV_FILE" DB_PORT 5432
  set_env_kv "$ENV_FILE" DB_DATABASE "$DB_NEW"
  set_env_kv "$ENV_FILE" DB_USERNAME "$DB_OWNER"
  set_env_kv "$ENV_FILE" DB_PASSWORD "$DB_PASSWORD"
  set_env_kv "$ENV_FILE" SESSION_DOMAIN "$DOMAIN"
  set_env_kv "$ENV_FILE" CACHE_STORE redis
  set_env_kv "$ENV_FILE" CACHE_PREFIX "${SLUG}_cache:"
  set_env_kv "$ENV_FILE" QUEUE_CONNECTION redis
  set_env_kv "$ENV_FILE" REDIS_PREFIX "${SLUG}:"
  set_env_kv "$ENV_FILE" MAIL_MAILER smtp
  set_env_kv "$ENV_FILE" VITE_APP_NAME "\"BO ${INSTANCE}\""

  app_key="$("$PHP_BIN" -r 'echo "base64:".base64_encode(random_bytes(32));')"
  set_env_kv "$ENV_FILE" APP_KEY "$app_key"

  chown "$APP_OS_USER:$APP_OS_USER" "$ENV_FILE"
  chmod 640 "$ENV_FILE"
}

step_install_dependencies_and_build() {
  info "Step 5: installing dependencies and building assets"

  mkdir -p "${APP_DIR}/.composer" "${APP_DIR}/.npm"
  chown -R "$APP_OS_USER:$APP_OS_USER" "${APP_DIR}/.composer" "${APP_DIR}/.npm"

  (
    cd "$APP_DIR"
    run_as_app_user env COMPOSER_HOME="${APP_DIR}/.composer" "$PHP_BIN" "$(command -v "$COMPOSER_BIN")" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
    run_as_app_user env npm_config_cache="${APP_DIR}/.npm" npm ci --no-audit --no-fund --prefer-offline
    run_as_app_user npm run build
  )
}

ensure_dirs_and_perms() {
  mkdir -p "$APP_DIR/bootstrap/cache" "$APP_DIR/storage" "$APP_DIR/storage/logs"
  chown -R "$APP_OS_USER:$APP_OS_USER" "$APP_DIR/bootstrap/cache" "$APP_DIR/storage"
  find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
  find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;
}

step_laravel_bootstrap() {
  info "Step 6: running Laravel bootstrap"
  ensure_dirs_and_perms

  (
    cd "$APP_DIR"
    run_as_app_user "$PHP_BIN" artisan storage:link || true
    run_as_app_user "$PHP_BIN" artisan migrate --force
    run_as_app_user "$PHP_BIN" artisan optimize:clear
    run_as_app_user "$PHP_BIN" artisan filament:clear-cached-components || true
    run_as_app_user "$PHP_BIN" artisan filament:assets
    run_as_app_user "$PHP_BIN" artisan schedule:clear-cache || true
    run_as_app_user "$PHP_BIN" artisan config:cache
    run_as_app_user "$PHP_BIN" artisan event:cache
    run_as_app_user "$PHP_BIN" artisan view:cache
    run_as_app_user "$PHP_BIN" artisan route:cache
  )

  ensure_dirs_and_perms
}

nginx_server_names() {
  if [ "$WITH_WWW" = "true" ]; then
    printf '%s www.%s' "$DOMAIN" "$DOMAIN"
  else
    printf '%s' "$DOMAIN"
  fi
}

step_configure_nginx() {
  local server_names
  local nginx_conf

  info "Step 7: configuring nginx"
  server_names="$(nginx_server_names)"
  nginx_conf="/etc/nginx/sites-available/${SLUG}"

  cat > "$nginx_conf" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${server_names};
    root ${APP_DIR}/public;

    index index.php index.html;
    charset utf-8;
    client_max_body_size 128M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

  ln -sfn "$nginx_conf" "/etc/nginx/sites-enabled/${SLUG}"
  nginx -t
  systemctl reload nginx
}

step_supervisor_and_scheduler() {
  local default_supervisor_conf
  local cron_line
  local current
  local filtered

  info "Step 8: configuring queue worker and scheduler cron"

  default_supervisor_conf="/etc/supervisor/conf.d/${SLUG}-queue-default.conf"

  cat > "$default_supervisor_conf" <<SUPERVISOR
[program:${SLUG}-queue-default]
process_name=%(program_name)s
command=${PHP_BIN} ${APP_DIR}/artisan queue:work redis --queue=default --sleep=5 --tries=3 --timeout=180 --max-time=1200 --max-jobs=150 --memory=192
directory=${APP_DIR}
autostart=true
autorestart=true
user=${APP_OS_USER}
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/queue-default.log
stopwaitsecs=600
killasgroup=true
stopasgroup=true
SUPERVISOR

  supervisorctl reread
  supervisorctl update
  supervisorctl restart "${SLUG}-queue-default"

  cron_line="* * * * * ${PHP_BIN} ${APP_DIR}/artisan schedule:run >> ${APP_DIR}/storage/logs/schedule.log 2>&1"
  current="$(sudo -u "$APP_OS_USER" crontab -l 2>/dev/null || true)"
  filtered="$(printf '%s\n' "$current" | grep -vF "${APP_DIR}/artisan schedule:run" || true)"
  printf '%s\n%s\n' "$filtered" "$cron_line" | sudo -u "$APP_OS_USER" crontab -
}

step_certbot_and_reload() {
  local certbot_domains

  info "Step 9: final reload and optional certbot"
  systemctl restart "$PHP_FPM_SERVICE"
  systemctl reload nginx

  if [ "$ENABLE_CERTBOT" != "true" ]; then
    warn "Certbot skipped."
    return
  fi

  if ! command -v certbot >/dev/null 2>&1; then
    warn "certbot command not found. Skipping SSL."
    return
  fi

  if [ -z "$CERTBOT_EMAIL" ]; then
    warn "CERTBOT_EMAIL not provided. Skipping SSL."
    return
  fi

  certbot_domains=(-d "$DOMAIN")
  if [ "$WITH_WWW" = "true" ]; then
    certbot_domains+=(-d "www.${DOMAIN}")
  fi

  certbot --nginx "${certbot_domains[@]}" --non-interactive --agree-tos -m "$CERTBOT_EMAIL" --redirect
}

run_step() {
  local number="$1"
  local name="$2"

  shift 2

  if [ "$START_STEP" -le "$number" ]; then
    "$@"
  else
    info "Skipping step ${number}: ${name}"
  fi
}

main() {
  require_root
  validate_step_number
  prepare_runtime_values
  ensure_system_requirements
  assert_safe_new_instance

  cat <<SUMMARY
=== BO new instance ===
INSTANCE:    ${INSTANCE}
DOMAIN:      ${DOMAIN}
APP_DIR:     ${APP_DIR}
BRANCH:      ${BRANCH}
DB_NEW:      ${DB_NEW}
DB_TEMPLATE: ${DB_TEMPLATE:-<empty database>}
WITH_WWW:    ${WITH_WWW}
START_STEP:  ${START_STEP}
SUMMARY

  run_step 2 "clone repository" step_clone_repository
  run_step 3 "create database" step_create_database
  run_step 4 "create .env" step_create_env
  run_step 5 "install dependencies and build assets" step_install_dependencies_and_build
  run_step 6 "Laravel bootstrap" step_laravel_bootstrap
  run_step 7 "nginx" step_configure_nginx
  run_step 8 "supervisor and scheduler" step_supervisor_and_scheduler
  run_step 9 "certbot and reload" step_certbot_and_reload

  info "Instance ready: https://${DOMAIN}"
}

main "$@"
