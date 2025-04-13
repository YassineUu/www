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
            <!-- Formulaire de paiement -->
            <div class="payment-form-container">
                <form method="POST" action="" id="payment-form">
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
                </div>
                
                <div class="order-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="item-quantity"><?php echo $item['quantity']; ?>x</div>
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-price"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</div>
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
                    <div class="total-line total-final">
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

<style>
:root {
    --primary-color: #4CAF50;
    --secondary-color: #f8d24b;
    --accent-color: #FF5722;
    --text-color: #333333;
    --text-light: #666666;
    --light-gray: #f5f5f5;
    --medium-gray: #e0e0e0;
    --error-color: #e74c3c;
    --shadow: 0 2px 8px rgba(0,0,0,0.1);
    --border-radius: 8px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.payment-container {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 30px;
}

.payment-header {
    padding: 25px;
    border-bottom: 1px solid var(--medium-gray);
    background-color: var(--light-gray);
}

.payment-header h1 {
    margin: 0;
    color: var(--text-color);
    font-size: 1.8rem;
}

.payment-subtitle {
    margin: 5px 0 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

.payment-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    padding: 30px;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h2 {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: var(--text-color);
    border-bottom: 1px solid var(--medium-gray);
    padding-bottom: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: var(--text-color);
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--medium-gray);
    border-radius: var(--border-radius);
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.form-text {
    font-size: 0.85rem;
    color: var(--text-light);
    margin-top: 5px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.payment-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.payment-option {
    position: relative;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-option label {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border: 1px solid var(--medium-gray);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.2s;
}

.payment-option input[type="radio"]:checked + label {
    border-color: var(--primary-color);
    background-color: rgba(76, 175, 80, 0.05);
}

.payment-option label i {
    margin-right: 10px;
    font-size: 1.2rem;
    color: var(--primary-color);
}

.card-details {
    background-color: var(--light-gray);
    padding: 15px;
    border-radius: var(--border-radius);
    margin-top: 15px;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    text-align: center;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: #3d8c40;
}

.btn-secondary {
    background-color: var(--light-gray);
    color: var(--text-color);
    border: 1px solid var(--medium-gray);
}

.btn-secondary:hover {
    background-color: var(--medium-gray);
}

.btn-large {
    width: 100%;
    padding: 15px 30px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.btn-large:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn i {
    margin-right: 8px;
}

.order-summary {
    background-color: var(--light-gray);
    border-radius: var(--border-radius);
    padding: 20px;
}

.order-summary h2 {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: var(--text-color);
    border-bottom: 1px solid var(--medium-gray);
    padding-bottom: 8px;
}

.restaurant-info {
    margin-bottom: 15px;
    color: var(--text-color);
}

.restaurant-info p {
    margin: 5px 0;
}

.restaurant-info i {
    margin-right: 8px;
    color: var(--primary-color);
}

.order-items {
    margin-bottom: 20px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--medium-gray);
}

.item-quantity {
    font-weight: bold;
    margin-right: 10px;
    color: var(--primary-color);
}

.item-name {
    flex: 1;
}

.item-price {
    font-weight: 500;
}

.order-totals {
    border-top: 1px solid var(--medium-gray);
    padding-top: 15px;
}

.total-line {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
    color: var(--text-light);
}

.total-final {
    font-weight: bold;
    font-size: 1.2rem;
    color: var(--accent-color);
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--medium-gray);
}

@media (max-width: 767px) {
    .payment-content {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        order: -1;
        margin-bottom: 20px;
    }
}

.error-message {
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px solid var(--error-color);
    padding: 15px;
    border-radius: var(--border-radius);
    color: var(--error-color);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.error-message i {
    font-size: 1.5rem;
    margin-right: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage des détails de carte selon le mode de paiement
    const paymentOptions = document.querySelectorAll('input[name="mode_paiement"]');
    const cardDetails = document.getElementById('card-details');
    
    function toggleCardDetails() {
        if (document.getElementById('cb').checked) {
            cardDetails.style.display = 'block';
        } else {
            cardDetails.style.display = 'none';
        }
    }
    
    // Appliquer initialement
    toggleCardDetails();
    
    // Ajouter des écouteurs d'événements pour les changements
    paymentOptions.forEach(option => {
        option.addEventListener('change', toggleCardDetails);
    });
    
    // Validation du formulaire
    const paymentForm = document.getElementById('payment-form');
    paymentForm.addEventListener('submit', function(e) {
        // Si carte bancaire est sélectionnée, valider les champs
        if (document.getElementById('cb').checked) {
            const cardNumber = document.getElementById('card_number').value.trim();
            const cardExpiry = document.getElementById('card_expiry').value.trim();
            const cardCvv = document.getElementById('card_cvv').value.trim();
            const cardName = document.getElementById('card_name').value.trim();
            
            // Simulation de validation (à adapter selon vos besoins)
            // Dans un environnement de production, la validation devrait être plus rigoureuse
            if (cardNumber === '' || cardExpiry === '' || cardCvv === '' || cardName === '') {
                e.preventDefault();
                alert('Veuillez remplir tous les champs de la carte bancaire.');
            }
        }
    });
    
    // Formattage automatique du numéro de carte (4 chiffres espacés)
    const cardNumberInput = document.getElementById('card_number');
    cardNumberInput.addEventListener('input', function(e) {
        // Supprimer tous les caractères non numériques
        let value = this.value.replace(/\D/g, '');
        
        // Ajouter des espaces tous les 4 chiffres
        if (value.length > 0) {
            value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
        }
        
        // Limiter à 19 caractères (16 chiffres + 3 espaces)
        if (value.length > 19) {
            value = value.substring(0, 19);
        }
        
        this.value = value;
    });
    
    // Formattage automatique de la date d'expiration (MM/AA)
    const cardExpiryInput = document.getElementById('card_expiry');
    cardExpiryInput.addEventListener('input', function(e) {
        // Supprimer tous les caractères non numériques
        let value = this.value.replace(/\D/g, '');
        
        // Ajouter un / après les 2 premiers chiffres
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        this.value = value;
    });
    
    // Limiter le CVV à 3-4 chiffres
    const cardCvvInput = document.getElementById('card_cvv');
    cardCvvInput.addEventListener('input', function(e) {
        // Supprimer tous les caractères non numériques
        let value = this.value.replace(/\D/g, '');
        
        // Limiter à 4 chiffres
        if (value.length > 4) {
            value = value.substring(0, 4);
        }
        
        this.value = value;
    });
});
</script>

<?php
include_once '../../includes/footer.php';
?> 