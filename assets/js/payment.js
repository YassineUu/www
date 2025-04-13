document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage des détails de carte selon le mode de paiement
    const paymentOptions = document.querySelectorAll('input[name="mode_paiement"]');
    const cardDetails = document.getElementById('card-details');
    
    function toggleCardDetails() {
        if (document.getElementById('cb') && document.getElementById('cb').checked) {
            if (cardDetails) cardDetails.style.display = 'block';
        } else {
            if (cardDetails) cardDetails.style.display = 'none';
        }
    }
    
    // Appliquer initialement
    toggleCardDetails();
    
    // Ajouter des écouteurs d'événements pour les changements
    paymentOptions.forEach(option => {
        option.addEventListener('change', toggleCardDetails);
    });
    
    // Validation du formulaire
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            // Si carte bancaire est sélectionnée, valider les champs
            if (document.getElementById('cb') && document.getElementById('cb').checked) {
                const cardNumber = document.getElementById('card_number').value.trim();
                const cardExpiry = document.getElementById('card_expiry').value.trim();
                const cardCvv = document.getElementById('card_cvv').value.trim();
                const cardName = document.getElementById('card_name').value.trim();
                
                // Simulation de validation (à adapter selon vos besoins)
                // Dans un environnement de production, la validation devrait être plus rigoureuse
                if (cardNumber === '' || cardExpiry === '' || cardCvv === '' || cardName === '') {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs de la carte bancaire.');
                }
            }
        });
    }
    
    // Formattage automatique du numéro de carte (4 chiffres espacés)
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            // Supprimer tous les caractères non numériques
            let value = this.value.replace(/\D/g, '');
            
            // Ajouter des espaces tous les 4 chiffres
            if (value.length > 0) {
                value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
            }
            
            // Limiter à 19 caractères (16 chiffres + 3 espaces)
            if (value.length > 19) {
                value = value.substring(0, 19);
            }
            
            this.value = value;
        });
    }
    
    // Formattage automatique de la date d'expiration (MM/AA)
    const cardExpiryInput = document.getElementById('card_expiry');
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            // Supprimer tous les caractères non numériques
            let value = this.value.replace(/\D/g, '');
            
            // Ajouter un / après les 2 premiers chiffres
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            this.value = value;
        });
    }
    
    // Limiter le CVV à 3-4 chiffres
    const cardCvvInput = document.getElementById('card_cvv');
    if (cardCvvInput) {
        cardCvvInput.addEventListener('input', function(e) {
            // Supprimer tous les caractères non numériques
            let value = this.value.replace(/\D/g, '');
            
            // Limiter à 4 chiffres
            if (value.length > 4) {
                value = value.substring(0, 4);
            }
            
            this.value = value;
        });
    }
}); 