#!/bin/bash
set -e

# Default to php-fpm if no command provided
CMD="${1:-php-fpm}"

# Wait for database to be ready
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection on $DB_HOST:${DB_PORT:-5432}..."
    while ! nc -z "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; do
        sleep 1
    done
    echo "Database connection established."
fi

# Wait for Redis if running queue worker
if echo "$*" | grep -q "queue:work"; then
    if [ -n "$REDIS_HOST" ]; then
        echo "Waiting for Redis connection on $REDIS_HOST:${REDIS_PORT:-6379}..."
        while ! nc -z "$REDIS_HOST" "${REDIS_PORT:-6379}" 2>/dev/null; do
            sleep 1
        done
        echo "Redis connection established."
    fi
fi

# Setup application if artisan is present
if [ -f "artisan" ]; then
    if [ ! -f ".env" ]; then
        echo "Creating .env from .env.example..."
        cp .env.example .env 2>/dev/null || true
    fi

    # Generate app key if not set
    if ! grep -q "APP_KEY=" .env 2>/dev/null || [ "$(grep 'APP_KEY=' .env | cut -d= -f2)" = "" ]; then
        echo "Generating application key..."
        php artisan key:generate --force 2>/dev/null || true
    fi

    # Run migrations if RUN_MIGRATIONS=true
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "Running migrations..."
        php artisan migrate --force 2>/dev/null || true
    fi
fi

# Execute the command
case "$CMD" in
    queue:work)
        echo "Starting Laravel Queue Worker..."
        exec php artisan queue:work redis --sleep=3 --tries=1 --max-time=3600
        ;;
    schedule:work)
        echo "Starting Laravel Scheduler..."
        exec php artisan schedule:work
        ;;
    php-fpm)
        exec php-fpm
        ;;
    *)
        exec "$@"
        ;;
esac
