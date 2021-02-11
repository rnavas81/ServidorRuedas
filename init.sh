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
echo ""
echo "      ╔════════════════════╗"
echo "      ║                    ║"
echo "      ║  APLICACIÓN LISTA  ║"
echo "      ║                    ║"
echo "      ╚════════════════════╝\n"
echo "Puede acceder a la aplicación desde su navegador en la ruta:  http://server-ruedas.com:81"
echo "\nSi la aplicación no funciona compruebe su fichero hosts y agrege la linea:\n127.0.0.1   server-ruedas.com"
echo "Para Windows: C:\Windows\System32\drivers\etc\hosts"
echo "Para Linux: /etc/hosts"
echo "Para Mac: /private/etc/hosts"
echo "\n"
