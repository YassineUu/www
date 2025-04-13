<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant qu'admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/auth/login.php?type=admin');
    exit;
}

$adminId = $_SESSION['user_id'];

// Fonctions pour récupérer les données

// Récupérer les statistiques générales
function getStatistics() {
    try {
        $conn = getDbConnection();
        
        // Nombre de clients
        $stmtClients = $conn->prepare("SELECT COUNT(*) as total FROM Client");
        $stmtClients->execute();
        $clients = $stmtClients->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de restaurants
        $stmtRestaurants = $conn->prepare("SELECT COUNT(*) as total FROM Restaurant");
        $stmtRestaurants->execute();
        $restaurants = $stmtRestaurants->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de livreurs
        $stmtLivreurs = $conn->prepare("SELECT COUNT(*) as total FROM Livreur");
        $stmtLivreurs->execute();
        $livreurs = $stmtLivreurs->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de commandes
        $stmtCommandes = $conn->prepare("SELECT COUNT(*) as total FROM Commande");
        $stmtCommandes->execute();
        $commandes = $stmtCommandes->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Chiffre d'affaires total
        $stmtCA = $conn->prepare("SELECT SUM(montant) as total FROM Paiement");
        $stmtCA->execute();
        $ca = $stmtCA->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
        
        return [
            'clients' => $clients,
            'restaurants' => $restaurants,
            'livreurs' => $livreurs,
            'commandes' => $commandes,
            'ca' => $ca
        ];
    } catch (PDOException $e) {
        return [
            'clients' => 0,
            'restaurants' => 0,
            'livreurs' => 0,
            'commandes' => 0,
            'ca' => 0
        ];
    }
}

// Récupérer les dernières commandes
function getRecentOrders($limit = 10) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT c.*, cl.nom_c, cl.prenom_c, p.montant, p.mode 
                               FROM Commande c 
                               JOIN Client cl ON c.id_client = cl.id_client
                               JOIN Paiement p ON c.id_commande = p.id_commande 
                               ORDER BY c.date DESC
                               LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les clients
