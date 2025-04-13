<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: /pages/auth/login.php?type=client');
    exit;
}

$clientId = $_SESSION['user_id'];

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

// Récupérer les commandes du client
function getClientOrders($clientId, $limit = 5) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT c.*, p.montant, p.mode 
                               FROM Commande c 
                               JOIN Paiement p ON c.id_commande = p.id_commande 
                               WHERE c.id_client = :clientId
                               ORDER BY c.date DESC
                               LIMIT :limit");
        $stmt->bindParam(':clientId', $clientId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Compter le nombre total de commandes
function countClientOrders($clientId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Commande WHERE id_client = :clientId");
        $stmt->bindParam(':clientId', $clientId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Calculer le montant total dépensé
function getTotalSpent($clientId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT SUM(p.montant) as total 
                               FROM Paiement p 
                               JOIN Commande c ON p.id_commande = c.id_commande 
                               WHERE c.id_client = :clientId");
        $stmt->bindParam(':clientId', $clientId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Compter les commandes en cours
function countPendingOrders($clientId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total 
                               FROM Commande 
                               WHERE id_client = :clientId 
                               AND statut IN ('en attente', 'confirmé', 'en préparation', 'en livraison')");
        $stmt->bindParam(':clientId', $clientId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Récupérer les données
$client = getClientInfo($clientId);
$orders = getClientOrders($clientId);
$totalOrders = countClientOrders($clientId);
$totalSpent = getTotalSpent($clientId);
$pendingOrders = countPendingOrders($clientId);

// Traiter la mise à jour du profil
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $adresse = trim($_POST['adresse']);
    $email = trim($_POST['email']);
    
    // Validation basique
    if (empty($nom) || empty($prenom) || empty($adresse) || empty($email)) {
        $updateError = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Vérifier si l'email existe déjà (sauf pour le client actuel)
            $stmt = $conn->prepare("SELECT * FROM Client WHERE email = :email AND id_client != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $clientId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $updateError = 'Cet email est déjà utilisé par un autre compte.';
            } else {
                // Mettre à jour le profil
                $stmt = $conn->prepare("UPDATE Client SET nom_c = :nom, prenom_c = :prenom, adresse_c = :adresse, email = :email WHERE id_client = :id");
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $clientId);
                $stmt->execute();
                
                $updateSuccess = true;
                
                // Rafraîchir les données du client
                $client = getClientInfo($clientId);
            }
        } catch (PDOException $e) {
            $updateError = 'Erreur lors de la mise à jour du profil: ' . $e->getMessage();
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
            <h2>Tableau de bord</h2>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($client['prenom_c'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($client['prenom_c'] . ' ' . $client['nom_c']); ?></div>
                <div class="user-role">Client</div>
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
                        <span>Mes commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#profile">
                        <i class="fas fa-user"></i>
                        <span>Mon profil</span>
                    </a>
                </li>
                <li>
                    <a href="/pages/client/restaurants.php">
                        <i class="fas fa-utensils"></i>
                        <span>Restaurants</span>
                    </a>
                </li>
                <li>
                    <a href="/pages/client/panier.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Mon panier</span>
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
                <p>Bienvenue, <?php echo htmlspecialchars($client['prenom_c']); ?> ! Voici un résumé de votre activité.</p>
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
                        <div class="info-card-value"><?php echo number_format($totalSpent, 2); ?> €</div>
                        <div class="info-card-label">Montant total dépensé</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon accent">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-card-content">
                        <div class="info-card-value"><?php echo $pendingOrders; ?></div>
                        <div class="info-card-label">Commandes en cours</div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Section Commandes récentes -->
        <section id="orders">
            <div class="data-table-container">
                <div class="data-table-header">
                    <h2>Commandes récentes</h2>
                    <div class="data-table-actions">
                        <a href="#" class="btn btn-primary">Voir toutes les commandes</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucune commande trouvée.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                            <td><?php echo number_format($order['montant'], 2); ?> €</td>
                            <td><?php echo formatStatus($order['statut']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="/pages/client/confirmation.php?id=<?php echo $order['id_commande']; ?>" class="btn-icon view" title="Voir les détails">
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
        
        <!-- Section Profil -->
        <section id="profile">
            <div class="form-card">
                <h2>Mon profil</h2>
                
                <?php if ($updateSuccess): ?>
                <div class="alert alert-success">
                    Votre profil a été mis à jour avec succès.
                </div>
                <?php endif; ?>
                
                <?php if (!empty($updateError)): ?>
                <div class="alert alert-danger">
                    <?php echo $updateError; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="dashboard.php#dashboard">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom">Nom :</label>
                            <input type="text" name="nom" id="nom" class="form-control" value="<?php echo htmlspecialchars($client['nom_c']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom :</label>
                            <input type="text" name="prenom" id="prenom" class="form-control" value="<?php echo htmlspecialchars($client['prenom_c']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="adresse">Adresse :</label>
                            <textarea name="adresse" id="adresse" class="form-control" rows="3" required><?php echo htmlspecialchars($client['adresse_c']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour le profil</button>
                    </div>
                </form>
            </div>
            
            <div class="form-card" style="margin-top: 2rem;">
                <h2>Changer mon mot de passe</h2>
                
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation des sections
    const menuLinks = document.querySelectorAll('.menu-items a');
    const sections = document.querySelectorAll('.dashboard-content section');
    
    // Fonction pour afficher une section spécifique par son ID
    function showSection(targetId) {
        console.log("Affichage de la section:", targetId);
        
        // Masquer toutes les sections
        sections.forEach(section => {
            section.style.display = 'none';
        });
        
        // Supprimer la classe active de tous les liens
        menuLinks.forEach(menuLink => {
            menuLink.classList.remove('active');
        });
        
        // Afficher la section cible
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            targetSection.style.display = 'block';
            
            // Mettre à jour le lien actif dans le menu
            const activeLink = document.querySelector(`.menu-items a[href="#${targetId}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }
            
            // Mettre à jour l'URL sans recharger la page
            history.replaceState(null, null, `#${targetId}`);
        }
    }
    
    // Vérifier si un fragment existe dans l'URL
    const hash = window.location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        // Si un fragment valide existe, afficher cette section
        showSection(hash);
    } else {
        // Sinon, afficher la première section (tableau de bord)
        showSection('dashboard');
    }
    
    // Gestion de la navigation
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si c'est un lien interne (avec #)
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                
                // Récupérer l'ID de la section à afficher
                const targetId = this.getAttribute('href').substring(1);
                
                // Afficher la section correspondante
                showSection(targetId);
                
                // Scroll au début de la section
                window.scrollTo(0, 0);
            }
        });
    });
    
    // Gestion des formulaires - rediriger vers la bonne section après soumission
    document.querySelectorAll('form').forEach(form => {
        // Vérifier si le formulaire a déjà une action avec un fragment
        const action = form.getAttribute('action') || '';
        if (!action.includes('#')) {
            // Ajouter le fragment dashboard pour rediriger vers l'accueil
            form.setAttribute('action', `dashboard.php#dashboard`);
        }
    });
    
    // Écouter les changements d'URL pour mettre à jour la section active
    window.addEventListener('hashchange', function() {
        const newHash = window.location.hash.substring(1);
        if (newHash && document.getElementById(newHash)) {
            showSection(newHash);
        }
    });
});
</script>

<?php
// Ne pas inclure le footer car on utilise notre propre mise en page pour le tableau de bord
?>
</body>
</html> 