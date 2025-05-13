<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();

// Récupérer toutes les candidatures du candidat
$stmt = $conn->prepare("
    SELECT a.*, j.title as job_title, j.company_name, j.location, j.type as job_type,
           DATE_FORMAT(a.created_at, '%d/%m/%Y') as application_date
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.candidate_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Mes candidatures</h2>

    <?php if ($flash_message = get_flash_message()): ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>">
            <?php echo $flash_message['message']; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore postulé à des offres.
            <a href="/jobs.php" class="alert-link">Voir les offres disponibles</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Entreprise</th>
                        <th>Localisation</th>
                        <th>Type</th>
                        <th>Date de candidature</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td>
                                <a href="/job-details.php?id=<?php echo $application['job_id']; ?>">
                                    <?php echo htmlspecialchars($application['job_title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($application['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($application['location']); ?></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($application['job_type']); ?>
                                </span>
                            </td>
                            <td><?php echo $application['application_date']; ?></td>
                            <td>
                                <?php
                                $status_classes = [
                                    'pending' => 'bg-warning',
                                    'reviewed' => 'bg-info',
                                    'accepted' => 'bg-success',
                                    'rejected' => 'bg-danger'
                                ];
                                $status_labels = [
                                    'pending' => 'En attente',
                                    'reviewed' => 'En cours d\'examen',
                                    'accepted' => 'Acceptée',
                                    'rejected' => 'Refusée'
                                ];
                                ?>
                                <span class="badge <?php echo $status_classes[$application['status']]; ?>">
                                    <?php echo $status_labels[$application['status']]; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/uploads/applications/<?php echo $application['job_id']; ?>/<?php echo $user_id; ?>/<?php echo basename($application['cv_path']); ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-file-pdf"></i> CV
                                    </a>
                                    <a href="/uploads/applications/<?php echo $application['job_id']; ?>/<?php echo $user_id; ?>/<?php echo basename($application['cover_letter_path']); ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-file-alt"></i> LM
                                    </a>
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