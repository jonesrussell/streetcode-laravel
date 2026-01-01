#!/bin/bash
set -e

# Fix permissions for storage directories
# This is needed because volume mounts can override container permissions
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Ensure all Laravel storage subdirectories exist
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Fix permissions again after creating directories
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Execute the main command
exec "$@"
