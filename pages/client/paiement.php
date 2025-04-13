<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que client
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client';

if (!$isLoggedIn) {
    header('Location: /pages/auth/login.php');
    exit;
}

// Vérifier si le panier est présent en session
if (!isset($_SESSION['cart_items']) || empty($_SESSION['cart_items']) || !isset($_SESSION['restaurant_id']) || !isset($_SESSION['cart_total'])) {
    header('Location: panier.php');
    exit;
}

// Récupération sécurisée des variables du panier
$cartItems = $_SESSION['cart_items'] ?? [];
$restaurantId = $_SESSION['restaurant_id'] ?? 0;
$totalAmount = $_SESSION['cart_total'] ?? 0;

// Récupérer les informations du client
function getClientInfo($clientId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Client WHERE id_client = :id");
        $stmt->bindParam(':id', $clientId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Récupérer les informations du restaurant
function getRestaurantInfo($restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Restaurant WHERE id_restaurant = :id");
        $stmt->bindParam(':id', $restaurantId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Récupérer les détails d'un produit
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

// Récupérer les informations du client et du restaurant
$client = getClientInfo($_SESSION['user_id']);
$restaurant = getRestaurantInfo($restaurantId);

// Vérifier si les informations du client sont disponibles
if (!$client) {
    // Rediriger vers la page de profil pour compléter les informations
    header('Location: /pages/client/profile.php?error=info_required');
    exit;
}

// Frais de livraison fixes (à adapter selon vos besoins)
$fraisLivraison = 2.99;
$totalAvecLivraison = $totalAmount + $fraisLivraison;

// Traiter le paiement et finaliser la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $modePaiement = $_POST['mode_paiement'];
    $error = '';
    
    try {
        $conn = getDbConnection();
        $conn->beginTransaction();
        
        // Vérifier la connexion
        if (!$conn) {
            throw new Exception('Erreur: impossible de se connecter à la base de données');
        }
        
        // Créer une nouvelle commande sans utiliser adresse_livraison
        $stmt = $conn->prepare("INSERT INTO Commande (id_client, date, statut) VALUES (:clientId, NOW(), 'en attente')");
        $stmt->bindParam(':clientId', $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Erreur SQL lors de l\'insertion de la commande: Code=' . $errorInfo[0] . ', État=' . $errorInfo[1] . ', Message=' . $errorInfo[2]);
        }
        
        $commandeId = $conn->lastInsertId();
        
        if (!$commandeId) {
            throw new Exception('Erreur lors de la création de la commande: impossible de récupérer l\'ID de la commande');
        }
        
        // Ajouter les produits à la commande
        foreach ($cartItems as $item) {
            $productId = $item['id'];
            $quantity = $item['quantity'];
            
            // Récupérer les détails du produit
            $product = getProductDetails($productId);
            
            if ($product) {
                // Ajouter le produit à la relation Contient
                $stmt = $conn->prepare("INSERT INTO Contient (id_commande, id_produit, quantite) VALUES (:commandeId, :productId, :quantity)");
                $stmt->bindParam(':commandeId', $commandeId);
                $stmt->bindParam(':productId', $productId);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->execute();
            }
        }
        
        // On ne tente pas d'insérer dans une table qui pourrait ne pas exister
        // La relation entre commandes et restaurants peut être déduite des produits commandés
        
        // Créer un paiement pour la commande
        $stmt = $conn->prepare("INSERT INTO Paiement (id_commande, montant, mode) VALUES (:commandeId, :montant, :mode)");
        $stmt->bindParam(':commandeId', $commandeId);
        $stmt->bindParam(':montant', $totalAvecLivraison);
        $stmt->bindParam(':mode', $modePaiement);
        $stmt->execute();
        
        $conn->commit();
        
        // Nettoyer les variables de session liées au panier
        unset($_SESSION['cart_items']);
        unset($_SESSION['restaurant_id']);
        unset($_SESSION['cart_total']);
        
        // Vider le panier local
        echo "<script>
            // Supprimer les données du panier du localStorage
            localStorage.removeItem('cart');
            
            // Fonction pour rediriger vers la page de confirmation après le nettoyage
            function redirectToConfirmation() {
                window.location.href = 'confirmation.php?id=" . $commandeId . "';
            }
            
            // Rediriger après un court délai pour s'assurer que le localStorage est bien nettoyé
            setTimeout(redirectToConfirmation, 100);
        </script>";
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Erreur lors de la finalisation de la commande: ' . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="payment-container">
        <div class="payment-header">
            <h1>Finaliser votre commande</h1>
            <p class="payment-subtitle">Vous êtes à un pas de déguster votre repas !</p>
        </div>
        
        <div class="payment-content">
            <div class="payment-form">
                <form action="" method="POST" id="payment-form">
                    <!-- Mode de paiement -->
                    <div class="form-section">
                        <h2>Mode de paiement</h2>
                        <div class="payment-options">
                            <div class="payment-option">
                                <input type="radio" id="cb" name="mode_paiement" value="carte" checked>
                                <label for="cb">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Carte bancaire</span>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="paypal" name="mode_paiement" value="paypal">
                                <label for="paypal">
                                    <i class="fab fa-paypal"></i>
                                    <span>PayPal</span>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="especes" name="mode_paiement" value="espèces">
                                <label for="especes">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Espèces à la livraison</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Détails carte bancaire (affichés uniquement si carte bancaire sélectionnée) -->
                        <div id="card-details" class="card-details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="card_number">Numéro de carte</label>
                                    <input type="text" id="card_number" class="form-control" placeholder="1234 5678 9012 3456" pattern="[0-9 ]{16,19}">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="card_expiry">Date d'expiration</label>
                                    <input type="text" id="card_expiry" class="form-control" placeholder="MM/AA" pattern="[0-9]{2}/[0-9]{2}">
                                </div>
                                
                                <div class="form-group">
                                    <label for="card_cvv">CVV</label>
                                    <input type="text" id="card_cvv" class="form-control" placeholder="123" pattern="[0-9]{3,4}">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="card_name">Titulaire de la carte</label>
                                <input type="text" id="card_name" class="form-control" placeholder="NOM Prénom">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bouton de confirmation -->
                    <div class="form-actions">
                        <button type="submit" name="confirm_payment" class="btn btn-primary btn-large">
                            <i class="fas fa-lock"></i> Confirmer la commande
                        </button>
                        <a href="panier.php" class="btn btn-secondary">Retour au panier</a>
                    </div>
                </form>
            </div>
            
            <!-- Récapitulatif de la commande -->
            <div class="order-summary">
                <h2>Récapitulatif de la commande</h2>
                
                <div class="restaurant-info">
                    <p><i class="fas fa-store"></i> <?php echo htmlspecialchars($restaurant['nom_r']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($restaurant['adresse_r']); ?></p>
                </div>
                
                <div class="order-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                        <span class="item-price"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="total-line">
                        <span>Sous-total</span>
                        <span><?php echo number_format($totalAmount, 2); ?> €</span>
                    </div>
                    <div class="total-line">
                        <span>Frais de livraison</span>
                        <span><?php echo number_format($fraisLivraison, 2); ?> €</span>
                    </div>
                    <div class="total-final">
                        <span>Total</span>
                        <span><?php echo number_format($totalAvecLivraison, 2); ?> €</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Afficher les messages d'erreur si nécessaire -->
<?php if (isset($error) && !empty($error)): ?>
<div class="error-message">
    <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
</div>
<?php endif; ?>

<?php
include_once '../../includes/footer.php';
?> 