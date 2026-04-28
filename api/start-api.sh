#!/bin/bash
cd /api
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
fi
exec php -S 0.0.0.0:8080 -t /api/public
