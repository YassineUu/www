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
        $stmt = $conn->prepare("SELECT c.qte_produit, p.*, r.nom_r as restaurant_name 
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

// Vider le panier local
echo "<script>localStorage.removeItem('cart');</script>";
?>

<div class="container">
    <div class="confirmation-box">
        <div class="confirmation-header">
            <i class="fa fa-check-circle"></i>
            <h1>Commande confirmée !</h1>
            <p>Merci pour votre commande. Votre numéro de commande est <strong>#<?php echo $commandeId; ?></strong>.</p>
        </div>
        
        <div class="order-details">
            <h2>Détails de la commande</h2>
            
            <div class="order-info">
                <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($commande['date'])); ?></p>
                <p><strong>Statut:</strong> <span class="status status-<?php echo $commande['statut']; ?>"><?php echo ucfirst($commande['statut']); ?></span></p>
                <p><strong>Mode de paiement:</strong> <?php echo $commande['mode']; ?></p>
                <p><strong>Montant total:</strong> <?php echo number_format($commande['montant'], 2); ?> €</p>
            </div>
            
            <h3>Articles commandés</h3>
            
            <div class="order-items">
                <?php foreach ($products as $product): ?>
                <div class="order-item">
                    <div class="order-item-details">
                        <h4><?php echo htmlspecialchars($product['nom_p']); ?></h4>
                        <p>Restaurant: <?php echo htmlspecialchars($product['restaurant_name']); ?></p>
                        <p>Prix unitaire: <?php echo number_format($product['prix'], 2); ?> €</p>
                    </div>
                    <div class="order-item-quantity">
                        <span>Quantité: <?php echo $product['qte_produit']; ?></span>
                    </div>
                    <div class="order-item-total">
                        <span><?php echo number_format($product['prix'] * $product['qte_produit'], 2); ?> €</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="order-tracking">
            <h2>Suivi de commande</h2>
            
            <div class="tracking-steps">
                <div class="tracking-step <?php echo in_array($commande['statut'], ['en attente', 'confirmé', 'en préparation', 'en livraison', 'livré']) ? 'active' : ''; ?>">
                    <div class="step-number">1</div>
                    <div class="step-info">
                        <h4>Commande reçue</h4>
                        <p>Votre commande a été reçue et est en attente de confirmation par le restaurant.</p>
                    </div>
                </div>
                
                <div class="tracking-step <?php echo in_array($commande['statut'], ['confirmé', 'en préparation', 'en livraison', 'livré']) ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <div class="step-info">
                        <h4>Commande confirmée</h4>
                        <p>Le restaurant a confirmé votre commande et va commencer la préparation.</p>
                    </div>
                </div>
                
                <div class="tracking-step <?php echo in_array($commande['statut'], ['en préparation', 'en livraison', 'livré']) ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <div class="step-info">
                        <h4>En préparation</h4>
                        <p>Votre commande est en cours de préparation par le restaurant.</p>
                    </div>
                </div>
                
                <div class="tracking-step <?php echo in_array($commande['statut'], ['en livraison', 'livré']) ? 'active' : ''; ?>">
                    <div class="step-number">4</div>
                    <div class="step-info">
                        <h4>En livraison</h4>
                        <p>Votre commande est en route avec notre livreur.</p>
                    </div>
                </div>
                
                <div class="tracking-step <?php echo $commande['statut'] === 'livré' ? 'active' : ''; ?>">
                    <div class="step-number">5</div>
                    <div class="step-info">
                        <h4>Livrée</h4>
                        <p>Votre commande a été livrée avec succès.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="confirmation-actions">
            <a href="/pages/client/dashboard.php" class="btn btn-primary">Voir mes commandes</a>
            <a href="/pages/client/restaurants.php" class="btn btn-secondary">Commander à nouveau</a>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?> 