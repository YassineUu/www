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
    <section class="restaurant-details">
        <div class="restaurant-header">
            <div class="restaurant-info">
                <h1><?php echo htmlspecialchars($restaurant['nom_r']); ?></h1>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($restaurant['adresse_r']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($restaurant['contact']); ?></p>
                <?php if (!empty($restaurant['description'])): ?>
                <p class="restaurant-description"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                <?php endif; ?>
            </div>
            <div class="restaurant-image">
                <?php if (!empty($restaurant['image'])): ?>
                <img src="<?php echo htmlspecialchars($restaurant['image']); ?>" alt="<?php echo htmlspecialchars($restaurant['nom_r']); ?>">
                <?php else: ?>
                <img src="/assets/images/restaurant.png" alt="<?php echo htmlspecialchars($restaurant['nom_r']); ?>">
                <?php endif; ?>
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
                <?php foreach ($categoryProducts as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>">
                        <?php else: ?>
                        <img src="/assets/images/product_default.jpg" alt="<?php echo htmlspecialchars($product['nom']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <h4 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h4>
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="product-price"><?php echo number_format($product['prix'], 2); ?> €</div>
                    </div>
                    <div class="product-actions">
                        <button class="btn-add-to-cart" 
                                data-product-id="<?php echo $product['id_produit']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['nom']); ?>"
                                data-product-price="<?php echo $product['prix']; ?>">
                            <i class="fas fa-cart-plus"></i> Ajouter
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
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Restaurant Details */
.restaurant-details {
    margin-bottom: 30px;
}

.restaurant-header {
    display: flex;
    align-items: center;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.restaurant-info {
    flex: 1;
    padding: 25px;
}

.restaurant-info h1 {
    margin-top: 0;
    color: var(--text-color);
    font-size: 2rem;
    margin-bottom: 15px;
}

.restaurant-info p {
    margin: 8px 0;
    color: #666;
}

.restaurant-info i {
    color: var(--primary-color);
    margin-right: 8px;
}

.restaurant-description {
    margin-top: 15px !important;
    font-style: italic;
    color: #777;
    line-height: 1.5;
}

.restaurant-image {
    width: 300px;
    height: 200px;
    overflow: hidden;
}

.restaurant-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Menu Categories */
.menu-categories {
    margin-bottom: 30px;
}

.menu-categories h2 {
    color: var(--text-color);
    font-size: 1.8rem;
    margin-bottom: 20px;
}

.category-tabs {
    display: flex;
    overflow-x: auto;
    gap: 10px;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.category-tab {
    flex: 0 0 auto;
    padding: 10px 20px;
    background-color: white;
    border-radius: 20px;
    text-decoration: none;
    color: var(--text-color);
    transition: all 0.3s ease;
    border: 1px solid var(--medium-gray);
    white-space: nowrap;
}

.category-tab:hover {
    background-color: var(--light-gray);
}

.category-tab.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Product List */
.products-list {
    margin-bottom: 40px;
}

.category-section {
    margin-bottom: 30px;
}

.category-section h3 {
    color: var(--text-color);
    font-size: 1.5rem;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--primary-color);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-card {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.product-image {
    height: 180px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.product-content {
    padding: 15px;
}

.product-title {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.2rem;
    color: var(--text-color);
}

.product-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
    line-height: 1.4;
    height: 60px;
    overflow: hidden;
}

.product-price {
    font-weight: bold;
    color: var(--accent-color);
    font-size: 1.2rem;
    margin: 10px 0;
}

.product-actions {
    padding: 0 15px 15px;
    display: flex;
    justify-content: flex-end;
}

.btn-add-to-cart {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.btn-add-to-cart i {
    margin-right: 5px;
}

.btn-add-to-cart:hover {
    background-color: #3d8c40;
}

.no-results {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    color: #666;
}

.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    transform: translateY(100px);
    transition: transform 0.3s ease;
    z-index: 1000;
}

.notification.show {
    transform: translateY(0);
}

.notification-content {
    background-color: var(--primary-color);
    color: white;
    padding: 12px 20px;
    border-radius: 5px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    min-width: 250px;
}

.cart-notification .notification-content {
    flex-direction: column;
    align-items: flex-start;
    padding: 15px 20px;
}

.notification-actions {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-top: 12px;
}

.btn-notification {
    background-color: white;
    color: var(--primary-color);
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-notification-secondary {
    background-color: transparent;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.5);
    cursor: pointer;
    font-size: 0.9rem;
}

.notification-error {
    background-color: #e74c3c;
}

.notification-content i {
    margin-right: 10px;
    font-size: 1.2em;
}

.notification-content a {
    color: white;
    text-decoration: underline;
    font-weight: bold;
    margin-left: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    
    // Initialiser les boutons d'ajout au panier
    const addToCartButtons = document.querySelectorAll('.btn-add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Vérifier si l'utilisateur est connecté
            if (!isLoggedIn) {
                showLoginRequiredNotification();
                return;
            }
            
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            const restaurantId = <?php echo $restaurantId; ?>;
            
            // Récupérer le panier existant ou créer un nouveau
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Vérifier si le panier contient déjà des produits d'un autre restaurant
            if (cart.length > 0 && cart[0].restaurant_id !== restaurantId) {
                showNotification('Vous ne pouvez pas commander des produits de différents restaurants en même temps. Veuillez vider votre panier ou terminer votre commande actuelle.', true);
                return;
            }
            
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
                    quantity: 1,
                    restaurant_id: restaurantId,
                    restaurant_name: <?php echo json_encode($restaurant['nom_r']); ?>
                });
            }
            
            // Sauvegarder le panier
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Afficher une notification plus élégante avec options
            showCartNotification(productName);
        });
    });
    
    // Fonction pour afficher une notification avec options pour le panier
    function showCartNotification(productName) {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = 'notification cart-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-check-circle"></i>
                <span><strong>${productName}</strong> a été ajouté au panier !</span>
                <div class="notification-actions">
                    <button class="btn-notification-secondary">Continuer mes achats</button>
                </div>
            </div>
        `;
        
        // Ajouter la notification au DOM
        document.body.appendChild(notification);
        
        // Afficher la notification avec animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Ajouter un événement au bouton "Continuer mes achats"
        const continueButton = notification.querySelector('.btn-notification-secondary');
        continueButton.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        });
        
        // Supprimer la notification après un délai
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
    
    // Fonction pour afficher une notification
    function showNotification(message, isError = false) {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.innerHTML = `
            <div class="notification-content ${isError ? 'notification-error' : ''}">
                <i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Ajouter la notification au DOM
        document.body.appendChild(notification);
        
        // Afficher la notification avec animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Supprimer la notification après un délai
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Fonction pour afficher une notification de connexion requise
    function showLoginRequiredNotification() {
        showNotification('Vous devez être connecté pour ajouter des produits au panier. <a href="/pages/auth/login.php">Se connecter</a>', true);
    }
});
</script>

<?php
include_once '../../includes/footer.php';
?> 