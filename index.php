<?php
include_once 'includes/header.php';
?>

<section class="banner">
    <div class="banner-content">
        <div class="banner-text">
            <h1>Livri</h1>
            <p>Livraison de repas rapide et facile</p>
        </div>
        <div class="banner-image">
            <img src="assets/images/burger.png" alt="Délicieux burger">
        </div>
        <div class="address-search">
            <input type="text" class="address-input" placeholder="Quelle est votre adresse ?">
            <button class="confirm-btn">Confirmer</button>
        </div>
    </div>
</section>

<section class="categories">
    <div class="category-container">
        <a href="pages/client/restaurants.php?category=1" class="category-item">
            <div class="category-icon">
                <img src="assets/images/dessert.png" alt="Dessert">
            </div>
            <h3>Dessert</h3>
        </a>
        <a href="pages/client/restaurants.php?category=2" class="category-item">
            <div class="category-icon">
                <img src="assets/images/asiatique.png" alt="Asiatique">
            </div>
            <h3>Asiatique</h3>
        </a>
        <a href="pages/client/restaurants.php?category=3" class="category-item">
            <div class="category-icon">
                <img src="assets/images/restaurant.png" alt="Restaurant">
            </div>
            <h3>Restaurant</h3>
        </a>
        <a href="pages/client/restaurants.php?category=4" class="category-item">
            <div class="category-icon">
                <img src="assets/images/fastfood.png" alt="Fast-food">
            </div>
            <h3>Fast-food</h3>
        </a>
        <a href="pages/client/restaurants.php?category=5" class="category-item">
            <div class="category-icon">
                <img src="assets/images/orientale.png" alt="Orientale">
            </div>
            <h3>Orientale</h3>
        </a>
    </div>
</section>

<style>
    main{
        padding: 0;
    }
.banner {
    background-color: #f8d24b;
    padding: 0;
    position: relative;
    overflow: hidden;
    clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
    height: 400px;
    margin-bottom: 80px;
    width: 100%;
}

.banner-content {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    height: 100%;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.banner-text {
    position: absolute;
    left: 400px;
    top: 40%;
    transform: translateY(-50%);
    text-align: left;
    color: #333;
    z-index: 5;
}

.banner-text h1 {
    font-size: 4rem;
    margin-bottom: 10px;
    font-weight: bold;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
}

.banner-text p {
    font-size: 1.5rem;
    margin: 0;
}

.banner-image {
    position: absolute;
    left: 100px;
    top: 50%;
    transform: translateY(-50%);
    width: 300px;
    z-index: 2;
}

.banner-image img {
    max-width: 100%;
    height: auto;
    display: block;
}

.address-search {
    position: absolute;
    right: 5%;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(152, 222, 217, 0.8);
    padding: 15px 20px;
    border-radius: 50px;
    width: 450px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.address-input {
    border: none;
    background: transparent;
    width: 70%;
    padding: 10px;
    font-size: 16px;
    outline: none;
    color: #333;
}

.address-input::placeholder {
    color: #333;
}

.confirm-btn {
    background-color: #f8d24b;
    color: #333;
    border: none;
    padding: 10px 25px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: bold;
    text-transform: none;
    font-size: 14px;
}

/* Styles spécifiques pour les catégories de la page d'accueil */
.categories {
    padding: 40px 0;
    margin-top: 40px;
}

.categories .category-container {
    display: flex;
    justify-content: center;
    gap: 50px;
    flex-wrap: wrap;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.categories .category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 120px;
    text-decoration: none;
    color: #333;
    transition: transform 0.3s ease;
}

.categories .category-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.categories .category-item:hover {
    transform: translateY(-5px);
}

.categories .category-item:hover .category-icon {
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
}

.categories .category-icon img {
    width: 60%;
    height: auto;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.categories .category-icon:hover img {
    transform: scale(1.1);
}

.categories .category-item h3 {
    margin: 0;
    font-size: 16px;
    font-weight: normal;
    color: #333;
    text-align: center;
}

@media (max-width: 768px) {
    .banner {
        height: 250px;
    }
    
    .banner-content {
        flex-direction: column;
    }
    
    .banner-image {
        position: relative;
        left: 0;
        top: 30px;
        transform: none;
        width: 150px;
        margin-bottom: 20px;
    }
    
    .address-search {
        position: relative;
        right: 0;
        top: 0;
        transform: none;
        width: 90%;
        margin: 0 auto;
    }
    
    .categories .category-container {
        gap: 30px;
    }
}
</style>

<?php
include_once 'includes/footer.php';
?> 