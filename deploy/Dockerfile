# ------------------------------------------------------------------
# Projects (PLATAFORMA-DE-SEGUIMIENTO) — imagen autocontenida
# nginx + php-fpm + supervisord en un solo contenedor, BD SQLite en
# volumen. Pensada para convivir con otros despliegues del servidor
# publicando un unico puerto HTTP libre.
# ------------------------------------------------------------------

# --- Etapa 1: assets frontend (Vite) ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js tailwind.config.js postcss.config.js ./
RUN npm ci
COPY resources ./resources
RUN npm run build

# --- Etapa 2: dependencias PHP ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# --- Etapa 3: runtime ---
FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx supervisor sqlite-libs icu-libs gmp \
    && apk add --no-cache --virtual .build icu-dev gmp-dev \
    && docker-php-ext-install intl opcache pdo_mysql gmp bcmath \
    && apk del .build

COPY deploy/php.ini /usr/local/etc/php/conf.d/app.ini
COPY deploy/nginx.conf /etc/nginx/http.d/default.conf
COPY deploy/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN rm -f bootstrap/cache/*.php \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
                storage/logs storage/app/public database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod +x deploy/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/var/www/html/deploy/entrypoint.sh"]
