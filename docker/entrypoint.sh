#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Esperar DB si variables existen (opcional)
if [[ -n "${DB_HOST:-}" && -n "${DB_PORT:-}" ]]; then
  echo "Esperando a la base de datos en ${DB_HOST}:${DB_PORT}..."
  for i in {1..30}; do
    if (echo > /dev/tcp/${DB_HOST}/${DB_PORT}) >/dev/null 2>&1; then
      echo "DB lista."
      break
    fi
    echo "DB no disponible aún... ($i/30)"
    sleep 2
  done
fi

# Generar APP_KEY si falta
if ! php -r "require 'vendor/autoload.php'; echo (env('APP_KEY') ?: (getenv('APP_KEY') ?: '')) ? 1 : 0;" | grep -q 1; then
  echo "Generando APP_KEY..."
  php artisan key:generate --force || true
fi

# Link de storage si no existe
if [[ ! -e public/storage ]]; then
  php artisan storage:link || true
fi

# Migraciones (silenciosas en prod)
if [[ "${RUN_MIGRATIONS:-true}" == "true" ]]; then
  echo "Ejecutando migraciones..."
  php artisan migrate --force || true
fi

# Caches de prod (ajusta según necesidad)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Arrancar PHP-FPM en background
echo "Iniciando PHP-FPM..."
php-fpm -D

# Arrancar Nginx en foreground (proceso PID 1)
echo "Iniciando Nginx..."
exec nginx -g 'daemon off;'
