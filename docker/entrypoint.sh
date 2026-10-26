#!/bin/sh
set -e

echo "=== [Vida Salud] Iniciando contenedor de producción ==="

# Asegurar directorios de framework y storage
mkdir -p /var/www/storage/framework/cache/data \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/logs \
         /var/www/bootstrap/cache

# Asegurar permisos requeridos por el servidor web
echo "-> Ajustando permisos para www-data..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Limpiar optimizaciones previas
echo "-> Limpiando cachés de Laravel..."
php artisan optimize:clear

# Si APP_KEY está definida, optimizar configuración y rutas
if [ -n "$APP_KEY" ]; then
    echo "-> Optimizando configuración y rutas para producción..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Iniciar PHP-FPM en segundo plano
echo "-> Iniciando PHP-FPM en segundo plano..."
php-fpm -D

# Iniciar Nginx en primer plano
echo "-> Iniciando Nginx en primer plano (Puerto 80)..."
exec nginx -g "daemon off;"
