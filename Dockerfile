# ==========================================
# Etapa 1: Compilación de Frontend con Vite
# ==========================================
FROM node:20-alpine AS frontend

WORKDIR /app

# Copiar manifiestos de dependencias
COPY package*.json ./

# Instalar dependencias de Node
RUN npm ci || npm install

# Copiar archivos necesarios para compilar assets
COPY resources resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY public public

# Compilar assets de producción (Vite 7)
RUN npm run build

# ==========================================
# Etapa 2: Backend PHP 8.2-FPM & Servidor Nginx
# ==========================================
FROM php:8.2-fpm-alpine

LABEL maintainer="Equipo DevOps - Clinica Vida Salud"
LABEL description="Contenedor de producción Laravel 12 con Nginx y PHP-FPM 8.2 para Vida Salud"

WORKDIR /var/www/html

# Configurar zona horaria y entorno
ENV TZ=America/La_Paz

# Instalar dependencias del sistema y librerías nativas para PHP y Nginx
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    bash \
    curl \
    git \
    unzip \
    tzdata \
    && cp /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo "${TZ}" > /etc/timezone

# Configurar y compilar extensiones indispensables de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        bcmath \
        mbstring \
        opcache \
        gd \
        zip \
        intl

# Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
RUN rm -rf /etc/nginx/http.d/default.conf

# Copiar código fuente de la aplicación
COPY . /var/www/html

# Copiar artefactos frontend compilados desde la etapa 1 explícitamente
COPY --from=frontend /app/public/build /var/www/html/public/build

# Instalar dependencias de producción de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Crear directorios necesarios y configurar permisos para www-data
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

# Exponer el puerto HTTP
EXPOSE 80

# Punto de entrada
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
