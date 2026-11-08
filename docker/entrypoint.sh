#!/bin/sh
set -e

echo "=== [Vida Salud] Iniciando contenedor de producción ==="

cd /var/www/html

# Asegurar directorios de framework y storage
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Asegurar permisos requeridos por el servidor web
echo "-> Ajustando permisos para www-data en /var/www/html..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

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

# Temporizadores (pago vencido, inasistencias, recordatorios): Render gratis no tiene cron.
echo "-> Iniciando el planificador de tareas en segundo plano..."
su -s /bin/sh www-data -c "php artisan schedule:work" > /var/www/html/storage/logs/schedule.log 2>&1 &

# Iniciar PHP-FPM en segundo plano
echo "-> Iniciando PHP-FPM en segundo plano..."
php-fpm -D

# Iniciar Nginx en primer plano
echo "-> Iniciando Nginx en primer plano (Puerto 80)..."
exec nginx -g "daemon off;"
