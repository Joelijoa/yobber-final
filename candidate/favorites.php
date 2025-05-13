<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

// Vérifier que l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || !isUserType('candidate')) {
    set_flash_message('error', 'Accès non autorisé');
    redirect('/auth/login.php');
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Récupérer les offres favorites avec les détails
    $stmt = $conn->prepare("
        SELECT j.*, f.created_at as favorited_at, r.company_name, r.company_logo 
        FROM favorites f 
        JOIN jobs j ON f.job_id = j.id 
        LEFT JOIN recruiter_profiles r ON j.recruiter_id = r.user_id 
        WHERE f.candidate_id = ? 
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([getUserId()]);
    $favorites = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur récupération favoris : ' . $e->getMessage());
    $favorites = [];
}
?>

<div class="container py-5">
    <h1 class="mb-4">Mes offres favorites</h1>
    
    <?php if (empty($favorites)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Vous n'avez pas encore d'offres en favoris.
            <a href="/jobs.php" class="alert-link">Parcourir les offres</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($favorites as $job): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <a href="/job-details.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h5>
                                    <h6 class="text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                                </div>
                                <?php if ($job['company_logo']): ?>
                                    <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" 
                                         alt="Logo <?php echo htmlspecialchars($job['company_name']); ?>" 
                                         class="company-logo" 
                                         style="max-width: 50px; max-height: 50px;">
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($job['type']); ?></span>
                                <span class="ms-2"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            
                            <p class="card-text text-truncate">
                                <?php echo htmlspecialchars($job['description']); ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Ajouté aux favoris le <?php echo date('d/m/Y', strtotime($job['favorited_at'])); ?>
                                </small>
                                <button type="button" 
                                        class="btn btn-danger btn-sm"
                                        onclick="toggleFavorite(this)"
                                        data-job-id="<?php echo $job['id']; ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleFavorite(button) {
    const jobId = button.dataset.jobId;
    
    fetch('/favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `job_id=${jobId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Supprimer la carte de l'offre
            button.closest('.col-md-6').remove();
            
            // Si c'était la dernière offre, afficher le message "aucun favori"
            const remainingCards = document.querySelectorAll('.card').length;
            if (remainingCards === 0) {
                location.reload();
            }
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 