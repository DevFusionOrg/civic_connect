FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Completely disable all MPMs then enable only prefork
RUN a2dismod mpm_event mpm_worker || true && \
    a2enmod mpm_prefork

COPY . /var/www/html/

RUN a2enmod rewrite

# Create a custom Apache config to force prefork
RUN echo "LoadModule mpm_prefork_module modules/mod_mpm_prefork.so" > /etc/apache2/conf-available/mpm.conf && \
    a2enconf mpm

EXPOSE 80

CMD ["apache2-foreground"]