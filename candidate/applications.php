<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || $_SESSION['user_type'] !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les candidatures du candidat
$stmt = $conn->prepare("
    SELECT a.*, j.title as job_title, j.company_name, j.location,
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
    WHERE a.candidate_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Mes Candidatures</h2>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore postulé à des offres d'emploi.
            <a href="../jobs.php" class="alert-link">Parcourir les offres</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Entreprise</th>
                        <th>Localisation</th>
                        <th>Date de candidature</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td>
                                <a href="../jobs/view.php?id=<?php echo $application['job_id']; ?>">
                                    <?php echo htmlspecialchars($application['job_title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($application['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($application['location']); ?></td>
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
                                    Voir détails
                                </a>
                                <?php if ($application['status'] === 'pending'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#withdrawModal<?php echo $application['id']; ?>">
                                        Retirer
                                    </button>

                                    <!-- Modal de confirmation de retrait -->
                                    <div class="modal fade" id="withdrawModal<?php echo $application['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmer le retrait</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Êtes-vous sûr de vouloir retirer votre candidature pour le poste de 
                                                    <strong><?php echo htmlspecialchars($application['job_title']); ?></strong> ?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <form action="withdraw_application.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">Confirmer le retrait</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 