# Laravel Docker Setup

## Overview

This project uses a multi-stage Docker build:
- **Stage 1 (builder)**: Installs Composer, dependencies, and optimizes the app
- **Stage 2 (production)**: Minimal runtime image with only what's needed to run

Benefits of multi-stage:
- Smaller final image (no git, zip, composer in production)
- Better security (fewer tools = smaller attack surface)
- Faster deployments

---

## Quick Start

### 1. Build the Image

```bash
# Standard build
docker build -t my-laravel-app .

# Force fresh build (no cache)
docker build --no-cache -t my-laravel-app .

# Build with multi-stage

docker build -f Dockerfile.multistage -t my-laravel-app-multistage .

# Build and time it
time docker build --no-cache -t my-laravel-app .
docker build --no-cache -t my-laravel-app .  0.20s user 0.22s system 0% cpu 48.081 total

time docker build --no-cache -f Dockerfile.multistage -t my-laravel-app-multistage .
docker build --no-cache -f Dockerfile.multistage -t my-laravel-app-multistage  0.24s user 0.29s system 0% cpu 55.313 total

Build Size
docker images my-laravel-app
my-laravel-app:latest   14f384b6264b        292MB         70.7MB        
docker images my-laravel-app-multistage
my-laravel-app-multistage:latest   559d0ee3992e        268MB         60.8MB        

The results 

docker build --no-cache -f Dockerfile.multistage -t my-laravel-app .  0.23s user 0.25s system 0% cpu 55.579 total


# Build specific stage only (useful for debugging)
docker build --target builder -t my-laravel-app:builder .
```

### 2. Run MySQL Container

```bash
docker network create laravel-net

docker run -d \
  --name laravel-app-docker-mysql \
  --network laravel-net \
  -e MYSQL_ROOT_PASSWORD=root_password \
  -e MYSQL_DATABASE=todo_db \
  -e MYSQL_USER=todo_user \
  -e MYSQL_PASSWORD=todo_password \
  -p 3306:3306 \
  mysql:8.0
```

### 3. Run Laravel Container

```bash
docker run -d -p 8080:8080 \
  --network laravel-net \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=laravel-app-docker-mysql \
  -e DB_PORT=3306 \
  -e DB_DATABASE=todo_db \
  -e DB_USERNAME=todo_user \
  -e DB_PASSWORD=todo_password \
  my-laravel-app
```

Visit: http://localhost:8080

---

## Alternative Run Configurations

### Connect to MySQL on Host Machine (Mac)

```bash
docker run -d -p 8080:8080 \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=todo_db \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=root_password \
  my-laravel-app
```

### Using SQLite (No MySQL Needed)

```bash
docker run -d -p 8080:8080 \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_CONNECTION=sqlite \
  my-laravel-app
```

---

## Useful Commands

### Image Info

```bash
# Check image size
docker images my-laravel-app

# See layer breakdown (what adds size)
docker history my-laravel-app

# Inspect image details
docker inspect my-laravel-app
```

### Container Management

```bash
# List running containers
docker ps

# List all containers (including stopped)
docker ps -a

# Stop all running containers
docker stop $(docker ps -q)

# Remove all stopped containers
docker rm $(docker ps -aq)

# View container logs
docker logs <container_id>

# Follow logs in real-time
docker logs -f <container_id>
```

### Debugging

```bash
# Shell into running container
docker exec -it <container_id> sh

# Check Laravel logs inside container
docker exec -it <container_id> cat /var/www/html/storage/logs/laravel.log

# Run artisan commands
docker exec -it <container_id> php artisan migrate
docker exec -it <container_id> php artisan config:clear

# Check if services are running
docker exec -it <container_id> ps aux
```

### Cleanup

```bash
# Remove unused images
docker image prune

# Remove all unused data (images, containers, networks)
docker system prune

# Nuclear option - remove everything
docker system prune -a --volumes
```

---

## Local Development Setup (Without Docker)

### Install PHP & Composer

```bash
# macOS
brew install php
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Verify
php --version
composer --version
```

### Create New Laravel Project

```bash
composer create-project laravel/laravel my-app
cd my-app
php artisan serve
```

### Or Use Docker for Local Dev

```bash
docker run -it -v $(pwd):/app -w /app php:8.3-cli bash
# Now you're in a PHP container with your code mounted
```

---

## Laravel Pulse Setup

```bash
# Install
composer require laravel/pulse

# Publish config and migrations
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"

# Run migrations
php artisan migrate
```

---

## Dockerfile Reference

### Single-Stage (Simple)

```dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions Laravel needs
RUN apk add --no-cache \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql bcmath opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Finish composer install and optimize
# NOTE: We skip config:cache here because it bakes config at build time.
# Since APP_KEY and DB credentials are passed at runtime via environment
# variables, caching config during build would ignore those values.
RUN composer dump-autoload --optimize \
    && php artisan route:cache \
    && php artisan view:cache

# Copy nginx and supervisor config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Set up non-root user
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /run \
    /var/lib/nginx \
    /var/log/nginx

USER www-data

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
```

### Multi-Stage (Optimized)

