# FoodDelivery - Projet académique

Application de livraison de nourriture de type UberEats/Glovo, développée à but pédagogique.

## 🔧 Technologies utilisées

- **Frontend** : HTML5, CSS3, JavaScript (vanilla)
- **Backend** : PHP
- **Base de données** : MySQL
- **Serveur** : WAMP, XAMPP ou équivalent

## 💡 Fonctionnalités

### 🎨 Design & Interface utilisateur

Interface moderne avec la palette de couleurs "Nature & Fraîcheur" :
- `#34A853` (vert frais) pour la navbar
- `#A8D5BA` (vert doux) pour les champs de recherche
- `#F4D35E` (jaune pastel) pour les boutons
- `#FAFAFA` (blanc pur) pour les arrière-plans
- `#2D6A4F` (vert foncé) pour les éléments d'accent

### 🏠 Pages d'accueil (landing pages)

- **Client** : Visualisation des restaurants et produits disponibles
- **Restaurant** : Interface pour voir les commandes et gérer les menus
- **Livreur** : Voir les commandes à livrer et suivre leur statut
- **Admin** : Gérer les utilisateurs, restaurants, livreurs, produits

### 📊 Tableaux de bord / Profils

- **Client** : Historique des commandes, profil, panier
- **Restaurant** : Gestion des plats, suivi des ventes, profil
- **Livreur** : Commandes en cours, statut, profil
- **Admin** : Gestion complète (CRUD) de tous les éléments

### 🔐 Authentification

- Inscription des clients, restaurants, livreurs
- Connexion sécurisée avec redirection selon le rôle

## 🗃️ Structure de la base de données

Le projet utilise les tables suivantes :

- **Client** : `id_client`, `nom_c`, `prenom_c`, `mot_de_passe`, `adresse_c`
- **Commande** : `id_commande`, `id_client`, `id_livreur`, `date`, `statut`
- **Produit** : `id_produit`, `id_restaurant`, `id_categorie`, `prix`, `description_c`, `nom_p`
- **Restaurant** : `id_restaurant`, `nom_r`, `contact`, `adresse_r`
- **Categorie** : `id_categorie`, `nom_categorie`, `description_c`
- **Contient** : `id_commande`, `id_produit`, `qte_produit`
- **Paiement** : `id_paiement`, `id_commande`, `montant`, `mode`
- **Livreur** : `id_livreur`, `nom_l`, `prenom_l`, `statut_l`, `telephone`
- **Admin** : `id_admin`, `username`, `password`

## 📂 Structure du projet

```
food_delivery/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── img/
├── config/
│   └── database.php
├── db/
│   └── schema.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── pages/
│   ├── admin/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── client/
│   ├── livreur/
│   └── restaurant/
├── index.php
└── README.md
```

## 🚀 Installation

1. Cloner le dépôt :
   ```
   git clone https://github.com/votre-utilisateur/food-delivery.git
   ```

2. Placer le projet dans votre dossier web (www ou htdocs)

3. Créer la base de données :
   - Importer le fichier `db/schema.sql` dans phpMyAdmin
   - Ou exécuter les requêtes SQL dans votre client MySQL

4. Configurer la connexion à la base de données :
   - Ouvrir `config/database.php`
   - Modifier les constantes si nécessaire (DB_HOST, DB_NAME, DB_USER, DB_PASS)

5. Accéder au site via votre navigateur :
   ```
   http://localhost/food-delivery/
   ```

## 🔎 Détails d'implémentation

### 👤 Comptes par défaut

- **Admin** : admin / admin123

### 📝 Notes

- Ce projet est développé à but pédagogique
- La sécurité n'est pas optimisée pour un environnement de production
- L'application est conçue pour démontrer les connaissances en développement web

## 🔨 À faire / Améliorations possibles

- Implémenter la géolocalisation pour la livraison
- Ajouter un système de notation des restaurants et livreurs
- Intégrer un système de paiement en ligne
- Développer une API pour une application mobile
- Optimiser la sécurité (hash des mots de passe, protection CSRF, etc.)

## 📄 Licence

Ce projet est développé à but éducatif uniquement. 