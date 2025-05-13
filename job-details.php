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
$stmt = $conn->prepare("
    SELECT j.*, r.company_name, r.company_logo, r.company_description, j.recruiter_id as recruiter_user_id 
    FROM jobs j 
    LEFT JOIN recruiter_profiles r ON j.recruiter_id = r.user_id 
    WHERE j.id = ? AND j.status = 'active' 
    LIMIT 1
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    echo '<div class="container py-5"><div class="alert alert-danger">Offre non trouvée ou inactive.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Vérifier si l'offre est en favoris
$is_favorite = false;
if (isLoggedIn() && isUserType('candidate')) {
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([$job_id, getUserId()]);
    $is_favorite = (bool)$stmt->fetch();
}

// Gestion de la candidature
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isUserType('candidate')) {
    try {
        // Vérifier si déjà candidat
        $stmtCheck = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
        $stmtCheck->execute([$job_id, getUserId()]);
        if ($stmtCheck->fetch()) {
            throw new Exception('Vous avez déjà postulé à cette offre.');
        }

        // Vérifier les fichiers
        if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Le CV est requis et doit être au format PDF.');
        }
        if (!isset($_FILES['cover_letter']) || $_FILES['cover_letter']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('La lettre de motivation est requise et doit être au format PDF.');
        }

        // Créer le dossier de destination
        $upload_dir = __DIR__ . '/uploads/applications/' . $job_id . '/' . getUserId();
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Sauvegarder les fichiers
        $cv_name = 'cv_' . uniqid() . '.pdf';
        $cover_letter_name = 'cover_letter_' . uniqid() . '.pdf';
        
        move_uploaded_file($_FILES['cv']['tmp_name'], $upload_dir . '/' . $cv_name);
        move_uploaded_file($_FILES['cover_letter']['tmp_name'], $upload_dir . '/' . $cover_letter_name);

        // Insérer la candidature
        $conn->beginTransaction();

        // Chemins relatifs pour la BD
        $cv_path = 'uploads/applications/' . $job_id . '/' . getUserId() . '/' . $cv_name;
        $cover_letter_path = 'uploads/applications/' . $job_id . '/' . getUserId() . '/' . $cover_letter_name;

        // Insérer la candidature
        $stmt = $conn->prepare("
            INSERT INTO applications (job_id, candidate_id, cv_path, cover_letter_path, message, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$job_id, getUserId(), $cv_path, $cover_letter_path, $_POST['message'] ?? '']);

        // Créer la notification
        if ($job['recruiter_user_id']) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, created_at)
                VALUES (?, 'new_application', ?, NOW())
            ");
            $stmt->execute([
                $job['recruiter_user_id'],
                'Nouvelle candidature pour le poste : ' . $job['title']
            ]);
        }

        $conn->commit();
        $success = 'Votre candidature a été envoyée avec succès !';
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage();
        if (DEBUG_MODE) {
            error_log('Erreur candidature : ' . $e->getMessage());
        }
    }
}
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <h5 class="text-muted mb-3"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                </div>
                <?php if (isLoggedIn() && isUserType('candidate')): ?>
                <button type="button" 
                        class="btn <?php echo $is_favorite ? 'btn-danger' : 'btn-outline-danger'; ?> favorite-btn" 
                        onclick="toggleFavorite(this)" 
                        data-job-id="<?php echo $job_id; ?>"
                        data-is-favorite="<?php echo $is_favorite ? 'true' : 'false'; ?>">
                    <i class="fas fa-heart"></i>
                    <span class="favorite-text"><?php echo $is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?></span>
                </button>
                <?php endif; ?>
            </div>
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
                        <?php if (DEBUG_MODE && isset($_FILES) && !empty($_FILES)): ?>
                            <div class="alert alert-info">
                                <h6>Debug Information:</h6>
                                <pre><?php print_r($_FILES); ?></pre>
                            </div>
                        <?php endif; ?>
                        <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>">
                            <div class="mb-3">
                                <label for="cv" class="form-label">CV (PDF uniquement) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="cv" name="cv" accept="application/pdf,.pdf" required>
                                <div class="form-text">Taille maximale : <?php echo ini_get('upload_max_filesize'); ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="cover_letter" class="form-label">Lettre de motivation (PDF uniquement) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="cover_letter" name="cover_letter" accept="application/pdf,.pdf" required>
                                <div class="form-text">Taille maximale : <?php echo ini_get('upload_max_filesize'); ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message complémentaire</label>
                                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Ajoutez un message pour accompagner votre candidature..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer ma candidature
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif (!isLoggedIn()): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Vous devez <a href="/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">vous connecter</a> pour postuler à cette offre.
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if ($job['company_logo']): ?>
                        <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="Logo <?php echo htmlspecialchars($job['company_name']); ?>" class="img-fluid mb-3" style="max-width: 120px; max-height: 120px;">
                    <?php else: ?>
                        <div class="company-placeholder mb-3">
                            <i class="fas fa-building fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    <h5 class="mb-2"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                    <?php if ($job['company_description']): ?>
                        <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($job['company_description'])); ?></p>
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

<script>
function validateForm() {
    const cv = document.getElementById('cv').files[0];
    const coverLetter = document.getElementById('cover_letter').files[0];
    const maxSize = <?php echo return_bytes(ini_get('upload_max_filesize')); ?>;
    
    if (!cv || !coverLetter) {
        alert('Veuillez sélectionner tous les fichiers requis.');
        return false;
    }
    
    if (cv.size > maxSize) {
        alert('Le CV est trop volumineux. Taille maximale : ' + formatBytes(maxSize));
        return false;
    }
    
    if (coverLetter.size > maxSize) {
        alert('La lettre de motivation est trop volumineuse. Taille maximale : ' + formatBytes(maxSize));
        return false;
    }
    
    if (!cv.type.match('application/pdf') && !cv.name.toLowerCase().endsWith('.pdf')) {
        alert('Le CV doit être au format PDF.');
        return false;
    }
    
    if (!coverLetter.type.match('application/pdf') && !coverLetter.name.toLowerCase().endsWith('.pdf')) {
        alert('La lettre de motivation doit être au format PDF.');
        return false;
    }
    
    return true;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function toggleFavorite(button) {
    const jobId = button.dataset.jobId;
    const isFavorite = button.dataset.isFavorite === 'true';
    
    fetch('/favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `job_id=${jobId}&is_favorite=${isFavorite}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                button.classList.remove('btn-outline-danger');
                button.classList.add('btn-danger');
            } else {
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-danger');
            }
            button.dataset.isFavorite = data.isFavorite;
            button.querySelector('.favorite-text').textContent = data.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
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

<?php require_once __DIR__ . '/includes/footer.php'; ?> 