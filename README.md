# Backend Laravel 11 (PHP 8.3) — Despliegue en AWS ECS (Fargate/EC2) con CloudFormation + Ejecución Local

> **Resumen**: Guía práctica, paso a paso, para empaquetar y desplegar un backend **Laravel 11** (PHP 8.3) en **AWS ECS** detrás de un **Application Load Balancer (ALB)**, usando **Amazon ECR** para imágenes y **AWS CloudFormation** para la infraestructura (VPC, Subnets, SG, ALB, ECS, RDS opcional). Incluye cómo correr **en local** con Docker (y `docker-compose` opcional).  
> **Sin datos sensibles**: utiliza *placeholders* (reemplázalos por tus valores).

---

## 0) Requisitos

- **Docker** 24+
- **AWS CLI v2** configurado (`aws configure`)
- Cuenta AWS y permisos para: **ECR**, **ECS**, **CloudFormation**, **EC2 (VPC/SG/Subnets/ALB)** y **RDS** (si creas BD).
- **Certificado ACM** válido en `us-east-1` si vas a usar HTTPS en el ALB.
- **Composer** (usaremos imagen oficial para instalar deps).

---

## 1) Estructura esperada del repo

```
.
├─ app/                # Código Laravel
├─ bootstrap/
├─ config/
├─ database/
├─ public/
├─ resources/
├─ routes/
├─ storage/
├─ vendor/             # (se crea con composer install)
│
├─ Dockerfile
├─ nginx.conf
├─ docker-php.ini
├─ entrypoint.sh
├─ template.yml        # Plantilla CloudFormation (VPC + ALB + ECS + RDS opcional)
├─ params.json         # Parámetros para CloudFormation (SIN secretos reales)
├─ docker-compose.yml  # (opcional para local con Postgres)
└─ README.md
```

> Si aún no tienes estos archivos, más abajo están sus **plantillas** listas para copiar/pegar.

---

## 2) Variables de referencia (ajusta a tu cuenta)

- **Región**: `us-east-1`
- **ID de cuenta**: `<AWS_ACCOUNT_ID>`
- **Repositorio ECR**: `backend-tarks` (puedes usar otro nombre)
- **Imagen/Tag**: `backend-tasks:100` (o `backend-tarks:latest` —unifica tu convención)
- **Stack de CloudFormation**: `laravel-backend-stack`

---

## 3) Construcción y publicación de imagen en ECR

### 3.1 Login en ECR
```bash
aws ecr get-login-password --region us-east-1 \
| docker login --username AWS --password-stdin <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com
```

### 3.2 Build, tag y push
> Elige un único tag para evitar confusiones.

```bash
# Build
docker build -t backend-task:100 .

# Tag hacia tu repositorio ECR
docker tag backend-task:100 <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:100

# Push
docker push <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:100
```

> Si prefieres `latest`:
```bash
docker tag backend-task:100 <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:latest
docker push <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:latest
```

---

## 4) Despliegue de infraestructura/app con CloudFormation

Tu `template.yml` debe contemplar (según tu definición original):
- **VPC** + Subnets públicas/privadas + IGW/NAT opcional
- **Security Groups** (ALB, Servicio ECS, RDS opcional)
- **ALB**: Listener 80 (redirige a 443) y Listener 443 con **ACM**
- **ECS Cluster** + **TaskDefinition** (Fargate por defecto) + **Service**
- **CloudWatch Logs**
- **RDS PostgreSQL** *opcional* (si `UseExistingDb=false`)

### 4.1 `params.json` — plantilla (SIN secretos reales)
> Usa nombres **alfanuméricos** para ParameterKey (CloudFormation no acepta `_`).

