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

// Initialiser la connexion à la base de données
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (PDOException $e) {
    set_flash_message('error', 'Erreur de connexion à la base de données.');
    redirect('/recruiter/jobs.php');
    exit;
}

$user_id = getUserId();
$error_message = '';

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
            'recruiter_id' => $user_id
        ];

        $conn->beginTransaction();

        // Insertion de l'offre
        $stmt = $conn->prepare("
            INSERT INTO jobs (
                title, company_name, location, type, description, requirements,
                salary, benefits, status, expiry_date, recruiter_id, created_at
            ) VALUES (
                :title, :company_name, :location, :type, :description, :requirements,
                :salary, :benefits, :status, :expiry_date, :recruiter_id, NOW()
            )
        ");
        
        $stmt->execute($data);
        $conn->commit();
        
        set_flash_message('success', "L'offre d'emploi a été créée avec succès !");
        redirect('/recruiter/jobs.php');
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

$page_title = 'Créer une nouvelle offre';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="jobs.php">Mes offres</a></li>
            <li class="breadcrumb-item active">Nouvelle offre</li>
        </ol>
    </nav>

    <h2>Créer une nouvelle offre d'emploi</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Titre du poste <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="company_name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Localisation <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Type de contrat <span class="text-danger">*</span></label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Sélectionner...</option>
                        <?php
                        $contract_types = ['CDI', 'CDD', 'Freelance', 'Stage', 'Alternance'];
                        foreach ($contract_types as $type) {
                            $selected = (isset($_POST['type']) && $_POST['type'] === $type) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($type) . "\" $selected>" . htmlspecialchars($type) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="salary" class="form-label">Salaire</label>
                    <input type="text" class="form-control" id="salary" name="salary" value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>" placeholder="Ex: 45K€ - 55K€">
                </div>

                <div class="mb-3">
                    <label for="expiry_date" class="form-label">Date d'expiration</label>
                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo isset($_POST['expiry_date']) ? htmlspecialchars($_POST['expiry_date']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="benefits" class="form-label">Avantages</label>
                    <textarea class="form-control" id="benefits" name="benefits" rows="3" placeholder="Listez les avantages offerts (mutuelle, télétravail, etc.)"><?php echo isset($_POST['benefits']) ? htmlspecialchars($_POST['benefits']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description du poste <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="6" required placeholder="Décrivez le poste, les responsabilités, etc."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="requirements" class="form-label">Prérequis <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="4" required placeholder="Listez les compétences et qualifications requises"><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Publier l'offre</button>
                    <button type="submit" name="status" value="draft" class="btn btn-secondary">Enregistrer en brouillon</button>
                    <a href="jobs.php" class="btn btn-link">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 