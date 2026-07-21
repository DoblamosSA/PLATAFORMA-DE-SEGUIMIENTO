#!/bin/sh
set -e

cd /var/www/html

# BD SQLite en el volumen (primer arranque)
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite
fi

# Clave de aplicacion (primer arranque, queda en el .env montado)
if ! grep -q '^APP_KEY=base64' .env 2>/dev/null; then
    php artisan key:generate --force
fi

php artisan storage:link 2>/dev/null || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage database

exec /usr/bin/supervisord -c /etc/supervisord.conf
