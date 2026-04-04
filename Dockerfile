FROM php:8.3-cli
RUN apt-get update && apt-get install -y git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev libicu-dev nodejs npm && docker-php-ext-install pdo_mysql mbstring xml bcmath ctype fileinfo zip intl gd && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs
RUN cp .env.example .env && php artisan key:generate
RUN npm install && npm run build
RUN php artisan filament:assets
RUN php artisan config:clear && php artisan view:clear
CMD php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" && php artisan migrate --force && php artisan db:seed --class=AdminSeeder --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
