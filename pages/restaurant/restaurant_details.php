                                <div class="product-footer">
                                    <div class="product-price"><?= number_format($produit['prix'], 2) ?> €</div>
                                    <button class="add-to-cart-btn" 
                                        data-id="<?= $produit['id_produit'] ?>" 
                                        data-name="<?= htmlspecialchars($produit['nom_p']) ?>" 
                                        data-price="<?= $produit['prix'] ?>">
                                        <i class="fas fa-plus"></i> Ajouter
                                    </button>
                                </div> 