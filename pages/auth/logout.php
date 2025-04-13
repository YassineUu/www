<?php
// Démarrer la session
session_start();

// Effacer toutes les variables de session
$_SESSION = array();

// Si un cookie de session existe, supprimer le cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: /');
exit;
?> 