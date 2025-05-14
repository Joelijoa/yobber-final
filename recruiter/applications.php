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
    $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

    // Construire la requête de base
    $query = "
        SELECT a.*, j.title as job_title, j.company_name,
               u.first_name, u.last_name, u.email,
               DATE_FORMAT(a.created_at, '%d/%m/%Y') as application_date,
               CASE 
                   WHEN a.status = 'pending' THEN 'En attente'
                   WHEN a.status = 'reviewed' THEN 'En cours d\'examen'
                   WHEN a.status = 'accepted' THEN 'Acceptée'
                   WHEN a.status = 'rejected' THEN 'Refusée'
                   ELSE a.status
               END as status_fr
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.candidate_id = u.id
        WHERE j.recruiter_id = :user_id
    ";

    $params = ['user_id' => $user_id];

    // Ajouter le filtre par offre si spécifié
    if ($job_id) {
        $query .= " AND a.job_id = :job_id";
        $params['job_id'] = $job_id;
    }

    $query .= " ORDER BY a.created_at DESC";

    // Exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer la liste des offres pour le filtre
    $stmt = $conn->prepare("
        SELECT id, title 
        FROM jobs 
        WHERE recruiter_id = :user_id
        ORDER BY title
    ");
    $stmt->execute(['user_id' => $user_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
    redirect('/recruiter/dashboard.php');
    exit;
}

$page_title = "Gestion des candidatures";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item active">Candidatures</li>
        </ol>
    </nav>

    <?php 
    $flash_message = get_flash_message();
    if ($flash_message): 
    ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>">
            <?php echo htmlspecialchars($flash_message['message']); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des candidatures</h2>
        <?php if ($job_id): ?>
            <a href="applications.php" class="btn btn-outline-primary">
                <i class="fas fa-times"></i> Effacer le filtre
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtre par offre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="job_id" class="form-label">Filtrer par offre</label>
                    <select class="form-select" id="job_id" name="job_id" onchange="this.form.submit()">
                        <option value="">Toutes les offres</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" 
                                    <?php echo $job_id == $job['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            Aucune candidature trouvée.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Candidat</th>
                        <th>Offre</th>
                        <th>Date de candidature</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($application['email']); ?>
                                </small>
                            </td>
                            <td>
                                <a href="view_job.php?id=<?php echo $application['job_id']; ?>">
                                    <?php echo htmlspecialchars($application['job_title']); ?>
                                </a>
                                <br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($application['company_name']); ?>
                                </small>
                            </td>
                            <td><?php echo $application['application_date']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($application['status']) {
                                        'pending' => 'warning',
                                        'reviewed' => 'info',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo $application['status_fr']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_application.php?id=<?php echo $application['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 