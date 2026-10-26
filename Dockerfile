# ==========================================
# Etapa 1: Compilación de Frontend con Vite
# ==========================================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY resources resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY public public
RUN npm run build

# ==========================================
# Etapa 2: Backend PHP 8.2-FPM & Servidor Nginx
# ==========================================
FROM php:8.2-fpm-alpine
LABEL maintainer="Equipo DevOps - Clinica Vida Salud"
WORKDIR /var/www/html

ENV TZ=America/La_Paz

# Instalar Nginx, bash y dependencias mínimas para PostgreSQL
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libpq-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    bash \
    tzdata \
    && cp /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo "${TZ}" > /etc/timezone

# Instalar solo las extensiones críticas de PHP (sin compilaciones pesadas que cuelguen la RAM)
RUN docker-php-ext-install \
        pdo \
        pdo_pgsql \
        bcmath \
        mbstring \
        opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
RUN rm -rf /etc/nginx/http.d/default.conf

# Copiar archivos de dependencias e instalar vendor primero (aprovecha cache de docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copiar el código fuente completo
COPY . /var/www/html

# Copiar los assets compilados de Vite
COPY --from=frontend /app/public/build /var/www/html/public/build

# Optimizar autoloader
RUN composer dump-autoload --optimize

# Permisos de almacenamiento
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar Script de Entrada
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
