document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('restaurant-search');
    const restaurantsContainer = document.getElementById('restaurants-container');
    const restaurantCards = document.querySelectorAll('.restaurant-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            // Filtrer les restaurants par nom
            restaurantCards.forEach(card => {
                const restaurantName = card.getAttribute('data-name');
                
                if (restaurantName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Vérifier s'il y a des résultats visibles
            const visibleCards = restaurantsContainer.querySelectorAll('.restaurant-card[style="display: block"]');
            
            if (visibleCards.length === 0 && searchTerm !== '') {
                // Créer un message "Aucun résultat" s'il n'existe pas déjà
                if (!document.querySelector('.no-search-results')) {
                    const noResults = document.createElement('div');
                    noResults.className = 'no-search-results';
                    noResults.innerHTML = '<i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px;"></i><p>Aucun restaurant trouvé pour "<strong>' + searchTerm + '</strong>".</p>';
                    restaurantsContainer.appendChild(noResults);
                }
            } else {
                // Supprimer le message s'il existe
                const noResults = document.querySelector('.no-search-results');
                if (noResults) {
                    noResults.remove();
                }
            }
        });
    }
}); 