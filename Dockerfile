FROM ubuntu:24.04

LABEL maintainer="Seapedia Developer"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Asia/Jakarta

WORKDIR /var/www/html

# Install system dependencies + PHP + Nginx in one layer
RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y \
        gnupg curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev \
        nginx \
        php8.3-fpm \
        php8.3-mysql \
        php8.3-xml \
        php8.3-mbstring \
        php8.3-curl \
        php8.3-zip \
        php8.3-bcmath \
        php8.3-intl \
        php8.3-redis \
        php8.3-soap \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

# Copy application files
COPY . /var/www/html

# Install PHP dependencies (no dev, optimized for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/artisan

# Configure PHP-FPM
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.3/fpm/php.ini \
    && sed -i 's/listen = \/run\/php\/php8.3-fpm.sock/listen = 127.0.0.1:9000/' /etc/php/8.3/fpm/pool.d/www.conf \
    && sed -i 's/^daemon off;//' /etc/nginx/nginx.conf

# Copy nginx config
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

EXPOSE 80

CMD service php8.3-fpm start && nginx