```json
[
  { "ParameterKey": "ProjectName",       "ParameterValue": "laravel-ecs" },
  { "ParameterKey": "Env",               "ParameterValue": "prod" },
  { "ParameterKey": "AWSRegion",         "ParameterValue": "us-east-1" },

  { "ParameterKey": "LaravelImage",      "ParameterValue": "<AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:100" },
  { "ParameterKey": "ForceUpdate",       "ParameterValue": "1700000000" },

  { "ParameterKey": "DomainAccess",      "ParameterValue": "https://mi-frontend.com" },
  { "ParameterKey": "ACMCertificateArn", "ParameterValue": "arn:aws:acm:us-east-1:<AWS_ACCOUNT_ID>:certificate/XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX" },

  { "ParameterKey": "ContainerPort",     "ParameterValue": "80" },
  { "ParameterKey": "PhpFpmPort",        "ParameterValue": "9000" },
  { "ParameterKey": "DesiredCount",      "ParameterValue": "2" },

  { "ParameterKey": "VpcCIDR",           "ParameterValue": "10.0.0.0/16" },
  { "ParameterKey": "PublicSubnet1CIDR", "ParameterValue": "10.0.1.0/24" },
  { "ParameterKey": "PublicSubnet2CIDR", "ParameterValue": "10.0.2.0/24" },
  { "ParameterKey": "PrivateSubnet1CIDR","ParameterValue": "10.0.101.0/24" },
  { "ParameterKey": "PrivateSubnet2CIDR","ParameterValue": "10.0.102.0/24" },

  { "ParameterKey": "HealthCheckPath",   "ParameterValue": "/api/health" },

  { "ParameterKey": "LaravelAppKey",     "ParameterValue": "base64:REEMPLAZA_APP_KEY" },
  { "ParameterKey": "JWTSecret",         "ParameterValue": "REEMPLAZA_JWT_SECRET" },

  { "ParameterKey": "UseExistingDb",     "ParameterValue": "false" },
  { "ParameterKey": "PublicDbAccessCidr","ParameterValue": "0.0.0.0/0" },

  { "ParameterKey": "DBConnection",      "ParameterValue": "pgsql" },
  { "ParameterKey": "DBHost",            "ParameterValue": "" },
  { "ParameterKey": "DBPort",            "ParameterValue": "5432" },
  { "ParameterKey": "DBDatabase",        "ParameterValue": "tasksdb" },
  { "ParameterKey": "DBUsername",        "ParameterValue": "postgres" },
  { "ParameterKey": "DBPassword",        "ParameterValue": "REEMPLAZA_PASSWORD" },
  { "ParameterKey": "DBSSLMode",         "ParameterValue": "require" },

  { "ParameterKey": "DBInstanceClass",   "ParameterValue": "db.t4g.micro" },
  { "ParameterKey": "DBAllocatedStorage","ParameterValue": "20" },
  { "ParameterKey": "DBEngineVersion",   "ParameterValue": "16.3" },
  { "ParameterKey": "CreateNatGateway",  "ParameterValue": "false" }
]
```

> **Notas**  
> - `UseExistingDb=false` → CFN crea **RDS** y exporta su endpoint a la Task.  
> - `UseExistingDb=true`  → rellena `DBHost`, `DBUsername`, `DBPassword`, etc.  
> - `ForceUpdate` (string) → cambia su valor (p.ej. epoch) para forzar nueva **TaskDefinition** aun sin cambios en plantilla.

### 4.2 Crear stack (primera vez)

```bash
aws cloudformation create-stack \
  --stack-name laravel-backend-stack \
  --template-body file://template.yml \
  --parameters file://params.json \
  --capabilities CAPABILITY_NAMED_IAM \
  --region us-east-1
```

### 4.3 Actualizar stack (siguientes despliegues)

```bash
aws cloudformation update-stack \
  --stack-name laravel-backend-stack \
  --use-previous-template \
  --parameters file://params.json \
  --capabilities CAPABILITY_NAMED_IAM \
  --tags Key=force,Value=$(date +%s) \
  --region us-east-1
```

> **Evita typos**: usa `--region` (no `- -region`).

### 4.4 Consultar salidas

```bash
aws cloudformation describe-stacks \
  --stack-name laravel-backend-stack \
  --region us-east-1 \
  --query "Stacks[0].Outputs" --output table
```

Las salidas típicas incluyen `ALBURL` y `ALBURLHTTPS` (útiles para probar).

---

## 5) Archivos de configuración (plantillas listas)

### 5.1 `Dockerfile`

```dockerfile
# Etapa 1: instalar dependencias PHP (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-ansi --no-scripts --no-progress --prefer-dist

# Etapa 2: runtime PHP-FPM + Nginx en un solo contenedor
FROM php:8.3-fpm-alpine

# SO + extensiones PHP necesarias para Laravel + Postgres
RUN apk add --no-cache bash nginx curl git icu-dev libzip-dev oniguruma-dev postgresql-dev \
  && docker-php-ext-install intl mbstring zip pdo pdo_pgsql opcache

# Config PHP
COPY docker-php.ini /usr/local/etc/php/conf.d/docker-php.ini

# Nginx
RUN mkdir -p /run/nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Código de la app
WORKDIR /var/www/html
COPY . /var/www/html

# Vendors desde la etapa 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# Permisos mínimos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENV PHP_FPM_PORT=9000
CMD ["/usr/local/bin/entrypoint.sh"]
```

