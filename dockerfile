# FROM  composer:latest as composer_container

# WORKDIR /html
# COPY ./ /html
# RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-progress --no-interaction --no-dev --no-autoloader

# RUN composer dump-autoload --optimize --apcu --no-dev

FROM php:apache-buster

WORKDIR /var/www/html

# RUN apt update
# # RUN apt install -y \
# #         libfreetype6-dev \
# #         libjpeg62-turbo-dev \
# #         libpng-dev \
# RUN docker-php-ext-install mysqli pdo_mysql
# RUN docker-php-ext-enable mysqli pdo_mysql


# COPY --from=composer_container /html /var/www/html

# CMD [ "php composer.phar require -n" ]
# ENTRYPOINT /var/www/html/initial.sh

RUN apt update
RUN apt-get install supervisor --assume-yes
# RUN apt-get install add-apt-repository
# RUN add-apt-repository universe
# RUN apt update
# RUN apt install supervisor
RUN apt-get clean

COPY supervisord.conf /etc/supervisord.conf
ENTRYPOINT ["supervisord","-c","/etc/supervisord.conf"]
