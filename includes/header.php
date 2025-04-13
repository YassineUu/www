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

// Déterminer le type d'interface en fonction de l'URL
$isLivreur = strpos($_SERVER['REQUEST_URI'], '/livreur/') !== false;
$isRestaurant = strpos($_SERVER['REQUEST_URI'], '/restaurant/') !== false;
$isClient = strpos($_SERVER['REQUEST_URI'], '/client/') !== false;
$isAdmin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livri - Livraison de repas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- Styles CSS communs -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/common.css">
    
    <!-- Ajout de SweetAlert2 pour les alertes élégantes -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <!-- Styles CSS spécifiques -->
    <?php if ($isDashboard): ?>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <?php endif; ?>
    
    <?php if ($isLivreur): ?>
    <link rel="stylesheet" href="/assets/css/livreur.css">
    <?php endif; ?>
    
    <?php if ($isRestaurant): ?>
    <link rel="stylesheet" href="/assets/css/restaurant.css">
    <?php endif; ?>
    
    <?php if ($isClient): ?>
    <link rel="stylesheet" href="/assets/css/client.css">
    <?php endif; ?>
    
    <?php if ($isAdmin): ?>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js" defer></script>
    <?php if ($isDashboard): ?>
    <script src="/assets/js/dashboard.js" defer></script>
    <?php endif; ?>
    
    <?php 
    // Vérifier si nous sommes sur la page des détails d'un restaurant
    $isRestaurantDetails = strpos($_SERVER['REQUEST_URI'], '/client/restaurant_details.php') !== false;
    if ($isRestaurantDetails): 
    ?>
    <script src="/assets/js/restaurant.js" defer></script>
    <?php endif; ?>
    
    <?php 
    // Vérifier si nous sommes sur la page du panier
    $isCart = strpos($_SERVER['REQUEST_URI'], '/client/panier.php') !== false;
    if ($isCart): 
    ?>
    <script src="/assets/js/cart.js" defer></script>
    <?php endif; ?>
    
    <?php 
    // Vérifier si nous sommes sur la page de paiement
    $isPayment = strpos($_SERVER['REQUEST_URI'], '/client/paiement.php') !== false;
    if ($isPayment): 
    ?>
    <script src="/assets/js/payment.js" defer></script>
    <?php endif; ?>
    
    <?php 
    // Vérifier si nous sommes sur la page de liste des restaurants
    $isRestaurantsList = strpos($_SERVER['REQUEST_URI'], '/client/restaurants.php') !== false;
    if ($isRestaurantsList): 
    ?>
    <script src="/assets/js/restaurants.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/">Livri</a>
            </div>
            <ul class="nav-links">
                <li><a href="/"><i class="fas fa-home"></i> Accueil</a></li>
                <li><a href="/pages/client/restaurants.php"><i class="fas fa-utensils"></i> Restaurants</a></li>
                
                <?php if ($userRole === 'client'): ?>
                    <li><a href="/pages/client/panier.php"><i class="fas fa-shopping-cart"></i> Panier</a></li>
                    <li><a href="/pages/client/dashboard.php"><i class="fas fa-user"></i> Compte</a></li>
                <?php elseif ($userRole === 'restaurant'): ?>
                    <li><a href="/pages/restaurant/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <?php elseif ($userRole === 'livreur'): ?>
                    <li><a href="/pages/livreur/dashboard.php"><i class="fas fa-motorcycle"></i> Dashboard</a></li>
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