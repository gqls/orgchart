#!/bin/bash
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Set permissions
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage

# Set ownership to www-data (PHP-FPM user)
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage
