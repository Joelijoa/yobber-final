<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Vérifier si l'ID de l'offre est fourni
if (!isset($_GET['id'])) {
    header('Location: jobs.php');
    exit();
}

$job_id = $_GET['id'];

// Récupérer les informations de l'offre
$stmt = $conn->prepare("
    SELECT * FROM jobs 
    WHERE id = ? AND recruiter_id = ?
");
$stmt->execute([$job_id, $user_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: jobs.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Validation des données
        $required_fields = ['title', 'company_name', 'location', 'type', 'description', 'requirements'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ " . $field . " est requis.");
            }
        }

        // Préparation des données
        $data = [
            'title' => $_POST['title'],
            'company_name' => $_POST['company_name'],
            'location' => $_POST['location'],
            'type' => $_POST['type'],
            'description' => $_POST['description'],
            'requirements' => $_POST['requirements'],
            'salary' => $_POST['salary'] ?? null,
            'benefits' => $_POST['benefits'] ?? null,
            'status' => $_POST['status'] ?? 'draft',
            'expiry_date' => $_POST['expiry_date'] ?? null,
            'job_id' => $job_id
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
            WHERE id = :job_id AND recruiter_id = ?
        ");
        $stmt->execute(array_merge($data, [$user_id]));

        $conn->commit();
        $success_message = "L'offre d'emploi a été mise à jour avec succès !";
        
        // Rafraîchir les données
        $stmt = $conn->prepare("
            SELECT * FROM jobs 
            WHERE id = ? AND recruiter_id = ?
        ");
        $stmt->execute([$job_id, $user_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Erreur lors de la mise à jour de l'offre : " . $e->getMessage();
    }
}
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

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre du poste *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($job['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nom de l'entreprise *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($job['company_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Localisation *</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($job['location']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type de contrat *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Sélectionner...</option>
                                <option value="CDI" <?php echo $job['type'] === 'CDI' ? 'selected' : ''; ?>>CDI</option>
                                <option value="CDD" <?php echo $job['type'] === 'CDD' ? 'selected' : ''; ?>>CDD</option>
                                <option value="Freelance" <?php echo $job['type'] === 'Freelance' ? 'selected' : ''; ?>>Freelance</option>
                                <option value="Stage" <?php echo $job['type'] === 'Stage' ? 'selected' : ''; ?>>Stage</option>
                                <option value="Alternance" <?php echo $job['type'] === 'Alternance' ? 'selected' : ''; ?>>Alternance</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="salary" class="form-label">Salaire</label>
                            <input type="text" class="form-control" id="salary" name="salary" 
                                   value="<?php echo htmlspecialchars($job['salary']); ?>"
                                   placeholder="Ex: 45K€ - 55K€">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Date d'expiration</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                   value="<?php echo $job['expiry_date']; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?php echo $job['status'] === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                                <option value="active" <?php echo $job['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Clôturée</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="benefits" class="form-label">Avantages</label>
                            <textarea class="form-control" id="benefits" name="benefits" rows="3"
                                      placeholder="Listez les avantages offerts (mutuelle, télétravail, etc.)"><?php echo htmlspecialchars($job['benefits']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description du poste *</label>
                    <textarea class="form-control" id="description" name="description" rows="6" required
                              placeholder="Décrivez le poste, les responsabilités, etc."><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="requirements" class="form-label">Prérequis *</label>
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