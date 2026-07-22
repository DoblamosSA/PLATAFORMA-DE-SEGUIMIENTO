#!/bin/sh
set -e

cd /var/www/html

# BD SQLite en el volumen (primer arranque)
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite
fi

# La configuracion llega por variables de entorno (compose env_file);
# no hay archivo .env dentro del contenedor. APP_KEY es obligatoria.
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY no esta definida. Fijala en deploy/.env.production"
    exit 1
fi

php artisan storage:link 2>/dev/null || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage database

exec /usr/bin/supervisord -c /etc/supervisord.conf
