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
    
    // Gestion de l'ajout au panier
    const addToCartButtons = document.querySelectorAll('.btn-add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Vérifier si l'utilisateur est connecté
            const isLoggedIn = typeof window.isLoggedIn !== 'undefined' ? window.isLoggedIn : false;
            
            if (!isLoggedIn) {
                showLoginRequiredNotification();
                return;
            }
            
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            
            // Récupérer l'ID du restaurant depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const restaurantId = urlParams.get('id');
            
            // Récupérer le nom du restaurant depuis la page
            const restaurantNameElement = document.querySelector('.restaurant-name');
            const restaurantName = restaurantNameElement ? restaurantNameElement.textContent : 'Restaurant';
            
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
                    restaurant_name: restaurantName
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