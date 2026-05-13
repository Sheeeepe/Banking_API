FROM php:8.2-cli

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y unzip git

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the api directory contents to the root of the app in the container.
# This ignores the frontend /app directory on your machine.
COPY api/ .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Start server
# Railway injects the PORT env var, we use it with a default fallback to 8080
CMD php -S 0.0.0.0:${PORT:-8080} -t public
