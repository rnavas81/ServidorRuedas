#Desacrga la imagen
FROM php:apache-buster

#Fichero para solucionar los problemas de rutas en Laravel
COPY ./000-default.conf /etc/apache2/sites-available/

# Instala las extensiones necesarias
RUN apt-get --yes update && apt-get --yes upgrade && apt --yes autoremove && apt-get --yes clean\
    # Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer &&\
    apt-get install -y libmcrypt-dev openssl zip unzip &&\
    # Necesidades para la persistencia de datos
    docker-php-ext-install mysqli pdo_mysql  &&\
    docker-php-ext-enable mysqli pdo_mysql  &&\
    # Recarga el vhost para aplicar los cambios necesarios
    a2enmod headers  &&\
    a2enmod rewrite  &&\
    service apache2 restart  &&\
    # Limpia los residuos de las instalaciones
    apt-get clean

WORKDIR /var/www/html/


EXPOSE 80

# Para la verisón definitiva copia los ficheros de la aplicación en la imagen
# COPY ./html /var/www/html
