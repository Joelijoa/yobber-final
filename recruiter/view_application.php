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

// Vérifier si l'ID de la candidature est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'ID de candidature invalide.');
    redirect('/recruiter/applications.php');
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
            j.title as job_title, j.company_name,
            u.first_name, u.last_name, u.email, u.phone,
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
        JOIN users u ON a.candidate_id = u.id
        WHERE a.id = :application_id AND j.recruiter_id = :user_id
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        'application_id' => $application_id,
        'user_id' => $user_id
    ]);
    
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
        set_flash_message('error', 'Candidature non trouvée ou accès non autorisé.');
        redirect('/recruiter/applications.php');
        exit;
    }

    // Traiter la réponse à la candidature
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        try {
            $conn->beginTransaction();

            $new_status = $_POST['action'] === 'accept' ? 'accepted' : 'rejected';
            $feedback = $_POST['feedback'] ?? '';
            
            // Mettre à jour le statut et le feedback
            $stmt = $conn->prepare("
                UPDATE applications 
                SET status = :status, 
                    feedback = :feedback,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'status' => $new_status,
                'feedback' => $feedback,
                'id' => $application_id
            ]);

            // Créer une notification pour le candidat
            $notification_message = $new_status === 'accepted' 
                ? "Votre candidature pour le poste \"{$application['job_title']}\" a été acceptée. Consultez les détails pour plus d'informations."
                : "Votre candidature pour le poste \"{$application['job_title']}\" n'a pas été retenue. Consultez les détails pour plus d'informations.";

            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, link, created_at)
                VALUES (:user_id, 'application_response', :message, :link, NOW())
            ");

            $stmt->execute([
                'user_id' => $application['candidate_id'],
                'message' => $notification_message,
                'link' => "/candidate/view_application.php?id=" . $application_id
            ]);

            $conn->commit();

            set_flash_message('success', 'La réponse a été envoyée au candidat.');
            redirect("/recruiter/view_application.php?id=" . $application_id);
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
            redirect("/recruiter/view_application.php?id=" . $application_id);
            exit;
        }
    }

} catch (Exception $e) {
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
    redirect('/recruiter/applications.php');
    exit;
}

$page_title = "Détails de la candidature";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="applications.php">Candidatures</a></li>
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

                    <h6>Date de candidature</h6>
                    <p><?php echo $application['formatted_date']; ?></p>

                    <h6>Message du candidat</h6>
                    <div class="card bg-light mb-3">
        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($application['message'] ?? 'Aucun message')); ?>
        </div>
    </div>

                    <?php if (!empty($application['feedback'])): ?>
                        <h6>Votre réponse</h6>
                        <div class="card bg-light mb-3">
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($application['feedback'])); ?>
            </div>
        </div>
    <?php endif; ?>
        </div>
    </div>

            <!-- Formulaire de réponse -->
            <?php if ($application['status'] === 'pending' || $application['status'] === 'reviewed'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Répondre à la candidature</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="respond_to_application.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        <div class="mb-3">
                            <label for="feedback" class="form-label">Votre message au candidat</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="5" required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="accept" class="btn btn-success">
                                <i class="fas fa-check"></i> Accepter pour un entretien
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                <i class="fas fa-times"></i> Refuser la candidature
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Informations sur le candidat -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations du candidat</h5>
                </div>
                <div class="card-body">
                    <h6><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h6>
                    <p>
                        <i class="fas fa-envelope"></i> 
                        <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>">
                            <?php echo htmlspecialchars($application['email']); ?>
                        </a>
                    </p>
                    <?php if (!empty($application['phone'])): ?>
                    <p>
                        <i class="fas fa-phone"></i> 
                        <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>">
                            <?php echo htmlspecialchars($application['phone']); ?>
                        </a>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($application['linkedin_url'])): ?>
                    <p>
                        <i class="fab fa-linkedin"></i> 
                        <a href="<?php echo htmlspecialchars($application['linkedin_url']); ?>" target="_blank">
                            Profil LinkedIn
                        </a>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($application['portfolio_url'])): ?>
                    <p>
                        <i class="fas fa-globe"></i> 
                        <a href="<?php echo htmlspecialchars($application['portfolio_url']); ?>" target="_blank">
                            Portfolio
                        </a>
                    </p>
                    <?php endif; ?>

                    <!-- Documents -->
                    <div class="mt-4">
                        <h6>Documents</h6>
                        <?php if (!empty($application['cv_path'])): ?>
                            <p>
                                <a href="/public/api/get-cv.php?id=<?php echo $application_id; ?>" 
                                   class="btn btn-outline-primary btn-sm" 
                                   target="_blank">
                                    <i class="fas fa-file-pdf"></i> CV
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($application['cover_letter_path'])): ?>
                            <p>
                                <a href="/public/api/get-cover-letter.php?id=<?php echo $application_id; ?>" 
                                   class="btn btn-outline-primary btn-sm" 
                                   target="_blank">
                                    <i class="fas fa-file-alt"></i> Lettre de motivation
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 