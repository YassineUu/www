<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que restaurant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: /pages/auth/login.php?type=restaurant');
    exit;
}

$restaurantId = $_SESSION['user_id'];

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

// Récupérer les commandes du restaurant
function getRestaurantOrders($restaurantId, $limit = 10) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT DISTINCT c.*, cl.nom_c, cl.prenom_c, p.montant, p.mode 
                               FROM Commande c 
                               JOIN Client cl ON c.id_client = cl.id_client
                               JOIN Paiement p ON c.id_commande = p.id_commande 
                               JOIN Contient co ON c.id_commande = co.id_commande
                               JOIN Produit pr ON co.id_produit = pr.id_produit
                               WHERE pr.id_restaurant = :restaurantId
                               ORDER BY c.date DESC
                               LIMIT :limit");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les produits du restaurant
function getRestaurantProducts($restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT p.*, c.nom as nom_categorie 
                               FROM Produit p 
                               JOIN Categorie c ON p.id_categorie = c.id_categorie 
                               WHERE p.id_restaurant = :restaurantId
                               ORDER BY c.nom, p.nom");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Déboggage - vérifier les données récupérées
        error_log("Nombre de produits récupérés: " . count($products));
        if (count($products) > 0) {
            error_log("Premier produit: " . print_r($products[0], true));
        }
        
        return $products;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des produits: " . $e->getMessage());
        return [];
    }
}

