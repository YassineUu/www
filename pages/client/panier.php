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
        try {
            $conn = getDbConnection();
            $conn->beginTransaction();
            
            // Créer une nouvelle commande
            $stmt = $conn->prepare("INSERT INTO Commande (id_client, date, statut) VALUES (:clientId, NOW(), 'en attente')");
            $stmt->bindParam(':clientId', $_SESSION['user_id']);
            $stmt->execute();
            
            $commandeId = $conn->lastInsertId();
            
            $totalAmount = 0;
            
            // Ajouter les produits à la commande
            foreach ($cartItems as $item) {
                $productId = $item['id'];
                $quantity = $item['quantity'];
                
                // Récupérer les détails du produit
                $product = getProductDetails($productId);
                
                if ($product) {
                    $itemTotal = $product['prix'] * $quantity;
                    $totalAmount += $itemTotal;
                    
                    // Ajouter le produit à la relation Contient
                    $stmt = $conn->prepare("INSERT INTO Contient (id_commande, id_produit, qte_produit) VALUES (:commandeId, :productId, :quantity)");
                    $stmt->bindParam(':commandeId', $commandeId);
                    $stmt->bindParam(':productId', $productId);
                    $stmt->bindParam(':quantity', $quantity);
                    $stmt->execute();
                }
            }
            
            // Créer un paiement pour la commande
            $stmt = $conn->prepare("INSERT INTO Paiement (id_commande, montant, mode) VALUES (:commandeId, :montant, :mode)");
            $stmt->bindParam(':commandeId', $commandeId);
            $stmt->bindParam(':montant', $totalAmount);
            $mode = 'Carte bancaire'; // Par défaut, à modifier selon les besoins
            $stmt->bindParam(':mode', $mode);
            $stmt->execute();
            
            $conn->commit();
            
            // Rediriger vers la page de confirmation
            header('Location: confirmation.php?id=' . $commandeId);
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Erreur lors de la création de la commande: ' . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h1>Votre panier</h1>
    
    <div id="cart-content">
        <!-- Le contenu du panier sera chargé dynamiquement via JavaScript -->
        <p>Chargement du panier...</p>
    </div>
    
    <div id="cart-empty" style="display: none;">
        <p>Votre panier est vide.</p>
        <a href="restaurants.php" class="btn btn-primary">Découvrir les restaurants</a>
    </div>
    
    <div id="cart-items" style="display: none;">
        <div class="cart-list">
            <!-- Les articles du panier seront affichés ici -->
        </div>
        
        <div class="cart-total">
            <h3>Total: <span class="cart-total-amount">0.00 €</span></h3>
        </div>
        
        <div class="cart-actions">
            <?php if ($isLoggedIn): ?>
            <form method="POST" action="">
                <input type="hidden" name="cart_items" id="cart-items-input">
                <button type="submit" name="place_order" class="btn btn-primary">Commander</button>
            </form>
            <?php else: ?>
            <p>Veuillez vous <a href="/pages/auth/login.php">connecter</a> pour finaliser votre commande.</p>
            <?php endif; ?>
            
            <button id="clear-cart" class="btn btn-secondary">Vider le panier</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                        <p>Prix unitaire: ${parseFloat(item.price).toFixed(2)} €</p>
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
                        <button class="remove-item">Supprimer</button>
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