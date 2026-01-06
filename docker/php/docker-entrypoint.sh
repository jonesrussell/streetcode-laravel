#!/bin/sh
set -e

# Copy build assets from image to volume (always overwrite to ensure latest build is used)
if [ -d /var/www/html/public/build.bak ] && [ "$(ls -A /var/www/html/public/build.bak 2>/dev/null)" ]; then
    echo "Copying build assets to volume..."
    mkdir -p /var/www/html/public/build
    cp -r /var/www/html/public/build.bak/* /var/www/html/public/build/ 2>/dev/null || true
fi

# Start supervisor in the background if running php-fpm
if [ "$1" = "php-fpm" ]; then
    echo "Starting supervisor..."
    /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
fi

exec "$@"
