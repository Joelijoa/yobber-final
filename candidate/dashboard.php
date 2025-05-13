<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();

// Récupérer les informations de l'utilisateur et son profil
$stmt = $conn->prepare("
    SELECT u.*, cp.* 
    FROM users u 
    LEFT JOIN candidate_profiles cp ON u.id = cp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Nombre de candidatures
$stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE candidate_id = ?");
$stmt->execute([$user_id]);
$applications_count = $stmt->fetchColumn();

// Nombre de favoris
$stmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE candidate_id = ?");
$stmt->execute([$user_id]);
$favorites_count = $stmt->fetchColumn();

// Nombre de notifications non lues
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$notifications_count = $stmt->fetchColumn();

// Dernières candidatures
$stmt = $conn->prepare("
    SELECT a.*, j.title, j.location, j.type as job_type, j.id as job_id 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.candidate_id = ? 
    ORDER BY a.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_applications = $stmt->fetchAll();

// Derniers favoris
$stmt = $conn->prepare("
    SELECT f.*, j.title, j.location, j.type as job_type, j.id as job_id 
    FROM favorites f 
    JOIN jobs j ON f.job_id = j.id 
    WHERE f.candidate_id = ? 
    ORDER BY f.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_favorites = $stmt->fetchAll();
?>

<section class="dashboard-section py-5">
    <div class="container">
        <h1 class="mb-4">Tableau de bord candidat</h1>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-paper-plane fa-2x text-primary mb-2"></i>
                        <h3 class="h5">Candidatures</h3>
                        <p class="display-6 fw-bold mb-0"><?php echo $applications_count; ?></p>
                        <a href="applications/" class="btn btn-outline-primary btn-sm mt-2">Voir mes candidatures</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                        <h3 class="h5">Favoris</h3>
                        <p class="display-6 fw-bold mb-0"><?php echo $favorites_count; ?></p>
                        <a href="favorites/" class="btn btn-outline-danger btn-sm mt-2">Voir mes favoris</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                        <h3 class="h5">Notifications</h3>
                        <p class="display-6 fw-bold mb-0"><?php echo $notifications_count; ?></p>
                        <a href="notifications/" class="btn btn-outline-warning btn-sm mt-2">Voir mes notifications</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-paper-plane me-2"></i>Dernières candidatures
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_applications)): ?>
                            <p class="text-muted mb-0">Aucune candidature récente.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_applications as $app): ?>
                                    <li class="list-group-item">
                                        <a href="../public/job-details.php?id=<?php echo $app['job_id']; ?>" class="fw-bold text-decoration-none"><?php echo htmlspecialchars($app['title']); ?></a>
                                        <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($app['job_type']); ?></span>
                                        <span class="text-muted ms-2"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location']); ?></span>
                                        <span class="float-end text-muted small">le <?php echo date('d/m/Y', strtotime($app['created_at'])); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-heart me-2"></i>Derniers favoris
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_favorites)): ?>
                            <p class="text-muted mb-0">Aucun favori récent.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_favorites as $fav): ?>
                                    <li class="list-group-item">
                                        <a href="../public/job-details.php?id=<?php echo $fav['job_id']; ?>" class="fw-bold text-decoration-none"><?php echo htmlspecialchars($fav['title']); ?></a>
                                        <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($fav['job_type']); ?></span>
                                        <span class="text-muted ms-2"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($fav['location']); ?></span>
                                        <span class="float-end text-muted small">le <?php echo date('d/m/Y', strtotime($fav['created_at'])); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="profile/" class="btn btn-primary btn-lg">
                    <i class="fas fa-user me-2"></i>Accéder à mon profil
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>