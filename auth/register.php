<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/public/index.php');
    exit;
}

// Détection du type d'inscription
$user_type = $_GET['type'] ?? '';
if (!in_array($user_type, ['candidate', 'recruiter'])) {
    redirect('/public/auth/register.php?type=candidate');
    exit;
}

// Si aucun type n'est spécifié, afficher la page de choix
if (empty($user_type)) {
    ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="mb-4">Choisissez votre type de compte</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="/public/auth/register.php?type=candidate" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-user-graduate me-2"></i>
                                    Je suis un candidat
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="/public/auth/register.php?type=recruiter" class="btn btn-secondary btn-lg w-100 mb-3">
                                    <i class="fas fa-building me-2"></i>
                                    Je suis un recruteur
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$errors = [];
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Début du traitement du formulaire d\'inscription');
    error_log('POST Data: ' . print_r($_POST, true));
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
    
    // Vérification simple du token CSRF
    if (!isset($_POST['csrf_token'])) {
        error_log('Erreur: Token CSRF manquant');
        $errors[] = "Requête invalide. Veuillez réessayer.";
    }
    
    if (empty($errors)) {
        try {
            if ($user_type === 'candidate') {
                error_log('Traitement inscription candidat');
                // Champs candidat
                $civility = $_POST['civility'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $first_name = $_POST['first_name'] ?? '';
                $birthdate = $_POST['birthdate'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $country = $_POST['country'] ?? '';
                $city = $_POST['city'] ?? '';
                $study_field = $_POST['study_field'] ?? '';
                $education_type = $_POST['education_type'] ?? '';
                $education_level = $_POST['education_level'] ?? '';
                // Validation
                if (!$civility) $errors[] = 'La civilité est requise';
                if (!$last_name) $errors[] = 'Le nom est requis';
                if (!$first_name) $errors[] = 'Le prénom est requis';
                if (!$birthdate) $errors[] = 'La date de naissance est requise';
                if (!$phone) $errors[] = 'Le numéro de téléphone est requis';
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
                if (!$password || strlen($password) < 8) $errors[] = 'Mot de passe requis (8 caractères min)';
                if (!$country) $errors[] = 'Le pays est requis';
                if (!$city) $errors[] = 'La ville est requise';
                if (!$study_field) $errors[] = 'Le domaine d\'études est requis';
                if (!$education_type) $errors[] = 'Le type de formation est requis';
                if (!$education_level) $errors[] = 'Le niveau d\'études est requis';
                // Enregistrement si pas d'erreur
                if (!$errors) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $errors[] = "Cet email est déjà utilisé.";
        } else {
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("INSERT INTO users (email, password, type, first_name, last_name, phone, country, city) 
                                VALUES (?, ?, 'candidate', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $first_name,
                $last_name,
                                $phone,
                                $country,
                                $city
                            ]);
                            $user_id = $pdo->lastInsertId();
                            
                            $stmt = $pdo->prepare("INSERT INTO candidates (user_id, education_level, education_type, study_field) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$user_id, $education_level, $education_type, $study_field]);
                            
                            // Générer et sauvegarder le code de vérification
                            $verification_code = random_int(10000, 99999);
                            error_log('Code de vérification généré: ' . $verification_code);
                            $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, code, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$user_id, $verification_code]);
                            
                            $pdo->commit();
                            error_log('Transaction réussie');
                            
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_type'] = 'candidate';
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                            
                            // Envoi de l'email avec le code
                            $to = $email;
                            $subject = "Vérification de votre compte JobPortal";
                            $message = "Bonjour $first_name,\n\n";
                            $message .= "Merci de vous être inscrit sur JobPortal. Votre code de vérification est : $verification_code\n\n";
                            $message .= "Veuillez entrer ce code sur la page de vérification pour activer votre compte.\n\n";
                            $message .= "Cordialement,\nL'équipe JobPortal";
                            $headers = "From: " . SMTP_FROM . "\r\n";
                            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                            
                            error_log("Tentative d'envoi d'email à : " . $email);
                            error_log("Code de vérification : " . $verification_code);
                            
                            if(mail($to, $subject, $message, $headers)) {
                                error_log("Email envoyé avec succès à " . $email);
                                // Redirection vers la page de vérification
                                redirect('/public/auth/verify.php?email=' . urlencode($email));
                            } else {
                                error_log("Échec de l'envoi de l'email à " . $email);
                                error_log("Erreur mail() : " . error_get_last()['message']);
                                // On redirige quand même vers la page de vérification
                                redirect('/public/auth/verify.php?email=' . urlencode($email));
                            }
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $errors[] = "Erreur lors de l'inscription : " . $e->getMessage();
                        }
                    }
                }
            } elseif ($user_type === 'recruiter') {
                // Champs entreprise
                $company_name = $_POST['company_name'] ?? '';
                $civility = $_POST['civility'] ?? '';
                $rep_last_name = $_POST['rep_last_name'] ?? '';
                $rep_first_name = $_POST['rep_first_name'] ?? '';
                $creation_date = $_POST['creation_date'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $country = $_POST['country'] ?? '';
                $city = $_POST['city'] ?? '';
                $legal_form = $_POST['legal_form'] ?? '';
                $company_type = $_POST['company_type'] ?? '';
                $activity_field = $_POST['activity_field'] ?? '';
                // Validation
                if (!$company_name) $errors[] = 'Le nom de la société est requis';
                if (!$civility) $errors[] = 'La civilité est requise';
                if (!$rep_last_name) $errors[] = 'Le nom du représentant est requis';
                if (!$rep_first_name) $errors[] = 'Le prénom du représentant est requis';
                if (!$creation_date) $errors[] = 'La date de création est requise';
                if (!$phone) $errors[] = 'Le numéro de téléphone est requis';
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
                if (!$password || strlen($password) < 8) $errors[] = 'Mot de passe requis (8 caractères min)';
                if (!$country) $errors[] = 'Le pays est requis';
                if (!$city) $errors[] = 'La ville est requise';
                if (!$legal_form) $errors[] = 'La forme juridique est requise';
                if (!$company_type) $errors[] = 'Le type de société est requis';
                if (!$activity_field) $errors[] = 'Le domaine d\'activité est requis';
                // Enregistrement si pas d'erreur
                if (!$errors) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $errors[] = "Cet email est déjà utilisé.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users (email, password, type, first_name, last_name, phone, country, city) 
                            VALUES (?, ?, 'recruiter', ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $email,
                            password_hash($password, PASSWORD_DEFAULT),
                            $rep_first_name,
                            $rep_last_name,
                            $phone,
                            $country,
                            $city
                        ]);
                        $user_id = $pdo->lastInsertId();
                        
                        $stmt = $pdo->prepare("INSERT INTO recruiter_profiles (user_id, company_name) VALUES (?, ?)");
                        $stmt->execute([$user_id, $company_name]);
                        
                        // Générer et sauvegarder le code de vérification
                        $verification_code = random_int(10000, 99999);
                        $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, code, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$user_id, $verification_code]);
                        
            $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_type'] = 'recruiter';
            $_SESSION['user_email'] = $email;
                        $_SESSION['user_name'] = $rep_first_name . ' ' . $rep_last_name;
                        
                        // Envoi de l'email avec le code
                        $to = $email;
                        $subject = "Vérification de votre compte JobPortal";
                        $message = "Bonjour $rep_first_name,\n\n";
                        $message .= "Merci de vous être inscrit sur JobPortal. Votre code de vérification est : $verification_code\n\n";
                        $message .= "Veuillez entrer ce code sur la page de vérification pour activer votre compte.\n\n";
                        $message .= "Cordialement,\nL'équipe JobPortal";
                        $headers = "From: " . SMTP_FROM . "\r\n";
                        
                        mail($to, $subject, $message, $headers);
                        
                        // Redirection vers la page de vérification
                        redirect('/public/auth/verify.php?email=' . urlencode($email));
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue : " . $e->getMessage();
            error_log("Erreur d'inscription : " . $e->getMessage());
        }
    }
}

