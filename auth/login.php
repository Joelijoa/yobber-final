<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/public/index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST Data: ' . print_r($_POST, true));
    error_log('Session CSRF: ' . ($_SESSION['csrf_token'] ?? 'not set'));
    error_log('POST CSRF: ' . ($_POST['csrf_token'] ?? 'not set'));

    // ver token existe
    if (!isset($_POST['csrf_token'])) {
        error_log('CSRF token missing');
        set_flash_message('error', get_error_message('invalid_request'));
        redirect('/public/auth/login.php');
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $errors = [];
    
    // Validation
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }
    
    if (empty($errors)) {
        try {
            // Vérification des identifiants
            $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name, user_type 
                     FROM users 
                     WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                loginUser($user['id'], $user['user_type']);
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Gestion du "Se souvenir de moi"
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $token, $expires]);
                    
                    setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                }
                
                // Redirection selon le type d'utilisateur
                if ($user['user_type'] === 'candidate') {
                    redirect('/public/candidate/dashboard.php');
                } elseif ($user['user_type'] === 'recruiter') {
                    redirect('/public/recruiter/dashboard.php');
                } else {
                    redirect('/public/admin/dashboard.php');
                }
                exit;
            } else {
                $errors[] = "Email ou mot de passe incorrect";
            }
        } catch (PDOException $e) {
            error_log("Erreur de connexion : " . $e->getMessage());
            $errors[] = "Une erreur est survenue lors de la connexion. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!-- Login Section -->
<section class="login-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center mb-4">Connexion</h1>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="/public/auth/login.php" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </button>
                            
                            <div class="text-center">
                                <a href="/public/auth/forgot-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted mb-3">Ou connectez-vous avec</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="#" class="btn btn-outline-danger">
                                    <i class="fab fa-google me-2"></i>Google
                                </a>
                                <a href="#" class="btn btn-outline-primary">
                                    <i class="fab fa-linkedin me-2"></i>LinkedIn
                                </a>
                                <a href="#" class="btn btn-outline-dark">
                                    <i class="fab fa-github me-2"></i>GitHub
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="mb-0">
                        Vous n'avez pas de compte ?
                        <a href="/public/auth/register.php" class="text-decoration-none">Inscrivez-vous</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.login-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.card {
    border: none;
    border-radius: 1rem;
}

.input-group-text {
    border-radius: 0.5rem 0 0 0.5rem;
}

.input-group .form-control {
    border-radius: 0 0.5rem 0.5rem 0;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
}

.social-login .btn {
    flex: 1;
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 