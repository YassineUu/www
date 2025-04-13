<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que client
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client';

// Récupérer les détails d'un produit depuis la base de données
function getProductDetails($productId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT p.*, r.nom_r as restaurant_name 
                               FROM Produit p 
                               JOIN Restaurant r ON p.id_restaurant = r.id_restaurant 
                               WHERE p.id_produit = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Traiter la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && $isLoggedIn) {
    $cartItems = json_decode($_POST['cart_items'], true);
    
    if (!empty($cartItems)) {
        // Stocker les informations du panier en session pour la page de paiement
        $_SESSION['cart_items'] = $cartItems;
        $_SESSION['restaurant_id'] = $cartItems[0]['restaurant_id'];
        
        // Calculer le montant total
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }
        $_SESSION['cart_total'] = $totalAmount;
        
        // Rediriger vers la page de paiement
        header('Location: paiement.php');
        exit;
    }
}
?>

<div class="container">
    <div class="cart-container">
        <div class="cart-header">
            <h1>Votre panier</h1>
            <p class="cart-subtitle">Consultez et modifiez les articles de votre panier avant de passer commande</p>
        </div>
        
        <div class="loading-message">
            <div class="loading-spinner"></div>
            <p>Chargement du panier...</p>
        </div>
        
        <div class="empty-cart" style="display: none;">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2>Votre panier est vide</h2>
            <p>Ajoutez des produits à votre panier pour passer commande</p>
            <a href="restaurants.php" class="btn btn-primary">Découvrir les restaurants</a>
        </div>
        
        <div id="cart-items" class="cart-items" style="display: none;">
            <!-- Les articles du panier seront affichés ici par JavaScript -->
        </div>
        
        <div class="cart-summary" style="display: none;">
            <div class="cart-total-wrapper">
                <div class="cart-total-label">Total de la commande</div>
                <div class="cart-total-amount">0.00 €</div>
            </div>
            
            <div class="cart-actions">
                <?php if ($isLoggedIn): ?>
                <form method="POST" action="">
                    <input type="hidden" name="cart_items" id="cart-items-input">
                    <button type="submit" name="place_order" class="btn-order">
                        <i class="fas fa-credit-card"></i> Procéder au paiement
                    </button>
                </form>
                <?php else: ?>
                <div class="login-required">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Veuillez vous <a href="/pages/auth/login.php">connecter</a> pour finaliser votre commande.</p>
                </div>
                <?php endif; ?>
                
                <button id="clear-cart" class="btn-secondary">
                    <i class="fas fa-trash"></i> Vider le panier
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variable globale pour la connexion utilisateur (utilisée par le JavaScript)
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>
<script src="../../assets/js/cart.js"></script>

<?php
include_once '../../includes/footer.php';
?> 