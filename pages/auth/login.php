<?php
include_once '../../includes/header.php';
require_once '../../config/database.php';

// Activer le débogage pour identifier les problèmes
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Initialiser les variables
$error = '';
$email = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    
    // Validation basique
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Déterminer la table et les colonnes selon le type d'utilisateur
            $tableName = '';
            $idColumn = '';
            $passwordColumn = '';
            $identityColumn = 'email'; // Par défaut pour tous les types d'utilisateurs
            
            switch ($userType) {
                case 'client':
                    $tableName = 'Client';
                    $idColumn = 'id_client';
                    $passwordColumn = 'mot_de_passe';
                    break;
                case 'restaurant':
                    $tableName = 'Restaurant';
                    $idColumn = 'id_restaurant';
                    $passwordColumn = 'contact';
                    break;
                case 'livreur':
                    $tableName = 'Livreur';
                    $idColumn = 'id_livreur';
                    $passwordColumn = 'mot_de_passe';
                    break;
                case 'admin':
                    $tableName = 'Admin';
                    $idColumn = 'id_admin';
                    $passwordColumn = 'password';
                    break;
                default:
                    $error = 'Type d\'utilisateur invalide.';
            }
            
            // Si pas d'erreur, continuer avec la vérification
            if (empty($error)) {
                // Vérifier d'abord si la table existe
                $tableQuery = "SHOW TABLES LIKE '{$tableName}'";
                $tableStmt = $conn->prepare($tableQuery);
                $tableStmt->execute();
                
                if ($tableStmt->rowCount() == 0) {
                    $error = "Table {$tableName} introuvable. Veuillez contacter l'administrateur.";
                } else {
                    // Vérifier que les colonnes existent dans la table
                    $checkColumnsQuery = "SHOW COLUMNS FROM {$tableName} WHERE Field IN ('{$identityColumn}', '{$passwordColumn}')";
                    $checkStmt = $conn->prepare($checkColumnsQuery);
                    $checkStmt->execute();
                    $columns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (count($columns) < 2) {
                        // Afficher les colonnes existantes pour le débogage
                        $columnsQuery = "SHOW COLUMNS FROM {$tableName}";
                        $columnsStmt = $conn->prepare($columnsQuery);
                        $columnsStmt->execute();
                        $allColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $error = "Structure de table incorrecte. Colonnes attendues : {$identityColumn}, {$passwordColumn}. Colonnes disponibles : " . implode(", ", $allColumns);
                    } else {
                        // Requête pour vérifier l'utilisateur
                        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE {$identityColumn} = :identity");
                        $stmt->bindParam(':identity', $email);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Debug: Afficher les valeurs pour comprendre le problème
                            // $error = "Debug - Password entré: {$password}, Password DB: {$user[$passwordColumn]}";
                            
                            // Vérifier le mot de passe (simple comparaison pour la démo)
                            if ($password === $user[$passwordColumn]) {
                                // Connexion réussie, stocker les informations en session
                                $_SESSION['user_id'] = $user[$idColumn];
                                $_SESSION['email'] = $user[$identityColumn];
                                $_SESSION['role'] = $userType;
                                
                                // Ajouter des informations supplémentaires en session selon le type d'utilisateur
                                switch ($userType) {
                                    case 'client':
                                        $_SESSION['nom'] = $user['nom_c'] ?? '';
                                        $_SESSION['prenom'] = $user['prenom_c'] ?? '';
                                        break;
                                    case 'restaurant':
                                        $_SESSION['nom'] = $user['nom_r'] ?? '';
                                        break;
                                    case 'livreur':
                                        $_SESSION['nom'] = $user['nom_l'] ?? '';
                                        $_SESSION['prenom'] = $user['prenom_l'] ?? '';
                                        $_SESSION['telephone'] = $user['telephone'] ?? '';
                                        break;
                                    case 'admin':
                                        $_SESSION['nom'] = $user['nom'] ?? '';
                                        $_SESSION['prenom'] = $user['prenom'] ?? '';
                                        break;
                                }
                                
                                // Rediriger selon le type d'utilisateur
                                switch ($userType) {
                                    case 'client':
                                        header('Location: /pages/client/dashboard.php');
                                        break;
                                    case 'restaurant':
                                        header('Location: /pages/restaurant/dashboard.php');
                                        break;
                                    case 'livreur':
                                        header('Location: /pages/livreur/dashboard.php');
                                        break;
                                    case 'admin':
                                        header('Location: /pages/admin/dashboard.php');
                                        break;
                                }
                                exit;
                            } else {
                                $error = 'Mot de passe incorrect.';
                            }
                        } else {
                            $error = 'Aucun utilisateur trouvé avec cet email.';
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur de base de données: ' . $e->getMessage();
        }
    }
}

// Récupérer le type d'utilisateur depuis l'URL (si présent)
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