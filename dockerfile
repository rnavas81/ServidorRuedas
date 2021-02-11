#Desacrga la imagen
FROM php:apache-buster

#Fichero para solucionar los problemas de rutas en Laravel
COPY ./000-default.conf /etc/apache2/sites-available/

    # Actualiza el sistema
RUN apt-get --yes update && apt-get --yes upgrade && apt --yes autoremove && apt-get --yes clean\
    # Instala Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer &&\
    # Instala las dependencias necesarias
    apt-get install -y libmcrypt-dev openssl zip unzip git &&\
    # Instala las necesidades para la persistencia de datos
    docker-php-ext-install mysqli pdo_mysql  &&\
    docker-php-ext-enable mysqli pdo_mysql  &&\
    # Recarga el vhost para aplicar los cambios necesarios
    a2enmod headers  &&\
    a2enmod rewrite  &&\
    service apache2 restart  &&\
    # Limpia los residuos de las instalaciones
    apt-get clean

# Directorio de trabajo
WORKDIR /var/www/html/

# Abre el puerto para el acceso
EXPOSE 80
