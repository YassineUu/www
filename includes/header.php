<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Déterminer le rôle actuel de l'utilisateur pour l'affichage du menu approprié
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'visitor';

// Déterminer si nous sommes dans un tableau de bord
$isDashboard = strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || 
              strpos($_SERVER['REQUEST_URI'], '/admin') !== false || 
              strpos($_SERVER['REQUEST_URI'], '/profile') !== false;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodDelivery - Livraison de repas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($isDashboard): ?>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <?php endif; ?>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/">FoodDelivery</a>
            </div>
            <ul class="nav-links">
                <li><a href="/"><i class="fas fa-home"></i> Accueil</a></li>
                <li><a href="/pages/client/restaurants.php"><i class="fas fa-utensils"></i> Restaurants</a></li>
                
                <?php if ($userRole === 'client'): ?>
                    <li><a href="/pages/client/panier.php"><i class="fas fa-shopping-cart"></i> Panier</a></li>
                    <li><a href="/pages/client/dashboard.php"><i class="fas fa-user"></i> Compte</a></li>
                <?php elseif ($userRole === 'restaurant'): ?>
                    <li><a href="/pages/restaurant/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="/pages/restaurant/profile.php"><i class="fas fa-user"></i> Compte</a></li>
                <?php elseif ($userRole === 'livreur'): ?>
                    <li><a href="/pages/livreur/dashboard.php"><i class="fas fa-motorcycle"></i> Dashboard</a></li>
                    <li><a href="/pages/livreur/profile.php"><i class="fas fa-user"></i> Compte</a></li>
                <?php elseif ($userRole === 'admin'): ?>
                    <li><a href="/pages/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <?php else: ?>
                    <li><a href="/pages/auth/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                    <li><a href="/pages/auth/register.php"><i class="fas fa-user-plus"></i> Inscription</a></li>
                <?php endif; ?>
                
                <?php if ($userRole !== 'visitor'): ?>
                    <li><a href="/pages/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main> 