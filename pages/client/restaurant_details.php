<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si un ID de restaurant est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: restaurants.php');
    exit;
}

$restaurantId = (int)$_GET['id'];

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
        
        $query = "SELECT p.*, c.nom_categorie 
                 FROM Produit p 
                 JOIN Categorie c ON p.id_categorie = c.id_categorie 
                 WHERE p.id_restaurant = :restaurantId";
        
        if ($categoryId !== null) {
            $query .= " AND p.id_categorie = :categoryId";
        }
        
        $query .= " ORDER BY c.nom_categorie, p.nom_p";
        
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
    <section class="restaurant-details">
        <div class="restaurant-header">
            <div class="restaurant-info">
                <h1><?php echo htmlspecialchars($restaurant['nom_r']); ?></h1>
                <p><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($restaurant['adresse_r']); ?></p>
                <p><i class="fa fa-phone"></i> <?php echo htmlspecialchars($restaurant['contact']); ?></p>
            </div>
            <div class="restaurant-image">
                <img src="/assets/img/restaurant_header.jpg" alt="<?php echo htmlspecialchars($restaurant['nom_r']); ?>">
            </div>
        </div>
    </section>
    
    <section class="menu-categories">
        <h2>Menu</h2>
        
        <div class="category-tabs">
            <a href="restaurant_details.php?id=<?php echo $restaurantId; ?>" class="category-tab <?php echo !$selectedCategory ? 'active' : ''; ?>">
                Tous
            </a>
            
            <?php foreach ($categories as $category): ?>
            <a href="restaurant_details.php?id=<?php echo $restaurantId; ?>&category=<?php echo $category['id_categorie']; ?>" class="category-tab <?php echo $selectedCategory == $category['id_categorie'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['nom_categorie']); ?>
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
            
            <div class="card-container">
                <?php foreach ($categoryProducts as $product): ?>
                <div class="card product-card">
                    <div class="card-image">
                        <img src="/assets/img/product_default.jpg" alt="<?php echo htmlspecialchars($product['nom_p']); ?>">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($product['nom_p']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($product['description_c']); ?></p>
                    </div>
                    <div class="card-footer">
                        <div class="card-price"><?php echo number_format($product['prix'], 2); ?> €</div>
                        <button class="btn btn-primary add-to-cart" 
                                data-product-id="<?php echo $product['id_produit']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['nom_p']); ?>"
                                data-product-price="<?php echo $product['prix']; ?>">
                            Ajouter au panier
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les boutons d'ajout au panier
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            
            // Récupérer le panier existant ou créer un nouveau
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Vérifier si le produit est déjà dans le panier
            const existingProductIndex = cart.findIndex(item => item.id === productId);
            
            if (existingProductIndex !== -1) {
                // Si oui, incrémenter la quantité
                cart[existingProductIndex].quantity += 1;
            } else {
                // Sinon, ajouter le produit
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: 1
                });
            }
            
            // Sauvegarder le panier
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Afficher une notification
            alert('Produit ajouté au panier !');
        });
    });
});
</script>

<?php
include_once '../../includes/footer.php';
?> 