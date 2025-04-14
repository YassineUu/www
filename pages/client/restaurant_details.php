<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si un ID de restaurant est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: restaurants.php');
    exit;
}

$restaurantId = (int)$_GET['id'];

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client';

// Récupérer les informations du restaurant
function getRestaurantDetails($id) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Restaurant WHERE id_restaurant = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Récupérer les catégories de produits pour ce restaurant
function getProductCategories($restaurantId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT DISTINCT c.* 
                               FROM Categorie c 
                               JOIN Produit p ON c.id_categorie = p.id_categorie 
                               WHERE p.id_restaurant = :restaurantId
                               ORDER BY c.nom_categorie");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les produits d'un restaurant par catégorie
function getProductsByCategory($restaurantId, $categoryId = null) {
    try {
        $conn = getDbConnection();
        
        $query = "SELECT p.*, c.nom as nom_categorie 
                 FROM Produit p 
                 JOIN Categorie c ON p.id_categorie = c.id_categorie 
                 WHERE p.id_restaurant = :restaurantId";
        
        if ($categoryId !== null) {
            $query .= " AND p.id_categorie = :categoryId";
        }
        
        $query .= " ORDER BY c.nom, p.nom";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':restaurantId', $restaurantId);
        
        if ($categoryId !== null) {
            $stmt->bindParam(':categoryId', $categoryId);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer le restaurant
$restaurant = getRestaurantDetails($restaurantId);

if (!$restaurant) {
    header('Location: restaurants.php');
    exit;
}

// Récupérer la catégorie sélectionnée depuis l'URL
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Récupérer les produits
$products = getProductsByCategory($restaurantId, $selectedCategory);

// Récupérer les catégories
$categories = getProductCategories($restaurantId);
?>

<div class="container">
    <div class="restaurant-header">
        <div class="restaurant-info">
            <h1 class="restaurant-name"><?= $restaurant['nom_r'] ?? 'Restaurant' ?></h1>
            <p><i class="fa fa-map-marker-alt"></i> <?= $restaurant['adresse_r'] ?? 'Adresse non disponible' ?></p>
            <p><i class="fa fa-phone"></i> <?= $restaurant['telephone'] ?? 'Téléphone non disponible' ?></p>
            <p class="restaurant-description"><?= $restaurant['description'] ?? '' ?></p>
        </div>
        <div class="restaurant-image">
            <img src="<?= !empty($restaurant['image_url']) ? $restaurant['image_url'] : '/assets/images/restaurant.png' ?>" alt="<?= $restaurant['nom_r'] ?? 'Restaurant' ?>">
        </div>
    </div>
    
    <section class="menu-categories">
        <h2>Menu</h2>
        
        <div class="category-tabs">
            <a href="restaurant_details.php?id=<?php echo $restaurantId; ?>" class="category-tab <?php echo !$selectedCategory ? 'active' : ''; ?>">
                Tous
            </a>
            
            <?php foreach ($categories as $category): ?>
            <a href="restaurant_details.php?id=<?php echo $restaurantId; ?>&category=<?php echo $category['id_categorie']; ?>" class="category-tab <?php echo $selectedCategory == $category['id_categorie'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['nom']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <section class="products-list">
        <?php if (count($products) === 0): ?>
        <div class="no-results">
            <p>Aucun produit trouvé. Veuillez essayer une autre catégorie.</p>
        </div>
        <?php else: ?>
        
        <?php 
        // Regrouper les produits par catégorie
        $productsByCategory = [];
        foreach ($products as $product) {
            $categoryName = $product['nom_categorie'];
            if (!isset($productsByCategory[$categoryName])) {
                $productsByCategory[$categoryName] = [];
            }
            $productsByCategory[$categoryName][] = $product;
        }
        ?>
        
        <?php foreach ($productsByCategory as $categoryName => $categoryProducts): ?>
        <div class="category-section">
            <h3><?php echo htmlspecialchars($categoryName); ?></h3>
            
            <div class="product-grid">
                <?php $i = 0; ?>
                <?php foreach ($categoryProducts as $product): ?>
                <?php $i++; ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($product['id_categorie']); ?>" style="--animation-order: <?php echo $i; ?>">
                    <div class="product-image">
                        <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" loading="lazy">
                        <?php else: ?>
                        <img src="/assets/images/produit.png" alt="<?php echo htmlspecialchars($product['nom']); ?>" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['nom']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="product-footer">
                            <span class="product-price"><?php echo number_format($product['prix'], 2); ?> €</span>
                            <button class="add-to-cart-btn" data-id="<?php echo $product['id_produit']; ?>" data-name="<?php echo htmlspecialchars($product['nom']); ?>" data-price="<?php echo $product['prix']; ?>">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </section>
</div>

<!-- Variable pour le JavaScript -->
<script>
    // Définir l'état de connexion pour le JavaScript
    window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>

<!-- Inclure le script JavaScript spécifique pour les détails du restaurant -->
<script src="/assets/js/restaurant.js" defer></script>

<?php
include_once '../../includes/footer.php';
?> 