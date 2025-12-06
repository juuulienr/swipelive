# AGENTS.md

Ce fichier fournit des instructions et du contexte spécifiques pour aider les agents d'IA à travailler sur ce projet SwipeLive.

## Conseils pour l'environnement de développement

### Installation avec Docker (Recommandée)

1. **Générer les certificats SSL (pour HTTPS)**
   ```bash
   cd docker/nginx/ssl
   ./generate-ssl.sh
   cd ../../..
   ```
   
   **Note** : Les certificats SSL auto-signés sont nécessaires pour permettre les requêtes HTTPS depuis le client. Ils sont valides pour `127.0.0.1` et `localhost`.

2. **Lancer les conteneurs Docker**
   ```bash
   docker-compose up -d --build
   ```

3. **Installer les dépendances**
   ```bash
   docker-compose exec app composer install
   ```

4. **Créer la base de données et exécuter les migrations**
   ```bash
   docker-compose exec app php bin/console doctrine:database:create
   docker-compose exec app php bin/console doctrine:migrations:migrate
   ```

5. **URLs d'accès**
   - **API HTTPS** : https://127.0.0.1:8000 (recommandé pour les requêtes API depuis le client)
   - **Site web HTTP** : http://localhost:8080 (redirigé automatiquement vers HTTPS)
   
   **Important** : Les certificats SSL sont auto-signés. Le client devra accepter l'avertissement de sécurité lors de la première connexion.

4. **Commandes Docker utiles**
   ```bash
   # Voir les logs en temps réel
   docker-compose logs -f
   
   # Accéder au conteneur de l'application
   docker-compose exec app bash
   
   # Exécuter des commandes Symfony
   docker-compose exec app php bin/console [commande]
   
   # Arrêter les conteneurs
   docker-compose down
   
   # Redémarrer les conteneurs
   docker-compose up -d
   ```

### Installation Classique

1. **Installer les dépendances**
   ```bash
   composer install
   npm install
   ```

2. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   # Configurez les variables d'environnement dans .env
   ```

3. **Configuration de la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Générer les clés JWT**
   ```bash
   mkdir -p config/jwt
   openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
   openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
   ```

5. **Compiler les assets**
   ```bash
   npm run build
   ```

6. **Démarrer le serveur**
   ```bash
   symfony server:start
   ```

### Configuration des Services Externes

Le projet nécessite la configuration des services suivants dans le fichier `.env` :

- **Firebase** : Fichier de configuration à placer dans `config/firebase/`
- **Stripe** : `STRIPE_PUBLIC_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`
- **AWS S3** : `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_REGION`, `AWS_BUCKET`
- **Cloudinary** : `CLOUDINARY_URL`
- **Pusher** : `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`
- **Agora** : `AGORA_APP_ID`, `AGORA_APP_CERTIFICATE`
- **Bugsnag** : `BUGSNAG_API_KEY`

## Instructions de test

### Tests PHPUnit

- Exécuter tous les tests :
  ```bash
  php bin/phpunit
  # ou avec Docker
  docker-compose exec app php bin/phpunit
  ```

- Exécuter un test spécifique :
  ```bash
  php bin/phpunit tests/Path/To/Test.php
  ```

- Le commit doit passer tous les tests avant d'être fusionné.

### Analyse statique avec PHPStan

- Exécuter PHPStan (niveau 5) :
  ```bash
  vendor/bin/phpstan analyse
  # ou avec Docker
  docker-compose exec app vendor/bin/phpstan analyse
  ```

- Corriger toutes les erreurs PHPStan avant de commiter.

### Refactoring avec Rector

- Analyser le code avec Rector :
  ```bash
  vendor/bin/rector process src --dry-run
  ```

- Appliquer les transformations :
  ```bash
  vendor/bin/rector process src
  ```

### Formatage du code avec PHP-CS-Fixer

- Vérifier le formatage :
  ```bash
  vendor/bin/php-cs-fixer fix --dry-run --diff
  ```

- Corriger le formatage :
  ```bash
  vendor/bin/php-cs-fixer fix
  ```

### Checklist avant commit

- [ ] Tous les tests PHPUnit passent
- [ ] PHPStan ne rapporte aucune erreur
- [ ] Le code est formaté avec PHP-CS-Fixer
- [ ] Les migrations Doctrine sont à jour
- [ ] Aucune erreur de syntaxe

## Conventions de code

### Standards de codage

- **Symfony Coding Standards** : Le projet suit les standards Symfony via PHP-CS-Fixer avec la règle `@Symfony`
- **PHP 8.2** : Utiliser les fonctionnalités de PHP 8.2
- **Strict Types** : Tous les fichiers PHP doivent commencer par `declare(strict_types=1);`
- **PHPStan Niveau 5** : Maintenir le niveau d'analyse statique à 5

### Structure du projet

```
src/
├── Command/          # Commandes Symfony (cron jobs, tâches automatisées)
├── Controller/      # Contrôleurs API REST
│   ├── App/         # Contrôleurs API pour l'application mobile
│   └── Web/         # Contrôleurs web
├── Entity/          # Entités Doctrine ORM
├── Repository/      # Repositories Doctrine
├── Service/         # Services métier
├── Listener/        # Event Listeners
├── Normalizer/      # Normalizers pour la sérialisation
└── DQL/            # Fonctions DQL personnalisées
```

### Bonnes pratiques

- **Doctrine ORM** : Utiliser Doctrine pour la persistance des données
- **JWT Authentication** : Utiliser Lexik JWT Authentication Bundle pour l'authentification API
- **Sérialisation** : Utiliser les groupes de sérialisation Symfony (`@Groups`)
- **Services** : Injecter les dépendances via le conteneur Symfony
- **Repositories** : Créer des méthodes de requête personnalisées dans les repositories
- **Migrations** : Créer une migration pour chaque modification de schéma :
  ```bash
  php bin/console make:migration
  php bin/console doctrine:migrations:migrate
  ```

### Conventions de nommage

- **Classes** : PascalCase (ex: `LiveAPIController`)
- **Méthodes** : camelCase (ex: `getUserById()`)
- **Propriétés** : camelCase (ex: `$createdAt`)
- **Constantes** : UPPER_SNAKE_CASE
- **Fichiers** : Même nom que la classe (ex: `LiveAPIController.php`)

### Entités Doctrine

- Toujours utiliser les annotations Doctrine
- Définir les relations correctement (OneToMany, ManyToOne, etc.)
- Utiliser les groupes de sérialisation pour contrôler l'exposition des données
- Implémenter les callbacks de cycle de vie si nécessaire (`@ORM\HasLifecycleCallbacks`)

### Contrôleurs API

- Tous les contrôleurs API sont dans `src/Controller/App/`
- Utiliser les annotations de route (`@Route`)
- Retourner des réponses JSON avec les codes HTTP appropriés
- Utiliser la sérialisation Symfony avec les groupes appropriés

## Instructions pour les pull requests

### Format du titre

- Utiliser un titre descriptif et concis
- Mentionner le composant concerné si applicable (ex: `[API] Fix user authentication`)

### Checklist avant de soumettre une PR

- [ ] Tous les tests passent (`php bin/phpunit`)
- [ ] PHPStan ne rapporte aucune erreur (`vendor/bin/phpstan analyse`)
- [ ] Le code est formaté (`vendor/bin/php-cs-fixer fix`)
- [ ] Les migrations Doctrine sont créées et testées
- [ ] Le code respecte les conventions du projet
- [ ] Les services externes sont configurés (si nécessaire pour les tests)
- [ ] La documentation est mise à jour si nécessaire

### Tests et validation

- Exécuter tous les outils de qualité de code avant de commiter :
  ```bash
  # Formatage
  vendor/bin/php-cs-fixer fix
  
  # Analyse statique
  vendor/bin/phpstan analyse
  
  # Tests
  php bin/phpunit
  
  # Migrations (vérifier qu'elles sont à jour)
  php bin/console doctrine:migrations:status
  ```

### Migrations Doctrine

- Toujours créer une migration pour les modifications de schéma :
  ```bash
  php bin/console make:migration
  ```

- Vérifier que les migrations sont à jour :
  ```bash
  php bin/console doctrine:migrations:status
  ```

- Tester les migrations avant de commiter :
  ```bash
  php bin/console doctrine:migrations:migrate --dry-run
  ```

### Services externes

- Pour les tests nécessitant des services externes (Stripe, Firebase, etc.), utiliser des variables d'environnement de test
- Ne pas hardcoder les clés API dans le code
- Utiliser des mocks pour les tests unitaires quand c'est possible



