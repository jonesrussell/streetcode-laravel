#!/bin/sh
set -e

# Copy build assets from image to volume if volume is empty
if [ -d /var/www/html/public/build.bak ] && [ "$(ls -A /var/www/html/public/build.bak 2>/dev/null)" ]; then
    if [ ! -d /var/www/html/public/build ] || [ -z "$(ls -A /var/www/html/public/build 2>/dev/null)" ]; then
        echo "Copying build assets to volume..."
        cp -r /var/www/html/public/build.bak/* /var/www/html/public/build/ 2>/dev/null || true
    fi
fi

exec "$@"
