# SwipeLive - Application de Live Shopping

[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-6.4-000000.svg?style=flat&logo=symfony)](https://symfony.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## 📱 À propos

SwipeLive était une application mobile innovante de live shopping, permettant aux vendeurs de présenter leurs produits en direct et aux acheteurs d'interagir et d'acheter en temps réel. Le projet combinait l'expérience immersive du streaming en direct avec la simplicité du commerce électronique.

**État du projet :** Le développement a été arrêté suite à l'arrivée de TikTok Shop en France.

### Composants du projet :
- **Backend :** API REST développée avec Symfony 6.4 (ce dépôt)
- **Frontend mobile :** Application développée avec Capacitor et Vue.js 3 (Me contacter si intéressé)

## 🚀 Fonctionnalités

- **Live Streaming**
  - Diffusion en direct via Agora
  - Chat en temps réel avec Pusher
  - Interactions vendeur-acheteur en direct

- **Gestion des Produits**
  - Catalogue de produits
  - Gestion des stocks en temps réel
  - Présentation des produits pendant le live

- **Paiements Sécurisés**
  - Intégration Stripe
  - Transactions sécurisées
  - Historique des commandes

- **Authentification et Sécurité**
  - JWT pour l'authentification API
  - Firebase pour l'authentification utilisateur
  - Gestion des rôles (acheteurs, vendeurs, administrateurs)

- **Gestion des Médias**
  - Cloudinary pour la gestion des médias
  - Traitement vidéo avec FFmpeg
  - Stockage AWS S3

## 🛠 Technologies Utilisées

- **Backend**
  - Symfony 6.4
  - PHP 8.2
  - Doctrine ORM
  - JWT Authentication
  - MySQL/MariaDB

- **Services Cloud**
  - AWS S3 pour le stockage
  - Cloudinary pour les médias
  - Firebase pour l'authentification
  - Pusher pour le temps réel
  - Stripe pour les paiements
  - Agora pour le streaming vidéo

- **Monitoring**
  - Bugsnag pour le suivi des erreurs
  - Logs personnalisés

## 📋 Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL/MariaDB
- FFmpeg
- Node.js et npm/yarn (pour les assets)

## 🚀 Installation

1. **Cloner le projet**
   ```bash
   git clone https://github.com/votre-username/swipelive.git
   cd swipelive
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   npm install # ou yarn install
   ```

3. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   # Configurez les variables d'environnement dans .env
   ```

4. **Configuration de la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Générer les clés JWT**
   ```bash
   mkdir -p config/jwt
   openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
   openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
   ```

6. **Compiler les assets**
   ```bash
   npm run build # ou yarn build
   ```

7. **Démarrer le serveur**
   ```bash
   symfony server:start
   ```

## ⚙️ Configuration des Services

### Services Requis

1. **Firebase**
   - Créer un projet Firebase
   - Télécharger le fichier de configuration
   - Placer le fichier dans `config/firebase/`

2. **Stripe**
   - Créer un compte Stripe
   - Configurer les clés API dans `.env`

3. **AWS S3**
   - Créer un bucket S3
   - Configurer les credentials AWS

4. **Cloudinary**
   - Créer un compte Cloudinary
   - Configurer l'URL Cloudinary dans `.env`

5. **Pusher**
   - Créer un compte Pusher
   - Configurer les clés dans `.env`

6. **Agora**
   - Créer un compte Agora
   - Configurer l'App ID et le certificat

## 🏗 Architecture

```mermaid
graph TD
    A[Client Mobile] -->|API Requests| B[API Symfony]
    B -->|Push Notifications| C[Firebase]
    B -->|Media Storage| D[AWS S3]
    B -->|Image Processing| E[Cloudinary]
    B -->|Real-time Events| F[Pusher]
    B -->|Live Streaming| G[Agora]
    B -->|Payments| H[Stripe]
    B -->|Database| I[(MySQL)]
```

### Structure du Backend
```
src/
├── Controller/     # Contrôleurs API REST
├── Entity/         # Entités Doctrine
├── Service/        # Services métier
├── Repository/     # Repositories Doctrine
└── EventListener/  # Listeners d'événements
```


## 📝 License

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

