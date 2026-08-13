#!/bin/bash
set -e

echo "🚀 Iniciando despliegue de Rentame API en Hostinger..."

# Traer últimos cambios de GitHub
echo "📥 Descargando cambios de Git..."
git pull origin main

# Instalar dependencias de Composer sin dependencias de desarrollo
echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# Verificar/crear base de datos SQLite si se usa SQLite
if [ ! -f database/database.sqlite ]; then
    echo "🗄️ Creando archivo de base de datos SQLite..."
    touch database/database.sqlite
fi

# Ejecutar migraciones de base de datos
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force

# Crear enlace simbólico de almacenamiento
echo "🔗 Verificando enlace simbólico de storage..."
php artisan storage:link --quiet || true

# Limpiar y optimizar cachés de Laravel
echo "⚡ Optimizando caché de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ ¡Despliegue completado con éxito!"
