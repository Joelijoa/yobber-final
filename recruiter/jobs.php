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

    // Récupérer les offres d'emploi du recruteur
    $stmt = $conn->prepare("
        SELECT j.*, 
               COUNT(a.id) as application_count,
               DATE_FORMAT(j.created_at, '%d/%m/%Y') as posted_date,
               DATE_FORMAT(j.expiry_date, '%d/%m/%Y') as expiry_date_formatted
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.recruiter_id = :user_id
        GROUP BY j.id
        ORDER BY j.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    set_flash_message('error', 'Une erreur est survenue lors de la récupération des offres.');
    $jobs = [];
}

$page_title = "Mes offres d'emploi";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mes offres d'emploi</h2>
        <a href="create_job.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle offre
        </a>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore créé d'offres d'emploi.
            <a href="create_job.php" class="alert-link">Créer votre première offre</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Entreprise</th>
                        <th>Type</th>
                        <th>Localisation</th>
                        <th>Date de publication</th>
                        <th>Date d'expiration</th>
                        <th>Statut</th>
                        <th>Candidatures</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>
                                <a href="view_job.php?id=<?php echo $job['id']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($job['type']); ?></td>
                            <td><?php echo htmlspecialchars($job['location']); ?></td>
                            <td><?php echo $job['posted_date']; ?></td>
                            <td><?php echo $job['expiry_date_formatted']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($job['status']) {
                                        'active' => 'success',
                                        'draft' => 'warning',
                                        'closed' => 'secondary',
                                        default => 'primary'
                                    };
                                ?>">
                                    <?php echo $job['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="applications.php?job_id=<?php echo $job['id']; ?>">
                                    <?php echo $job['application_count']; ?> candidature(s)
                                </a>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($job['status'] === 'active'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#closeJobModal<?php echo $job['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteJobModal<?php echo $job['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <!-- Modal de fermeture d'offre -->
                                <div class="modal fade" id="closeJobModal<?php echo $job['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Fermer l'offre</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Êtes-vous sûr de vouloir fermer cette offre d'emploi ?
                                                <br>
                                                <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="close_job.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <button type="submit" class="btn btn-warning">Fermer l'offre</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de suppression -->
                                <div class="modal fade" id="deleteJobModal<?php echo $job['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Supprimer l'offre</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Êtes-vous sûr de vouloir supprimer cette offre d'emploi ?
                                                <br>
                                                <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                                <br>
                                                <small class="text-danger">
                                                    Cette action est irréversible et supprimera également toutes les candidatures associées.
                                                </small>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="delete_job.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
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