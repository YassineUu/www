<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que restaurant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: /pages/auth/login.php?type=restaurant');
    exit;
}

$restaurantId = $_SESSION['user_id'];
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$productSuccess = '';
$productError = '';

// Vérifier si le produit existe et appartient bien au restaurant
function getProductDetails($productId, $restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT p.*, c.nom as nom_categorie, c.id_categorie 
                               FROM Produit p 
                               JOIN Categorie c ON p.id_categorie = c.id_categorie 
                               WHERE p.id_produit = :productId 
                               AND p.id_restaurant = :restaurantId");
        $stmt->bindParam(':productId', $productId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
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

$product = getProductDetails($productId, $restaurantId);
$categories = getCategories();

// Rediriger si le produit n'existe pas ou n'appartient pas au restaurant
if (!$product) {
    header('Location: dashboard.php#products');
    exit;
}

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $nom = trim($_POST['nom']);
    $prix = floatval($_POST['prix']);
    $categorie = intval($_POST['categorie']);
    $description = trim($_POST['description']);
    
    // Validation basique
    if (empty($nom) || $prix <= 0 || empty($description) || $categorie <= 0) {
        $productError = 'Veuillez remplir tous les champs correctement.';
    } else {
        try {
            $conn = getDbConnection();
            $image_url = $product['image_url']; // Valeur par défaut (ancienne image)
            
            // Gestion de l'upload d'image
            if (isset($_FILES['product_image']) && $_FILES['product_image']['size'] > 0) {
                $image = $_FILES['product_image'];
                $image_name = $image['name'];
                $image_tmp = $image['tmp_name'];
                $image_size = $image['size'];
                $image_error = $image['error'];
                
                // Vérifier l'extension du fichier
                $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($image_ext, $allowed_extensions)) {
                    // Vérifier la taille du fichier (max 20MB)
                    if ($image_size <= 20971520) {
                        // Créer le dossier d'uploads s'il n'existe pas
                        $upload_dir = '../../uploads/products/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Générer un nom de fichier unique
                        $new_image_name = uniqid('product_') . '.' . $image_ext;
                        $image_path = $upload_dir . $new_image_name;
                        
                        // Déplacer le fichier uploadé
                        if (move_uploaded_file($image_tmp, $image_path)) {
                            $image_url = '/uploads/products/' . $new_image_name;
                            
                            // Supprimer l'ancienne image si elle existe
                            if (!empty($product['image_url']) && file_exists('../../' . ltrim($product['image_url'], '/'))) {
                                @unlink('../../' . ltrim($product['image_url'], '/'));
                            }
                        } else {
                            $productError = 'Erreur lors de l\'upload de l\'image.';
                        }
                    } else {
                        $productError = 'L\'image est trop volumineuse (max 20MB).';
                    }
                } else {
                    $productError = 'Extension de fichier non autorisée.';
                }
            }
            
            if (empty($productError)) {
                // Mettre à jour le produit dans la base de données
                $stmt = $conn->prepare("UPDATE Produit 
                                      SET nom = :nom, 
                                          description = :description, 
                                          prix = :prix, 
                                          id_categorie = :categorieId, 
                                          image_url = :image_url 
                                      WHERE id_produit = :productId 
                                      AND id_restaurant = :restaurantId");
                
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':prix', $prix);
                $stmt->bindParam(':categorieId', $categorie);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':productId', $productId);
                $stmt->bindParam(':restaurantId', $restaurantId);
                
                if ($stmt->execute()) {
                    $productSuccess = 'Produit mis à jour avec succès.';
                    
                    // Rafraîchir les données du produit
                    $product = getProductDetails($productId, $restaurantId);
                } else {
                    $productError = 'Erreur lors de la mise à jour du produit.';
                }
            }
        } catch (PDOException $e) {
            $productError = 'Erreur lors de la mise à jour: ' . $e->getMessage();
        }
    }
}
?>

