-- Script complet de création de la base de données food_delivery

-- Supprimer la base de données si elle existe et la recréer
DROP DATABASE IF EXISTS food_delivery;
CREATE DATABASE food_delivery;
USE food_delivery;

-- Table Client
CREATE TABLE Client (
    id_client INT AUTO_INCREMENT PRIMARY KEY,
    nom_c VARCHAR(50) NOT NULL,
    prenom_c VARCHAR(50) NOT NULL,
    adresse_c TEXT NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table Restaurant
CREATE TABLE Restaurant (
    id_restaurant INT AUTO_INCREMENT PRIMARY KEY,
    nom_r VARCHAR(100) NOT NULL,
    adresse_r TEXT NOT NULL,
    description TEXT,
    image VARCHAR(255),
    email VARCHAR(100) NOT NULL UNIQUE,
    contact VARCHAR(255) NOT NULL COMMENT 'Utilisé comme mot de passe',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table Livreur
CREATE TABLE Livreur (
    id_livreur INT AUTO_INCREMENT PRIMARY KEY,
    nom_l VARCHAR(50) NOT NULL,
    prenom_l VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telephone VARCHAR(20) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    vehicule ENUM('scooter', 'velo', 'voiture') DEFAULT 'scooter',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table Admin
CREATE TABLE Admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table Categorie
CREATE TABLE Categorie (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE
);

-- Table Produit
CREATE TABLE Produit (
    id_produit INT AUTO_INCREMENT PRIMARY KEY,
    id_restaurant INT NOT NULL,
    id_categorie INT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    disponible BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_restaurant) REFERENCES Restaurant(id_restaurant) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES Categorie(id_categorie) ON DELETE SET NULL
);

-- Table Commande
CREATE TABLE Commande (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_client INT NOT NULL,
    id_livreur INT,
    statut ENUM('en attente', 'confirmé', 'en préparation', 'en livraison', 'livré', 'annulé') DEFAULT 'en attente',
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_client) REFERENCES Client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (id_livreur) REFERENCES Livreur(id_livreur) ON DELETE SET NULL
);

-- Table Contient (relation Commande-Produit)
CREATE TABLE Contient (
    id_commande INT NOT NULL,
    id_produit INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id_commande, id_produit),
    FOREIGN KEY (id_commande) REFERENCES Commande(id_commande) ON DELETE CASCADE,
    FOREIGN KEY (id_produit) REFERENCES Produit(id_produit) ON DELETE CASCADE
);

-- Table Paiement
CREATE TABLE Paiement (
    id_paiement INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    mode ENUM('carte', 'espèces', 'paypal') DEFAULT 'carte',
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_commande) REFERENCES Commande(id_commande) ON DELETE CASCADE
);

-- Création des index pour optimiser les requêtes
CREATE INDEX idx_commande_client ON Commande(id_client);
CREATE INDEX idx_commande_livreur ON Commande(id_livreur);
CREATE INDEX idx_commande_statut ON Commande(statut);
CREATE INDEX idx_produit_restaurant ON Produit(id_restaurant);
CREATE INDEX idx_produit_categorie ON Produit(id_categorie);

-- Insertion de données initiales

-- Administrateur par défaut
INSERT INTO Admin (email, password, nom, prenom) 
VALUES ('admin@fooddelivery.com', 'admin123', 'Admin', 'System');

-- Catégories
INSERT INTO Categorie (nom) VALUES 
('Fast-food'),
('Asiatique'),
('Italien'),
('Dessert'),
('Orientale');

-- Exemples de clients
INSERT INTO Client (nom_c, prenom_c, adresse_c, email, mot_de_passe) VALUES
('Dupont', 'Pierre', '1 rue de Paris, 75001 Paris', 'pierre.dupont@exemple.com', 'motdepasse123'),
('Martin', 'Sophie', '23 avenue des Champs-Élysées, 75008 Paris', 'sophie.martin@exemple.com', 'motdepasse123'),
('Dubois', 'Jean', '5 place de la République, 69001 Lyon', 'jean.dubois@exemple.com', 'motdepasse123');

