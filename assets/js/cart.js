document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté (sera défini dans le HTML)
    // const isLoggedIn = défini via un attribut data ou une variable PHP
    
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
        if (cartContent) cartContent.style.display = 'none';
        
        if (cart.length === 0) {
            // Afficher le message "panier vide"
            if (cartEmpty) cartEmpty.style.display = 'block';
        } else {
            // Afficher les articles du panier
            if (cartItems) cartItems.style.display = 'block';
            
            // Vider la liste des articles
            if (cartList) cartList.innerHTML = '';
            
            // Variables pour calculer le total
            let total = 0;
            
            // Ajouter chaque article à la liste
            cart.forEach((item, index) => {
                const itemTotal = parseFloat(item.price) * item.quantity;
                total += itemTotal;
                
                if (cartList) {
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
                }
            });
            
            // Mettre à jour le total
            if (cartTotalAmount) cartTotalAmount.textContent = total.toFixed(2) + ' €';
            
            // Mettre à jour l'input caché pour le formulaire
            if (cartItemsInput) cartItemsInput.value = JSON.stringify(cart);
            
            // Ajouter les écouteurs d'événements pour les boutons de quantité
            initQuantityControls();
            
            // Ajouter les écouteurs d'événements pour les boutons de suppression
            initRemoveButtons();
        }
        
        // Ajouter l'écouteur d'événement pour le bouton "Vider le panier"
        const clearCartButton = document.getElementById('clear-cart');
        if (clearCartButton) {
            clearCartButton.addEventListener('click', clearCart);
        }
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
            if (cartItemsInput) {
                cartItemsInput.value = JSON.stringify(cart);
            }
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
        
        if (cartTotalAmount) {
            let total = 0;
            cart.forEach(item => {
                total += parseFloat(item.price) * item.quantity;
            });
            
            cartTotalAmount.textContent = total.toFixed(2) + ' €';
        }
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