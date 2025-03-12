#!/bin/bash
set -e

# Trust the mounted directory
git config --global --add safe.directory /var/www/html

# Fix permissions for Laravel directories
fix-permissions

# Start PHP-FPM
exec php-fpm