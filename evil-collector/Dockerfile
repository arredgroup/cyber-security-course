FROM php:8.1-apache

# "Servidor del atacante" para el laboratorio de XSS (Día 2).
# Recibe las cookies robadas vía un beacon de imagen (steal.php) y las guarda
# en SU PROPIA base de datos SQLite (/tmp/robos.sqlite), separada de la víctima.
#
# Nota: la imagen oficial php:8.1-apache ya incluye pdo_sqlite habilitado,
# por lo que NO hay que instalar ninguna extensión (igual que intranet-php).

COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
