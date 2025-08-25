# =========
# Base PHP
# =========
FROM php:8.3-fpm

# Paquetes de sistema y extensiones necesarias
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
     libpq-dev libzip-dev git unzip curl nginx ca-certificates postgresql-client \
  && docker-php-ext-configure zip \
  && docker-php-ext-install pdo pdo_pgsql bcmath zip \
  && rm -rf /var/lib/apt/lists/*

# Composer (desde imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Config PHP (tu archivo)
COPY docker-php.ini /usr/local/etc/php/conf.d/docker-php.ini

# Nginx: tu server block
COPY nginx.conf /etc/nginx/conf.d/laravel.conf

# Asegurar que Nginx carga conf.d/* y preparar runtime
RUN printf '%s\n' \
  'user www-data;' \
  'worker_processes auto;' \
  'pid /run/nginx.pid;' \
  'events { worker_connections 1024; }' \
  'http { include       /etc/nginx/mime.types;' \
  '       default_type  application/octet-stream;' \
  '       sendfile      on;' \
  '       keepalive_timeout 65;' \
  '       access_log /dev/stdout;' \
  '       error_log  /dev/stderr;' \
  '       include /etc/nginx/conf.d/*.conf; }' \
  > /etc/nginx/nginx.conf \
  && rm -f /etc/nginx/conf.d/default.conf /etc/nginx/sites-enabled/default || true \
  && mkdir -p /run/nginx /run/php

# Asegurar que PHP-FPM escucha en 127.0.0.1:9000
RUN { \
      echo '[global]'; \
      echo 'daemonize = no'; \
      echo ''; \
      echo '[www]'; \
      echo 'listen = 127.0.0.1:9000'; \
      echo 'pm = dynamic'; \
      echo 'pm.max_children = 8'; \
      echo 'pm.start_servers = 2'; \
      echo 'pm.min_spare_servers = 1'; \
      echo 'pm.max_spare_servers = 3'; \
    } > /usr/local/etc/php-fpm.d/zz-docker.conf

# App
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts
COPY . /var/www/html

# Limpiar caches de framework
RUN rm -f bootstrap/cache/*.php || true \
  && composer dump-autoload --optimize || true

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache

# Healthcheck
EXPOSE 80 90
HEALTHCHECK --interval=30s --timeout=3s CMD curl -fsS http://localhost/api/health || exit 1

# === NUEVO: entrypoint que migra y arranca servicios ===
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
