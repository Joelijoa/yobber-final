<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les statistiques des offres d'emploi
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_jobs,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_jobs,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_jobs
    FROM jobs 
    WHERE recruiter_id = ?
");
$stmt->execute([$user_id]);
$job_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les statistiques des candidatures
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
        SUM(CASE WHEN a.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_applications,
        SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
        SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.recruiter_id = ?
");
$stmt->execute([$user_id]);
$application_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les candidatures récentes
$stmt = $conn->prepare("
    SELECT 
        a.id as application_id,
        a.created_at as application_date,
        a.status as application_status,
        j.title as job_title,
        u.first_name,
        u.last_name,
        u.email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.candidate_id = u.id
    WHERE j.recruiter_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les offres d'emploi actives
$stmt = $conn->prepare("
    SELECT j.*, 
           COUNT(a.id) as application_count,
           DATE_FORMAT(j.created_at, '%d/%m/%Y') as posted_date
    FROM jobs j
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE j.recruiter_id = ? AND j.status = 'active'
    GROUP BY j.id
    ORDER BY j.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$active_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <h2 class="dashboard-title">Tableau de bord recruteur</h2>

    <!-- Stats Cards -->
    <div class="recruiter-stats">
        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $job_stats['total_jobs']; ?></div>
            <div class="stat-title">Total des offres</div>
        </div>
        
        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $job_stats['active_jobs']; ?></div>
            <div class="stat-title">Offres actives</div>
        </div>
        
        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $job_stats['draft_jobs']; ?></div>
            <div class="stat-title">Brouillons</div>
        </div>
        
        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $job_stats['closed_jobs']; ?></div>
            <div class="stat-title">Offres closes</div>
        </div>

        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $application_stats['total_applications']; ?></div>
            <div class="stat-title">Total candidatures</div>
        </div>

        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $application_stats['pending_applications']; ?></div>
            <div class="stat-title">En attente</div>
        </div>

        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $application_stats['accepted_applications']; ?></div>
            <div class="stat-title">Acceptées</div>
        </div>

        <div class="recruiter-stat-card">
            <div class="stat-number"><?php echo $application_stats['rejected_applications']; ?></div>
            <div class="stat-title">Refusées</div>
        </div>
    </div>

    <div class="row">
        <!-- Dernières candidatures -->
        <div class="col-md-6 mb-4">
            <div class="activity-section">
                <div class="activity-header">
                    <h3 class="activity-title">
                        <i class="fas fa-users me-2"></i>Dernières candidatures
                    </h3>
                    <a href="applications.php" class="view-all-btn">Voir tout</a>
                </div>
                <div class="activity-list">
                    <?php if (empty($recent_applications)): ?>
                        <p class="text-muted mb-0">Aucune candidature récente</p>
                    <?php else: ?>
                        <?php foreach ($recent_applications as $application): ?>
                            <div class="activity-item">
                                <div>
                                    <div class="activity-item-title">
                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                        <span class="status-badge <?php echo $application['application_status']; ?>">
                                            <?php echo ucfirst($application['application_status']); ?>
                                        </span>
                                    </div>
                                    <div class="activity-item-meta">
                                        <?php echo htmlspecialchars($application['job_title']); ?>
                                    </div>
                                </div>
                                <div class="activity-item-meta">
                                    <?php echo date('d/m/Y', strtotime($application['application_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Offres d'emploi actives -->
        <div class="col-md-6 mb-4">
            <div class="activity-section">
                <div class="activity-header">
                    <h3 class="activity-title">
                        <i class="fas fa-briefcase me-2"></i>Offres d'emploi actives
                    </h3>
                    <a href="jobs.php" class="view-all-btn">Voir tout</a>
                </div>
                <div class="activity-list">
                    <?php if (empty($active_jobs)): ?>
                        <p class="text-muted mb-0">Aucune offre d'emploi active</p>
                    <?php else: ?>
                        <?php foreach ($active_jobs as $job): ?>
                            <div class="activity-item">
                                <div>
                                    <div class="activity-item-title">
                                        <a href="view_job.php?id=<?php echo $job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </div>
                                    <div class="activity-item-meta">
                                        <?php echo htmlspecialchars($job['company_name']); ?> •
                                        <?php echo $job['application_count']; ?> candidature(s)
                                    </div>
                                </div>
                                <div class="activity-item-meta">
                                    <?php echo $job['posted_date']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 