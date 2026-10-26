FROM php:8.1-apache

# 1. Instalar ping (iputils-ping) para que funcione el laboratorio de Command Injection (127.0.0.1 && whoami)
RUN apt-get update && apt-get install -y iputils-ping && rm -rf /var/lib/apt/lists/*

# 2. Configuraciones intencionalmente inseguras de Apache y PHP (OWASP A05 / A07)
RUN echo "ServerTokens Full" >> /etc/apache2/apache2.conf && \
    echo "ServerSignature On" >> /etc/apache2/apache2.conf && \
    echo "<Directory /var/www/html/documentos>\n Options +Indexes\n AllowOverride None\n Require all granted\n</Directory>" >> /etc/apache2/apache2.conf && \
    echo "expose_php = On" >> /usr/local/etc/php/conf.d/inseguro.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/inseguro.ini && \
    echo "session.cookie_httponly = 0" >> /usr/local/etc/php/conf.d/inseguro.ini

COPY src/ /var/www/html/

# 3. Evidencias para el Laboratorio 6 de Hardening de Servidores Linux (chmod 777 y SSH Root Login)
RUN mkdir -p /var/www/html/uploads /var/www/html/documentos /etc/ssh && \
    chown -R www-data:www-data /var/www/html && \
    echo "<?php\n// Archivo crítico con permisos 777 para auditoría Linux — FLAG{A05_LINUX_CHMOD_777_DETECTADO}\n\$DB_USER = 'root';\n\$DB_PASS = 'SuperSecretRootPass2026!';\n?>" > /var/www/html/config.php && \
    chmod 777 /var/www/html/config.php && \
    echo "# Configuración SSH Vulnerable para Auditoría de Hardening Linux\nPort 22\nPermitRootLogin yes\nPasswordAuthentication yes\n" > /etc/ssh/sshd_config

EXPOSE 80