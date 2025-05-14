<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || !isUserType('candidate')) {
    set_flash_message('error', 'Accès non autorisé.');
    redirect('/auth/login.php');
    exit;
}

// Vérifier si l'ID de la candidature est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'ID de candidature invalide.');
    redirect('/candidate/applications.php');
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $application_id = (int)$_GET['id'];
    $user_id = getUserId();

    // Récupérer les détails de la candidature
    $query = "
        SELECT 
            a.*,
            j.title as job_title, j.company_name, j.location, j.type as job_type,
            DATE_FORMAT(a.created_at, '%d/%m/%Y à %H:%i') as formatted_date,
            DATE_FORMAT(a.updated_at, '%d/%m/%Y à %H:%i') as response_date,
            CASE 
                WHEN a.status = 'pending' THEN 'En attente'
                WHEN a.status = 'reviewed' THEN 'En cours d\'examen'
                WHEN a.status = 'accepted' THEN 'Acceptée'
                WHEN a.status = 'rejected' THEN 'Refusée'
                ELSE a.status
            END as status_fr
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = :application_id AND a.candidate_id = :user_id
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        'application_id' => $application_id,
        'user_id' => $user_id
    ]);
    
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        set_flash_message('error', 'Candidature non trouvée ou accès non autorisé.');
        redirect('/candidate/applications.php');
        exit;
    }

    // Debug : afficher les informations de la candidature
    error_log("Application ID: " . $application_id);
    error_log("User ID: " . $user_id);
    error_log("Application data: " . print_r($application, true));

    // Marquer la notification comme lue si elle existe
    if (isset($_GET['notification']) && $_GET['notification'] == 1) {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE user_id = ? 
            AND link LIKE ? 
            AND read_at IS NULL
        ");
        $stmt->execute([
            $user_id, 
            "/candidate/view_application.php?id=" . $application_id
        ]);
    }

} catch (Exception $e) {
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
    redirect('/candidate/applications.php');
    exit;
}

$page_title = "Détails de la candidature";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="applications.php">Mes candidatures</a></li>
            <li class="breadcrumb-item active">Détails de la candidature</li>
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

    <div class="row">
        <div class="col-md-8">
            <!-- Informations sur la candidature -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de la candidature</h5>
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
                <div class="card-body">
                    <h6>Poste</h6>
                    <p>
                        <strong><?php echo htmlspecialchars($application['job_title']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($application['company_name']); ?></small>
                    </p>

                    <h6>Détails du poste</h6>
                    <p>
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($application['location']); ?><br>
                        <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($application['job_type']); ?>
                    </p>

                    <h6>Date de candidature</h6>
                    <p><?php echo $application['formatted_date']; ?></p>

                    <h6>Votre message</h6>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($application['message'] ?? 'Aucun message')); ?>
                        </div>
                    </div>

                    <?php if (!empty($application['feedback'])): ?>
                        <h6>Réponse du recruteur</h6>
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <p class="text-muted mb-2">
                                    <small>Réponse reçue le <?php echo $application['response_date']; ?></small>
                                </p>
                                <?php echo nl2br(htmlspecialchars($application['feedback'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <a href="/job-details.php?id=<?php echo $application['job_id']; ?>" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-eye"></i> Voir l'offre d'emploi
                    </a>
                    <a href="applications.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Retour aux candidatures
                    </a>
                </div>
            </div>

            <!-- Statut de la candidature -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Statut de la candidature</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-circle text-<?php 
                                echo match($application['status']) {
                                    'pending' => 'warning',
                                    'reviewed' => 'info',
                                    'accepted' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary'
                                };
                            ?>"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo $application['status_fr']; ?></h6>
                            <small class="text-muted">
                                Dernière mise à jour : <?php echo $application['response_date'] ?? $application['formatted_date']; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 