<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';
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

    // Vérifier si l'ID de l'offre est fourni
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        set_flash_message('error', 'ID de l\'offre invalide.');
        redirect('/recruiter/jobs.php');
        exit;
    }

    $job_id = (int)$_GET['id'];

    // Récupérer les informations de l'offre avec le nombre de candidatures
    $stmt = $conn->prepare("
        SELECT j.*,
               COUNT(a.id) as application_count,
               DATE_FORMAT(j.created_at, '%d/%m/%Y') as posted_date,
               DATE_FORMAT(j.expiry_date, '%d/%m/%Y') as expiry_date_formatted
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.id = :job_id AND j.recruiter_id = :user_id
        GROUP BY j.id
    ");
    $stmt->execute([
        'job_id' => $job_id,
        'user_id' => $user_id
    ]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        set_flash_message('error', 'Offre non trouvée ou accès non autorisé.');
        redirect('/recruiter/jobs.php');
        exit;
    }

} catch (Exception $e) {
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
    redirect('/recruiter/jobs.php');
    exit;
}

$page_title = $job['title'];
require_once __DIR__ . '/../includes/header.php';

// Générer un nouveau token CSRF
$csrf_token = generate_csrf_token();
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="jobs.php">Mes offres</a></li>
            <li class="breadcrumb-item active">Détails de l'offre</li>
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
        <h2><?php echo htmlspecialchars($job['title']); ?></h2>
        <div>
            <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <?php if ($job['status'] === 'active'): ?>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#closeJobModal">
                    <i class="fas fa-times"></i> Clôturer
                </button>
            <?php endif; ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteJobModal">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Informations sur le poste</h5>
                    <div class="mb-3">
                        <strong>Entreprise :</strong> <?php echo htmlspecialchars($job['company_name']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Localisation :</strong> <?php echo htmlspecialchars($job['location']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Type de contrat :</strong> <?php echo htmlspecialchars($job['type']); ?>
                    </div>
                    <?php if ($job['salary']): ?>
                        <div class="mb-3">
                            <strong>Salaire :</strong> <?php echo htmlspecialchars($job['salary']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <strong>Description :</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                    </div>
                    <div class="mb-4">
                        <strong>Prérequis :</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                    </div>
                    <?php if ($job['benefits']): ?>
                        <div class="mb-3">
                            <strong>Avantages :</strong>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($job['benefits'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Statut de l'offre</h5>
                    <div class="mb-3">
                        <span class="badge bg-<?php 
                            echo match($job['status']) {
                                'active' => 'success',
                                'draft' => 'warning',
                                'closed' => 'secondary',
                                default => 'primary'
                            };
                        ?> mb-2">
                            <?php echo $job['status']; ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Date de publication :</strong><br>
                        <?php echo $job['posted_date']; ?>
                    </div>
                    <?php if ($job['expiry_date']): ?>
                        <div class="mb-2">
                            <strong>Date d'expiration :</strong><br>
                            <?php echo $job['expiry_date_formatted']; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <strong>Candidatures reçues :</strong><br>
                        <a href="applications.php?job_id=<?php echo $job['id']; ?>">
                            <?php echo $job['application_count']; ?> candidature(s)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de fermeture d'offre -->
    <div class="modal fade" id="closeJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clôturer l'offre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir clôturer cette offre d'emploi ?
                    <br>
                    <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="close_job.php" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        <button type="submit" class="btn btn-warning">Clôturer l'offre</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de suppression -->
    <div class="modal fade" id="deleteJobModal" tabindex="-1">
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
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 