// Affichage du formulaire approprié selon le type d'utilisateur
$page_title = ($user_type === 'candidate') ? "Inscription Candidat" : "Inscription Recruteur";
?>

<!-- Register Section -->
<section class="register-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center mb-4">
                            <?php echo $page_title; ?>
                        </h1>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="/public/auth/register.php?type=<?php echo htmlspecialchars($user_type); ?>" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                            
                            <?php if ($user_type === 'candidate'): ?>
                                <!-- Formulaire candidat -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Civilité *</label>
                                        <select class="form-select" name="civility" required>
                                            <option value="">Sélectionnez</option>
                                            <option value="M." <?php if(($_POST['civility'] ?? '')==='M.') echo 'selected'; ?>>M.</option>
                                            <option value="Mme" <?php if(($_POST['civility'] ?? '')==='Mme') echo 'selected'; ?>>Mme</option>
                                            <option value="Autre" <?php if(($_POST['civility'] ?? '')==='Autre') echo 'selected'; ?>>Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date de Naissance *</label>
                                        <input type="date" class="form-control" name="birthdate" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Numéro de téléphone *</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mot de passe *</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pays *</label>
                                        <input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ville *</label>
                                        <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Domaine d'études *</label>
                                        <input type="text" class="form-control" name="study_field" value="<?php echo htmlspecialchars($_POST['study_field'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type de formation *</label>
                                        <select class="form-select" name="education_type" required>
                                            <option value="">Sélectionnez</option>
                                            <option value="INIT" <?php if(($_POST['education_type'] ?? '')==='INIT') echo 'selected'; ?>>Formation initiale</option>
                                            <option value="CONT" <?php if(($_POST['education_type'] ?? '')==='CONT') echo 'selected'; ?>>Formation continue</option>
                                            <option value="ALT" <?php if(($_POST['education_type'] ?? '')==='ALT') echo 'selected'; ?>>Alternance</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Niveau d'études *</label>
                                        <select class="form-select" name="education_level" required>
                                            <option value="">Sélectionnez</option>
                                            <option value="BAC" <?php if(($_POST['education_level'] ?? '')==='BAC') echo 'selected'; ?>>Bac</option>
                                            <option value="BAC2" <?php if(($_POST['education_level'] ?? '')==='BAC2') echo 'selected'; ?>>Bac+2</option>
                                            <option value="BAC3" <?php if(($_POST['education_level'] ?? '')==='BAC3') echo 'selected'; ?>>Bac+3</option>
                                            <option value="BAC5" <?php if(($_POST['education_level'] ?? '')==='BAC5') echo 'selected'; ?>>Bac+5</option>
                                        </select>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Formulaire recruteur -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom de la société *</label>
                                        <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Civilité *</label>
                                        <select class="form-select" name="civility" required>
                                            <option value="">Sélectionnez</option>
                                            <option value="M." <?php if(($_POST['civility'] ?? '')==='M.') echo 'selected'; ?>>M.</option>
                                            <option value="Mme" <?php if(($_POST['civility'] ?? '')==='Mme') echo 'selected'; ?>>Mme</option>
                                            <option value="Autre" <?php if(($_POST['civility'] ?? '')==='Autre') echo 'selected'; ?>>Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nom du représentant *</label>
                                        <input type="text" class="form-control" name="rep_last_name" value="<?php echo htmlspecialchars($_POST['rep_last_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prénom du représentant *</label>
                                        <input type="text" class="form-control" name="rep_first_name" value="<?php echo htmlspecialchars($_POST['rep_first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date de création *</label>
                                        <input type="date" class="form-control" name="creation_date" value="<?php echo htmlspecialchars($_POST['creation_date'] ?? ''); ?>" required>
                                </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Numéro de téléphone *</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                            </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                                <div class="col-md-6">
                                        <label class="form-label">Mot de passe *</label>
                                        <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="col-md-6">
                                        <label class="form-label">Pays *</label>
                                        <input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>" required>
                                </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ville *</label>
                                        <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                            </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Forme juridique *</label>
                                        <input type="text" class="form-control" name="legal_form" value="<?php echo htmlspecialchars($_POST['legal_form'] ?? ''); ?>" required>
                            </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type de société *</label>
                                        <input type="text" class="form-control" name="company_type" value="<?php echo htmlspecialchars($_POST['company_type'] ?? ''); ?>" required>
                            </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Domaine d'activité *</label>
                                        <input type="text" class="form-control" name="activity_field" value="<?php echo htmlspecialchars($_POST['activity_field'] ?? ''); ?>" required>
                            </div>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>S'inscrire
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0">
                                Déjà inscrit ?
                                <a href="/public/auth/login.php" class="text-decoration-none">Connectez-vous</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.register-section {
    min-height: calc(100vh - 200px);
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
</style>

<script>
// Aucun JavaScript pour la manipulation des formulaires
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 