FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Remove ALL MPM modules completely
RUN rm -f /etc/apache2/mods-available/mpm_event.load && \
    rm -f /etc/apache2/mods-available/mpm_worker.load && \
    rm -f /etc/apache2/mods-enabled/mpm_event.load && \
    rm -f /etc/apache2/mods-enabled/mpm_worker.load && \
    rm -f /etc/apache2/mods-enabled/mpm_prefork.load

# Ensure only prefork is enabled
RUN a2enmod mpm_prefork

COPY . /var/www/html/

RUN a2enmod rewrite

# Disable other modules that might cause issues
RUN a2dismod -f mpm_event mpm_worker 2>/dev/null || true

EXPOSE 80

CMD ["apache2-foreground"]