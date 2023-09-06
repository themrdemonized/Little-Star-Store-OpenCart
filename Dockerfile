# FROM php:7.3-fpm-buster
# FROM php:7.3-apache-buster
FROM demonized/oc_php_apache_fpm

ENV MYSQL_SERVER_ADDRESS=mysql-mariadb10-service
ENV MYSQL_SERVER_PORT=3306

ADD public_html /var/www/html
ADD storage /var/www/storage

COPY config.php /var/www/html/
COPY admin_config.php /var/www/html/admin/config.php
COPY .htaccess /var/www/html/

RUN mkdir -p /var/www/html/image/catalog
RUN mkdir -p /var/www/html/image/cache

# COPY php.ini /usr/local/etc/php/
COPY php_custom.ini /usr/local/etc/php/conf.d/
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

# COPY apache_debug.conf /usr/local/apache2/conf.d/docker.apache.conf

# RUN rm -f /etc/apache2/mods-enabled/mpm_prefork.conf
# RUN rm -f /etc/apache2/mods-enabled/mpm_prefork.load
# RUN cp /etc/apache2/mods-available/mpm_event.load /etc/apache2/mods-enabled/mpm_event.load
# RUN cp /etc/apache2/mods-available/mpm_event.conf /etc/apache2/mods-enabled/mpm_event.conf

RUN chmod -R 777 /var/www/html
RUN chmod -R 777 /var/www/storage
