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
$stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE candidate_id = ?");
$stmt->execute([$user_id]);
$applications_count = $stmt->fetchColumn();

// Nombre de favoris
$stmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE candidate_id = ?");
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
    WHERE f.candidate_id = ? 
    ORDER BY f.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_favorites = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <h2 class="dashboard-title">Tableau de bord</h2>

    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stats-title">Mes candidatures</div>
            <div class="stats-number"><?php echo $applications_count; ?></div>
            <a href="applications.php" class="stats-link">Voir mes candidatures</a>
        </div>

        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stats-title">Mes favoris</div>
            <div class="stats-number"><?php echo $favorites_count; ?></div>
            <a href="favorites.php" class="stats-link">Voir mes favoris</a>
        </div>

        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stats-title">Notifications</div>
            <div class="stats-number"><?php echo $unread_notifications; ?></div>
            <a href="notifications.php" class="stats-link">Voir mes notifications</a>
        </div>
    </div>

    <div class="row">
        <!-- Dernières candidatures -->
        <div class="col-md-6 mb-4">
            <div class="activity-section">
                <div class="activity-header">
                    <h3 class="activity-title">
                        <i class="fas fa-history me-2"></i>Dernières candidatures
                    </h3>
                    <a href="applications.php" class="view-all-btn">Voir tout</a>
                </div>
                <?php if (empty($recent_applications)): ?>
                    <p class="text-muted mb-0">Aucune candidature récente.</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="activity-item">
                                <div>
                                    <div class="activity-item-title">
                                        <a href="/job-details.php?id=<?php echo $app['job_id']; ?>">
                                            <?php echo htmlspecialchars($app['title']); ?>
                                        </a>
                                    </div>
                                    <div class="activity-item-meta">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($app['location']); ?> •
                                        <span class="badge rounded-pill bg-primary-light">
                                            <?php echo htmlspecialchars($app['job_type']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="activity-item-meta">
                                    <?php echo date('d/m/Y', strtotime($app['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Derniers favoris -->
        <div class="col-md-6 mb-4">
            <div class="activity-section">
                <div class="activity-header">
                    <h3 class="activity-title">
                        <i class="fas fa-heart me-2"></i>Derniers favoris
                    </h3>
                    <a href="favorites.php" class="view-all-btn">Voir tout</a>
                </div>
                <?php if (empty($recent_favorites)): ?>
                    <p class="text-muted mb-0">Aucun favori récent.</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_favorites as $fav): ?>
                            <div class="activity-item">
                                <div>
                                    <div class="activity-item-title">
                                        <a href="/job-details.php?id=<?php echo $fav['job_id']; ?>">
                                            <?php echo htmlspecialchars($fav['title']); ?>
                                        </a>
                                    </div>
                                    <div class="activity-item-meta">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($fav['location']); ?> •
                                        <span class="badge rounded-pill bg-primary-light">
                                            <?php echo htmlspecialchars($fav['job_type']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="activity-item-meta">
                                    <?php echo date('d/m/Y', strtotime($fav['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="profile.php" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-user me-2"></i>Gérer mon profil
            </a>
            <a href="jobs.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-search me-2"></i>Parcourir les offres
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>