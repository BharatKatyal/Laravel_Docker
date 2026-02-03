# Laravel_Docker

brew install php

php --version


# Run PHP container with current directory mounted                                                 
docker run -it -v $(pwd):/app -w /app php:8.2-cli bash     

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
apt-get update && apt-get install -y git zip unzip libzip-dev docker-php-ext-install zip pdo pdo_mysql bcmath                                                    


--------------------------------




I created a new broadcaster service and Im looking to build out the infra. lets start with a dockerfile for it. keep it lean


docker run -d --name laravel-app-docker-mysql -e MYSQL_ROOT_PASSWORD=root_password -e MYSQL_DATABASE=todo_db -e MYSQL_USER=todo_user -e MYSQL_PASSWORD=todo_password -p 3306:3306 mysql:8.0



php artisan migrate:fresh



cd new-broadcaster && npm install
Docker file






https://phpdocker.io/


docker build -t broadcaster:old .