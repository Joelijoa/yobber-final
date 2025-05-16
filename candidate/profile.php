<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();
$success_message = '';
$error_message = '';

// Vérifier d'abord si le candidat existe déjà
$stmt = $conn->prepare("SELECT 1 FROM candidates WHERE user_id = ?");
$stmt->execute([$user_id]);
$candidate_exists = $stmt->fetchColumn();

// Si le candidat n'existe pas, créer son profil
if (!$candidate_exists) {
    $stmt = $conn->prepare("
        INSERT INTO candidates (
            user_id, summary, skills, experience, education, location,
            linkedin_url, github_url, portfolio_url, twitter_url
        ) VALUES (
            ?, '', '', '', '', '', '', '', '', ''
        )
    ");
    $stmt->execute([$user_id]);
}

// Récupérer le profil complet
$stmt = $conn->prepare("
    SELECT u.*, c.*
    FROM users u
    LEFT JOIN candidates c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mise à jour des informations utilisateur
        $stmt = $conn->prepare("
            UPDATE users SET
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?,
            address = ?,
            city = ?,
            country = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['city'],
            $_POST['country'],
            $user_id
        ]);

        // Mise à jour du profil candidat
        $stmt = $conn->prepare("
            UPDATE candidates SET
            summary = ?,
            skills = ?,
            experience = ?,
            education = ?,
            location = ?,
            address= ?,
            linkedin_url = ?,
            github_url = ?,
            portfolio_url = ?,
            twitter_url = ?
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $_POST['bio'] ?? '',
            $_POST['skills'] ?? '',
            $_POST['experience'] ?? '',
            $_POST['education'] ?? '',
            $_POST['address'] ?? '',
            $_POST['location'] ?? '',
            $_POST['linkedin_url'] ?? '',
            $_POST['github_url'] ?? '',
            $_POST['portfolio_url'] ?? '',
            $_POST['twitter_url'] ?? '',
            $user_id
        ]);

        // Gestion du CV
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $cv_path = handleFileUpload($_FILES['cv'], 'cv');
            if ($cv_path) {
                $stmt = $conn->prepare("UPDATE candidates SET cv_path = ? WHERE user_id = ?");
                $stmt->execute([$cv_path, $user_id]);
            }
        }

        $success_message = "Profil mis à jour avec succès !";
        
        // Récupérer le profil mis à jour
        $stmt = $conn->prepare("
            SELECT u.*, c.*
            FROM users u
            LEFT JOIN candidates c ON u.id = c.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();

    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h2>Mon Profil</h2>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Informations Personnelles</h4>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Adresse</h4>
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="country" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($profile['country'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h4>Profil Professionnel</h4>
                        <div class="mb-3">
                            <label for="bio" class="form-label">Biographie</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($profile['summary'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="skills" class="form-label">Compétences</label>
                            <textarea class="form-control" id="skills" name="skills" rows="3"><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Séparez vos compétences par des virgules</small>
                        </div>
                        <div class="mb-3">
                            <label for="experience" class="form-label">Expérience</label>
                            <textarea class="form-control" id="experience" name="experience" rows="4"><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="education" class="form-label">Formation</label>
                            <textarea class="form-control" id="education" name="education" rows="4"><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Localisation souhaitée</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="cv" class="form-label">CV (PDF)</label>
                            <input type="file" class="form-control" id="cv" name="cv" accept=".pdf">
                            <?php if (!empty($profile['cv_path'])): ?>
                                <small class="form-text text-muted">
                                    CV actuel : <?php echo htmlspecialchars($profile['cv_path']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h4>Réseaux Sociaux</h4>
                        <div class="mb-3">
                            <label for="linkedin_url" class="form-label">LinkedIn</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                       value="<?php echo htmlspecialchars($profile['linkedin_url'] ?? ''); ?>" 
                                       placeholder="https://linkedin.com/in/votre-profil">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="github_url" class="form-label">GitHub</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-github"></i></span>
                                <input type="url" class="form-control" id="github_url" name="github_url" 
                                       value="<?php echo htmlspecialchars($profile['github_url'] ?? ''); ?>" 
                                       placeholder="https://github.com/votre-profil">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="portfolio_url" class="form-label">Portfolio</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" 
                                       value="<?php echo htmlspecialchars($profile['portfolio_url'] ?? ''); ?>" 
                                       placeholder="https://votre-portfolio.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="twitter_url" class="form-label">Twitter</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                                       value="<?php echo htmlspecialchars($profile['twitter_url'] ?? ''); ?>" 
                                       placeholder="https://twitter.com/votre-profil">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 