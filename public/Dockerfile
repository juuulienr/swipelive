# Utiliser une image de base qui inclut Nginx et PHP-FPM
FROM richarvey/nginx-php-fpm:3.1.6

# Copier les fichiers de l'application dans le conteneur
COPY . .

# Configuration de l'image
ENV WEBROOT /var/www/html/public   # Symfony utilise également ce répertoire pour le contenu web accessible
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Configuration de Symfony
ENV APP_ENV prod  # Production environment pour Symfony
ENV APP_DEBUG false  # Désactiver le mode debug en production
ENV LOG_CHANNEL stderr  # Configurer Symfony pour enregistrer les erreurs via stderr

# Permettre à Composer de s'exécuter avec des privilèges root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Commande pour démarrer les services Nginx et PHP-FPM
CMD ["/start.sh"]
