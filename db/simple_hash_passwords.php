<?php
/**
 * Script simple pour hasher tous les mots de passe
 * Ce script définit directement les mots de passe hashés pour toutes les tables
 */

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Afficher l'en-tête
echo "===========================================\n";
echo "  HASHAGE DIRECT DES MOTS DE PASSE\n";
echo "===========================================\n\n";

try {
    // Connexion à la base de données
    $conn = getDbConnection();
    echo "✓ Connexion à la base de données réussie\n\n";
    
    // Mot de passe par défaut
    $plainPassword = 'motdepasse123';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    echo "Mot de passe en clair: $plainPassword\n";
    echo "Mot de passe hashé: $hashedPassword\n\n";
    
    // 1. Clients
    echo "Mise à jour des mots de passe pour les clients...\n";
    $stmt = $conn->prepare("UPDATE Client SET mot_de_passe = :password");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ $count clients mis à jour\n\n";
    
    // 2. Restaurant - Ajouter la colonne password si elle n'existe pas
    echo "Vérification de la colonne password pour les restaurants...\n";
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM Restaurant LIKE 'password'");
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $stmt = $conn->prepare("ALTER TABLE Restaurant ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
            $stmt->execute();
            echo "✓ Colonne password ajoutée à la table Restaurant\n";
        }
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de la vérification/création de la colonne password: " . $e->getMessage() . "\n";
    }
    
    // Mettre à jour les mots de passe des restaurants
    echo "Mise à jour des mots de passe pour les restaurants...\n";
    $stmt = $conn->prepare("UPDATE Restaurant SET password = :password");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ $count restaurants mis à jour\n\n";
    
    // 3. Livreurs
    echo "Mise à jour des mots de passe pour les livreurs...\n";
    $stmt = $conn->prepare("UPDATE Livreur SET mot_de_passe = :password");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ $count livreurs mis à jour\n\n";
    
    // 4. Admins
    echo "Mise à jour des mots de passe pour les administrateurs...\n";
    $stmt = $conn->prepare("UPDATE Admin SET password = :password");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ $count administrateurs mis à jour\n\n";
    
    // 5. Mettre à jour le commentaire de la colonne contact si nécessaire
    echo "Mise à jour du commentaire de la colonne contact dans Restaurant...\n";
    try {
        $stmt = $conn->prepare("ALTER TABLE Restaurant CHANGE contact contact VARCHAR(20) NOT NULL COMMENT 'Numéro de téléphone'");
        $stmt->execute();
        echo "✓ Commentaire de la colonne contact mis à jour\n\n";
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de la mise à jour du commentaire: " . $e->getMessage() . "\n\n";
    }
    
    echo "===========================================\n";
    echo "  HASHAGE DES MOTS DE PASSE TERMINÉ\n";
    echo "===========================================\n\n";
    echo "Tous les utilisateurs ont maintenant le mot de passe: $plainPassword\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
} 