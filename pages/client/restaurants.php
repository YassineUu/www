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
                        <div class="restaurant-badge"><?php echo $restaurant['product_count']; ?> produits</div>
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

<style>
:root {
    --primary-color: #4CAF50;
    --secondary-color: #f8d24b;
    --accent-color: #FF5722;
    --text-color: #333333;
    --light-gray: #f5f5f5;
    --medium-gray: #e0e0e0;
    --shadow: 0 2px 8px rgba(0,0,0,0.1);
    --border-radius: 8px;
    --transition: all 0.3s ease;
}

.page-restaurants {
    background-color: #f9f9f9;
}

/* Hero Section */
.hero-banner {
    background: var(--secondary-color);
    color: #333;
    padding: 60px 0 70px;
    margin-bottom: 30px;
    position: relative;
}

.hero-banner::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 30px;
    background: linear-gradient(135deg, transparent 50%, #f9f9f9 50%);
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.subtitle {
    font-size: 1.1rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.search-container {
    max-width: 600px;
    margin: 0 auto;
}

.search-box {
    display: flex;
    align-items: center;
    background-color: white;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 8px 20px;
}

.search-icon {
    color: #999;
    margin-right: 10px;
    font-size: 18px;
}

.search-input {
    flex: 1;
    border: none;
    padding: 12px 0;
    font-size: 16px;
    outline: none;
}

/* Main Content */
.main-content {
    padding: 20px 0 60px;
}

.section-title {
    font-size: 1.6rem;
    margin-bottom: 25px;
    color: var(--text-color);
    position: relative;
    padding-bottom: 10px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background-color: var(--primary-color);
}

/* Categories */
.categories-section {
    margin-bottom: 40px;
}

.category-container {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding: 10px 0;
    scrollbar-width: thin;
}

.category-container::-webkit-scrollbar {
    height: 5px;
}

.category-container::-webkit-scrollbar-track {
    background: var(--light-gray);
    border-radius: 10px;
}

.category-container::-webkit-scrollbar-thumb {
    background: var(--medium-gray);
    border-radius: 10px;
}

.category-item {
    flex: 0 0 auto;
    text-align: center;
    text-decoration: none;
    color: var(--text-color);
    transition: var(--transition);
    padding: 10px;
    border-radius: var(--border-radius);
}

.category-item:hover {
    transform: translateY(-5px);
}

.category-icon {
    width: 80px;
    height: 80px;
    background-color: white;
    border-radius: 50%;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.category-item.active .category-icon {
    background-color: var(--secondary-color);
    transform: scale(1.05);
}

.category-icon img {
    width: 60%;
    height: auto;
}

.category-item h3 {
    font-size: 14px;
    margin: 0;
    font-weight: 500;
}

/* Restaurant Cards */
.restaurants-section {
    margin-bottom: 40px;
}

.restaurant-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.restaurant-card {
    background-color: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.restaurant-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.restaurant-image {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.restaurant-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.restaurant-card:hover .restaurant-image img {
    transform: scale(1.05);
}

.restaurant-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: var(--primary-color);
    color: white;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
}

.restaurant-details {
    padding: 20px;
}

.restaurant-name {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: var(--text-color);
}

.restaurant-info {
    margin-bottom: 15px;
}

.restaurant-address {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.restaurant-address i {
    margin-right: 5px;
    color: var(--primary-color);
}

.btn-view-menu {
    display: inline-block;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    text-align: center;
    width: 100%;
}

.btn-view-menu:hover {
    background-color: #3d8c40;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 50px 20px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.no-results-icon {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 20px;
    opacity: 0.7;
}

.no-results h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--text-color);
}

.no-results p {
    color: #666;
}

.no-search-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 30px;
    color: #666;
    background-color: var(--light-gray);
    border-radius: var(--border-radius);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .category-icon {
        width: 70px;
        height: 70px;
    }
    
    .restaurant-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('restaurant-search');
    const restaurantsContainer = document.getElementById('restaurants-container');
    const restaurantCards = document.querySelectorAll('.restaurant-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            // Filtrer les restaurants par nom
            restaurantCards.forEach(card => {
                const restaurantName = card.getAttribute('data-name');
                
                if (restaurantName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Vérifier s'il y a des résultats visibles
            const visibleCards = restaurantsContainer.querySelectorAll('.restaurant-card[style="display: block"]');
            
            if (visibleCards.length === 0 && searchTerm !== '') {
                // Créer un message "Aucun résultat" s'il n'existe pas déjà
                if (!document.querySelector('.no-search-results')) {
                    const noResults = document.createElement('div');
                    noResults.className = 'no-search-results';
                    noResults.innerHTML = '<i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px;"></i><p>Aucun restaurant trouvé pour "<strong>' + searchTerm + '</strong>".</p>';
                    restaurantsContainer.appendChild(noResults);
                }
            } else {
                // Supprimer le message s'il existe
                const noResults = document.querySelector('.no-search-results');
                if (noResults) {
                    noResults.remove();
                }
            }
        });
    }
});
</script>

<?php
include_once '../../includes/footer.php';
?> 