FROM php:8.2-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libpq-dev libzip-dev git unzip \
	&& docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql zip \
	&& apt-get clean \
	&& rm -rf /var/lib/apt/lists/* \
	&& a2dismod mpm_event mpm_worker || true \
	&& a2enmod rewrite \
	&& sed -ri 's/AllowOverride[[:space:]]+None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY . /var/www/html/
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

EXPOSE 80