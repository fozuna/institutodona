FROM php:8.2-apache

# Instalar extensões MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Ativar mod_rewrite
RUN a2enmod rewrite

# Alterar DocumentRoot para public_html
ENV APACHE_DOCUMENT_ROOT /var/www/html/public_html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar arquivos
COPY . /var/www/html/

WORKDIR /var/www/html

EXPOSE 80
