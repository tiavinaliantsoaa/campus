#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ -f .env ]]; then
    app_env="$(grep -E '^APP_ENV=' .env | head -n 1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
    if [[ "$app_env" == "local" && "${1:-}" != "--force" ]]; then
        echo "Ce script prépare le serveur de préproduction. APP_ENV=local : rien n'a été modifié."
        echo "Sur le serveur, copiez .env.example vers .env, puis relancez scripts/preprod.sh."
        exit 1
    fi
fi

if [[ ! -f .env ]]; then
    cp .env.example .env
    php artisan key:generate --force --no-interaction
fi

mkdir -p database storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Préproduction prête. La racine web du serveur doit être le dossier public/."
