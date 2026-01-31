# Imagen base con PHP 8.1 y Apache
FROM php:8.1-apache

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar el DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar AllowOverride All para .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copiar archivos del proyecto
COPY . /var/www/html/

# Crear directorio de uploads y dar permisos
RUN mkdir -p /var/www/html/uploads/logos && \
    chmod -R 755 /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads

# Crear directorio de logs
RUN mkdir -p /var/www/html/logs && \
    chmod -R 755 /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/logs

# Exponer puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]
