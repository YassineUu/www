// Code JavaScript pour la page des détails d'un restaurant
document.addEventListener('DOMContentLoaded', function() {
    // Gérer le scroll des catégories
    const categoriesContainer = document.querySelector('.menu-categories');
    
    if (categoriesContainer) {
        // Gérer le clic sur une catégorie
        const categoryLinks = document.querySelectorAll('.category-tab');
        categoryLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Retirer la classe active de toutes les catégories
                categoryLinks.forEach(cat => cat.classList.remove('active'));
                
                // Ajouter la classe active à la catégorie cliquée
                this.classList.add('active');
                
                // Récupérer la catégorie cible
                const url = this.getAttribute('href');
                if (url) {
                    window.location.href = url;
                }
            });
        });
    }
    
    // Sélecteurs des éléments
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const container = document.querySelector('.container');
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = '<i class="fas fa-check-circle"></i> Produit ajouté au panier';
    document.body.appendChild(notification);
    
    // Fonction pour ajouter au panier
    function addToCart(event) {
        const button = event.currentTarget;
        const productId = button.getAttribute('data-id');
        const productName = button.getAttribute('data-name');
        const productPrice = parseFloat(button.getAttribute('data-price'));
        
        // Récupérer le nom du restaurant et son ID depuis la page
        const restaurantNameElement = document.querySelector('.restaurant-name');
        const restaurantName = restaurantNameElement ? restaurantNameElement.textContent.trim() : 'Restaurant';
        const restaurantId = new URLSearchParams(window.location.search).get('id');
        
        // Vérifier si l'utilisateur est connecté
        if (!window.isLoggedIn) {
            // Rediriger vers la page de connexion
            window.location.href = '/pages/auth/login.php?type=client&redirect=' + encodeURIComponent(window.location.href);
            return;
        }
        
        // Animation du bouton
        button.innerHTML = '<i class="fas fa-check"></i> Ajouté';
        button.classList.add('added');
        
        // Restaurer le bouton après un délai
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-plus"></i> Ajouter';
            button.classList.remove('added');
        }, 2000);
        
        // Récupérer le panier actuel du localStorage
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Vérifier si le panier contient déjà des produits d'un autre restaurant
        if (cart.length > 0 && cart[0].restaurant_id && cart[0].restaurant_id !== restaurantId) {
            // Utiliser une confirmation plus esthétique
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Attention !',
                    text: "Vous avez déjà des produits d'un autre restaurant dans votre panier. Si vous continuez, votre panier actuel sera vidé.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4CAF50',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, vider le panier',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Vider le panier existant
                        cart = [];
                        
                        // Ajouter le nouveau produit au panier
                        cart.push({
                            id: productId,
                            name: productName,
                            price: productPrice,
                            quantity: 1,
                            restaurant_id: restaurantId,
                            restaurant_name: restaurantName
                        });
                        
                        // Mettre à jour le panier dans localStorage
                        localStorage.setItem('cart', JSON.stringify(cart));
                        
                        // Mettre à jour le compteur du panier dans le header
                        updateCartCounter();
                        
                        // Afficher la notification
                        showNotification();
                    }
                });
                return;
            } else {
                // Fallback sur alert standard si SweetAlert n'est pas disponible
                if (confirm("Attention: Vous avez déjà des produits d'un autre restaurant dans votre panier. Si vous continuez, votre panier actuel sera vidé.")) {
                    // Vider le panier existant
                    cart = [];
                } else {
                    return;
                }
            }
        }
        
        // Vérifier si le produit est déjà dans le panier
        const existingProductIndex = cart.findIndex(item => item.id === productId);
        
        if (existingProductIndex !== -1) {
            // Incrémenter la quantité si le produit existe déjà
            cart[existingProductIndex].quantity += 1;
        } else {
            // Ajouter le nouveau produit au panier
            cart.push({
                id: productId,
                name: productName,
                price: productPrice,
                quantity: 1,
                restaurant_id: restaurantId,
                restaurant_name: restaurantName
            });
        }
        
        // Mettre à jour le panier dans localStorage
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Mettre à jour le compteur du panier dans le header
        updateCartCounter();
        
        // Afficher la notification
        showNotification();
    }
    
    // Fonction pour mettre à jour le compteur du panier
    function updateCartCounter() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartItems = cart.reduce((total, item) => total + item.quantity, 0);
        
        // Mise à jour du compteur dans le header si l'élément existe
        const cartCounter = document.querySelector('.cart-counter');
        if (cartCounter) {
            cartCounter.textContent = cartItems;
            
            if (cartItems > 0) {
                cartCounter.classList.add('visible');
            } else {
                cartCounter.classList.remove('visible');
            }
        }
    }
    
    // Fonction pour afficher la notification
    function showNotification() {
        notification.classList.add('show');
        
        // Cacher la notification après 3 secondes
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
    
    // Ajouter les écouteurs d'événements
    addToCartButtons.forEach(button => {
        button.addEventListener('click', addToCart);
    });
    
    // Mettre à jour le compteur au chargement de la page
    updateCartCounter();
    
    // Animation des cards au scroll
    const productCards = document.querySelectorAll('.product-card');
    
    // Fonction pour vérifier si un élément est visible dans la fenêtre
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    // Fonction pour animer les éléments visibles
    function animateVisibleElements() {
        productCards.forEach(card => {
            if (isElementInViewport(card) && !card.classList.contains('visible')) {
                card.classList.add('visible');
            }
        });
    }
    
    // Appeler la fonction au chargement et au scroll
    window.addEventListener('scroll', animateVisibleElements);
    animateVisibleElements();
}); 