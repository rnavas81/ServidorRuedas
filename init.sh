#!/bin/bash
echo "\n******* Actualizando los permisos de la carpeta..."
chmod 777 -R .

echo "\n******* Creando los contenedores..."
docker-compose up -d

echo "\n******* Copiando el fichero de variables del entorno..."
docker exec -ti carshare-server sh -c "cp .env.example .env"

echo "\n******* Recuperando las dependencias necesarias de Laravel..."
docker exec -ti carshare-server sh -c "php composer.phar require -n"

echo "\n******* Generando la clave de seguridad..."
docker exec -ti carshare-server sh -c "php artisan key:generate --force"

echo "\n******* Creando bases de datos..."
docker exec -ti carshare-server sh -c "php artisan migrate"

echo "\n******* Creando las instancias necesarias para passport..."
docker exec -ti carshare-server sh -c "php artisan passport:install --force"

echo "\n******* Rellenando la base de datos..."
docker exec -ti carshare-server sh -c "php artisan db:seed"

sleep 10s

echo ""
echo "      ╔════════════════════╗"
echo "      ║                    ║"
echo "      ║  APLICACIÓN LISTA  ║"
echo "      ║                    ║"
echo "      ╚════════════════════╝\n"
echo "Puede acceder a la aplicación desde su navegador en la ruta:  http://carshare.server.local:81"
echo "\nSi la aplicación no funciona compruebe su fichero hosts y agrege la linea:\n127.0.0.1   carshare.server.local"
echo "Para Windows: C:\Windows\System32\drivers\etc\hosts"
echo "Para Linux: /etc/hosts"
echo "Para Mac: /private/etc/hosts"
echo "\n"
