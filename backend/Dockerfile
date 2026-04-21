FROM php:8.2-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev git unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && a2dismod mpm_event mpm_worker || true \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride[[:space:]]+None/AllowOverride All/g' /etc/apache2/apache2.conf

# Add custom CORS configuration directly to Apache
RUN echo '<IfModule mod_headers.c>\n\
    SetEnvIf Origin "^https://civic-connect-topaz-five\.vercel\.app$" ACAO=$0\n\
    Header always set Access-Control-Allow-Origin %{ACAO}e env=ACAO\n\
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"\n\
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin"\n\
    Header always set Access-Control-Allow-Credentials "true"\n\
    Header always set Access-Control-Max-Age "3600"\n\
    \n\
    # Handle preflight OPTIONS requests\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_METHOD} OPTIONS\n\
    RewriteRule ^(.*)$ $1 [R=200,L]\n\
</IfModule>' > /etc/apache2/conf-available/cors.conf \
    && a2enconf cors

COPY . /var/www/html/
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

EXPOSE 80