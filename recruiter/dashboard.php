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

// Récupérer les dernières candidatures
$stmt = $conn->prepare("
    SELECT a.*, j.title as job_title, 
           c.first_name, c.last_name,
           DATE_FORMAT(a.created_at, '%d/%m/%Y') as application_date
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN candidates c ON a.candidate_id = c.user_id
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

<div class="container mt-4">
    <h2>Tableau de bord recruteur</h2>

    <!-- Statistiques des offres d'emploi -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total des offres</h5>
                    <h2 class="card-text"><?php echo $job_stats['total_jobs']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Offres actives</h5>
                    <h2 class="card-text"><?php echo $job_stats['active_jobs']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Brouillons</h5>
                    <h2 class="card-text"><?php echo $job_stats['draft_jobs']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">Offres closes</h5>
                    <h2 class="card-text"><?php echo $job_stats['closed_jobs']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques des candidatures -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total candidatures</h5>
                    <h2 class="card-text"><?php echo $application_stats['total_applications']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">En attente</h5>
                    <h2 class="card-text"><?php echo $application_stats['pending_applications']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Acceptées</h5>
                    <h2 class="card-text"><?php echo $application_stats['accepted_applications']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Refusées</h5>
                    <h2 class="card-text"><?php echo $application_stats['rejected_applications']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Dernières candidatures -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dernières candidatures</h5>
                    <a href="applications.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_applications)): ?>
                        <p class="text-muted">Aucune candidature récente</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_applications as $application): ?>
                                <a href="view_application.php?id=<?php echo $application['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($application['job_title']); ?></h6>
                                        <small class="text-muted"><?php echo $application['application_date']; ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                    </p>
                                    <small class="text-muted">
                                        Statut : 
                                        <span class="badge bg-<?php 
                                            echo match($application['status']) {
                                                'pending' => 'warning',
                                                'reviewed' => 'info',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo $application['status']; ?>
                                        </span>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Offres d'emploi actives -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Offres d'emploi actives</h5>
                    <a href="jobs.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($active_jobs)): ?>
                        <p class="text-muted">Aucune offre d'emploi active</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($active_jobs as $job): ?>
                                <a href="view_job.php?id=<?php echo $job['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                        <small class="text-muted"><?php echo $job['posted_date']; ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    <small class="text-muted">
                                        <?php echo $job['application_count']; ?> candidature(s)
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 