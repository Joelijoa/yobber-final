<?php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'ID de la candidature est fourni
if (!isset($_GET['id'])) {
    header('Location: applications.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['id'];

// Récupérer les détails de la candidature
$stmt = $conn->prepare("
    SELECT a.*, j.title as job_title, j.company_name, j.description as job_description,
           c.first_name, c.last_name, c.email, c.phone, c.cv_path,
           DATE_FORMAT(a.created_at, '%d/%m/%Y') as application_date,
           DATE_FORMAT(a.updated_at, '%d/%m/%Y') as last_update_date,
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
    WHERE a.id = ? AND j.recruiter_id = ?
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: applications.php');
    exit();
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Détails de la candidature</h2>
        <a href="applications.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Retour aux candidatures
        </a>
    </div>

    <div class="row">
        <!-- Informations du candidat -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations du candidat</h5>
                </div>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h4>
                    <p class="text-muted">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($application['email']); ?><br>
                        <?php if ($application['phone']): ?>
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($application['phone']); ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($application['cv_path']): ?>
                        <a href="../uploads/cv/<?php echo htmlspecialchars($application['cv_path']); ?>" 
                           class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> Voir le CV
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informations de la candidature -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations de la candidature</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Offre :</strong><br>
                        <a href="view_job.php?id=<?php echo $application['job_id']; ?>">
                            <?php echo htmlspecialchars($application['job_title']); ?>
                        </a>
                        <br>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($application['company_name']); ?>
                        </small>
                    </p>
                    <p>
                        <strong>Date de candidature :</strong><br>
                        <?php echo $application['application_date']; ?>
                    </p>
                    <p>
                        <strong>Statut actuel :</strong><br>
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
                    </p>
                    <?php if ($application['last_update_date']): ?>
                        <p>
                            <strong>Dernière mise à jour :</strong><br>
                            <?php echo $application['last_update_date']; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Description du poste -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Description du poste</h5>
        </div>
        <div class="card-body">
            <?php echo nl2br(htmlspecialchars($application['job_description'])); ?>
        </div>
    </div>

    <!-- Lettre de motivation -->
    <?php if ($application['cover_letter']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Lettre de motivation</h5>
            </div>
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Feedback -->
    <?php if ($application['feedback']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Feedback</h5>
            </div>
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($application['feedback'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Actions</h5>
        </div>
        <div class="card-body">
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal" 
                    data-bs-target="#updateStatusModal">
                <i class="fas fa-edit"></i> Mettre à jour le statut
            </button>
        </div>
    </div>

    <!-- Modal de mise à jour du statut -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
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
</div>

<?php require_once '../includes/footer.php'; ?> 