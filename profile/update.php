<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';

if (!isLoggedIn() || !isUserType('candidate')) {
    header('Location: /auth/login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Mise à jour des informations de base dans la table users
        $stmtUser = $conn->prepare("
            UPDATE users 
            SET first_name = ?,
                last_name = ?,
                address = ?,
                city = ?,
                country = ?
            WHERE id = ?
        ");
        $stmtUser->execute([
            $_POST['first_name'] ?? '',
            $_POST['last_name'] ?? '',
            $_POST['address'] ?? '',
            $_POST['city'] ?? '',
            $_POST['country'] ?? '',
            getUserId()
        ]);

        // Mise à jour du profil candidat
        $stmtProfile = $conn->prepare("
            UPDATE candidates 
            SET summary = ?,
                skills = ?,
                experience = ?,
                education = ?,
                cv_path = ?,
                linkedin_url = ?,
                github_url = ?,
                portfolio_url = ?,
                twitter_url = ?,
                availability_status = ?,
                preferred_job_type = ?
            WHERE user_id = ?
        ");
        $stmtProfile->execute([
            $_POST['summary'] ?? '',
            $_POST['skills'] ?? '',
            $_POST['experience'] ?? '',
            $_POST['education'] ?? '',
            $_POST['cv_path'] ?? '',
            $_POST['linkedin_url'] ?? '',
            $_POST['github_url'] ?? '',
            $_POST['portfolio_url'] ?? '',
            $_POST['twitter_url'] ?? '',
            $_POST['availability_status'] ?? 'available',
            $_POST['preferred_job_type'] ?? 'full-time',
            getUserId()
        ]);

        $conn->commit();
        $success = 'Profil mis à jour avec succès !';
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Erreur lors de la mise à jour du profil : ' . $e->getMessage();
        if (DEBUG_MODE) {
            error_log('Erreur mise à jour profil : ' . $e->getMessage());
        }
    }
}

// Récupération des données actuelles
$stmt = $conn->prepare("
    SELECT u.*, c.*
    FROM users u
    LEFT JOIN candidates c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->execute([getUserId()]);
$user = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Modifier mon profil</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <!-- Informations personnelles -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informations personnelles</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">Ville</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Pays</label>
                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Réseaux sociaux -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Réseaux sociaux</h5>
                <div class="mb-3">
                    <label for="linkedin_url" class="form-label">LinkedIn</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                        <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/in/votre-profil">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="github_url" class="form-label">GitHub</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-github"></i></span>
                        <input type="url" class="form-control" id="github_url" name="github_url" value="<?php echo htmlspecialchars($user['github_url'] ?? ''); ?>" placeholder="https://github.com/votre-profil">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="portfolio_url" class="form-label">Portfolio</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                        <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" value="<?php echo htmlspecialchars($user['portfolio_url'] ?? ''); ?>" placeholder="https://votre-portfolio.com">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="twitter_url" class="form-label">Twitter</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                        <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($user['twitter_url'] ?? ''); ?>" placeholder="https://twitter.com/votre-profil">
                    </div>
                </div>
            </div>
        </div>

        <!-- Statut professionnel -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Statut professionnel</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="availability_status" class="form-label">Disponibilité</label>
                        <select class="form-select" id="availability_status" name="availability_status">
                            <option value="available" <?php echo ($user['availability_status'] ?? '') === 'available' ? 'selected' : ''; ?>>Disponible immédiatement</option>
                            <option value="not_available" <?php echo ($user['availability_status'] ?? '') === 'not_available' ? 'selected' : ''; ?>>Non disponible</option>
                            <option value="open_to_offers" <?php echo ($user['availability_status'] ?? '') === 'open_to_offers' ? 'selected' : ''; ?>>Ouvert aux opportunités</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="preferred_job_type" class="form-label">Type de contrat recherché</label>
                        <select class="form-select" id="preferred_job_type" name="preferred_job_type">
                            <option value="full-time" <?php echo ($user['preferred_job_type'] ?? '') === 'full-time' ? 'selected' : ''; ?>>CDI</option>
                            <option value="part-time" <?php echo ($user['preferred_job_type'] ?? '') === 'part-time' ? 'selected' : ''; ?>>Temps partiel</option>
                            <option value="contract" <?php echo ($user['preferred_job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>CDD</option>
                            <option value="internship" <?php echo ($user['preferred_job_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Stage</option>
                            <option value="remote" <?php echo ($user['preferred_job_type'] ?? '') === 'remote' ? 'selected' : ''; ?>>Télétravail</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profil professionnel -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Profil professionnel</h5>
                <div class="mb-3">
                    <label for="summary" class="form-label">Résumé</label>
                    <textarea class="form-control" id="summary" name="summary" rows="4"><?php echo htmlspecialchars($user['summary'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="skills" class="form-label">Compétences</label>
                    <textarea class="form-control" id="skills" name="skills" rows="4"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="experience" class="form-label">Expérience professionnelle</label>
                    <textarea class="form-control" id="experience" name="experience" rows="4"><?php echo htmlspecialchars($user['experience'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="education" class="form-label">Formation</label>
                    <textarea class="form-control" id="education" name="education" rows="4"><?php echo htmlspecialchars($user['education'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Enregistrer les modifications
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 