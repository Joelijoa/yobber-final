<?php
$page_title = "Réinitialisation du mot de passe - JobPortal";
require_once '../includes/header.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/public/index.php');
    exit;
}

// Récupération du token depuis l'URL
$token = $_GET['token'] ?? '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $token = $_POST['token'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (empty($token)) {
        $errors[] = "Token de réinitialisation invalide";
    }
    
    if (empty($errors)) {
        // Connexion à la base de données
        $database = new Database();
        $db = $database->getConnection();
        
        // Vérification du token
        $query = "SELECT user_id, expires_at FROM password_resets WHERE token = ? AND used = 0";
        $stmt = $db->prepare($query);
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reset) {
            $errors[] = "Token de réinitialisation invalide ou déjà utilisé";
        } elseif (strtotime($reset['expires_at']) < time()) {
            $errors[] = "Le token de réinitialisation a expiré";
        } else {
            // Mise à jour du mot de passe
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                password_hash($password, PASSWORD_DEFAULT),
                $reset['user_id']
            ]);
            
            // Marquer le token comme utilisé
            $query = "UPDATE password_resets SET used = 1 WHERE token = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$token]);
            
            // Message de succès
            $_SESSION['flash_message'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            $_SESSION['flash_type'] = "success";
            
            // Redirection vers la page de connexion
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!-- Reset Password Section -->
<section class="reset-password-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center mb-4">Réinitialisation du mot de passe</h1>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="reset-password.php" class="needs-validation" novalidate>
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Le mot de passe doit contenir au moins 8 caractères</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-key me-2"></i>Réinitialiser le mot de passe
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">
                                <a href="login.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-2"></i>Retour à la connexion
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.reset-password-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.card {
    border: none;
    border-radius: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
}
</style>

<script>
// Validation du formulaire
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?> 