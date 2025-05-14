<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';

// Vérifier que l'utilisateur est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    set_flash_message('error', 'Accès non autorisé.');
    redirect('/auth/login.php');
    exit;
}

// Récupérer les informations du recruteur
$user_id = getUserId();
$stmt = $conn->prepare("
    SELECT u.*, r.* 
    FROM users u 
    LEFT JOIN recruiter_profiles r ON u.id = r.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $company_name = trim($_POST['company_name'] ?? '');
        $company_description = trim($_POST['company_description'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $industry = trim($_POST['industry'] ?? '');
        $company_size = trim($_POST['company_size'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (empty($company_name)) {
            throw new Exception('Le nom de l\'entreprise est requis.');
        }

        // Gestion du logo
        $logo_path = $profile['company_logo'] ?? null;
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_type = mime_content_type($_FILES['company_logo']['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Le logo doit être au format JPEG ou PNG.');
            }

            $upload_dir = __DIR__ . '/../uploads/logos';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('logo_') . '.' . $file_extension;
            $logo_path = 'uploads/logos/' . $new_filename;

            move_uploaded_file($_FILES['company_logo']['tmp_name'], __DIR__ . '/../' . $logo_path);
        }

        // Mise à jour du profil
        if ($profile['user_id']) {
            // Mettre à jour le profil existant
            $stmt = $conn->prepare("
                UPDATE recruiter_profiles 
                SET company_name = ?, company_description = ?, website = ?, 
                    industry = ?, company_size = ?, location = ?, company_logo = ?
                WHERE user_id = ?
            ");
        } else {
            // Créer un nouveau profil
            $stmt = $conn->prepare("
                INSERT INTO recruiter_profiles 
                (user_id, company_name, company_description, website, industry, company_size, location, company_logo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
        }

        $stmt->execute([
            $company_name,
            $company_description,
            $website,
            $industry,
            $company_size,
            $location,
            $logo_path,
            $user_id
        ]);

        set_flash_message('success', 'Profil mis à jour avec succès.');
        redirect('/recruiter/profile.php');
    } catch (Exception $e) {
        set_flash_message('error', $e->getMessage());
    }
}

$page_title = 'Profil Entreprise';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Profil Entreprise</h1>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="industry" class="form-label">Secteur d'activité</label>
                            <input type="text" class="form-control" id="industry" name="industry" value="<?php echo htmlspecialchars($profile['industry'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="company_size" class="form-label">Taille de l'entreprise</label>
                            <select class="form-select" id="company_size" name="company_size">
                                <option value="">Sélectionnez...</option>
                                <option value="1-10" <?php echo ($profile['company_size'] ?? '') === '1-10' ? 'selected' : ''; ?>>1-10 employés</option>
                                <option value="11-50" <?php echo ($profile['company_size'] ?? '') === '11-50' ? 'selected' : ''; ?>>11-50 employés</option>
                                <option value="51-200" <?php echo ($profile['company_size'] ?? '') === '51-200' ? 'selected' : ''; ?>>51-200 employés</option>
                                <option value="201-500" <?php echo ($profile['company_size'] ?? '') === '201-500' ? 'selected' : ''; ?>>201-500 employés</option>
                                <option value="501+" <?php echo ($profile['company_size'] ?? '') === '501+' ? 'selected' : ''; ?>>Plus de 500 employés</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Localisation</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="website" class="form-label">Site web</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="company_logo" class="form-label">Logo de l'entreprise</label>
                            <?php if (!empty($profile['company_logo'])): ?>
                                <div class="mb-2">
                                    <img src="/<?php echo htmlspecialchars($profile['company_logo']); ?>" alt="Logo actuel" class="img-thumbnail" style="max-width: 150px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/jpeg,image/png">
                            <div class="form-text">Format JPEG ou PNG, taille maximale : 2MB</div>
                        </div>

                        <div class="mb-3">
                            <label for="company_description" class="form-label">Description de l'entreprise</label>
                            <textarea class="form-control" id="company_description" name="company_description" rows="5"><?php echo htmlspecialchars($profile['company_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 