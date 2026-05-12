FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libmariadb-dev zip curl \
    && docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY start.sh /start.sh
RUN chmod +x /start.sh

ENTRYPOINT ["/start.sh"]
EXPOSE 8080
