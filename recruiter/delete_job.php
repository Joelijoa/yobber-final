<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    set_flash_message('error', 'Accès non autorisé.');
    redirect('/recruiter/jobs.php');
    exit;
}

// Vérifier si la requête est en POST et si l'ID de l'offre est fourni
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    set_flash_message('error', 'Méthode non autorisée ou ID de l\'offre invalide.');
    redirect('/recruiter/jobs.php');
    exit;
}

try {
    // Initialiser la connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    $job_id = (int)$_POST['job_id'];
    $user_id = getUserId();

    // Commencer une transaction
    $conn->beginTransaction();

    // Vérifier que l'offre appartient bien au recruteur
    $stmt = $conn->prepare("SELECT recruiter_id FROM jobs WHERE id = :job_id");
    $stmt->execute(['job_id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job || $job['recruiter_id'] != $user_id) {
        throw new Exception('Vous n\'êtes pas autorisé à supprimer cette offre.');
    }

    // Supprimer les candidatures associées à cette offre
    $stmt = $conn->prepare("DELETE FROM applications WHERE job_id = :job_id");
    $stmt->execute(['job_id' => $job_id]);

    // Supprimer l'offre
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = :job_id AND recruiter_id = :recruiter_id");
    $stmt->execute([
        'job_id' => $job_id,
        'recruiter_id' => $user_id
    ]);

    $conn->commit();
    set_flash_message('success', 'L\'offre a été supprimée avec succès.');

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    set_flash_message('error', 'Une erreur est survenue lors de la suppression de l\'offre : ' . $e->getMessage());
} finally {
    redirect('/recruiter/jobs.php');
    exit;
} 