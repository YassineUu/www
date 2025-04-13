<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que livreur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'livreur') {
    header('Location: /pages/auth/login.php?type=livreur');
    exit;
}

$livreurId = $_SESSION['user_id'];

// Initialiser les variables pour les messages
$profileSuccess = '';
$profileError = '';
$passwordSuccess = '';
$passwordError = '';
$orderSuccess = '';
$orderError = '';

// Récupérer les informations du livreur
function getLivreurInfo($livreurId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Livreur WHERE id_livreur = :id");
        $stmt->bindParam(':id', $livreurId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Récupérer les commandes assignées au livreur (en cours)
function getCurrentDeliveries($livreurId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT c.*, cl.nom_c, cl.prenom_c, cl.adresse_c, 
                               (SELECT nom_r FROM Restaurant r WHERE r.id_restaurant = 
                                  (SELECT DISTINCT p.id_restaurant FROM Produit p 
                                   JOIN Contient co ON p.id_produit = co.id_produit 
                                   WHERE co.id_commande = c.id_commande LIMIT 1)) as nom_r,
                               (SELECT adresse_r FROM Restaurant r WHERE r.id_restaurant = 
                                  (SELECT DISTINCT p.id_restaurant FROM Produit p 
                                   JOIN Contient co ON p.id_produit = co.id_produit 
                                   WHERE co.id_commande = c.id_commande LIMIT 1)) as adresse_r,
                               p.montant
                               FROM Commande c 
                               JOIN Client cl ON c.id_client = cl.id_client
                               JOIN Paiement p ON c.id_commande = p.id_commande
                               WHERE c.id_livreur = :livreurId 
                               AND c.statut IN ('confirmé', 'en préparation', 'en livraison')
                               ORDER BY c.date DESC");
        
        $stmt->bindParam(':livreurId', $livreurId);
        $stmt->execute();
        
        // Log pour debug
        echo "<!-- Nombre de livraisons en cours: " . $stmt->rowCount() . " -->";
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<!-- Erreur livraisons en cours: " . $e->getMessage() . " -->";
        return [];
    }
}

// Récupérer l'historique des livraisons terminées
function getCompletedDeliveries($livreurId, $limit = 10) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT c.*, cl.nom_c, cl.prenom_c, p.montant
                               FROM Commande c 
                               JOIN Client cl ON c.id_client = cl.id_client
                               JOIN Paiement p ON c.id_commande = p.id_commande
                               WHERE c.id_livreur = :livreurId 
                               AND c.statut = 'livré'
                               ORDER BY c.date DESC
                               LIMIT :limit");
        $stmt->bindParam(':livreurId', $livreurId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Compter le total des livraisons
function countTotalDeliveries($livreurId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Commande WHERE id_livreur = :livreurId");
        $stmt->bindParam(':livreurId', $livreurId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Compter les livraisons du jour
function countTodayDeliveries($livreurId) {
    try {
        $conn = getDbConnection();
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Commande 
                               WHERE id_livreur = :livreurId 
                               AND DATE(date) = :today");
        $stmt->bindParam(':livreurId', $livreurId);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Calculer les revenus totaux
function calculateTotalEarnings($livreurId) {
    try {
        $conn = getDbConnection();
        // Hypothétique: 10% de commission sur chaque livraison
        $stmt = $conn->prepare("SELECT SUM(p.montant * 0.1) as total 
                               FROM Paiement p 
                               JOIN Commande c ON p.id_commande = c.id_commande 
                               WHERE c.id_livreur = :livreurId 
                               AND c.statut = 'livré'");
        $stmt->bindParam(':livreurId', $livreurId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Récupérer les commandes disponibles pour livraison
function getAvailableOrders() {
    try {
        $conn = getDbConnection();
        // Modifié pour prendre toutes les commandes en attente ou confirmées sans livreur
        $stmt = $conn->prepare("SELECT DISTINCT c.*, cl.nom_c, cl.prenom_c, cl.adresse_c, 
                               (SELECT nom_r FROM Restaurant r WHERE r.id_restaurant = 
                                  (SELECT DISTINCT p.id_restaurant FROM Produit p 
                                   JOIN Contient co ON p.id_produit = co.id_produit 
                                   WHERE co.id_commande = c.id_commande LIMIT 1)) as nom_r,
                               (SELECT adresse_r FROM Restaurant r WHERE r.id_restaurant = 
                                  (SELECT DISTINCT p.id_restaurant FROM Produit p 
                                   JOIN Contient co ON p.id_produit = co.id_produit 
                                   WHERE co.id_commande = c.id_commande LIMIT 1)) as adresse_r,
                               p.montant
                               FROM Commande c 
                               JOIN Client cl ON c.id_client = cl.id_client
                               JOIN Paiement p ON c.id_commande = p.id_commande
                               WHERE c.id_livreur IS NULL
                               AND (c.statut = 'confirmé' OR c.statut = 'en attente')
                               ORDER BY c.date DESC");
        $stmt->execute();
        
        // Log pour debug
        echo "<!-- Nombre de commandes disponibles: " . $stmt->rowCount() . " -->";
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<!-- Erreur: " . $e->getMessage() . " -->";
        return [];
    }
}

// Récupérer les données
$livreur = getLivreurInfo($livreurId);
$currentDeliveries = getCurrentDeliveries($livreurId);
$completedDeliveries = getCompletedDeliveries($livreurId);
$availableOrders = getAvailableOrders();
$totalDeliveries = countTotalDeliveries($livreurId);
$todayDeliveries = countTodayDeliveries($livreurId);
$totalEarnings = calculateTotalEarnings($livreurId);

// Debug des commandes
echo "<!-- Debug des commandes du livreur #$livreurId -->";
echo "<!-- Commandes en cours: " . count($currentDeliveries) . " -->";
echo "<!-- Commandes disponibles: " . count($availableOrders) . " -->";
echo "<!-- Commandes complétées: " . count($completedDeliveries) . " -->";

// Afficher les IDs des commandes en cours pour débug
echo "<!-- IDs des commandes en cours: ";
foreach ($currentDeliveries as $delivery) {
    echo $delivery['id_commande'] . " (statut: " . $delivery['statut'] . "), ";
}
echo " -->";

// Traiter l'acceptation d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_order'])) {
        $orderId = intval($_POST['order_id']);
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare("UPDATE Commande SET id_livreur = :livreurId, statut = 'en livraison' WHERE id_commande = :orderId");
            $stmt->bindParam(':livreurId', $livreurId);
            $stmt->bindParam(':orderId', $orderId);
            $stmt->execute();
            
            $conn->beginTransaction();
            
            // Vérifier le statut actuel de la commande
            $stmt = $conn->prepare("SELECT statut FROM Commande WHERE id_commande = :orderId");
            $stmt->bindParam(':orderId', $orderId);
            $stmt->execute();
            $commande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si la commande est en attente, la passer en préparation d'abord
            if ($commande['statut'] === 'en attente') {
                $stmt = $conn->prepare("UPDATE Commande SET id_livreur = :livreurId, statut = 'en préparation' WHERE id_commande = :orderId");
            } else {
                // Sinon la passer directement en livraison
                $stmt = $conn->prepare("UPDATE Commande SET id_livreur = :livreurId, statut = 'en livraison' WHERE id_commande = :orderId");
            }
            
            $stmt->bindParam(':livreurId', $livreurId);
            $stmt->bindParam(':orderId', $orderId);
            $stmt->execute();
            
            $conn->commit();
            
            if ($commande['statut'] === 'en attente') {
                $orderSuccess = "Vous avez accepté la commande #$orderId. Son statut est maintenant 'en préparation'.";
            } else {
                $orderSuccess = "Vous avez accepté la commande #$orderId. Son statut est maintenant 'en livraison'.";
            }
            
            // Rafraîchir les données
            $currentDeliveries = getCurrentDeliveries($livreurId);
            $availableOrders = getAvailableOrders();
        } catch (PDOException $e) {
            $orderError = "Erreur lors de l'acceptation de la commande : " . $e->getMessage();
        }
    }
    
    // Traiter la mise à jour du statut d'une commande
    if (isset($_POST['update_status'])) {
        $orderId = intval($_POST['order_id']);
        $status = $_POST['status'];
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare("UPDATE Commande SET statut = :status WHERE id_commande = :orderId AND id_livreur = :livreurId");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':orderId', $orderId);
            $stmt->bindParam(':livreurId', $livreurId);
            $stmt->execute();
            
            $orderSuccess = "Le statut de la commande #$orderId a été mis à jour avec succès.";
            
            // Rafraîchir les données
            $currentDeliveries = getCurrentDeliveries($livreurId);
            $completedDeliveries = getCompletedDeliveries($livreurId);
            $totalDeliveries = countTotalDeliveries($livreurId);
            $todayDeliveries = countTodayDeliveries($livreurId);
            $totalEarnings = calculateTotalEarnings($livreurId);
        } catch (PDOException $e) {
            $orderError = "Erreur lors de la mise à jour du statut : " . $e->getMessage();
        }
    }
    
    // Traiter la mise à jour du profil
    if (isset($_POST['update_profile'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone']);
        $vehicule = $_POST['vehicule'];
        
        // Validation basique
        if (empty($nom) || empty($prenom) || empty($email) || empty($telephone)) {
            $profileError = 'Veuillez remplir tous les champs obligatoires.';
        } else {
            try {
                $conn = getDbConnection();
                $stmt = $conn->prepare("UPDATE Livreur SET nom_l = :nom, prenom_l = :prenom, email = :email, telephone = :telephone, vehicule = :vehicule WHERE id_livreur = :id");
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':telephone', $telephone);
                $stmt->bindParam(':vehicule', $vehicule);
                $stmt->bindParam(':id', $livreurId);
                $stmt->execute();
                
                $profileSuccess = 'Votre profil a été mis à jour avec succès.';
                
                // Rafraîchir les informations du livreur
                $livreur = getLivreurInfo($livreurId);
            } catch (PDOException $e) {
                $profileError = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
            }
        }
    }
    
    // Traiter le changement de mot de passe
    if (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validation basique
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $passwordError = 'Veuillez remplir tous les champs.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'Les nouveaux mots de passe ne correspondent pas.';
        } elseif (strlen($newPassword) < 6) {
            $passwordError = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } else {
            try {
                $conn = getDbConnection();
                
                // Vérifier le mot de passe actuel
                $stmt = $conn->prepare("SELECT mot_de_passe FROM Livreur WHERE id_livreur = :id");
                $stmt->bindParam(':id', $livreurId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($currentPassword, $user['mot_de_passe'])) {
                    // Hasher le nouveau mot de passe
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Mettre à jour le mot de passe
                    $stmt = $conn->prepare("UPDATE Livreur SET mot_de_passe = :password WHERE id_livreur = :id");
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':id', $livreurId);
                    $stmt->execute();
                    
                    $passwordSuccess = 'Votre mot de passe a été mis à jour avec succès.';
                } else {
                    $passwordError = 'Le mot de passe actuel est incorrect.';
                }
            } catch (PDOException $e) {
                $passwordError = "Erreur lors de la mise à jour du mot de passe : " . $e->getMessage();
            }
        }
    }
}

// Formater les statuts pour l'affichage
function formatStatus($status) {
    switch ($status) {
        case 'en attente':
            return '<span class="status status-pending">En attente</span>';
        case 'confirmé':
            return '<span class="status status-confirmed">Confirmée</span>';
        case 'en préparation':
            return '<span class="status status-confirmed">En préparation</span>';
        case 'en livraison':
            return '<span class="status status-confirmed">En livraison</span>';
        case 'livré':
            return '<span class="status status-delivered">Livrée</span>';
        case 'annulé':
            return '<span class="status status-cancelled">Annulée</span>';
        default:
            return '<span class="status">' . ucfirst($status) . '</span>';
    }
}
?>

<div class="livreur-dashboard-container">
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Espace Livreur</h2>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($livreur['prenom_l'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($livreur['prenom_l'] . ' ' . $livreur['nom_l']); ?></div>
                <div class="user-role">Livreur</div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu</h3>
            <ul class="menu-items">
                <li>
                    <a href="#dashboard" class="active">
                        <i class="fas fa-home"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="#current-deliveries">
                        <i class="fas fa-motorcycle"></i>
                        <span>Livraisons en cours</span>
                    </a>
                </li>
                <li>
                    <a href="#available-orders">
                        <i class="fas fa-bell"></i>
                        <span>Commandes disponibles</span>
                    </a>
                </li>
                <li>
                    <a href="#delivery-history">
                        <i class="fas fa-history"></i>
                        <span>Historique</span>
                    </a>
                </li>
                <li>
                    <a href="#profile">
                        <i class="fas fa-user"></i>
                        <span>Mon profil</span>
                    </a>
                </li>
                <li>
                    <a href="/pages/auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Contenu principal -->
    <div class="dashboard-content">
        <!-- Section Tableau de bord -->
        <section id="dashboard" class="active-section">
            <div class="content-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue, <?php echo $livreur['prenom_l']; ?>. Voici votre activité récente.</p>
                
                <?php if (!empty($orderSuccess)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $orderSuccess; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($orderError)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $orderError; ?>
                </div>
                <?php endif; ?>
                
                <?php if (count($availableOrders) > 0): ?>
                <div class="alert alert-info orders-available-alert">
                    <i class="fas fa-bell"></i> 
                    <strong><?php echo count($availableOrders); ?> commande(s) disponible(s) en attente de livreur !</strong> 
                    <a href="#available-orders" class="btn btn-sm">Voir les commandes</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon primary">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo count($currentDeliveries); ?></div>
                        <div class="info-card-label">Livraisons en cours</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon secondary">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $totalDeliveries; ?></div>
                        <div class="info-card-label">Livraisons totales</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon accent">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $todayDeliveries; ?></div>
                        <div class="info-card-label">Livraisons aujourd'hui</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon light">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo number_format($totalEarnings, 2); ?> €</div>
                        <div class="info-card-label">Gains totaux</div>
                    </div>
                </div>
            </div>
            
            <!-- Livraisons en cours résumé -->
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Livraisons en cours</h2>
                    <div class="data-table-actions">
                        <a href="#current-deliveries" class="btn btn-primary">Voir toutes les livraisons</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Restaurant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($currentDeliveries) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucune livraison en cours.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php 
                        $displayedDeliveries = array_slice($currentDeliveries, 0, 3);
                        foreach ($displayedDeliveries as $delivery): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($delivery['prenom_c'] . ' ' . $delivery['nom_c']); ?></td>
                            <td><?php echo htmlspecialchars($delivery['nom_r']); ?></td>
                            <td><?php echo formatStatus($delivery['statut']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="#delivery-<?php echo $delivery['id_commande']; ?>" class="btn-icon view delivery-details-btn">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Commandes disponibles résumé -->
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Commandes disponibles</h2>
                    <div class="data-table-actions">
                        <a href="#available-orders" class="btn btn-primary">Voir toutes les commandes</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Restaurant</th>
                            <th>Montant</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($availableOrders) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucune commande disponible pour le moment.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php 
                        $displayedOrders = array_slice($availableOrders, 0, 3);
                        foreach ($displayedOrders as $order): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['prenom_c'] . ' ' . $order['nom_c']); ?></td>
                            <td><?php echo htmlspecialchars($order['nom_r']); ?></td>
                            <td><?php echo number_format($order['montant'], 2); ?> €</td>
                            <td>
                                <div class="row-actions">
                                    <form method="POST" action="" class="accept-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id_commande']; ?>">
                                        <button type="submit" name="accept_order" class="btn-icon primary">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <a href="#order-<?php echo $order['id_commande']; ?>" class="btn-icon view order-details-btn">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Section Livraisons en cours -->
        <section id="current-deliveries">
            <div class="content-header">
                <h1>Livraisons en cours</h1>
                <p>Gérez vos livraisons actuelles et mettez à jour leur statut.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Toutes les livraisons en cours</h2>
                </div>
                
                <?php if (count($currentDeliveries) === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-motorcycle"></i>
                    <p>Vous n'avez pas de livraisons en cours.</p>
                    <a href="#available-orders" class="btn btn-primary">Voir les commandes disponibles</a>
                </div>
                <?php else: ?>
                
                <div class="deliveries-grid">
                    <?php foreach ($currentDeliveries as $delivery): ?>
                    <div class="delivery-card" id="delivery-<?php echo $delivery['id_commande']; ?>">
                        <div class="delivery-header">
                            <div class="delivery-id">#<?php echo $delivery['id_commande']; ?></div>
                            <div class="delivery-status"><?php echo formatStatus($delivery['statut']); ?></div>
                        </div>
                        
                        <div class="delivery-customer">
                            <h3>Client</h3>
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($delivery['prenom_c'] . ' ' . $delivery['nom_c']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($delivery['adresse_c']); ?></p>
                        </div>
                        
                        <div class="delivery-restaurant">
                            <h3>Restaurant</h3>
                            <p><i class="fas fa-store"></i> <?php echo htmlspecialchars($delivery['nom_r']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($delivery['adresse_r']); ?></p>
                        </div>
                        
                        <div class="delivery-details">
                            <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($delivery['date'])); ?></p>
                            <p><i class="fas fa-money-bill-wave"></i> <?php echo number_format($delivery['montant'], 2); ?> €</p>
                        </div>
                        
                        <div class="delivery-actions">
                            <form method="POST" action="">
                                <input type="hidden" name="order_id" value="<?php echo $delivery['id_commande']; ?>">
                                <select name="status" class="form-control status-select">
                                    <option value="en préparation" <?php echo $delivery['statut'] === 'en préparation' ? 'selected' : ''; ?>>En préparation</option>
                                    <option value="en livraison" <?php echo $delivery['statut'] === 'en livraison' ? 'selected' : ''; ?>>En livraison</option>
                                    <option value="livré">Livrée</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Section Commandes disponibles -->
        <section id="available-orders">
            <div class="content-header">
                <h1>Commandes disponibles</h1>
                <p>Consultez et acceptez les commandes disponibles pour livraison.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Commandes en attente de livreur</h2>
                </div>
                
                <?php if (count($availableOrders) === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune commande disponible pour le moment.</p>
                    <p class="empty-state-help">Les commandes confirmées par les restaurants apparaîtront ici.<br>Revenez vérifier dans quelques minutes.</p>
                </div>
                <?php else: ?>
                
                <div class="orders-grid">
                    <?php foreach ($availableOrders as $order): ?>
                    <div class="order-card" id="order-<?php echo $order['id_commande']; ?>">
                        <div class="order-header">
                            <div class="order-id">#<?php echo $order['id_commande']; ?></div>
                            <div class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></div>
                        </div>
                        
                        <div class="order-customer">
                            <h3>Client</h3>
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['prenom_c'] . ' ' . $order['nom_c']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['adresse_c']); ?></p>
                        </div>
                        
                        <div class="order-restaurant">
                            <h3>Restaurant</h3>
                            <p><i class="fas fa-store"></i> <?php echo htmlspecialchars($order['nom_r']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['adresse_r']); ?></p>
                        </div>
                        
                        <div class="order-details">
                            <p><i class="fas fa-money-bill-wave"></i> <?php echo number_format($order['montant'], 2); ?> €</p>
                            <p><i class="fas fa-money-bill-wave"></i> Votre commission: <?php echo number_format($order['montant'] * 0.1, 2); ?> €</p>
                        </div>
                        
                        <div class="order-actions">
                            <form method="POST" action="">
                                <input type="hidden" name="order_id" value="<?php echo $order['id_commande']; ?>">
                                <button type="submit" name="accept_order" class="btn btn-primary">Accepter la livraison</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Section Historique des livraisons -->
        <section id="delivery-history">
            <div class="content-header">
                <h1>Historique des livraisons</h1>
                <p>Consultez l'historique de vos livraisons terminées.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Livraisons terminées</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($completedDeliveries) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucune livraison terminée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($completedDeliveries as $delivery): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($delivery['date'])); ?></td>
                            <td><?php echo htmlspecialchars($delivery['prenom_c'] . ' ' . $delivery['nom_c']); ?></td>
                            <td><?php echo number_format($delivery['montant'], 2); ?> €</td>
                            <td><?php echo number_format($delivery['montant'] * 0.1, 2); ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Section Profil -->
        <section id="profile">
            <div class="content-header">
                <h1>Mon profil</h1>
                <p>Gérez vos informations personnelles.</p>
            </div>
            
            <div class="form-card">
                <h2>Informations personnelles</h2>
                
                <?php if (!empty($profileSuccess)): ?>
                <div class="alert alert-success">
                    <?php echo $profileSuccess; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($profileError)): ?>
                <div class="alert alert-danger">
                    <?php echo $profileError; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="dashboard.php#dashboard">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom">Nom :</label>
                            <input type="text" name="nom" id="nom" class="form-control" value="<?php echo htmlspecialchars($livreur['nom_l']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom :</label>
                            <input type="text" name="prenom" id="prenom" class="form-control" value="<?php echo htmlspecialchars($livreur['prenom_l']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone :</label>
                            <input type="tel" name="telephone" id="telephone" class="form-control" value="<?php echo htmlspecialchars($livreur['telephone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($livreur['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="vehicule">Type de véhicule :</label>
                            <select name="vehicule" id="vehicule" class="form-control" required>
                                <option value="scooter" <?php echo $livreur['vehicule'] === 'scooter' ? 'selected' : ''; ?>>Scooter</option>
                                <option value="velo" <?php echo $livreur['vehicule'] === 'velo' ? 'selected' : ''; ?>>Vélo</option>
                                <option value="voiture" <?php echo $livreur['vehicule'] === 'voiture' ? 'selected' : ''; ?>>Voiture</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour le profil</button>
                    </div>
                </form>
            </div>
            
            <div class="form-card" style="margin-top: 2rem;">
                <h2>Changer mon mot de passe</h2>
                
                <?php if (!empty($passwordSuccess)): ?>
                <div class="alert alert-success">
                    <?php echo $passwordSuccess; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($passwordError)): ?>
                <div class="alert alert-danger">
                    <?php echo $passwordError; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="dashboard.php#dashboard">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel :</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe :</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_password" class="btn btn-primary">Changer le mot de passe</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php
// Inclure le footer
include_once '../../includes/footer.php';
?>
</body>
</html> 