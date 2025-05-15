<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';

// Vérifier la connexion à la base de données
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer les dernières offres d'emploi actives
try {
    $stmt = $pdo->prepare("SELECT j.*, r.company_name, r.company_logo
        FROM jobs j
        LEFT JOIN recruiter_profiles r ON j.recruiter_id = r.user_id
        WHERE j.status = 'active'
        ORDER BY j.created_at DESC
        LIMIT 6");
    $stmt->execute();
    $latest_jobs = $stmt->fetchAll();
} catch (Exception $e) {
    $latest_jobs = [];
    if (DEBUG_MODE) {
        echo '<div class="alert alert-warning">Erreur lors de la récupération des offres : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!-- Hero Section -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Trouvez votre prochain emploi de rêve</h1>
                <p class="lead mb-4">Des milliers d'offres d'emploi vous attendent. Commencez votre recherche dès maintenant.</p>
                <div class="search-box bg-white p-4 rounded-3 shadow-sm">
                    <form action="jobs.php" method="GET" class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" name="q" placeholder="Métier, compétence ou entreprise">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-map-marker-alt text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" name="location" placeholder="Ville ou région">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/images/job-image.jpg" alt="Job Search" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Dernières offres -->
<section class="latest-jobs-section py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Dernières Offres</h2>
            <a href="jobs.php" class="btn btn-outline-primary">Voir toutes les offres</a>
        </div>
        <div class="row g-4">
            <?php if (count($latest_jobs) > 0): ?>
                <?php foreach ($latest_jobs as $job): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if (!empty($job['company_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>" class="company-logo me-3" style="width: 50px; height: 50px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="company-logo-placeholder me-3 bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-building text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <h6 class="card-subtitle text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                                    </div>
                                </div>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                    <?php echo htmlspecialchars($job['location']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($job['type']); ?></span>
                                    <small class="text-muted">Publié le <?php echo date('d/m/Y', strtotime($job['created_at'])); ?></small>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary w-100">Voir l'offre</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">Aucune offre d'emploi récente pour le moment.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Carrousel entreprises partenaires -->
<section class="partners-carousel-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Entreprises partenaires</h2>
            <a href="/companies.php" class="btn btn-outline-primary">Voir la liste des entreprises</a>
        </div>
        <div id="partnersCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="d-flex justify-content-center gap-5">
                        <img src="assets/images/Entreprise1.png" alt="Entreprise 1" style="height: 80px;">
                        <img src="assets/images/Entreprise2.png" alt="Entreprise 2" style="height: 80px;">
                        <img src="assets/images/Entreprise3.png" alt="Entreprise 3" style="height: 80px;">
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="d-flex justify-content-center gap-5">
                        <img src="assets/images/Entreprise4.jpg" alt="Entreprise 4" style="height: 80px;">
                        <img src="assets/images/Entreprise5.png" alt="Entreprise 5" style="height: 80px;">
                        <img src="assets/images/Entreprise6.png" alt="Entreprise 6" style="height: 80px;">
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#partnersCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#partnersCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Suivant</span>
            </button>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-4">Prêt à trouver votre prochain emploi ?</h2>
        <p class="lead mb-4">Rejoignez des milliers de candidats et recruteurs sur Yobber.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/public/auth/register.php?type=candidate" class="btn btn-light btn-lg">Je cherche un emploi</a>
            <a href="/public/auth/register.php?type=recruiter" class="btn btn-outline-light btn-lg">Je recrute</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<style>
.hero-section {
    background: linear-gradient(135deg, #f8fafc, #e3e9f7);
    color: #222;
    padding: 100px 0 60px 0;
    margin-top: -1.5rem;
}
.company-logo-placeholder {
    background-color: #f3f3f3;
    border-radius: 0.5rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>