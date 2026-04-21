FROM php:8.2-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libpq-dev \
	&& docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql \
	&& apt-get clean \
	&& rm -rf /var/lib/apt/lists/* \
	&& a2dismod mpm_event mpm_worker || true \
	&& a2enmod rewrite

COPY . /var/www/html/

EXPOSE 80