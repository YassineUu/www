<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'food_delivery');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données
function getDbConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        // Configurer PDO pour lever des exceptions en cas d'erreur
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Assurer que les ID auto-incrémentés sont retournés comme des entiers
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}
?> 