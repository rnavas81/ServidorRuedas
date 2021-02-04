# Descarga la imagen
FROM mariadb:10.5.8

# Abre el puerto de comunicación con el resto de contenedores
EXPOSE 3306

# Copiamos los ficheros sql para que se ejecuten en el primer arranque del contenedor
COPY . /docker-entrypoint-initdb.d/

