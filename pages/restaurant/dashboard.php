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
        $stmt = $conn->prepare("SELECT p.*, c.nom_categorie 
                               FROM Produit p 
                               JOIN Categorie c ON p.id_categorie = c.id_categorie 
                               WHERE p.id_restaurant = :restaurantId
                               ORDER BY c.nom_categorie, p.nom_p");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les catégories
function getCategories() {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Categorie ORDER BY nom_categorie");
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

// Traiter l'ajout/modification de produit
$productSuccess = '';
$productError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traiter l'ajout d'un produit
    if (isset($_POST['add_product'])) {
        $nom = trim($_POST['nom']);
        $prix = floatval($_POST['prix']);
        $description = trim($_POST['description']);
        $categorie = intval($_POST['categorie']);
        
        // Validation basique
        if (empty($nom) || $prix <= 0 || empty($description) || $categorie <= 0) {
            $productError = 'Veuillez remplir tous les champs correctement.';
        } else {
            try {
                $conn = getDbConnection();
                $stmt = $conn->prepare("INSERT INTO Produit (id_restaurant, id_categorie, nom_p, prix, description_c) 
                                       VALUES (:restaurantId, :categorieId, :nom, :prix, :description)");
                $stmt->bindParam(':restaurantId', $restaurantId);
                $stmt->bindParam(':categorieId', $categorie);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prix', $prix);
                $stmt->bindParam(':description', $description);
                $stmt->execute();
                
                $productSuccess = 'Produit ajouté avec succès.';
                
                // Rafraîchir la liste des produits
                $products = getRestaurantProducts($restaurantId);
                $totalProducts = countProducts($restaurantId);
            } catch (PDOException $e) {
                $productError = 'Erreur lors de l\'ajout du produit: ' . $e->getMessage();
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
        } catch (PDOException $e) {
            // Gérer l'erreur
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

<div class="dashboard-container">
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
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php 
                        $displayedOrders = array_slice($orders, 0, 5);
                        foreach ($displayedOrders as $order): 
                        ?>
                        <tr>
                            <td>#<?php echo $order['id_commande']; ?></td>
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
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Toutes les commandes</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
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
                            <td colspan="6" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id_commande']; ?></td>
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
                    <form method="POST" action="">
                        <input type="hidden" id="order_id" name="order_id">
                        <div class="form-group">
                            <label for="status">Nouveau statut :</label>
                            <select name="status" id="status" class="form-control">
                                <option value="en attente">En attente</option>
                                <option value="confirmé">Confirmé</option>
                                <option value="en préparation">En préparation</option>
                                <option value="en livraison">En livraison</option>
                                <option value="livré">Livré</option>
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
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun produit trouvé.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>#<?php echo $product['id_produit']; ?></td>
                            <td><?php echo htmlspecialchars($product['nom_p']); ?></td>
                            <td><?php echo htmlspecialchars($product['nom_categorie']); ?></td>
                            <td><?php echo number_format($product['prix'], 2); ?> €</td>
                            <td>
                                <div class="row-actions">
                                    <a href="#" class="btn-icon edit product-edit-btn" data-product-id="<?php echo $product['id_produit']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn-icon delete product-delete-btn" data-product-id="<?php echo $product['id_produit']; ?>">
                                        <i class="fas fa-trash"></i>
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
                
                <form method="POST" action="">
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
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom_restaurant">Nom du restaurant :</label>
                            <input type="text" name="nom_restaurant" id="nom_restaurant" class="form-control" value="<?php echo htmlspecialchars($restaurant['nom_r']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact">Contact :</label>
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
        </section>
    </div>
</div>

<style>
/* Style pour la modal */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    position: relative;
}

.close-modal {
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

/* Alerts */
.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.alert-success {
    background-color: rgba(52, 168, 83, 0.1);
    color: var(--vert-frais);
    border: 1px solid var(--vert-frais);
}

.alert-danger {
    background-color: rgba(255, 99, 71, 0.1);
    color: tomato;
    border: 1px solid tomato;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation des sections
    const menuLinks = document.querySelectorAll('.menu-items a');
    const sections = document.querySelectorAll('.dashboard-content section');
    
    // Masquer toutes les sections sauf la première
    sections.forEach((section, index) => {
        if (index !== 0) {
            section.style.display = 'none';
        }
    });
    
    // Gestion de la navigation
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si c'est un lien interne (avec #)
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                
                // Supprimer la classe active de tous les liens
                menuLinks.forEach(menuLink => {
                    menuLink.classList.remove('active');
                });
                
                // Ajouter la classe active au lien cliqué
                this.classList.add('active');
                
                // Récupérer l'ID de la section à afficher
                const targetId = this.getAttribute('href').substring(1);
                
                // Masquer toutes les sections
                sections.forEach(section => {
                    section.style.display = 'none';
                });
                
                // Afficher la section cible
                document.getElementById(targetId).style.display = 'block';
            }
        });
    });
    
    // Modal pour modifier le statut des commandes
    const statusModal = document.getElementById('status-modal');
    const closeModal = document.querySelector('.close-modal');
    const statusBtns = document.querySelectorAll('.order-status-btn');
    
    statusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const status = this.getAttribute('data-status');
            
            document.getElementById('order_id').value = orderId;
            document.getElementById('status').value = status;
            
            statusModal.style.display = 'block';
        });
    });
    
    closeModal.addEventListener('click', function() {
        statusModal.style.display = 'none';
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === statusModal) {
            statusModal.style.display = 'none';
        }
    });
});
</script>

<?php
// Ne pas inclure le footer car on utilise notre propre mise en page pour le tableau de bord
?>
</body>
</html> 