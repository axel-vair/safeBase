# Utilisation de l'image PHP 8.3 avec Apache
FROM php:8.3-apache

# Permettre à Composer d'être exécuté en tant que superutilisateur
ENV COMPOSER_ALLOW_SUPERUSER=1

# Installation des dépendances nécessaires
RUN apt-get update \
    && apt-get install -yqq --no-install-recommends \
       git \
       curl \
       libpq-dev \
       libicu-dev \
       zip \
       unzip \
       postgresql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configuration et installation des extensions PHP requises
RUN docker-php-ext-configure intl && docker-php-ext-install pdo pdo_pgsql intl

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/safebase

# Copier composer.json et composer.lock (si disponible) dans le conteneur
COPY composer.json /var/www/safebase/
COPY composer.lock /var/www/safebase/
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Vérifier la version de PHP et Composer
RUN php -v && composer --version

# Installer les dépendances PHP via Composer
RUN composer install --prefer-dist --no-autoloader --no-progress --no-interaction --no-scripts --no-cache

# Changer l'utilisateur www-data pour avoir les permissions correctes
RUN usermod -u 1000 www-data
RUN chown -R www-data:www-data /var/www/safebase/

# Passer à l'utilisateur www-data pour exécuter Apache
USER www-data

# Exposer le port 80 pour accéder à l'application
EXPOSE 80

# Commande par défaut pour démarrer Apache en mode avant-plan
CMD ["apache2-foreground"]

