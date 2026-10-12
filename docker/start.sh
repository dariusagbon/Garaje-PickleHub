#!/bin/sh
# Runs every time the container starts (each deploy on Render).
set -e

# Render tells the app its public address; use it unless APP_URL is set.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Send errors to Render's Logs tab unless told otherwise.
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

# Without a valid APP_KEY every page is a "500 Server Error": say so clearly.
php -r '
    $key = trim((string) getenv("APP_KEY"), " \t\n\r\x27\"");
    $raw = str_starts_with($key, "base64:") ? base64_decode(substr($key, 7), true) : false;
    if ($raw === false || strlen($raw) !== 32) {
        fwrite(STDERR, "ERROR: APP_KEY is missing or invalid. Set it to the base64:... value from `php artisan key:generate --show`.\n");
        exit(1);
    }
'

echo "Running database migrations..."
php artisan migrate --force

# Create or update the admin login from ADMIN_EMAIL / ADMIN_PASSWORD (if set).
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    echo "Ensuring admin account $ADMIN_EMAIL exists..."
    php artisan db:seed --class=AdminUserSeeder --force
fi

# Cache config, routes and views for speed.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
