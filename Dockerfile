# Production image for voerfit-portal.
# Build with: gcloud builds submit --config=cloudbuild.yaml --substitutions=_TAG=<tag> .

# --- Build: PHP dependencies and frontend assets ---------------------------
# The Wayfinder Vite plugin runs `php artisan` during the asset build, so the
# assets are built in a PHP image with Node added.
FROM serversideup/php:8.4-cli-v4.5.1 AS build

USER root
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && rm -rf node_modules

# --- Runtime: nginx + PHP-FPM on port 8080 ----------------------------------
FROM serversideup/php:8.4-fpm-nginx-v4.5.1

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
