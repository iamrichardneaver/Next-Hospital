#!/usr/bin/env bash
#
# NextHospital backend — Linux/Plesk deployment script
# Run from the backend project root on the server (not on Windows/XAMPP).
#
# Usage:
#   cd /var/www/vhosts/your-domain.com/httpdocs/backend
#   chmod +x scripts/deploy-linux.sh
#   ./scripts/deploy-linux.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "==> NextHospital deploy (Linux)"
echo "    Project root: $ROOT"

if [[ "$ROOT" == *"\\"* ]] || [[ "$ROOT" == *[Cc]:* ]] || [[ "$ROOT" == *"/xampp/"* ]] || [[ "$ROOT" == *"/Applications/XAMPP/"* ]]; then
    echo "ERROR: This script must run on a Linux server, not from a Windows/XAMPP path."
    exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
    echo "ERROR: composer not found in PATH."
    exit 1
fi

if ! command -v php >/dev/null 2>&1; then
    echo "ERROR: php not found in PATH."
    exit 1
fi

if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then
        echo "==> Creating .env from .env.example"
        cp .env.example .env
    else
        echo "ERROR: .env missing. Copy .env.example to .env and configure production values."
        exit 1
    fi
fi

if [[ ! -f app/Console/Commands/DeployCheckCommand.php ]]; then
    echo "ERROR: DeployCheckCommand.php missing. Re-upload app/ and scripts/ from your repo."
    exit 1
fi

echo "==> Ensuring writable Laravel directories"
mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

echo "==> Clearing bootstrap/cache PHP files (never upload these from Windows)"
rm -f bootstrap/cache/packages.php \
    bootstrap/cache/services.php \
    bootstrap/cache/config.php \
    bootstrap/cache/routes-v7.php
find bootstrap/cache -maxdepth 1 -type f -name '*.php' -delete 2>/dev/null || true

echo "==> Clearing compiled Blade views (never upload these from Windows)"
rm -rf storage/framework/views/* 2>/dev/null || true

echo "==> Reinstalling PHP dependencies on Linux (never upload vendor/ from Windows)"
rm -rf vendor
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Setting storage and bootstrap/cache permissions"
DEPLOY_USER="$(whoami)"
if getent group psacln >/dev/null 2>&1; then
    chown -R "${DEPLOY_USER}:psacln" storage bootstrap/cache 2>/dev/null || true
elif getent group www-data >/dev/null 2>&1; then
    chown -R "${DEPLOY_USER}:www-data" storage bootstrap/cache 2>/dev/null || true
fi
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Clearing stale Laravel caches before rebuild"
php artisan optimize:clear --no-ansi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "==> Generating APP_KEY"
    php artisan key:generate --force
fi

echo "==> Linking public storage"
php artisan storage:link --force 2>/dev/null || php artisan storage:link

echo "==> Running migrations"
php artisan migrate --force

echo "==> Caching configuration and routes"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deployment safety check"
if ! php artisan deploy:check --no-ansi; then
    echo ""
    echo "ERROR: deploy:check failed. Do NOT use this build in production."
    echo "       See docs/DEPLOYMENT_CLOUD.md — usually: delete bootstrap/cache/*.php,"
    echo "       rm -rf vendor && composer install, clear storage/framework/views/*, then re-run this script."
    exit 1
fi

echo ""
echo "==> Optional: if the web user differs from $(whoami), adjust ownership:"
echo "    chown -R <plesk-domain-user>:psacln storage bootstrap/cache"
echo "    chmod -R a+rX storage/app/public"
echo ""
echo "Deploy complete. Verify APP_URL, FORCE_HTTPS, and DB_* in .env match your Plesk site."
