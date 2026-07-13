# Use an official PHP image with Apache pre-installed (PHP 8.2)
FROM php:8.2-apache

# Install required system dependencies and PHP extensions for PipraPay
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your PipraPay application files into the server directory
COPY . /var/www/html/

# Set correct permissions for the Apache web directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for traffic
EXPOSE 80

# RUNTIME FIX: Deletes conflicting MPMs right when the container turns on, 
# then boots Apache normally. This defeats any hidden Railway configurations.
CMD sh -c "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && apache2-foreground"