### 5.2 `nginx.conf`

```nginx
worker_processes auto;
events { worker_connections 1024; }

http {
  include       /etc/nginx/mime.types;
  default_type  application/octet-stream;
  sendfile      on;
  keepalive_timeout  65;

  server {
    listen 80;
    server_name _;

    root /var/www/html/public;
    index index.php index.html;

    # Healthcheck (ALB)
    location = /api/health {
      return 200 "OK";
      add_header Content-Type text/plain;
    }

    # Preflight CORS (ajusta a tu política real)
    if ($request_method = OPTIONS) {
      add_header Access-Control-Allow-Origin  $http_origin;
      add_header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS";
      add_header Access-Control-Allow-Headers "Authorization, Content-Type, X-Requested-With";
      add_header Access-Control-Max-Age 86400;
      return 204;
    }

    # Archivos estáticos
    location ~* \.(?:jpg|jpeg|png|gif|svg|css|js|ico|woff2?|ttf|map)$ {
      expires 7d;
      add_header Cache-Control "public";
      access_log off;
      try_files $uri =404;
    }

    # Rutas de Laravel
    location / {
      try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
      include fastcgi_params;
      fastcgi_index index.php;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_param DOCUMENT_ROOT $realpath_root;
      fastcgi_read_timeout 120;
      fastcgi_send_timeout 120;
      fastcgi_pass 127.0.0.1:9000;
    }

    location ~ /\.ht {
      deny all;
    }
  }
}
```

### 5.3 `docker-php.ini`

```ini
date.timezone = UTC
memory_limit = 512M
post_max_size = 32M
upload_max_filesize = 32M
max_execution_time = 120

; Opcache recomendado para prod
opcache.enable=1
opcache.enable_cli=1
opcache.validate_timestamps=0
opcache.max_accelerated_files=20000
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
```

### 5.4 `entrypoint.sh`

```bash
#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Esperar DB si hay variables
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

# Symlink storage
if [[ ! -e public/storage ]]; then
  php artisan storage:link || true
fi

# Migraciones (controladas por env RUN_MIGRATIONS=true|false)
: "${RUN_MIGRATIONS:=true}"
if [[ "$RUN_MIGRATIONS" == "true" ]]; then
  echo "Ejecutando migraciones..."
  php artisan migrate --force || true
fi

# Caches de prod
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Iniciar servicios
echo "Iniciando PHP-FPM..."
php-fpm -D

echo "Iniciando Nginx..."
exec nginx -g 'daemon off;'
```

### 5.5 `.env.example` (seguro para el repo)

```dotenv
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<ALB_or_Custom_Domain>
FRONTEND_URL=https://mi-frontend.com

LOG_CHANNEL=stack
LOG_LEVEL=info

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

DB_CONNECTION=pgsql
DB_HOST=<DB_HOST>
DB_PORT=5432
DB_DATABASE=tasksdb
DB_USERNAME=postgres
DB_PASSWORD=<DB_PASSWORD>
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_DEFAULT_REGION=us-east-1

JWT_SECRET=<JWT_SECRET>
JWT_ALGO=HS256
```

### 5.6 `docker-compose.yml` (opcional para local)

```yaml
version: "3.9"
services:
  app:
    build: .
    container_name: laravel_app
    ports:
      - "8080:80"
    env_file:
      - .env
    depends_on:
      - db
    volumes:
      - ./:/var/www/html
  db:
    image: postgres:16-alpine
    container_name: pg_db
    environment:
      POSTGRES_DB: tasksdb
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    ports:
      - "5432:5432"
    volumes:
      - pgdata:/var/lib/postgresql/data
volumes:
  pgdata:
```

---

## 6) Ejecución **en local**

### 6.1 Preparar dependencias

```bash
# Instalar dependencias PHP con imagen oficial de Composer
docker run --rm -v "$PWD":/app -w /app composer:2 bash -lc "composer install"
```

### 6.2 Correr con Docker (simple)

