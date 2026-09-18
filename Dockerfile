FROM php:8.2-cli-alpine

# Install system dependencies & PHP sqlite extension
RUN apk add --no-cache nodejs npm sqlite sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application files
COPY . .

# Install PHP and Node dependencies & build frontend assets
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && npm install \
    && npm run build \
    && chmod +x start.sh

EXPOSE 8000

CMD ["sh", "start.sh"]
