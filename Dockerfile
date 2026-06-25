# Use the official PHP image with Apache
FROM php:8.2-apache

# Install necessary PHP extensions for MySQL connection
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for clean URLs and routing
RUN a2enmod rewrite