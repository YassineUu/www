<?php
// Démarrer la session
session_start();

// tims7 l session
$_SESSION = array();

// ila kanou chi cookie  ti ms7hom 
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: /');
exit;
?> 