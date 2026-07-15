# Use an official PHP image with Apache pre-installed (PHP 8.2)
FROM php:8.2-apache

# Install required system dependencies and PHP extensions for PipraPay
# Added libmagickwand-dev (for Imagick), libzip-dev (for ZipArchive)
# and libonig-dev (for mbstring)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath zip mbstring exif \
    && pecl install imagick mongodb \
    && docker-php-ext-enable imagick mongodb

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# FIX 1: Allow and read the .htaccess file in your root folder
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# FIX 2: SSL Reverse Proxy Fix for Railway (Forces PHP to recognize HTTPS)
RUN echo 'SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on' >> /etc/apache2/apache2.conf

# Recommended PHP runtime settings
RUN { \
        echo 'allow_url_fopen = On'; \
        echo 'file_uploads = On'; \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'memory_limit = 256M'; \
    } > /usr/local/etc/php/conf.d/piprapay.ini

# Copy your PipraPay application files into the server directory
COPY . /var/www/html/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Run Composer Install (ensures mongodb library is downloaded)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-mongodb

# FIX 3: Set proper directory and file permissions for assets
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Expose port 80 for traffic
EXPOSE 80

# RUNTIME FIX: Deletes conflicting MPMs right when the container turns on,
# then boots Apache normally. This defeats any hidden Railway configuration overrides.
CMD sh -c "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && apache2-foreground"
