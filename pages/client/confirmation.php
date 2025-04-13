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

// Vider le panier local s'il reste des données
echo "<script>
    // S'assurer que le panier est bien vidé
    if (localStorage.getItem('cart')) {
        localStorage.removeItem('cart');
        console.log('Panier vidé avec succès');
    }
</script>";
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

<style>
:root {
    --primary-color: #4CAF50;
    --secondary-color: #f8d24b;
    --accent-color: #FF5722;
    --text-color: #333333;
    --light-gray: #f9f9f9;
    --medium-gray: #e0e0e0;
    --dark-gray: #757575;
    --shadow: 0 2px 10px rgba(0,0,0,0.1);
    --border-radius: 8px;
}

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 15px;
}

.confirmation-box {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 40px;
}

.confirmation-header {
    background-color: var(--primary-color);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
}

.confirmation-header i {
    font-size: 60px;
    margin-bottom: 15px;
    display: block;
    color: rgba(255, 255, 255, 0.9);
}

.confirmation-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 10px;
}

.confirmation-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
}

.order-details, .order-tracking {
    padding: 30px;
    border-bottom: 1px solid var(--medium-gray);
}

.order-details h2, .order-tracking h2 {
    color: var(--text-color);
    font-size: 22px;
    margin-top: 0;
    margin-bottom: 20px;
    font-weight: 600;
    position: relative;
    padding-bottom: 10px;
}

.order-details h2:after, .order-tracking h2:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background-color: var(--primary-color);
}

.order-info {
    background-color: var(--light-gray);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 25px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.order-info p {
    margin: 0;
    color: var(--text-color);
    font-size: 15px;
}

.status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    background-color: var(--medium-gray);
}

.status-en {
    background-color: #FFD54F;
    color: #5D4037;
}

.status-confirmé, .status-en.attente {
    background-color: #FFCA28;
    color: #5D4037;
}

.status-en.préparation {
    background-color: #FFB300;
    color: #5D4037;
}

.status-en.livraison {
    background-color: #2196F3;
    color: white;
}

.status-livré {
    background-color: var(--primary-color);
    color: white;
}

.status-annulé {
    background-color: #F44336;
    color: white;
}

h3 {
    color: var(--text-color);
    font-size: 18px;
    margin-top: 30px;
    margin-bottom: 15px;
    font-weight: 500;
}

.order-items {
    border: 1px solid var(--medium-gray);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.order-item {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr;
    padding: 15px;
    align-items: center;
    border-bottom: 1px solid var(--medium-gray);
}

.order-item:last-child {
    border-bottom: none;
}

.order-item:hover {
    background-color: var(--light-gray);
}

.order-item-details h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: var(--text-color);
}

.order-item-details p {
    margin: 0 0 3px 0;
    font-size: 14px;
    color: var(--dark-gray);
}

.order-item-quantity {
    font-weight: 500;
    text-align: center;
}

.order-item-total {
    font-weight: 600;
    color: var(--accent-color);
    text-align: right;
}

.tracking-steps {
    margin-top: 20px;
    position: relative;
}

.tracking-steps:before {
    content: '';
    position: absolute;
    top: 30px;
    left: 30px;
    bottom: 30px;
    width: 3px;
    background-color: var(--medium-gray);
    z-index: 1;
}

.tracking-step {
    display: flex;
    margin-bottom: 25px;
    position: relative;
    z-index: 2;
}

.tracking-step:last-child {
    margin-bottom: 0;
}

.step-number {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: white;
    border: 3px solid var(--medium-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    color: var(--dark-gray);
    margin-right: 20px;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.tracking-step.active .step-number {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.step-info {
    padding-top: 5px;
}

.step-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: var(--text-color);
    font-weight: 600;
}

.step-info p {
    margin: 0;
    font-size: 14px;
    color: var(--dark-gray);
}

.tracking-step.active .step-info h4 {
    color: var(--primary-color);
}

.confirmation-actions {
    padding: 25px 30px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.btn {
    display: inline-block;
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-align: center;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: #3a9a3e;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-secondary {
    background-color: white;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-secondary:hover {
    background-color: #f5f5f5;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .order-info {
        grid-template-columns: 1fr;
    }
    
    .order-item {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .order-item-quantity, .order-item-total {
        text-align: left;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php
include_once '../../includes/footer.php';
?> 