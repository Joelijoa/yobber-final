<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Initialisation de la connexion à la base de données
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$email = $_GET['email'] ?? '';
$errors = [];
$success = '';

// Afficher le code de vérification dans les logs pour le développement
if ($email) {
    $stmt = $pdo->prepare("SELECT u.id, v.code FROM users u JOIN email_verifications v ON u.id = v.user_id WHERE u.email = ? ORDER BY v.created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    if ($result) {
        error_log("Code de vérification pour $email : " . $result['code']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = $_POST['verification_code'] ?? '';
    
    if (!$verification_code) {
        $errors[] = "Le code de vérification est requis.";
    } else {
        try {
            // Récupérer l'utilisateur par email
            $stmt = $pdo->prepare("SELECT id, type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Vérifier le code
                $stmt = $pdo->prepare("SELECT * FROM email_verifications WHERE user_id = ? AND code = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                $stmt->execute([$user['id'], $verification_code]);
                $verification = $stmt->fetch();
                
                if ($verification) {
                    // Marquer l'utilisateur comme vérifié
                    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Supprimer le code de vérification
                    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    
                    $success = "Votre compte a été vérifié avec succès !";
                    
                    // Rediriger vers le tableau de bord approprié après 2 secondes
                    header("refresh:2;url=/" . $user['type'] . "/dashboard.php");
                } else {
                    $errors[] = "Code de vérification invalide ou expiré.";
                }
            } else {
                $errors[] = "Utilisateur non trouvé.";
            }
        } catch (PDOException $e) {
            error_log("Erreur de vérification : " . $e->getMessage());
            $errors[] = "Une erreur est survenue lors de la vérification.";
        }
    }
}
?>

<section class="verify-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center mb-4">Vérification du compte</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center">
                                <?php echo htmlspecialchars($success); ?>
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
                            
                            <p class="text-center mb-4">
                                Un code de vérification a été envoyé à <strong><?php echo htmlspecialchars($email); ?></strong>.<br>
                                Veuillez entrer ce code pour activer votre compte.
                            </p>
                            
                            <form method="post" action="/auth/verify.php?email=<?php echo urlencode($email); ?>" class="verification-form">
                                <div class="mb-4">
                                    <label class="form-label">Code de vérification</label>
                                    <input type="text" class="form-control form-control-lg text-center" name="verification_code" maxlength="5" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Vérifier</button>
                                </div>
                            </form>
                            
                            <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                            <div class="mt-3">
                                <p class="text-muted small text-center">
                                    Mode développement : Vérifiez les logs PHP pour voir le code de vérification.
                                </p>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.verify-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.verification-form input {
    letter-spacing: 0.5em;
    font-size: 1.5em;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 