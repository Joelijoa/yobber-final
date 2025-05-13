<?php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || $_SESSION['user_type'] !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les offres favorites
$stmt = $conn->prepare("
    SELECT j.*, f.created_at as favorited_at,
           DATE_FORMAT(f.created_at, '%d/%m/%Y') as favorited_date
    FROM favorites f
    JOIN jobs j ON f.job_id = j.id
    WHERE f.candidate_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Mes Offres Favorites</h2>

    <?php if (empty($favorites)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore d'offres favorites.
            <a href="../jobs.php" class="alert-link">Parcourir les offres</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($favorites as $job): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo htmlspecialchars($job['company_name']); ?>
                            </h6>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($job['type']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($job['location']); ?></span>
                                <?php if ($job['salary']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($job['salary']); ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="card-text">
                                <?php 
                                    $description = htmlspecialchars($job['description']);
                                    echo strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description;
                                ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Ajouté le <?php echo $job['favorited_date']; ?>
                                </small>
                                <div>
                                    <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        Voir détails
                                    </a>
                                    <button type="button" 
                                            class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#removeFavoriteModal<?php echo $job['id']; ?>">
                                        Retirer
                                    </button>

                                    <!-- Modal de confirmation de suppression -->
                                    <div class="modal fade" id="removeFavoriteModal<?php echo $job['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Êtes-vous sûr de vouloir retirer cette offre de vos favoris ?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <form action="remove_favorite.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">Confirmer</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 