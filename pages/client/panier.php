<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté en tant que client
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client';

// Récupérer les détails d'un produit depuis la base de données
function getProductDetails($productId) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT p.*, r.nom_r as restaurant_name 
                               FROM Produit p 
                               JOIN Restaurant r ON p.id_restaurant = r.id_restaurant 
                               WHERE p.id_produit = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Traiter la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && $isLoggedIn) {
    $cartItems = json_decode($_POST['cart_items'], true);
    
    if (!empty($cartItems)) {
        // Stocker les informations du panier en session pour la page de paiement
        $_SESSION['cart_items'] = $cartItems;
        $_SESSION['restaurant_id'] = $cartItems[0]['restaurant_id'];
        
        // Calculer le montant total
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }
        $_SESSION['cart_total'] = $totalAmount;
        
        // Rediriger vers la page de paiement
        header('Location: paiement.php');
        exit;
    }
}
?>

<div class="container">
    <div class="cart-container">
        <div class="cart-header">
            <h1>Votre panier</h1>
            <p class="cart-subtitle">Consultez et modifiez les articles de votre panier avant de passer commande</p>
        </div>
        
        <div id="cart-content" class="loading-message">
            <div class="loading-spinner"></div>
            <p>Chargement du panier...</p>
        </div>
        
        <div id="cart-empty" class="empty-cart" style="display: none;">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2>Votre panier est vide</h2>
            <p>Ajoutez des produits à votre panier pour passer commande</p>
            <a href="restaurants.php" class="btn btn-primary">Découvrir les restaurants</a>
        </div>
        
        <div id="cart-items" style="display: none;">
            <div class="cart-list-header">
                <div class="cart-header-product">Produit</div>
                <div class="cart-header-price">Prix unitaire</div>
                <div class="cart-header-quantity">Quantité</div>
                <div class="cart-header-total">Total</div>
                <div class="cart-header-actions">Actions</div>
            </div>
            
            <div class="cart-list">
                <!-- Les articles du panier seront affichés ici -->
            </div>
            
            <div class="cart-summary">
                <div class="cart-total">
                    <div class="cart-total-label">Total de la commande</div>
                    <div class="cart-total-amount">0.00 €</div>
                </div>
                
                <div class="cart-actions">
                    <?php if ($isLoggedIn): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="cart_items" id="cart-items-input">
                        <button type="submit" name="place_order" class="btn btn-primary btn-order btn-large">
                            <i class="fas fa-credit-card"></i> Procéder au paiement
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="login-required">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Veuillez vous <a href="/pages/auth/login.php">connecter</a> pour finaliser votre commande.</p>
                    </div>
                    <?php endif; ?>
                    
                    <button id="clear-cart" class="btn btn-secondary">
                        <i class="fas fa-trash"></i> Vider le panier
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4CAF50;
    --secondary-color: #f8d24b;
    --accent-color: #FF5722;
    --text-color: #333333;
    --text-light: #666666;
    --light-gray: #f5f5f5;
    --medium-gray: #e0e0e0;
    --error-color: #e74c3c;
    --shadow: 0 2px 8px rgba(0,0,0,0.1);
    --border-radius: 8px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.cart-container {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 30px;
}

.cart-header {
    padding: 25px;
    border-bottom: 1px solid var(--medium-gray);
    background-color: var(--light-gray);
}

.cart-header h1 {
    margin: 0;
    color: var(--text-color);
    font-size: 1.8rem;
}

