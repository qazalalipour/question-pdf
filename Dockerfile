FROM php:8.4-cli

# -----------------------------------------
# System Dependencies
# -----------------------------------------

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    chromium \
    fonts-noto-core \
    fonts-noto-cjk \
    fonts-liberation \
    && rm -rf /var/lib/apt/lists/*


# -----------------------------------------
# PHP Extensions
# -----------------------------------------

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl


# -----------------------------------------
# Composer
# -----------------------------------------

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# -----------------------------------------
# Node.js
# -----------------------------------------

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && node -v \
    && npm -v


# -----------------------------------------
# Working Directory
# -----------------------------------------

WORKDIR /var/www


# -----------------------------------------
# Composer Dependencies
# -----------------------------------------

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# -----------------------------------------
# Copy Project
# -----------------------------------------

COPY . .


# -----------------------------------------
# Composer Autoload
# -----------------------------------------

RUN composer dump-autoload --optimize


# -----------------------------------------
# Node Dependencies
# -----------------------------------------

RUN npm ci


# -----------------------------------------
# Build Frontend
# -----------------------------------------

RUN npm run build


# -----------------------------------------
# Puppeteer / Chromium
# -----------------------------------------

ENV PUPPETEER_SKIP_DOWNLOAD=true

ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium


# -----------------------------------------
# Storage Permissions
# -----------------------------------------

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public/questions

RUN chmod -R 775 storage bootstrap/cache


# -----------------------------------------
# Laravel Cache
# -----------------------------------------

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache


# -----------------------------------------
# Port
# -----------------------------------------

EXPOSE 10000


# -----------------------------------------
# Start Laravel
# -----------------------------------------

CMD php artisan serve \
    --host=0.0.0.0 \
    --port=${PORT:-10000}
