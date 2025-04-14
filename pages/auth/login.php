<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Initialiser les variables
$error = '';
$email = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    
    // Validation des données
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Déterminer la table et la requête selon le type d'utilisateur
            switch ($userType) {
                case 'client':
                    $stmt = $conn->prepare("SELECT id_client, nom_c, prenom_c, mot_de_passe FROM Client WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Vérifier le mot de passe
                        if (password_verify($password, $user['mot_de_passe'])) {
                            $_SESSION['user_id'] = $user['id_client'];
                            $_SESSION['user_name'] = $user['prenom_c'] . ' ' . $user['nom_c'];
                            $_SESSION['role'] = 'client';
                            header('Location: ../client/dashboard.php');
                            exit;
                        } else {
                            $error = 'Mot de passe incorrect.';
                        }
                    } else {
                        $error = 'Email non trouvé.';
                    }
                    break;
                    
                case 'restaurant':
                    $stmt = $conn->prepare("SELECT id_restaurant, nom_r, contact, password FROM Restaurant WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        if (password_verify($password, $user['password'])) {
                            $_SESSION['user_id'] = $user['id_restaurant'];
                            $_SESSION['user_name'] = $user['nom_r'];
                            $_SESSION['role'] = 'restaurant';
                            header('Location: ../restaurant/dashboard.php');
                            exit;
                        } else {
                            $error = 'Mot de passe incorrect.';
                        }
                    } else {
                        $error = 'Email non trouvé.';
                    }
                    break;
                    
                case 'livreur':
                    $stmt = $conn->prepare("SELECT id_livreur, nom_l, prenom_l, mot_de_passe FROM Livreur WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Vérifier le mot de passe
                        if (password_verify($password, $user['mot_de_passe'])) {
                            $_SESSION['user_id'] = $user['id_livreur'];
                            $_SESSION['user_name'] = $user['prenom_l'] . ' ' . $user['nom_l'];
                            $_SESSION['role'] = 'livreur';
                            header('Location: ../livreur/dashboard.php');
                            exit;
                        } else {
                            $error = 'Mot de passe incorrect.';
                        }
                    } else {
                        $error = 'Email non trouvé.';
                    }
                    break;
                    
                case 'admin':
                    $stmt = $conn->prepare("SELECT id_admin, nom, prenom, password FROM Admin WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        if (password_verify($password, $user['password'])) {
                            $_SESSION['user_id'] = $user['id_admin'];
                            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                            $_SESSION['role'] = 'admin';
                            header('Location: ../admin/dashboard.php');
                            exit;
                        } else {
                            $error = 'Mot de passe incorrect.';
                        }
                    } else {
                        $error = 'Email non trouvé.';
                    }
                    break;
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données: ' . $e->getMessage();
        }
    }
}


$userTypeFromURL = isset($_GET['type']) ? $_GET['type'] : 'client';
?>

<div class="form-container">
    <h2>Connexion</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="user_type">Je suis :</label>
            <select name="user_type" id="user_type" class="form-control">
                <option value="client" <?php echo $userTypeFromURL === 'client' ? 'selected' : ''; ?>>Client</option>
                <option value="restaurant" <?php echo $userTypeFromURL === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                <option value="livreur" <?php echo $userTypeFromURL === 'livreur' ? 'selected' : ''; ?>>Livreur</option>
                <option value="admin" <?php echo $userTypeFromURL === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Se connecter</button>
        </div>
    </form>
    
    <div class="form-footer">
        <p>Vous n'avez pas de compte ? <a href="register.php<?php echo !empty($userTypeFromURL) ? '?type=' . $userTypeFromURL : ''; ?>">S'inscrire</a></p>
    </div>
</div>

<?php
include_once '../../includes/footer.php';
?> 