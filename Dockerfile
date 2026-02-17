FROM php:8.2-apache

# Ativar mod_rewrite
RUN a2enmod rewrite

# Copiar arquivos para o servidor
COPY . /var/www/html/

# Definir webroot como public_html
WORKDIR /var/www/html

# Ajustar Apache para usar public_html como DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/html/public_html|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
