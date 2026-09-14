# Compila Vue dentro do Docker para permitir reproduzir a interface.
FROM node:24-bookworm-slim AS frontend
WORKDIR /frontend
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Ambiente de desenvolvimento Laravel; PostgreSQL no servico db.
FROM php:8.5-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev libsqlite3-dev unzip \
    && docker-php-ext-install pdo_pgsql pdo_sqlite zip bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Instala as dependencias no Linux, sem migrations ou outros scripts.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
COPY --from=frontend /frontend/public/build ./public/build
RUN composer dump-autoload --no-scripts \
    && mkdir -p storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
