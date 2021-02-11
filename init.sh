#!/bin/bash

echo "\n******* Pone en marcha los contenedores de forma desatendida..."
docker-compose up -d

echo "\n******* Copia el fichero de variables del entorno"
docker exec -ti carshare-servidor sh -c "cp .env.example .env"

echo "\n******* Recupera las dependencias necesarias de Laravel"
docker exec -ti carshare-servidor sh -c "php composer.phar require -n"

echo "\n******* Crea la clave de seguridad"
docker exec -ti carshare-servidor sh -c "php artisan key:generate --force"

echo "\n******* Crea las bases de datos"
docker exec -ti carshare-servidor sh -c "php artisan migrate"

echo "\n******* Crea las instancias necesarias para passport"
docker exec -ti carshare-servidor sh -c "php artisan passport:install --force"

echo "\n******* Rellena las bases de datos"
docker exec -ti carshare-servidor sh -c "php artisan db:seed"