function getClients($limit = 10) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Client ORDER BY date_inscription DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les restaurants
function getRestaurants($limit = 10) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Restaurant ORDER BY date_inscription DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les livreurs
function getLivreurs($limit = 10) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Livreur ORDER BY date_inscription DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les données
$stats = getStatistics();
$recentOrders = getRecentOrders();
$clients = getClients();
$restaurants = getRestaurants();
$livreurs = getLivreurs();

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
            <h2>Administration</h2>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-info">
                <div class="user-name">Administrateur</div>
                <div class="user-role">Super Admin</div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu</h3>
            <ul class="menu-items">
                <li>
                    <a href="#dashboard" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="#clients">
                        <i class="fas fa-users"></i>
                        <span>Clients</span>
                    </a>
                </li>
                <li>
                    <a href="#restaurants">
                        <i class="fas fa-store"></i>
                        <span>Restaurants</span>
                    </a>
                </li>
                <li>
                    <a href="#livreurs">
                        <i class="fas fa-motorcycle"></i>
                        <span>Livreurs</span>
                    </a>
                </li>
                <li>
                    <a href="#commandes">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#categories">
                        <i class="fas fa-tags"></i>
                        <span>Catégories</span>
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
                <h1>Tableau de bord administrateur</h1>
                <p>Vue d'ensemble de toutes les activités de la plateforme.</p>
            </div>
            
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $stats['clients']; ?></div>
                        <div class="info-card-label">Clients</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon secondary">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $stats['restaurants']; ?></div>
                        <div class="info-card-label">Restaurants</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon accent">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $stats['livreurs']; ?></div>
                        <div class="info-card-label">Livreurs</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon light">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $stats['commandes']; ?></div>
                        <div class="info-card-label">Commandes</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon primary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo number_format($stats['ca'], 2); ?> €</div>
                        <div class="info-card-label">Chiffre d'affaires</div>
                    </div>
                </div>
            </div>
            
            <!-- Commandes récentes -->
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Commandes récentes</h2>
                    <div class="data-table-actions">
                        <a href="#commandes" class="btn btn-primary">Voir toutes les commandes</a>
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
                        <?php if (count($recentOrders) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php 
                        $displayedOrders = array_slice($recentOrders, 0, 5);
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
        
        <!-- Section Clients -->
        <section id="clients">
            <div class="content-header">
                <h1>Gestion des Clients</h1>
                <p>Liste de tous les clients enregistrés sur la plateforme.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Tous les clients</h2>
                    <div class="data-table-actions">
                        <a href="#add-client" class="btn btn-primary">Ajouter un client</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clients) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun client trouvé.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>#<?php echo $client['id_client']; ?></td>
                            <td><?php echo htmlspecialchars($client['prenom_c'] . ' ' . $client['nom_c']); ?></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo isset($client['date_inscription']) ? date('d/m/Y', strtotime($client['date_inscription'])) : 'N/A'; ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="#edit-client-<?php echo $client['id_client']; ?>" class="btn-icon edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#delete-client-<?php echo $client['id_client']; ?>" class="btn-icon delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="#view-client-<?php echo $client['id_client']; ?>" class="btn-icon view">
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
        
        <!-- Section Restaurants -->
        <section id="restaurants">
            <div class="content-header">
                <h1>Gestion des Restaurants</h1>
                <p>Liste de tous les restaurants enregistrés sur la plateforme.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Tous les restaurants</h2>
                    <div class="data-table-actions">
                        <a href="#add-restaurant" class="btn btn-primary">Ajouter un restaurant</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($restaurants) === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun restaurant trouvé.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($restaurants as $restaurant): ?>
                        <tr>
                            <td>#<?php echo $restaurant['id_restaurant']; ?></td>
                            <td><?php echo htmlspecialchars($restaurant['nom_r']); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['adresse_r']); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['contact']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="#edit-restaurant-<?php echo $restaurant['id_restaurant']; ?>" class="btn-icon edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#delete-restaurant-<?php echo $restaurant['id_restaurant']; ?>" class="btn-icon delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="#view-restaurant-<?php echo $restaurant['id_restaurant']; ?>" class="btn-icon view">
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
        
        <!-- Section Livreurs -->
        <section id="livreurs">
            <div class="content-header">
                <h1>Gestion des Livreurs</h1>
                <p>Liste de tous les livreurs enregistrés sur la plateforme.</p>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Tous les livreurs</h2>
                    <div class="data-table-actions">
                        <a href="#add-livreur" class="btn btn-primary">Ajouter un livreur</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Véhicule</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($livreurs) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Aucun livreur trouvé.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($livreurs as $livreur): ?>
                        <tr>
                            <td>#<?php echo $livreur['id_livreur']; ?></td>
                            <td><?php echo htmlspecialchars($livreur['prenom_l'] . ' ' . $livreur['nom_l']); ?></td>
                            <td><?php echo htmlspecialchars($livreur['email']); ?></td>
                            <td><?php echo htmlspecialchars($livreur['telephone']); ?></td>
                            <td><?php echo htmlspecialchars($livreur['vehicule']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="#edit-livreur-<?php echo $livreur['id_livreur']; ?>" class="btn-icon edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#delete-livreur-<?php echo $livreur['id_livreur']; ?>" class="btn-icon delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="#view-livreur-<?php echo $livreur['id_livreur']; ?>" class="btn-icon view">
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
        
        <!-- Section Commandes -->
        <section id="commandes">
            <div class="content-header">
                <h1>Gestion des Commandes</h1>
                <p>Liste de toutes les commandes passées sur la plateforme.</p>
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
                        <?php if (count($recentOrders) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id_commande']; ?></td>
                            <td><?php echo htmlspecialchars($order['prenom_c'] . ' ' . $order['nom_c']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                            <td><?php echo number_format($order['montant'], 2); ?> €</td>
                            <td><?php echo formatStatus($order['statut']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="#edit-commande-<?php echo $order['id_commande']; ?>" class="btn-icon edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#view-commande-<?php echo $order['id_commande']; ?>" class="btn-icon view">
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
        
        <!-- Section Catégories -->
        <section id="categories">
            <div class="content-header">
                <h1>Gestion des Catégories</h1>
                <p>Liste des catégories de produits disponibles.</p>
            </div>
            
            <div class="form-card">
                <h2>Ajouter une catégorie</h2>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom_categorie">Nom de la catégorie :</label>
                            <input type="text" name="nom_categorie" id="nom_categorie" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_category" class="btn btn-primary">Ajouter la catégorie</button>
                    </div>
                </form>
            </div>
            
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Catégories existantes</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Remplir avec les catégories existantes -->
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

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
});
</script>

<?php
// Ne pas inclure le footer car on utilise notre propre mise en page pour le tableau de bord
?>
</body>
</html> 