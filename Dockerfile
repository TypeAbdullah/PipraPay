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

# Enable Apache mod_rewrite (critical for clean routing/API paths)
RUN a2enmod rewrite

# Copy your PipraPay application files into the server directory
COPY . /var/www/html/

# FIX FOR CSS/ASSETS: Set strict and correct permissions so Apache can read styles/scripts
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# OPTIONAL FRAMEWORK CONFIG: If PipraPay uses a "public" folder as its entrypoint, 
# uncomment the line below by removing the '#' so Apache routes directly to it:
# RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose port 80 for traffic
EXPOSE 80

# RUNTIME FIX: Deletes conflicting MPMs right when the container turns on, 
# then boots Apache normally. This defeats any hidden Railway configuration overrides.
CMD sh -c "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && apache2-foreground"
