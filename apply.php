<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
requireAccess('candidate', '/auth/login.php');

$user_id = getUserId();
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Vérifier si l'offre existe
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'active'");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: /jobs.php');
    exit();
}

// Vérifier si le candidat n'a pas déjà postulé
$stmt = $conn->prepare("SELECT * FROM applications WHERE job_id = ? AND candidate_id = ?");
$stmt->execute([$job_id, $user_id]);
if ($stmt->fetch()) {
    set_flash_message('error', 'Vous avez déjà postulé à cette offre.');
    header('Location: /job-details.php?id=' . $job_id);
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Vérifier les fichiers
        if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Le CV est obligatoire.');
        }
        if (!isset($_FILES['cover_letter']) || $_FILES['cover_letter']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('La lettre de motivation est obligatoire.');
        }

        // Vérifier les types de fichiers
        $allowed_types = ['application/pdf'];
        if (!in_array($_FILES['cv']['type'], $allowed_types)) {
            throw new Exception('Le CV doit être au format PDF.');
        }
        if (!in_array($_FILES['cover_letter']['type'], $allowed_types)) {
            throw new Exception('La lettre de motivation doit être au format PDF.');
        }

        // Créer les dossiers si nécessaire
        $upload_dir = __DIR__ . '/uploads/applications/' . $job_id . '/' . $user_id . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Enregistrer les fichiers
        $cv_name = 'cv_' . time() . '.pdf';
        $cover_letter_name = 'cover_letter_' . time() . '.pdf';

        move_uploaded_file($_FILES['cv']['tmp_name'], $upload_dir . $cv_name);
        move_uploaded_file($_FILES['cover_letter']['tmp_name'], $upload_dir . $cover_letter_name);

        // Créer la candidature
        $stmt = $conn->prepare("
            INSERT INTO applications (job_id, candidate_id, cv_path, cover_letter_path, message, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $job_id,
            $user_id,
            'uploads/applications/' . $job_id . '/' . $user_id . '/' . $cv_name,
            'uploads/applications/' . $job_id . '/' . $user_id . '/' . $cover_letter_name,
            $_POST['message'] ?? ''
        ]);

        $conn->commit();
        set_flash_message('success', 'Votre candidature a été envoyée avec succès !');
        header('Location: /candidate/applications.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h2>Postuler à l'offre : <?php echo htmlspecialchars($job['title']); ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="cv" class="form-label">CV (PDF uniquement)</label>
                    <input type="file" class="form-control" id="cv" name="cv" accept=".pdf" required>
                </div>

                <div class="mb-3">
                    <label for="cover_letter" class="form-label">Lettre de motivation (PDF uniquement)</label>
                    <input type="file" class="form-control" id="cover_letter" name="cover_letter" accept=".pdf" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message (optionnel)</label>
                    <textarea class="form-control" id="message" name="message" rows="4"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Envoyer ma candidature</button>
                <a href="/job-details.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">Retour à l'offre</a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 