# Use an official PHP image with Apache
FROM php:8.2-apache

# Install the PHP extensions you need
# pdo_mysql is for MySQL/MariaDB
# pdo_pgsql is for PostgreSQL
# Keep the one(s) you decide to use
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Copy your entire project directory into the Apache web root
COPY . /var/www/html/

# Optional: Enable Apache's mod_rewrite for "clean URLs" if your router.php needs it
RUN a2enmod rewrite