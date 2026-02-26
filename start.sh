#!/bin/sh

echo "Running migrations..."
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force

echo "Starting Laravel..."
php artisan serve --host=0.0.0.0 --port=8000