<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();

// Récupérer les favoris du candidat
$stmt = $conn->prepare("
    SELECT j.*, f.created_at as favorited_at,
           DATE_FORMAT(j.created_at, '%d/%m/%Y') as posted_date,
           DATE_FORMAT(f.created_at, '%d/%m/%Y') as favorite_date
    FROM favorites f
    JOIN jobs j ON f.job_id = j.id
    WHERE f.candidate_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Mes offres favorites</h2>

    <?php if ($flash_message = get_flash_message()): ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>">
            <?php echo $flash_message['message']; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($favorites)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore d'offres en favoris.
            <a href="/jobs.php" class="alert-link">Parcourir les offres</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($favorites as $job): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/job-details.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo htmlspecialchars($job['company_name']); ?>
                            </h6>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?><br>
                                <i class="fas fa-clock"></i> <?php echo htmlspecialchars($job['type']); ?><br>
                                <i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary']); ?>
                            </p>
                            <div class="card-text mb-3">
                                <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 150))); ?>...
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Ajouté aux favoris le <?php echo $job['favorite_date']; ?>
                                </small>
                                <div class="btn-group">
                                    <a href="../job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary">
                                        Voir détails
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger favorite-btn" 
                                            data-job-id="<?php echo $job['id']; ?>"
                                            onclick="toggleFavorite(this)">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
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
    
    fetch('../favorite.php', {
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
            
            // Si plus d'offres, afficher le message
            const jobCards = document.querySelectorAll('.col-md-6');
            if (jobCards.length === 0) {
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