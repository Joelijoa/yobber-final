<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    set_flash_message('error', 'Accès non autorisé.');
    redirect('/auth/login.php');
    exit;
}

try {
    // Initialiser la connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();
    
    $user_id = getUserId();

    // Vérifier si l'ID de l'offre est fourni
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        set_flash_message('error', 'ID de l\'offre invalide.');
        redirect('/recruiter/jobs.php');
        exit;
    }

    $job_id = (int)$_GET['id'];

    // Récupérer les informations de l'offre
    $stmt = $conn->prepare("
        SELECT * FROM jobs 
        WHERE id = :job_id AND recruiter_id = :user_id
    ");
    $stmt->execute([
        'job_id' => $job_id,
        'user_id' => $user_id
    ]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        set_flash_message('error', 'Offre non trouvée ou accès non autorisé.');
        redirect('/recruiter/jobs.php');
        exit;
    }

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validation des données
            $required_fields = ['title', 'company_name', 'location', 'type', 'description', 'requirements'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                throw new Exception("Les champs suivants sont requis : " . implode(', ', $missing_fields));
            }

            $conn->beginTransaction();

            // Préparation des données
            $data = [
                'title' => $_POST['title'],
                'company_name' => $_POST['company_name'],
                'location' => $_POST['location'],
                'type' => $_POST['type'],
                'description' => $_POST['description'],
                'requirements' => $_POST['requirements'],
                'salary' => !empty($_POST['salary']) ? $_POST['salary'] : null,
                'benefits' => !empty($_POST['benefits']) ? $_POST['benefits'] : null,
                'status' => isset($_POST['status']) ? $_POST['status'] : 'draft',
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'job_id' => $job_id,
                'user_id' => $user_id
            ];

            // Mise à jour de l'offre
            $stmt = $conn->prepare("
                UPDATE jobs SET
                    title = :title,
                    company_name = :company_name,
                    location = :location,
                    type = :type,
                    description = :description,
                    requirements = :requirements,
                    salary = :salary,
                    benefits = :benefits,
                    status = :status,
                    expiry_date = :expiry_date,
                    updated_at = NOW()
                WHERE id = :job_id AND recruiter_id = :user_id
            ");
            $stmt->execute($data);

            $conn->commit();
            set_flash_message('success', "L'offre d'emploi a été mise à jour avec succès !");
            
            // Rafraîchir les données
            $stmt = $conn->prepare("
                SELECT * FROM jobs 
                WHERE id = :job_id AND recruiter_id = :user_id
            ");
            $stmt->execute([
                'job_id' => $job_id,
                'user_id' => $user_id
            ]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            set_flash_message('error', "Une erreur est survenue lors de la mise à jour de l'offre : " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
    redirect('/recruiter/jobs.php');
    exit;
}

$page_title = "Modifier l'offre";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="jobs.php">Mes offres</a></li>
            <li class="breadcrumb-item active">Modifier l'offre</li>
        </ol>
    </nav>

    <h2>Modifier l'offre d'emploi</h2>

    <?php 
    $flash_message = get_flash_message();
    if ($flash_message): 
    ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>">
            <?php echo htmlspecialchars($flash_message['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $job_id); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre du poste <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($job['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($job['company_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Localisation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($job['location']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type de contrat <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Sélectionner...</option>
                                <?php
                                $contract_types = ['CDI', 'CDD', 'Freelance', 'Stage', 'Alternance'];
                                foreach ($contract_types as $type) {
                                    $selected = ($job['type'] === $type) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($type) . "\" $selected>" . htmlspecialchars($type) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="salary" class="form-label">Salaire</label>
                            <input type="text" class="form-control" id="salary" name="salary" 
                                   value="<?php echo htmlspecialchars($job['salary'] ?? ''); ?>"
                                   placeholder="Ex: 45K€ - 55K€">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Date d'expiration</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                   value="<?php echo htmlspecialchars($job['expiry_date'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?php echo ($job['status'] === 'draft') ? 'selected' : ''; ?>>Brouillon</option>
                                <option value="active" <?php echo ($job['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="closed" <?php echo ($job['status'] === 'closed') ? 'selected' : ''; ?>>Clôturée</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="benefits" class="form-label">Avantages</label>
                            <textarea class="form-control" id="benefits" name="benefits" rows="3"
                                      placeholder="Listez les avantages offerts (mutuelle, télétravail, etc.)"><?php echo htmlspecialchars($job['benefits'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description du poste <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="6" required
                              placeholder="Décrivez le poste, les responsabilités, etc."><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="requirements" class="form-label">Prérequis <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="4" required
                              placeholder="Listez les compétences et qualifications requises"><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="jobs.php" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 