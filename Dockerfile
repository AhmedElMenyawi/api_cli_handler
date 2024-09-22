# Base image with PHP 8.3 FPM
FROM php:8.3.11-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    curl

# Install PHP extensions
RUN docker-php-ext-install intl

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Ensure .env file exists
COPY .env /var/www/html/.env

# Install project dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader


# Clear Symfony cache
RUN php bin/console cache:clear

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Use Symfony's built-in server to serve the app
CMD ["php", "-S", "0.0.0.0:9000", "-t", "public"]
