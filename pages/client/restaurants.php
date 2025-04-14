<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Récupérer les catégories de restaurants
function getCategories() {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM Categorie");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer les restaurants
function getRestaurants($categoryId = null) {
    try {
        $conn = getDbConnection();
        
        $query = "SELECT r.*, COUNT(p.id_produit) AS product_count 
                 FROM Restaurant r 
                 LEFT JOIN Produit p ON r.id_restaurant = p.id_restaurant";
        
        $params = [];
        
        if ($categoryId) {
            $query .= " LEFT JOIN Produit p2 ON r.id_restaurant = p2.id_restaurant 
                        WHERE p2.id_categorie = :categoryId";
            $params[':categoryId'] = $categoryId;
        }
        
        $query .= " GROUP BY r.id_restaurant";
        
        $stmt = $conn->prepare($query);
        
        if ($categoryId) {
            $stmt->bindParam(':categoryId', $categoryId);
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Récupérer la catégorie sélectionnée depuis l'URL
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Récupérer les restaurants selon la catégorie sélectionnée
$restaurants = getRestaurants($selectedCategory);

// Récupérer toutes les catégories
$categories = getCategories();
?>

<div class="page-restaurants">
    <div class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h1>Restaurants disponibles</h1>
                <p class="subtitle">Choisissez parmi nos restaurants partenaires pour commander vos plats préférés.</p>
                
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="restaurant-search" class="search-input" placeholder="Rechercher un restaurant...">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container main-content">
        <section class="categories-section">
            <h2 class="section-title">Catégories</h2>
            <div class="category-container">
                <a href="restaurants.php" class="category-item <?php echo !$selectedCategory ? 'active' : ''; ?>">
                    <div class="category-icon">
                        <img src="/assets/images/fastfood.png" alt="Toutes les catégories">
                    </div>
                    <h3>Tous</h3>
                </a>
                
                <?php foreach ($categories as $category): ?>
                <a href="restaurants.php?category=<?php echo $category['id_categorie']; ?>" class="category-item <?php echo $selectedCategory == $category['id_categorie'] ? 'active' : ''; ?>">
                    <div class="category-icon">
                        <?php 
                        // Utiliser le nom comme identifiant pour l'image
                        $imageName = strtolower(str_replace(' ', '', $category['nom']));
                        $imagePath = "/assets/images/{$imageName}.png";
                        ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($category['nom']); ?>">
                    </div>
                    <h3><?php echo htmlspecialchars($category['nom']); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section class="restaurants-section">
            <h2 class="section-title">Résultats</h2>
            
            <?php if (count($restaurants) === 0): ?>
            <div class="no-results">
                <div class="no-results-icon"><i class="fas fa-utensils"></i></div>
                <h3>Aucun restaurant trouvé</h3>
                <p>Veuillez essayer une autre catégorie.</p>
            </div>
            <?php else: ?>
            
            <div class="restaurant-grid" id="restaurants-container">
                <?php foreach ($restaurants as $restaurant): ?>
                <div class="restaurant-card" data-name="<?php echo strtolower(htmlspecialchars($restaurant['nom_r'])); ?>">
                    <div class="restaurant-image">
                        <img src="/assets/images/restaurant.png" alt="<?php echo htmlspecialchars($restaurant['nom_r']); ?>">
                    </div>
                    <div class="restaurant-details">
                        <h3 class="restaurant-name"><?php echo htmlspecialchars($restaurant['nom_r']); ?></h3>
                        <div class="restaurant-info">
                            <p class="restaurant-address"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($restaurant['adresse_r']); ?></p>
                        </div>
                        <a href="restaurant_details.php?id=<?php echo $restaurant['id_restaurant']; ?>" class="btn-view-menu">Voir le menu</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </section>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?> 