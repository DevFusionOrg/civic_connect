FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Disable conflicting MPM modules and enable only prefork
RUN a2dismod mpm_event && \
    a2dismod mpm_worker && \
    a2enmod mpm_prefork

COPY . /var/www/html/

RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]