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

<div class="container">
    <section class="restaurant-header">
        <h1>Restaurants disponibles</h1>
        <p>Choisissez parmi nos restaurants partenaires pour commander vos plats préférés.</p>
        
        <div class="search-container">
            <input type="text" id="restaurant-search" class="form-control" placeholder="Rechercher un restaurant...">
        </div>
    </section>
    
    <section class="categories-filter">
        <h2>Catégories</h2>
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
    
    <section class="restaurants-list">
        <h2>Résultats</h2>
        
        <?php if (count($restaurants) === 0): ?>
        <div class="no-results">
            <p>Aucun restaurant trouvé. Veuillez essayer une autre catégorie.</p>
        </div>
        <?php else: ?>
        
        <div class="card-container" id="restaurants-container">
            <?php foreach ($restaurants as $restaurant): ?>
            <div class="card restaurant-card" data-name="<?php echo strtolower(htmlspecialchars($restaurant['nom_r'])); ?>">
                <div class="card-image">
                    <img src="/assets/images/restaurant.png" alt="<?php echo htmlspecialchars($restaurant['nom_r']); ?>">
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($restaurant['nom_r']); ?></h3>
                    <p class="card-text"><?php echo htmlspecialchars($restaurant['adresse_r']); ?></p>
                    <p class="card-text"><small><?php echo $restaurant['product_count']; ?> produits disponibles</small></p>
                </div>
                <div class="card-footer">
                    <a href="restaurant_details.php?id=<?php echo $restaurant['id_restaurant']; ?>" class="btn btn-primary">Voir le menu</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </section>
</div>

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
                    noResults.textContent = 'Aucun restaurant trouvé pour "' + searchTerm + '".';
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