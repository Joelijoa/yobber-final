<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();

// Récupérer les informations de l'utilisateur et son profil
$stmt = $conn->prepare("
    SELECT u.*, c.* 
    FROM users u 
    LEFT JOIN candidates c ON u.id = c.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Nombre de candidatures
$stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
$stmt->execute([$user_id]);
$applications_count = $stmt->fetchColumn();

// Nombre de favoris
$stmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites_count = $stmt->fetchColumn();

// Compter les notifications non lues
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
$stmt->execute([$user_id]);
$unread_notifications = $stmt->fetchColumn();

// Dernières candidatures
$stmt = $conn->prepare("
    SELECT a.*, j.title, j.location, j.type as job_type, j.id as job_id 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.user_id = ? 
    ORDER BY a.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_applications = $stmt->fetchAll();

// Derniers favoris
$stmt = $conn->prepare("
    SELECT f.*, j.title, j.location, j.type as job_type, j.id as job_id 
    FROM favorites f 
    JOIN jobs j ON f.job_id = j.id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_favorites = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h1 class="mb-4">Tableau de bord</h1>

    <?php if ($flash_message = get_flash_message()): ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>">
            <?php echo $flash_message['message']; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-paper-plane fa-2x text-primary mb-3"></i>
                    <h3 class="card-title h5">Mes candidatures</h3>
                    <p class="display-6 fw-bold mb-0"><?php echo $applications_count; ?></p>
                    <a href="applications.php" class="btn btn-outline-primary mt-3">
                        Voir mes candidatures
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-heart fa-2x text-danger mb-3"></i>
                    <h3 class="card-title h5">Mes favoris</h3>
                    <p class="display-6 fw-bold mb-0"><?php echo $favorites_count; ?></p>
                    <a href="favorites.php" class="btn btn-outline-danger mt-3">
                        Voir mes favoris
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-bell fa-2x text-warning mb-3"></i>
                    <h3 class="card-title h5">Notifications</h3>
                    <p class="display-6 fw-bold mb-0"><?php echo $unread_notifications; ?></p>
                    <a href="notifications.php" class="btn btn-outline-warning mt-3">
                        Voir mes notifications
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-paper-plane me-2"></i>Dernières candidatures
                </div>
                <div class="card-body">
                    <?php if (empty($recent_applications)): ?>
                        <p class="text-muted mb-0">Aucune candidature récente.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_applications as $app): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">
                                            <a href="/job-details.php?id=<?php echo $app['job_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($app['title']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($app['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($app['job_type']); ?></span>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($app['location']); ?>
                                        </small>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-heart me-2"></i>Derniers favoris
                </div>
                <div class="card-body">
                    <?php if (empty($recent_favorites)): ?>
                        <p class="text-muted mb-0">Aucun favori récent.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_favorites as $fav): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">
                                            <a href="/job-details.php?id=<?php echo $fav['job_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($fav['title']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($fav['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($fav['job_type']); ?></span>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($fav['location']); ?>
                                        </small>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="profile.php" class="btn btn-primary btn-lg">
                <i class="fas fa-user me-2"></i>Gérer mon profil
            </a>
            <a href="/jobs.php" class="btn btn-success btn-lg ms-3">
                <i class="fas fa-search me-2"></i>Parcourir les offres
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>