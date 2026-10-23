FROM php:8.1-apache

# Configuración intencionalmente insegura para Auditoría de Infraestructura (Track 1 y Track 6 - A05)
RUN echo "ServerTokens Full" >> /etc/apache2/apache2.conf && \
    echo "ServerSignature On" >> /etc/apache2/apache2.conf && \
    echo "<Directory /var/www/html/documentos>\n Options +Indexes\n AllowOverride None\n Require all granted\n</Directory>" >> /etc/apache2/apache2.conf && \
    echo "expose_php = On" >> /usr/local/etc/php/conf.d/inseguro.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/inseguro.ini && \
    echo "session.cookie_httponly = 0" >> /usr/local/etc/php/conf.d/inseguro.ini

COPY src/ /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/documentos && \
    chown -R www-data:www-data /var/www/html

EXPOSE 80
