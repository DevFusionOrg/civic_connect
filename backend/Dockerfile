FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
	&& a2enmod rewrite \
	&& sed -ri 's!/var/www/html!/var/www/html/api!g' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

RUN printf '%s\n' '<Directory /var/www/html/api>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/civic-connect.conf \
	&& a2enconf civic-connect

WORKDIR /var/www/html/api

EXPOSE 80