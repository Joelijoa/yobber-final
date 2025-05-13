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
            'recruiter_id' => $user_id
        ];

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
        $success_message = "L'offre d'emploi a été créée avec succès !";
        
        // Redirection vers la liste des offres
        header('Location: jobs.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Erreur lors de la création de l'offre : " . $e->getMessage();
    }
}
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
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nom de l'entreprise *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Localisation *</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type de contrat *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Sélectionner...</option>
                                <option value="CDI">CDI</option>
                                <option value="CDD">CDD</option>
                                <option value="Freelance">Freelance</option>
                                <option value="Stage">Stage</option>
                                <option value="Alternance">Alternance</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="salary" class="form-label">Salaire</label>
                            <input type="text" class="form-control" id="salary" name="salary" 
                                   placeholder="Ex: 45K€ - 55K€">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Date d'expiration</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft">Brouillon</option>
                                <option value="active">Active</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="benefits" class="form-label">Avantages</label>
                            <textarea class="form-control" id="benefits" name="benefits" rows="3"
                                      placeholder="Listez les avantages offerts (mutuelle, télétravail, etc.)"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description du poste *</label>
                    <textarea class="form-control" id="description" name="description" rows="6" required
                              placeholder="Décrivez le poste, les responsabilités, etc."></textarea>
                </div>

                <div class="mb-3">
                    <label for="requirements" class="form-label">Prérequis *</label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="4" required
                              placeholder="Listez les compétences et qualifications requises"></textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Publier l'offre</button>
                    <button type="submit" name="status" value="draft" class="btn btn-secondary">
                        Enregistrer en brouillon
                    </button>
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