.cart-subtitle {
    margin: 5px 0 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Loading state */
.loading-message {
    padding: 50px;
    text-align: center;
    color: var(--text-light);
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--medium-gray);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    margin: 0 auto 15px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Empty cart */
.empty-cart {
    padding: 50px;
    text-align: center;
    color: var(--text-light);
}

.empty-cart-icon {
    font-size: 4rem;
    color: var(--medium-gray);
    margin-bottom: 15px;
}

.empty-cart h2 {
    color: var(--text-color);
    margin-bottom: 10px;
}

.empty-cart p {
    margin-bottom: 20px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background-color: #3d8c40;
}

.btn-secondary {
    background-color: var(--light-gray);
    color: var(--text-color);
    border: 1px solid var(--medium-gray);
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-secondary:hover {
    background-color: var(--medium-gray);
}

/* Cart list */
.cart-list-header {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 1fr;
    padding: 15px 25px;
    background-color: var(--light-gray);
    font-weight: bold;
    color: var(--text-color);
    border-bottom: 1px solid var(--medium-gray);
}

.cart-list {
    padding: 0;
}

.cart-item {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 1fr;
    padding: 15px 25px;
    border-bottom: 1px solid var(--light-gray);
    align-items: center;
}

.cart-item:hover {
    background-color: var(--light-gray);
}

.cart-item-info h3 {
    margin: 0 0 5px;
    font-size: 1rem;
    color: var(--text-color);
}

.cart-item-info p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-light);
}

.cart-item-restaurant {
    font-size: 0.85rem;
    color: var(--primary-color);
    margin-top: 5px;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
}

.quantity-input {
    width: 40px;
    text-align: center;
    border: 1px solid var(--medium-gray);
    border-radius: 4px;
    padding: 5px;
    margin: 0 5px;
}

.quantity-decrease, .quantity-increase {
    width: 28px;
    height: 28px;
    border: 1px solid var(--medium-gray);
    background-color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.quantity-decrease:hover, .quantity-increase:hover {
    background-color: var(--light-gray);
}

.cart-item-total {
    font-weight: 500;
    color: var(--text-color);
}

.remove-item {
    background-color: transparent;
    border: none;
    color: var(--error-color);
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    transition: opacity 0.2s;
}

.remove-item:hover {
    opacity: 0.7;
}

.remove-item i {
    margin-right: 5px;
}

/* Cart summary */
.cart-summary {
    padding: 25px;
    border-top: 1px solid var(--medium-gray);
    background-color: var(--light-gray);
}

.cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--medium-gray);
}

.cart-total-label {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--text-color);
}

.cart-total-amount {
    font-size: 1.4rem;
    font-weight: bold;
    color: var(--accent-color);
}

.cart-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-order {
    padding: 15px 30px;
    font-size: 1.1rem;
}

.btn-large {
    width: 100%;
    margin-bottom: 15px;
    font-size: 1.2rem;
    padding: 15px 30px;
    transition: all 0.3s ease;
}

.btn-large:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-order i {
    margin-right: 8px;
}

.login-required {
    display: flex;
    align-items: center;
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px solid var(--error-color);
    padding: 15px;
    border-radius: var(--border-radius);
    color: var(--error-color);
}

.login-required i {
    font-size: 1.5rem;
    margin-right: 10px;
}

