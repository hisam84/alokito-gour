# Base Image with PHP 8.2 & Apache
FROM php:8.2-apache

# Install required system libraries for GD (WebP, JPEG, PNG, FreeType) & SQLite
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for clean URLs (.htaccess)
RUN a2enmod rewrite

# Allow .htaccess overrides in Apache
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Custom PHP settings for uploads & memory
RUN echo "upload_max_filesize = 20M\npost_max_size = 25M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY . /var/www/html/

# Ensure web server has proper write permissions for SQLite & image uploads
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/database /var/www/html/assets/uploads

# Expose HTTP port
EXPOSE 80

# Dynamically listen on $PORT (Render assigns $PORT, fallback to 80)
CMD ["sh", "-c", "sed -i \"s/80/${PORT:-80}/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]

