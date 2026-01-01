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

# Ensure bootstrap/cache directory exists and is writable
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

# Ensure supervisor log directory exists
mkdir -p /var/log/supervisor

# Start supervisor in foreground (it will manage PHP-FPM and other processes)
# Supervisor becomes PID 1 and manages all child processes
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
