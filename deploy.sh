#!/bin/bash
set -e

echo "🚀 Iniciando despliegue de Rentame API en Hostinger..."

# 1. Crear directorio y archivo de base de datos SQLite ANTES de nada
if [ ! -f database/database.sqlite ]; then
    echo "🗄️ Creando archivo de base de datos SQLite..."
    mkdir -p database
    touch database/database.sqlite
fi

# 2. Descartar cualquier modificación local en el servidor y sincronizar con origin/main
echo "📥 Sincronizando con los últimos cambios de GitHub..."
git checkout -- . || true
git fetch origin main
git reset --hard origin/main

# Re-verificar base de datos SQLite tras el reset
if [ ! -f database/database.sqlite ]; then
    mkdir -p database
    touch database/database.sqlite
fi

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
