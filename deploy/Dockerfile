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
# ext-gd solo hace falta en runtime (phpoffice/phpspreadsheet la requiere,
# pero aqui solo se resuelven/descargan paquetes, no se ejecuta codigo PHP).
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-req=ext-gd

# --- Etapa 3: runtime ---
FROM php:8.3-fpm-alpine

# gd y zip son requeridos por phpoffice/phpspreadsheet (exportacion Excel).
RUN apk add --no-cache nginx supervisor sqlite-libs icu-libs gmp freetype libjpeg-turbo libpng libwebp libzip \
    && apk add --no-cache --virtual .build icu-dev gmp-dev freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libzip-dev pkgconfig \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install intl opcache pdo_mysql gmp bcmath gd zip \
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
