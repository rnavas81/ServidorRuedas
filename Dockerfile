#Desacrga la imagen
FROM php:apache-buster

#Fichero para solucionar los problemas de rutas en Laravel
COPY ./000-default.conf /etc/apache2/sites-available/

# Instala las extensiones de mysqli y reescribe el vhost por defecto para solucionar los problemas de laravel
RUN apt-get install -y libpq-dev\
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install mysqli pdo_mysql
RUN docker-php-ext-enable mysqli pdo_mysql
RUN a2enmod headers
RUN a2enmod rewrite
RUN service apache2 restart
RUN apt-get clean

WORKDIR /var/www/html/

CMD [ "php", "artisan", "migrate" ]


EXPOSE 80

# Para la verisón definitiva copia los ficheros de la aplicación en la imagen
# COPY ./html /var/www/html
