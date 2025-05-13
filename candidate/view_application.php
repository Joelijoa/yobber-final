<?php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || $_SESSION['user_type'] !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: applications.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['id'];

// Récupérer les détails de la candidature
$stmt = $conn->prepare("
    SELECT a.*, j.title as job_title, j.company_name, j.location, j.description as job_description,
           j.requirements, j.salary, j.type as job_type,
           DATE_FORMAT(a.created_at, '%d/%m/%Y') as application_date,
           DATE_FORMAT(a.updated_at, '%d/%m/%Y') as last_update,
           CASE 
               WHEN a.status = 'pending' THEN 'En attente'
               WHEN a.status = 'reviewed' THEN 'En cours d\'examen'
               WHEN a.status = 'accepted' THEN 'Acceptée'
               WHEN a.status = 'rejected' THEN 'Refusée'
               ELSE a.status
           END as status_fr
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.id = ? AND a.candidate_id = ?
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: applications.php');
    exit();
}
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="applications.php">Mes candidatures</a></li>
            <li class="breadcrumb-item active">Détails de la candidature</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0"><?php echo htmlspecialchars($application['job_title']); ?></h3>
                </div>
                <div class="card-body">
                    <h5 class="card-subtitle mb-3"><?php echo htmlspecialchars($application['company_name']); ?></h5>
                    
                    <div class="mb-4">
                        <h6>Description du poste</h6>
                        <p><?php echo nl2br(htmlspecialchars($application['job_description'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h6>Prérequis</h6>
                        <p><?php echo nl2br(htmlspecialchars($application['requirements'])); ?></p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <strong>Type de contrat :</strong>
                            <p><?php echo htmlspecialchars($application['job_type']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Salaire :</strong>
                            <p><?php echo htmlspecialchars($application['salary']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Localisation :</strong>
                            <p><?php echo htmlspecialchars($application['location']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statut de la candidature</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Statut actuel :</strong>
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
                    </div>

                    <div class="mb-3">
                        <strong>Date de candidature :</strong>
                        <p><?php echo $application['application_date']; ?></p>
                    </div>

                    <div class="mb-3">
                        <strong>Dernière mise à jour :</strong>
                        <p><?php echo $application['last_update']; ?></p>
                    </div>

                    <?php if ($application['status'] === 'pending'): ?>
                        <button type="button" 
                                class="btn btn-danger w-100"
                                data-bs-toggle="modal" 
                                data-bs-target="#withdrawModal">
                            Retirer ma candidature
                        </button>

                        <!-- Modal de confirmation de retrait -->
                        <div class="modal fade" id="withdrawModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirmer le retrait</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Êtes-vous sûr de vouloir retirer votre candidature pour ce poste ?
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
                </div>
            </div>

            <?php if ($application['feedback']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Retour du recruteur</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($application['feedback'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 