FROM php:8.2-apache

# Instalar extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Ativar mod_rewrite
RUN a2enmod rewrite

# Copiar arquivos
COPY . /var/www/html/

WORKDIR /var/www/html

# Ajustar DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/html/public_html|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
