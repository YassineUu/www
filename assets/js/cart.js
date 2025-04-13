document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté (sera défini dans le HTML)
    // const isLoggedIn = défini via un attribut data ou une variable PHP
    
    // Initialiser le bouton "Vider le panier"
    const clearCartButton = document.getElementById('clear-cart');
    if (clearCartButton) {
        clearCartButton.addEventListener('click', clearCart);
    }
    
    // Charger le panier immédiatement au chargement de la page
    loadCart();
    
    // Charger le panier
    function loadCart() {
        // Récupérer le panier depuis localStorage
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Récupérer les éléments du DOM
        const loadingMessage = document.querySelector('.loading-message');
        const emptyCartMessage = document.querySelector('.empty-cart');
        const cartItemsContainer = document.querySelector('.cart-items');
        const cartSummary = document.querySelector('.cart-summary');
        const cartTotalAmount = document.querySelector('.cart-total-amount');
        const cartItemsInput = document.getElementById('cart-items-input');
        
        // Masquer le message de chargement
        if (loadingMessage) loadingMessage.style.display = 'none';
        
        // Afficher le message approprié en fonction du contenu du panier
        if (cart.length === 0) {
            if (emptyCartMessage) emptyCartMessage.style.display = 'block';
            if (cartItemsContainer) cartItemsContainer.style.display = 'none';
            if (cartSummary) cartSummary.style.display = 'none';
            return;
        }
        
        // Afficher le contenu du panier
        if (emptyCartMessage) emptyCartMessage.style.display = 'none';
        if (cartItemsContainer) cartItemsContainer.style.display = 'block';
        if (cartSummary) cartSummary.style.display = 'block';
        
        // Vider le conteneur des articles
        if (cartItemsContainer) cartItemsContainer.innerHTML = '';
        
        // Créer la structure du tableau
        const tableHeader = document.createElement('div');
        tableHeader.className = 'cart-list-header';
        tableHeader.innerHTML = `
            <div class="cart-header-product">Produit</div>
            <div class="cart-header-price">Prix unitaire</div>
            <div class="cart-header-quantity">Quantité</div>
            <div class="cart-header-total">Total</div>
            <div class="cart-header-actions">Actions</div>
        `;
        cartItemsContainer.appendChild(tableHeader);
        
        const cartList = document.createElement('div');
        cartList.className = 'cart-list';
        cartItemsContainer.appendChild(cartList);
        
        // Afficher les informations du restaurant si disponibles
        if (cart.length > 0 && cart[0].restaurant_name) {
            const restaurantInfo = document.createElement('div');
            restaurantInfo.className = 'restaurant-info-cart';
            restaurantInfo.innerHTML = `
                <h3><i class="fas fa-store"></i> Restaurant: ${cart[0].restaurant_name}</h3>
            `;
            cartList.appendChild(restaurantInfo);
        }
        
        // Parcourir les éléments du panier
        cart.forEach((item, index) => {
            // Créer un élément de panier
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.setAttribute('data-index', index);
            cartItem.setAttribute('data-price', item.price);
            
            cartItem.innerHTML = `
                <div class="cart-item-info">
                    <h3>${item.name}</h3>
                    <p class="cart-item-restaurant"><i class="fas fa-store"></i> ${item.restaurant_name || 'Restaurant'}</p>
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
                    ${(item.price * item.quantity).toFixed(2)} €
                </div>
                <div class="cart-item-remove">
                    <button class="remove-item"><i class="fas fa-trash"></i> Supprimer</button>
                </div>
            `;
            
            cartList.appendChild(cartItem);
        });
        
        // Calculer et afficher le total
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        if (cartTotalAmount) cartTotalAmount.textContent = total.toFixed(2) + ' €';
        
        // Mettre à jour l'input caché pour le formulaire
        if (cartItemsInput) cartItemsInput.value = JSON.stringify(cart);
        
        // Initialiser les contrôles de quantité
        initQuantityControls();
        
        // Initialiser les boutons de suppression
        initRemoveButtons();
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
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Supprimer l\'article ?',
                        text: "Voulez-vous vraiment retirer cet article du panier ?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4CAF50',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Oui, supprimer',
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            removeCartItem(index);
                            Swal.fire(
                                'Supprimé !',
                                'L\'article a été retiré de votre panier.',
                                'success'
                            );
                        }
                    });
                } else {
                    if (confirm("Voulez-vous vraiment retirer cet article du panier ?")) {
                        removeCartItem(index);
                    }
                }
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
            
            // Mettre à jour le compteur du panier dans l'en-tête
            updateCartBadge();
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
            
            // Mettre à jour le compteur du panier dans l'en-tête
            updateCartBadge();
        }
    }
    
    // Vider le panier
    function clearCart() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Vider le panier ?',
                text: "Voulez-vous vraiment vider votre panier ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, vider le panier',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('cart');
                    loadCart();
                    updateCartBadge();
                    Swal.fire(
                        'Vidé !',
                        'Votre panier a été vidé avec succès.',
                        'success'
                    );
                }
            });
        } else {
            if (confirm("Voulez-vous vraiment vider votre panier ?")) {
                localStorage.removeItem('cart');
                loadCart();
                updateCartBadge();
            }
        }
    }
    
    // Mettre à jour le badge du panier dans le header
    function updateCartBadge() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCounter = document.querySelector('.cart-counter');
        
        if (cartCounter) {
            const cartItems = cart.reduce((total, item) => total + item.quantity, 0);
            cartCounter.textContent = cartItems;
            
            if (cartItems > 0) {
                cartCounter.classList.add('visible');
            } else {
                cartCounter.classList.remove('visible');
            }
        }
    }
}); 