<div class="restaurant-dashboard-container">
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Espace Restaurant</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu</h3>
            <ul class="menu-items">
                <li>
                    <a href="dashboard.php#dashboard">
                        <i class="fas fa-home"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#orders">
                        <i class="fas fa-list"></i>
                        <span>Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#products" class="active">
                        <i class="fas fa-utensils"></i>
                        <span>Mes produits</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#add-product">
                        <i class="fas fa-plus-circle"></i>
                        <span>Ajouter un produit</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#profile">
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
        <div class="content-header">
            <h1>Modifier un produit</h1>
            <p>Mettez à jour les informations du produit.</p>
        </div>
        
        <div class="form-card">
            <div class="product-edit-header">
                <h2>Informations du produit</h2>
                <a href="dashboard.php#products" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour aux produits
                </a>
            </div>
            
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
            
            <form method="POST" action="edit_product.php?id=<?php echo $productId; ?>" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Nom du produit :</label>
                        <input type="text" name="nom" id="nom" class="form-control" value="<?php echo htmlspecialchars($product['nom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prix">Prix (€) :</label>
                        <input type="number" name="prix" id="prix" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($product['prix']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categorie">Catégorie :</label>
                        <select name="categorie" id="categorie" class="form-control" required>
                            <option value="">Sélectionnez une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id_categorie']; ?>" <?php echo ($category['id_categorie'] == $product['id_categorie']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['nom_categorie']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description :</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Image actuelle :</label>
                        <div class="current-image-container">
                            <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="current-product-image">
                            <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="fas fa-image"></i>
                                <p>Aucune image</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image">Nouvelle image (optionnel) :</label>
                        <input type="file" name="product_image" id="product_image" class="form-control" accept="image/jpeg, image/png, image/gif">
                        <small class="form-text text-muted">Formats acceptés: JPEG, PNG, GIF. Taille max: 20MB</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_product" class="btn btn-primary">Mettre à jour le produit</button>
                    <a href="dashboard.php#products" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Style général de la page */
.restaurant-dashboard-container {
    background-color: #f9f9f9;
    min-height: 100vh;
}

.dashboard-content {
    padding: 30px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* En-tête de page */
.content-header {
    margin-bottom: 30px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
}

.content-header h1 {
    font-size: 28px;
    color: #333;
    margin-bottom: 8px;
}

.content-header p {
    color: #777;
    font-size: 16px;
}

/* Carte du formulaire */
.form-card {
    background-color: #fff;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.product-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.product-edit-header h2 {
    font-size: 22px;
    color: #444;
    font-weight: 600;
}

/* Grille du formulaire */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

/* Groupes de formulaire */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #f8c24a;
    box-shadow: 0 0 0 3px rgba(248, 194, 74, 0.25);
    outline: none;
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 40px;
}

/* Conteneur d'image */
.current-image-container {
    max-width: 300px;
    margin-bottom: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 10px;
    background-color: #f9f9f9;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.current-image-container:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    border-color: #f8c24a;
}

.current-product-image {
    width: 100%;
    height: 220px;
    border-radius: 6px;
    display: block;
    object-fit: cover;
    object-position: center;
}

.no-image-placeholder {
    width: 100%;
    height: 220px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background-color: #f0f0f0;
    color: #aaa;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.no-image-placeholder:hover {
    background-color: #e5e5e5;
}

.no-image-placeholder i {
    font-size: 54px;
    margin-bottom: 12px;
    color: #999;
}

.no-image-placeholder p {
    font-size: 16px;
    margin: 0;
}

/* Input file */
input[type="file"].form-control {
    padding: 10px;
    background-color: #f9f9f9;
}

.form-text {
    display: block;
    margin-top: 5px;
    color: #777;
    font-size: 13px;
}

/* Actions de formulaire */
.form-actions {
    display: flex;
    justify-content: flex-start;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    line-height: 1.5;
}

.btn-primary {
    background-color: #f8c24a;
    color: #fff;
}

.btn-primary:hover {
    background-color: #f0b73d;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(248, 194, 74, 0.3);
}

.btn-secondary {
    background-color: #e2e2e2;
    color: #555;
}

.btn-secondary:hover {
    background-color: #d5d5d5;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.btn i {
    margin-right: 8px;
}

/* Alertes */
.alert {
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 25px;
    font-size: 15px;
    display: flex;
    align-items: center;
}

.alert-success {
    background-color: #e7f7ed;
    color: #28a745;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: #f9e7e7;
    color: #dc3545;
    border-left: 4px solid #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-content {
        padding: 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-actions {
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
</body>
</html> 