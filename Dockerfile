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
# FIX: Force disable conflicting MPMs and ensure prefork is enabled
RUN a2enmod rewrite \
    && a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork

# Copy your PipraPay application files into the server directory
COPY . /var/www/html/

# Set correct permissions for the Apache web directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 for traffic
EXPOSE 80
