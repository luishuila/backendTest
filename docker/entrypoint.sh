
set -euo pipefail

cd /var/www/html


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


if ! php -r "require 'vendor/autoload.php'; echo (env('APP_KEY') ?: (getenv('APP_KEY') ?: '')) ? 1 : 0;" | grep -q 1; then
  echo "Generando APP_KEY..."
  php artisan key:generate --force || true
fi


if [[ ! -e public/storage ]]; then
  php artisan storage:link || true
fi


if [[ "${RUN_MIGRATIONS:-true}" == "true" ]]; then
  echo "Ejecutando migraciones..."
  php artisan migrate --force || true
fi


php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true


echo "Iniciando PHP-FPM..."
php-fpm -D


echo "Iniciando Nginx..."
exec nginx -g 'daemon off;'
