

# Project Title
Online Library

## Installation
1. Clone the repository:
```bash
 git clone https://github.com/bulatovicb/OnlineLibrary
```
2. Install dependencies:
```
# Installing Sail 
composer require laravel/sail --dev
php artisan sail:install
./vendor/bin/sail up
# Install redis

$ docker run -d --name redis-stack -p 6381:6379 -p 8001:8001 redis/redis-stack:latest
 ```
 3.  Add Docker to System PATH
 ```
 C:\Program Files\Docker\Docker\resources\bin

 4. Install Pint and run it
 composer require --dev laravel/pint
 ./vendor/bin/pint

 5. Install Pest and set it up
 composer require --dev pestphp/pest
    php artisan pest:install



  ```
# Online Library Api Documentation

The .json file is located in the `/postman` directory within the project.
In Postman import online_library_postman_collection.json