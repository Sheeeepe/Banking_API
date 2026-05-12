#!/bin/bash
set -e

echo "Waiting for MySQL..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --port="${DB_PORT:-3306}" --silent 2>/dev/null; do
    sleep 2
done

echo "Running init.sql..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --port="${DB_PORT:-3306}" "$DB_NAME" < /app/build/init.sql 2>/dev/null || true

echo "Starting PHP server..."
exec php -S 0.0.0.0:8080 -t /app /app/index.php
