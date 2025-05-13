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
$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

// Construire la requête de base
$query = "
    SELECT a.*, j.title as job_title, j.company_name,
           c.first_name, c.last_name, c.email, c.phone,
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
    JOIN candidates c ON a.candidate_id = c.user_id
    WHERE j.recruiter_id = ?
";

$params = [$user_id];

// Ajouter le filtre par offre si spécifié
if ($job_id) {
    $query .= " AND a.job_id = ?";
    $params[] = $job_id;
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
    WHERE recruiter_id = ? AND status = 'active'
    ORDER BY title
");
$stmt->execute([$user_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
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
                                    <?php if ($application['phone']): ?>
                                        <br><?php echo htmlspecialchars($application['phone']); ?>
                                    <?php endif; ?>
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
                                <div class="btn-group">
                                    <a href="view_application.php?id=<?php echo $application['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#updateStatusModal<?php echo $application['id']; ?>">
                                        <i class="fas fa-edit"></i> Statut
                                    </button>
                                </div>

                                <!-- Modal de mise à jour du statut -->
                                <div class="modal fade" id="updateStatusModal<?php echo $application['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Mettre à jour le statut</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="update_application_status.php" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="status" class="form-label">Nouveau statut</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="pending" <?php echo $application['status'] === 'pending' ? 'selected' : ''; ?>>
                                                                En attente
                                                            </option>
                                                            <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>
                                                                En cours d'examen
                                                            </option>
                                                            <option value="accepted" <?php echo $application['status'] === 'accepted' ? 'selected' : ''; ?>>
                                                                Acceptée
                                                            </option>
                                                            <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>
                                                                Refusée
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="feedback" class="form-label">Commentaire (optionnel)</label>
                                                        <textarea class="form-control" name="feedback" rows="3"><?php echo htmlspecialchars($application['feedback'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 