FROM php:8.5-apache

# Install PgSQL extension
RUN apt-get -q update && apt-get install -q -y libpq-dev && \
    docker-php-ext-install pgsql

# Install Xdebug extension
RUN pecl install xdebug-3.5.0 && docker-php-ext-enable xdebug

# Configure PHP.ini for debugging
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/app/xdebug.ini /tmp
RUN mv /tmp/xdebug.ini "$PHP_INI_DIR/conf.d/99-xdebug.ini"

# Add .phar extension to Apache config
RUN echo "AddType application/x-httpd-php .phar" >> /etc/apache2/apache2.conf

# Copy files over and set permissions
COPY app /var/www/html
RUN chown -R www-data:www-data /var/www/html