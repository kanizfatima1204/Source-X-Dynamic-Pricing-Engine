#!/bin/sh
set -e

# Create directories and database file
mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

# If .env does not exist, copy from .env.example
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure APP_KEY exists
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Clear any stale config/route/view caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations and seeds
php artisan migrate --force --seed

# Fix permissions
chmod -R 775 storage bootstrap/cache database 2>/dev/null || true

# Start web server on Railway's dynamic PORT
PORT="${PORT:-8000}"
echo "Starting Laravel server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
