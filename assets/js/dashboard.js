document.addEventListener('DOMContentLoaded', function() {
    // Navigation des sections
    const menuLinks = document.querySelectorAll('.menu-items a');
    const sections = document.querySelectorAll('.dashboard-content section');
    
    // Fonction pour afficher une section spécifique par son ID
    function showSection(targetId) {
        console.log("Affichage de la section:", targetId);
        
        // Masquer toutes les sections
        sections.forEach(section => {
            section.style.display = 'none';
        });
        
        // Supprimer la classe active de tous les liens
        menuLinks.forEach(menuLink => {
            menuLink.classList.remove('active');
        });
        
        // Afficher la section cible
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            targetSection.style.display = 'block';
            
            // Mettre à jour le lien actif dans le menu
            const activeLink = document.querySelector(`.menu-items a[href="#${targetId}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }
            
            // Mettre à jour l'URL sans recharger la page
            history.replaceState(null, null, `#${targetId}`);
        }
    }
    
    // Vérifier si un fragment existe dans l'URL
    const hash = window.location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        // Si un fragment valide existe, afficher cette section
        showSection(hash);
    } else {
        // Sinon, afficher la première section (tableau de bord)
        showSection('dashboard');
    }
    
    // Gestion de la navigation
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si c'est un lien interne (avec #)
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                
                // Récupérer l'ID de la section à afficher
                const targetId = this.getAttribute('href').substring(1);
                
                // Afficher la section correspondante
                showSection(targetId);
                
                // Scroll au début de la section
                window.scrollTo(0, 0);
            }
        });
    });
    
    // Gestion des formulaires - rediriger vers la bonne section après soumission
    document.querySelectorAll('form').forEach(form => {
        // Vérifier si le formulaire a déjà une action avec un fragment
        const action = form.getAttribute('action') || '';
        if (!action.includes('#')) {
            // Ajouter le fragment dashboard pour rediriger vers l'accueil
            form.setAttribute('action', `dashboard.php#dashboard`);
        }
    });
    
    // Écouter les changements d'URL pour mettre à jour la section active
    window.addEventListener('hashchange', function() {
        const newHash = window.location.hash.substring(1);
        if (newHash && document.getElementById(newHash)) {
            showSection(newHash);
        }
    });
    
    // === FONCTIONNALITÉS SPÉCIFIQUES AU RESTAURANT ===
    
    // Gestion de la modal pour modifier le statut des commandes
    const statusModal = document.getElementById('status-modal');
    if (statusModal) {
        const closeModal = document.querySelector('.close-modal');
        const statusBtns = document.querySelectorAll('.order-status-btn');
        
        statusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const status = this.getAttribute('data-status');
                
                document.getElementById('order_id').value = orderId;
                document.getElementById('status').value = status;
                
                statusModal.style.display = 'block';
            });
        });
        
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                statusModal.style.display = 'none';
            });
        }
        
        window.addEventListener('click', function(e) {
            if (e.target === statusModal) {
                statusModal.style.display = 'none';
            }
        });
    }
    
    // Gestion de la suppression des produits
    const deleteModal = document.getElementById('delete-confirm-modal');
    if (deleteModal) {
        const deleteBtns = document.querySelectorAll('button[name="delete_product"]');
        const deleteConfirmBtn = document.getElementById('delete-confirm');
        const deleteCancelBtn = document.getElementById('delete-cancel');
        let currentDeleteForm = null;
        
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                currentDeleteForm = this.closest('form');
                deleteModal.style.display = 'block';
            });
        });
        
        if (deleteCancelBtn) {
            deleteCancelBtn.addEventListener('click', function() {
                deleteModal.style.display = 'none';
                currentDeleteForm = null;
            });
        }
        
        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', function() {
                if (currentDeleteForm) {
                    currentDeleteForm.submit();
                }
                deleteModal.style.display = 'none';
            });
        }
        
        window.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.style.display = 'none';
                currentDeleteForm = null;
            }
        });
    }
    
    // === FONCTIONNALITÉS SPÉCIFIQUES AU LIVREUR ===
    
    // Gestion des boutons de visualisation (icônes d'œil)
    document.querySelectorAll('.delivery-details-btn, .order-details-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Récupérer l'ID cible du bouton
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Déterminer quelle section principale afficher
                if (targetId.startsWith('delivery-')) {
                    // C'est une livraison en cours, donc afficher la section des livraisons
                    showSection('current-deliveries');
                } else if (targetId.startsWith('order-')) {
                    // C'est une commande disponible, donc afficher la section des commandes
                    showSection('available-orders');
                }
                
                // Scroll jusqu'à l'élément
                setTimeout(() => {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                    
                    // Mettre en évidence l'élément
                    targetElement.classList.add('highlight-card');
                    setTimeout(() => {
                        targetElement.classList.remove('highlight-card');
                    }, 2000);
                }, 300);
            }
        });
    });
}); 