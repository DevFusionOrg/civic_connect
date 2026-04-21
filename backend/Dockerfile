FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Use Railway's PORT variable
ENV APACHE_LISTEN_PORT=${PORT}

COPY . /var/www/html/

RUN a2enmod rewrite

EXPOSE ${PORT}

CMD ["apache2-foreground"]