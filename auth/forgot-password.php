<?php
$page_title = "Mot de passe oublié - JobPortal";
require_once '../includes/header.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/public/index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $errors = [];
    $success = false;
    
    // Validation
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (empty($errors)) {
        // Connexion à la base de données
        $database = new Database();
        $db = $database->getConnection();
        
        // Vérification si l'email existe
        $query = "SELECT id FROM users WHERE email = ? AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Génération du token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Suppression des anciens tokens
            $query = "DELETE FROM password_resets WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user['id']]);
            
            // Insertion du nouveau token
            $query = "INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([$user['id'], $token, $expires_at]);
            
            // Envoi de l'email
            $reset_link = BASE_PATH . '/auth/reset-password.php?token=' . $token;
            $to = $email;
            $subject = "Réinitialisation de votre mot de passe - JobPortal";
            $message = "Bonjour,\n\n";
            $message .= "Vous avez demandé la réinitialisation de votre mot de passe sur JobPortal.\n\n";
            $message .= "Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :\n";
            $message .= $reset_link . "\n\n";
            $message .= "Ce lien expirera dans 1 heure.\n\n";
            $message .= "Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.\n\n";
            $message .= "Cordialement,\nL'équipe JobPortal";
            $headers = "From: noreply@jobportal.com";
            
            if (mail($to, $subject, $message, $headers)) {
                $success = true;
            } else {
                $errors[] = "Une erreur est survenue lors de l'envoi de l'email";
            }
        } else {
            // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
            $success = true;
        }
    }
}
?>

<!-- Forgot Password Section -->
<section class="forgot-password-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center mb-4">Mot de passe oublié</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <p class="mb-0">
                                    Si votre adresse email est associée à un compte actif, vous recevrez un email contenant les instructions pour réinitialiser votre mot de passe.
                                </p>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="forgot-password.php" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    <div class="form-text">
                                        Entrez l'adresse email associée à votre compte. Nous vous enverrons un lien pour réinitialiser votre mot de passe.
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer le lien de réinitialisation
                                </button>
                            </form>
                        <?php endif; ?>
                        
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
.forgot-password-section {
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