```bash
docker build -t backend-local:dev .
docker run --rm -p 8080:80 --env-file .env backend-local:dev
# Prueba health:
# http://localhost:8080/api/health
```

### 6.3 Correr con docker-compose (con Postgres local)

```bash
docker compose up --build
# Health:
# http://localhost:8080/api/health
```

---

## 7) Verificación post-despliegue (AWS)

- **ALB**: usa la salida `ALBURLHTTPS` del stack CloudFormation.
- **Healthcheck**: `GET https://<ALB_DNS>/api/health` → debe responder `200 OK`.
- **Logs**: ve a **CloudWatch Logs** → grupo `/ecs/<stack-name>`.
- **ECS Exec** (opc.): entra a la task para debug:
  ```bash
  aws ecs execute-command --region us-east-1 \
    --cluster <ECS_CLUSTER> \
    --task <TASK_ARN> \
    --container app \
    --interactive \
    --command "/bin/sh"
  ```

---

## 8) Notas sobre ECS **Fargate** vs **EC2**

- Esta guía asume **Fargate** (sin servidores).  
- Si tu **cluster usa EC2** (Capacity Provider EC2):  
  - Debes tener un **ASG** con instancias registradas al cluster.  
  - La **TaskDefinition** puede cambiar `RequiresCompatibilities: [EC2]` y omitir `awsvpc` si usas bridge/host (recomendado seguir `awsvpc`).  
  - Asegúrate de que los **SG/Subnets** permitan tráfico desde el ALB hacia las instancias (puerto del contenedor).  
- En ambos casos, los pasos de **ECR**, **ALB** y **Service** son equivalentes a alto nivel.

---

## 9) Problemas comunes & soluciones rápidas

- **CORS bloqueado**: no mezcles `*` con un origen específico. Ajusta `nginx.conf` y `config/cors.php`.
- **422 (Unprocessable Content)**: revisa validaciones de Laravel y el payload del frontend.
- **500 / clases no encontradas**: verifica namespaces, `composer dump-autoload`, permisos de `storage` y `bootstrap/cache`.
- **Timeout a RDS**: en el SG de RDS habilita `5432` desde el **SG del Service ECS** y (opc) tu `IP/32` si usarás DBeaver. Comprueba `PubliclyAccessible=true` si conectas desde internet.
- **CFN `Parameter name ... is non alphanumeric`**: usa nombres **CamelCase** sin `_`.
- **ECS Exec `TargetNotConnected`**: espera a que la task esté **RUNNING** y con **ExecuteCommand** habilitado en el Service.

---

## 10) Seguridad y limpieza

- Nunca subas **APP_KEY**, **JWT_SECRET** o passwords al repo. Usa **NoEcho** en CFN o **Secrets Manager**/**SSM**.
- **Eliminar**:
  ```bash
  aws cloudformation delete-stack --stack-name laravel-backend-stack --region us-east-1
  aws ecr delete-repository --repository-name backend-tarks --force --region us-east-1
  ```

---

## 11) Apéndice — Comandos rápidos (resumen)

```bash
# 1) Login ECR
aws ecr get-login-password --region us-east-1 \
| docker login --username AWS --password-stdin <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com

# 2) Build & Push
docker build -t backend-task:100 .
docker tag backend-task:100 <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:100
docker push <AWS_ACCOUNT_ID>.dkr.ecr.us-east-1.amazonaws.com/backend-tarks:100

# 3) Crear stack (primera vez)
aws cloudformation create-stack \
  --stack-name laravel-backend-stack \
  --template-body file://template.yml \
  --parameters file://params.json \
  --capabilities CAPABILITY_NAMED_IAM \
  --region us-east-1

# 4) Actualizar stack
aws cloudformation update-stack \
  --stack-name laravel-backend-stack \
  --use-previous-template \
  --parameters file://params.json \
  --capabilities CAPABILITY_NAMED_IAM \
  --tags Key=force,Value=$(date +%s) \
  --region us-east-1

# 5) Salidas
aws cloudformation describe-stacks \
  --stack-name laravel-backend-stack \
  --region us-east-1 \
  --query "Stacks[0].Outputs" --output table
```

---

### Créditos
Guía preparada a partir de tus especificaciones: **Laravel 11, PHP 8.3, Nginx, Postgres, ECS (Fargate/EC2), ALB, ECR y CloudFormation**, con healthcheck en `/api/health` y envs seguros.
