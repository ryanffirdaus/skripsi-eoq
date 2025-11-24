# ==========================================
# Stage 1: Builder (Punya PHP & Node.js)
# ==========================================
FROM php:8.3-fpm as builder

# 1. Install dependencies OS (Termasuk libicu-dev untuk intl)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl

# 2. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 3. Copy file config dulu (Caching layer)
COPY composer.json composer.lock package.json package-lock.json ./

# 4. Install Backend Dependencies (Wajib ada biar artisan jalan)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 5. Install Frontend Dependencies
RUN npm install

# 6. Copy Source Code
COPY . .

# 7. Generate dummy .env (PENTING: artisan butuh .env minimal untuk booting)
RUN cp .env.example .env || true
RUN php artisan package:discover || true

# 8. Build Frontend (Hasilnya ada di /app/public/build)
RUN npm run build

# ==========================================
# Stage 2: Final Production Image (Slim)
# ==========================================
FROM php:8.3-fpm

# Install PHP Extensions (Sama seperti requirement Laravel)
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# 1. COPY SOURCE CODE DULUAN (PENTING!)
# Kita copy codingan mentah dari laptop ke image
COPY . .

# Copy custom php.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# 2. COPY HASIL BUILD SETELAHNYA (TIMPA)
# Ini memastikan folder vendor dan public/build yang valid dari stage 1
# akan menimpa/melengkapi source code yang tadi dicopy.
COPY --from=builder /app/vendor /var/www/html/vendor
COPY --from=builder /app/public/build /var/www/html/public/build

# Setup permission & folder storage
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache/data} /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
