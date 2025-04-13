<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Initialiser les variables
$error = '';
$success = '';
$nom = '';
$prenom = '';
$email = '';
$adresse = '';
$telephone = '';

// Récupérer le type d'utilisateur depuis l'URL (si présent)
$userType = isset($_GET['type']) ? $_GET['type'] : 'client';

// Traiter le formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['user_type'];
    $nom = trim($_POST['nom']);
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    
    // Validation basique
    if (empty($nom) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Hasher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Déterminer la table et les champs selon le type d'utilisateur
            switch ($userType) {
                case 'client':
                    // Vérifier si l'email existe déjà
                    $stmt = $conn->prepare("SELECT * FROM Client WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $error = 'Cet email est déjà utilisé.';
                    } else {
                        // Insérer le nouveau client
                        $stmt = $conn->prepare("INSERT INTO Client (nom_c, prenom_c, adresse_c, email, mot_de_passe) VALUES (:nom, :prenom, :adresse, :email, :password)");
                        $stmt->bindParam(':nom', $nom);
                        $stmt->bindParam(':prenom', $prenom);
                        $stmt->bindParam(':adresse', $adresse);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashedPassword); // Utilisez le mot de passe haché
                        $stmt->execute();
                        
                        $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
                    }
                    break;
                    
                case 'restaurant':
                    // Vérifier si l'email existe déjà
                    $stmt = $conn->prepare("SELECT * FROM Restaurant WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $error = 'Cet email est déjà utilisé.';
                    } else {
                        // Insérer le nouveau restaurant
                        $stmt = $conn->prepare("INSERT INTO Restaurant (nom_r, adresse_r, email, contact) VALUES (:nom, :adresse, :email, :password)");
                        $stmt->bindParam(':nom', $nom);
                        $stmt->bindParam(':adresse', $adresse);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashedPassword); // Utilisez le mot de passe haché dans le champ contact
                        $stmt->execute();
                        
                        $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
                    }
                    break;
                    
                case 'livreur':
                    // Vérifier si l'email existe déjà
                    $stmt = $conn->prepare("SELECT * FROM Livreur WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $error = 'Cet email est déjà utilisé.';
                    } else {
                        // Insérer le nouveau livreur
                        $stmt = $conn->prepare("INSERT INTO Livreur (nom_l, prenom_l, email, telephone, mot_de_passe, vehicule) VALUES (:nom, :prenom, :email, :telephone, :password, 'scooter')");
                        $stmt->bindParam(':nom', $nom);
                        $stmt->bindParam(':prenom', $prenom);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':telephone', $telephone);
                        $stmt->bindParam(':password', $hashedPassword); // Utilisez le mot de passe haché
                        $stmt->execute();
                        
                        $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
                    }
                    break;
                    
                default:
                    $error = 'Type d\'utilisateur invalide.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de base de données: ' . $e->getMessage();
        }
    }
}
?>

<div class="form-container">
    <h2>Inscription</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <p>Cliquez <a href="login.php?type=<?php echo $userType; ?>">ici</a> pour vous connecter.</p>
    <?php else: ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="user_type">Je m'inscris en tant que :</label>
            <select name="user_type" id="user_type" class="form-control" onchange="updateForm()">
                <option value="client" <?php echo $userType === 'client' ? 'selected' : ''; ?>>Client</option>
                <option value="restaurant" <?php echo $userType === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                <option value="livreur" <?php echo $userType === 'livreur' ? 'selected' : ''; ?>>Livreur</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="nom">Nom <?php echo $userType === 'restaurant' ? 'du restaurant' : ''; ?> :</label>
            <input type="text" name="nom" id="nom" class="form-control" value="<?php echo htmlspecialchars($nom); ?>" required>
        </div>
        
        <div class="form-group" id="prenom_group" <?php echo $userType === 'restaurant' ? 'style="display:none;"' : ''; ?>>
            <label for="prenom">Prénom :</label>
            <input type="text" name="prenom" id="prenom" class="form-control" value="<?php echo htmlspecialchars($prenom); ?>">
        </div>
        
        <div class="form-group">
            <label for="email"><?php echo $userType === 'restaurant' ? 'Email de contact' : 'Email'; ?> :</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        
        <div class="form-group" id="telephone_group" <?php echo $userType !== 'livreur' ? 'style="display:none;"' : ''; ?>>
            <label for="telephone">Téléphone :</label>
            <input type="tel" name="telephone" id="telephone" class="form-control" value="<?php echo htmlspecialchars($telephone); ?>">
        </div>
        
        <div class="form-group" id="adresse_group" <?php echo $userType === 'livreur' ? 'style="display:none;"' : ''; ?>>
            <label for="adresse">Adresse :</label>
            <textarea name="adresse" id="adresse" class="form-control" rows="3"><?php echo htmlspecialchars($adresse); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </div>
    </form>
    
    <div class="form-footer">
        <p>Vous avez déjà un compte ? <a href="login.php?type=<?php echo $userType; ?>">Se connecter</a></p>
    </div>
    
    <?php endif; ?>
</div>

<script>
function updateForm() {
    const userType = document.getElementById('user_type').value;
    const prenomGroup = document.getElementById('prenom_group');
    const telephoneGroup = document.getElementById('telephone_group');
    const adresseGroup = document.getElementById('adresse_group');
    const nomLabel = document.querySelector('label[for="nom"]');
    const emailLabel = document.querySelector('label[for="email"]');
    
    // Afficher/masquer les champs selon le type d'utilisateur
    if (userType === 'restaurant') {
        prenomGroup.style.display = 'none';
        telephoneGroup.style.display = 'none';
        adresseGroup.style.display = 'block';
        nomLabel.textContent = 'Nom du restaurant :';
        emailLabel.textContent = 'Email de contact :';
    } else if (userType === 'livreur') {
        prenomGroup.style.display = 'block';
        telephoneGroup.style.display = 'block';
        adresseGroup.style.display = 'none';
        nomLabel.textContent = 'Nom :';
        emailLabel.textContent = 'Email :';
    } else { // client
        prenomGroup.style.display = 'block';
        telephoneGroup.style.display = 'none';
        adresseGroup.style.display = 'block';
        nomLabel.textContent = 'Nom :';
        emailLabel.textContent = 'Email :';
    }
}

// Initialiser le formulaire au chargement
document.addEventListener('DOMContentLoaded', updateForm);
</script>

<?php
include_once '../../includes/footer.php';
?> 