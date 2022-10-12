FROM php:8.1-apache

# add dependencies
RUN apt-get update && apt-get install -y \
      mariadb-client-10.5 \
      tar \
      unzip \
      vim \
    && /usr/local/bin/docker-php-ext-install -j$(nproc) mysqli pdo_mysql opcache

# use custom php configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# copy site and confs
ARG build_type
COPY ./ /var/www/html/
COPY ./docker/*.inc /var/www/html/config/
COPY ./docker/$build_type.conf /etc/apache2/sites-available

RUN a2ensite $build_type.conf && a2enmod rewrite
RUN rm /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www/html/config

EXPOSE 80 443
CMD ["apache2-foreground"]
