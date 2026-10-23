# Apache + PHP, the classic. Listens on $PORT (Railway sets it; 8080 otherwise).
# Pinned to an exact patched release (PHP 8.5.12, security release of 2026-10-21); bump it when PHP ships the next one.
FROM php:8.5.12-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/app/public \
    PORT=8080

# OPcache is built into PHP 8.5; older images ship it as an extension to switch on.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && if [ -f "$(php-config --extension-dir)/opcache.so" ]; then docker-php-ext-enable opcache; fi \
 && a2enmod rewrite headers deflate \
 && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# The base image turns off the other MPMs by deleting their links; on Railway a deleted link can come back and Apache
# then dies with "More than one MPM loaded". Empty files are writes, not deletes, so they stay as written.
RUN for f in mpm_event.load mpm_event.conf mpm_worker.load mpm_worker.conf; do \
      rm -f /etc/apache2/mods-enabled/$f; \
      : > /etc/apache2/mods-enabled/$f; \
    done

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-unhackable.ini"
COPY docker/apache.conf /etc/apache2/conf-available/unhackable.conf
RUN a2enconf unhackable

COPY src /var/www/app/src
COPY public /var/www/app/public

# Refuse to build if any PHP file has a syntax error.
RUN find /var/www/app -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null \
 && apache2ctl -t