-- Exemples de restaurants
INSERT INTO Restaurant (nom_r, adresse_r, description, email, contact) VALUES
('Burger Palace', '10 rue de la Paix, 75002 Paris', 'Les meilleurs burgers de la ville', 'contact@burgerpalace.com', 'motdepasse123'),
('Sushi Master', '15 rue du Commerce, 75015 Paris', 'Spécialités de sushi et maki', 'contact@sushimaster.com', 'motdepasse123'),
('Pasta Fresca', '8 boulevard Saint-Germain, 75006 Paris', 'Pâtes fraîches et pizzas artisanales', 'contact@pastafresca.com', 'motdepasse123');

-- Exemples de livreurs
INSERT INTO Livreur (nom_l, prenom_l, email, telephone, mot_de_passe, vehicule) VALUES
('Leroy', 'Marc', 'marc.leroy@exemple.com', '0612345678', 'motdepasse123', 'scooter'),
('Bernard', 'Julie', 'julie.bernard@exemple.com', '0687654321', 'motdepasse123', 'velo'),
('Petit', 'Thomas', 'thomas.petit@exemple.com', '0698765432', 'motdepasse123', 'voiture');

-- Exemples de produits pour le restaurant Burger Palace (id_restaurant = 1)
INSERT INTO Produit (id_restaurant, id_categorie, nom, description, prix, disponible) VALUES
(1, 1, 'Classic Burger', 'Burger classique avec steak, salade, tomate et oignon', 8.50, TRUE),
(1, 1, 'Cheese Burger', 'Burger avec steak, double fromage, salade et tomate', 9.50, TRUE),
(1, 1, 'Bacon Burger', 'Burger avec steak, bacon croustillant, fromage et sauce barbecue', 10.50, TRUE),
(1, 4, 'Milkshake Vanille', 'Milkshake onctueux à la vanille', 4.50, TRUE);

-- Exemples de produits pour le restaurant Sushi Master (id_restaurant = 2)
INSERT INTO Produit (id_restaurant, id_categorie, nom, description, prix, disponible) VALUES
(2, 2, 'California Roll', 'Rouleau de riz avec avocat, concombre et surimi', 12.00, TRUE),
(2, 2, 'Sashimi Saumon', 'Tranches fines de saumon frais', 15.00, TRUE),
(2, 2, 'Maki Concombre', 'Rouleau de riz et concombre enveloppé d\'algue nori', 8.00, TRUE);

-- Exemples de produits pour le restaurant Pasta Fresca (id_restaurant = 3)
INSERT INTO Produit (id_restaurant, id_categorie, nom, description, prix, disponible) VALUES
(3, 3, 'Pâtes Carbonara', 'Pâtes fraîches avec sauce carbonara', 13.50, TRUE),
(3, 3, 'Pizza Margherita', 'Pizza avec tomate, mozzarella et basilic', 11.00, TRUE),
(3, 3, 'Lasagnes', 'Lasagnes à la bolognaise', 14.50, TRUE);

-- Exemples de commandes
INSERT INTO Commande (id_client, id_livreur, statut, date) VALUES
(1, 1, 'livré', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 'en livraison', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, NULL, 'confirmé', NOW());

-- Association des produits aux commandes
INSERT INTO Contient (id_commande, id_produit, quantite) VALUES
(1, 1, 2), -- 2 Classic Burgers dans la commande 1
(1, 4, 1), -- 1 Milkshake dans la commande 1
(2, 5, 3), -- 3 California Rolls dans la commande 2
(2, 6, 1), -- 1 Sashimi Saumon dans la commande 2
(3, 8, 1), -- 1 Pâtes Carbonara dans la commande 3
(3, 9, 1); -- 1 Pizza Margherita dans la commande 3

-- Paiements pour les commandes
INSERT INTO Paiement (id_commande, montant, mode) VALUES
(1, 21.50, 'carte'),  -- Commande 1: 2 Classic Burgers (8.50 × 2) + 1 Milkshake (4.50)
(2, 51.00, 'paypal'), -- Commande 2: 3 California Rolls (12.00 × 3) + 1 Sashimi Saumon (15.00)
(3, 24.50, 'carte');  -- Commande 3: 1 Pâtes Carbonara (13.50) + 1 Pizza Margherita (11.00)

-- Message de confirmation
SELECT 'La base de données food_delivery a été créée et initialisée avec succès!' AS 'Message'; 