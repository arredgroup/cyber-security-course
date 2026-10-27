FROM php:8.1-apache

# "Servidor del atacante" para el laboratorio de XSS (Track Avanzado Día 2).
# Recibe las cookies robadas vía un beacon de imagen y las guarda en SU PROPIA
# base de datos SQLite (/tmp/robos.sqlite), separada de la intranet víctima.
RUN docker-php-ext-install pdo pdo_sqlite

# Permitir peticiones cross-origin del beacon (la víctima está en otro puerto).
RUN echo "Header set Access-Control-Allow-Origin \"*\"" >> /etc/apache2/apache2.conf

COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
