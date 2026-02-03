FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions Laravel needs
RUN apk add --no-cache \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql bcmath opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Finish composer install and optimize
RUN composer dump-autoload --optimize \
# NOTE: We skip config:cache here because it bakes config at build time.
# Since APP_KEY and DB credentials are passed at runtime via environment
# variables, caching config during build would ignore those values.
    # && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache


    # Copy nginx and supervisor config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf


# Set permissions
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache


# Set up non-root user
# Create necessary directories and set ownership so www-data can write to them
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /run \
    /var/lib/nginx \
    /var/log/nginx

# Switch to non-root user
USER www-data



EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]