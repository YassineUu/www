// Fonction pour initialiser les événements après chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Ajout au panier
    initAddToCart();
    
    // Formulaire d'adresse de livraison
    initAddressForm();
    
    // Quantité des produits
});

// Initialisation des boutons d'ajout au panier
function initAddToCart() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    if (addToCartButtons.length > 0) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                const productPrice = this.getAttribute('data-product-price');
                
                // Ajouter au panier (localStorage pour la démo)
                addProductToCart(productId, productName, productPrice);
                
                // Afficher une notification
                showNotification('Produit ajouté au panier !');
            });
        });
    }
}

// Ajouter un produit au panier (localStorage)
function addProductToCart(id, name, price) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Vérifier si le produit est déjà dans le panier
    const existingProductIndex = cart.findIndex(item => item.id === id);
    
    if (existingProductIndex !== -1) {
        // Si oui, incrémenter la quantité
        cart[existingProductIndex].quantity += 1;
    } else {
        // Sinon, ajouter le produit
        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: 1
        });
    }
    
    // Sauvegarder le panier
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Mettre à jour le compteur du panier
    updateCartCount();
}

// Mettre à jour le compteur du panier
function updateCartCount() {
    const cartCounter = document.querySelector('.cart-count');
    if (cartCounter) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        cartCounter.textContent = totalItems;
    }
}

// Afficher une notification
function showNotification(message) {
    // Créer un élément de notification
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    // Ajouter au corps du document
    document.body.appendChild(notification);
    
    // Afficher avec animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Initialisation du formulaire d'adresse
function initAddressForm() {
    const addressForm = document.querySelector('.address-form');
    const confirmButton = document.querySelector('.confirm-address');
    
    if (addressForm && confirmButton) {
        confirmButton.addEventListener('click', function(e) {
            e.preventDefault();
            const addressInput = document.querySelector('.address-input');
            
            if (addressInput && addressInput.value.trim() !== '') {
                // Sauvegarder l'adresse (localStorage pour la démo)
                localStorage.setItem('deliveryAddress', addressInput.value);
                
                // Afficher une notification
                showNotification('Adresse de livraison confirmée !');
                
                // Rediriger vers la page des restaurants ou continuer
                if (this.getAttribute('data-redirect') === 'true') {
                    window.location.href = '/pages/client/restaurants.php';
                }
            } else {
                showNotification('Veuillez entrer une adresse valide.');
            }
        });
    }
}