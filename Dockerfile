FROM php:8.2-apache

# Installation des extensions pour connecter PHP à MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activation de l'URL rewriting (utile pour les sites modernes)
RUN a2enmod rewrite
