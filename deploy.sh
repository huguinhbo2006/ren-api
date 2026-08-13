#!/bin/bash
set -e

echo "🚀 Iniciando despliegue de Rentame API en Hostinger..."

# 1. Crear directorio y archivo de base de datos SQLite ANTES de ejecutar composer
if [ ! -f database/database.sqlite ]; then
    echo "🗄️ Creando archivo de base de datos SQLite..."
    mkdir -p database
    touch database/database.sqlite
fi

# 2. Traer últimos cambios de GitHub
echo "📥 Descargando cambios de Git..."
git pull origin main

# 3. Instalar dependencias de Composer
echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# 4. Ejecutar migraciones de base de datos
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force

# 5. Crear enlace simbólico de almacenamiento
echo "🔗 Verificando enlace simbólico de storage..."
php artisan storage:link --quiet || true

# 6. Limpiar y optimizar cachés de Laravel
echo "⚡ Optimizando caché de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ ¡Despliegue completado con éxito!"
