FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libmariadb-dev mariadb-client zip curl \
    && docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chmod +x /app/build/start.sh

EXPOSE 8080
ENTRYPOINT ["/app/build/start.sh"]
