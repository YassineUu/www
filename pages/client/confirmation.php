<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que client
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client';

if (!$isLoggedIn) {
    header('Location: /pages/auth/login.php');
    exit;
}

// Vérifier si un ID de commande est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /pages/client/dashboard.php');
    exit;
}

$commandeId = (int)$_GET['id'];

// Récupérer les détails de la commande
function getCommandeDetails($commandeId, $clientId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT c.*, p.montant, p.mode 
                               FROM Commande c 
                               JOIN Paiement p ON c.id_commande = p.id_commande 
                               WHERE c.id_commande = :commandeId AND c.id_client = :clientId");
        $stmt->bindParam(':commandeId', $commandeId);
        $stmt->bindParam(':clientId', $clientId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Récupérer les produits de la commande
function getCommandeProducts($commandeId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT c.quantite, p.*, r.nom_r as restaurant_name 
                               FROM Contient c 
                               JOIN Produit p ON c.id_produit = p.id_produit 
                               JOIN Restaurant r ON p.id_restaurant = r.id_restaurant 
                               WHERE c.id_commande = :commandeId");
        $stmt->bindParam(':commandeId', $commandeId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer la commande
$commande = getCommandeDetails($commandeId, $_SESSION['user_id']);

if (!$commande) {
    header('Location: /pages/client/dashboard.php');
    exit;
}

// Récupérer les produits de la commande
$products = getCommandeProducts($commandeId);

// Vider le panier local s'il reste des données
echo "<script>
    // S'assurer que le panier est bien vidé
    if (localStorage.getItem('cart')) {
        localStorage.removeItem('cart');
        console.log('Panier vidé avec succès');
    }
    
    // Mettre à jour le badge du panier dans le header
    function updateCartBadge() {
        const cartCounter = document.querySelector('.cart-counter');
        if (cartCounter) {
            cartCounter.textContent = '0';
            cartCounter.classList.remove('visible');
        }
    }
    updateCartBadge();
</script>";
?>

<div class="container">
    <div class="confirmation-box">
        <div class="confirmation-header">
            <i class="fas fa-check-circle"></i>
            <h1>Commande confirmée</h1>
            <p>Merci pour votre commande! Votre commande a été enregistrée avec succès.</p>
        </div>

        <div class="order-details">
            <h2><i class="fas fa-info-circle"></i> Détails de la commande</h2>
            <div class="order-info">
                <p><i class="fas fa-hashtag"></i> <strong>Numéro de commande:</strong> <?php echo $commande['id_commande']; ?></p>
                <p><i class="fas fa-calendar-alt"></i> <strong>Date:</strong> <?php echo !empty($commande['date']) ? date('d/m/Y H:i', strtotime($commande['date'])) : 'Date non disponible'; ?></p>
                <p><i class="fas fa-clock"></i> <strong>Statut:</strong> <span class="status status-<?php echo strtolower(str_replace(' ', '.', $commande['statut'])); ?>"><?php echo $commande['statut']; ?></span></p>
                <p><i class="fas fa-credit-card"></i> <strong>Méthode de paiement:</strong> <?php echo $commande['mode'] ?? 'Non spécifiée'; ?></p>
                <p><i class="fas fa-money-bill-wave"></i> <strong>Montant total:</strong> <?php echo !empty($commande['montant']) ? number_format($commande['montant'], 2) : '0.00'; ?> €</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Adresse de livraison:</strong> <?php echo $commande['adresse'] ?? 'Non spécifiée'; ?></p>
            </div>

            <h3><i class="fas fa-utensils"></i> Articles commandés</h3>
            <div class="order-items">
                <?php foreach($products as $product): ?>
                <div class="order-item">
                    <div class="order-item-details">
                        <h4><?php echo htmlspecialchars($product['nom'] ?? $product['nom_p'] ?? 'Produit'); ?></h4>
                        <p><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['restaurant_name'] ?? 'Restaurant'); ?></p>
                        <p><i class="fas fa-tag"></i> <?php echo !empty($product['prix']) ? number_format($product['prix'], 2) : '0.00'; ?> € / unité</p>
                    </div>
                    <div class="order-item-quantity"><?php echo $product['quantite'] ?? 0; ?></div>
                    <div class="order-item-total"><?php echo !empty($product['prix']) && !empty($product['quantite']) ? number_format($product['prix'] * $product['quantite'], 2) : '0.00'; ?> €</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="confirmation-actions">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Retour à l'accueil</a>
            <a href="dashboard.php#orders" class="btn btn-primary"><i class="fas fa-list-ul"></i> Voir mes commandes</a>
        </div>
    </div>
</div>

<script>
// Vider le panier après confirmation de la commande
localStorage.removeItem('cart');
localStorage.removeItem('restaurantInfo');

// Mettre à jour le badge du panier
function updateCartBadge() {
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = '0';
        cartBadge.style.display = 'none';
    }
}

// Exécuter après le chargement complet de la page
document.addEventListener('DOMContentLoaded', function() {
    updateCartBadge();
});
</script>

<?php
include_once '../../includes/footer.php';
?> 