// Récupérer les catégories
function getCategories() {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT id_categorie, nom as nom_categorie FROM Categorie ORDER BY nom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Compter le nombre total de commandes
function countRestaurantOrders($restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT c.id_commande) as total 
                               FROM Commande c 
                               JOIN Contient co ON c.id_commande = co.id_commande
                               JOIN Produit p ON co.id_produit = p.id_produit
                               WHERE p.id_restaurant = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Calculer le montant total des ventes
function getTotalSales($restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT SUM(p.prix * co.qte_produit) as total 
                               FROM Produit p 
                               JOIN Contient co ON p.id_produit = co.id_produit
                               JOIN Commande c ON co.id_commande = c.id_commande
                               WHERE p.id_restaurant = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Compter le nombre de produits
function countProducts($restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Produit WHERE id_restaurant = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Récupérer les données
$restaurant = getRestaurantInfo($restaurantId);
$orders = getRestaurantOrders($restaurantId);
$products = getRestaurantProducts($restaurantId);
$categories = getCategories();
$totalOrders = countRestaurantOrders($restaurantId);
$totalSales = getTotalSales($restaurantId);
$totalProducts = countProducts($restaurantId);

// Initialiser les variables pour les messages
$productSuccess = '';
$productError = '';
$orderSuccess = '';
$orderError = '';
$profileSuccess = '';
$profileError = '';
$passwordSuccess = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traiter l'ajout d'un produit
    if (isset($_POST['add_product'])) {
        $nom = trim($_POST['nom']);
        $prix = floatval($_POST['prix']);
        $categorie = intval($_POST['categorie']);
        $description = trim($_POST['description']);
        $id_restaurant = $restaurantId;
        
        // Validation basique
        if (empty($nom) || $prix <= 0 || empty($description) || $categorie <= 0) {
            $productError = 'Veuillez remplir tous les champs correctement.';
        } else {
            try {
                $conn = getDbConnection();
                
                $stmt = $conn->prepare("INSERT INTO Produit (id_restaurant, id_categorie, nom, description, prix, disponible) 
                                      VALUES (:restaurantId, :categorieId, :nom, :description, :prix, TRUE)");
                $stmt->bindParam(':restaurantId', $id_restaurant);
                $stmt->bindParam(':categorieId', $categorie);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':prix', $prix);
                
                $result = $stmt->execute();
                if ($result) {
                    $productSuccess = 'Produit ajouté avec succès.';
                    
                    // Rafraîchir la liste des produits
                    $products = getRestaurantProducts($id_restaurant);
                    $totalProducts = countProducts($id_restaurant);
                    
                    // Redirection vers la section produits pour voir le produit ajouté
                    echo "<script>window.location.href = 'dashboard.php#products';</script>";
                } else {
                    $productError = 'Erreur lors de l\'exécution de la requête.';
                }
            } catch (PDOException $e) {
                $productError = 'Erreur lors de l\'ajout du produit: ' . $e->getMessage();
                error_log("Erreur PDO: " . $e->getMessage());
            }
        }
    }
    
    // Traiter la modification du statut d'une commande
    if (isset($_POST['update_order_status'])) {
        $orderId = intval($_POST['order_id']);
        $status = $_POST['status'];
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare("UPDATE Commande SET statut = :status WHERE id_commande = :orderId");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':orderId', $orderId);
            $stmt->execute();
            
            // Rafraîchir la liste des commandes
            $orders = getRestaurantOrders($restaurantId);
            
            // Message de succès
            $orderSuccess = "Le statut de la commande #$orderId a été mis à jour avec succès.";
            
            // Rediriger vers la section des commandes
            echo "<script>window.location.href = 'dashboard.php#orders';</script>";
        } catch (PDOException $e) {
            // Gérer l'erreur
            $orderError = "Erreur lors de la mise à jour du statut : " . $e->getMessage();
        }
    }
    
    // Traiter la mise à jour du profil
    if (isset($_POST['update_profile'])) {
        $nom = trim($_POST['nom_restaurant']);
        $adresse = trim($_POST['adresse']);
        $contact = trim($_POST['contact']);
        
        // Validation basique
        if (empty($nom) || empty($adresse) || empty($contact)) {
            $profileError = 'Veuillez remplir tous les champs.';
        } else {
            try {
                $conn = getDbConnection();
                $stmt = $conn->prepare("UPDATE Restaurant SET nom_r = :nom, adresse_r = :adresse, contact = :contact WHERE id_restaurant = :id");
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':contact', $contact);
                $stmt->bindParam(':id', $restaurantId);
                $stmt->execute();
                
                // Message de succès
                $profileSuccess = 'Votre profil a été mis à jour avec succès.';
                
                // Rafraîchir les informations du restaurant
                $restaurant = getRestaurantInfo($restaurantId);
            } catch (PDOException $e) {
                $profileError = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
            }
        }
    }
    
    // Traiter la suppression d'un produit
    if (isset($_POST['delete_product'])) {
        $productId = intval($_POST['product_id']);
        
        try {
            $conn = getDbConnection();
            // Vérifier si le produit appartient bien au restaurant
            $stmt = $conn->prepare("SELECT id_produit FROM Produit WHERE id_produit = :productId AND id_restaurant = :restaurantId");
            $stmt->bindParam(':productId', $productId);
            $stmt->bindParam(':restaurantId', $restaurantId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Supprimer le produit
                $stmt = $conn->prepare("DELETE FROM Produit WHERE id_produit = :productId");
                $stmt->bindParam(':productId', $productId);
                $stmt->execute();
                
                $productSuccess = 'Produit supprimé avec succès.';
                
                // Rafraîchir la liste des produits
                $products = getRestaurantProducts($restaurantId);
                $totalProducts = countProducts($restaurantId);
            } else {
                $productError = 'Erreur : produit non trouvé ou non autorisé.';
            }
        } catch (PDOException $e) {
            // Si le produit est utilisé dans des commandes, il y aura une erreur de contrainte d'intégrité
            $productError = 'Impossible de supprimer ce produit car il est utilisé dans des commandes.';
        }
    }
    
    // Traiter la mise à jour du mot de passe
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
                
                // Vérifier si le champ password existe dans la table Restaurant
                $stmt = $conn->prepare("SHOW COLUMNS FROM Restaurant LIKE 'password'");
                $stmt->execute();
                
                // Si le champ n'existe pas, nous l'ajoutons
                if ($stmt->rowCount() == 0) {
                    $stmt = $conn->prepare("ALTER TABLE Restaurant ADD COLUMN password VARCHAR(255) NULL AFTER email");
                    $stmt->execute();
                }
                
                // Vérifier le mot de passe actuel
                $stmt = $conn->prepare("SELECT contact, password FROM Restaurant WHERE id_restaurant = :id");
                $stmt->bindParam(':id', $restaurantId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $passwordVerified = false;
                
                // Vérifier si le mot de passe est stocké dans le champ password ou contact
                if (!empty($user['password']) && password_verify($currentPassword, $user['password'])) {
                    $passwordVerified = true;
                } elseif (password_verify($currentPassword, $user['contact'])) {
                    $passwordVerified = true;
                }
                
                if ($passwordVerified) {
                    // Hasher le nouveau mot de passe
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Mettre à jour le mot de passe dans le champ password
                    $stmt = $conn->prepare("UPDATE Restaurant SET password = :password WHERE id_restaurant = :id");
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':id', $restaurantId);
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

<div class="restaurant-dashboard-container">
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Espace Restaurant</h2>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($restaurant['nom_r'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($restaurant['nom_r']); ?></div>
                <div class="user-role">Restaurant</div>
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
                    <a href="#orders">
                        <i class="fas fa-list"></i>
                        <span>Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#products">
                        <i class="fas fa-utensils"></i>
                        <span>Mes produits</span>
                    </a>
                </li>
                <li>
                    <a href="#add-product">
                        <i class="fas fa-plus-circle"></i>
                        <span>Ajouter un produit</span>
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
        <section id="dashboard">
            <div class="content-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue, <?php echo htmlspecialchars($restaurant['nom_r']); ?> ! Voici un résumé de votre activité.</p>
            </div>
            
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon primary">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $totalOrders; ?></div>
                        <div class="info-card-label">Commandes totales</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon secondary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo number_format($totalSales, 2); ?> €</div>
                        <div class="info-card-label">Chiffre d'affaires</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon accent">
                        <i class="fas fa-hamburger"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $totalProducts; ?></div>
                        <div class="info-card-label">Produits au menu</div>
                    </div>
                </div>
            </div>
            
            <!-- Commandes récentes -->
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Commandes récentes</h2>
                    <div class="data-table-actions">
                        <a href="#orders" class="btn btn-primary">Voir toutes les commandes</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php 
                        $displayedOrders = array_slice($orders, 0, 5);
                        foreach ($displayedOrders as $order): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['prenom_c'] . ' ' . $order['nom_c']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                            <td><?php echo number_format($order['montant'], 2); ?> €</td>
                            <td><?php echo formatStatus($order['statut']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Section Commandes -->
        <section id="orders">
            <div class="content-header">
                <h1>Gestion des commandes</h1>
                <p>Visualisez et gérez les commandes passées par les clients.</p>
            </div>
            
            <?php if (!empty($orderSuccess)): ?>
            <div class="alert alert-success">
                <?php echo $orderSuccess; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($orderError)): ?>
            <div class="alert alert-danger">
                <?php echo $orderError; ?>
            </div>
            <?php endif; ?>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Toutes les commandes</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['prenom_c'] . ' ' . $order['nom_c']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                            <td><?php echo number_format($order['montant'], 2); ?> €</td>
                            <td><?php echo formatStatus($order['statut']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <button class="btn-icon edit order-status-btn" data-order-id="<?php echo $order['id_commande']; ?>" data-status="<?php echo $order['statut']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="#" class="btn-icon view" title="Voir les détails">
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
            
            <!-- Modal pour changer le statut -->
            <div id="status-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>Modifier le statut de la commande</h2>
                    <form method="POST" action="dashboard.php#orders">
                        <input type="hidden" id="order_id" name="order_id">
                        <div class="form-group">
                            <label for="status">Nouveau statut :</label>
                            <select name="status" id="status" class="form-control">
                                <option value="confirmé">Confirmé</option>
                                <option value="en préparation">En préparation</option>
                                <option value="en livraison">En livraison</option>
                                <option value="annulé">Annulé</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="update_order_status" class="btn btn-primary">Mettre à jour</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <!-- Section Produits -->
        <section id="products">
            <div class="content-header">
                <h1>Mes produits</h1>
                <p>Gérez les produits proposés par votre restaurant.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Liste des produits</h2>
                    <div class="data-table-actions">
                        <a href="#add-product" class="btn btn-primary">Ajouter un produit</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucun produit trouvé.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['nom']); ?></td>
                            <td><?php echo htmlspecialchars($product['nom_categorie']); ?></td>
                            <td><?php echo number_format($product['prix'], 2); ?> €</td>
                            <td>
                                <div class="row-actions">
                                    <a href="#" class="btn-icon edit product-edit-btn" data-product-id="<?php echo $product['id_produit']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="dashboard.php#products" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id_produit']; ?>">
                                        <button type="submit" name="delete_product" class="btn-icon delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Section Ajouter un produit -->
        <section id="add-product">
            <div class="content-header">
                <h1>Ajouter un produit</h1>
                <p>Ajoutez un nouveau produit à votre menu.</p>
            </div>
            
            <div class="form-card">
                <h2>Informations du produit</h2>
                
                <?php if (!empty($productSuccess)): ?>
                <div class="alert alert-success">
                    <?php echo $productSuccess; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($productError)): ?>
                <div class="alert alert-danger">
                    <?php echo $productError; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="dashboard.php#add-product">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom">Nom du produit :</label>
                            <input type="text" name="nom" id="nom" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prix">Prix (€) :</label>
                            <input type="number" name="prix" id="prix" step="0.01" min="0" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="categorie">Catégorie :</label>
                            <select name="categorie" id="categorie" class="form-control" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id_categorie']; ?>"><?php echo htmlspecialchars($category['nom_categorie']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description :</label>
                            <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_product" class="btn btn-primary">Ajouter le produit</button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Section Profil -->
        <section id="profile">
            <div class="content-header">
                <h1>Mon profil</h1>
                <p>Gérez les informations de votre restaurant.</p>
            </div>
            
            <div class="form-card">
                <h2>Informations du restaurant</h2>
                
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
                            <label for="nom_restaurant">Nom du restaurant :</label>
                            <input type="text" name="nom_restaurant" id="nom_restaurant" class="form-control" value="<?php echo htmlspecialchars($restaurant['nom_r']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact">Numéro de téléphone :</label>
                            <input type="text" name="contact" id="contact" class="form-control" value="<?php echo htmlspecialchars($restaurant['contact']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="adresse">Adresse :</label>
                            <textarea name="adresse" id="adresse" class="form-control" rows="3" required><?php echo htmlspecialchars($restaurant['adresse_r']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour le profil</button>
                    </div>
                </form>
            </div>
            
            <div class="form-card mt-4">
                <h2>Changer le mot de passe</h2>
                
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
                            <small class="form-text text-muted">Le mot de passe doit contenir au moins 6 caractères.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_password" class="btn btn-primary">Mettre à jour le mot de passe</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div id="delete-confirm-modal" class="modal">
    <div class="modal-content">
        <div class="delete-confirm-content">
            <h2>Confirmer la suppression</h2>
            <p>Êtes-vous sûr de vouloir supprimer ce produit ?</p>
            <div class="delete-confirm-actions">
                <button id="delete-cancel" class="btn-cancel">Annuler</button>
                <button id="delete-confirm" class="btn-delete">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?>
</body>
</html>

<script>
// Script pour gérer le comportement de la modal de statut
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de modification de statut
    const statusButtons = document.querySelectorAll('.order-status-btn');
    const modal = document.getElementById('status-modal');
    const closeModal = document.querySelector('.close-modal');
    const orderIdInput = document.getElementById('order_id');
    const statusSelect = document.getElementById('status');
    
    // Ouvrir la modal quand on clique sur un bouton de statut
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const currentStatus = this.getAttribute('data-status');
            
            // Définir l'ID de commande dans le formulaire
            orderIdInput.value = orderId;
            
            // Sélectionner le statut actuel dans le menu déroulant
            for (let i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value === currentStatus) {
                    statusSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Afficher la modal
            modal.style.display = 'block';
        });
    });
    
    // Fermer la modal quand on clique sur le X
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Fermer la modal si on clique en dehors
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script> 