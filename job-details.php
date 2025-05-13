<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/header.php';

$job_id = $_GET['id'] ?? null;
if (!$job_id) {
    header('Location: jobs.php');
    exit;
}

// Récupérer les infos de l'offre
$stmt = $conn->prepare("SELECT j.*, r.company_name, r.company_logo, r.company_description FROM jobs j LEFT JOIN recruiter_profiles r ON j.recruiter_id = r.user_id WHERE j.id = ? AND j.status = 'active' LIMIT 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch();
if (!$job) {
    echo '<div class="container py-5"><div class="alert alert-danger">Offre non trouvée ou inactive.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Gestion de la candidature
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isUserType('candidate')) {
    $motivation = trim($_POST['motivation'] ?? '');
    if (empty($motivation)) {
        $error = 'La lettre de motivation est obligatoire.';
    } else {
        // Vérifier si déjà candidat
        $stmtCheck = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
        $stmtCheck->execute([$job_id, $_SESSION['user_id']]);
        if ($stmtCheck->fetch()) {
            $error = 'Vous avez déjà postulé à cette offre.';
        } else {
            $stmtApply = $conn->prepare("INSERT INTO applications (job_id, candidate_id, cover_letter, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmtApply->execute([$job_id, $_SESSION['user_id'], $motivation]);
            $success = 'Votre candidature a bien été envoyée !';
        }
    }
}
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
            <h5 class="text-muted mb-3"><?php echo htmlspecialchars($job['company_name']); ?></h5>
            <div class="mb-3">
                <span class="badge bg-primary"><?php echo htmlspecialchars($job['type']); ?></span>
                <span class="ms-3"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                <span class="ms-3"><i class="fas fa-calendar-alt"></i> Publiée le <?php echo date('d/m/Y', strtotime($job['created_at'])); ?></span>
            </div>
            <div class="mb-4">
                <h4>Description du poste</h4>
                <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            </div>
            <div class="mb-4">
                <h4>Profil recherché</h4>
                <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
            </div>
            <?php if ($job['benefits']): ?>
            <div class="mb-4">
                <h4>Avantages</h4>
                <p><?php echo nl2br(htmlspecialchars($job['benefits'])); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isLoggedIn() && isUserType('candidate')): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Postuler à cette offre</h5>
                        <form method="post">
                            <div class="mb-3">
                                <label for="motivation" class="form-label">Lettre de motivation <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="motivation" name="motivation" rows="5" required><?php echo htmlspecialchars($_POST['motivation'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer ma candidature</button>
                        </form>
                    </div>
                </div>
            <?php elseif (!isLoggedIn()): ?>
                <div class="alert alert-info">Vous devez <a href="/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">vous connecter</a> pour postuler à cette offre.</div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if ($job['company_logo']): ?>
                        <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="Logo entreprise" style="max-width: 120px; max-height: 120px;" class="mb-3">
                    <?php endif; ?>
                    <h5 class="mb-1"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                    <?php if ($job['company_description']): ?>
                        <p class="text-muted small"><?php echo htmlspecialchars($job['company_description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($job['salary_min'] || $job['salary_max']): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="mb-2">Salaire</h6>
                    <p class="mb-0">
                        <?php if ($job['salary_min']): ?>À partir de <?php echo number_format($job['salary_min'], 0, ',', ' '); ?>€<?php endif; ?>
                        <?php if ($job['salary_max']): ?> - Jusqu'à <?php echo number_format($job['salary_max'], 0, ',', ' '); ?>€<?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?> 