<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();
$success_message = '';
$error_message = '';

// Récupérer les informations du candidat
$stmt = $conn->prepare("
    SELECT cp.*, u.email, u.first_name, u.last_name 
    FROM candidate_profiles cp 
    JOIN users u ON cp.user_id = u.id 
    WHERE cp.user_id = ?
");
$stmt->execute([$user_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le profil n'existe pas encore, le créer
if (!$candidate) {
    $stmt = $conn->prepare("
        INSERT INTO candidate_profiles (user_id) 
        VALUES (?)
    ");
    $stmt->execute([$user_id]);
    
    // Récupérer les informations de base de l'utilisateur
    $stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Mise à jour des informations utilisateur
        $stmt = $conn->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $user_id
        ]);

        // Mise à jour des informations candidat
        $stmt = $conn->prepare("
            UPDATE candidate_profiles 
            SET phone = ?, address = ?, city = ?, country = ?,
                bio = ?, skills = ?, experience = ?, education = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $_POST['phone'],
            $_POST['address'],
            $_POST['city'],
            $_POST['country'],
            $_POST['bio'],
            $_POST['skills'],
            $_POST['experience'],
            $_POST['education'],
            $user_id
        ]);

        // Gestion du CV
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $cv_dir = __DIR__ . '/../uploads/cvs/';
            if (!file_exists($cv_dir)) {
                mkdir($cv_dir, 0777, true);
            }

            $cv_name = $user_id . '_' . time() . '_' . basename($_FILES['cv']['name']);
            $cv_path = $cv_dir . $cv_name;

            if (move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
                $stmt = $conn->prepare("UPDATE candidate_profiles SET cv_path = ? WHERE user_id = ?");
                $stmt->execute([$cv_name, $user_id]); // Stocker uniquement le nom du fichier
            }
        }

        $conn->commit();
        $success_message = "Profil mis à jour avec succès !";
        
        // Rafraîchir les données
        $stmt = $conn->prepare("
            SELECT cp.*, u.email, u.first_name, u.last_name 
            FROM candidate_profiles cp 
            JOIN users u ON cp.user_id = u.id 
            WHERE cp.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $conn->rollBack();
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
                                   value="<?php echo htmlspecialchars($candidate['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($candidate['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($candidate['email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($candidate['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Adresse</h4>
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($candidate['address'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($candidate['city'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="country" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($candidate['country'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h4>Profil Professionnel</h4>
                        <div class="mb-3">
                            <label for="bio" class="form-label">Biographie</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($candidate['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="skills" class="form-label">Compétences</label>
                            <textarea class="form-control" id="skills" name="skills" rows="3"><?php echo htmlspecialchars($candidate['skills'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Séparez vos compétences par des virgules</small>
                        </div>
                        <div class="mb-3">
                            <label for="experience" class="form-label">Expérience</label>
                            <textarea class="form-control" id="experience" name="experience" rows="4"><?php echo htmlspecialchars($candidate['experience'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="education" class="form-label">Formation</label>
                            <textarea class="form-control" id="education" name="education" rows="4"><?php echo htmlspecialchars($candidate['education'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cv" class="form-label">CV (PDF)</label>
                            <input type="file" class="form-control" id="cv" name="cv" accept=".pdf">
                            <?php if (!empty($candidate['cv_path'])): ?>
                                <small class="form-text text-muted">
                                    CV actuel : <?php echo htmlspecialchars($candidate['cv_path']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 