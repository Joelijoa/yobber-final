<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id'])) {
    header('Location: jobs.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = $_POST['job_id'];

try {
    $conn->beginTransaction();

    // Vérifier que l'offre appartient bien au recruteur
    $stmt = $conn->prepare("
        SELECT * FROM jobs 
        WHERE id = ? AND recruiter_id = ?
    ");
    $stmt->execute([$job_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Offre non trouvée.");
    }

    // Supprimer les candidatures associées
    $stmt = $conn->prepare("DELETE FROM applications WHERE job_id = ?");
    $stmt->execute([$job_id]);

    // Supprimer les favoris associés
    $stmt = $conn->prepare("DELETE FROM favorites WHERE job_id = ?");
    $stmt->execute([$job_id]);

    // Supprimer l'offre
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND recruiter_id = ?");
    $stmt->execute([$job_id, $user_id]);

    $conn->commit();
    $_SESSION['success_message'] = "L'offre a été supprimée avec succès.";
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error_message'] = "Erreur lors de la suppression de l'offre : " . $e->getMessage();
}

header('Location: jobs.php');
exit(); 