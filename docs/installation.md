# Guide d'installation de FoodDelivery

Ce guide vous explique comment installer et configurer l'application de livraison de nourriture FoodDelivery.

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx)
- Composer (optionnel)

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/votre-nom/food-delivery.git
cd food-delivery
```

### 2. Configurer la base de données

1. Créer une base de données MySQL nommée `food_delivery`
2. Importer le fichier SQL pour créer les tables et initialiser les données

```bash
mysql -u root -p food_delivery < food_delivery_db.sql
```

Si vous avez un mot de passe MySQL, utilisez la commande suivante :

```bash
mysql -u root -p food_delivery < food_delivery_db.sql
```

### 3. Configurer la connexion à la base de données

1. Ouvrez le fichier `config/database.php`
2. Modifiez les constantes suivantes avec vos informations de connexion :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'food_delivery');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mettre votre mot de passe MySQL ici
```

### 4. Configurer le serveur web

#### Avec Apache

1. Assurez-vous que mod_rewrite est activé
2. Configurez un VirtualHost pour pointer vers le dossier du projet

```apache
<VirtualHost *:80>
    ServerName fooddelivery.local
    DocumentRoot "/chemin/vers/food-delivery"
    
    <Directory "/chemin/vers/food-delivery">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Ajoutez l'entrée suivante à votre fichier hosts :

```
127.0.0.1 fooddelivery.local
```

#### Avec WAMP, XAMPP ou MAMP

Placez simplement le dossier du projet dans le répertoire www ou htdocs de votre installation.

### 5. Accéder à l'application

Ouvrez votre navigateur et accédez à l'URL :

```
http://localhost/food-delivery
```

Ou si vous avez configuré un virtualhost :

```
http://fooddelivery.local
```

## Comptes par défaut

L'application est préchargée avec quelques comptes de test :

### Client
- Email: pierre.dupont@exemple.com
- Mot de passe: motdepasse123

### Restaurant
- Email: contact@burgerpalace.com
- Mot de passe: motdepasse123

### Livreur
- Email: marc.leroy@exemple.com
- Mot de passe: 0612345678

### Administrateur
- Email: admin@fooddelivery.com
- Mot de passe: admin123

## Structure des répertoires

```
food-delivery/
│
├── assets/               # Fichiers statiques (CSS, JS, images)
├── config/               # Configuration (base de données, etc.)
├── includes/             # Fichiers inclus (header, footer, etc.)
├── pages/                # Pages de l'application
│   ├── admin/            # Interface administrateur
│   ├── auth/             # Authentification (login, register)
│   ├── client/           # Interface client
│   ├── livreur/          # Interface livreur
│   └── restaurant/       # Interface restaurant
└── index.php             # Page d'accueil
```

## Résolution des problèmes

### Erreur de connexion à la base de données

Si vous rencontrez une erreur "Column not found: 1054", exécutez le script de correction de la base de données :

```bash
mysql -u root -p food_delivery < food_delivery_db.sql
```

### Erreur d'autorisation de fichiers

Assurez-vous que les répertoires du projet ont les bonnes permissions :

```bash
chmod -R 755 /chemin/vers/food-delivery
```

## Support

Si vous rencontrez des problèmes lors de l'installation, veuillez ouvrir un ticket sur le dépôt GitHub ou contacter le support technique. 