```dockerfile
###################
# STAGE 1: Build
###################
FROM php:8.3-fpm-alpine AS builder

# Install build dependencies
RUN apk add --no-cache \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql bcmath opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (including dev for building)
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Finish composer install and optimize (production, no dev dependencies)
RUN composer install --no-dev --optimize-autoloader

# Cache routes and views (but NOT config - that uses runtime env vars)
RUN php artisan route:cache \
    && php artisan view:cache


###################
# STAGE 2: Production
###################
FROM php:8.3-fpm-alpine AS production

# Install only runtime dependencies (no build tools)
RUN apk add --no-cache \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql bcmath opcache

WORKDIR /var/www/html

# Copy only what we need from builder stage
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/bootstrap ./bootstrap
COPY --from=builder /var/www/html/public ./public
COPY --from=builder /var/www/html/routes ./routes
COPY --from=builder /var/www/html/config ./config
COPY --from=builder /var/www/html/app ./app
COPY --from=builder /var/www/html/resources ./resources
COPY --from=builder /var/www/html/storage ./storage
COPY --from=builder /var/www/html/database ./database
COPY --from=builder /var/www/html/artisan ./artisan
COPY --from=builder /var/www/html/composer.json ./composer.json

# Copy nginx and supervisor config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Set up non-root user
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /run \
    /var/lib/nginx \
    /var/log/nginx

USER www-data

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
```

---

## Supporting Config Files

### docker/nginx.conf

```nginx
server {
    listen 8080;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### docker/supervisord.conf

```ini
[supervisord]
nodaemon=true
user=www-data

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
```

### .dockerignore

```
.git
.env
node_modules
vendor
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
tests
.phpunit.result.cache
```

---

## Build Performance

| Build Type | Time | Image Size |
|------------|------|------------|
| No cache | ~53s | 292MB |
| With cache | ~5s | 292MB |
| Multi-stage | ~60s | ~180MB |

---

## Important Notes

### Why No config:cache?

We skip `php artisan config:cache` in the Dockerfile because it bakes configuration at build time. Since APP_KEY and database credentials are passed at runtime via environment variables, caching config during build would ignore those values.

### Why Non-Root User?

Running as non-root (www-data) is a security best practice. It limits the damage if the container is compromised. We use port 8080 internally because binding to port 80 requires root.

### Layer Ordering

Put things that change least frequently at the top of the Dockerfile:
1. Base image
2. System dependencies  
3. Composer dependencies
4. Application code (changes most often)

This maximizes Docker's build cache efficiency.

### Docker Networking

When containers need to communicate:
- Create a network: `docker network create laravel-net`
- Connect containers to it: `--network laravel-net`
- Use container names as hostnames: `DB_HOST=laravel-app-docker-mysql`

### Connecting to Host Services

To connect from a container to services running on your Mac, use `host.docker.internal` as the hostname.








Creating the Docker File

Layer ordering matters: Put things that change least frequently at the top (base image, dependencies) and things that change often (your code) at the bottom. This maximizes cache efficiency.





Setting up Laravel Locally  ( or you can start inside a docker container by running docker run -it -v $(pwd):/app -w /app php:8.2-cli bash)
# Run PHP container with current directory mounted                                                 


# Install PHP

brew install php


php --version
  

# Install Composer 
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

apt-get update && apt-get install -y git zip 

# Create Laravel project                                                                           
composer create-project laravel/laravel laravel-app-docker


# After creation, navigate to the project and start the server:  
cd laravel-app-docker
php artisan serve

                                                                                                     
# I did not install these, are they required?                                 
apt-get install -y  zip unzip libzip-dev                                                      


--------------------------------

Laraval Pulse Install 
You may install Pulse using the Composer package manager:


```
composer require laravel/pulse
```
Next, you should publish the Pulse configuration and migration files using the vendor:publish Artisan command:

```
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
```

Finally, you should run the migrate command in order to create the tables needed to store Pulse's data:

```
php artisan migrate
```





docker build -t my-laravel-app .

docker build --no-cache -t my-laravel-app .


time docker build --no-cache -t my-laravel-app .

docker build --no-cache -t my-laravel-app .  0.21s user 0.24s system 0% cpu 53.265 total

docker images my-laravel-app

my-laravel-app:latest   52a8a8cff4b3        292MB         70.7MB        

docker run -d -p 8082:80 \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_HOST=localhost \
  -e DB_DATABASE=todo_db \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=root_password \
  my-laravel-app



docker run -d -p 8084:80 \
  -e APP_KEY=base64:Na0tjOXG5jsSESnbeVc0SMxZ6dQPwGCCE8QSxWCMCc4= \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=todo_db \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=root_password \
  my-laravel-app

I created a new broadcaster service and Im looking to build out the infra. lets start with a dockerfile for it. keep it lean


docker run -d --name laravel-app-docker-mysql -e MYSQL_ROOT_PASSWORD=root_password -e MYSQL_DATABASE=todo_db -e MYSQL_USER=todo_user -e MYSQL_PASSWORD=todo_password -p 3306:3306 mysql:8.0



php artisan migrate:fresh



cd new-broadcaster && npm install
Docker file






https://phpdocker.io/


docker build -t broadcaster:old .