FROM php:8.3-cli
RUN apt-get update && apt-get install -y git curl libpng-dev libpq-dev libonig-dev libxml2-dev zip unzip libzip-dev libicu-dev nodejs npm && docker-php-ext-install pdo_mysql pdo_pgsql mbstring xml bcmath ctype fileinfo zip intl gd && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN docker-php-ext-install pdo_pgsql opcache
WORKDIR /app
COPY . .
RUN rm -f .env
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build
RUN php artisan filament:assets
RUN php artisan config:clear && php artisan view:clear

ENV APP_ENV=production
ENV APP_DEBUG=false

CMD php artisan migrate --force && php artisan db:seed --class=AdminSeeder --force -n && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
