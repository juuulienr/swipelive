FROM richarvey/nginx-php-fpm:3.1.6

COPY . .

# Installer Composer dans le conteneur
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Permettre à Composer de s'exécuter avec des privilèges root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Configuration de Symfony
ENV APP_ENV prod 
ENV APP_DEBUG false 
ENV LOG_CHANNEL stderr

# Installer les dépendances avec Composer
RUN composer install --no-dev --optimize-autoloader

# Configuration de l'image
ENV WEBROOT /var/www/html/public 
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Commande pour démarrer les services Nginx et PHP-FPM
CMD ["/start.sh"]