.login-required a {
    color: var(--error-color);
    font-weight: bold;
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
    .cart-list-header {
        display: none;
    }
    
    .cart-item {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 15px;
    }
    
    .cart-item-total, .cart-item-quantity {
        justify-content: flex-start;
    }
    
    .cart-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .login-required {
        flex-direction: column;
        text-align: center;
    }
    
    .login-required i {
        margin-right: 0;
        margin-bottom: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    
    loadCart();
    
    // Charger le panier
    function loadCart() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartContent = document.getElementById('cart-content');
        const cartEmpty = document.getElementById('cart-empty');
        const cartItems = document.getElementById('cart-items');
        const cartList = document.querySelector('.cart-list');
        const cartTotalAmount = document.querySelector('.cart-total-amount');
        const cartItemsInput = document.getElementById('cart-items-input');
        
        // Masquer le message de chargement
        cartContent.style.display = 'none';
        
        if (cart.length === 0) {
            // Afficher le message "panier vide"
            cartEmpty.style.display = 'block';
        } else {
            // Afficher les articles du panier
            cartItems.style.display = 'block';
            
            // Vider la liste des articles
            cartList.innerHTML = '';
            
            // Variables pour calculer le total
            let total = 0;
            
            // Ajouter chaque article à la liste
            cart.forEach((item, index) => {
                const itemTotal = parseFloat(item.price) * item.quantity;
                total += itemTotal;
                
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.setAttribute('data-price', item.price);
                cartItem.setAttribute('data-index', index);
                
                cartItem.innerHTML = `
                    <div class="cart-item-info">
                        <h3>${item.name}</h3>
                        <p class="cart-item-restaurant">Restaurant: ${item.restaurant_name || 'Non spécifié'}</p>
                    </div>
                    <div class="cart-item-price">
                        ${parseFloat(item.price).toFixed(2)} €
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-decrease">-</button>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" readonly>
                        <button class="quantity-increase">+</button>
                    </div>
                    <div class="cart-item-total">
                        ${itemTotal.toFixed(2)} €
                    </div>
                    <div class="cart-item-remove">
                        <button class="remove-item"><i class="fas fa-trash"></i> Supprimer</button>
                    </div>
                `;
                
                cartList.appendChild(cartItem);
            });
            
            // Mettre à jour le total
            cartTotalAmount.textContent = total.toFixed(2) + ' €';
            
            // Mettre à jour l'input caché pour le formulaire
            cartItemsInput.value = JSON.stringify(cart);
            
            // Ajouter les écouteurs d'événements pour les boutons de quantité
            initQuantityControls();
            
            // Ajouter les écouteurs d'événements pour les boutons de suppression
            initRemoveButtons();
        }
        
        // Ajouter l'écouteur d'événement pour le bouton "Vider le panier"
        const clearCartButton = document.getElementById('clear-cart');
        clearCartButton.addEventListener('click', clearCart);
    }
    
    // Initialiser les contrôles de quantité
    function initQuantityControls() {
        const decreaseButtons = document.querySelectorAll('.quantity-decrease');
        const increaseButtons = document.querySelectorAll('.quantity-increase');
        
        decreaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const cartItem = this.closest('.cart-item');
                const index = parseInt(cartItem.getAttribute('data-index'));
                const input = cartItem.querySelector('.quantity-input');
                let quantity = parseInt(input.value);
                
                if (quantity > 1) {
                    quantity--;
                    input.value = quantity;
                    updateCartItem(index, quantity);
                    updateCartItemTotal(cartItem, quantity);
                }
            });
        });
        
        increaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const cartItem = this.closest('.cart-item');
                const index = parseInt(cartItem.getAttribute('data-index'));
                const input = cartItem.querySelector('.quantity-input');
                let quantity = parseInt(input.value);
                
                quantity++;
                input.value = quantity;
                updateCartItem(index, quantity);
                updateCartItemTotal(cartItem, quantity);
            });
        });
    }
    
    // Initialiser les boutons de suppression
    function initRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-item');
        
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const cartItem = this.closest('.cart-item');
                const index = parseInt(cartItem.getAttribute('data-index'));
                
                removeCartItem(index);
            });
        });
    }
    
    // Mettre à jour la quantité d'un article dans le panier
    function updateCartItem(index, quantity) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (index >= 0 && index < cart.length) {
            cart[index].quantity = quantity;
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Mettre à jour l'input caché pour le formulaire
            const cartItemsInput = document.getElementById('cart-items-input');
            cartItemsInput.value = JSON.stringify(cart);
        }
    }
    
    // Mettre à jour le total d'un article du panier
    function updateCartItemTotal(cartItem, quantity) {
        const price = parseFloat(cartItem.getAttribute('data-price'));
        const totalElement = cartItem.querySelector('.cart-item-total');
        
        const itemTotal = price * quantity;
        totalElement.textContent = itemTotal.toFixed(2) + ' €';
        
        // Mettre à jour le total du panier
        updateCartTotal();
    }
    
    // Mettre à jour le total du panier
    function updateCartTotal() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartTotalAmount = document.querySelector('.cart-total-amount');
        
        let total = 0;
        cart.forEach(item => {
            total += parseFloat(item.price) * item.quantity;
        });
        
        cartTotalAmount.textContent = total.toFixed(2) + ' €';
    }
    
    // Supprimer un article du panier
    function removeCartItem(index) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Recharger le panier
            loadCart();
        }
    }
    
    // Vider le panier
    function clearCart() {
        localStorage.removeItem('cart');
        loadCart();
    }
});
</script>

<?php
include_once '../../includes/footer.php';
?> 