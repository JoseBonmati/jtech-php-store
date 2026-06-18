# Usa la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala las extensiones necesarias para conectarnos a MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitam mod_rewrite de Apache
RUN a2enmod rewrite